"""
This file runs first. It creates the main FastAPI application instance and connects all files together.
It sets up environment and opens the door for incoming requests.
"""
import logging
import os
from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from api.database import Database
from api.errors import install_error_handlers
from api.evidence import EvidenceStorage
from api.factories import create_mapper, create_service
from api.retention import RetentionScheduler
from api.routers import auth as auth_routes
from api.routers import facilities as facility_routes
from api.routers import reports as report_routes
from api.routers import system as system_routes
from api.routers import violations as violation_routes
from api.routes import router
from api.settings import get_settings

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
logger = logging.getLogger("cim.api")


#lifespan function
@asynccontextmanager
async def lifespan(app: FastAPI):
    settings = get_settings()
    app.state.settings = settings
    app.state.db = Database(settings.database)
    if settings.r2_access_key and settings.r2_secret_key and settings.r2_endpoint:
        from api.r2_storage import R2EvidenceStorage
        app.state.evidence_storage = R2EvidenceStorage(
            endpoint=settings.r2_endpoint,
            access_key=settings.r2_access_key,
            secret_key=settings.r2_secret_key,
            bucket=settings.r2_bucket,
            presign_ttl=settings.r2_presign_ttl_seconds,
        )
        logger.info("Evidence storage: Cloudflare R2 bucket '%s'", settings.r2_bucket)
    else:
        app.state.evidence_storage = EvidenceStorage(settings.evidence_dir)
        logger.info("Evidence storage: Local disk '%s'", settings.evidence_dir)
    app.state.evidence_storage.ensure_root()
    app.state.get_mapper = create_mapper()
    # AI models are only needed for /predictions; loading them is optional (CIM_ENABLE_INFERENCE).
    app.state.get_service = create_service() if settings.enable_inference else None

    try:
        yield # Server is running
    finally:
        pass


#Create FastAPI
app=FastAPI(
    title="Campus Integrity Monitor API",
    description=(
        "API trung gian giữa Desktop, Web và SQL Server: nhận bản ghi vi phạm và ảnh bằng chứng từ Desktop, "
        "tra cứu/xử lý bản ghi, thống kê, báo cáo, quản lý tài khoản, khu vực và camera. "
        "Hệ thống không nhận diện và không lưu danh tính sinh viên trong bản ghi AI."
    ),
    version="2.0.0",
    lifespan=lifespan
)

cors_origins_env = os.getenv("CIM_CORS_ORIGINS", "")
if cors_origins_env:
    cors_origins = [o.strip() for o in cors_origins_env.split(",") if o.strip()]
else:
    cors_origins = [
        "http://localhost:3000",
        "http://localhost:5173",
        "http://localhost:8000",
        "http://127.0.0.1:8000",
        "http://localhost:8001",
        "http://127.0.0.1:8001",
    ]

app.add_middleware(
    CORSMiddleware,
    allow_origins=cors_origins,
    allow_origin_regex=r"^https?://(localhost|127\.0\.0\.1)(:\d+)?$",
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

install_error_handlers(app)
app.include_router(system_routes.router)
app.include_router(auth_routes.router)
app.include_router(facility_routes.router)
app.include_router(violation_routes.router)
app.include_router(report_routes.router)
app.include_router(router)
