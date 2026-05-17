<?php

use App\Http\Controllers\Web\EmployeeHistoryController;
use App\Http\Controllers\Web\EmployeeLoginController;
use App\Http\Controllers\Web\EmployeePunchController;
use App\Http\Controllers\Web\EmployeeShiftController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\LogsWebController;
use App\Http\Controllers\Web\OverviewController;
use App\Http\Controllers\Web\ShiftsWebController;
use App\Http\Controllers\Web\TeamWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('landing'))->name('home');

Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('login',  [EmployeeLoginController::class, 'show'])->name('login');
    Route::post('login', [EmployeeLoginController::class, 'login'])->name('login.submit');
    Route::post('logout',[EmployeeLoginController::class, 'logout'])->name('logout');

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
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.submit');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware('web.manager')->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->name('overview');
        Route::get('team', [TeamWebController::class, 'index'])->name('team');
        Route::get('shifts', [ShiftsWebController::class, 'index'])->name('shifts');
        Route::get('logs', [LogsWebController::class, 'index'])->name('logs');
    });
});
