# Graph Report - Campus-Integrity-Monitor  (2026-10-21)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1654 nodes · 3473 edges · 153 communities (57 shown, 96 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 259 edges (avg confidence: 0.91)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `825067d9`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Database
- cli.py
- composer.json
- CampusMonitorUI
- routers/auth.py
- test_voting_and_events.py
- test_violation_sender.py
- web.php
- App
- Event
- StudentService
- routers/violations.py
- exporters.py
- test_api_integration.py
- security.py
- factories.py
- User
- public/assets/js/students.js
- student_module.php
- EventLogger
- Illuminate\Http\Request
- ui/assets/js/students.js
- dependencies.py
- StudentImportExportTest
- package.json
- PromptBuilder
- StudentService.php
- errors.py
- Illuminate\Database\Schema\Blueprint
- AuthController
- student_violation_add
- app.py
- routes.py
- dataclasses
- StudentTestPDO
- includes/auth.php
- SqliteOutboxRepository
- catalog_module.php
- student_module_test.php
- routers/reports.py
- Prediction
- evidence.py
- settings.py
- public/assets/js/common.js
- ui/assets/js/common.js
- EvidenceStorage
- InferenceService
- CatalogService
- students.php
- report_stats.py
- database.py
- SyncWorker
- public/assets/js/violations.js
- public/assets/js/catalogs.js
- ui/assets/js/catalogs.js
- CardDetection
- public/assets/js/facilities.js
- ui/assets/js/facilities.js
- service.py
- retention.py
- public/assets/js/student_violations.js
- ui/assets/js/student_violations.js
- ui/assets/js/violations.js
- test_prompt_builder.py
- bootstrap/app.php
- Closure
- cim_env
- RetentionScheduler
- TestFullEventFlow
- AppServiceProvider
- public/assets/js/charts.js
- init
- ui/assets/js/charts.js
- init
- admin
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
1. `Database` - 98 edges
2. `StudentService` - 36 edges
3. `Event` - 34 edges
4. `CampusMonitorUI` - 32 edges
5. `ApiError` - 26 edges
6. `CurrentUser` - 25 edges
7. `validation_error()` - 25 edges
8. `EvidenceStorage` - 24 edges
9. `TemporalVoting` - 24 edges
10. `App` - 22 edges

## Surprising Connections (you probably didn't know these)
- `create_admin()` --uses--> `Database`  [INFERRED]
  Final_System/api/cli.py → Final_System/api/database.py
- `Database` --uses--> `DatabaseSettings`  [INFERRED]
  Final_System/api/database.py → Final_System/api/settings.py
- `get_current_user()` --uses--> `Database`  [INFERRED]
  Final_System/api/dependencies.py → Final_System/api/database.py
- `get_db()` --uses--> `Database`  [INFERRED]
  Final_System/api/dependencies.py → Final_System/api/database.py
- `get_device_camera()` --uses--> `Database`  [INFERRED]
  Final_System/api/dependencies.py → Final_System/api/database.py

## Import Cycles
- None detected.

## Communities (153 total, 96 thin omitted)

### Community 0 - "Database"
Cohesion: 0.05
Nodes (72): Database, is_foreign_key_violation(), is_unique_violation(), row_to_dict(), rows_to_dicts(), conflict(), not_found(), validation_error() (+64 more)

### Community 1 - "cli.py"
Cohesion: 0.05
Nodes (23): LLMService, get_password_hash(), check_config(), create_admin(), _line(), main(), _read_password(), main() (+15 more)

### Community 2 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 4 - "routers/auth.py"
Cohesion: 0.12
Nodes (26): CurrentUser, change_password(), create_account(), list_accounts(), login(), me(), reset_password(), update_account() (+18 more)

### Community 5 - "test_voting_and_events.py"
Cohesion: 0.10
Nodes (17): PipelineResult, TemporalVoting, combined_status(), crop_evidence(), ViolationEventDetector, default_config(), feed(), _frame() (+9 more)

