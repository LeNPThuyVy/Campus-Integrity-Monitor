# Graph Report - Campus-Integrity-Monitor  (2026-10-23)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1752 nodes · 3645 edges · 169 communities (58 shown, 111 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 222 edges (avg confidence: 0.9)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `d6a747a1`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- repositories/violations.py
- repositories/facilities.py
- composer.json
- test_violation_sender.py
- dependencies.py
- StudentService
- App
- routers/violations.py
- Event
- test_api_integration.py
- web.php
- pipeline.py
- settings.py
- conftest.py
- factories.py
- public/assets/js/students.js
- routers/auth.py
- test_voting_and_events.py
- Database
- User
- student_module.php
- EventLogger
- security.py
- ui/assets/js/students.js
- accounts.py
- StudentImportExportTest
- Illuminate\Http\Request
- package.json
- public/assets/js/violations.js
- AuthController
- PromptBuilder
- errors.py
- Illuminate\Database\Schema\Blueprint
- student_violation_add
- routes.py
- StudentTestPDO
- includes/auth.php
- SqliteOutboxRepository
- CampusMonitorUI
- catalog_module.php
- student_module_test.php
- CatalogController.php
- app.py
- config.py
- EvidenceStorage
- retention.py
- public/assets/js/common.js
- ui/assets/js/common.js
- InferenceService
- CatalogService
- students.php
- report_stats.py
- ModernSlider
- SyncWorker
- CimApiClient
- evidence.py
- public/assets/js/catalogs.js
- ui/assets/js/catalogs.js
- test_evidence.py
- R2EvidenceStorage
- .stop_monitoring
- .__init__
- public/assets/js/facilities.js
- ui/assets/js/facilities.js
- service.py
- public/assets/js/student_violations.js
- ui/assets/js/student_violations.js
- ui/assets/js/violations.js
- test_prompt_builder.py
- bootstrap/app.php
- Closure
- ModernSpinbox
- cim_env
- TestFullEventFlow
- AppServiceProvider
- public/assets/js/charts.js
- init
- ui/assets/js/charts.js
- init
- ExampleTest
- public/assets/js/accounts.js
- public/assets/js/reports.js
- ui/assets/js/accounts.js
- ui/assets/js/reports.js
- dashboard/index.blade.php
- reports/index.blade.php
- accounts/index.blade.php
- change_password.blade.php
- catalogs/index.blade.php
- facilities/index.blade.php
- students/index.blade.php
- violations/index.blade.php

## God Nodes (most connected - your core abstractions)
1. `Database` - 64 edges
2. `StudentService` - 41 edges
3. `CampusMonitorUI` - 37 edges
4. `Event` - 34 edges
5. `ApiError` - 26 edges
6. `App` - 26 edges
7. `TemporalVoting` - 25 edges
8. `validation_error()` - 25 edges
9. `CimProxyController` - 23 edges
10. `EvidenceStorage` - 23 edges

## Surprising Connections (you probably didn't know these)
- `_CameraState` --uses--> `Pipeline`  [INFERRED]
  Final_System/api/service.py → Final_System/ai/pipeline.py
- `InferenceService` --uses--> `Pipeline`  [INFERRED]
  Final_System/api/service.py → Final_System/ai/pipeline.py
- `App` --uses--> `Pipeline`  [INFERRED]
  Final_System/desktop/app.py → Final_System/ai/pipeline.py
- `report_ai_pdf()` --uses--> `AiPdfReportRequest`  [INFERRED]
  Final_System/api/routers/reports.py → Final_System/api/v1_schemas.py
- `_CameraState` --uses--> `TemporalVoting`  [INFERRED]
  Final_System/api/service.py → Final_System/ai/temporal_voting.py

## Import Cycles
- None detected.

## Communities (169 total, 111 thin omitted)

### Community 0 - "repositories/violations.py"
Cohesion: 0.05
Nodes (42): StatsPromptBuilder, build_pdf(), _build_pdf_fpdf2(), _build_pdf_matplotlib(), build_xlsx(), write_table(), _excel_safe(), _fmt_detail_time() (+34 more)

### Community 1 - "repositories/facilities.py"
Cohesion: 0.09
Nodes (38): is_foreign_key_violation(), is_unique_violation(), conflict(), not_found(), area_to_dict(), camera_to_dict(), _code(), current_semester() (+30 more)

