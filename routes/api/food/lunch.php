<?php

use App\Http\Controllers\LunchController;
use Illuminate\Support\Facades\Route;

Route::get('/getShops', [LunchController::class, 'getShops']);
Route::get('/getCategories', [LunchController::class, 'getCategories']);
Route::get('/getFoods', [LunchController::class, 'getFoods']);
Route::post('/addorder', [LunchController::class, 'addorder']);
Route::get('/getOrders', [LunchController::class, 'getOrders']);
Route::post('/orderpaid', [LunchController::class, 'orderpaid']);
Route::get('/getManagerControl', [LunchController::class, 'getManagerControl']);
Route::post('/addbubbleteaorder', [LunchController::class, 'addbubbleteaorder']);
Route::get('/getBubbleteaorders', [LunchController::class, 'getBubbleteaorders']);
Route::post('/bubbleteaorderpaid', [LunchController::class, 'bubbleteaorderpaid']);
Route::post('/changeOrderOverview', [LunchController::class, 'changeOrderOverview']);
Route::post('/changeIsMealActive', [LunchController::class, 'changeIsMealActive']);
Route::post('/changeIsDrinkActive', [LunchController::class, 'changeIsDrinkActive']);
Route::post('/updateChargedSeatNumber', [LunchController::class, 'updateChargedSeatNumber']);
Route::post('/updateBubbleteaOrderURL', [LunchController::class, 'updateBubbleteaOrderURL']);
Route::post('/updateOrderType', [LunchController::class, 'updateOrderType']);
Route::post('/updateOrderRound', [LunchController::class, 'updateOrderRound']);