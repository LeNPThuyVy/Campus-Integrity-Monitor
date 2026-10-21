# Graph Report - Campus-Integrity-Monitor  (2026-10-14)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1280 nodes · 2956 edges · 101 communities (49 shown, 52 thin omitted)
- Extraction: 89% EXTRACTED · 11% INFERRED · 0% AMBIGUOUS · INFERRED: 329 edges (avg confidence: 0.93)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `427b9d5e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- event_setup.py
- CampusMonitorUI
- test_voting_and_events.py
- test_violation_sender.py
- factories.py
- repositories/reports.py
- test_api_integration.py
- App
- SqliteOutboxRepository
- EventStore
- repositories/facilities.py
- routers/violations.py
- EvidenceStorage
- security.py
- EventLogger
- PromptBuilder
- student_module.php
- students.js
- event_routes.py
- dependencies.py
- Database
- routes.py
- CurrentUser
- routers/auth.py
- student_violation_add
- app.py
- dataclasses
- accounts.py
- repositories/violations.py
- datetime
- Event
- event_schemas.py
- StudentTestPDO
- errors.py
- auth.php
- cli.py
- catalog_module.php
- student_module_test.php
- students.php
- common.js
- api/main.py
- migrate.py
- report_stats.py
- _build_period_stats
- settings.py
- os
- catalogs.js
- ObjectStore
- CardDetection
- conftest.py
- facilities.js
- service.py
- retention.py
- student_violations.js
- violations.js
- test_prompt_builder.py
- cim_env
- charts.js
- init
- .track
- accounts.js
- reports.js

## God Nodes (most connected - your core abstractions)
1. `Database` - 111 edges
2. `EventStore` - 43 edges
3. `Event` - 37 edges
4. `CurrentUser` - 33 edges
5. `CampusMonitorUI` - 32 edges
6. `App` - 30 edges
7. `EvidenceStorage` - 28 edges
8. `Settings` - 27 edges
9. `ApiError` - 27 edges
10. `TemporalVoting` - 25 edges

## Surprising Connections (you probably didn't know these)
- `get_device()` --uses--> `EventStore`  [INFERRED]
  Final_System/api/event_routes.py → Final_System/api/event_store.py
- `create_report()` --uses--> `ReportService`  [INFERRED]
  Final_System/api/event_routes.py → Final_System/ai/reporting/report_service.py
- `App` --uses--> `CampusMonitorUI`  [INFERRED]
  Final_System/desktop/app.py → Final_System/desktop/ui.py
- `get_current_user()` --uses--> `Settings`  [INFERRED]
  Final_System/api/dependencies.py → Final_System/api/settings.py
- `get_settings_dep()` --uses--> `Settings`  [INFERRED]
  Final_System/api/dependencies.py → Final_System/api/settings.py

## Import Cycles
- None detected.

## Communities (101 total, 52 thin omitted)

### Community 0 - "event_setup.py"
Cohesion: 0.05
Nodes (9): LLMService, _add_analysis(), _add_table(), build_docx(), ReportService, StatsPromptBuilder, create_db_engine(), setup_event_services() (+1 more)

### Community 2 - "test_voting_and_events.py"
Cohesion: 0.09
Nodes (17): PipelineResult, TemporalVoting, combined_status(), crop_evidence(), ViolationEventDetector, default_config(), feed(), _frame() (+9 more)

### Community 3 - "test_violation_sender.py"
Cohesion: 0.10
Nodes (14): ViolationEvent, ApiConfig, SenderStatus, ViolationSender, FakeResponse, FakeSession, make_event(), sender() (+6 more)

### Community 4 - "factories.py"
Cohesion: 0.10
Nodes (10): Classifier, Detector, Pipeline, create_service(), pipeline_factory(), create_voting(), _CameraState, InferenceService (+2 more)

### Community 5 - "repositories/reports.py"
Cohesion: 0.13
Nodes (18): build_pdf(), build_xlsx(), write_table(), _excel_safe(), _find_fonts(), count_in_range(), dashboard(), detail_rows() (+10 more)

### Community 6 - "test_api_integration.py"
Cohesion: 0.09
Nodes (18): admin(), ctsv(), jpeg(), local_now(), send(), setup_camera(), test_dashboard_and_reports(), test_database_unavailable_returns_503() (+10 more)

### Community 9 - "EventStore"
Cohesion: 0.08
Nodes (3): EventStore, hash_api_key(), TestFullEventFlow

### Community 10 - "repositories/facilities.py"
Cohesion: 0.17
Nodes (22): is_foreign_key_violation(), not_found(), area_to_dict(), camera_to_dict(), _code(), current_semester(), delete_semester(), get_area() (+14 more)

### Community 11 - "routers/violations.py"
Cohesion: 0.13
Nodes (17): AuthenticatedCamera, validation_error(), touch_camera(), mark_evidence_missing(), device_me(), _encode(), get_evidence(), get_storage() (+9 more)

### Community 12 - "EvidenceStorage"
Cohesion: 0.14
Nodes (11): EvidenceError, EvidenceStorage, StoredEvidence, UnsafeEvidencePath, validate_and_normalize_image(), jpeg_bytes(), test_invalid_images_rejected(), test_size_and_dimension_limits() (+3 more)

### Community 13 - "security.py"
Cohesion: 0.16
Nodes (18): _b64decode(), _b64encode(), create_token(), generate_device_key(), hash_device_key(), hash_password(), _require_secret(), TokenError (+10 more)

### Community 14 - "EventLogger"
Cohesion: 0.13
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 15 - "PromptBuilder"
Cohesion: 0.10
Nodes (3): GeminiService, PromptBuilder, test_gemini_generates_report()

### Community 16 - "student_module.php"
Cohesion: 0.19
Nodes (21): student_bind_like(), student_catalog(), student_code(), student_database_failure(), student_decode_json_body(), student_delete(), student_find_class(), student_import_commit() (+13 more)

### Community 17 - "students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 18 - "event_routes.py"
Cohesion: 0.15
Nodes (14): create_access_token(), decode_access_token(), verify_password(), _build_image_key(), cleanup_retention(), confirm_image(), get_device(), get_event_store() (+6 more)

### Community 19 - "dependencies.py"
Cohesion: 0.13
Nodes (13): get_current_user(), get_db(), get_device_camera(), get_mapper(), get_service(), get_settings_dep(), require_roles(), _dependency() (+5 more)

### Community 20 - "Database"
Cohesion: 0.15
Nodes (7): build_connection_string(), Database, DatabaseConfigError, _odbc_value(), row_to_dict(), rows_to_dicts(), DatabaseSettings

### Community 21 - "routes.py"
Cohesion: 0.17
Nodes (6): Mapper, predict(), BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 22 - "CurrentUser"
Cohesion: 0.19
Nodes (15): CurrentUser, create_area(), create_camera(), create_semester(), delete_semester(), list_areas(), list_cameras(), list_semesters() (+7 more)

### Community 23 - "routers/auth.py"
Cohesion: 0.18
Nodes (14): change_password(), create_account(), list_accounts(), login(), me(), reset_password(), update_account(), AccountCreateRequest (+6 more)

### Community 24 - "student_violation_add"
Cohesion: 0.16
Nodes (20): {closure#1}(), {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+12 more)

### Community 25 - "app.py"
Cohesion: 0.16
Nodes (3): Camera, _api_message(), check_connection()

### Community 27 - "accounts.py"
Cohesion: 0.19
Nodes (15): is_unique_violation(), account_to_dict(), authenticate(), change_own_password(), _count_other_active_admins(), create_account(), get_account(), list_accounts() (+7 more)

### Community 28 - "repositories/violations.py"
Cohesion: 0.26
Nodes (13): duplicate_result(), evidence_info(), evidence_key_for(), _find_by_event(), find_existing_event(), get_violation(), IncomingViolation, ingest_violation() (+5 more)

### Community 30 - "Event"
Cohesion: 0.17
Nodes (4): JsonRepository, JsonEventMapper, Event, test_append_and_read_back()

### Community 31 - "event_schemas.py"
Cohesion: 0.18
Nodes (14): register_device(), review_event(), DeviceRegisterRequest, DeviceRegisterResponse, EventBatchRequest, EventBatchResponse, EventIn, EventOut (+6 more)

### Community 33 - "errors.py"
Cohesion: 0.18
Nodes (10): ApiError, bad_request(), conflict(), error_body(), install_error_handlers(), _api_error(), _database_unavailable(), _http_error() (+2 more)

### Community 34 - "auth.php"
Cohesion: 0.18
Nodes (13): cim_api_error_message(), cim_api_json(), cim_api_request(), cim_clear_auth(), cim_current_user(), cim_idle_seconds(), cim_login(), cim_logout() (+5 more)

### Community 35 - "cli.py"
Cohesion: 0.17
Nodes (8): check_config(), create_admin(), _line(), main(), _read_password(), test_check_config_hides_secrets(), test_create_admin_only_once_without_flag(), test_main_reports_errors_without_traceback()

### Community 36 - "catalog_module.php"
Cohesion: 0.18
Nodes (10): {closure#1}(), {closure#2}(), catalog_code(), catalog_data(), catalog_delete(), catalog_flag(), catalog_int(), catalog_record() (+2 more)

### Community 37 - "student_module_test.php"
Cohesion: 0.17
Nodes (10): check(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), expectWorkbookError(), student_excel_xml(), student_parse_xlsx() (+2 more)

### Community 38 - "students.php"
Cohesion: 0.19
Nodes (12): temporaryWorkbook(), student_detect_xlsx_upload(), student_download_template(), student_export(), student_filter_input(), student_import_upload(), student_request_failure(), student_create_xlsx() (+4 more)

### Community 39 - "common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 40 - "api/main.py"
Cohesion: 0.18
Nodes (3): create_mapper(), lifespan(), RetentionScheduler

### Community 41 - "migrate.py"
Cohesion: 0.29
Nodes (9): DatabaseUnavailable, applied_versions(), apply(), existing_tables(), main(), migration_files(), run_script(), split_batches() (+1 more)

### Community 42 - "report_stats.py"
Cohesion: 0.26
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 43 - "_build_period_stats"
Cohesion: 0.24
Nodes (9): _build_period_stats(), create_report(), device_health(), export_events(), get_event_detail(), get_image_url(), get_stats(), list_events() (+1 more)

### Community 44 - "settings.py"
Cohesion: 0.26
Nodes (5): _env(), _env_bool(), _env_int(), get_settings(), _resolve_dir()

### Community 46 - "catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 49 - "conftest.py"
Cohesion: 0.24
Nodes (3): client(), database(), evidence_dir()

### Community 50 - "facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 52 - "retention.py"
Cohesion: 0.44
Nodes (5): _log(), main(), purge_expired_evidence(), _purge_one(), PurgeReport

### Community 53 - "student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 54 - "violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 56 - "test_prompt_builder.py"
Cohesion: 0.38
Nodes (4): builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

### Community 57 - "cim_env"
Cohesion: 0.43
Nodes (5): student_today(), cim_env(), cim_env_bool(), cim_load_env(), student_db()

### Community 58 - "charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 61 - "accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **52 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Database` to `cli.py`, `repositories/reports.py`, `test_api_integration.py`, `api/main.py`, `migrate.py`, `repositories/facilities.py`, `routers/violations.py`, `conftest.py`, `dependencies.py`, `retention.py`, `CurrentUser`, `routers/auth.py`, `accounts.py`, `repositories/violations.py`?**
  _High betweenness centrality (0.063) - this node is a cross-community bridge._
- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `app.py`, `App`?**
  _High betweenness centrality (0.058) - this node is a cross-community bridge._
- **Why does `Event` connect `Event` to `event_setup.py`, `SqliteOutboxRepository`, `EventStore`, `report_stats.py`, `EventLogger`, `PromptBuilder`, `test_prompt_builder.py`, `datetime`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Are the 80 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 80 INFERRED edges - model-reasoned connections that need verification._
- **Are the 20 inferred relationships involving `EventStore` (e.g. with `_build_period_stats()` and `cleanup_retention()`) actually correct?**
  _`EventStore` has 20 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 8 INFERRED edges - model-reasoned connections that need verification._
- **Are the 25 inferred relationships involving `CurrentUser` (e.g. with `change_password()` and `create_account()`) actually correct?**
  _`CurrentUser` has 25 INFERRED edges - model-reasoned connections that need verification._