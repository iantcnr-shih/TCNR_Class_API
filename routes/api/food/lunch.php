<?php

use App\Http\Controllers\LunchController;
use Illuminate\Support\Facades\Route;

Route::get('/getAllShops', [LunchController::class, 'getAllShops']);
Route::get('/getShops', [LunchController::class, 'getShops']);
Route::get('/GetAllcategories', [LunchController::class, 'GetAllcategories']);
Route::get('/getCategories', [LunchController::class, 'getCategories']);
Route::get('/GetAllfoods', [LunchController::class, 'GetAllfoods']);
Route::get('/getFoods', [LunchController::class, 'getFoods']);
Route::post('/addorder', [LunchController::class, 'addorder']);
Route::get('/getOrders', [LunchController::class, 'getOrders']);
Route::post('/orderpaid', [LunchController::class, 'orderpaid']);
Route::middleware(['auth:sanctum'])->get('/getUserHistoryOrders', [LunchController::class, 'getUserHistoryOrders']);
Route::middleware(['auth:sanctum'])->get('/getUserHistoryBubbleteaOrders', [LunchController::class, 'getUserHistoryBubbleteaOrders']);
Route::get('/getManagerControl', [LunchController::class, 'getManagerControl']);
Route::post('/addbubbleteaorder', [LunchController::class, 'addbubbleteaorder']);
Route::get('/getBubbleteaorders', [LunchController::class, 'getBubbleteaorders']);
Route::post('/bubbleteaorderpaid', [LunchController::class, 'bubbleteaorderpaid']);
Route::post('/changeOrderOverview', [LunchController::class, 'changeOrderOverview']);
Route::post('/changeIsMealActive', [LunchController::class, 'changeIsMealActive']);
Route::post('/changeIsDrinkActive', [LunchController::class, 'changeIsDrinkActive']);
Route::post('/updateThisdayshop', [LunchController::class, 'updateThisdayshop']);
Route::post('/updateChargedSeatNumber', [LunchController::class, 'updateChargedSeatNumber']);
Route::post('/updateBubbleteaOrderURL', [LunchController::class, 'updateBubbleteaOrderURL']);
Route::post('/updateOrderType', [LunchController::class, 'updateOrderType']);
Route::post('/updateOrderRound', [LunchController::class, 'updateOrderRound']);

Route::get('/GetAlloders', [LunchController::class, 'GetAlloders']);
Route::get('/GetAllbubbleteaorders', [LunchController::class, 'GetAllbubbleteaorders']);

Route::post('/changeIsShopActive', [LunchController::class, 'changeIsShopActive']);
Route::post('/changeIsCategoryActive', [LunchController::class, 'changeIsCategoryActive']);
Route::post('/changeIsFoodActive', [LunchController::class, 'changeIsFoodActive']);

Route::post('/addShop', [LunchController::class, 'addShop']);
Route::post('/addCategory', [LunchController::class, 'addCategory']);
Route::post('/addFood', [LunchController::class, 'addFood']);



Route::post('/updateShop', [LunchController::class, 'updateShop']);
Route::post('/updateCategory', [LunchController::class, 'updateCategory']);
Route::post('/updateFood', [LunchController::class, 'updateFood']);

Route::post('/deleteShop', [LunchController::class, 'deleteShop']);
Route::post('/deleteCategory', [LunchController::class, 'deleteCategory']);
Route::post('/deleteFood', [LunchController::class, 'deleteFood']);

Route::post('/updateOrder', [LunchController::class, 'updateOrder']);
Route::post('/deleteOrder', [LunchController::class, 'deleteOrder']);

Route::post('/updateBubbleteaOrder', [LunchController::class, 'updateBubbleteaOrder']);
Route::post('/deleteBubbleteaOrder', [LunchController::class, 'deleteBubbleteaOrder']);


Route::get('/GetWdayShops', [LunchController::class, 'GetWdayShops']);
Route::post('/updateWdayShops', [LunchController::class, 'updateWdayShops']);



