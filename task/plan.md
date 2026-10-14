# Kế Hoạch Giải Quyết Conflict Khi Merge (origin/Qui vào thuy)

## Mục tiêu (Goal Description)
Hiện tại dự án đang trong quá trình merge nhánh `origin/Qui` (commit `45af3ea Update Campus Integrity Monitor`) vào nhánh `thuy`. 
Có 3 tệp tin đang rơi vào trạng thái conflict (`Unmerged paths`):
1. [`.gitignore`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/.gitignore)
2. [`Final_System/api/main.py`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Final_System/api/main.py)
3. [`Final_System/test/reporting/test_prompt_builder.py`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Final_System/test/reporting/test_prompt_builder.py)

---

## Chi tiết phân tích & Đề xuất giữ lại (Proposed Changes)

### 1. File [`.gitignore`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/.gitignore)

#### Hiện trạng & Nguyên nhân conflict
- Git thông báo `Binary files differ` do ở nhánh base từng có dòng `t\x00a\x00s\x00k\x00/\x00` chứa null bytes (hệ quả khi dùng lệnh PowerShell ghi file tự động mã hoá UTF-16LE).
- **Nhánh `thuy` (HEAD)** thêm:
  - `Training/`
  - `task/`
  - `Architecture_system.md`
- **Nhánh `origin/Qui`** thêm:
  - `.env.local`
  - `Final_System/storage/`
  - `Final_System/desktop/config.json`
  - `Final_System/desktop/outbox/`
  - `/tools/`

#### Quyết định chọn phần nào để giữ
- **Giữ TẤT CẢ** các mục bỏ qua của cả hai bên.
- **XOÁ** các byte rác / null-byte bị hỏng (`t a s k / `).
- Lưu file dưới dạng chuẩn **UTF-8 (no BOM)**.

```gitignore
# File
.history
.vscode
__pycache__

Training/
task/
Architecture_system.md

# Campus Integrity Monitor - local data and secrets
.env.local
Final_System/storage/
Final_System/desktop/config.json
Final_System/desktop/outbox/

# Local tools installed by scripts/setup-php-windows.ps1
/tools/
```

- **Lý do (Strategy):**
  - Nhánh `thuy` cần `task/`, `Architecture_system.md`, `Training/` theo quy định làm việc để không đẩy các file phụ/plan/dataset lên Git.
  - Nhánh `Qui` cần bỏ qua dữ liệu cục bộ (`storage/`), secrets (`.env.local`), cấu hình camera desktop (`config.json`), hàng đợi offline (`outbox/`) và PHP binaries (`/tools/`).
- **Ảnh hưởng:** 
  - Khắc phục lỗi Git coi `.gitignore` là file nhị phân.
  - Cả môi trường của Thuy và cấu trúc backend/website mới của Qui đều được bảo vệ, tránh commit nhầm file nhạy cảm hay dữ liệu lớn.

---

### 2. File [`Final_System/api/main.py`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Final_System/api/main.py)

#### Hiện trạng conflict
- **Nhánh `thuy` (HEAD):** Thêm cấu hình `CORSMiddleware` (đọc biến môi trường `CIM_CORS_ORIGINS` hoặc fallback về danh sách localhost:3000, 5173, 8000; regex domain localhost/127.0.0.1).
- **Nhánh `origin/Qui`:** Cập nhật kiến trúc API v2.0:
  - Thêm `install_error_handlers(app)`
  - Mount 5 router mới: `system_routes.router`, `auth_routes.router`, `facility_routes.router`, `violation_routes.router`, `report_routes.router`
  - Router AI suy luận `app.include_router(router)`.

#### Quyết định chọn phần nào để giữ
- **KẾT HỢP CẢ HAI (MERGE CẢ HAI):**
  1. Giữ toàn bộ router và error handler của nhánh `origin/Qui`.
  2. Giữ toàn bộ cấu hình `CORSMiddleware` của nhánh `thuy`, đồng thời chuyển các import (`from fastapi.middleware.cors import CORSMiddleware`, `import os`) lên đầu file theo đúng chuẩn PEP 8.

- **Lý do (Strategy):**
  - Backend của hệ thống hiện tại phục vụ cả Web UI (giao diện PHP/JS chạy qua port 8000 hoặc dev servers) và Desktop app. Nếu không có `CORSMiddleware` từ nhánh `thuy`, trình duyệt web sẽ chặn các request API do vi phạm chính sách CORS.
  - Nếu thiếu các router của nhánh `origin/Qui`, toàn bộ tính năng quản trị, đăng nhập, báo cáo, lưu trữ vi phạm của hệ thống mới sẽ không hoạt động.
- **Ảnh hưởng:** 
  - Hệ thống API chạy trơn tru với đầy đủ endpoint và chấp nhận request từ các client Web/Desktop một cách bảo mật.

