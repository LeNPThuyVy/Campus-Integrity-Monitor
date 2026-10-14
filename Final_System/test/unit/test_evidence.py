from datetime import datetime

import cv2
import numpy as np
import pytest

from api.evidence import EvidenceError, EvidenceStorage, UnsafeEvidencePath, validate_and_normalize_image


def jpeg_bytes(width=64, height=96):
    image = np.random.default_rng(1).integers(0, 255, size=(height, width, 3), dtype=np.uint8)
    return cv2.imencode(".jpg", image)[1].tobytes()


def test_valid_image_is_reencoded_as_jpeg():
    data = jpeg_bytes() + b"<script>trailing junk</script>"
    jpeg, width, height = validate_and_normalize_image(data, 1_000_000, 4096)
    assert jpeg.startswith(b"\xff\xd8\xff") and b"<script>" not in jpeg
    assert (width, height) == (64, 96)


@pytest.mark.parametrize("data,status", [
    (b"", 422),
    (b"GIF89a" + b"0" * 100, 415),
    (b"\xff\xd8\xff" + b"not really a jpeg", 422),
])
def test_invalid_images_rejected(data, status):
    with pytest.raises(EvidenceError) as error:
        validate_and_normalize_image(data, 1_000_000, 4096)
    assert error.value.status_code == status


def test_size_and_dimension_limits():
    with pytest.raises(EvidenceError) as error:
        validate_and_normalize_image(jpeg_bytes(), 100, 4096)
    assert error.value.status_code == 413
    with pytest.raises(EvidenceError):
        validate_and_normalize_image(jpeg_bytes(8, 8), 1_000_000, 4096)
    with pytest.raises(EvidenceError):
        validate_and_normalize_image(jpeg_bytes(64, 96), 1_000_000, 50)


def test_storage_save_resolve_delete(tmp_path):
    storage = EvidenceStorage(tmp_path)
    stored = storage.save(b"\xff\xd8\xffdata", 10, 10, datetime(2026, 10, 13))
    assert stored.key.startswith("2026/10/") and stored.key.endswith(".jpg")
    assert storage.exists(stored.key)
    assert storage.delete(stored.key) is True
    assert storage.delete(stored.key) is False  # already gone is not an error


@pytest.mark.parametrize("key", ["../outside.jpg", "2026/10/../../x.jpg", "C:/Windows/win.ini", "/etc/passwd",
                                 "2026/10/abc.jpg", "", "2026\\10\\..\\..\\x.jpg"])
def test_unsafe_keys_never_resolve(tmp_path, key):
    storage = EvidenceStorage(tmp_path)
    with pytest.raises(UnsafeEvidencePath):
        storage.resolve(key)
    with pytest.raises(UnsafeEvidencePath):
        storage.delete(key)
