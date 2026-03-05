<?php

use App\Http\Controllers\AuthController;


Route::middleware(['auth:sanctum'])->post('/verify-password', [AuthController::class, 'verifyPassword']);
Route::middleware(['auth:sanctum'])->get('/user', [AuthController::class, 'user']);
Route::middleware(['auth:sanctum'])->get('/user/profile', [AuthController::class, 'profile']);
Route::get('/getAllSkills', [AuthController::class, 'getAllSkills']);
Route::get('/GetPositions', [AuthController::class, 'GetPositions']);
Route::middleware(['auth:sanctum'])->post('/skills/add', [AuthController::class, 'addSkill']);
Route::middleware(['auth:sanctum'])->post('/user/setSeatNumber', [AuthController::class, 'setSeatNumber']);
Route::middleware(['auth:sanctum'])->post('/user/updateAvatar', [AuthController::class, 'updateAvatar']);
Route::middleware(['auth:sanctum'])->post('/user/updateprofile', [AuthController::class, 'updateprofile']);
Route::middleware(['auth:sanctum'])->post('/user/updateUserSkills', [AuthController::class, 'updateUserSkills']);
Route::middleware(['auth:sanctum'])->post('/user/updatePassword', [AuthController::class, 'updatePassword']);
Route::middleware('auth:sanctum')->post('/user/deactivate', [AuthController::class, 'deactivate']);
Route::get('/getUserIP', [AuthController::class, 'getUserIP']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/GetAllStudents', [AuthController::class, 'GetAllStudents']);


