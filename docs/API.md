# API Campus Integrity Monitor (v1)

Tài liệu tương tác (OpenAPI/Swagger) có sẵn khi chạy API: `http://<host>:8000/docs`.

## Quy ước chung

- Base URL mặc định: `http://127.0.0.1:8000`
- Thời gian dùng **giờ địa phương của trường** (mặc định UTC+7), định dạng ISO 8601, ví dụ `2026-10-13T08:15:30`.
- Lỗi luôn có dạng:

```json
{ "error": { "code": "validation_error", "message": "Dữ liệu gửi lên không hợp lệ.", "details": [ ... ] } }
```

| HTTP | `code` thường gặp | Ý nghĩa |
|---|---|---|
| 400 | `bad_request`, `wrong_password` | Yêu cầu sai |
| 401 | `unauthorized`, `invalid_credentials` | Chưa đăng nhập / token hết hạn / sai khóa thiết bị |
| 403 | `forbidden`, `account_disabled` | Không đủ quyền, camera hoặc tài khoản đã ngừng |
| 404 | `not_found` | Không tìm thấy |
| 409 | `conflict`, `violation_type_inactive` | Trùng mã, `event_id` thuộc camera khác, loại lỗi đã ngừng |
| 410 | `evidence_unavailable` | Ảnh bằng chứng hết hạn/đã xóa/mất tệp (bản ghi vẫn còn) |
| 413 / 415 | `payload_too_large`, `unsupported_media_type` | Ảnh quá lớn / sai định dạng |
| 422 | `validation_error` | Dữ liệu không hợp lệ |
| 423 | `account_locked` | Tạm khóa sau nhiều lần đăng nhập sai |
| 503 | `database_unavailable` | Không kết nối được SQL Server |

### Xác thực

- **Người dùng Web**: `POST /api/v1/auth/login` → `Authorization: Bearer <access_token>`. Vai trò và trạng thái
  tài khoản được kiểm tra lại trong database ở mỗi request; đổi mật khẩu, vô hiệu hóa hoặc đổi vai trò sẽ thu hồi token cũ.
  Trình duyệt **không** giữ token: Web PHP lưu token trong session phía server và gọi API qua `Website/ui/api/cim.php`.
- **Desktop**: header `X-Camera-Code` + `X-Device-Key`. Khóa thiết bị do Quản trị viên tạo khi thêm camera (hiển thị một lần),
  database chỉ lưu SHA-256 của khóa.

### Phân quyền

| Chức năng | ADMIN | CTSV |
|---|:-:|:-:|
| Tra cứu, xử lý bản ghi vi phạm, xem ảnh, dashboard, báo cáo, xuất Excel/PDF | ✔ | ✔ |
| Xem khu vực/camera/học kỳ | ✔ (kèm nguồn video) | ✔ (ẩn nguồn video) |
| Thêm/sửa/ngừng khu vực, camera, cấp lại khóa thiết bị, học kỳ | ✔ | ✘ |
| Quản lý tài khoản | ✔ | ✘ |
| Sinh viên, Khoa/Ngành/Lớp, import Excel (Web PHP) | ✔ | ✔ |
| Loại vi phạm (Web PHP) | ✔ | chỉ xem |

## Hệ thống

| Method | Path | Mô tả |
|---|---|---|
| GET | `/health` | API đang chạy (không chạm database) |
| GET | `/api/v1/health` | Trạng thái database, migration, thư mục ảnh. 200 khi ổn, 503 khi lỗi. Không trả connection string. |

```json
{ "status": "ok",
  "database": { "status": "ok", "missing_tables": [], "migrations": ["001"] },
  "evidence_storage": { "writable": true, "retention_days": 30 },
  "inference": { "enabled": false }, "auth_configured": true, "server_time": "2026-10-13T20:10:00" }
```

## Desktop → API

### `GET /api/v1/device/me`
Kiểm tra mã camera + khóa thiết bị. Trả về camera, khu vực, các mã lỗi hợp lệ.

### `POST /api/v1/violations` (multipart/form-data)

