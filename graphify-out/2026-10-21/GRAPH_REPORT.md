# Graph Report - Campus-Integrity-Monitor  (2026-10-19)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1623 nodes · 3438 edges · 159 communities (58 shown, 101 thin omitted)
- Extraction: 92% EXTRACTED · 8% INFERRED · 0% AMBIGUOUS · INFERRED: 289 edges (avg confidence: 0.91)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `825067d9`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- routers/reports.py
- StudentService
- composer.json
- Database
- test_violation_sender.py
- factories.py
- dependencies.py
- Event
- accounts.py
- test_voting_and_events.py
- EventLogger
- routers/violations.py
- test_api_integration.py
- web.php
- User
- public/assets/js/students.js
- student_module.php
- api/main.py
- repositories/facilities.py
- StudentService.php
- ui/assets/js/students.js
- StudentImportExportTest
- package.json
- repositories/violations.py
- App
- CampusMonitorUI
- Illuminate\Database\Schema\Blueprint
- Illuminate\Http\Request
- student_violation_add
- database.py
- dataclasses
- includes/auth.php
- SqliteOutboxRepository
- app.py
- catalog_module.php
- report_stats.py
- sync_worker.py
- cli.py
- Illuminate\View\View
- student_module_test.php
- evidence.py
- public/assets/js/common.js
- ui/assets/js/common.js
- system.py
- Utils
- ViolationEventDetector
- EvidenceStorage
- CatalogService
- students.php
- PromptBuilder
- api/auth.py
- settings.py
- migrate.py
- CimApiClient
- public/assets/js/catalogs.js
- ui/assets/js/catalogs.js
- CardDetection
- conftest.py
- public/assets/js/facilities.js
- ui/assets/js/facilities.js
- .transaction
- retention.py
- public/assets/js/student_violations.js
- public/assets/js/violations.js
- ui/assets/js/student_violations.js
- ui/assets/js/violations.js
- test_prompt_builder.py
- bootstrap/app.php
- Closure
- gemini_service.py
- cim_env
- RetentionScheduler
- Camera
- TestFullEventFlow
- AppServiceProvider
- public/assets/js/charts.js
- init
- ui/assets/js/charts.js
- init
- predict
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
1. `Database` - 106 edges
2. `StudentService` - 36 edges
3. `Event` - 34 edges
4. `CampusMonitorUI` - 32 edges
5. `CurrentUser` - 30 edges
6. `EvidenceStorage` - 28 edges
7. `ApiError` - 26 edges
8. `validation_error()` - 25 edges
9. `TemporalVoting` - 24 edges
10. `Settings` - 22 edges

## Surprising Connections (you probably didn't know these)
- `get_current_user()` --uses--> `Settings`  [INFERRED]
  Final_System/api/dependencies.py → Final_System/api/settings.py
- `authenticate()` --uses--> `Settings`  [INFERRED]
  Final_System/api/repositories/accounts.py → Final_System/api/settings.py
- `login()` --uses--> `Settings`  [INFERRED]
  Final_System/api/routers/auth.py → Final_System/api/settings.py
- `App` --uses--> `CampusMonitorUI`  [INFERRED]
  Final_System/desktop/app.py → Final_System/desktop/ui.py
- `Database` --uses--> `DatabaseSettings`  [INFERRED]
  Final_System/api/database.py → Final_System/api/settings.py

## Import Cycles
- None detected.

## Communities (159 total, 101 thin omitted)

### Community 0 - "routers/reports.py"
Cohesion: 0.07
Nodes (26): GeminiService, StatsPromptBuilder, build_pdf(), _build_pdf_fpdf2(), _build_pdf_matplotlib(), build_xlsx(), write_table(), _excel_safe() (+18 more)

### Community 1 - "StudentService"
Cohesion: 0.08
Nodes (3): StudentService, StudentTestPDO, StudentTestStatement

### Community 2 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 3 - "Database"
Cohesion: 0.12
Nodes (31): Database, CurrentUser, change_password(), create_account(), list_accounts(), login(), me(), reset_password() (+23 more)

### Community 4 - "test_violation_sender.py"
Cohesion: 0.08
Nodes (20): error_body(), install_error_handlers(), _api_error(), _database_unavailable(), _http_error(), _unhandled(), _validation(), ApiConfig (+12 more)

