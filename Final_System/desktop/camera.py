import cv2
import logging

class Camera:
    def __init__(self, source = 0):
        self.is_file = not isinstance(source, int)
        self.cap=cv2.VideoCapture(source)
        self._failures = 0
        if not self.cap.isOpened():
            self.cap.release()
            raise RuntimeError("Can't access to the camera!")
        raw_fps = float(self.cap.get(cv2.CAP_PROP_FPS) or 0.0)
        self.fps = raw_fps if 1.0 <= raw_fps <= 120.0 else 25.0
        if isinstance(source, int):
            # Webcams open at 640x480 by default; 720p gives sharper person crops.
            # Unsupported sizes are ignored by OpenCV and the driver default is kept.
            self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
            self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
            logging.info("Webcam resolution: %dx%d",
                         int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH)), int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT)))

    def grab(self) -> bool:
        return bool(self.cap.grab())

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
