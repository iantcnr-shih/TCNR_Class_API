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
     * GET /api/reviews?shop_id=1&type=shop      -> 店家評論（food_id IS NULL）
     * GET /api/reviews?shop_id=1&type=food      -> 餐點評論（food_id IS NOT NULL）
     */
    public function index(Request $request)
    {
        $shopId = $request->query('shop_id');
        $foodId = $request->query('food_id'); // 可能是 null / 'null' / '' / '3'
        $type   = $request->query('type');    // shop | food | null

        if (!$shopId) {
            return response()->json(['message' => 'shop_id is required'], 422);
        }

        // normalize
        $type = $type ? strtolower(trim($type)) : null;
        if ($request->has('food_id') && $foodId === '') {
            $foodId = null;
        }

        $query = DB::table('reviews as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('shops as s', 's.id', '=', 'r.shop_id')
            ->leftJoin('foods as f', 'f.id', '=', 'r.food_id')
            ->where('r.shop_id', $shopId)
            ->select([
                'r.id as review_id',
                'r.shop_id',
                's.shop_name',
                'r.food_id',
                DB::raw('f.food_name as food_name'),
                'r.user_id',
                'u.user_name',
                'r.rating',
                'r.comment',
                'r.created_at',
                'r.updated_at',
            ])
            ->orderByDesc('r.created_at');

        /**
         * 過濾優先順序：
         * 1) food_id（指定餐點 or 指定店家評價）
         * 2) type（shop/food）
         * 3) 都沒有 -> 全部
         */
        if ($request->has('food_id')) {
            // 相容 ?food_id=null 代表店家評價
            if ($foodId === null || $foodId === 'null') {
                $query->whereNull('r.food_id');
            } else {
                $foodIdInt = (int) $foodId;
                if ($foodIdInt <= 0) {
                    return response()->json(['message' => 'food_id must be a positive integer or null'], 422);
                }
                $query->where('r.food_id', $foodIdInt);
            }
        } else if ($type) {
            if ($type === 'shop') {
                $query->whereNull('r.food_id');
            } else if ($type === 'food') {
                $query->whereNotNull('r.food_id');
            } else {
                return response()->json(['message' => 'type must be shop or food'], 422);
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
            $payload = $request->only(['shop_id', 'food_id', 'user_id', 'rating', 'comment']);

            $newId = DB::table('reviews')->insertGetId([
                'shop_id' => $payload['shop_id'],
                'food_id' => $payload['food_id'] ?? null,
                'user_id' => $payload['user_id'],
                'rating'  => $payload['rating'],
                'comment' => $payload['comment'] ?? null,
            ]);

            // 回傳含 user_name / food_name / shop_name
            $review = DB::table('reviews as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->join('shops as s', 's.id', '=', 'r.shop_id')
                ->leftJoin('foods as f', 'f.id', '=', 'r.food_id')
                ->where('r.id', $newId)
                ->select([
                    'r.id as review_id',
                    'r.shop_id',
                    's.shop_name',
                    'r.food_id',
                    DB::raw('f.food_name as food_name'),
                    'r.user_id',
                    'u.user_name',
                    'r.rating',
                    'r.comment',
                    'r.created_at',
                    'r.updated_at',
                ])
                ->first();

            return response()->json(['data' => $review], 201);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Duplicate review (unique constraint: uk_user_shop_food)'
                ], 409);
            }
            throw $e;
        }
    }
}
