<?php

use App\Http\Controllers\MailController;
use App\Http\Controllers\AuthController;

Route::get('/send-test', [MailController::class, 'sendTest']);
Route::post('/send-code', [AuthController::class, 'sendCode']);