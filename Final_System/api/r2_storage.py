"""
Cloudflare R2 evidence storage (S3-compatible via boto3).
Replaces local EvidenceStorage when R2 credentials are configured.
Keeps the same key format: YYYY/MM/<uuid_hex>.jpg
"""
from __future__ import annotations

import hashlib
import logging
import uuid
from datetime import datetime

import boto3
from botocore.config import Config

from api.evidence import EvidenceError, StoredEvidence

logger = logging.getLogger("cim.api.r2")


class R2EvidenceStorage:
    def __init__(
        self,
        endpoint: str,
        access_key: str,
        secret_key: str,
        bucket: str,
        presign_ttl: int = 3600,
    ):
        self._bucket = bucket
        self._presign_ttl = presign_ttl
        self._client = boto3.client(
            "s3",
            endpoint_url=endpoint,
            aws_access_key_id=access_key,
            aws_secret_access_key=secret_key,
            config=Config(signature_version="s3v4"),
            region_name="auto",
        )

    def ensure_root(self) -> None:
        """Compatibility shim matching EvidenceStorage.ensure_root()."""
        # Bucket is managed remotely in Cloudflare R2
        pass

    def save(self, jpeg_bytes: bytes, width: int, height: int, when: datetime) -> StoredEvidence:
        key = f"{when:%Y}/{when:%m}/{uuid.uuid4().hex}.jpg"
        try:
            self._client.put_object(
                Bucket=self._bucket,
                Key=key,
                Body=jpeg_bytes,
                ContentType="image/jpeg",
            )
        except Exception as exc:
            logger.exception("Không upload được ảnh lên Cloudflare R2 bucket %s: %s", self._bucket, exc)
            raise EvidenceError(f"Không upload được ảnh lên Cloudflare R2: {exc}") from exc

        return StoredEvidence(
            key=key,
            sha256=hashlib.sha256(jpeg_bytes).hexdigest(),
            size=len(jpeg_bytes),
            width=width,
            height=height,
        )

    def presign_url(self, key: str) -> str:
        """Generate a presigned GET URL for the object valid for self._presign_ttl seconds."""
        return self._client.generate_presigned_url(
            "get_object",
            Params={"Bucket": self._bucket, "Key": key},
            ExpiresIn=self._presign_ttl,
        )

    def exists(self, key: str) -> bool:
        try:
            self._client.head_object(Bucket=self._bucket, Key=key)
            return True
        except Exception:
            return False

    def delete(self, key: str) -> bool:
        try:
            self._client.delete_object(Bucket=self._bucket, Key=key)
            return True
        except Exception:
            return False

    def resolve(self, key: str):
        """Not supported on R2 remote storage."""
        raise NotImplementedError("R2EvidenceStorage không dùng local path, dùng presign_url().")
