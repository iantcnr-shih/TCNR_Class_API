<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            $AllShops = Shops::select('id as shop_id', 'shop_name', 'shop_phone', 'shop_url', 'remark', 'is_active', 'delete_flag')
                ->get();

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
                $firstShops = Shops::select('id as shop_id', 'shop_name', 'is_active')
                    ->where('id', $selectShop->c_value)
                    ->where('is_active', 1)
                    ->where('delete_flag', 0)
                    ->get();
            } else {
                // 沒指定 → 用星期店家W
                $firstShops = WdayShopView::select('shop_id', 'shop_name')
                    ->where('wday', $wday)
                    ->whereNotNull('shop_id')
                    ->where('is_active', 1)
                    ->where('delete_flag', 0)
                    ->get();
            }

            $data = $firstShops->isEmpty() ? [] : $firstShops->toArray();

            // 第二個查詢：固定 shop_url
            $secondShops = Shops::select('id as shop_id', 'shop_name', 'is_active')
                ->whereIn('shop_url', ['jiaxiang', 'zhongxing'])
                ->where('is_active', 1)
                ->where('delete_flag', 0)
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

    public function GetAllcategories(Request $request)
    {
        try {
            // 查詢所有餐點樣式：
            $Allcategories = MenuCategories::select('id as menu_category_id','shop_id', 'category_name', 'is_active', 'delete_flag')
                ->get();

            return response()->json([
                'success' => true,
                'Allcategories' => $Allcategories
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
            $categories = MenuCategories::select('id as menu_category_id','shop_id', 'category_name', 'is_active')
                // ->where('is_active', 1)
                ->where('delete_flag', 0)
                ->where('shop_id', $shop_id)
                ->orderBy('id', 'asc') // 依 id 升序排列
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

    public function GetAllfoods(Request $request)
    {
        try {
            // 查詢所有餐點樣式：
            $Allfoods =Foods::select('id as food_id', 'menu_category_id', 'food_name', 'price', 'is_active', 'delete_flag')
                ->get();

            return response()->json([
                'success' => true,
                'Allfoods' => $Allfoods
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
            $foods = Foods::select('id as food_id', 'menu_category_id', 'food_name', 'price', 'is_active')
                ->where('menu_category_id', $menu_category_id)
                // ->where('is_active', 1)
                ->where('delete_flag', 0)
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
        $user_id  = $request->input('user_id'); 
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
                'user_id' => $user_id,
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
        // user_no = 最後一碼 - 1
        $user_id = $request->input('user_id'); // 使用 input() 來取得 seat_number
        $order_date = $request->input('order_date') ?? Carbon::today()->toDateString(); // 使用 input() 來取得 order_date
        $order_type = $request->input('order_type'); // 使用 input() 來取得 order_type
        // $order_round = $request->input('order_round'); // 使用 input() 來取得 order_round
        $data = [];

        try {
            $orders = OrdersView::where('order_date', $order_date)
                ->where('order_type', $order_type)
                ->where('delete_flag', 0)
                // ->where('order_round', $order_round)
                ->get();

            $data = $orders->toArray();

            // 過濾出指定座位號的訂單
             $userOrders = $user_id ? $orders->where('user_id', $user_id)->values() : $data;

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
        $order_id = (int) $request->input('order_id');
        $is_paid = $request->input('is_paid'); 
        try {
            $order = Orders::find($order_id);
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

    public function getUserHistoryOrders(Request $request)
    {
        $user_id = intval($request->input('user_id'));
        $data = [];

        try {
            $orders = OrdersView::where('user_id', $user_id)
                ->get();

            $data = $orders->toArray();

            return response()->json([
                'success' => true,
                'user_orders' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserHistoryBubbleteaOrders(Request $request)
    {
        $user_id = intval($request->input('user_id'));
        $data = [];

        try {
            $orders = BubbleteaOrders::select(
                'bubbletea_orders.order_date',
                'bubbletea_orders.user_id',
                'bubbletea_orders.bubbletea_name',
                'bubbletea_orders.bubbletea_price',
                'bubbletea_orders.is_paid',
                'bubbletea_orders.delete_flag',
                'users.seat_number'
            )
            ->leftJoin('users', 'bubbletea_orders.user_id', '=', 'users.id')
            ->where('bubbletea_orders.user_id', $user_id)
            ->get();

            $data = $orders->toArray();

            return response()->json([
                'success' => true,
                'user_orders' => $data
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
            $controls = ManagerControl::where('c_date',$today)
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
        $user_id = $request->input('user_id'); 
        $bubbletea_name = $request->input('bubbletea_name'); 
        $bubbletea_price     = $request->input('bubbletea_price'); 

        try {
            // 使用 Eloquent create() 插入資料
            $bubbleteaorder = BubbleteaOrders::create([
                'order_date'  => $order_date,
                'user_id' => $user_id,
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
        $user_id = $request->input('user_id'); // 使用 input() 來取得 seat_number
        $order_date = $request->input('order_date') ?? Carbon::today()->toDateString(); // 使用 input() 來取得 order_date
        $data = [];

        try {
            $bubbleteaorders = BubbleteaOrders::select(
                'bubbletea_orders.id as bubbletea_order_id',
                'bubbletea_orders.order_date',
                'bubbletea_orders.user_id',
                'bubbletea_orders.bubbletea_name',
                'bubbletea_orders.bubbletea_price',
                'bubbletea_orders.is_paid',
                'bubbletea_orders.delete_flag',
                'users.seat_number'
            )
            ->leftJoin('users', 'bubbletea_orders.user_id', '=', 'users.id')
            ->where('bubbletea_orders.order_date', $order_date)
            ->get();

            $data = $bubbleteaorders->toArray();

            // 過濾出指定座位號的訂單
             $userBubbleteaOrders = $user_id ? $bubbleteaorders->where('user_id', $user_id)->values() : $data;

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
        $bubbletea_order_id = (int) $request->input('bubbletea_order_id'); 
        $is_paid = $request->input('is_paid'); 
        try {
            $bubbleteaorder = BubbleteaOrders::find($bubbletea_order_id);
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

                    if ($shop) {
                        $thisday_shop_id->c_value = $shop->shop_id;
                    } else {
                        $thisday_shop_id->c_value = 0; // 或者其他預設值
                    }
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
                if ($shop) {
                    $thisday_shop_id->c_value = $shop->shop_id;
                } else {
                    $thisday_shop_id->c_value = 0; // 或者其他預設值
                }
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

    public function changeIsShopActive(Request $request)
    {
        $shop_id = $request->input('shop_id');
        $enabled = $request->input('enabled');

        try {
            $shop = Shops::where('id', $shop_id)->first();
            
            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'shop not found'
                ], 404);
            }

            // 轉布林
            $boolEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

            $shop->is_active = $boolEnabled;
            $shop->save();

            return response()->json([
                'success' => true,
                'IsShopActive' => $boolEnabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function changeIsCategoryActive(Request $request)
    {
        $menu_category_id = $request->input('menu_category_id');
        $enabled = $request->input('enabled');

        try {
            $category = MenuCategories::where('id', $menu_category_id)->first();
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'category not found'
                ], 404);
            }

            // 轉布林
            $boolEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

            $category->is_active = $boolEnabled;
            $category->save();

            return response()->json([
                'success' => true,
                'IsCategoryActive' => $boolEnabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }
    
    public function changeIsFoodActive(Request $request)
    {
        $food_id = $request->input('food_id');
        $enabled = $request->input('enabled');

        try {
            $food = Foods::where('id', $food_id)->first();
            
            if (!$food) {
                return response()->json([
                    'success' => false,
                    'message' => 'food not found'
                ], 404);
            }

            // 轉布林
            $boolEnabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

            $food->is_active = $boolEnabled;
            $food->save();

            return response()->json([
                'success' => true,
                'IsFoodActive' => $boolEnabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }
    
    public function addShop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_name' => 'required|unique:shops,shop_name',
            'shop_phone' => 'nullable',
            'remark' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'shop_name already exists'
            ], 400);
        }

        try {
            $shop = new Shops();
            $shop->shop_name = $request->shop_name;
            $shop->shop_phone = $request->shop_phone;
            $shop->remark = $request->remark;
            $shop->is_active = 1;
            $shop->delete_flag = 0;

            $shop->save();

            return response()->json([
                'success' => true,
                'shop' => $shop
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateShop(Request $request)
    {
        // 先抓 shop_id
        $shop_id = $request->shop_id;

        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id', // 確保要更新的店家存在
            'shop_name' => 'required|unique:shops,shop_name,' . $request->shop_id . ',id',
            'shop_phone' => 'nullable',
            'remark' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            // 抓到現有店家
            $shop = Shops::where('id', $shop_id)->first();

            $shop->shop_name = $request->shop_name;
            $shop->shop_phone = $request->shop_phone;
            $shop->remark = $request->remark;

            $shop->save();

            return response()->json([
                'success' => true,
                'shop' => $shop
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function deleteShop(Request $request)
    {
        $shop_id = $request->input('shop_id');

        try {
            $shop = Shops::where('id', $shop_id)->first();
            
            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'shop not found'
                ], 404);
            }

            $shop->delete_flag = 1;
            $shop->save();

            return response()->json([
                'success' => true,
                'deleteShopID' => $shop_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function addCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'category_name' => [
                'required',
                Rule::unique('menu_categories')->where(fn($query) => 
                    $query->where('shop_id', $request->shop_id)
                ),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'category_name already exists'
            ], 400);
        }

        try {
            $category = MenuCategories::create([
                'shop_id' => $request->shop_id,
                'category_name' => $request->category_name,
                'is_active' => 1,
                'delete_flag' => 0
            ]);

            return response()->json([
                'success' => true,
                'category' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateCategory(Request $request)
    {
        // 先抓 shop_id
        $shop_id = $request->shop_id;
        $menu_category_id = $request->menu_category_id;

        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'menu_category_id' => 'required|exists:menu_categories,id',
            'category_name' => [
                'required',
                Rule::unique('menu_categories')
                    ->where(fn ($query) => $query->where('shop_id', $request->shop_id))
                    ->ignore($request->menu_category_id, 'id'),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            // 抓到現有店家
            $category = MenuCategories::where('id', $menu_category_id)->first();

            $category->shop_id = $request->shop_id;
            $category->category_name = $request->category_name;

            $category->save();

            return response()->json([
                'success' => true,
                'category' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function deleteCategory(Request $request)
    {
        $menu_category_id = $request->input('menu_category_id');

        try {
            $category = MenuCategories::where('id', $menu_category_id)->first();
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'category not found'
                ], 404);
            }

            $category->delete_flag = 1;
            $category->save();

            return response()->json([
                'success' => true,
                'MenuCategoryID' => $menu_category_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function addFood(Request $request)
    {
        Log::debug($request->all());
        $validator = Validator::make($request->all(), [
            'menu_category_id' => 'required|exists:menu_categories,id',
            'food_name' => [
                'required',
                Rule::unique('foods')->where(fn($query) => 
                    $query->where('menu_category_id', $request->menu_category_id)
                ),
            ],
            'price' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'food_name already exists'
            ], 400);
        }

        try {
            $food = Foods::create([
                'menu_category_id' => $request->menu_category_id,
                'food_name' => $request->food_name,
                'price' => $request->price,
                'is_active' => 1,
                'delete_flag' => 0
            ]);

            return response()->json([
                'success' => true,
                'food' => $food
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function updateFood(Request $request)
    {
        // 先抓 menu_ca_id
        $menu_category_id = $request->menu_category_id;
        $food_id = $request->food_id;

        $validator = Validator::make($request->all(), [
            'menu_category_id' => 'required|exists:menu_categories,id',
            'food_id' => 'required|exists:foods,id',
            'food_name' => [
                'required',
                Rule::unique('foods')
                    ->where(fn ($query) => $query->where('menu_category_id', $request->menu_category_id))
                    ->ignore($request->food_id, 'id'),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            // 抓到現有店家
            $food = Foods::where('id', $food_id)->first();

            $food->menu_category_id = $request->menu_category_id;
            $food->food_name = $request->food_name;
            $food->price = $request->price;

            $food->save();

            return response()->json([
                'success' => true,
                'food' => $food
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }

    public function deleteFood(Request $request)
    {
        $food_id = $request->input('food_id');

        try {
            $food = Foods::where('id', $food_id)->first();
            
            if (!$food) {
                return response()->json([
                    'success' => false,
                    'message' => 'food not found'
                ], 404);
            }

            $food->delete_flag = 1;
            $food->save();

            return response()->json([
                'success' => true,
                'foodID' => $food
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error, check log'
            ], 500);
        }
    }
}
