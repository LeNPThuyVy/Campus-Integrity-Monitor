# Graph Report - Final_System  (2026-10-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 279 nodes · 541 edges · 14 communities (5 shown, 9 thin omitted)
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 32 edges (avg confidence: 0.93)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2935937b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Event
- app.py
- factories.py
- CampusMonitorUI
- Mapper
- App
- gemini_service.py
- .track

## God Nodes (most connected - your core abstractions)
1. `CampusMonitorUI` - 24 edges
2. `Event` - 23 edges
3. `Mapper` - 19 edges
4. `App` - 19 edges
5. `TemporalVoting` - 14 edges
6. `EventLogger` - 12 edges
7. `InferenceService` - 12 edges
8. `JsonRepository` - 11 edges
9. `TrackingResult` - 11 edges
10. `Pipeline` - 11 edges

## Surprising Connections (you probably didn't know these)
- `App` --uses--> `Pipeline`  [INFERRED]
  desktop/app.py → ai/pipeline.py
- `App` --uses--> `TemporalVoting`  [INFERRED]
  desktop/app.py → ai/temporal_voting.py
- `TestEventLogger` --uses--> `JsonRepository`  [INFERRED]
  test/reporting/test_event_logger.py → ai/reporting/json_repository.py
- `TestEventLogger` --uses--> `EventLogger`  [INFERRED]
  test/reporting/test_event_logger.py → ai/reporting/event_logger.py
- `TestEventLogger` --uses--> `TrackingResult`  [INFERRED]
  test/reporting/test_event_logger.py → ai/reporting/models.py

## Import Cycles
- None detected.

## Communities (14 total, 9 thin omitted)

### Community 0 - "Event"
Cohesion: 0.06
Nodes (9): EventLogger, EventRepository, JsonRepository, JsonEventMapper, ActiveEvent, Event, TrackingResult, PromptBuilder (+1 more)

### Community 1 - "app.py"
Cohesion: 0.08
Nodes (5): CardDetection, CardDetector, Prediction, Utils, Camera

### Community 2 - "factories.py"
Cohesion: 0.07
Nodes (12): Classifier, Detector, Pipeline, PipelineResult, TemporalVoting, VotingResult, create_mapper(), create_pipeline() (+4 more)

### Community 3 - "CampusMonitorUI"
Cohesion: 0.08
Nodes (3): CampusMonitorUI, _bind_mousewheel(), _on_mousewheel()

### Community 4 - "Mapper"
Cohesion: 0.14
Nodes (8): get_mapper(), get_service(), Mapper, predict(), BBox, DetectionResponse, InferenceResponse, ModelInfo

## Knowledge Gaps
- **9 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `app.py`, `App`?**
  _High betweenness centrality (0.246) - this node is a cross-community bridge._
- **Why does `App` connect `App` to `app.py`, `factories.py`, `CampusMonitorUI`?**
  _High betweenness centrality (0.168) - this node is a cross-community bridge._
- **Are the 4 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 4 INFERRED edges - model-reasoned connections that need verification._
- **Are the 7 inferred relationships involving `Mapper` (e.g. with `get_mapper()` and `BBox`) actually correct?**
  _`Mapper` has 7 INFERRED edges - model-reasoned connections that need verification._
- **Are the 4 inferred relationships involving `App` (e.g. with `Pipeline` and `TemporalVoting`) actually correct?**
  _`App` has 4 INFERRED edges - model-reasoned connections that need verification._
- **Are the 3 inferred relationships involving `TemporalVoting` (e.g. with `PipelineResult` and `InferenceService`) actually correct?**
  _`TemporalVoting` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Should `Event` be split into smaller, more focused modules?**
  _Cohesion score 0.06351236146632566 - nodes in this community are weakly interconnected._