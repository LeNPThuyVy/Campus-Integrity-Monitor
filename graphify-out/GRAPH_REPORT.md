# Graph Report - Campus-Integrity-Monitor  (2026-10-26)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1767 nodes · 3674 edges · 168 communities (59 shown, 109 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 235 edges (avg confidence: 0.9)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `12366403`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- api/main.py
- routers/reports.py
- StudentService
- repositories/facilities.py
- App
- composer.json
- test_violation_sender.py
- Event
- web.php
- test_api_integration.py
- TemporalVoting
- PromptBuilder
- public/assets/js/students.js
- .__init__
- app.py
- Database
- User
- student_module.php
- routers/auth.py
- EventLogger
- routers/violations.py
- accounts.py
- ui/assets/js/students.js
- security.py
- dependencies.py
- repositories/violations.py
- os
- factories.py
- routes.py
- Illuminate\Http\Request
- package.json
- public/assets/js/violations.js
- StudentService.php
- Illuminate\Database\Schema\Blueprint
- student_violation_add
- pipeline.py
- settings.py
- StudentTestPDO
- includes/auth.php
- cli.py
- Settings
- CampusMonitorUI
- catalog_module.php
- SqliteOutboxRepository
- student_module_test.php
- AuthController
- errors.py
- public/assets/js/common.js
- ui/assets/js/common.js
- CatalogService
- students.php
- report_stats.py
- ModernSlider
- Utils
- SyncWorker
- ViolationEventDetector
- migrate.py
- public/assets/js/catalogs.js
- ui/assets/js/catalogs.js
- .stop_monitoring
- .__init__
- public/assets/js/facilities.js
- ui/assets/js/facilities.js
- service.py
- public/assets/js/student_violations.js
- ui/assets/js/student_violations.js
- ui/assets/js/violations.js
- bootstrap/app.php
- Closure
- ModernSpinbox
- CimApiClient
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
- layouts.app
- accounts/index.blade.php
- change_password.blade.php
- catalogs/index.blade.php
- facilities/index.blade.php
- violations/index.blade.php

## God Nodes (most connected - your core abstractions)
1. `Database` - 64 edges
2. `StudentService` - 41 edges
3. `CampusMonitorUI` - 37 edges
4. `Event` - 34 edges
5. `App` - 27 edges
6. `Settings` - 27 edges
7. `TemporalVoting` - 26 edges
8. `ApiError` - 26 edges
9. `validation_error()` - 25 edges
10. `EvidenceStorage` - 23 edges

## Surprising Connections (you probably didn't know these)
- `RetentionScheduler` --uses--> `Database`  [INFERRED]
  Final_System/api/retention.py → Final_System/api/database.py
- `RetentionScheduler` --uses--> `DatabaseUnavailable`  [INFERRED]
  Final_System/api/retention.py → Final_System/api/database.py
- `get_evidence()` --uses--> `R2EvidenceStorage`  [INFERRED]
  Final_System/api/routers/violations.py → Final_System/api/r2_storage.py
- `_CameraState` --uses--> `TemporalVoting`  [INFERRED]
  Final_System/api/service.py → Final_System/ai/temporal_voting.py
- `InferenceService` --uses--> `TemporalVoting`  [INFERRED]
  Final_System/api/service.py → Final_System/ai/temporal_voting.py

## Import Cycles
- None detected.

## Communities (168 total, 109 thin omitted)

### Community 0 - "api/main.py"
Cohesion: 0.05
Nodes (20): EvidenceError, EvidenceStorage, StoredEvidence, UnsafeEvidencePath, validate_and_normalize_image(), create_mapper(), lifespan(), R2EvidenceStorage (+12 more)

### Community 1 - "routers/reports.py"
Cohesion: 0.07
Nodes (28): StatsPromptBuilder, build_pdf(), _build_pdf_fpdf2(), _build_pdf_matplotlib(), build_xlsx(), write_table(), _excel_safe(), _fmt_detail_time() (+20 more)

### Community 2 - "StudentService"
Cohesion: 0.07
Nodes (5): StudentService, ExampleTest, StudentImportExportTest, WebPortalTest, TestCase

### Community 3 - "repositories/facilities.py"
Cohesion: 0.11
Nodes (40): is_foreign_key_violation(), conflict(), not_found(), area_to_dict(), camera_to_dict(), _code(), current_semester(), delete_area() (+32 more)

