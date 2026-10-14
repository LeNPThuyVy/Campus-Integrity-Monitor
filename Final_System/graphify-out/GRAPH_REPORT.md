# Graph Report - Final_System  (2026-10-01)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 258 nodes · 507 edges · 13 communities (4 shown, 9 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 34 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `b12518c2`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- app.py
- Event
- Mapper
- CampusMonitorUI
- App
- EventLogger
- PipelineResult
- gemini_service.py

## God Nodes (most connected - your core abstractions)
1. `CampusMonitorUI` - 24 edges
2. `Event` - 23 edges
3. `App` - 21 edges
4. `Mapper` - 19 edges
5. `Pipeline` - 14 edges
6. `TemporalVoting` - 14 edges
7. `Classifier` - 13 edges
8. `InferenceService` - 12 edges
9. `EventLogger` - 12 edges
10. `Detector` - 11 edges

## Surprising Connections (you probably didn't know these)
- `App` --uses--> `Classifier`  [INFERRED]
  desktop/app.py → ai/classifier.py
- `App` --uses--> `Detector`  [INFERRED]
  desktop/app.py → ai/detector.py
- `App` --uses--> `Pipeline`  [INFERRED]
  desktop/app.py → ai/pipeline.py
- `App` --uses--> `TemporalVoting`  [INFERRED]
  desktop/app.py → ai/temporal_voting.py
- `TestEventLogger` --uses--> `JsonRepository`  [INFERRED]
  test/reporting/test_event_logger.py → ai/reporting/json_repository.py

## Import Cycles
- None detected.

## Communities (13 total, 9 thin omitted)

### Community 0 - "app.py"
Cohesion: 0.08
Nodes (10): Classifier, Detector, TrackResult, Utils, Pipeline, TemporalVoting, VotingResult, create_pipeline() (+2 more)

### Community 1 - "Event"
Cohesion: 0.09
Nodes (5): EventRepository, JsonRepository, JsonEventMapper, Event, PromptBuilder

### Community 2 - "Mapper"
Cohesion: 0.11
Nodes (11): get_mapper(), get_service(), create_mapper(), create_service(), lifespan(), Mapper, predict(), BBox (+3 more)

### Community 5 - "EventLogger"
Cohesion: 0.14
Nodes (4): EventLogger, ActiveEvent, TrackingResult, TestEventLogger

## Knowledge Gaps
- **9 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `CampusMonitorUI` connect `CampusMonitorUI` to `app.py`, `App`?**
  _High betweenness centrality (0.240) - this node is a cross-community bridge._
- **Why does `App` connect `App` to `app.py`, `CampusMonitorUI`?**
  _High betweenness centrality (0.195) - this node is a cross-community bridge._
- **Why does `Event` connect `Event` to `EventLogger`?**
  _High betweenness centrality (0.120) - this node is a cross-community bridge._
- **Are the 4 inferred relationships involving `Event` (e.g. with `EventRepository` and `JsonRepository`) actually correct?**
  _`Event` has 4 INFERRED edges - model-reasoned connections that need verification._
- **Are the 6 inferred relationships involving `App` (e.g. with `Classifier` and `Detector`) actually correct?**
  _`App` has 6 INFERRED edges - model-reasoned connections that need verification._
- **Are the 7 inferred relationships involving `Mapper` (e.g. with `get_mapper()` and `BBox`) actually correct?**
  _`Mapper` has 7 INFERRED edges - model-reasoned connections that need verification._
- **Are the 5 inferred relationships involving `Pipeline` (e.g. with `Classifier` and `Detector`) actually correct?**
  _`Pipeline` has 5 INFERRED edges - model-reasoned connections that need verification._