### Community 2 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 3 - "test_violation_sender.py"
Cohesion: 0.09
Nodes (16): ViolationEvent, _api_message(), ApiConfig, check_connection(), SenderStatus, ViolationSender, FakeResponse, FakeSession (+8 more)

### Community 4 - "dependencies.py"
Cohesion: 0.09
Nodes (15): AuthenticatedCamera, get_current_user(), get_db(), get_device_camera(), get_mapper(), get_service(), get_settings_dep(), require_roles() (+7 more)

### Community 7 - "routers/violations.py"
Cohesion: 0.11
Nodes (16): touch_camera(), broadcast_violation(), device_me(), _encode(), get_evidence(), get_storage(), get_violation(), ingest_violation() (+8 more)

### Community 8 - "Event"
Cohesion: 0.14
Nodes (5): EventRepository, JsonRepository, JsonEventMapper, Event, test_append_and_read_back()

### Community 9 - "test_api_integration.py"
Cohesion: 0.09
Nodes (18): admin(), ctsv(), jpeg(), local_now(), send(), setup_camera(), test_dashboard_and_reports(), test_database_unavailable_returns_503() (+10 more)

### Community 10 - "web.php"
Cohesion: 0.09
Nodes (6): AccountController, Controller, DashboardController, FacilityController, ReportController, ViolationController

### Community 11 - "pipeline.py"
Cohesion: 0.09
Nodes (3): CardDetection, TrackResult, Utils

### Community 12 - "settings.py"
Cohesion: 0.11
Nodes (13): check_config(), create_admin(), _line(), main(), _read_password(), _env(), _env_bool(), _env_int() (+5 more)

### Community 13 - "conftest.py"
Cohesion: 0.08
Nodes (6): LLMService, get_password_hash(), main(), client(), database(), evidence_dir()

### Community 14 - "factories.py"
Cohesion: 0.12
Nodes (10): CardDetector, Classifier, Detector, Pipeline, create_mapper(), create_service(), pipeline_factory(), create_voting() (+2 more)

### Community 15 - "public/assets/js/students.js"
Cohesion: 0.17
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 16 - "routers/auth.py"
Cohesion: 0.15
Nodes (19): CurrentUser, change_password(), create_account(), list_accounts(), login(), me(), reset_password(), update_account() (+11 more)

### Community 17 - "test_voting_and_events.py"
Cohesion: 0.14
Nodes (14): Prediction, PipelineResult, TemporalVoting, default_config(), feed(), _frame(), result(), test_crop_evidence_validation() (+6 more)

### Community 18 - "Database"
Cohesion: 0.16
Nodes (12): Database, row_to_dict(), rows_to_dicts(), applied_versions(), apply(), existing_tables(), main(), migration_files() (+4 more)

### Community 19 - "User"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 20 - "student_module.php"
Cohesion: 0.18
Nodes (24): student_bind_like(), student_catalog(), student_code(), student_database_failure(), student_decode_json_body(), student_delete(), student_find_class(), student_import_columns() (+16 more)

### Community 21 - "EventLogger"
Cohesion: 0.13
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 22 - "security.py"
Cohesion: 0.18
Nodes (17): _b64decode(), _b64encode(), create_token(), hash_device_key(), hash_password(), _require_secret(), TokenError, TokenPayload (+9 more)

### Community 23 - "ui/assets/js/students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 24 - "accounts.py"
Cohesion: 0.18
Nodes (17): ApiError, bad_request(), validation_error(), account_to_dict(), authenticate(), change_own_password(), _count_other_active_admins(), create_account() (+9 more)

### Community 25 - "StudentImportExportTest"
Cohesion: 0.13
Nodes (4): ExampleTest, StudentImportExportTest, WebPortalTest, TestCase

### Community 27 - "package.json"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 28 - "public/assets/js/violations.js"
Cohesion: 0.15
Nodes (17): badge(), connectSSE(), field(), handleExcelUpload(), load(), loadRedflagMeta(), openDetail(), openRedflagModal() (+9 more)

### Community 30 - "PromptBuilder"
Cohesion: 0.12
Nodes (3): GeminiService, PromptBuilder, test_gemini_generates_report()

### Community 31 - "errors.py"
Cohesion: 0.14
Nodes (11): build_connection_string(), DatabaseConfigError, DatabaseUnavailable, _odbc_value(), error_body(), install_error_handlers(), _api_error(), _database_unavailable() (+3 more)