| Trường | Bắt buộc | Mô tả |
|---|:-:|---|
| `event_id` | ✔ | UUID do Desktop sinh; **khóa chống trùng** (gửi lại cùng `event_id` không tạo bản ghi mới) |
| `violation_code` | ✔ | `UNIFORM` (sai đồng phục), `NO_CARD` (thiếu thẻ), `BOTH` (cả hai) |
| `occurred_at` | ✔ | ISO 8601; không quá 5 phút ở tương lai, không cũ hơn `CIM_EVENT_MAX_AGE_DAYS` |
| `image` | ✔ | Ảnh crop JPEG/PNG ≤ `CIM_EVIDENCE_MAX_BYTES` (mặc định 2 MB), 16..4096 px; được kiểm tra và mã hóa lại JPEG |
| `track_id` | | Số nguyên ≥ 0 |
| `uniform_confidence`, `card_confidence` | | Số trong khoảng 0..1 |

Mọi trường khác bị từ chối (422); đặc biệt **không nhận** `ma_sv`, `mssv`, `ho_ten`, `student_id`...

```bash
curl -X POST http://127.0.0.1:8000/api/v1/violations \
  -H "X-Camera-Code: CAM_CC01" -H "X-Device-Key: cimdev_xxx" \
  -F event_id=4f0c8a43-3f1d-4a7e-9a83-5c1f0d2b9e11 -F violation_code=NO_CARD \
  -F occurred_at=2026-10-13T07:42:10+07:00 -F track_id=12 -F card_confidence=0.93 \
  -F image=@crop.jpg;type=image/jpeg
```

`201 Created` (lần đầu) hoặc `200 OK` với `"duplicate": true` (gửi lại):

```json
{ "data": { "id": 128, "event_id": "4f0c8a43-3f1d-4a7e-9a83-5c1f0d2b9e11", "occurred_at": "2026-10-13T07:42:10",
            "type": { "id": 2, "code": "NO_CARD", "name": "Thiếu thẻ sinh viên" },
            "camera": { "id": 1, "code": "CAM_CC01", "name": "Camera cổng chính 01" },
            "area": { "id": 1, "code": "CONG_CHINH", "name": "Cổng chính" },
            "track_id": 12, "uniform_confidence": null, "card_confidence": 0.93,
            "evidence": { "status": "available", "available": true, "expires_at": "2026-11-12T07:42:11",
                          "url": "/api/v1/violations/128/evidence" },
            "processing": { "status": "MOI", "note": null, "processed_by": null, "processed_at": null } },
  "duplicate": false }
```

## Web → API (Bearer token)

### Xác thực & tài khoản
| Method | Path | Body |
|---|---|---|
| POST | `/api/v1/auth/login` | `{"username": "...", "password": "..."}` → `access_token`, `expires_at` (epoch), `user` |
| GET | `/api/v1/auth/me` | |
| POST | `/api/v1/auth/change-password` | `{"current_password", "new_password"}` (≥ 8 ký tự, có hoa/thường/số) |
| GET/POST | `/api/v1/accounts` | POST: `{"username", "full_name", "role": "ADMIN"|"CTSV", "password"}` |
| PUT | `/api/v1/accounts/{id}` | `{"full_name", "role", "active"}` — không tự khóa mình, luôn còn ≥ 1 admin |
| POST | `/api/v1/accounts/{id}/reset-password` | `{"new_password"}` |

### Khu vực, camera, học kỳ
| Method | Path | Ghi chú |
|---|---|---|
| GET/POST | `/api/v1/areas` | `{"code", "name", "description", "active"}` |
| PUT | `/api/v1/areas/{id}` | Ngừng sử dụng: `"active": false` (không xóa dữ liệu) |
| GET/POST | `/api/v1/cameras` | `{"code", "name", "area_id", "location", "stream_source", "active"}`; POST trả `device_key` **một lần** |
| PUT | `/api/v1/cameras/{id}` | |
| POST | `/api/v1/cameras/{id}/rotate-key` | Khóa cũ mất hiệu lực ngay |
| GET/POST | `/api/v1/semesters` | `{"code", "name", "start_date", "end_date"}`; không cho trùng khoảng ngày |
| PUT/DELETE | `/api/v1/semesters/{id}` | Xóa học kỳ không ảnh hưởng bản ghi vi phạm |

