# Graph Report - QuanLyTacPhongSV  (2026-10-14)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 402 nodes · 594 edges · 49 communities (15 shown, 34 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 23 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- composer.json
- User
- StudentService
- students.js
- package.json
- Illuminate\Database\Schema\Blueprint
- Illuminate\View\View
- CatalogController.php
- common.js
- WebPortalTest
- CatalogService
- Illuminate\Http\Request
- catalogs.js
- facilities.js
- AuthController
- CimApiClient
- student_violations.js
- violations.js
- bootstrap/app.php
- AppServiceProvider
- charts.js
- init
- ExampleTest
- accounts.js
- reports.js
- dashboard/index.blade.php
- reports/index.blade.php
- accounts/index.blade.php
- change_password.blade.php
- catalogs/index.blade.php
- facilities/index.blade.php
- students/index.blade.php
- violations/index.blade.php

## God Nodes (most connected - your core abstractions)
1. `StudentService` - 19 edges
2. `CatalogService` - 14 edges
3. `AuthController` - 11 edges
4. `CimApiClient` - 11 edges
5. `Controller` - 10 edges
6. `User` - 9 edges
7. `scripts` - 9 edges
8. `loadStudents()` - 8 edges
9. `showStatus()` - 8 edges
10. `require-dev` - 8 edges

## Surprising Connections (you probably didn't know these)
- `CatalogController` --references--> `CatalogService`  [EXTRACTED]
  app/Http/Controllers/CatalogController.php → app/Services/CatalogService.php
- `AuthController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AuthController.php → app/Http/Controllers/Controller.php
- `AuthController` --references--> `CimApiClient`  [EXTRACTED]
  app/Http/Controllers/AuthController.php → app/Services/CimApiClient.php
- `CimProxyController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CimProxyController.php → app/Http/Controllers/Controller.php
- `StudentController` --references--> `StudentService`  [EXTRACTED]
  app/Http/Controllers/StudentController.php → app/Services/StudentService.php

## Import Cycles
- None detected.

## Communities (49 total, 34 thin omitted)

### Community 0 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 1 - "User"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 3 - "students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 4 - "package.json"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 5 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 6 - "Illuminate\View\View"
Cohesion: 0.17
Nodes (6): AccountController, Controller, DashboardController, FacilityController, ReportController, ViolationController

### Community 8 - "common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 10 - "WebPortalTest"
Cohesion: 0.19
Nodes (3): ExampleTest, WebPortalTest, TestCase

### Community 13 - "catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 14 - "facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 17 - "student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 18 - "violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 19 - "bootstrap/app.php"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 22 - "charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 26 - "accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **60 isolated node(s):** `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader`, `preferred-install`, `sort-packages` (+55 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 149 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **34 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `StudentService` connect `StudentService` to `CatalogController.php`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Why does `CatalogService` connect `CatalogService` to `StudentService`, `CatalogController.php`?**
  _High betweenness centrality (0.016) - this node is a cross-community bridge._
- **Why does `StudentController` connect `CatalogController.php` to `web.php`, `StudentService`, `Illuminate\View\View`?**
  _High betweenness centrality (0.012) - this node is a cross-community bridge._
- **What connects `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader` to the rest of the system?**
  _60 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.041666666666666664 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.09971509971509972 - nodes in this community are weakly interconnected._
- **Should `StudentService` be split into smaller, more focused modules?**
  _Cohesion score 0.12923076923076923 - nodes in this community are weakly interconnected._