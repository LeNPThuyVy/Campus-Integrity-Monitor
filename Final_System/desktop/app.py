import json
import os
import logging
import tkinter as tk

from ai.classifier import Classifier
from ai.detector import Detector
from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
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
        self.classifier = Classifier(
            model_path=my_config.CLASSIFY_UNIFORM_PATH,
            num_class=len(my_config.LABELS)
        )
        self.detector = Detector(
            model_path=my_config.DETECT_PERSON_PATH,
            device=my_config.DEVICE,
            conf=my_config.DETECT_CONF
        )
        self.pipeline = Pipeline(detector=self.detector, classifier=self.classifier)
        self.voting = TemporalVoting()
        self.camera: Camera | None = None

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
            "IOU_THRESHOLD":             getattr(my_config, "IOU_THRESHOLD", 0.7),
            "FRAME_SKIP":                my_config.FRAME_SKIP,
            "LEN_HISTORY":               my_config.LEN_HISTORY,
            "VOTING_THREDSHOLD":         my_config.VOTING_THREDSHOLD,
            "MISSING_COUNTER_THRESHOLD": my_config.MISSING_COUNTER_THRESHOLD,
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
        my_config.CLASSIFY_CONF             = self.params["CLASSIFY_CONF"]
        my_config.IOU_THRESHOLD             = self.params["IOU_THRESHOLD"]
        my_config.FRAME_SKIP                = self.params["FRAME_SKIP"]
        my_config.LEN_HISTORY               = self.params["LEN_HISTORY"]
        my_config.VOTING_THREDSHOLD         = self.params["VOTING_THREDSHOLD"]
        my_config.MISSING_COUNTER_THRESHOLD = self.params["MISSING_COUNTER_THRESHOLD"]

        if self.detector:
            self.detector.conf = self.params["DETECT_CONF"]
        if self.voting:
            self.voting.len_history = self.params["LEN_HISTORY"]

    # ------------------------------------------------------------------
    # Camera / Stream Control
    # ------------------------------------------------------------------

    def start_camera(self, source) -> None:
        """Opens the video source (int index for webcam, str path for file)."""
        self.camera = Camera(source)

    def stop_camera(self) -> None:
        """Releases the active camera / video capture."""
        if self.camera:
            self.camera.release()
            self.camera = None

    def read_frame(self):
        """Returns the next frame from the camera, or None if unavailable."""
        if self.camera is None:
            return None
        return self.camera.read()

    # ------------------------------------------------------------------
    # AI Pipeline Processing
    # ------------------------------------------------------------------

    def process_frame(self, frame):
        """
        Runs the detection + classification pipeline on a single frame,
        feeds results into temporal voting, and returns:
          - results       : raw pipeline results (list of DetectionResult)
          - results_voting: voted results per track_id (dict)
        """
        results = self.pipeline.run(frame=frame)
        self.voting.update(results)
        results_voting = self.voting.vote()
        return results, results_voting

    # ------------------------------------------------------------------
    # Statistics
    # ------------------------------------------------------------------

    def reset_state(self) -> None:
        """Clears all temporal voting history (called on UI reset)."""
        if self.voting:
            self.voting.histories.clear()
            self.voting.missing_counter.clear()

    @staticmethod
    def compute_statistics(results_voting: dict) -> dict:
        """
        Derives aggregate statistics from voting results.
        Returns a dict with keys: total, uniform, non_uniform, waiting, compliance_rate.
        """
        total = len(results_voting)
        uniform = non_uniform = waiting = 0

        for vote_res in results_voting.values():
            label = vote_res.label
            if label == "Uniform":
                uniform += 1
            elif label == "Non_Uniform":
                non_uniform += 1
            else:
                waiting += 1

        compliance_rate = (uniform / total * 100) if total > 0 else 0.0

        return {
            "total":           total,
            "uniform":         uniform,
            "non_uniform":     non_uniform,
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