### Community 5 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 6 - "test_violation_sender.py"
Cohesion: 0.10
Nodes (14): ViolationEvent, ApiConfig, SenderStatus, ViolationSender, FakeResponse, FakeSession, make_event(), sender() (+6 more)

### Community 7 - "Event"
Cohesion: 0.13
Nodes (5): EventRepository, JsonRepository, JsonEventMapper, Event, test_append_and_read_back()

### Community 8 - "web.php"
Cohesion: 0.08
Nodes (7): AccountController, CatalogController, Controller, DashboardController, FacilityController, ReportController, ViolationController

### Community 9 - "test_api_integration.py"
Cohesion: 0.09
Nodes (18): admin(), ctsv(), jpeg(), local_now(), send(), setup_camera(), test_dashboard_and_reports(), test_database_unavailable_returns_503() (+10 more)

### Community 10 - "TemporalVoting"
Cohesion: 0.12
Nodes (14): Prediction, PipelineResult, TemporalVoting, default_config(), feed(), _frame(), result(), test_crop_evidence_validation() (+6 more)

### Community 11 - "PromptBuilder"
Cohesion: 0.10
Nodes (7): GeminiService, PromptBuilder, test_gemini_generates_report(), builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

### Community 12 - "public/assets/js/students.js"
Cohesion: 0.17
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 13 - ".__init__"
Cohesion: 0.09
Nodes (6): CardDetector, Classifier, Detector, TrackResult, pipeline(), sample_frame()

### Community 14 - "app.py"
Cohesion: 0.12
Nodes (3): Camera, _api_message(), check_connection()

### Community 15 - "Database"
Cohesion: 0.13
Nodes (8): build_connection_string(), Database, DatabaseConfigError, DatabaseUnavailable, _odbc_value(), row_to_dict(), rows_to_dicts(), DatabaseSettings

### Community 16 - "User"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 17 - "student_module.php"
Cohesion: 0.18
Nodes (24): student_bind_like(), student_catalog(), student_code(), student_database_failure(), student_decode_json_body(), student_delete(), student_find_class(), student_import_columns() (+16 more)

### Community 18 - "routers/auth.py"
Cohesion: 0.16
Nodes (16): CurrentUser, change_password(), create_account(), list_accounts(), login(), me(), reset_password(), update_account() (+8 more)

### Community 19 - "EventLogger"
Cohesion: 0.13
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 20 - "routers/violations.py"
Cohesion: 0.14
Nodes (10): ApiError, validation_error(), broadcast_violation(), _encode(), get_storage(), ingest_violation(), _parse_confidence(), _parse_occurred_at() (+2 more)

### Community 21 - "accounts.py"
Cohesion: 0.16
Nodes (19): is_unique_violation(), account_to_dict(), authenticate(), change_own_password(), _count_other_active_admins(), create_account(), get_account(), list_accounts() (+11 more)

### Community 22 - "ui/assets/js/students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 23 - "security.py"
Cohesion: 0.17
Nodes (15): get_current_user(), _b64encode(), create_token(), generate_device_key(), hash_device_key(), _require_secret(), TokenError, TokenPayload (+7 more)

### Community 24 - "dependencies.py"
Cohesion: 0.13
Nodes (13): AuthenticatedCamera, get_db(), get_device_camera(), get_mapper(), get_service(), get_settings_dep(), require_roles(), _dependency() (+5 more)

### Community 25 - "repositories/violations.py"
Cohesion: 0.24
Nodes (14): duplicate_result(), evidence_info(), evidence_key_for(), _find_by_event(), find_existing_event(), get_violation(), IncomingViolation, ingest_violation() (+6 more)

### Community 26 - "os"
Cohesion: 0.12
Nodes (3): LLMService, get_password_hash(), main()

### Community 27 - "factories.py"
Cohesion: 0.13
Nodes (6): Pipeline, create_service(), pipeline_factory(), create_voting(), _CameraState, InferenceService

### Community 28 - "routes.py"
Cohesion: 0.18
Nodes (6): Mapper, predict(), BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 30 - "package.json"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 31 - "public/assets/js/violations.js"
Cohesion: 0.15
Nodes (17): badge(), connectSSE(), field(), handleExcelUpload(), load(), loadRedflagMeta(), openDetail(), openRedflagModal() (+9 more)

