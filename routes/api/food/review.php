<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);

Route::get('/review-summary', [ShopReviewController::class, 'reviewSummary']);