### Bản ghi vi phạm
| Method | Path | Ghi chú |
|---|---|---|
| GET | `/api/v1/violation-types` | |
| GET | `/api/v1/violations` | Lọc: `date_from`, `date_to`, `area_id`, `camera_id`, `type_id`, `status` (`MOI`/`DA_XEM`/`DA_XU_LY`/`BO_QUA`); `page`, `page_size` (≤100), `sort` (`newest`/`oldest`) |
| GET | `/api/v1/violations/{id}` | |
| PATCH | `/api/v1/violations/{id}` | `{"status": "DA_XU_LY", "note": "..."}` — chỉ trạng thái xử lý nội bộ; không gán danh tính, không trừ điểm |
| GET | `/api/v1/violations/{id}/evidence` | `image/jpeg`; `410` nếu ảnh hết hạn/đã xóa/mất tệp |

### Thống kê & báo cáo
Tham số kỳ chung: `period` = `day` \| `week` \| `month` \| `semester` \| `custom`, kèm `anchor` (ngày), `month` (`YYYY-MM`),
`semester_id`, hoặc `date_from` + `date_to` (custom, tối đa 400 ngày); lọc thêm `area_id`, `camera_id`, `type_id`.

| Method | Path | Kết quả |
|---|---|---|
| GET | `/api/v1/dashboard` | `counters` (hôm nay/tuần/tháng/học kỳ hiện tại) + `summary` của kỳ chọn |
| GET | `/api/v1/reports/summary` | `total`, `by_type`, `by_day`, `by_hour`, `by_weekday`, `by_area`, `by_camera`, `hotspots` (khu vực × khung giờ), `by_status` |
| GET | `/api/v1/reports/export?format=xlsx\|pdf` | Tệp Excel (nhiều sheet) hoặc PDF có tiêu đề, thời điểm lập, bộ lọc |

## API của Web PHP (session + `X-CSRF-Token`)

Các chức năng sinh viên/danh mục chạy trong `Website/ui/api/*.php` (ghi trực tiếp SQL Server bằng PDO sqlsrv), dùng chung đăng nhập Web.

| Method | Path | Mô tả |
|---|---|---|
| GET | `api/students.php?action=init\|list\|detail\|catalog\|export\|template` | Sinh viên, tìm kiếm/lọc/phân trang, xuất/nhập Excel |
| POST/DELETE | `api/students.php` | `save`, `import-commit`, xóa hồ sơ (bị chặn nếu còn vi phạm tham chiếu) |
| GET | `api/students.php?action=violations&id={ID_SV}` | Lịch sử vi phạm thủ công (`CT_VIPHAM`) + loại vi phạm đang dùng |
| POST | `api/students.php` `{"action":"violation-add","violation":{"student_id","type_id","date":"YYYY-MM-DD","note","confirm_duplicate"}}` | Ghi nhận thủ công. `409` + `code: duplicate_same_day` khi đã có cùng loại cùng ngày (gửi lại với `confirm_duplicate: true`). `422`: ngày tương lai/không hợp lệ, loại lỗi ngừng dùng, ghi chú > 500 ký tự |
| POST | `api/students.php` `{"action":"violation-delete","id","student_id"}` | Xóa một lần ghi nhận (phải đúng sinh viên) |
| GET/POST | `api/catalogs.php` | Khoa/Ngành/Lớp/Loại vi phạm (loại vi phạm: chỉ ADMIN được sửa) |

## AI trực tiếp (giữ tương thích bản cũ)
`POST /predictions` (multipart: `image`, `camera_name`) — chỉ hoạt động khi `CIM_ENABLE_INFERENCE=true`.
Mỗi `camera_name` có tracker và lịch sử bỏ phiếu riêng.
