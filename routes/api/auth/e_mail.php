<?php

use App\Http\Controllers\MailController;
use App\Http\Controllers\AuthController;

Route::post('/send-code', [AuthController::class, 'sendCode']);
Route::post('/forgot-password-code', [AuthController::class, 'sendForgotPasswordCode']);
Route::post('/verify-otp', [AuthController::class, 'VerifyOtp']);
