<?php

use App\Http\Controllers\Web\EmployeeHistoryController;
use App\Http\Controllers\Web\EmployeeLoginController;
use App\Http\Controllers\Web\EmployeePunchController;
use App\Http\Controllers\Web\EmployeeShiftController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\LogsWebController;
use App\Http\Controllers\Web\OverviewController;
use App\Http\Controllers\Web\ShiftsWebController;
use App\Http\Controllers\Web\SitesWebController;
use App\Http\Controllers\Web\TeamWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('landing'))->name('home');

Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('login',  [EmployeeLoginController::class, 'show'])->name('login');
    Route::post('login', [EmployeeLoginController::class, 'login'])->name('login.submit');
    Route::post('logout',[EmployeeLoginController::class, 'logout'])->name('logout');
    Route::get('pin',    [EmployeeLoginController::class, 'showPin'])->name('pin');
    Route::post('pin',   [EmployeeLoginController::class, 'verifyPin'])->name('pin.submit');

    Route::middleware(['web.employee', 'org.isolation'])->group(function () {
        Route::get('/',             [EmployeePunchController::class, 'index'])->name('clock');
        Route::post('clock-in',    [EmployeePunchController::class, 'clockIn'])->name('clock.in');
        Route::post('clock-out',   [EmployeePunchController::class, 'clockOut'])->name('clock.out');
        Route::post('break-start', [EmployeePunchController::class, 'breakStart'])->name('break.start');
        Route::post('break-end',   [EmployeePunchController::class, 'breakEnd'])->name('break.end');

        Route::get('history', [EmployeeHistoryController::class, 'index'])->name('history');
        Route::get('shifts',  [EmployeeShiftController::class, 'index'])->name('shifts');
    });
});


Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('login',  [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.submit');
    Route::post('logout',[LoginController::class, 'logout'])->name('logout');
    Route::get('pin',    [LoginController::class, 'showPin'])->name('pin');
    Route::post('pin',   [LoginController::class, 'verifyPin'])->name('pin.submit');

    Route::middleware('web.manager')->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->name('overview');

        Route::get('team', [TeamWebController::class, 'index'])->name('team');
        Route::post('team', [TeamWebController::class, 'store'])->name('team.store');
        Route::patch('team/{id}', [TeamWebController::class, 'update'])->name('team.update');
        Route::delete('team/{id}', [TeamWebController::class, 'deactivate'])->name('team.deactivate');
        Route::post('team/{id}/reset-pin', [TeamWebController::class, 'resetPin'])->name('team.reset-pin');

        Route::get('shifts', [ShiftsWebController::class, 'index'])->name('shifts');
        Route::post('shifts', [ShiftsWebController::class, 'store'])->name('shifts.store');
        Route::patch('shifts/{id}', [ShiftsWebController::class, 'update'])->name('shifts.update');
        Route::delete('shifts/{id}', [ShiftsWebController::class, 'destroy'])->name('shifts.destroy');

        Route::get('logs', [LogsWebController::class, 'index'])->name('logs');
        Route::post('logs/adjust', [LogsWebController::class, 'manualAdj'])->name('logs.adjust');
        Route::delete('logs/sessions/{id}', [LogsWebController::class, 'voidSession'])->name('logs.void');

        Route::get('sites', [SitesWebController::class, 'index'])->name('sites');
        Route::post('sites', [SitesWebController::class, 'store'])->name('sites.store');
        Route::patch('sites/{id}', [SitesWebController::class, 'update'])->name('sites.update');
        Route::delete('sites/{id}', [SitesWebController::class, 'destroy'])->name('sites.destroy');
    });
});
