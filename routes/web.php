<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-web', function () {
    return 'ok';
});
Route::get('/test-no-session', function () {
    return 'ok';
})->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class]);