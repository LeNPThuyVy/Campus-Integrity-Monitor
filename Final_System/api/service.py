"""
This file contains the core business logic.
After done the business logic, it returns the final processed result back to the routing layer
"""
import threading
import time
from collections import OrderedDict
from typing import Callable

import numpy as np

from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
from api.mapper import Mapper

MAX_CAMERA_STATES = 16


class _CameraState:
    def __init__(self, pipeline: Pipeline, voting: TemporalVoting):
        self.pipeline = pipeline
        self.voting = voting
        self.lock = threading.Lock()


class InferenceService:
    """
    Keeps tracker + temporal-voting state per camera, so frames from different cameras never
    share track IDs or voting history. Classifier weights are shared between cameras.
    """

    def __init__(self, pipeline_factory: Callable[[], Pipeline], voting_factory: Callable[[], TemporalVoting],
                 mapper: Mapper):
        self._pipeline_factory = pipeline_factory
        self._voting_factory = voting_factory
        self.mapper = mapper
        self._states: "OrderedDict[str, _CameraState]" = OrderedDict()
        self._states_lock = threading.Lock()

    def _state_for(self, camera: str) -> _CameraState:
        with self._states_lock:
            state = self._states.get(camera)
            if state is None:
                state = _CameraState(self._pipeline_factory(), self._voting_factory())
                self._states[camera] = state
                while len(self._states) > MAX_CAMERA_STATES:
                    self._states.popitem(last=False)
            else:
                self._states.move_to_end(camera)
            return state

    def predict(self, frame: np.ndarray, camera: str = "default"):
        """
        This function will run AI logic
        """
        if frame is None or not isinstance(frame, np.ndarray) or frame.size == 0:
            raise ValueError("Khung hình không hợp lệ.")
        start_time = time.perf_counter()
        state = self._state_for(camera)
        with state.lock:  # one frame at a time per camera keeps tracker state consistent
            pipeline_results = state.pipeline.run(frame)
            bbox_map = {result.track_id: self.mapper.mapping_bbox(result.bbox) for result in pipeline_results}
            state.voting.update(pipeline_results)
            results = state.voting.vote()

        list_detect = []
        for key, value in results.items():
            if key in bbox_map:
                list_detect.append(self.mapper.mapping_detectionResponse(
                    track_id=key,
                    bbox=bbox_map[key],
                    uniform_label=value.uniform_label,
                    card_label=value.card_label,
                    label=f"{value.uniform_label} | {value.card_label}",
                    matched_count=value.matched_count,
                ))
        execution_time = time.perf_counter() - start_time
        return list_detect, execution_time * 1000
