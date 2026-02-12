<?php

use Illuminate\Support\Facades\Route;

require base_path('routes/api/test.php');

Route::prefix('api')->group(function () {
    require base_path('routes/api/test.php');
});

Route::get('/', function () {
    return view('welcome');
});