### Community 5 - "factories.py"
Cohesion: 0.10
Nodes (10): Classifier, Detector, Pipeline, create_service(), pipeline_factory(), create_voting(), _CameraState, InferenceService (+2 more)

### Community 6 - "dependencies.py"
Cohesion: 0.12
Nodes (24): get_current_user(), get_device_camera(), require_roles(), _dependency(), forbidden(), unauthorized(), rotate_device_key(), _b64decode() (+16 more)

### Community 7 - "Event"
Cohesion: 0.13
Nodes (6): EventRepository, JsonRepository, JsonEventMapper, Event, test_gemini_generates_report(), test_append_and_read_back()

### Community 8 - "accounts.py"
Cohesion: 0.13
Nodes (21): is_unique_violation(), ApiError, bad_request(), conflict(), validation_error(), account_to_dict(), authenticate(), change_own_password() (+13 more)

### Community 9 - "test_voting_and_events.py"
Cohesion: 0.11
Nodes (16): Prediction, PipelineResult, _decide(), TemporalVoting, VotingResult, default_config(), feed(), _frame() (+8 more)

### Community 10 - "EventLogger"
Cohesion: 0.12
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 11 - "routers/violations.py"
Cohesion: 0.13
Nodes (16): AuthenticatedCamera, get_settings_dep(), touch_camera(), device_me(), _encode(), get_evidence(), get_storage(), get_violation() (+8 more)

### Community 12 - "test_api_integration.py"
Cohesion: 0.11
Nodes (14): jpeg(), local_now(), send(), test_dashboard_and_reports(), test_database_unavailable_returns_503(), test_inactive_camera_and_inactive_violation_type_are_rejected(), test_ingest_creates_record_without_identity(), test_ingest_is_idempotent_on_event_id() (+6 more)

### Community 13 - "web.php"
Cohesion: 0.08
Nodes (5): AccountController, Controller, DashboardController, FacilityController, ReportController

### Community 14 - "User"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 15 - "public/assets/js/students.js"
Cohesion: 0.19
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 16 - "student_module.php"
Cohesion: 0.18
Nodes (24): student_bind_like(), student_catalog(), student_code(), student_database_failure(), student_decode_json_body(), student_delete(), student_find_class(), student_import_columns() (+16 more)

### Community 17 - "api/main.py"
Cohesion: 0.15
Nodes (7): create_mapper(), lifespan(), Mapper, BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 18 - "repositories/facilities.py"
Cohesion: 0.20
Nodes (19): is_foreign_key_violation(), not_found(), area_to_dict(), camera_to_dict(), _code(), current_semester(), delete_semester(), get_area() (+11 more)

### Community 20 - "ui/assets/js/students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 21 - "StudentImportExportTest"
Cohesion: 0.13
Nodes (4): ExampleTest, StudentImportExportTest, WebPortalTest, TestCase

### Community 22 - "package.json"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 23 - "repositories/violations.py"
Cohesion: 0.24
Nodes (14): duplicate_result(), evidence_info(), evidence_key_for(), _find_by_event(), find_existing_event(), get_violation(), IncomingViolation, ingest_violation() (+6 more)

