import cv2
import numpy as np
import ai.config as my_config


class BrightnessNormalizer:
    """
    Adaptive brightness and glare normalizer for frames input to AI models.
    Performs low-frequency measurement with caching, exponential moving average (EMA)
    smoothing, hysteresis mode switching, and hardware-accelerated lookup tables (cv2.LUT).
    Original input frames are never modified in-place.
    """

    def __init__(self):
        self.reset()

    def reset(self):
        self.mode = "normal"  # "normal" | "dark" | "bright"
        self.gamma = 1.0
        self.lut = None
        self.lut_gamma = 1.0
        self.counter = 0

    def _measure(self, frame: np.ndarray) -> tuple[float, float]:
        """Downscales frame and measures mean brightness and high-clipping ratio."""
        small = cv2.resize(frame, (64, 36), interpolation=cv2.INTER_AREA)
        gray = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)
        mean_val = float(gray.mean())
        clip_hi = float((gray >= 245).mean())
        return mean_val, clip_hi

    def _target_gamma(self, mean: float, clip_hi: float) -> float:
        """Determines operating mode with hysteresis and calculates target gamma."""
        dark_on = getattr(my_config, "BRIGHTNESS_DARK_ON", 60)
        dark_off = getattr(my_config, "BRIGHTNESS_DARK_OFF", 75)
        bright_on = getattr(my_config, "BRIGHTNESS_BRIGHT_ON", 195)
        bright_off = getattr(my_config, "BRIGHTNESS_BRIGHT_OFF", 180)
        clip_on = getattr(my_config, "BRIGHTNESS_CLIP_ON", 0.10)
        clip_off = getattr(my_config, "BRIGHTNESS_CLIP_OFF", 0.07)
        target_mean = getattr(my_config, "BRIGHTNESS_TARGET", 120)
        gamma_min = getattr(my_config, "BRIGHTNESS_GAMMA_MIN", 0.6)
        gamma_max = getattr(my_config, "BRIGHTNESS_GAMMA_MAX", 1.6)
        glare_gamma = getattr(my_config, "BRIGHTNESS_GLARE_GAMMA", 1.25)

        # Hysteresis mode transition
        if self.mode == "dark":
            if mean > dark_off:
                self.mode = "normal"
        elif self.mode == "bright":
            if mean < bright_off and clip_hi < clip_off:
                self.mode = "normal"
        else:
            if mean < dark_on:
                self.mode = "dark"
            elif mean > bright_on or (clip_hi > clip_on and mean > 150):
                self.mode = "bright"

        if self.mode == "normal":
            return 1.0

        if self.mode == "dark":
            safe_mean = max(1.0, min(mean, 254.0))
            raw_gamma = np.log(target_mean / 255.0) / np.log(safe_mean / 255.0)
            return float(np.clip(raw_gamma, gamma_min, 1.0))

        # self.mode == "bright"
        if mean > bright_on:
            safe_mean = max(1.0, min(mean, 254.0))
            raw_gamma = np.log(target_mean / 255.0) / np.log(safe_mean / 255.0)
            return float(np.clip(raw_gamma, 1.0, gamma_max))
        else:
            return float(glare_gamma)

    def _update(self, frame: np.ndarray) -> None:
        """Measures frame, updates smoothed gamma, and generates LUT if necessary."""
        mean, clip_hi = self._measure(frame)
        target_g = self._target_gamma(mean, clip_hi)

        alpha = getattr(my_config, "BRIGHTNESS_EMA_ALPHA", 0.3)
        if self.counter == 0:
            self.gamma = target_g
        else:
            self.gamma = float(alpha * target_g + (1.0 - alpha) * self.gamma)

        dead_zone = getattr(my_config, "BRIGHTNESS_DEAD_ZONE", 0.08)
        if abs(self.gamma - 1.0) < dead_zone:
            self.lut = None
        else:
            if self.lut is None or abs(self.gamma - self.lut_gamma) > 0.02:
                # out = 255 * (in / 255) ** gamma
                lut = np.array([((i / 255.0) ** self.gamma) * 255.0 for i in range(256)]).astype("uint8")
                self.lut = lut
                self.lut_gamma = self.gamma

    def apply(self, frame: np.ndarray) -> np.ndarray:
        """
        Applies brightness normalization if needed.
        Returns the original frame object untouched when lighting is normal or disabled.
        """
        try:
            if not getattr(my_config, "BRIGHTNESS_ENABLED", 1):
                self.counter = 0
                return frame

            if (
                frame is None
                or getattr(frame, "size", 0) == 0
                or getattr(frame, "dtype", None) != np.uint8
                or getattr(frame, "ndim", 0) != 3
            ):
                return frame

            interval = max(1, int(getattr(my_config, "BRIGHTNESS_MEASURE_INTERVAL", 15)))
            if self.counter % interval == 0:
                self._update(frame)
            self.counter += 1

            dead_zone = getattr(my_config, "BRIGHTNESS_DEAD_ZONE", 0.08)
            if self.lut is None or abs(self.gamma - 1.0) < dead_zone:
                return frame

            return cv2.LUT(frame, self.lut)
        except Exception:
            return frame
