<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Access;

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
            'email' => 'required|email|unique:access,email',
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
        $exists = Access::where('email', $request->email)->exists();
        if ($exists) {
            return response()->json([
                'message' => '此 Email 已被註冊'
            ], 400);
        }
        
        $access = Access::create([
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

        $access = Access::where('email', $request->email)->first();

        if (!$access || !Hash::check($request->password, $access->password)) {
            return response()->json([
                'message' => '帳號或密碼錯誤'
            ], 401);
        }

        // 5️⃣ 登入（使用 Sanctum session）
        Auth::login($access, $request->remember ?? false);

        return response()->json([
            'message' => '登入成功',
            'access_id' => $access->access_id
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => '已登出'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
