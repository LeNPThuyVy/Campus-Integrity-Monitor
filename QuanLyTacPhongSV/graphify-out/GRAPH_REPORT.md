# Graph Report - QuanLyTacPhongSV  (2026-10-18)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 426 nodes · 692 edges · 46 communities (16 shown, 30 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 23 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- composer.json
- web.php
- StudentService
- Illuminate\Http\Request
- User
- students.js
- package.json
- Illuminate\Database\Schema\Blueprint
- CatalogService
- common.js
- WebPortalTest
- catalogs.js
- facilities.js
- student_violations.js
- violations.js
- bootstrap/app.php
- AppServiceProvider
- CimApiClient
- charts.js
- init
- ExampleTest
- accounts.js
- reports.js
- dashboard/index.blade.php
- reports/index.blade.php
- layouts.app
- accounts/index.blade.php
- change_password.blade.php
- facilities/index.blade.php
- students/index.blade.php
- violations/index.blade.php

## God Nodes (most connected - your core abstractions)
1. `StudentService` - 21 edges
2. `CimProxyController` - 21 edges
3. `CatalogService` - 16 edges
4. `AuthController` - 13 edges
5. `User` - 9 edges
6. `scripts` - 9 edges
7. `Controller` - 8 edges
8. `loadStudents()` - 8 edges
9. `showStatus()` - 8 edges
10. `require-dev` - 8 edges

## Surprising Connections (you probably didn't know these)
- `CatalogController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CatalogController.php → app/Http/Controllers/Controller.php
- `StudentController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/StudentController.php → app/Http/Controllers/Controller.php
- `AccountController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AccountController.php → app/Http/Controllers/Controller.php
- `DashboardController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DashboardController.php → app/Http/Controllers/Controller.php
- `FacilityController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FacilityController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (46 total, 30 thin omitted)

### Community 0 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 1 - "web.php"
Cohesion: 0.07
Nodes (7): AccountController, AuthController, Controller, DashboardController, FacilityController, ReportController, ViolationController

### Community 3 - "Illuminate\Http\Request"
Cohesion: 0.21
Nodes (3): CimProxyController, CimAuthenticate, CimRole

### Community 4 - "User"
Cohesion: 0.10
Nodes (3): User, UserFactory, DatabaseSeeder

### Community 5 - "students.js"
Cohesion: 0.21
Nodes (24): actionButton(), addOptions(), closeDrawer(), confirmImport(), currentDataset(), downloadTemplate(), filters(), handleStudentSubmit() (+16 more)

### Community 6 - "package.json"
Cohesion: 0.10
Nodes (20): devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, optionalDependencies, @laravel/multiplex (+12 more)

### Community 7 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 9 - "common.js"
Cohesion: 0.20
Nodes (8): api(), ApiError, buildUrl(), download(), el(), formatDate(), formatDateTime(), stateRow()

### Community 10 - "WebPortalTest"
Cohesion: 0.19
Nodes (3): ExampleTest, WebPortalTest, TestCase

### Community 11 - "catalogs.js"
Cohesion: 0.44
Nodes (10): displayValue(), fieldControl(), loadCatalogs(), optionData(), removeItem(), renderForm(), renderTable(), request() (+2 more)

### Community 12 - "facilities.js"
Cohesion: 0.38
Nodes (7): actionButton(), loadAll(), renderAreaChoices(), renderAreas(), renderCameras(), renderSemesters(), showKey()

### Community 13 - "student_violations.js"
Cohesion: 0.58
Nodes (8): addViolation(), call(), formatDate(), load(), removeViolation(), renderList(), setStatus(), show()

### Community 14 - "violations.js"
Cohesion: 0.39
Nodes (7): badge(), field(), load(), openDetail(), params(), renderEvidence(), renderRow()

### Community 15 - "bootstrap/app.php"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "charts.js"
Cohesion: 0.60
Nodes (3): baseOptions(), render(), renderSummary()

### Community 23 - "accounts.js"
Cohesion: 0.83
Nodes (3): load(), renderRow(), setMode()

## Knowledge Gaps
- **60 isolated node(s):** `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader`, `preferred-install`, `sort-packages` (+55 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 150 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **30 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `CatalogService` connect `CatalogService` to `StudentService`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Why does `CimProxyController` connect `Illuminate\Http\Request` to `web.php`?**
  _High betweenness centrality (0.013) - this node is a cross-community bridge._
- **What connects `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader` to the rest of the system?**
  _60 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.041666666666666664 - nodes in this community are weakly interconnected._
- **Should `web.php` be split into smaller, more focused modules?**
  _Cohesion score 0.07188160676532769 - nodes in this community are weakly interconnected._
- **Should `StudentService` be split into smaller, more focused modules?**
  _Cohesion score 0.09615384615384616 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.09971509971509972 - nodes in this community are weakly interconnected._