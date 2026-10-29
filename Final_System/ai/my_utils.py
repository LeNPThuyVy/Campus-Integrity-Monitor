import torch
from torchvision import transforms
import cv2
import numpy as np
import ai.config as my_config

class Utils:
    _normalize = transforms.Normalize(mean=[0.485,0.456,0.406], std=[0.229,0.224,0.225])

    @staticmethod
    def is_person_large_enough(frame_shape, bbox: list[float], min_height_ratio: float = None) -> bool:
        """
        Check if the detected person is large enough in the frame.
        If bbox height / frame height < min_height_ratio, the person is too far away.
        """
        if frame_shape is None or len(frame_shape) < 2 or bbox is None or len(bbox) < 4:
            return False
        
        frame_h = frame_shape[0]
        if frame_h <= 0:
            return False
            
        threshold = getattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.25) if min_height_ratio is None else min_height_ratio
        
        y1 = max(0, int(bbox[1]))
        y2 = min(frame_h, int(bbox[3]))
        person_h = y2 - y1
        if person_h <= 0:
            return False
            
        ratio = person_h / frame_h
        return ratio >= threshold

    @staticmethod
    def preprocess(image, image_size):
        """
        This function preprocesses before classify (Optimized with adaptive per-crop CLAHE)
        """
        img_resized = cv2.resize(image, (image_size, image_size))
        
        # Apply local CLAHE if enabled and lighting is poor/harsh
        brightness_enabled = getattr(my_config, "BRIGHTNESS_ENABLED", 1)
        clahe_enabled = getattr(my_config, "CROP_CLAHE_ENABLED", 1)
        if brightness_enabled and clahe_enabled and img_resized.size > 0:
            try:
                lab = cv2.cvtColor(img_resized, cv2.COLOR_BGR2LAB)
                l_channel = lab[:, :, 0]
                l_mean = float(l_channel.mean())
                l_std = float(l_channel.std())
                if l_mean < 80 or l_mean > 190 or l_std < 35:
                    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(4, 4))
                    lab[:, :, 0] = clahe.apply(l_channel)
                    img_resized = cv2.cvtColor(lab, cv2.COLOR_LAB2BGR)
            except Exception:
                pass

        img_rgb = cv2.cvtColor(img_resized, cv2.COLOR_BGR2RGB)
        tensor = torch.from_numpy(img_rgb).permute(2, 0, 1).float() / 255.0
        tensor = Utils._normalize(tensor)
        return tensor.unsqueeze(0)
    
    @staticmethod
    def get_crop_ratio(person_height_ratio: float) -> float:
        """
        Determine upper-body crop ratio based on person height ratio in image.
        Uses a consistent crop ratio to avoid flipping predictions across distance boundaries.
        """
        base_ratio = getattr(my_config, "UPPER_BODY_CROP_RATIO", 0.68)
        if person_height_ratio >= 0.90:
            return 0.85
        return float(base_ratio)

    @staticmethod
    def crop_person(frame: np.ndarray, bbox: list[float]) -> np.ndarray:
        """
        This function will crop upper body of person from frame using adaptive crop ratio
        """
        image_h, image_w = frame.shape[:2]
        x1, y1, x2, y2 = map(int, bbox)

        x1 = max(0, x1)
        y1 = max(0, y1)
        x2 = min(image_w, x2)
        y2 = min(image_h, y2)

        person_h = y2 - y1
        person_w = x2 - x1

        if person_h <= 0 or person_w <= 0:
            return np.empty((0, 0, 3), dtype=frame.dtype)

        person_height_ratio = person_h / image_h
        crop_ratio = Utils.get_crop_ratio(person_height_ratio)

        upper_y2 = int(y1 + person_h * crop_ratio)
        upper_y2 = min(upper_y2, image_h)

        person_images = frame[y1:upper_y2, x1:x2]
        return person_images
