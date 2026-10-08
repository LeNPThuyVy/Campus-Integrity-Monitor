"""
Endpoints of the central server for events, images, statistics and reports.
Two kinds of API key (header X-API-Key):
 - device key: given when a device is registered, can only write that device's events and images
 - admin key (CIM_ADMIN_API_KEY): register devices, read events/images, statistics, export reports
"""
import secrets
from datetime import date, datetime, timedelta, timezone
from fastapi import APIRouter, Depends, Header, HTTPException, Request, Response
from api.event_schemas import (
    EventBatchRequest, EventBatchResponse, ImageUploadUrlResponse, ImageConfirmRequest,
    ImageUrlResponse, EventOut, DeviceRegisterRequest, DeviceRegisterResponse
)
from api.event_store import EventStore
from api.object_store import ObjectStore
from ai.reporting.report_service import ReportService
from ai.reporting.report_stats import build_statistics, get_period_range, get_previous_range, LOCAL_TZ
from api.event_schemas import (
    LoginRequest, LoginResponse, ReviewRequest
)
from api.auth import verify_password, create_access_token, decode_access_token
import csv
from io import StringIO

router = APIRouter(prefix="/v1")

PRESIGNED_PUT_SECONDS = 300
PRESIGNED_GET_SECONDS = 300
REPORT_IMAGE_COUNT = 3
DOCX_MEDIA_TYPE = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"


# ----------------------------------------------------------------------
# Dependencies
# ----------------------------------------------------------------------

def get_event_store(request: Request) -> EventStore:
    store = getattr(request.app.state, "event_store", None)
    if store is None:
        raise HTTPException(status_code=503, detail="Database is not configured (CIM_DATABASE_URL)")
    return store


def get_object_store(request: Request) -> ObjectStore:
    object_store = getattr(request.app.state, "object_store", None)
    if object_store is None:
        raise HTTPException(status_code=503, detail="Cloud storage is not configured (R2_* variables)")
    return object_store


def get_device(x_api_key: str = Header(default=""), store: EventStore = Depends(get_event_store)) -> dict:
    device = store.get_device_by_api_key(x_api_key) if x_api_key else None
    if device is None:
        raise HTTPException(status_code=401, detail="Invalid device API key")
    return device


def require_admin(request: Request, x_api_key: str = Header(default=""), authorization: str = Header(default="")) -> str:
    # Accept either API Key or Bearer token
    admin_key = getattr(request.app.state, "admin_api_key", "")
    if x_api_key and admin_key and secrets.compare_digest(x_api_key, admin_key):
        return "admin_api_key"
    
    if authorization and authorization.startswith("Bearer "):
        token = authorization.split(" ")[1]
        payload = decode_access_token(token)
        return payload.get("sub", "unknown")
    
    raise HTTPException(status_code=401, detail="Invalid admin credentials")


def _build_image_key(event: dict) -> str:
    return f"{event['device_id']}/{event['first_seen']:%Y/%m/%d}/{event['event_uuid']}.jpg"


def _get_own_event(store: EventStore, event_uuid: str, device: dict) -> dict:
    event = store.get_event(event_uuid)
    #404 for both "not found" and "belongs to another device" so devices can't probe each other
    if event is None or event["device_id"] != device["device_id"]:
        raise HTTPException(status_code=404, detail="Event not found")
    return event


def _utc(value: datetime) -> datetime:
    return value if value.tzinfo else value.replace(tzinfo=timezone.utc)


# ----------------------------------------------------------------------
# Devices (admin)
# ----------------------------------------------------------------------

@router.post("/devices", response_model=DeviceRegisterResponse, dependencies=[Depends(require_admin)])
def register_device(body: DeviceRegisterRequest, store: EventStore = Depends(get_event_store)):
    """
    Register a device (or reset its key). The api_key is shown only once, copy it to the device's .env
    """
    api_key = secrets.token_urlsafe(32)
    store.register_device(device_id=body.device_id, location=body.location, api_key=api_key)
    return DeviceRegisterResponse(device_id=body.device_id, location=body.location, api_key=api_key)


# ----------------------------------------------------------------------
# Device endpoints: events and images
# ----------------------------------------------------------------------