### Community 6 - "test_violation_sender.py"
Cohesion: 0.10
Nodes (14): ViolationEvent, ApiConfig, SenderStatus, ViolationSender, FakeResponse, FakeSession, make_event(), sender() (+6 more)

### Community 7 - "web.php"
Cohesion: 0.08
Nodes (7): AccountController, Controller, DashboardController, FacilityController, ReportController, StudentController, ViolationController

### Community 9 - "Event"
Cohesion: 0.14
Nodes (5): EventRepository, JsonRepository, JsonEventMapper, Event, test_append_and_read_back()

### Community 11 - "routers/violations.py"
Cohesion: 0.12
Nodes (14): mark_evidence_missing(), broadcast_violation(), device_me(), _encode(), get_evidence(), get_storage(), get_violation(), ingest_violation() (+6 more)

### Community 12 - "exporters.py"
Cohesion: 0.12
Nodes (10): StatsPromptBuilder, build_pdf(), _build_pdf_fpdf2(), _build_pdf_matplotlib(), build_xlsx(), write_table(), _excel_safe(), _fmt_detail_time() (+2 more)

### Community 13 - "test_api_integration.py"
Cohesion: 0.10
Nodes (15): jpeg(), local_now(), send(), test_dashboard_and_reports(), test_database_unavailable_returns_503(), test_inactive_camera_and_inactive_violation_type_are_rejected(), test_ingest_creates_record_without_identity(), test_ingest_is_idempotent_on_event_id() (+7 more)

### Community 14 - "security.py"
Cohesion: 0.15
Nodes (17): _b64decode(), _b64encode(), create_token(), hash_device_key(), hash_password(), _require_secret(), TokenError, TokenPayload (+9 more)

### Community 15 - "factories.py"
Cohesion: 0.14
Nodes (9): Classifier, Detector, Pipeline, create_mapper(), create_service(), pipeline_factory(), create_voting(), pipeline() (+1 more)

### Community 16 - "User"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 17 - "public/assets/js/students.js"
Cohesion: 0.19
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 18 - "student_module.php"
Cohesion: 0.18
Nodes (24): student_bind_like(), student_catalog(), student_code(), student_database_failure(), student_decode_json_body(), student_delete(), student_find_class(), student_import_columns() (+16 more)

### Community 19 - "EventLogger"
Cohesion: 0.13
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 21 - "ui/assets/js/students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 22 - "dependencies.py"
Cohesion: 0.13
Nodes (14): AuthenticatedCamera, get_current_user(), get_db(), get_device_camera(), get_mapper(), get_service(), get_settings_dep(), require_roles() (+6 more)

### Community 23 - "StudentImportExportTest"
Cohesion: 0.13
Nodes (4): ExampleTest, StudentImportExportTest, WebPortalTest, TestCase

### Community 24 - "package.json"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 25 - "PromptBuilder"
Cohesion: 0.12
Nodes (3): GeminiService, PromptBuilder, test_gemini_generates_report()

### Community 27 - "errors.py"
Cohesion: 0.14
Nodes (11): ApiError, bad_request(), error_body(), install_error_handlers(), _api_error(), _database_unavailable(), _http_error(), _unhandled() (+3 more)

