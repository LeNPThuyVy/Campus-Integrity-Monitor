import cv2
import logging

class Camera:
    def __init__(self, source = 0):
        self.cap=cv2.VideoCapture(source)
        if not self.cap.isOpened():
            raise RuntimeError("Can't access to the camera!")
        
    def read(self):
        state,frame=self.cap.read()
        if state:
            return frame
        else:
            logging.error("Fail to read frame from the camera!")
            return None
    
    def release(self):
        self.cap.release()
        cv2.destroyAllWindows()