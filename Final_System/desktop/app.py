import json
import os
import logging
import tkinter as tk
import threading
import queue
import time
import uuid
from datetime import datetime, timedelta, timezone

from ai.classifier import Classifier
from ai.card_detector import CardDetector
from ai.detector import Detector
from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
from ai.my_utils import Utils
from ai.reporting.event_logger import EventLogger
from ai.reporting.models import TrackingResult, get_violation_type
from ai.reporting.sqlite_outbox import SqliteOutboxRepository
from ai.reporting.sync_worker import SyncWorker
from desktop.camera import Camera
import ai.config as my_config

CONFIG_FILE_PATH = os.path.join(os.path.dirname(__file__), "config.json")


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
        # Card detection is now done by a YOLO detector, not a MobileNetV3 classifier.
        # The CardDetector returns bounding boxes; Pipeline converts them to a
        # Prediction-compatible result so voting and UI code stay unchanged.
        self.card_detector = CardDetector(
            model_path=my_config.DETECT_CARD_PATH,
            device=my_config.DEVICE,
            conf=my_config.DETECT_CARD_CONF,
            iou=my_config.DETECT_CARD_IOU,
            image_size=my_config.DETECT_CARD_IMAGE_SIZE
        )
        self.classifier = self.uniform_classifier
        self.detector = Detector(
            model_path=my_config.DETECT_PERSON_PATH,
            device=my_config.DEVICE,
            conf=my_config.DETECT_CONF
        )
        self.pipeline = Pipeline(
            detector=self.detector,
            uniform_classifier=self.uniform_classifier,
            card_detector=self.card_detector
        )
        self.voting = TemporalVoting()
        # Events are saved to the local outbox first, SyncWorker pushes them to the server (if SERVER_URL is set)
        self.outbox = SqliteOutboxRepository(file_path=my_config.EVENT_OUTBOX_PATH)
        self.event_logger = EventLogger(
            repository=self.outbox,
            timeout=timedelta(seconds=my_config.EVENT_TIMEOUT_SECONDS),
            device_id=my_config.DEVICE_ID,
            session_id=str(uuid.uuid4()),
            image_dir=my_config.EVENT_IMAGE_DIR
        )
        self.sync_worker: SyncWorker | None = None
        if my_config.SERVER_URL:
            self.sync_worker = SyncWorker(
                repository=self.outbox,
                server_url=my_config.SERVER_URL,
                api_key=my_config.DEVICE_API_KEY,
                interval_seconds=my_config.SYNC_INTERVAL_SECONDS,
                batch_size=my_config.SYNC_BATCH_SIZE,
                image_workers=my_config.SYNC_IMAGE_WORKERS,
                timeout_seconds=my_config.SYNC_TIMEOUT_SECONDS
            )
        self.camera: Camera | None = None

        self.is_running = False
        self.frame_queue = queue.Queue(maxsize=1)
        self.result_queue = queue.Queue(maxsize=2)
        self.io_queue = queue.Queue(maxsize=50)
        self.worker_thread = None
        self.capture_thread = None
        self.io_thread = None

        # Loaded once at startup; UI sliders may update these values later
        self.params: dict = {}
        self.load_parameters()

    # ------------------------------------------------------------------
    # Configuration Management
    # ------------------------------------------------------------------

    def load_parameters(self) -> dict:
        """Loads configuration from config.json, falling back to ai.config defaults."""
        self.params = {
            "DETECT_CONF":               my_config.DETECT_CONF,
            "CLASSIFY_CONF":             my_config.CLASSIFY_CONF,
            "LEN_HISTORY":               my_config.LEN_HISTORY,
            "MIN_PERSON_HEIGHT_RATIO":   getattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.10),
            "MISSING_COUNTER_THRESHOLD": my_config.MISSING_COUNTER_THRESHOLD,
            "MIN_SAMPLES":               getattr(my_config, "MIN_SAMPLES", 3),
            "CONFIRM_SCORE":             getattr(my_config, "CONFIRM_SCORE", 2.5),
            "CARD_ROI_TOP":              getattr(my_config, "CARD_ROI", (0.1, 0.6))[0],
            "CARD_ROI_BOT":              getattr(my_config, "CARD_ROI", (0.1, 0.6))[1],
            "CLASSIFY_CADENCE_CONFIRMED":getattr(my_config, "CLASSIFY_CADENCE_CONFIRMED", 15),
            # YOLO card detector knobs
            "DETECT_CARD_CONF":          my_config.DETECT_CARD_CONF,
            "DETECT_CARD_IOU":           my_config.DETECT_CARD_IOU,
            "DETECT_CARD_IMAGE_SIZE":    my_config.DETECT_CARD_IMAGE_SIZE,
        }

        if os.path.exists(CONFIG_FILE_PATH):
            try:
                with open(CONFIG_FILE_PATH, "r", encoding="utf-8") as f:
                    saved = json.load(f)
                    self.params.update(saved)
            except Exception as e:
                logging.error(f"Error loading config: {e}")

        self.apply_params_to_config()
        return self.params

    def save_parameters(self, new_params: dict) -> None:
        """Persists updated parameters to config.json and applies them immediately."""
        self.params.update(new_params)
        try:
            with open(CONFIG_FILE_PATH, "w", encoding="utf-8") as f:
                json.dump(self.params, f, indent=4)
        except Exception as e:
            logging.error(f"Error saving config: {e}")
            raise

        self.apply_params_to_config()

    def apply_params_to_config(self) -> None:
        """Pushes current params into ai.config and live model components."""
        my_config.DETECT_CONF               = self.params["DETECT_CONF"]
        my_config.CLASSIFY_CONF             = self.params.get("CLASSIFY_CONF", 0.8)
        my_config.LEN_HISTORY               = self.params["LEN_HISTORY"]
        my_config.MIN_PERSON_HEIGHT_RATIO   = self.params.get("MIN_PERSON_HEIGHT_RATIO", 0.10)
        my_config.MISSING_COUNTER_THRESHOLD = self.params["MISSING_COUNTER_THRESHOLD"]
        my_config.MIN_SAMPLES               = self.params.get("MIN_SAMPLES", 3)
        my_config.CONFIRM_SCORE             = self.params.get("CONFIRM_SCORE", 2.5)
        my_config.CARD_ROI                  = (self.params.get("CARD_ROI_TOP", 0.1), self.params.get("CARD_ROI_BOT", 0.6))
        my_config.CLASSIFY_CADENCE_CONFIRMED= self.params.get("CLASSIFY_CADENCE_CONFIRMED", 15)
        my_config.DETECT_CARD_CONF          = self.params["DETECT_CARD_CONF"]
        my_config.DETECT_CARD_IOU           = self.params["DETECT_CARD_IOU"]
        my_config.DETECT_CARD_IMAGE_SIZE    = self.params["DETECT_CARD_IMAGE_SIZE"]

        if self.detector:
            self.detector.conf = self.params["DETECT_CONF"]

        # Push YOLO card detector thresholds to the live model
        if self.card_detector:
            self.card_detector.conf       = self.params["DETECT_CARD_CONF"]
            self.card_detector.iou        = self.params["DETECT_CARD_IOU"]
            self.card_detector.image_size = self.params["DETECT_CARD_IMAGE_SIZE"]

        if self.voting:
            self.voting.len_history = self.params["LEN_HISTORY"]

    # ------------------------------------------------------------------
    # Camera / Stream Control
    # ------------------------------------------------------------------

    def start_camera(self, source) -> None:
        """Opens the video source (int index for webcam, str path for file)."""
        self.camera = Camera(source)
        self.is_running = True
        self.event_logger.session_id = str(uuid.uuid4())
        if self.sync_worker:
            self.sync_worker.start()
        self.frame_queue = queue.Queue(maxsize=1)
        self.result_queue = queue.Queue(maxsize=2)
        self.io_queue = queue.Queue(maxsize=50)
        self.latest_frame = None
        self.latest_ai = ([], {}, 0.0)
        self.ai_fps = 0.0
        self.last_ai_time = time.perf_counter()
        
        self.capture_thread = threading.Thread(target=self._capture_worker, daemon=True)
        self.capture_thread.start()
        
        self.io_thread = threading.Thread(target=self._io_worker, daemon=True)
        self.io_thread.start()
        
        self.worker_thread = threading.Thread(target=self._ai_worker, daemon=True)
        self.worker_thread.start()

    def stop_camera(self) -> None:
        """Releases the active camera / video capture."""
        self.is_running = False
        if self.worker_thread:
            self.worker_thread.join(timeout=1.0)
            self.worker_thread = None
        if getattr(self, "capture_thread", None):
            self.capture_thread.join(timeout=1.0)
            self.capture_thread = None
        if getattr(self, "io_thread", None):
            self.io_queue.put(None)
            self.io_thread.join(timeout=2.0)
            self.io_thread = None
        # Save events that are still open, the AI thread has stopped so it is safe
        self.event_logger.flush()
        if self.camera:
            self.camera.release()
            self.camera = None

    def read_frame(self):
        """Returns the next frame from the camera, or None if unavailable."""
        if self.camera is None:
            return None
        return self.camera.read()

    def _capture_worker(self):
        """Background thread to strictly read frames from camera/video."""
        is_file_source = self.camera and isinstance(getattr(self.camera, "source", None), str)
        target_delay = 0.033 # ~30fps
        while self.is_running:
            t0 = time.time()
            frame = self.read_frame()
            if frame is None:
                time.sleep(0.01)
                continue
            
            # Keep only the freshest frame in queue for AI thread
            if self.frame_queue.full():
                try:
                    self.frame_queue.get_nowait()
                except queue.Empty:
                    pass
            self.frame_queue.put((frame, t0))
            self.latest_frame = frame
            
            if is_file_source:
                elapsed = time.time() - t0
                if elapsed < target_delay:
                    time.sleep(target_delay - elapsed)

    def _io_worker(self):
        """Background thread for saving evidence and SQLite logs to avoid blocking AI."""
        while self.is_running or not self.io_queue.empty():
            try:
                item = self.io_queue.get(timeout=0.1)
                if item is None:
                    break
                tracking_results, timestamp, finalized = item
                self.event_logger.process(results=tracking_results, timestamp=timestamp)
                self.io_queue.task_done()
            except queue.Empty:
                pass

    def _ai_worker(self):
        """Background thread to process frames."""
        consecutive_none_count = 0
        max_none_retries = 20
        frame_count = 0
        last_classified = {}

        while self.is_running:
            try:
                frame, start_time = self.frame_queue.get(timeout=0.1)
                consecutive_none_count = 0
            except queue.Empty:
                consecutive_none_count += 1
                if consecutive_none_count > max_none_retries:
                    if not self.result_queue.full():
                        self.result_queue.put(("EOF", None, None, None))
                continue

            frame_count += 1

            try:
                results, results_voting = self.process_frame(frame, frame_count, last_classified)
            except Exception as e:
                logging.error(f"Error in process_frame: {e}")
                results, results_voting = [], {}

            now = time.perf_counter()
            elapsed_ai = now - getattr(self, "last_ai_time", now)
            self.last_ai_time = now
            if elapsed_ai > 0:
                instant_ai_fps = 1.0 / elapsed_ai
                self.ai_fps = 0.1 * instant_ai_fps + 0.9 * getattr(self, "ai_fps", 0.0)
                
            self.latest_ai = (results, results_voting, time.time())

            if self.result_queue.full():
                try:
                    self.result_queue.get_nowait()
                except queue.Empty:
                    pass
            self.result_queue.put((frame, results, results_voting, start_time))

    # ------------------------------------------------------------------
    # AI Pipeline Processing
    # ------------------------------------------------------------------

    def process_frame(self, frame, frame_count: int, last_classified: dict):
        """
        Runs the detection + classification pipeline on a single frame.
        """
        results = self.pipeline.run(frame=frame, frame_count=frame_count, last_classified=last_classified, current_labels=self.voting.current_uniform_labels)
        self.voting.update(results)
        results_voting = self.voting.vote()
        
        # Get finalized votes for tracks that disappeared
        finalized = self.voting.get_finalized_and_remove()
        
        try:
            self._log_events(frame, results, results_voting, finalized)
        except Exception as e:
            logging.error(f"Error in _log_events: {e}")
            
        return results, results_voting

    def _log_events(self, frame, results, results_voting: dict, finalized: dict) -> None:
        """
        Converts voted results into TrackingResult and pushes to IO thread.
        """
        bbox_map = {result.track_id: result.bbox for result in results}
        tracking_results = []
        
        for track_id, vote_res in list(results_voting.items()) + list(finalized.items()):
            u_lbl = getattr(vote_res, "uniform_label", vote_res.label)
            c_lbl = getattr(vote_res, "card_label", "Waiting")
            if u_lbl == "Waiting" and c_lbl == "Waiting":
                continue
                
            bbox = bbox_map.get(track_id)
            image = None
            if (bbox is not None
                    and get_violation_type(u_lbl, c_lbl) != "none"
                    and self.event_logger.needs_image(track_id)):
                image = Utils.crop_person(frame=frame, bbox=bbox)
                if image.size == 0:
                    image = None
                    
            tracking_results.append(TrackingResult(
                track_id=track_id,
                uniform_label=u_lbl,
                card_label=c_lbl,
                label=f"{u_lbl} | {c_lbl}",
                bbox=bbox,
                image=image
            ))
            
        try:
            self.io_queue.put_nowait((tracking_results, datetime.now(timezone.utc), finalized))
        except queue.Full:
            now = time.time()
            if now - getattr(self, "_last_io_queue_warn", 0) > 10:
                logging.warning("IO queue is full, dropping tick")
                self._last_io_queue_warn = now

    # ------------------------------------------------------------------
    # Statistics
    # ------------------------------------------------------------------

    def reset_state(self) -> None:
        """Clears all temporal voting history (called on UI reset)."""
        if self.voting:
            self.voting.uniform_histories.clear()
            self.voting.card_histories.clear()
            self.voting.missing_counter.clear()
            self.voting.current_uniform_labels.clear()
            self.voting.current_card_labels.clear()

    @staticmethod
    def compute_statistics(results_voting: dict) -> dict:
        """
        Derives aggregate statistics from voting results.
        Returns a dict with keys: total, uniform, non_uniform, card_ok, no_card, fully_compliant, waiting, compliance_rate.
        """
        total = len(results_voting)
        uniform = non_uniform = card_ok = no_card = fully_compliant = waiting = 0

        for vote_res in results_voting.values():
            u_lbl = getattr(vote_res, "uniform_label", vote_res.label)
            c_lbl = getattr(vote_res, "card_label", "Waiting")

            if u_lbl == "Uniform":
                uniform += 1
            elif u_lbl == "Non_Uniform":
                non_uniform += 1

            if c_lbl == "Card":
                card_ok += 1
            elif c_lbl == "No_Card":
                no_card += 1

            if u_lbl == "Uniform" and c_lbl == "Card":
                fully_compliant += 1
            elif u_lbl == "Waiting" or c_lbl == "Waiting":
                waiting += 1

        compliance_rate = (fully_compliant / total * 100) if total > 0 else 0.0

        return {
            "total":           total,
            "uniform":         uniform,
            "non_uniform":     non_uniform,
            "card_ok":         card_ok,
            "no_card":         no_card,
            "fully_compliant": fully_compliant,
            "waiting":         waiting,
            "compliance_rate": compliance_rate,
        }

    # ------------------------------------------------------------------
    # Entry Point
    # ------------------------------------------------------------------

    def run(self) -> None:
        """Launches the Tkinter UI (imported here to keep UI separate)."""
        from desktop.ui import CampusMonitorUI
        root = tk.Tk()
        CampusMonitorUI(root, self)
        root.mainloop()
