<?php

use App\Http\Controllers\AuthController;


Route::middleware(['web'])->post('/login', [AuthController::class, 'login']);
Route::middleware(['web', 'auth:sanctum'])->post('/logout', [AuthController::class, 'logout']);
Route::middleware(['web', 'auth:sanctum'])->get('/user', [AuthController::class, 'user']);
Route::get('/getUserIP', [AuthController::class, 'getUserIP']);