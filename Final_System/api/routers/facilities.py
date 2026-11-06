"""Areas, cameras and semesters."""
from __future__ import annotations

from fastapi import APIRouter, Depends, Path, Query

from api.database import Database
from api.dependencies import ROLE_ADMIN, CurrentUser, get_db, require_roles
from api.repositories import facilities
from api.v1_schemas import AreaRequest, CameraRequest, SemesterDatesRequest, SemesterRequest

router = APIRouter(prefix="/api/v1", tags=["Khu vực, camera & học kỳ"])


@router.get("/areas", summary="Danh sách khu vực")
def list_areas(include_inactive: bool = True, _: CurrentUser = Depends(require_roles()),
               db: Database = Depends(get_db)):
    return {"items": facilities.list_areas(db, include_inactive)}


@router.post("/areas", status_code=201, summary="Thêm khu vực (Quản trị viên)")
def create_area(body: AreaRequest, _: CurrentUser = Depends(require_roles(ROLE_ADMIN)), db: Database = Depends(get_db)):
    return {"data": facilities.save_area(db, None, body.code, body.name, body.description, body.active)}


@router.put("/areas/{area_id}", summary="Sửa / ngừng sử dụng khu vực (Quản trị viên)")
def update_area(body: AreaRequest, area_id: int = Path(ge=1), _: CurrentUser = Depends(require_roles(ROLE_ADMIN)),
                db: Database = Depends(get_db)):
    return {"data": facilities.save_area(db, area_id, body.code, body.name, body.description, body.active)}


@router.get("/cameras", summary="Danh sách camera")
def list_cameras(area_id: int | None = Query(default=None, ge=1), _: CurrentUser = Depends(require_roles()),
                 db: Database = Depends(get_db)):
    return {"items": facilities.list_cameras(db, area_id=area_id)}


@router.post("/cameras", status_code=201, summary="Thêm camera (Quản trị viên)")
def create_camera(body: CameraRequest, _: CurrentUser = Depends(require_roles(ROLE_ADMIN)),
                  db: Database = Depends(get_db)):
    camera = facilities.save_camera(db, None, body.code, body.name, body.area_id, body.location, body.active)
    return {"data": camera, "message": "Đã thêm camera thành công."}


@router.put("/cameras/{camera_id}", summary="Sửa / ngừng sử dụng camera (Quản trị viên)")
def update_camera(body: CameraRequest, camera_id: int = Path(ge=1), _: CurrentUser = Depends(require_roles(ROLE_ADMIN)),
                  db: Database = Depends(get_db)):
    camera = facilities.save_camera(db, camera_id, body.code, body.name, body.area_id, body.location, body.active)
    return {"data": camera}


@router.get("/semesters", summary="Danh sách học kỳ")
def list_semesters(_: CurrentUser = Depends(require_roles()), db: Database = Depends(get_db)):
    return {"items": facilities.list_semesters(db)}


@router.post("/semesters", status_code=201, summary="Thêm học kỳ")
def create_semester(body: SemesterRequest, _: CurrentUser = Depends(require_roles()),
                    db: Database = Depends(get_db)):
    return {"data": facilities.save_semester(db, body.academic_year, body.term, body.start_date, body.end_date,
                                             creating=True)}


@router.put("/semesters/{academic_year}/{term}", summary="Sửa ngày bắt đầu/kết thúc của học kỳ")
def update_semester(body: SemesterDatesRequest, academic_year: str = Path(pattern=r"^\d{4}-\d{4}$"),
                    term: int = Path(ge=1, le=3), _: CurrentUser = Depends(require_roles()),
                    db: Database = Depends(get_db)):
    return {"data": facilities.save_semester(db, academic_year, term, body.start_date, body.end_date,
                                             creating=False)}


@router.delete("/semesters/{academic_year}/{term}", summary="Xóa học kỳ (bị chặn khi đã có vi phạm liên kết)")
def delete_semester(academic_year: str = Path(pattern=r"^\d{4}-\d{4}$"), term: int = Path(ge=1, le=3),
                    _: CurrentUser = Depends(require_roles()), db: Database = Depends(get_db)):
    facilities.delete_semester(db, academic_year, term)
    return {"data": {"deleted": True}}

@router.delete("/areas/{area_id}", summary="Xóa khu vực (Quản trị viên)")
def delete_area(area_id: int = Path(ge=1), _: CurrentUser = Depends(require_roles(ROLE_ADMIN)),
                db: Database = Depends(get_db)):
    facilities.delete_area(db, area_id)
    return {"data": {"deleted": True}}


@router.delete("/cameras/{camera_id}", summary="Xóa camera (Quản trị viên)")
def delete_camera(camera_id: int = Path(ge=1), _: CurrentUser = Depends(require_roles(ROLE_ADMIN)),
                  db: Database = Depends(get_db)):
    facilities.delete_camera(db, camera_id)
    return {"data": {"deleted": True}}