@router.post("/events/batch", response_model=EventBatchResponse)
def post_events(body: EventBatchRequest, device: dict = Depends(get_device), store: EventStore = Depends(get_event_store)):
    new_events = []
    for item in body.events:
        data = item.model_dump()
        data["event_uuid"] = str(item.event_uuid)
        new_events.append(data)
    store.upsert_events(device_id=device["device_id"], new_events=new_events)
    return EventBatchResponse(accepted=len(new_events))


@router.post("/events/{event_uuid}/image-upload-url", response_model=ImageUploadUrlResponse)
def get_image_upload_url(event_uuid: str, device: dict = Depends(get_device),
                         store: EventStore = Depends(get_event_store),
                         object_store: ObjectStore = Depends(get_object_store)):
    event = _get_own_event(store, event_uuid, device)
    if event["image_status"] == "none":
        raise HTTPException(status_code=409, detail="This event has no image")
    image_key = _build_image_key(event)
    upload_url = object_store.presigned_put_url(image_key, "image/jpeg", PRESIGNED_PUT_SECONDS)
    return ImageUploadUrlResponse(upload_url=upload_url, image_key=image_key, expires_in=PRESIGNED_PUT_SECONDS)


@router.post("/events/{event_uuid}/image-confirm")
def confirm_image(event_uuid: str, body: ImageConfirmRequest, device: dict = Depends(get_device),
                  store: EventStore = Depends(get_event_store),
                  object_store: ObjectStore = Depends(get_object_store)):
    event = _get_own_event(store, event_uuid, device)
    #The key is computed by the server, never trusted from the client
    image_key = _build_image_key(event)
    if body.image_key != image_key:
        raise HTTPException(status_code=400, detail="image_key doesn't match")
    if not object_store.exists(image_key):
        raise HTTPException(status_code=409, detail="Image has not been uploaded")
    store.set_image(event_uuid=event_uuid, image_key=image_key, status="uploaded")
    return {"status": "uploaded"}


# ----------------------------------------------------------------------
# Admin / web endpoints: read
# ----------------------------------------------------------------------

@router.get("/events", response_model=list[EventOut], dependencies=[Depends(require_admin)])
def list_events(start: datetime, end: datetime, device_id: str | None = None, violation_only: bool = False,
                review_status: str | None = None, limit: int = 100, offset: int = 0, store: EventStore = Depends(get_event_store)):
    if limit < 1 or limit > 500:
        raise HTTPException(status_code=400, detail="limit must be between 1 and 500")
    location_map = store.get_location_map()
    rows = store.list_events(start=_utc(start), end=_utc(end), device_id=device_id,
                             violation_only=violation_only, review_status=review_status, limit=limit, offset=offset)
    return [EventOut(location=location_map.get(row["device_id"]), **{
        key: row[key] for key in row.keys() if key != "location"
    }) for row in rows]


@router.get("/events/{event_uuid}/image-url", response_model=ImageUrlResponse, dependencies=[Depends(require_admin)])
def get_image_url(event_uuid: str, store: EventStore = Depends(get_event_store),
                  object_store: ObjectStore = Depends(get_object_store)):
    event = store.get_event(event_uuid)
    if event is None or event["image_status"] != "uploaded":
        raise HTTPException(status_code=404, detail="Image not found")
    url = object_store.presigned_get_url(event["image_key"], PRESIGNED_GET_SECONDS)
    return ImageUrlResponse(url=url, expires_in=PRESIGNED_GET_SECONDS)


def _build_period_stats(store: EventStore, period: str, ref_date: date | None) -> tuple[dict, datetime, datetime]:
    ref_date = ref_date or datetime.now(LOCAL_TZ).date()
    try:
        start, end = get_period_range(period, ref_date)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    previous_start, previous_end = get_previous_range(period, start)
    stats = build_statistics(
        events=store.get_events_in_range(start, end),
        period=period, start=start, end=end,
        location_map=store.get_location_map(),
        previous_events=store.get_events_in_range(previous_start, previous_end)
    )
    return stats, start, end


@router.get("/stats", dependencies=[Depends(require_admin)])
def get_stats(period: str = "day", date: date | None = None, store: EventStore = Depends(get_event_store)):
    stats, _, _ = _build_period_stats(store, period, date)
    return stats


