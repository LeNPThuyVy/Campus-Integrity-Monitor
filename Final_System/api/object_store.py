"""
Cloud object storage for evidence images (Cloudflare R2, S3 compatible).
The server never receives image bytes from devices: it only hands out presigned URLs,
devices upload directly to the bucket, so the server isn't a bottleneck.
"""
import os
from abc import ABC, abstractmethod


class ObjectStore(ABC):
    @abstractmethod
    def presigned_put_url(self, key: str, content_type: str, expires_seconds: int) -> str:
        pass

    @abstractmethod
    def presigned_get_url(self, key: str, expires_seconds: int) -> str:
        pass

    @abstractmethod
    def exists(self, key: str) -> bool:
        pass

    @abstractmethod
    def get_bytes(self, key: str) -> bytes:
        pass

    @abstractmethod
    def delete(self, key: str) -> None:
        pass

    @abstractmethod
    def delete_many(self, keys: list[str]) -> None:
        pass


class R2ObjectStore(ObjectStore):
    def __init__(self, account_id: str, access_key_id: str, secret_access_key: str, bucket: str):
        import boto3
        from botocore.config import Config
        self._bucket = bucket
        self._client = boto3.client(
            "s3",
            endpoint_url=f"https://{account_id}.r2.cloudflarestorage.com",
            aws_access_key_id=access_key_id,
            aws_secret_access_key=secret_access_key,
            region_name="auto",
            config=Config(signature_version="s3v4")
        )

    @staticmethod
    def from_env() -> "R2ObjectStore | None":
        """
        Returns None if R2 variables are not filled yet
        """
        names = ["R2_ACCOUNT_ID", "R2_ACCESS_KEY_ID", "R2_SECRET_ACCESS_KEY", "R2_BUCKET"]
        values = [os.getenv(name, "") for name in names]
        if not all(values):
            return None
        return R2ObjectStore(*values)

    def presigned_put_url(self, key: str, content_type: str, expires_seconds: int) -> str:
        return self._client.generate_presigned_url(
            "put_object",
            Params={"Bucket": self._bucket, "Key": key, "ContentType": content_type},
            ExpiresIn=expires_seconds
        )

    def presigned_get_url(self, key: str, expires_seconds: int) -> str:
        return self._client.generate_presigned_url(
            "get_object",
            Params={"Bucket": self._bucket, "Key": key},
            ExpiresIn=expires_seconds
        )

    def exists(self, key: str) -> bool:
        from botocore.exceptions import ClientError
        try:
            self._client.head_object(Bucket=self._bucket, Key=key)
            return True
        except ClientError:
            return False

    def get_bytes(self, key: str) -> bytes:
        return self._client.get_object(Bucket=self._bucket, Key=key)["Body"].read()

    def delete(self, key: str) -> None:
        from botocore.exceptions import ClientError
        try:
            self._client.delete_object(Bucket=self._bucket, Key=key)
        except ClientError:
            pass

    def delete_many(self, keys: list[str]) -> None:
        if not keys:
            return
        from botocore.exceptions import ClientError
        chunk_size = 1000
        for i in range(0, len(keys), chunk_size):
            chunk = keys[i:i + chunk_size]
            try:
                self._client.delete_objects(
                    Bucket=self._bucket,
                    Delete={"Objects": [{"Key": k} for k in chunk], "Quiet": True}
                )
            except ClientError:
                pass
