<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Models\Users;
use App\Models\AuthUsers;
use Carbon\Carbon;

class AuthController extends Controller
{
    // 🔥 發送驗證碼
    public function sendCode(Request $request)
    {
        $code = rand(100000, 999999);
        // 存 5 分鐘
        Cache::put('register_code_'.$request->email, $code, 300);

        Mail::raw("你的驗證碼是：$code", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('TCNR 驗證碼');
        });

        return response()->json([
            'message' => '驗證碼已寄出'
        ]);
    }


    // 🔥 註冊
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:auth_users,email',
            'password' => 'required|min:6',
            'code' => 'required'
        ], [
            'email.unique' => '此 Email 已被註冊',
        ]);

        $cacheCode = Cache::get('register_code_'.$request->email);

        if ($cacheCode != $request->code) {
            return response()->json([
                'message' => '驗證碼錯誤'
            ], 400);
        }

        // 🔥 先檢查 email 是否已存在
        $exists = AuthUsers::where('email', $request->email)->exists();
        if ($exists) {
            return response()->json([
                'message' => '此 Email 已被註冊'
            ], 400);
        }
        
        $authuser = AuthUsers::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Cache::forget('register_code_'.$request->email);

        return response()->json([
            'message' => '註冊成功'
        ]);
    }
    // 登入
    public function login(Request $request)
    {
        // 1️⃣ 驗證欄位
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'captcha' => 'required|captcha'
        ]);

        $authuser = AuthUsers::where('email', $request->email)->first();

        if (!$authuser || !Hash::check($request->password, $authuser->password)) {
            return response()->json([
                'message' => '帳號或密碼錯誤'
            ], 401);
        }

        // 5️⃣ 登入（使用 Sanctum session）
        Auth::login($authuser, $request->remember ?? false);

        return response()->json([
            'message' => '登入成功',
            'authuser_id' => $authuser->id
        ]);
    }

    public function logout(Request $request)
    {
        if ($user = $request->user()) {
            // 刪除該用戶的所有 session token (若你有使用 token)
            $user->tokens()->delete();
        }

        // 清掉 session
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => '已登出']);
    }

    // public function user(Request $request)
    // {
    //     $access = $request->user(); // Access model

    //     if (!$access) {
    //         return response()->json(['error' => 'Unauthenticated'], 401);
    //     }

    //     $user = $access->user;
    //     $user->load('roles');

    //     return response()->json([
    //         'email' => $access->email,
    //         'seat_number' => $user->seat_number,
    //         'user_name' => $user->user_name,
    //         'roles' => $user->roles->pluck('role_name'),
    //     ]);
    // }

    public function user(Request $request)
    {
        $auth = $request->user(); // AuthUser
        $user = $auth->user;
        $user->load('roles');
    
        return response()->json([
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'seat_number' => $user->seat_number,
                'roles' => $user->roles->pluck('role_name'),
            ],
            'auth' => [
                'provider' => $auth->provider,
                'email' => $auth->email,
            ]
        ]);
    }

    public function getUserIP(Request $request)
    {
        // 檢查是否有 X-Forwarded-For 標頭
        $xForwardedFor = $request->header('X-Forwarded-For');

        // 使用 X-Forwarded-For（如果有），否則使用默認 IP
        $userIP = $xForwardedFor ? explode(',', $xForwardedFor)[0] : $request->ip();
        
        $today = Carbon::now();
        $todayFormatted = [
            'date' => $today->toDateString(),  // 格式化為 YYYY-MM-DD
            'day' => $today->locale('zh_TW')->dayName  // 使用中文星期幾
        ];
    
        return response()->json([
            'user_ip' => $userIP,
            'today' => $todayFormatted
        ]);
    }
}
