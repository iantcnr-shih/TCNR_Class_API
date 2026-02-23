<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\WdayShopView;
use App\Models\Shops;
use App\Models\MenuCategories;
use App\Models\Foods;
use App\Models\Orders;
use App\Models\BubbleteaOrders;
use App\Models\OrdersView;
use App\Models\ManagerControl;
use Illuminate\Support\Facades\Log;

class LunchController extends Controller
{
    public function getShops(Request $request)
    {
        // 取得今天的星期幾 (1-7)
        // $wday = Carbon::now()->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $wday = 1; // 0 (Sunday) to 6 (Saturday)
        $data = [];

        try {
            // 第一個查詢：wday_shop_view
            $firstShops = WdayShopView::select('shop_id', 'shop_name')
                ->where('wday', $wday)
                ->whereNotNull('shop_id')
                ->get();

            $data = $firstShops->isEmpty() ? [] : $firstShops->toArray();

            // 第二個查詢：固定 shop_url
            $secondShops = Shops::select('shop_id', 'shop_name')
                ->whereIn('shop_url', ['jiaxiang', 'zhongxing'])
                ->get();

            // 合併兩筆資料
            $data = array_merge($data, $secondShops->toArray());

            return response()->json([
                'success' => true,
                'shops' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getCategories(Request $request)
    {
        // 取得 shop_id
        $shop_id = $request->input('shop_id'); // 使用 input() 來取得 shop_id
        $data = [];

        try {
            $categories = MenuCategories::select('*')
                ->where('shop_id', $shop_id)
                ->get();

            $data = $categories->toArray();

            return response()->json([
                'success' => true,
                'categories' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFoods(Request $request)
    {
        // 取得 category_id
        $menu_category_id = $request->input('menu_category_id'); // 使用 input() 來取得 shop_id
        $data = [];

        try {
            $foods = Foods::select('*')
                ->where('menu_category_id', $menu_category_id)
                ->get();

            $data = $foods->toArray();

            return response()->json([
                'success' => true,
                'foods' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addorder(Request $request)
    {
        // 取得用戶 IP
        $xForwardedFor = $request->header('X-Forwarded-For');
        $user_ip = $xForwardedFor ? explode(',', $xForwardedFor)[0] : $request->ip();

        // 取得前端送過來的訂單資料
        $order_date   = $request->input('order_date'); 
        $order_type   = $request->input('order_type'); 
        $order_round  = $request->input('order_round'); 
        $seat_number  = $request->input('seat_number'); 
        $food_id      = $request->input('food_id'); 
        $quantity     = $request->input('quantity'); 

        try {
            // 使用 Eloquent create() 插入資料
            $order = Orders::create([
                'order_date'  => $order_date,
                'order_type'  => $order_type,
                'order_round' => $order_round,
                'seat_number' => $seat_number,
                'food_id'     => $food_id,
                'quantity'    => $quantity,
                'user_ip'     => $user_ip,
                'is_paid'     => 0, // 預設未付款
                'delete_flag' => 0, // 預設未刪除
            ]);

            return response()->json([
                'success' => true,
                'order'   => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getOrders(Request $request)
    {
        $user_ip = $request->ip();
        // 取 IP 最後一段
        $ip_parts = explode('.', $user_ip);
        $last_digit = intval(end($ip_parts));

        // user_no = 最後一碼 - 1
        $user_no = $last_digit - 1;
        $seat_number = $request->input('seat_number') ?? $user_no; // 使用 input() 來取得 seat_number
        $order_date = $request->input('order_date') ?? Carbon::today()->toDateString(); // 使用 input() 來取得 order_date
        $order_type = $request->input('order_type'); // 使用 input() 來取得 order_type
        $order_round = $request->input('order_round'); // 使用 input() 來取得 order_round
        $data = [];

        try {
            $orders = OrdersView::select('*')
                ->where('order_date', $order_date)
                ->where('order_type', $order_type)
                ->where('order_round', $order_round)
                ->get();

            $data = $orders->toArray();

            // 過濾出指定座位號的訂單
             $userOrders = $seat_number ? $orders->where('seat_number', $seat_number)->values() : $data;

            return response()->json([
                'success' => true,
                'orders' => $data,
                'user_orders' => $userOrders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function orderpaid(Request $request)
    {
        // 取得前端送過來的訂單資料
        $order_id = $request->input('order_id'); 
        $is_paid = $request->input('is_paid'); 
        try {
            $order = Orders::where('order_id', $order_id)->first();
            // 如果找不到該訂單，返回錯誤
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // 更新 is_paid 狀態
            $order->is_paid = $is_paid;
            $order->save();  // 儲存更新的資料

            return response()->json([
                'success' => true,
                'order'   => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getManagerControl(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $data = [];

        try {
            $controls = ManagerControl::select('*')
                ->where('c_date',$today)
                ->get();

            $data = $controls->toArray();

            return response()->json([
                'success' => true,
                'controls' => $controls
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addbubbleteaorder(Request $request)
    {
        // 取得用戶 IP
        $xForwardedFor = $request->header('X-Forwarded-For');
        $user_ip = $xForwardedFor ? explode(',', $xForwardedFor)[0] : $request->ip();

        // 取得前端送過來的訂單資料
        $order_date = $request->input('order_date'); 
        $seat_number = $request->input('seat_number'); 
        $bubbletea_name = $request->input('bubbletea_name'); 
        $bubbletea_price     = $request->input('bubbletea_price'); 

        try {
            // 使用 Eloquent create() 插入資料
            $bubbleteaorder = BubbleteaOrders::create([
                'order_date'  => $order_date,
                'seat_number' => $seat_number,
                'bubbletea_name'     => $bubbletea_name,
                'bubbletea_price'    => $bubbletea_price,
                'user_ip'     => $user_ip,
                'is_paid'     => 0, // 預設未付款
                'delete_flag' => 0, // 預設未刪除
            ]);

            return response()->json([
                'success' => true,
                'bubbleteaorder'   => $bubbleteaorder
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getBubbleteaorders(Request $request)
    {
        $user_ip = $request->ip();
        // 取 IP 最後一段
        $ip_parts = explode('.', $user_ip);
        $last_digit = intval(end($ip_parts));

        // user_no = 最後一碼 - 1
        $user_no = $last_digit - 1;
        $seat_number = $request->input('seat_number') ?? $user_no; // 使用 input() 來取得 seat_number
        $order_date = $request->input('order_date') ?? Carbon::today()->toDateString(); // 使用 input() 來取得 order_date
        $data = [];

        try {
            $bubbleteaorders = BubbleteaOrders::select('*')
                ->where('order_date', $order_date)
                ->get();

            $data = $bubbleteaorders->toArray();

            // 過濾出指定座位號的訂單
             $userBubbleteaOrders = $seat_number ? $bubbleteaorders->where('seat_number', $seat_number)->values() : $data;

            return response()->json([
                'success' => true,
                'bubbletea_orders' => $data,
                'user_bubbletea_orders' => $userBubbleteaOrders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bubbleteaorderpaid(Request $request)
    {
        // 取得前端送過來的訂單資料
        $bubbletea_order_id = $request->input('bubbletea_order_id'); 
        $is_paid = $request->input('is_paid'); 
        try {
            $bubbleteaorder = BubbleteaOrders::where('bubbletea_order_id', $bubbletea_order_id)->first();
            // 如果找不到該訂單，返回錯誤
            if (!$bubbleteaorder) {
                return response()->json([
                    'success' => false,
                    'message' => 'bubbleteaorder not found'
                ], 404);
            }

            // 更新 is_paid 狀態
            $bubbleteaorder->is_paid = $is_paid;
            $bubbleteaorder->save();  // 儲存更新的資料

            return response()->json([
                'success' => true,
                'bubbleteaorder'   => $bubbleteaorder
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
}
