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
    public function getAllShops(Request $request)
    {
        try {
            // 查詢所有店家：
            $AllShops = Shops::get();

            return response()->json([
                'success' => true,
                'AllShops' => $AllShops
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getShops(Request $request)
    {
        // 取得今天的星期幾 (1-7)
        $wday = Carbon::now()->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        // $wday = 1; // 0 (Sunday) to 6 (Saturday)
        $today = Carbon::today()->toDateString();
        $data = [];

        try {
            $selectShop = ManagerControl::where('c_date',$today)
                ->where('c_title','thisday_shop_id')
                ->whereNotNull('c_value')
                ->first();
            if ($selectShop) {
                // 有指定今日店家
                $firstShops = Shops::select('shop_id', 'shop_name')
                    ->where('shop_id', $selectShop->c_value)
                    ->get();
            } else {
                // 沒指定 → 用星期店家
                $firstShops = WdayShopView::select('shop_id', 'shop_name')
                    ->where('wday', $wday)
                    ->whereNotNull('shop_id')
                    ->get();
            }

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
        $today = Carbon::today()->toDateString();

        // 取得前端送過來的訂單資料
        $order_date   = $request->input('order_date'); 
        $order_type   = $request->input('order_type'); 
        $order_round  = $request->input('order_round'); 
        $seat_number  = $request->input('seat_number'); 
        $food_id      = $request->input('food_id'); 
        $quantity     = $request->input('quantity'); 

        try {
            $default_order_round = ManagerControl::where('c_date',$today)
            ->where('c_title','order_round')
            ->first();

            $defaultRound = $default_order_round?->c_value;

            if ($defaultRound !== null && (string)$defaultRound !== (string)$order_round) {
                return response()->json([
                    'success' => false,
                    'message' => 'order_round_error',
                    'orderRound' => $defaultRound,
                ]);
            }

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
        // $order_round = $request->input('order_round'); // 使用 input() 來取得 order_round
        $data = [];

        try {
            $orders = OrdersView::select('*')
                ->where('order_date', $order_date)
                ->where('order_type', $order_type)
                ->where('delete_flag', 0)
                // ->where('order_round', $order_round)
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

    public function changeOrderOverview(Request $request)
    {
        $enabled = $request->input('enabled');
        $today = Carbon::today()->toDateString();

        try {
            $isOrderable = ManagerControl::where('c_title', 'isOrderable')->first();
            $isBubbleTeaOrderable = ManagerControl::where('c_title', 'isBubbleTeaOrderable')->first();
            $charged_seat_number = ManagerControl::where('c_title', 'charged_seat_number')->first();
            $order_type = ManagerControl::where('c_title', 'order_type')->first();
            $order_round = ManagerControl::where('c_title', 'order_round')->first();
            $bubble_tea_url = ManagerControl::where('c_title', 'bubble_tea_url')->first();
            $thisday_shop_id = ManagerControl::where('c_title', 'thisday_shop_id')->first();

            if (!$isOrderable || !$isBubbleTeaOrderable || !$charged_seat_number || !$order_type || !$order_round || !$bubble_tea_url || !$thisday_shop_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            if ($enabled === true) {
                if ($charged_seat_number instanceof ManagerControl && $charged_seat_number->c_date != $today) {
                    $charged_seat_number->c_value = '25';
                    $charged_seat_number->c_date = $today;
                    $charged_seat_number->save();
                }
                if ($order_type instanceof ManagerControl && $order_type->c_date != $today) {
                    $order_type->c_value = '1';
                    $order_type->c_date = $today;
                    $order_type->save();
                }
                if ($order_round instanceof ManagerControl &&  $order_round->c_date != $today) {
                    $order_round->c_value = '1';
                    $order_round->c_date = $today;
                    $order_round->save();
                }
                if ($bubble_tea_url instanceof ManagerControl && $bubble_tea_url->c_date != $today) {
                    $bubble_tea_url->c_value = '';
                    $bubble_tea_url->c_date = $today;
                    $bubble_tea_url->save();
                }
                if ($thisday_shop_id instanceof ManagerControl && $thisday_shop_id->c_date != $today) {
                    $wday = Carbon::now()->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
                    $shop = WdayShopView::where('wday', $wday)
                        ->whereNotNull('shop_id')
                        ->first();

                    $thisday_shop_id->c_value = $shop?->shop_id;
                    $thisday_shop_id->c_date = $today;
                    $thisday_shop_id->save();
                }
            } 

            // 轉布林
            $boolEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

            if ($isOrderable instanceof ManagerControl) {
                $isOrderable->c_date = $today;
                $isOrderable->c_value = $boolEnabled ? 'Y' : 'N';
                $isOrderable->save();
            }

            if ($isBubbleTeaOrderable instanceof ManagerControl) {
                $isBubbleTeaOrderable->c_date = $today;
                $isBubbleTeaOrderable->c_value = $boolEnabled ? 'Y' : 'N';
                $isBubbleTeaOrderable->save();
            }

            return response()->json([
                'success' => true,
                'OrderOverview' => $boolEnabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function changeIsMealActive(Request $request)
    {
        $enabled = $request->input('enabled');
        $today = Carbon::today()->toDateString();

        try {
            $isOrderable = ManagerControl::where('c_title', 'isOrderable')->first();
            $thisday_shop_id = ManagerControl::where('c_title', 'thisday_shop_id')->first();
            
            if (!$isOrderable || !$thisday_shop_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            if ($thisday_shop_id instanceof ManagerControl && $thisday_shop_id->c_date != $today) {
                $wday = Carbon::now()->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
                $shop = WdayShopView::where('wday', $wday)
                    ->whereNotNull('shop_id')
                    ->first();
                $thisday_shop_id->c_value = $shop?->shop_id;
                $thisday_shop_id->c_date = $today;
                $thisday_shop_id->save();
            }

            // 轉布林
            $boolEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

            if ($isOrderable instanceof ManagerControl) {
                $isOrderable->c_date = $today;
                $isOrderable->c_value = $boolEnabled ? 'Y' : 'N';
                $isOrderable->save();
            }

            return response()->json([
                'success' => true,
                'IsMealActive' => $boolEnabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function changeIsDrinkActive(Request $request)
    {
        $enabled = $request->input('enabled');
        $today = Carbon::today()->toDateString();

        try {
            $isBubbleTeaOrderable = ManagerControl::where('c_title', 'isBubbleTeaOrderable')->first();

            if (!$isBubbleTeaOrderable) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            // 轉布林
            $boolEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

            if ($isBubbleTeaOrderable instanceof ManagerControl) {
                $isBubbleTeaOrderable->c_date = $today;
                $isBubbleTeaOrderable->c_value = $boolEnabled ? 'Y' : 'N';
                $isBubbleTeaOrderable->save();
            }

            return response()->json([
                'success' => true,
                'IsMealActive' => $boolEnabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateThisdayshop(Request $request)
    {
        $thisdayShopId = $request->input('thisday_shop_id');
        $today = Carbon::today()->toDateString();

        try {
            $thisday_shop_id = ManagerControl::where('c_title', 'thisday_shop_id')->first();

            if (!$thisday_shop_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            if ($thisday_shop_id instanceof ManagerControl) {
                $thisday_shop_id->c_date = $today;
                $thisday_shop_id->c_value = $thisdayShopId;
                $thisday_shop_id->save();
            }

            return response()->json([
                'success' => true,
                'thisdayShopId' => $thisday_shop_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateChargedSeatNumber(Request $request)
    {
        $charged_seat_number = $request->input('charged_seat_number');
        $today = Carbon::today()->toDateString();

        try {
            $chargedSeatNumber = ManagerControl::where('c_title', 'charged_seat_number')->first();

            if (!$chargedSeatNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            if ($chargedSeatNumber instanceof ManagerControl) {
                $chargedSeatNumber->c_date = $today;
                $chargedSeatNumber->c_value = $charged_seat_number;
                $chargedSeatNumber->save();
            }

            return response()->json([
                'success' => true,
                'chargedSeatNumber' => $charged_seat_number
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateBubbleteaOrderURL(Request $request)
    {
        $bubble_tea_url = $request->input('bubble_tea_url');
        $today = Carbon::today()->toDateString();

        try {
            $bubbleTeaUrl = ManagerControl::where('c_title', 'bubble_tea_url')->first();

            if (!$bubbleTeaUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            // 🔑 如果沒有 http:// 或 https://，自動補 https://
            if (
                $bubble_tea_url !== '' &&
                !preg_match('/^https?:\/\//i', $bubble_tea_url)
            ) {
                $bubble_tea_url = 'https://' . $bubble_tea_url;
            }

            // （選擇性）驗證是否為合法 URL
            if (
                $bubble_tea_url !== '' &&
                !filter_var($bubble_tea_url, FILTER_VALIDATE_URL)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid URL format'
                ], 422);
            }

            $bubbleTeaUrl->c_date = $today;
            $bubbleTeaUrl->c_value = $bubble_tea_url;
            $bubbleTeaUrl->save();

            return response()->json([
                'success' => true,
                'bubbleTeaUrl' => $bubble_tea_url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateOrderType(Request $request)
    {
        $order_type = $request->input('order_type');
        $today = Carbon::today()->toDateString();

        try {
            $orderType = ManagerControl::where('c_title', 'order_type')->first();

            if (!$orderType) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            if ($orderType instanceof ManagerControl) {
                $orderType->c_date = $today;
                $orderType->c_value = $order_type;
                $orderType->save();
            }

            return response()->json([
                'success' => true,
                'orderType' => $order_type
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateOrderRound(Request $request)
    {
        $order_round = $request->input('order_round');
        $today = Carbon::today()->toDateString();

        try {
            $orderRound = ManagerControl::where('c_title', 'order_round')->first();

            if (!$orderRound) {
                return response()->json([
                    'success' => false,
                    'message' => 'item not found'
                ], 404);
            }

            if ($orderRound instanceof ManagerControl) {
                $orderRound->c_date = $today;
                $orderRound->c_value = $order_round;
                $orderRound->save();
            }

            return response()->json([
                'success' => true,
                'orderRound' => $order_round
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }



    public function GetAlloders(Request $request)
    {
        try {
            $orders = OrdersView::get();
        
            return response()->json([
                'success' => true,
                'Allorders' => $orders,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
}
