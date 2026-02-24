<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;

require base_path('routes/api/test.php');
require base_path('routes/api/auth/login.php');
require base_path('routes/api/auth/e_mail.php');
require base_path('routes/api/auth/register.php');
require base_path('routes/api/food/lunch.php');

Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);