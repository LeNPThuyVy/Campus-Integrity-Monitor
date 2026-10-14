import cv2
import logging

class Camera:
    def __init__(self, source = 0):
        self.source = source
        self.cap=cv2.VideoCapture(source)
        if isinstance(source, int):
            self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
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