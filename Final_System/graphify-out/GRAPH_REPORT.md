# Graph Report - Final_System  (2026-10-25)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 987 nodes · 2377 edges · 64 communities (32 shown, 32 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 142 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `12366403`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- repositories/facilities.py
- routers/reports.py
- EvidenceStorage
- App
- routers/violations.py
- SqliteOutboxRepository
- test_api_integration.py
- factories.py
- security.py
- datetime
- routers/auth.py
- routes.py
- app.py
- test_voting_and_events.py
- PromptBuilder
- TemporalVoting
- repositories/violations.py
- api/main.py
- dependencies.py
- Event
- cli.py
- CampusMonitorUI
- test_models_smoke.py
- models.py
- test_violation_sender.py
- Database
- ViolationSender
- system.py
- report_stats.py
- classifier.py
- ModernSlider
- create_service
- violation_events.py
- api/auth.py
- database.py
- migrate.py
- test_full_event_flow.py
- .stop_monitoring
- .__init__
- conftest.py
- service.py
- error_body
- gemini_service.py
- ModernSpinbox
- test_prompt_builder.py
- ApiConfig
- authenticate

## God Nodes (most connected - your core abstractions)
1. `Database` - 74 edges
2. `CampusMonitorUI` - 37 edges
3. `Event` - 29 edges
4. `Settings` - 28 edges
5. `TemporalVoting` - 26 edges
6. `ApiError` - 26 edges
7. `App` - 26 edges
8. `validation_error()` - 25 edges
9. `EvidenceStorage` - 24 edges
10. `not_found()` - 22 edges

## Surprising Connections (you probably didn't know these)
- `App` --uses--> `TemporalVoting`  [INFERRED]
  desktop/app.py → ai/temporal_voting.py
- `test_create_admin_only_once_without_flag()` --uses--> `ApiError`  [INFERRED]
  test/integration/test_cli.py → api/errors.py
- `database()` --uses--> `Database`  [INFERRED]
  test/conftest.py → api/database.py
- `test_database_unavailable_returns_503()` --calls--> `Database`  [EXTRACTED]
  test/integration/test_api_integration.py → api/database.py
- `sender()` --uses--> `ViolationSender`  [INFERRED]
  test/unit/test_violation_sender.py → desktop/violation_sender.py

## Import Cycles
- None detected.

## Communities (64 total, 32 thin omitted)

### Community 0 - "repositories/facilities.py"
Cohesion: 0.08
Nodes (54): is_foreign_key_violation(), is_unique_violation(), conflict(), not_found(), validation_error(), account_to_dict(), change_own_password(), _count_other_active_admins() (+46 more)

### Community 1 - "routers/reports.py"
Cohesion: 0.06
Nodes (29): StatsPromptBuilder, build_pdf(), _build_pdf_fpdf2(), _build_pdf_matplotlib(), build_xlsx(), write_table(), _excel_safe(), _fmt_detail_time() (+21 more)

### Community 2 - "EvidenceStorage"
Cohesion: 0.06
Nodes (19): EvidenceError, EvidenceStorage, StoredEvidence, UnsafeEvidencePath, validate_and_normalize_image(), R2EvidenceStorage, _log(), main() (+11 more)

### Community 4 - "routers/violations.py"
Cohesion: 0.11
Nodes (16): touch_camera(), broadcast_violation(), device_me(), _encode(), get_evidence(), get_storage(), get_violation(), ingest_violation() (+8 more)

### Community 6 - "test_api_integration.py"
Cohesion: 0.09
Nodes (17): admin(), ctsv(), jpeg(), local_now(), send(), setup_camera(), test_dashboard_and_reports(), test_database_unavailable_returns_503() (+9 more)

### Community 7 - "factories.py"
Cohesion: 0.12
Nodes (4): CardDetection, CardDetector, Detector, TrackResult

### Community 8 - "security.py"
Cohesion: 0.18
Nodes (17): _b64decode(), _b64encode(), create_token(), hash_device_key(), hash_password(), _require_secret(), TokenError, TokenPayload (+9 more)

### Community 9 - "datetime"
Cohesion: 0.15
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

### Community 10 - "routers/auth.py"
Cohesion: 0.19
Nodes (15): CurrentUser, change_password(), create_account(), list_accounts(), login(), me(), reset_password(), update_account() (+7 more)

### Community 11 - "routes.py"
Cohesion: 0.17
Nodes (6): Mapper, predict(), BBox, DetectionResponse, InferenceResponse, ModelInfo

### Community 12 - "app.py"
Cohesion: 0.15
Nodes (3): Camera, _api_message(), check_connection()

### Community 13 - "test_voting_and_events.py"
Cohesion: 0.17
Nodes (14): Prediction, PipelineResult, ViolationEventDetector, default_config(), feed(), _frame(), result(), test_crop_evidence_validation() (+6 more)

### Community 14 - "PromptBuilder"
Cohesion: 0.12
Nodes (3): GeminiService, PromptBuilder, test_gemini_generates_report()

### Community 15 - "TemporalVoting"
Cohesion: 0.13
Nodes (3): TemporalVoting, _CameraState, InferenceService

