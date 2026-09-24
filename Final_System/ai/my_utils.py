from torchvision import transforms
from PIL import Image
import cv2
import numpy as np
class Utils:
    @staticmethod
    def preprocess(image,image_size):
        """
        This function preprocesses before classify 
        """
        image_transform=transforms.Compose([
            transforms.Resize(size=(image_size,image_size)),
            transforms.ToTensor(),
            
            transforms.Normalize(
                mean=[0.485,0.456,0.406],
                std=[0.229,0.224,0.225]
            ),
        ])
        image=cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        image=Image.fromarray(image)
        image=image_transform(image)
        image=image.unsqueeze(0)
        return image
    
    @staticmethod
    def get_crop_ratio(person_height_ratio: float) -> float:
        """
        Determine upper-body crop ratio based on person height ratio in image
        """
        if person_height_ratio < 0.55:
            return 0.6
        elif person_height_ratio < 0.85:
            return 0.7
        else:
            return 1.0

    @staticmethod
    def crop_person(frame: np.ndarray, bbox:  list[float])-> np.ndarray:
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

