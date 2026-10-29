import json
import os
import logging
import socket
import subprocess
import sys
import tkinter as tk
import threading
import queue
import time
from datetime import datetime
from pathlib import Path
from dotenv import load_dotenv

load_dotenv()
load_dotenv(Path(__file__).resolve().parent.parent / ".env")

from ai.classifier import Classifier
from ai.card_detector import CardDetector
from ai.detector import Detector
from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
from ai.violation_events import ViolationEventDetector, combined_status
from ai.brightness import BrightnessNormalizer
from desktop.camera import Camera
from desktop.violation_sender import ApiConfig, ViolationSender, check_connection
import ai.config as my_config

CONFIG_FILE_PATH = os.path.join(os.path.dirname(__file__), "config.json")
OUTBOX_DIR = Path(__file__).resolve().parent / "outbox"

# Environment variables override config.json for the API connection (useful for deployments).
API_ENV = {
    "API_URL": "CIM_DESKTOP_API_URL",
    "CAMERA_CODE": "CIM_DESKTOP_CAMERA_CODE",
}


class App:
    """
    Application backend logic.
    Manages AI pipeline, camera, configuration, and real-time statistics.
    The UI layer (CampusMonitorUI) delegates all processing calls to this class.
    """

    def __init__(self):
        self.uniform_classifier = Classifier(
            model_path=my_config.CLASSIFY_UNIFORM_PATH,
            num_class=len(my_config.UNIFORM_LABELS),
            labels=my_config.UNIFORM_LABELS
        )
        self.card_detector = CardDetector(
            model_path=my_config.DETECT_CARD_PATH,
            device=my_config.DEVICE,
            conf=my_config.DETECT_CARD_CONF,
            iou=my_config.DETECT_CARD_IOU,
            image_size=my_config.DETECT_CARD_IMAGE_SIZE
        )
        self.card_classifier = None
        self.classifier = self.uniform_classifier
        self.detector = Detector(
            model_path=my_config.DETECT_PERSON_PATH,
            device=my_config.DEVICE,
            conf=my_config.DETECT_CONF,
            iou=my_config.IOU_THRESHOLD
        )
        self.pipeline = Pipeline(
            detector=self.detector,
            uniform_classifier=self.uniform_classifier,
            card_detector=self.card_detector
        )
        self.voting = TemporalVoting()
        self.event_detector = ViolationEventDetector()
        self.brightness = BrightnessNormalizer()
        self.camera: Camera | None = None

        self.is_running = False
        self.frame_queue = queue.Queue(maxsize=2)
        self.result_queue = queue.Queue(maxsize=2)
        self.alert_queue: queue.Queue = queue.Queue(maxsize=200)
        self.worker_thread = None
        self._state_lock = threading.Lock()

        # Whether violation events may be recorded for the current session.
        self.recording_enabled = False
        self.recording_note = "Chưa kiểm tra kết nối API."
        self.camera_info: dict | None = None

        # Loaded once at startup; UI sliders may update these values later
        self.params: dict = {}
        self._spawned_api_process: subprocess.Popen | None = None
        self.load_parameters()
        self.ensure_local_api()

        self.sender = ViolationSender(OUTBOX_DIR, self.api_config())
        self.sender.start()
        try:
            self.check_api()
        except Exception:
            pass

    # ------------------------------------------------------------------
    # Configuration Management
    # ------------------------------------------------------------------

    def load_parameters(self) -> dict:
        """Loads configuration from config.json, falling back to ai.config defaults."""
        self.params = {
            "DETECT_CONF":               my_config.DETECT_CONF,
            "CLASSIFY_CONF":             my_config.CLASSIFY_CONF,
            "NON_UNIFORM_CONF":          getattr(my_config, "NON_UNIFORM_CONF", 0.7),
            "MIN_PERSON_HEIGHT_RATIO":   getattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.25),
            "LABEL_HOLD_SECONDS":        getattr(my_config, "LABEL_HOLD_SECONDS", 2),
            "DETECT_CARD_CONF":          getattr(my_config, "DETECT_CARD_CONF", 0.3),
            "DETECT_CARD_IOU":           getattr(my_config, "DETECT_CARD_IOU", 0.3),
            "DETECT_CARD_IMAGE_SIZE":    getattr(my_config, "DETECT_CARD_IMAGE_SIZE", 224),
            "IOU_THRESHOLD":             my_config.IOU_THRESHOLD,
            "FRAME_SKIP":                my_config.FRAME_SKIP,
            "BRIGHTNESS_ENABLED":        getattr(my_config, "BRIGHTNESS_ENABLED", 1),
            "LEN_HISTORY":               my_config.LEN_HISTORY,
            "VOTING_THREDSHOLD":         my_config.VOTING_THREDSHOLD,
            "VIOLATION_RATIO":           my_config.VIOLATION_RATIO,
            "MISSING_COUNTER_THRESHOLD": my_config.MISSING_COUNTER_THRESHOLD,
            "EVENT_DEDUP_SECONDS":       my_config.EVENT_DEDUP_SECONDS,
            "API_URL":                   "http://127.0.0.1:8001",
            "CAMERA_CODE":               "",
        }

        if os.path.exists(CONFIG_FILE_PATH):
            try:
                with open(CONFIG_FILE_PATH, "r", encoding="utf-8") as f:
                    saved = json.load(f)
                    self.params.update({k: v for k, v in saved.items() if k in self.params})
            except Exception as e:
                logging.error(f"Error loading config: {e}")

        for key, env_name in API_ENV.items():
            if os.getenv(env_name):
                self.params[key] = os.getenv(env_name).strip()

        self.params = self.validate_params(self.params)
        self.apply_params_to_config()
        return self.params

    @staticmethod
    def validate_params(params: dict) -> dict:
        """Clamp numeric values to safe ranges; raise ValueError on unusable input."""
        p = dict(params)
        p["DETECT_CONF"] = min(max(float(p["DETECT_CONF"]), 0.05), 1.0)
        p["CLASSIFY_CONF"] = min(max(float(p["CLASSIFY_CONF"]), 0.0), 1.0)
        p["NON_UNIFORM_CONF"] = min(max(float(p.get("NON_UNIFORM_CONF", 0.7)), 0.0), 1.0)
        p["MIN_PERSON_HEIGHT_RATIO"] = min(max(float(p.get("MIN_PERSON_HEIGHT_RATIO", 0.25)), 0.05), 0.80)
        p["LABEL_HOLD_SECONDS"] = min(max(int(p.get("LABEL_HOLD_SECONDS", 2)), 0), 60)
        p["DETECT_CARD_CONF"] = min(max(float(p.get("DETECT_CARD_CONF", 0.3)), 0.05), 1.0)
        p["DETECT_CARD_IOU"] = min(max(float(p.get("DETECT_CARD_IOU", 0.3)), 0.05), 1.0)
        p["DETECT_CARD_IMAGE_SIZE"] = int(p.get("DETECT_CARD_IMAGE_SIZE", 224))
        p["IOU_THRESHOLD"] = min(max(float(p["IOU_THRESHOLD"]), 0.1), 1.0)
        p["FRAME_SKIP"] = min(max(int(p["FRAME_SKIP"]), 1), 30)
        p["BRIGHTNESS_ENABLED"] = 1 if int(p.get("BRIGHTNESS_ENABLED", 1)) else 0
        p["LEN_HISTORY"] = min(max(int(p["LEN_HISTORY"]), 3), 100)
        p["VOTING_THREDSHOLD"] = min(max(int(p["VOTING_THREDSHOLD"]), 1), p["LEN_HISTORY"])
        p["VIOLATION_RATIO"] = min(max(float(p["VIOLATION_RATIO"]), 0.5), 1.0)
        p["MISSING_COUNTER_THRESHOLD"] = min(max(int(p["MISSING_COUNTER_THRESHOLD"]), 1), 300)
        p["EVENT_DEDUP_SECONDS"] = min(max(int(p["EVENT_DEDUP_SECONDS"]), 10), 86400)
        p["API_URL"] = str(p.get("API_URL") or "").strip().rstrip("/")
        if p["API_URL"] and not p["API_URL"].startswith(("http://", "https://")):
            raise ValueError("Địa chỉ API phải bắt đầu bằng http:// hoặc https://")
        p["CAMERA_CODE"] = str(p.get("CAMERA_CODE") or "").strip().upper()
        return p

    def save_parameters(self, new_params: dict) -> None:
        """Persists updated parameters to config.json and applies them immediately."""
        merged = self.validate_params({**self.params, **new_params})
        try:
            with open(CONFIG_FILE_PATH, "w", encoding="utf-8") as f:
                json.dump(merged, f, indent=4, ensure_ascii=False)
        except Exception as e:
            logging.error(f"Error saving config: {e}")
            raise
        history_changed = merged["LEN_HISTORY"] != self.params.get("LEN_HISTORY")
        self.params = merged
        self.apply_params_to_config()
        if history_changed:
            with self._state_lock:
                self.voting.reset(self.params["LEN_HISTORY"])
        self.sender.update_config(self.api_config())

    def apply_params_to_config(self) -> None:
        """Pushes current params into ai.config and live model components."""
        my_config.DETECT_CONF               = self.params["DETECT_CONF"]
        my_config.CLASSIFY_CONF             = self.params["CLASSIFY_CONF"]
        my_config.NON_UNIFORM_CONF          = self.params["NON_UNIFORM_CONF"]
        my_config.MIN_PERSON_HEIGHT_RATIO   = self.params["MIN_PERSON_HEIGHT_RATIO"]
        my_config.LABEL_HOLD_SECONDS        = self.params["LABEL_HOLD_SECONDS"]
        my_config.DETECT_CARD_CONF          = self.params["DETECT_CARD_CONF"]
        my_config.DETECT_CARD_IOU           = self.params["DETECT_CARD_IOU"]
        my_config.DETECT_CARD_IMAGE_SIZE    = self.params["DETECT_CARD_IMAGE_SIZE"]
        my_config.IOU_THRESHOLD             = self.params["IOU_THRESHOLD"]
        my_config.FRAME_SKIP                = self.params["FRAME_SKIP"]
        my_config.BRIGHTNESS_ENABLED        = self.params.get("BRIGHTNESS_ENABLED", 1)
        my_config.LEN_HISTORY               = self.params["LEN_HISTORY"]
        my_config.VOTING_THREDSHOLD         = self.params["VOTING_THREDSHOLD"]
        my_config.VIOLATION_RATIO           = self.params["VIOLATION_RATIO"]
        my_config.MISSING_COUNTER_THRESHOLD = self.params["MISSING_COUNTER_THRESHOLD"]
        my_config.EVENT_DEDUP_SECONDS       = self.params["EVENT_DEDUP_SECONDS"]

        if getattr(self, "detector", None):
            self.detector.conf = self.params["DETECT_CONF"]
            self.detector.iou = self.params["IOU_THRESHOLD"]
        if getattr(self, "card_detector", None):
            self.card_detector.conf = self.params["DETECT_CARD_CONF"]
            self.card_detector.iou = self.params["DETECT_CARD_IOU"]
            self.card_detector.image_size = self.params["DETECT_CARD_IMAGE_SIZE"]
        if getattr(self, "voting", None):
            self.voting.len_history = self.params["LEN_HISTORY"]
        if getattr(self, "event_detector", None):
            self.event_detector.dedup_seconds = self.params["EVENT_DEDUP_SECONDS"]

    def _is_port_in_use(self, port: int = 8001, host: str = "127.0.0.1") -> bool:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.settimeout(0.5)
            return s.connect_ex((host, port)) == 0

    def ensure_local_api(self) -> bool:
        """
        If API_URL points to local port 8001 and port is not listening,
        spawns FastAPI backend as a background process automatically.
        """
        api_url = str(self.params.get("API_URL", "")).lower()
        if not ("127.0.0.1:8001" in api_url or "localhost:8001" in api_url):
            return False  # Remote production server (e.g. https://cim-api.huit.edu.vn)

        if self._is_port_in_use(8001):
            return True

        logging.info("Port 8001 chưa chạy. Đang tự động khởi chạy FastAPI backend ngầm...")
        final_system_dir = Path(__file__).resolve().parent.parent
        cmd = [sys.executable, "-m", "uvicorn", "api.main:app", "--host", "127.0.0.1", "--port", "8001"]
        flags = 0
        if sys.platform == "win32":
            flags = subprocess.CREATE_NO_WINDOW

        env = os.environ.copy()
        env["PYTHONPATH"] = str(final_system_dir)
        env["PYTHONIOENCODING"] = "utf-8"

        try:
            self._spawned_api_process = subprocess.Popen(
                cmd,
                cwd=str(final_system_dir),
                env=env,
                creationflags=flags
            )
            for _ in range(25):
                time.sleep(0.2)
                if self._is_port_in_use(8001):
                    logging.info("FastAPI backend (Port 8001) đã sẵn sàng phục vụ.")
                    return True
        except Exception as e:
            logging.error(f"Không thể tự động khởi động FastAPI: {e}")

        return False

    def api_config(self) -> ApiConfig:
        return ApiConfig(base_url=self.params["API_URL"], camera_code=self.params["CAMERA_CODE"])

    def check_api(self) -> tuple[bool, str]:
        """Validate API URL + camera code. Decides whether events may be recorded."""
        self.ensure_local_api()
        ok, message, data = check_connection(self.api_config())
        if ok:
            self.camera_info = data
            self.recording_enabled = True
            self.recording_note = message
        elif "ngừng sử dụng" in message or "không hợp lệ" in message or "Chưa cấu hình" in message:
            # Inactive camera/area or wrong credentials: never record events for this session.
            self.camera_info = None
            self.recording_enabled = False
            self.recording_note = message
        else:
            # Network/API temporarily unavailable: keep recording into the outbox, send later.
            self.recording_enabled = bool(self.api_config().configured)
            self.recording_note = message + (" Bản ghi sẽ được lưu tạm và gửi lại khi có kết nối."
                                             if self.recording_enabled else "")
        return ok, self.recording_note

    def camera_label(self) -> str:
        if self.camera_info and self.camera_info.get("camera", {}).get("name"):
            return self.camera_info["camera"]["name"]
        return self.params.get("CAMERA_NAME") or "Camera 1 - Camera Desktop Local"

    # ------------------------------------------------------------------
    # Camera / Stream Control
    # ------------------------------------------------------------------

    def start_camera(self, source) -> None:
        """Opens the video source (int index for webcam, str path for file)."""
        self.camera = Camera(source)
        # A new source starts with clean tracks, votes and anti-repeat memory.
        with self._state_lock:
            self.detector.reset_tracker()
            self.voting.reset(self.params["LEN_HISTORY"])
            self.event_detector.reset()
            self.brightness.reset()
        self.is_running = True
        self.frame_queue = queue.Queue(maxsize=2)
        self.result_queue = queue.Queue(maxsize=2)
        self.worker_thread = threading.Thread(target=self._ai_worker, daemon=True)
        self.worker_thread.start()

    def stop_camera(self) -> None:
        """Releases the active camera / video capture."""
        self.is_running = False
        if self.worker_thread:
            self.worker_thread.join(timeout=5.0)
            self.worker_thread = None
        if self.camera:
            self.camera.release()
            self.camera = None

    def read_frame(self):
        """Returns the next frame from the camera, or None if unavailable."""
        if self.camera is None:
            return None
        return self.camera.read()

    def _ai_worker(self):
        """Background thread to process frames."""
        consecutive_none_count = 0
        max_none_retries = 10
        frame_index = 0
        last_results, last_voting = [], {}

        while self.is_running:
            start_time = time.time()
            frame = self.read_frame()
            if frame is None or getattr(frame, "size", 0) == 0:
                consecutive_none_count += 1
                if consecutive_none_count > max_none_retries:
                    if not self.result_queue.full():
                        self.result_queue.put(("EOF", None, None, None))
                    break
                time.sleep(0.05)
                continue

            consecutive_none_count = 0
            frame_index += 1

            if (frame_index - 1) % max(1, int(my_config.FRAME_SKIP)) == 0:
                try:
                    last_results, last_voting = self.process_frame(frame)
                except Exception as e:
                    logging.error(f"Error in process_frame: {e}")
                    last_results, last_voting = [], {}

            if self.result_queue.full():
                try:
                    self.result_queue.get_nowait()
                except queue.Empty:
                    pass
            self.result_queue.put((frame, last_results, last_voting, start_time))

    # ------------------------------------------------------------------
    # AI Pipeline Processing
    # ------------------------------------------------------------------

    def process_frame(self, frame):
        """
        Runs the detection + classification pipeline on a single frame,
        feeds results into temporal voting, records violation events, and returns:
          - results       : raw pipeline results (list of DetectionResult)
          - results_voting: voted results per track_id (dict)
        """
        with self._state_lock:
            ai_frame = self.brightness.apply(frame)
            results = self.pipeline.run(frame=ai_frame)
            self.voting.update(results)
            results_voting = self.voting.vote()
            now = datetime.now().astimezone().replace(microsecond=0)
            events = self.event_detector.process(frame, results, results_voting, now)
        for event in events:
            self._record_event(event)
        return results, results_voting

    def _record_event(self, event) -> None:
        queued = False
        if self.recording_enabled:
            queued = self.sender.enqueue(event, self.params["CAMERA_CODE"])
        alert = {
            "event_id": event.event_id,
            "time": event.occurred_at.strftime("%H:%M:%S"),
            "track_id": event.track_id,
            "label": event.label,
            "code": event.violation_code,
            "camera": self.camera_label(),
            "queued": queued,
            "note": None if queued else self.recording_note,
        }
        try:
            self.alert_queue.put_nowait(alert)
        except queue.Full:
            pass

    # ------------------------------------------------------------------
    # Statistics
    # ------------------------------------------------------------------

    def reset_state(self) -> None:
        """Clears all temporal voting history (called on UI reset)."""
        with self._state_lock:
            self.voting.reset()
            self.event_detector.reset()
            self.brightness.reset()

    @staticmethod
    def compute_statistics(results_voting: dict) -> dict:
        """
        Derives aggregate statistics from voting results.
        Returns a dict with keys: total, uniform, non_uniform, card_ok, no_card, fully_compliant,
        violators, waiting, compliance_rate.
        """
        total = len(results_voting)
        uniform = non_uniform = card_ok = no_card = fully_compliant = violators = waiting = 0

        for vote_res in results_voting.values():
            u_lbl = vote_res.uniform_label
            c_lbl = vote_res.card_label

            if u_lbl == "Uniform":
                uniform += 1
            elif u_lbl == "Non_Uniform":
                non_uniform += 1

            if c_lbl == "Card":
                card_ok += 1
            elif c_lbl == "No_Card":
                no_card += 1

            status = combined_status(u_lbl, c_lbl)
            if status is None:
                waiting += 1
            elif status == "COMPLIANT":
                fully_compliant += 1
            else:
                violators += 1

        decided = fully_compliant + violators
        compliance_rate = (fully_compliant / decided * 100) if decided > 0 else 0.0

        return {
            "total":           total,
            "uniform":         uniform,
            "non_uniform":     non_uniform,
            "card_ok":         card_ok,
            "no_card":         no_card,
            "fully_compliant": fully_compliant,
            "violators":       violators,
            "waiting":         waiting,
            "compliance_rate": compliance_rate,
        }

    # ------------------------------------------------------------------
    # Lifecycle
    # ------------------------------------------------------------------

    def shutdown(self) -> None:
        """Stop camera, worker and sender threads and release model memory."""
        self.stop_camera()
        self.sender.stop()
        if getattr(self, "_spawned_api_process", None) is not None:
            try:
                logging.info("Đang đóng tiến trình FastAPI backend do App khởi tạo...")
                self._spawned_api_process.terminate()
                self._spawned_api_process.wait(timeout=3.0)
            except Exception:
                try:
                    self._spawned_api_process.kill()
                except Exception:
                    pass
            self._spawned_api_process = None
        self.pipeline = None
        self.detector = None
        self.card_detector = None
        self.uniform_classifier = self.card_classifier = self.classifier = None
        try:
            import torch
            if torch.cuda.is_available():
                torch.cuda.empty_cache()
        except Exception:
            pass

    # ------------------------------------------------------------------
    # Entry Point
    # ------------------------------------------------------------------

    def run(self) -> None:
        """Launches the Tkinter UI (imported here to keep UI separate)."""
        from desktop.ui import CampusMonitorUI
        root = tk.Tk()
        CampusMonitorUI(root, self)
        root.mainloop()