@router.post("/reports", dependencies=[Depends(require_admin)])
def create_report(request: Request, period: str = "day", date: date | None = None,
                  store: EventStore = Depends(get_event_store)):
    """
    Returns a .docx report of the period (day | week | month)
    """
    report_service: ReportService | None = getattr(request.app.state, "report_service", None)
    if report_service is None:
        raise HTTPException(status_code=503, detail="Report is not configured (GEMINI_API_KEY)")
    stats, start, end = _build_period_stats(store, period, date)

    #Evidence images are optional: if the cloud storage isn't ready or an image fails, the report still works
    images = []
    object_store = getattr(request.app.state, "object_store", None)
    if object_store is not None:
        location_map = store.get_location_map()
        for item in store.get_violation_images(start, end, limit=REPORT_IMAGE_COUNT):
            try:
                caption = (f"{location_map.get(item['device_id'], item['device_id'])} - "
                           f"{_utc(item['first_seen']).astimezone(LOCAL_TZ):%d/%m/%Y %H:%M}")
                images.append((caption, object_store.get_bytes(item["image_key"])))
            except Exception:
                continue

    content = report_service.generate_docx(stats=stats, images=images)
    file_name = f"report_{period}_{start:%Y%m%d}.docx"
    return Response(content=content, media_type=DOCX_MEDIA_TYPE,
                    headers={"Content-Disposition": f'attachment; filename="{file_name}"'})

# ----------------------------------------------------------------------
# NEW API ROUTES (Phase 4 & 5)
# ----------------------------------------------------------------------

@router.post("/auth/login", response_model=LoginResponse)
def login(body: LoginRequest, request: Request, store: EventStore = Depends(get_event_store)):
    hashed = store.get_admin_password_hash(body.username)
    if not hashed or not verify_password(body.password, hashed):
        raise HTTPException(status_code=401, detail="Tên đăng nhập hoặc mật khẩu không đúng")
    
    token = create_access_token(data={"sub": body.username})
    
    # Log access
    with store._engine.begin() as conn:
        from sqlalchemy import text
        conn.execute(text("INSERT INTO access_logs (username, ip_address, user_agent, action) VALUES (:u, :ip, :ua, :a)"),
                     {"u": body.username, "ip": request.client.host if request.client else "unknown", 
                      "ua": request.headers.get("user-agent", "unknown"), "a": "login"})
    
    return LoginResponse(access_token=token)


@router.patch("/events/{event_uuid}/review")
def review_event(event_uuid: str, body: ReviewRequest, username: str = Depends(require_admin), store: EventStore = Depends(get_event_store)):
    success = store.set_review(event_uuid, body.status, username)
    if not success:
        raise HTTPException(status_code=404, detail="Event not found")
    return {"status": "success", "review_status": body.status}


@router.get("/events/export", dependencies=[Depends(require_admin)])
def export_events(start: datetime, end: datetime, store: EventStore = Depends(get_event_store)):
    rows = store.list_events(start=_utc(start), end=_utc(end), limit=100000)
    
    output = StringIO()
    writer = csv.writer(output)
    writer.writerow(["event_uuid", "device_id", "first_seen", "uniform_label", "card_label", "review_status", "reviewed_by"])
    
    for r in rows:
        writer.writerow([r["event_uuid"], r["device_id"], r["first_seen"], r["uniform_label"], r["card_label"], r.get("review_status", ""), r.get("reviewed_by", "")])
    
    return Response(content=output.getvalue(), media_type="text/csv", 
                    headers={"Content-Disposition": f'attachment; filename="events_export.csv"'})


@router.get("/health/devices", dependencies=[Depends(require_admin)])
def device_health(store: EventStore = Depends(get_event_store)):
    with store._engine.connect() as conn:
        from sqlalchemy import text
        rows = conn.execute(text("SELECT device_id, location, created_at FROM devices")).all()
    return [{"device_id": r[0], "location": r[1], "registered_at": r[2]} for r in rows]


@router.post("/maintenance/retention", dependencies=[Depends(require_admin)])
def cleanup_retention(days: int = 30, store: EventStore = Depends(get_event_store)):
    cutoff = datetime.now(timezone.utc) - timedelta(days=days)
    with store._engine.begin() as conn:
        from sqlalchemy import text
        res = conn.execute(text("DELETE FROM events WHERE first_seen < :c"), {"c": cutoff})
    return {"deleted_rows": res.rowcount}
