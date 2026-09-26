<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Documents;
use App\Http\Controllers\Employee;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Security;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
 * Notifications and push subscriptions are scoped per user rather than per
 * role, so they sit outside the admin and employee groups and only require
 * an authenticated session.
 */
Route::middleware('auth.custom')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/all', [NotificationController::class, 'page'])->name('notifications.page');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/push/key', [PushSubscriptionController::class, 'key'])->name('push.key');
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

Route::middleware(['auth.custom', 'admin'])->prefix('security')->name('security.')->group(function () {
    Route::get('/', [Security\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/alerts', [Security\AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [Security\AlertController::class, 'show'])->name('alerts.show');
    Route::post('/alerts/{alert}/resolve', [Security\AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::post('/alerts/{alert}/reopen', [Security\AlertController::class, 'reopen'])->name('alerts.reopen');
    Route::delete('/alerts/{alert}', [Security\AlertController::class, 'destroy'])->name('alerts.destroy');

    Route::get('/blocks', [Security\IPBlockController::class, 'index'])->name('blocks.index');
    Route::post('/blocks', [Security\IPBlockController::class, 'block'])->name('blocks.store');
    Route::post('/blocks/{block}/unblock', [Security\IPBlockController::class, 'unblock'])->name('blocks.unblock');
    Route::delete('/blocks/{block}', [Security\IPBlockController::class, 'destroy'])->name('blocks.destroy');

    Route::get('/server-status', [Security\ServerStatusController::class, 'index'])->name('server-status');

    Route::get('/actions', [Security\AgentActionController::class, 'index'])->name('actions.index');

    Route::get('/settings', [Security\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [Security\SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth.custom', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/balance', [Admin\BalanceController::class, 'index'])->name('balance');
    Route::post('/balance/set', [Admin\BalanceController::class, 'set'])->name('balance.set');
    Route::post('/balance/add', [Admin\BalanceController::class, 'add'])->name('balance.add');

    Route::resource('jobs', Admin\JobController::class)->except(['show']);

    Route::resource('users', Admin\UserController::class)->except(['show']);

    Route::post('/documents/items/quick', [Documents\DocumentController::class, 'quickStore'])->name('documents.items.quick');
    Route::resource('items', Documents\ItemController::class)->except(['show']);
    Route::resource('documents', Documents\DocumentController::class)->except(['show']);
    Route::get('/documents/{document}/pdf', [Documents\DocumentController::class, 'downloadPdf'])->name('documents.pdf');

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

    Route::post('/documents/items/quick', [Documents\DocumentController::class, 'quickStore'])->name('documents.items.quick');
    Route::resource('items', Documents\ItemController::class)->except(['show']);
    Route::resource('documents', Documents\DocumentController::class)->except(['show']);
    Route::get('/documents/{document}/pdf', [Documents\DocumentController::class, 'downloadPdf'])->name('documents.pdf');
});
