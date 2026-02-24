<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreReviewRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * GET /api/reviews?shop_id=1
     * GET /api/reviews?shop_id=1&food_id=null   -> 店家評論（food_id IS NULL）
     * GET /api/reviews?shop_id=1&food_id=3      -> 餐點評論（food_id = 3）
     */
    public function index(Request $request)
    {
        $shopId = $request->query('shop_id');
        $foodId = $request->query('food_id'); // 可能是 null / 'null' / '' / '3'

        if (!$shopId) {
            return response()->json(['message' => 'shop_id is required'], 422);
        }

        $query = DB::table('reviews as r')
            ->join('users as u', 'u.user_id', '=', 'r.user_id')
            ->leftJoin('foods as f', 'f.food_id', '=', 'r.food_id')
            ->where('r.shop_id', $shopId)
            ->select([
                'r.review_id',
                'r.shop_id',
                'r.food_id',
                'r.user_id',
                'r.rating',
                'r.comment',
                'r.created_at',
                'r.updated_at',
                'u.user_name',
                DB::raw('f.food_name as food_name'),
            ])
            ->orderByDesc('r.created_at');

        // 有帶 food_id 參數才過濾（相容 ?food_id=null / ?food_id= / ?food_id=3）
        if ($request->has('food_id')) {
            if ($foodId === null || $foodId === '' || $foodId === 'null') {
                $query->whereNull('r.food_id');      // 店家評論
            } else {
                $query->where('r.food_id', $foodId); // 餐點評論
            }
        }

        $reviews = $query->get();

        return response()->json(['data' => $reviews]);
    }

    /**
     * POST /api/reviews
     * body: { shop_id, food_id(null), user_id, rating(1~5), comment }
     */
    public function store(StoreReviewRequest $request)
    {
        try {
            // 只取需要寫入 reviews 的欄位
            $payload = $request->only(['shop_id', 'food_id', 'user_id', 'rating', 'comment']);

            // reviews.created_at 預設 current_timestamp()
            $newId = DB::table('reviews')->insertGetId([
                'shop_id' => $payload['shop_id'],
                'food_id' => $payload['food_id'] ?? null,
                'user_id' => $payload['user_id'],
                'rating'  => $payload['rating'],
                'comment' => $payload['comment'] ?? null,
                // 不手動塞 created_at/updated_at，交給 DB 預設
            ]);

            // 再查回含 user_name / food_name 的完整資料
            $review = DB::table('reviews as r')
                ->join('users as u', 'u.user_id', '=', 'r.user_id')
                ->leftJoin('foods as f', 'f.food_id', '=', 'r.food_id')
                ->where('r.review_id', $newId)
                ->select([
                    'r.review_id',
                    'r.shop_id',
                    'r.food_id',
                    'r.user_id',
                    'r.rating',
                    'r.comment',
                    'r.created_at',
                    'r.updated_at',
                    'u.user_name',
                    DB::raw('f.food_name as food_name'),
                ])
                ->first();

            return response()->json(['data' => $review], 201);
        } catch (QueryException $e) {
            // uk_user_shop_food 重複會進來（MySQL: 23000）
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Duplicate review (unique constraint: uk_user_shop_food)'
                ], 409);
            }
            throw $e;
        }
    }
}
