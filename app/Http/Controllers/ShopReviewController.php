<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopReviewController extends Controller

{
    /**
     * GET /api/review-summary
     */
    public function reviewSummary()
    {
        $shops = DB::table('shops as s')
            ->leftJoin('reviews as r', 'r.shop_id', '=', 's.shop_id')
            ->select([
                's.shop_id',
                's.shop_name',
                's.shop_url',

                // 全部評論
                DB::raw('ROUND(AVG(r.rating), 2) as avg_rating_all'),
                DB::raw('COUNT(r.review_id) as review_count_all'),

                // 店家評價（food_id IS NULL）
                DB::raw('ROUND(AVG(CASE WHEN r.food_id IS NULL THEN r.rating END), 2) as avg_shop_rating'),
                DB::raw('SUM(CASE WHEN r.food_id IS NULL THEN 1 ELSE 0 END) as shop_review_count'),

                // 餐點評價（food_id IS NOT NULL）
                DB::raw('ROUND(AVG(CASE WHEN r.food_id IS NOT NULL THEN r.rating END), 2) as avg_food_rating'),
                DB::raw('SUM(CASE WHEN r.food_id IS NULL THEN 1 ELSE 0 END) as food_review_count'),
            ])
            ->groupBy('s.shop_id', 's.shop_name', 's.shop_url')
            ->orderBy(DB::raw('avg_rating_all IS NULL')) // 無評論排最後
            ->orderByDesc('avg_rating_all')
            ->get();

        return response()->json(['data' => $shops]);
    }
}

