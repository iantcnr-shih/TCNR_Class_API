<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->query('shop_id');
        $foodId = $request->query('food_id');
        // 可為 null

        if (!$shopId) {
            return response()->json([
                'message' => 'shop_id is required'
            ], 422);
        }

        $query = Review::query()
        ->where('shop_is', $shopId)
        ->with([
            'user:user_id,user_name',
            'food:food_id,food_name',
        ])
        ->orderByDesc('created_at');
        // 若 food_id 有帶：查該餐點評論
        // 若 food_id 沒帶：回傳該店全部（含店家+餐點）
        if ($request->has('food_id')) {
            if (is_null($foodId) || $foodId === 'null') {
                $query->whereNull('food_id'); 
                // 店家評論
            } else {
                $query ->where('food_id', $foodId);
                // 餐點評論
            }
        }

        $reviews = $query->get()->map(function ($r) {
            return [
                'review_id' => $r->review_id,
                'shop_id'   => $r->shop_id,
                'food_id'   => $r->food_id, 
                'user_id'   => $r->user_id,
                'rating'    => $r->rating,
                'comment'   => $r->comment,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
                'user_name' => optional($r->user)->user_name,
                'food_name' => optional($r->food)->food_name,
            ];
        });

        return response()->json([
            'data' => $reviews
        ]);
    }
}