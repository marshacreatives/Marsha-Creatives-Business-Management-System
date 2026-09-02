<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->middleware('agent.api')->group(function () {
    Route::get('/ping', [AgentController::class, 'ping']);
    Route::post('/alert', [AgentController::class, 'storeAlert']);
    Route::post('/status', [AgentController::class, 'storeStatus']);
    Route::post('/block', [AgentController::class, 'storeBlock']);
    Route::post('/unblock', [AgentController::class, 'storeUnblock']);
    Route::post('/log', [AgentController::class, 'storeLog']);
});
