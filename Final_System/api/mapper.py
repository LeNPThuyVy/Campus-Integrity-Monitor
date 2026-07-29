from api.schemas import BBox, ModelInfo, DetectionResponse, InferenceResponse
import ai.config as my_config
import numpy as np
import cv2

class Mapper:
    @classmethod
    def mapping_bytes_to_ndarray(self,raw_img: bytes):
        try:
            #convert frame to numpy type
            frame_np= np.frombuffer(raw_img,dtype=np.uint8) 
            #convert numpy type to image
            frame=cv2.imdecode(frame_np,cv2.IMREAD_COLOR)
            return frame
        except Exception as e:
            print(f"Error: {e}")
            return

    @classmethod
    def mapping_bbox(self,bbox: list[float]) -> BBox:
        return BBox(x1=bbox[0],y1=bbox[1],x2=bbox[2],y2=bbox[3])

    @classmethod
    def mapping_model_info(self) -> ModelInfo:
        return ModelInfo(detector=my_config.DETECTOR_MODEL_NAME,classifier=my_config.CLASSIFIER_MODEL_NAME)

    @classmethod
    def mapping_detectionResponse(self,track_id: int,bbox: BBox,label,matched_count: int):
        return DetectionResponse(track_id=track_id,bbox=bbox,label=label,matched_count=matched_count)

    @classmethod
    def mapping_inferenceResponse(
            self,
            camera: str,
            total: int,
            results: list[DetectionResponse],
            processing_time_ms: float = 0.0):
        return InferenceResponse(camera=camera,total=total,models=self.mapping_model_info(),results=results,processing_time_ms=processing_time_ms)

