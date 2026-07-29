<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Employee;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth.custom', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/balance', [Admin\BalanceController::class, 'index'])->name('balance');
    Route::post('/balance/set', [Admin\BalanceController::class, 'set'])->name('balance.set');
    Route::post('/balance/add', [Admin\BalanceController::class, 'add'])->name('balance.add');

    Route::resource('jobs', Admin\JobController::class)->except(['show']);

    Route::resource('users', Admin\UserController::class)->except(['show']);

    Route::get('/financials', [Admin\FinancialController::class, 'index'])->name('financials');

    Route::post('/fund-requests/{fundRequest}/approve', [Admin\FundRequestController::class, 'approve'])->name('fund-requests.approve');
    Route::post('/fund-requests/{fundRequest}/dismiss', [Admin\FundRequestController::class, 'dismiss'])->name('fund-requests.dismiss');
});

Route::middleware(['auth.custom', 'employee'])->prefix('employee')->name('employee.')->group(function () {
    Route::get('/dashboard', [Employee\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/jobs', [Employee\JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/create', [Employee\JobController::class, 'create'])->name('jobs.create');
    Route::post('/jobs', [Employee\JobController::class, 'store'])->name('jobs.store');
    Route::post('/jobs/{job}/status', [Employee\JobController::class, 'updateStatus'])->name('jobs.update-status');

    Route::get('/jobs/history', [Employee\JobController::class, 'history'])->name('jobs.history');
});
