import pytest

from api.security import (TokenError, create_token, generate_device_key, hash_device_key, hash_password,
                          validate_password_strength, verify_device_key, verify_password, verify_token)

SECRET = "s" * 40


def test_password_hash_roundtrip_and_salt():
    first = hash_password("Abcdef12")
    second = hash_password("Abcdef12")
    assert first != second  # random salt
    assert first.startswith("$2y$")  # PHP/Laravel bcrypt prefix, shared TAIKHOAN.PASSWORD_HASH
    assert verify_password("Abcdef12", first)
    assert not verify_password("abcdef12", first)
    assert not verify_password("Abcdef12", "garbage")
    assert not verify_password("Abcdef12", first[:-5])


def test_verify_password_accepts_legacy_scrypt_and_bcrypt_variants():
    import base64
    import hashlib

    import bcrypt

    salt = b"0123456789abcdef"
    digest = hashlib.scrypt(b"Abcdef12", salt=salt, n=2 ** 14, r=8, p=1, dklen=32)
    enc = lambda b: base64.urlsafe_b64encode(b).rstrip(b"=").decode()  # noqa: E731
    assert verify_password("Abcdef12", f"scrypt${2 ** 14}$8$1${enc(salt)}${enc(digest)}")
    assert verify_password("Abcdef12", bcrypt.hashpw(b"Abcdef12", bcrypt.gensalt(4)).decode())  # $2b$


def test_password_strength_rules():
    assert validate_password_strength("Abcdef12") == []
    assert validate_password_strength("short1A")
    assert validate_password_strength("alllowercase1")
    assert validate_password_strength("NoDigitsHere")


def test_token_roundtrip_expiry_and_tamper():
    token, expires_at = create_token(7, 3, SECRET, ttl_minutes=1, now=1_000)
    payload = verify_token(token, SECRET, now=1_030)
    assert (payload.account_id, payload.version, payload.expires_at) == (7, 3, expires_at)
    with pytest.raises(TokenError, match="hết hạn"):
        verify_token(token, SECRET, now=expires_at + 1)
    body, signature = token.split(".")
    with pytest.raises(TokenError):
        verify_token(body + "." + signature[:-2] + "AA", SECRET, now=1_030)
    with pytest.raises(TokenError):
        verify_token(token, "o" * 40, now=1_030)


def test_token_requires_long_secret():
    with pytest.raises(TokenError):
        create_token(1, 1, "short", ttl_minutes=5)
    with pytest.raises(TokenError):
        create_token(1, 1, None, ttl_minutes=5)


def test_device_key_hash_and_verify():
    key = generate_device_key()
    stored = hash_device_key(key)
    assert len(stored) == 64 and key not in stored
    assert verify_device_key(key, stored)
    assert not verify_device_key(key + "x", stored)
    assert not verify_device_key(key, None)
