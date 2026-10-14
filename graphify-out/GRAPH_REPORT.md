# Graph Report - Campus-Integrity-Monitor  (2026-10-08)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 490 nodes · 1022 edges · 21 communities (12 shown, 9 thin omitted)
- Extraction: 91% EXTRACTED · 9% INFERRED · 0% AMBIGUOUS · INFERRED: 92 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `863d4022`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- event_routes.py
- Event
- event_setup.py
- EventLogger
- CampusMonitorUI
- Mapper
- event_store.py
- App
- SyncWorker
- Pipeline
- Classifier
- app.py
- report_docx.py
- report_stats.py
- Detector
- TemporalVoting

## God Nodes (most connected - your core abstractions)
1. `EventStore` - 35 edges
2. `Event` - 35 edges
3. `App` - 30 edges
4. `CampusMonitorUI` - 24 edges
5. `EventLogger` - 19 edges
6. `Mapper` - 19 edges
7. `SqliteOutboxRepository` - 18 edges
8. `Pipeline` - 16 edges
9. `TrackingResult` - 15 edges
10. `TemporalVoting` - 14 edges

## Surprising Connections (you probably didn't know these)
- `get_device()` --uses--> `EventStore`  [INFERRED]
  Final_System/api/event_routes.py → Final_System/api/event_store.py
- `EventStore` --uses--> `Event`  [INFERRED]
  Final_System/api/event_store.py → Final_System/ai/reporting/models.py
- `EventLogger` --uses--> `EventRepository`  [INFERRED]
  Final_System/ai/reporting/event_logger.py → Final_System/ai/reporting/event_repository.py
- `TestEventLogger` --uses--> `JsonRepository`  [INFERRED]
  Final_System/test/reporting/test_event_logger.py → Final_System/ai/reporting/json_repository.py
- `build_statistics()` --uses--> `Event`  [INFERRED]
  Final_System/ai/reporting/report_stats.py → Final_System/ai/reporting/models.py

## Import Cycles
- None detected.

## Communities (21 total, 9 thin omitted)

### Community 0 - "event_routes.py"
Cohesion: 0.06
Nodes (39): create_access_token(), decode_access_token(), verify_password(), _build_image_key(), _build_period_stats(), cleanup_retention(), confirm_image(), create_report() (+31 more)

### Community 1 - "Event"
Cohesion: 0.06
Nodes (6): EventRepository, JsonRepository, JsonEventMapper, Event, PromptBuilder, SqliteOutboxRepository

### Community 2 - "event_setup.py"
Cohesion: 0.06
Nodes (11): GeminiService, LLMError, LLMService, ReportService, StatsPromptBuilder, create_db_engine(), setup_event_services(), create_mapper() (+3 more)

### Community 3 - "EventLogger"
Cohesion: 0.07
Nodes (6): EventLogger, _iou(), ActiveEvent, get_violation_type(), TrackingResult, TestEventLogger

### Community 4 - "CampusMonitorUI"
Cohesion: 0.08
Nodes (3): CampusMonitorUI, _bind_mousewheel(), _on_mousewheel()

### Community 5 - "Mapper"
Cohesion: 0.12
Nodes (9): get_mapper(), get_service(), Mapper, predict(), BBox, DetectionResponse, InferenceResponse, ModelInfo (+1 more)

### Community 10 - "Pipeline"
Cohesion: 0.17
Nodes (4): CardDetection, CardDetector, Pipeline, PipelineResult

### Community 11 - "Classifier"
Cohesion: 0.16
Nodes (3): Classifier, Prediction, Utils

### Community 13 - "report_docx.py"
Cohesion: 0.16
Nodes (3): _add_analysis(), _add_table(), build_docx()

### Community 14 - "report_stats.py"
Cohesion: 0.23
Nodes (6): build_statistics(), get_period_range(), get_previous_range(), _rate(), _summary(), _to_local()

### Community 15 - "Detector"
Cohesion: 0.20
Nodes (3): Detector, TrackResult, create_pipeline()

### Community 16 - "TemporalVoting"
Cohesion: 0.25
Nodes (3): TemporalVoting, VotingResult, create_voting()

## Knowledge Gaps
- **9 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Event` connect `Event` to `event_routes.py`, `EventLogger`, `report_stats.py`, `event_store.py`?**
  _High betweenness centrality (0.159) - this node is a cross-community bridge._
- **Why does `App` connect `App` to `Event`, `EventLogger`, `CampusMonitorUI`, `SyncWorker`, `Pipeline`, `Classifier`, `app.py`, `Detector`, `TemporalVoting`?**
  _High betweenness centrality (0.150) - this node is a cross-community bridge._
- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `app.py`, `App`?**
  _High betweenness centrality (0.138) - this node is a cross-community bridge._
- **Are the 18 inferred relationships involving `EventStore` (e.g. with `_build_period_stats()` and `cleanup_retention()`) actually correct?**
  _`EventStore` has 18 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 8 INFERRED edges - model-reasoned connections that need verification._
- **Are the 12 inferred relationships involving `App` (e.g. with `CardDetector` and `Classifier`) actually correct?**
  _`App` has 12 INFERRED edges - model-reasoned connections that need verification._
- **Are the 3 inferred relationships involving `EventLogger` (e.g. with `EventRepository` and `App`) actually correct?**
  _`EventLogger` has 3 INFERRED edges - model-reasoned connections that need verification._