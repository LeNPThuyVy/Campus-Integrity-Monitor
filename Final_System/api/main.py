"""
This file runs first. It creates the main FastAPI application instance and connects all files together.
It sets up environment and opens the door for incoming requests.
"""
import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI

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
    app.state.evidence_storage = EvidenceStorage(settings.evidence_dir)
    app.state.evidence_storage.ensure_root()
    app.state.get_mapper = create_mapper()
    # AI models are only needed for /predictions; loading them is optional (CIM_ENABLE_INFERENCE).
    app.state.get_service = create_service() if settings.enable_inference else None

    missing = settings.database.missing_fields()
    if missing:
        logger.warning("Chưa cấu hình kết nối SQL Server (%s). Các API dữ liệu sẽ trả 503.", ", ".join(missing))
    if not settings.auth_secret or len(settings.auth_secret) < 32:
        logger.warning("CIM_AUTH_SECRET chưa được cấu hình (>= 32 ký tự). Đăng nhập Web sẽ không hoạt động.")

    scheduler = None
    if settings.enable_retention_job and not missing:
        scheduler = RetentionScheduler(app.state.db, app.state.evidence_storage, settings.retention_interval_minutes,
                                       settings.now_local)
        scheduler.start()
    try:
        yield # Server is running
    finally:
        if scheduler is not None:
            scheduler.stop()


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

install_error_handlers(app)
app.include_router(system_routes.router)
app.include_router(auth_routes.router)
app.include_router(facility_routes.router)
app.include_router(violation_routes.router)
app.include_router(report_routes.router)
app.include_router(router)
