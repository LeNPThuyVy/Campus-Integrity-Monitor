# Graph Report - Final_System  (2026-10-23)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 965 nodes · 2371 edges · 58 communities (30 shown, 28 thin omitted)
- Extraction: 90% EXTRACTED · 10% INFERRED · 0% AMBIGUOUS · INFERRED: 226 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `d6a747a1`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- EvidenceStorage
- Database
- App
- test_violation_sender.py
- migrate.py
- SqliteOutboxRepository
- test_api_integration.py
- exporters.py
- factories.py
- accounts.py
- test_voting_and_events.py
- routers/violations.py
- repositories/facilities.py
- datetime
- repositories/reports.py
- routers/reports.py
- routes.py
- security.py
- repositories/violations.py
- PipelineResult
- models.py
- Event
- dependencies.py
- CampusMonitorUI
- cli.py
- settings.py
- routers/auth.py
- report_stats.py
- PromptBuilder
- ModernSlider
- ViolationEventDetector
- test_prompt_builder.py
- database.py
- .transaction
- CardDetection
- .stop_monitoring
- .__init__
- test_api_gemini.py
- service.py
- error_body
- test_full_event_flow.py
- _CameraState
- ModernSpinbox
- generate_device_key
- .track

## God Nodes (most connected - your core abstractions)
1. `Database` - 114 edges
2. `CampusMonitorUI` - 37 edges
3. `CurrentUser` - 35 edges
4. `App` - 31 edges
5. `Event` - 29 edges
6. `EvidenceStorage` - 28 edges
7. `Settings` - 28 edges
8. `ApiError` - 26 edges
9. `validation_error()` - 25 edges
10. `TemporalVoting` - 24 edges

## Surprising Connections (you probably didn't know these)
- `database()` --uses--> `Database`  [INFERRED]
  test/conftest.py → api/database.py
- `test_database_unavailable_returns_503()` --calls--> `Database`  [EXTRACTED]
  test/integration/test_api_integration.py → api/database.py
- `_CameraState` --uses--> `TemporalVoting`  [INFERRED]
  api/service.py → ai/temporal_voting.py
- `InferenceService` --uses--> `TemporalVoting`  [INFERRED]
  api/service.py → ai/temporal_voting.py
- `App` --uses--> `TemporalVoting`  [INFERRED]
  desktop/app.py → ai/temporal_voting.py

## Import Cycles
- None detected.

## Communities (58 total, 28 thin omitted)

### Community 0 - "EvidenceStorage"
Cohesion: 0.05
Nodes (23): EvidenceError, EvidenceStorage, StoredEvidence, UnsafeEvidencePath, validate_and_normalize_image(), create_mapper(), lifespan(), R2EvidenceStorage (+15 more)

### Community 1 - "Database"
Cohesion: 0.11
Nodes (29): Database, CurrentUser, delete_semester(), change_password(), create_account(), list_accounts(), me(), reset_password() (+21 more)

### Community 3 - "test_violation_sender.py"
Cohesion: 0.09
Nodes (16): ViolationEvent, _api_message(), ApiConfig, check_connection(), SenderStatus, ViolationSender, FakeResponse, FakeSession (+8 more)

### Community 4 - "migrate.py"
Cohesion: 0.07
Nodes (14): LLMService, get_password_hash(), main(), applied_versions(), apply(), existing_tables(), main(), migration_files() (+6 more)

### Community 6 - "test_api_integration.py"
Cohesion: 0.09
Nodes (17): admin(), ctsv(), jpeg(), local_now(), send(), setup_camera(), test_dashboard_and_reports(), test_database_unavailable_returns_503() (+9 more)

### Community 7 - "exporters.py"
Cohesion: 0.12
Nodes (10): StatsPromptBuilder, build_pdf(), _build_pdf_fpdf2(), _build_pdf_matplotlib(), build_xlsx(), write_table(), _excel_safe(), _fmt_detail_time() (+2 more)

### Community 8 - "factories.py"
Cohesion: 0.14
Nodes (9): Classifier, Detector, Pipeline, create_service(), pipeline_factory(), create_voting(), InferenceService, pipeline() (+1 more)

### Community 9 - "accounts.py"
Cohesion: 0.18
Nodes (20): is_unique_violation(), conflict(), not_found(), validation_error(), account_to_dict(), change_own_password(), _count_other_active_admins(), create_account() (+12 more)

### Community 10 - "test_voting_and_events.py"
Cohesion: 0.15
Nodes (12): TemporalVoting, default_config(), feed(), _frame(), result(), test_crop_evidence_validation(), test_event_detector_reports_once_per_track_and_respects_dedup_window(), test_low_confidence_predictions_are_ignored() (+4 more)

### Community 11 - "routers/violations.py"
Cohesion: 0.13
Nodes (12): AuthenticatedCamera, touch_camera(), broadcast_violation(), device_me(), _encode(), get_storage(), ingest_violation(), _parse_confidence() (+4 more)

### Community 12 - "repositories/facilities.py"
Cohesion: 0.20
Nodes (17): is_foreign_key_violation(), area_to_dict(), camera_to_dict(), _code(), current_semester(), get_area(), get_camera(), get_semester() (+9 more)