### Community 28 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 30 - "student_violation_add"
Cohesion: 0.18
Nodes (18): {closure#1}(), {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+10 more)

### Community 31 - "app.py"
Cohesion: 0.18
Nodes (3): Camera, _api_message(), check_connection()

### Community 32 - "routes.py"
Cohesion: 0.22
Nodes (5): Mapper, BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 35 - "includes/auth.php"
Cohesion: 0.18
Nodes (13): cim_api_error_message(), cim_api_json(), cim_api_request(), cim_clear_auth(), cim_current_user(), cim_idle_seconds(), cim_login(), cim_logout() (+5 more)

### Community 37 - "catalog_module.php"
Cohesion: 0.18
Nodes (10): {closure#1}(), {closure#2}(), catalog_code(), catalog_data(), catalog_delete(), catalog_flag(), catalog_int(), catalog_record() (+2 more)

### Community 38 - "student_module_test.php"
Cohesion: 0.17
Nodes (10): check(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), expectWorkbookError(), student_excel_xml(), student_parse_xlsx() (+2 more)

### Community 39 - "routers/reports.py"
Cohesion: 0.19
Nodes (5): lifespan(), _generate_ai_analysis(), _get_prompt_builder(), report_ai_pdf(), AiPdfReportRequest

### Community 41 - "evidence.py"
Cohesion: 0.27
Nodes (8): EvidenceError, UnsafeEvidencePath, validate_and_normalize_image(), jpeg_bytes(), test_invalid_images_rejected(), test_size_and_dimension_limits(), test_unsafe_keys_never_resolve(), test_valid_image_is_reencoded_as_jpeg()

### Community 42 - "settings.py"
Cohesion: 0.21
Nodes (6): _env(), _env_bool(), _env_int(), get_settings(), _resolve_dir(), Settings

### Community 43 - "public/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 44 - "ui/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 45 - "EvidenceStorage"
Cohesion: 0.20
Nodes (3): EvidenceStorage, StoredEvidence, test_storage_save_resolve_delete()

### Community 46 - "InferenceService"
Cohesion: 0.15
Nodes (3): predict(), _CameraState, InferenceService

### Community 48 - "students.php"
Cohesion: 0.21
Nodes (11): temporaryWorkbook(), student_detect_xlsx_upload(), student_download_template(), student_export(), student_filter_input(), student_import_upload(), student_request_failure(), student_create_xlsx() (+3 more)

### Community 49 - "report_stats.py"
Cohesion: 0.26
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 50 - "database.py"
Cohesion: 0.19
Nodes (4): build_connection_string(), DatabaseConfigError, _odbc_value(), DatabaseSettings

### Community 52 - "public/assets/js/violations.js"
Cohesion: 0.30
Nodes (10): badge(), connectSSE(), field(), load(), openDetail(), params(), renderEvidence(), renderRow() (+2 more)

### Community 53 - "public/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 54 - "ui/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 57 - "public/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 58 - "ui/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 60 - "retention.py"
Cohesion: 0.44
Nodes (5): _log(), main(), purge_expired_evidence(), _purge_one(), PurgeReport

### Community 61 - "public/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 62 - "ui/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 63 - "ui/assets/js/violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 64 - "test_prompt_builder.py"
Cohesion: 0.32
Nodes (4): builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

### Community 65 - "bootstrap/app.php"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 67 - "cim_env"
Cohesion: 0.43
Nodes (5): student_today(), cim_env(), cim_env_bool(), cim_load_env(), student_db()

### Community 72 - "public/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 74 - "ui/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 76 - "admin"
Cohesion: 0.50
Nodes (3): admin(), ctsv(), setup_camera()

### Community 79 - "public/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

### Community 81 - "ui/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **60 isolated node(s):** `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader`, `preferred-install`, `sort-packages` (+55 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 525 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **96 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Database` to `cli.py`, `RetentionScheduler`, `routers/auth.py`, `routers/reports.py`, `routers/violations.py`, `test_api_integration.py`, `database.py`, `dependencies.py`, `errors.py`, `retention.py`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `App`, `app.py`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Why does `SyncWorker` connect `SyncWorker` to `SqliteOutboxRepository`, `pathlib`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Are the 66 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 66 INFERRED edges - model-reasoned connections that need verification._
- **Are the 7 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 7 INFERRED edges - model-reasoned connections that need verification._
- **What connects `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader` to the rest of the system?**
  _60 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Database` be split into smaller, more focused modules?**
  _Cohesion score 0.05142941349837902 - nodes in this community are weakly interconnected._