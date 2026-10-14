<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CimProxyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ViolationController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- Guest / Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------------------------------------------------------------- Authenticated Routes (CIM Session)
Route::middleware(['auth.cim'])->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/home', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/list-vipham', [ViolationController::class, 'index'])->name('violations');
    Route::get('/ql-sinhvien', [StudentController::class, 'index'])->name('students');
    Route::get('/camera-khuvuc', [FacilityController::class, 'index'])->name('facilities');
    Route::get('/reporting', [ReportController::class, 'index'])->name('reports');
    Route::get('/danh-muc', [CatalogController::class, 'index'])->name('catalogs');
    Route::get('/doi-mat-khau', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/doi-mat-khau', [AuthController::class, 'changePassword']);

    // Admin only
    Route::middleware(['role.cim:ADMIN'])->group(function () {
        Route::get('/phanquyen', [AccountController::class, 'index'])->name('accounts');
    });

    // AJAX API compatibility endpoints (for frontend JavaScript)
    Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/api/cim.php', [CimProxyController::class, 'handle']);
    Route::match(['GET', 'POST', 'DELETE'], '/api/students.php', [StudentController::class, 'api']);
    Route::match(['GET', 'POST'], '/api/catalogs.php', [CatalogController::class, 'api']);

    // Clean API aliases
    Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/api/cim', [CimProxyController::class, 'handle']);
    Route::match(['GET', 'POST', 'DELETE'], '/api/students', [StudentController::class, 'api']);
    Route::match(['GET', 'POST'], '/api/catalogs', [CatalogController::class, 'api']);
});

// ---------------------------------------------------------------- Legacy URL Aliases (.php)
Route::get('/login.php', fn() => redirect()->route('login'));
Route::get('/home.php', fn() => redirect()->route('dashboard'));
Route::get('/list_vipham.php', fn() => redirect()->route('violations'));
Route::get('/ql_sinhvien.php', fn() => redirect()->route('students'));
Route::get('/camera_khuvuc.php', fn() => redirect()->route('facilities'));
Route::get('/reporting.php', fn() => redirect()->route('reports'));
Route::get('/danh_muc.php', fn() => redirect()->route('catalogs'));
Route::get('/phanquyen.php', fn() => redirect()->route('accounts'));
Route::get('/doi_mat_khau.php', fn() => redirect()->route('password.change'));
Route::post('/logout.php', [AuthController::class, 'logout']);