---

### 3. File [`Final_System/test/reporting/test_prompt_builder.py`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Final_System/test/reporting/test_prompt_builder.py)

#### Hiện trạng conflict
- **Nhánh `thuy` (HEAD):** Là dạng kịch bản chạy thử nghiệm thủ công (hardcode danh sách `eventsA`, `eventsB`, `eventsC`, `eventsD` rồi `print(prompt)` ra màn hình, không có assert kiểm tra tự động).
- **Nhánh `origin/Qui`:** Đã chuyển đổi tệp thành bộ test tự động theo chuẩn **`pytest`**:
  - Hàm trợ giúp `make_event(track_id, uniform_label, card_label)`
  - Fixture `builder`
  - Các test case kiểm tra: `test_empty_events_prompt_says_no_data`, `test_events_are_listed_with_uniform_and_card_status`.

#### Quyết định chọn phần nào để giữ
- **GIỮ NỀN TẢNG CỦA NHÁNH QUI** (chuẩn pytest) VÀ **BỔ SUNG TEST CASE** lấy ý tưởng từ danh sách `eventsB/C/D` của Thuy.
- Xoá đoạn code script chạy thủ công (`eventsA/B/C/D` tĩnh và lệnh `print(prompt)`).

#### Nội dung hoàn thiện:
```python
from datetime import datetime
import pytest

import ai.config as my_config
from ai.reporting.models import Event
from ai.reporting.prompt_builder import PromptBuilder


def make_event(track_id, uniform_label, card_label):
    now = datetime(2026, 10, 13, 8, 0, 0)
    return Event(
        track_id=track_id,
        uniform_label=uniform_label,
        card_label=card_label,
        label=f"{uniform_label} | {card_label}",
        first_seen=now,
        last_seen=now,
    )


@pytest.fixture
def builder():
    return PromptBuilder(prompt_yaml_path=my_config.PROMPT_YAML_PATH)


def test_empty_events_prompt_says_no_data(builder):
    prompt = builder.build([])
    assert "HIỆN KHÔNG CÓ DỮ LIỆU SỰ KIỆN" in prompt
    assert "TÓM TẮT" in prompt and "KIẾN NGHỊ" in prompt


def test_events_are_listed_with_uniform_and_card_status(builder):
    events = [make_event(1, "Uniform", "Card"), make_event(2, "Non_Uniform", "No_Card")]
    prompt = builder.build(events)
    assert prompt.count("Sự kiện") >= 2
    assert "Trạng thái Đồng phục: Non_Uniform" in prompt
    assert "Trạng thái Thẻ sinh viên: No_Card" in prompt
    assert "Mã theo dõi: 2" in prompt


def test_multiple_mixed_events_generation(builder):
    # Kế thừa kịch bản nhiều sự kiện kết hợp từ nhánh thuy
    events = [
        make_event(1, "Uniform", "Card"),
        make_event(2, "Non_Uniform", "Card"),
        make_event(3, "Non_Uniform", "No_Card"),
        make_event(4, "Uniform", "Card"),
    ]
    prompt = builder.build(events)
    assert prompt.count("Sự kiện") == 4
```

- **Lý do (Strategy):**
  - Toàn bộ thư mục `Final_System/test` hiện tại được cấu hình chạy thông qua `pytest` (nhánh Qui đã thêm `pytest.ini`). Để lệnh `print` chạy tự do sẽ làm bẩn log test và không thể tự động xác nhận tính đúng đắn trong CI/CD.
  - Việc chuyển các bộ sự kiện của Thuy thành test case `test_multiple_mixed_events_generation` vừa giữ trọn vẹn mục đích test nhiều case của Thuy, vừa đảm bảo tính chuẩn hoá.
- **Ảnh hưởng:** 
  - Chạy `py -3.12 -m pytest Final_System/test/reporting/test_prompt_builder.py` sẽ pass 100%, không bị lỗi syntax hay test runner bị dừng.

---

## Kế hoạch kiểm tra xác minh (Verification Plan)

Sau khi được duyệt và giải quyết conflict:

### 1. Automated Tests
- Chạy kiểm tra cú pháp và unit test của file test vừa sửa:
  ```powershell
  py -3.12 -m pytest Final_System/test/reporting/test_prompt_builder.py
  ```
- Chạy smoke test toàn bộ unit tests:
  ```powershell
  py -3.12 -m pytest Final_System/test/unit/
  ```

### 2. Manual Verification
- Kiểm tra trạng thái git:
  ```powershell
  git status
  ```
  Xác nhận không còn file nào trong danh sách `Unmerged paths`.
- Kiểm tra import và khởi động của FastAPI app (không bị lỗi syntax, CORS và router hoạt động đầy đủ).
