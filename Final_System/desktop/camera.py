import cv2
import logging

class Camera:
    def __init__(self, source = 0):
        self.cap=cv2.VideoCapture(source)
        self._failures = 0
        if not self.cap.isOpened():
            self.cap.release()
            raise RuntimeError("Can't access to the camera!")

    def read(self):
        state,frame=self.cap.read()
        if state and frame is not None and frame.size > 0:
            self._failures = 0
            return frame
        if self._failures == 0:
            # Logged once per failure streak (end of a video file also lands here).
            logging.warning("Fail to read frame from the camera / video source.")
        self._failures += 1
        return None

    def release(self):
        self.cap.release()
        try:
            cv2.destroyAllWindows()
        except cv2.error:
            # opencv-python-headless has no GUI backend; nothing to destroy.
            pass
