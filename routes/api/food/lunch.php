<?php

use App\Http\Controllers\LunchController;


Route::get('/getShops', [LunchController::class, 'getShops']);
Route::get('/getCategories', [LunchController::class, 'getCategories']);
Route::get('/getFoods', [LunchController::class, 'getFoods']);
Route::post('/addorder', [LunchController::class, 'addorder']);
Route::get('/getOrders', [LunchController::class, 'getOrders']);
Route::post('/orderpaid', [LunchController::class, 'orderpaid']);
Route::get('/getManagerControl', [LunchController::class, 'getManagerControl']);