### Community 32 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 33 - "student_violation_add"
Cohesion: 0.18
Nodes (18): {closure#1}(), {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+10 more)

### Community 34 - "routes.py"
Cohesion: 0.22
Nodes (5): Mapper, BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 36 - "includes/auth.php"
Cohesion: 0.18
Nodes (13): cim_api_error_message(), cim_api_json(), cim_api_request(), cim_clear_auth(), cim_current_user(), cim_idle_seconds(), cim_login(), cim_logout() (+5 more)

### Community 38 - "CampusMonitorUI"
Cohesion: 0.19
Nodes (3): CampusMonitorUI, _bind_cfg_mousewheel(), _on_cfg_mousewheel()

### Community 39 - "catalog_module.php"
Cohesion: 0.18
Nodes (10): {closure#1}(), {closure#2}(), catalog_code(), catalog_data(), catalog_delete(), catalog_flag(), catalog_int(), catalog_record() (+2 more)

### Community 40 - "student_module_test.php"
Cohesion: 0.17
Nodes (10): check(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), expectWorkbookError(), student_excel_xml(), student_parse_xlsx() (+2 more)

### Community 43 - "config.py"
Cohesion: 0.19
Nodes (4): combined_status(), crop_evidence(), ViolationEventDetector, test_combined_status_four_states()

### Community 44 - "EvidenceStorage"
Cohesion: 0.20
Nodes (3): EvidenceStorage, UnsafeEvidencePath, test_unsafe_keys_never_resolve()

### Community 45 - "retention.py"
Cohesion: 0.22
Nodes (6): _log(), main(), purge_expired_evidence(), _purge_one(), PurgeReport, RetentionScheduler

### Community 46 - "public/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 47 - "ui/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 48 - "InferenceService"
Cohesion: 0.15
Nodes (3): predict(), _CameraState, InferenceService

### Community 50 - "students.php"
Cohesion: 0.21
Nodes (11): temporaryWorkbook(), student_detect_xlsx_upload(), student_download_template(), student_export(), student_filter_input(), student_import_upload(), student_request_failure(), student_create_xlsx() (+3 more)

### Community 51 - "report_stats.py"
Cohesion: 0.26
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 57 - "public/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 58 - "ui/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 60 - "test_evidence.py"
Cohesion: 0.38
Nodes (7): EvidenceError, validate_and_normalize_image(), jpeg_bytes(), test_invalid_images_rejected(), test_size_and_dimension_limits(), test_storage_save_resolve_delete(), test_valid_image_is_reencoded_as_jpeg()

### Community 65 - "public/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 66 - "ui/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 68 - "public/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 69 - "ui/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 70 - "ui/assets/js/violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 71 - "test_prompt_builder.py"
Cohesion: 0.32
Nodes (4): builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

### Community 72 - "bootstrap/app.php"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 75 - "cim_env"
Cohesion: 0.43
Nodes (5): student_today(), cim_env(), cim_env_bool(), cim_load_env(), student_db()

### Community 80 - "public/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 82 - "ui/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 88 - "public/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

### Community 90 - "ui/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **60 isolated node(s):** `layouts.app`, `layouts.app`, `layouts.app`, `pestphp/pest-plugin`, `php-http/discovery` (+55 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 564 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **111 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `App` connect `App` to `CampusMonitorUI`, `test_voting_and_events.py`, `app.py`, `factories.py`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **Why does `Database` connect `Database` to `repositories/violations.py`, `repositories/facilities.py`, `dependencies.py`, `routers/violations.py`, `test_api_integration.py`, `settings.py`, `retention.py`, `conftest.py`, `routers/auth.py`, `accounts.py`?**
  _High betweenness centrality (0.027) - this node is a cross-community bridge._
- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `.__init__`, `App`, `app.py`, `.make_modern_button`, `.update_frame`, `.stop_monitoring`, `.run_in_background`?**
  _High betweenness centrality (0.015) - this node is a cross-community bridge._
- **Are the 32 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 32 INFERRED edges - model-reasoned connections that need verification._
- **Are the 7 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 7 INFERRED edges - model-reasoned connections that need verification._
- **What connects `layouts.app`, `layouts.app`, `layouts.app` to the rest of the system?**
  _60 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `repositories/violations.py` be split into smaller, more focused modules?**
  _Cohesion score 0.0546218487394958 - nodes in this community are weakly interconnected._