### Community 33 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 34 - "student_violation_add"
Cohesion: 0.18
Nodes (18): {closure#1}(), {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+10 more)

### Community 36 - "settings.py"
Cohesion: 0.16
Nodes (8): _env(), _env_bool(), _env_int(), get_settings(), _resolve_dir(), client(), database(), evidence_dir()

### Community 38 - "includes/auth.php"
Cohesion: 0.18
Nodes (13): cim_api_error_message(), cim_api_json(), cim_api_request(), cim_clear_auth(), cim_current_user(), cim_idle_seconds(), cim_login(), cim_logout() (+5 more)

### Community 39 - "cli.py"
Cohesion: 0.17
Nodes (8): check_config(), create_admin(), _line(), main(), _read_password(), test_check_config_hides_secrets(), test_create_admin_only_once_without_flag(), test_main_reports_errors_without_traceback()

### Community 40 - "Settings"
Cohesion: 0.21
Nodes (7): device_me(), get_evidence(), get_violation(), list_violations(), update_violation(), violation_types(), Settings

### Community 41 - "CampusMonitorUI"
Cohesion: 0.19
Nodes (3): CampusMonitorUI, _bind_cfg_mousewheel(), _on_cfg_mousewheel()

### Community 42 - "catalog_module.php"
Cohesion: 0.18
Nodes (10): {closure#1}(), {closure#2}(), catalog_code(), catalog_data(), catalog_delete(), catalog_flag(), catalog_int(), catalog_record() (+2 more)

### Community 44 - "student_module_test.php"
Cohesion: 0.17
Nodes (10): check(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), expectWorkbookError(), student_excel_xml(), student_parse_xlsx() (+2 more)

### Community 46 - "errors.py"
Cohesion: 0.20
Nodes (8): bad_request(), error_body(), install_error_handlers(), _api_error(), _database_unavailable(), _http_error(), _unhandled(), _validation()

### Community 47 - "public/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 48 - "ui/assets/js/common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 50 - "students.php"
Cohesion: 0.21
Nodes (11): temporaryWorkbook(), student_detect_xlsx_upload(), student_download_template(), student_export(), student_filter_input(), student_import_upload(), student_request_failure(), student_create_xlsx() (+3 more)

### Community 51 - "report_stats.py"
Cohesion: 0.26
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 55 - "ViolationEventDetector"
Cohesion: 0.21
Nodes (4): combined_status(), crop_evidence(), ViolationEventDetector, test_combined_status_four_states()

### Community 56 - "migrate.py"
Cohesion: 0.40
Nodes (8): applied_versions(), apply(), existing_tables(), main(), migration_files(), run_script(), split_batches(), status()

### Community 58 - "public/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 59 - "ui/assets/js/catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 63 - "public/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 64 - "ui/assets/js/facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 66 - "public/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 67 - "ui/assets/js/student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 68 - "ui/assets/js/violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 69 - "bootstrap/app.php"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 73 - "cim_env"
Cohesion: 0.43
Nodes (5): student_today(), cim_env(), cim_env_bool(), cim_load_env(), student_db()

### Community 77 - "public/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 79 - "ui/assets/js/charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 84 - "public/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

### Community 86 - "ui/assets/js/accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **60 isolated node(s):** `concurrently`, `laravel-vite-plugin`, `tailwindcss`, `@tailwindcss/vite`, `vite` (+55 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 573 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **109 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Database` to `api/main.py`, `routers/reports.py`, `repositories/facilities.py`, `settings.py`, `cli.py`, `test_api_integration.py`, `routers/auth.py`, `routers/violations.py`, `accounts.py`, `security.py`, `dependencies.py`, `repositories/violations.py`, `migrate.py`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Why does `TemporalVoting` connect `TemporalVoting` to `service.py`, `App`, `.__init__`, `app.py`, `factories.py`?**
  _High betweenness centrality (0.022) - this node is a cross-community bridge._
- **Why does `SqliteOutboxRepository` connect `SqliteOutboxRepository` to `SyncWorker`, `app.py`, `Event`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Are the 32 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 32 INFERRED edges - model-reasoned connections that need verification._
- **Are the 7 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 7 INFERRED edges - model-reasoned connections that need verification._
- **What connects `concurrently`, `laravel-vite-plugin`, `tailwindcss` to the rest of the system?**
  _60 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `api/main.py` be split into smaller, more focused modules?**
  _Cohesion score 0.05443371378402107 - nodes in this community are weakly interconnected._