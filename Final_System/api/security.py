"""
Password hashing, signed access tokens and camera device keys.

Password hash format: bcrypt "$2y$..." (shared with Laravel); legacy scrypt$<n>$<r>$<p>$<salt>$<hash> still verifies.
Access token format:  <payload_b64url>.<hmac_sha256_b64url>
  payload = {"sub": account_id, "ver": token_version, "iat": ..., "exp": ..., "jti": ...}
  The role is NOT trusted from the token; it is re-read from the database on every request,
  together with the active flag and token version (password change / deactivation revoke tokens).
"""
from __future__ import annotations

import base64
import hashlib
import hmac
import json
import re
import secrets
import time
from dataclasses import dataclass

import bcrypt

BCRYPT_ROUNDS = 10
SCRYPT_N = 2 ** 14
SCRYPT_R = 8
SCRYPT_P = 1
SCRYPT_DKLEN = 32
MIN_SECRET_LENGTH = 32

PASSWORD_MIN_LENGTH = 8
PASSWORD_MAX_LENGTH = 128


class TokenError(ValueError):
    pass


def _b64encode(data: bytes) -> str:
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode("ascii")


def _b64decode(text: str) -> bytes:
    padding = "=" * (-len(text) % 4)
    return base64.urlsafe_b64decode(text + padding)


def validate_password_strength(password: str) -> list[str]:
    """Return a list of problems (empty when the password is acceptable)."""
    problems = []
    if not isinstance(password, str):
        return ["Mật khẩu không hợp lệ."]
    if len(password) < PASSWORD_MIN_LENGTH:
        problems.append(f"Mật khẩu phải có ít nhất {PASSWORD_MIN_LENGTH} ký tự.")
    if len(password) > PASSWORD_MAX_LENGTH:
        problems.append(f"Mật khẩu không được vượt quá {PASSWORD_MAX_LENGTH} ký tự.")
    if not re.search(r"[A-Z]", password):
        problems.append("Mật khẩu cần ít nhất một chữ in hoa.")
    if not re.search(r"[a-z]", password):
        problems.append("Mật khẩu cần ít nhất một chữ thường.")
    if not re.search(r"[0-9]", password):
        problems.append("Mật khẩu cần ít nhất một chữ số.")
    return problems


def hash_password(password: str) -> str:
    """bcrypt with the PHP "$2y$" prefix so Laravel's password_verify() accepts the same hash."""
    digest = bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt(rounds=BCRYPT_ROUNDS)).decode("ascii")
    return "$2y$" + digest[4:]


def verify_password(password: str, stored_hash: str) -> bool:
    if not isinstance(stored_hash, str) or not isinstance(password, str):
        return False
    if stored_hash[:4] in ("$2y$", "$2b$", "$2a$"):
        try:
            return bcrypt.checkpw(password.encode("utf-8"), ("$2b$" + stored_hash[4:]).encode("ascii"))
        except ValueError:
            return False
    # Legacy scrypt hashes created by earlier FastAPI versions.
    try:
        scheme, n, r, p, salt_b64, hash_b64 = stored_hash.split("$")
        if scheme != "scrypt":
            return False
        expected = _b64decode(hash_b64)
        digest = hashlib.scrypt(password.encode("utf-8"), salt=_b64decode(salt_b64), n=int(n), r=int(r),
                                p=int(p), dklen=len(expected))
    except (ValueError, TypeError):
        return False
    return hmac.compare_digest(digest, expected)


# A valid hash for timing-equalisation when the username does not exist.
DUMMY_PASSWORD_HASH = hash_password(secrets.token_urlsafe(16))


@dataclass(frozen=True)
class TokenPayload:
    account_id: int
    version: int
    issued_at: int
    expires_at: int


def _require_secret(secret: str | None) -> bytes:
    if not secret or len(secret) < MIN_SECRET_LENGTH:
        raise TokenError(
            f"CIM_AUTH_SECRET chưa được cấu hình hoặc ngắn hơn {MIN_SECRET_LENGTH} ký tự."
        )
    return secret.encode("utf-8")


def create_token(account_id: int, version: int, secret: str | None, ttl_minutes: int,
                 now: int | None = None) -> tuple[str, int]:
    key = _require_secret(secret)
    issued_at = int(now if now is not None else time.time())
    expires_at = issued_at + ttl_minutes * 60
    payload = {"sub": account_id, "ver": version, "iat": issued_at, "exp": expires_at,
               "jti": secrets.token_hex(8)}
    body = _b64encode(json.dumps(payload, separators=(",", ":")).encode("utf-8"))
    signature = _b64encode(hmac.new(key, body.encode("ascii"), hashlib.sha256).digest())
    return f"{body}.{signature}", expires_at


def verify_token(token: str, secret: str | None, now: int | None = None) -> TokenPayload:
    key = _require_secret(secret)
    if not isinstance(token, str) or token.count(".") != 1 or len(token) > 2048:
        raise TokenError("Token không hợp lệ.")
    body, signature = token.split(".")
    expected = _b64encode(hmac.new(key, body.encode("ascii"), hashlib.sha256).digest())
    if not hmac.compare_digest(signature, expected):
        raise TokenError("Chữ ký token không hợp lệ.")
    try:
        payload = json.loads(_b64decode(body))
        result = TokenPayload(account_id=int(payload["sub"]), version=int(payload["ver"]),
                              issued_at=int(payload["iat"]), expires_at=int(payload["exp"]))
    except (ValueError, KeyError, TypeError) as exc:
        raise TokenError("Nội dung token không hợp lệ.") from exc
    current = int(now if now is not None else time.time())
    if result.expires_at <= current:
        raise TokenError("Phiên đăng nhập đã hết hạn.")
    return result


def generate_device_key() -> str:
    """Random 256-bit key shown once to the administrator; only its SHA-256 is stored."""
    return "cimdev_" + secrets.token_urlsafe(32)


def hash_device_key(device_key: str) -> str:
    return hashlib.sha256(device_key.encode("utf-8")).hexdigest()


def verify_device_key(device_key: str, stored_hash: str | None) -> bool:
    if not stored_hash or not device_key:
        return False
    return hmac.compare_digest(hash_device_key(device_key), stored_hash.strip().lower())
