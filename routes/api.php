<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserOptionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// User Options API (Web認証 - Inertia.jsのフロントエンドから使用)
Route::middleware(['web', 'auth'])->prefix('user-options')->name('api.user-options.')->group(function () {
    Route::get('/', [UserOptionsController::class, 'show'])->name('show');
    Route::put('/', [UserOptionsController::class, 'update'])->name('update');
});

// Projects API (Sanctum認証)
Route::middleware('auth:sanctum')->prefix('projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index']);
    Route::post('/', [ProjectController::class, 'store']);
    Route::get('/{project}', [ProjectController::class, 'show']);
    Route::put('/{project}', [ProjectController::class, 'update']);
    Route::delete('/{project}', [ProjectController::class, 'destroy']);
    Route::get('/{project}/gantt', [ProjectController::class, 'ganttData']);
});

// Projects API (Web認証 - Inertia.jsのフロントエンドから使用)
Route::middleware(['web', 'auth'])->prefix('projects')->name('api.projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::post('/', [ProjectController::class, 'store'])->name('store');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
    Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    Route::get('/{project}/gantt', [ProjectController::class, 'ganttData'])->name('gantt');
});

// Tasks API (Sanctum認証)
Route::middleware('auth:sanctum')->prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/{task}', [TaskController::class, 'show']);
    Route::put('/{task}', [TaskController::class, 'update']);
    Route::delete('/{task}', [TaskController::class, 'destroy']);
    Route::put('/{task}/progress', [TaskController::class, 'updateProgress']);
    Route::put('/{task}/dates', [TaskController::class, 'updateDates']);
    Route::get('/gantt/data', [TaskController::class, 'ganttData']);
});

// Tasks API (Web認証 - Inertia.jsのフロントエンドから使用)
Route::middleware(['web', 'auth'])->prefix('tasks')->name('api.tasks.')->group(function () {
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::get('/{task}', [TaskController::class, 'show'])->name('show');
    Route::put('/{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
    Route::put('/{task}/progress', [TaskController::class, 'updateProgress'])->name('updateProgress');
    Route::put('/{task}/dates', [TaskController::class, 'updateDates'])->name('updateDates');
    Route::get('/gantt/data', [TaskController::class, 'ganttData'])->name('ganttData');
});