### Community 16 - "repositories/violations.py"
Cohesion: 0.24
Nodes (14): duplicate_result(), evidence_info(), evidence_key_for(), _find_by_event(), find_existing_event(), get_violation(), IncomingViolation, ingest_violation() (+6 more)

### Community 17 - "api/main.py"
Cohesion: 0.16
Nodes (7): create_mapper(), lifespan(), _env(), _env_bool(), _env_int(), get_settings(), _resolve_dir()

### Community 18 - "dependencies.py"
Cohesion: 0.20
Nodes (9): AuthenticatedCamera, get_current_user(), get_device_camera(), require_roles(), _dependency(), ApiError, bad_request(), forbidden() (+1 more)

### Community 19 - "Event"
Cohesion: 0.20
Nodes (4): JsonRepository, JsonEventMapper, Event, test_append_and_read_back()

### Community 20 - "cli.py"
Cohesion: 0.17
Nodes (8): check_config(), create_admin(), _line(), main(), _read_password(), test_check_config_hides_secrets(), test_create_admin_only_once_without_flag(), test_main_reports_errors_without_traceback()

### Community 21 - "CampusMonitorUI"
Cohesion: 0.19
Nodes (3): CampusMonitorUI, _bind_cfg_mousewheel(), _on_cfg_mousewheel()

### Community 22 - "test_models_smoke.py"
Cohesion: 0.15
Nodes (3): Classifier, pipeline(), sample_frame()

### Community 24 - "test_violation_sender.py"
Cohesion: 0.26
Nodes (10): ViolationEvent, FakeResponse, FakeSession, make_event(), test_duplicate_answer_counts_as_delivered(), test_enqueue_persists_event_without_identity(), test_event_for_other_camera_is_not_sent(), test_network_error_keeps_event_then_resend_succeeds() (+2 more)

### Community 25 - "Database"
Cohesion: 0.23
Nodes (4): Database, row_to_dict(), rows_to_dicts(), DatabaseSettings

### Community 27 - "system.py"
Cohesion: 0.17
Nodes (7): get_db(), get_mapper(), get_service(), get_settings_dep(), _jsonable(), liveness(), readiness()

### Community 28 - "report_stats.py"
Cohesion: 0.24
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 31 - "create_service"
Cohesion: 0.18
Nodes (4): Pipeline, create_service(), pipeline_factory(), create_voting()

### Community 32 - "violation_events.py"
Cohesion: 0.25
Nodes (3): combined_status(), crop_evidence(), test_combined_status_four_states()

### Community 34 - "database.py"
Cohesion: 0.24
Nodes (4): build_connection_string(), DatabaseConfigError, DatabaseUnavailable, _odbc_value()

### Community 35 - "migrate.py"
Cohesion: 0.40
Nodes (8): applied_versions(), apply(), existing_tables(), main(), migration_files(), run_script(), split_batches(), status()

### Community 41 - "conftest.py"
Cohesion: 0.24
Nodes (3): client(), database(), evidence_dir()

### Community 43 - "error_body"
Cohesion: 0.33
Nodes (7): error_body(), install_error_handlers(), _api_error(), _database_unavailable(), _http_error(), _unhandled(), _validation()

### Community 46 - "test_prompt_builder.py"
Cohesion: 0.38
Nodes (4): builder(), make_event(), test_events_are_listed_with_uniform_and_card_status(), test_multiple_mixed_events_generation()

## Knowledge Gaps
- **32 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `App`, `.update_frame`, `.stop_monitoring`, `.run_in_background`, `.__init__`, `app.py`, `.make_modern_button`?**
  _High betweenness centrality (0.109) - this node is a cross-community bridge._
- **Why does `Database` connect `Database` to `repositories/facilities.py`, `routers/reports.py`, `database.py`, `migrate.py`, `EvidenceStorage`, `routers/violations.py`, `test_api_integration.py`, `conftest.py`, `routers/auth.py`, `repositories/violations.py`, `api/main.py`, `dependencies.py`, `authenticate`, `cli.py`, `system.py`?**
  _High betweenness centrality (0.093) - this node is a cross-community bridge._
- **Why does `App` connect `App` to `app.py`, `test_voting_and_events.py`, `TemporalVoting`, `CampusMonitorUI`, `create_service`?**
  _High betweenness centrality (0.063) - this node is a cross-community bridge._
- **Are the 42 inferred relationships involving `Database` (e.g. with `create_admin()` and `DatabaseSettings`) actually correct?**
  _`Database` has 42 INFERRED edges - model-reasoned connections that need verification._
- **Are the 4 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 4 INFERRED edges - model-reasoned connections that need verification._
- **Are the 18 inferred relationships involving `Settings` (e.g. with `get_current_user()` and `get_settings_dep()`) actually correct?**
  _`Settings` has 18 INFERRED edges - model-reasoned connections that need verification._
- **Are the 4 inferred relationships involving `TemporalVoting` (e.g. with `PipelineResult` and `_CameraState`) actually correct?**
  _`TemporalVoting` has 4 INFERRED edges - model-reasoned connections that need verification._