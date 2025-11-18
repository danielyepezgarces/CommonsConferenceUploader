<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/callback', [AuthController::class, 'callback']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);

// Event routes
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/events', [EventController::class, 'create']);
Route::put('/events/{id}', [EventController::class, 'update']);
Route::delete('/events/{id}', [EventController::class, 'delete']);
Route::get('/events/{id}/stats', [EventController::class, 'statistics']);

// Upload routes
Route::post('/events/{id}/upload', [UploadController::class, 'upload']);
Route::get('/events/{id}/uploads', [UploadController::class, 'list']);
Route::get('/uploads/my', [UploadController::class, 'myUploads']);

// Category routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/search', [CategoryController::class, 'search']);
Route::get('/categories/top', [CategoryController::class, 'top']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'create']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);
Route::delete('/categories/{id}', [CategoryController::class, 'delete']);

// Statistics routes
Route::get('/stats/user', [StatsController::class, 'user']);
Route::get('/stats/global', [StatsController::class, 'global']);
