import pytest

from api.security import (TokenError, create_token, generate_device_key, hash_device_key, hash_password,
                          validate_password_strength, verify_device_key, verify_password, verify_token)

SECRET = "s" * 40


def test_password_hash_roundtrip_and_salt():
    first = hash_password("Abcdef12")
    second = hash_password("Abcdef12")
    assert first != second  # random salt
    assert first.startswith("scrypt$")
    assert verify_password("Abcdef12", first)
    assert not verify_password("abcdef12", first)
    assert not verify_password("Abcdef12", "garbage")


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
