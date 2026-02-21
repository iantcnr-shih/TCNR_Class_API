<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-web', function () {
    return 'ok';
});
Route::get('/test-no-session', function () {
    return 'ok';
})->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class]);
Route::get('/phpinfo', function () {
    phpinfo();
});
Route::get('/gd-test', function () {
    return function_exists('imagecreate') ? 'GD OK' : 'GD NOT FOUND';
});
Route::middleware(['web'])->get('/my-captcha/default', function () {
    Log::info('Session before captcha: ', session()->all());
    return Captcha::create('default');
});
// Route::get('/my-captcha/default', function () {
//     Log::info('Session before captcha: ', session()->all());
//     return response(Captcha::create('default'))
//         ->header('Content-Type', 'image/png');
// });
Route::get('/my-captcha/default', function () {
    Log::info('Session before captcha: ', session()->all());
    return Captcha::create('default');
});