### Community 26 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 28 - "student_violation_add"
Cohesion: 0.18
Nodes (18): {closure#1}(), {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+10 more)

### Community 29 - "database.py"
Cohesion: 0.14
Nodes (5): build_connection_string(), DatabaseConfigError, DatabaseUnavailable, _odbc_value(), DatabaseSettings

### Community 31 - "includes/auth.php"
Cohesion: 0.18
Nodes (13): cim_api_error_message(), cim_api_json(), cim_api_request(), cim_clear_auth(), cim_current_user(), cim_idle_seconds(), cim_login(), cim_logout() (+5 more)

### Community 34 - "catalog_module.php"
Cohesion: 0.18
Nodes (10): {closure#1}(), {closure#2}(), catalog_code(), catalog_data(), catalog_delete(), catalog_flag(), catalog_int(), catalog_record() (+2 more)

### Community 35 - "report_stats.py"
Cohesion: 0.20
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 37 - "cli.py"
Cohesion: 0.18
Nodes (8): check_config(), create_admin(), _line(), main(), _read_password(), test_check_config_hides_secrets(), test_create_admin_only_once_without_flag(), test_main_reports_errors_without_traceback()

### Community 39 - "student_module_test.php"
Cohesion: 0.17
Nodes (10): check(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), expectWorkbookError(), student_excel_xml(), student_parse_xlsx() (+2 more)

### Community 40 - "evidence.py"
Cohesion: 0.27
Nodes (8): EvidenceError, UnsafeEvidencePath, validate_and_normalize_image(), jpeg_bytes(), test_invalid_images_rejected(), test_size_and_dimension_limits(), test_unsafe_keys_never_resolve(), test_valid_image_is_reencoded_as_jpeg()

### Community 41 - "public/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 42 - "ui/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 43 - "system.py"
Cohesion: 0.18
Nodes (6): get_db(), get_mapper(), get_service(), _jsonable(), liveness(), readiness()

### Community 45 - "ViolationEventDetector"
Cohesion: 0.18
Nodes (5): combined_status(), crop_evidence(), ViolationEvent, ViolationEventDetector, test_combined_status_four_states()

### Community 46 - "EvidenceStorage"
Cohesion: 0.20
Nodes (3): EvidenceStorage, StoredEvidence, test_storage_save_resolve_delete()

### Community 48 - "students.php"
Cohesion: 0.21
Nodes (11): temporaryWorkbook(), student_detect_xlsx_upload(), student_download_template(), student_export(), student_filter_input(), student_import_upload(), student_request_failure(), student_create_xlsx() (+3 more)

### Community 52 - "settings.py"
Cohesion: 0.26
Nodes (5): _env(), _env_bool(), _env_int(), get_settings(), _resolve_dir()

### Community 53 - "migrate.py"
Cohesion: 0.40
Nodes (8): applied_versions(), apply(), existing_tables(), main(), migration_files(), run_script(), split_batches(), status()

### Community 55 - "public/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 56 - "ui/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 59 - "conftest.py"
Cohesion: 0.24
Nodes (3): client(), database(), evidence_dir()

### Community 60 - "public/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 61 - "ui/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 63 - "retention.py"
Cohesion: 0.44
Nodes (5): _log(), main(), purge_expired_evidence(), _purge_one(), PurgeReport

### Community 65 - "public/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 66 - "public/assets/js/violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 67 - "ui/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 68 - "ui/assets/js/violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 69 - "test_prompt_builder.py"
Cohesion: 0.32
Nodes (4): builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

### Community 70 - "bootstrap/app.php"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 73 - "cim_env"
Cohesion: 0.43
Nodes (5): student_today(), cim_env(), cim_env_bool(), cim_load_env(), student_db()

### Community 80 - "public/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 82 - "ui/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 85 - "admin"
Cohesion: 0.50
Nodes (3): admin(), ctsv(), setup_camera()

### Community 88 - "public/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

### Community 90 - "ui/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **60 isolated node(s):** `layouts.app`, `layouts.app`, `layouts.app`, `layouts.app`, `pestphp/pest-plugin` (+55 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 509 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **101 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Database` to `routers/reports.py`, `cli.py`, `dependencies.py`, `accounts.py`, `RetentionScheduler`, `system.py`, `routers/violations.py`, `test_api_integration.py`, `api/main.py`, `repositories/facilities.py`, `migrate.py`, `repositories/violations.py`, `conftest.py`, `database.py`, `.transaction`, `retention.py`?**
  _High betweenness centrality (0.049) - this node is a cross-community bridge._
- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `.create_left_panel`, `app.py`, `.run_in_background`, `App`, `.create_widgets`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `SqliteOutboxRepository` connect `SqliteOutboxRepository` to `sync_worker.py`, `Event`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Are the 74 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 74 INFERRED edges - model-reasoned connections that need verification._
- **Are the 7 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 7 INFERRED edges - model-reasoned connections that need verification._
- **What connects `layouts.app`, `layouts.app`, `layouts.app` to the rest of the system?**
  _60 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `routers/reports.py` be split into smaller, more focused modules?**
  _Cohesion score 0.06605222734254992 - nodes in this community are weakly interconnected._