### Community 13 - "datetime"
Cohesion: 0.15
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 14 - "repositories/reports.py"
Cohesion: 0.22
Nodes (15): count_in_range(), dashboard(), describe_filters(), detail_rows(), _grouped(), report_filters(), ReportRange, resolve_range() (+7 more)

### Community 15 - "routers/reports.py"
Cohesion: 0.13
Nodes (11): get_db(), get_mapper(), get_service(), get_settings_dep(), _generate_ai_analysis(), _get_prompt_builder(), report_ai_pdf(), _jsonable() (+3 more)

### Community 16 - "routes.py"
Cohesion: 0.18
Nodes (6): Mapper, predict(), BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 17 - "security.py"
Cohesion: 0.18
Nodes (13): _b64decode(), _b64encode(), create_token(), _require_secret(), TokenError, TokenPayload, validate_password_strength(), verify_password() (+5 more)

### Community 18 - "repositories/violations.py"
Cohesion: 0.26
Nodes (13): duplicate_result(), evidence_info(), evidence_key_for(), _find_by_event(), find_existing_event(), get_violation(), IncomingViolation, ingest_violation() (+5 more)

### Community 19 - "PipelineResult"
Cohesion: 0.13
Nodes (3): Prediction, Utils, PipelineResult

### Community 21 - "Event"
Cohesion: 0.20
Nodes (4): JsonRepository, JsonEventMapper, Event, test_append_and_read_back()

### Community 22 - "dependencies.py"
Cohesion: 0.21
Nodes (8): get_current_user(), get_device_camera(), require_roles(), _dependency(), ApiError, bad_request(), forbidden(), unauthorized()

### Community 23 - "CampusMonitorUI"
Cohesion: 0.19
Nodes (3): CampusMonitorUI, _bind_cfg_mousewheel(), _on_cfg_mousewheel()

### Community 24 - "cli.py"
Cohesion: 0.18
Nodes (8): check_config(), create_admin(), _line(), main(), _read_password(), test_check_config_hides_secrets(), test_create_admin_only_once_without_flag(), test_main_reports_errors_without_traceback()

### Community 25 - "settings.py"
Cohesion: 0.18
Nodes (6): DatabaseSettings, _env(), _env_bool(), _env_int(), get_settings(), _resolve_dir()

### Community 26 - "routers/auth.py"
Cohesion: 0.25
Nodes (9): authenticate(), login(), AccountCreateRequest, AccountUpdateRequest, ChangePasswordRequest, LoginRequest, PasswordResetRequest, ProcessingUpdateRequest (+1 more)

### Community 29 - "report_stats.py"
Cohesion: 0.24
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 32 - "ViolationEventDetector"
Cohesion: 0.21
Nodes (4): combined_status(), crop_evidence(), ViolationEventDetector, test_combined_status_four_states()

### Community 33 - "test_prompt_builder.py"
Cohesion: 0.22
Nodes (4): builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

### Community 34 - "database.py"
Cohesion: 0.24
Nodes (4): build_connection_string(), DatabaseConfigError, DatabaseUnavailable, _odbc_value()

### Community 43 - "error_body"
Cohesion: 0.33
Nodes (7): error_body(), install_error_handlers(), _api_error(), _database_unavailable(), _http_error(), _unhandled(), _validation()

### Community 47 - "generate_device_key"
Cohesion: 0.47
Nodes (5): rotate_device_key(), generate_device_key(), hash_device_key(), verify_device_key(), test_device_key_hash_and_verify()

## Knowledge Gaps
- **28 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Database` connect `Database` to `EvidenceStorage`, `database.py`, `.transaction`, `migrate.py`, `test_api_integration.py`, `accounts.py`, `routers/violations.py`, `repositories/facilities.py`, `repositories/reports.py`, `routers/reports.py`, `generate_device_key`, `repositories/violations.py`, `dependencies.py`, `cli.py`, `settings.py`, `routers/auth.py`?**
  _High betweenness centrality (0.137) - this node is a cross-community bridge._
- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `App`, `.update_frame`, `.stop_monitoring`, `.run_in_background`, `.__init__`, `.make_modern_button`, `app.py`?**
  _High betweenness centrality (0.112) - this node is a cross-community bridge._
- **Why does `App` connect `App` to `ViolationEventDetector`, `test_violation_sender.py`, `factories.py`, `test_voting_and_events.py`, `CampusMonitorUI`, `app.py`?**
  _High betweenness centrality (0.070) - this node is a cross-community bridge._
- **Are the 83 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 83 INFERRED edges - model-reasoned connections that need verification._
- **Are the 27 inferred relationships involving `CurrentUser` (e.g. with `change_password()` and `create_account()`) actually correct?**
  _`CurrentUser` has 27 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `App` (e.g. with `Classifier` and `Detector`) actually correct?**
  _`App` has 8 INFERRED edges - model-reasoned connections that need verification._
- **Are the 4 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 4 INFERRED edges - model-reasoned connections that need verification._