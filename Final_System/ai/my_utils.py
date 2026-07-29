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
    def crop_person(frame: np.ndarray, bbox:  list[float])-> np.ndarray:
        """
        This function will crop person from frame. So it will return images of person
        """
        x1,y1,x2,y2=map(int,bbox)
        person_images=frame[y1:y2,x1:x2]
        return person_images

