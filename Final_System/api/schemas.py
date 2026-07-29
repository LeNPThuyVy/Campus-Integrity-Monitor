"""
This file defines the exact shape of the data going in and out of the API (data validation).
Use Pydantic to set the rules. 
"""

from pydantic import BaseModel

class BBox(BaseModel):
    x1: float
    y1: float
    x2: float
    y2: float

class ModelInfo(BaseModel):
    detector: str
    classifier: str

class DetectionResponse(BaseModel):
    track_id: int
    bbox: BBox
    label:str
    matched_count: int


class InferenceResponse(BaseModel):
    camera: str
    total: int
    processing_time_ms: float
    models: ModelInfo
    results: list[DetectionResponse]


