<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Models\Users;
use App\Models\UserRoles;
use App\Models\AuthUsers;
use App\Models\UsersView;
use App\Models\Skills;
use App\Models\Positions;
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
            'password' => 'required|min:4',
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

    // 🔐 忘記密碼 - 發送驗證碼
    public function sendForgotPasswordCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'g-recaptcha-response' => 'required|captcha', // <- 直接用 captcha 驗證
        ]);

        // 1️⃣ 使用者必須存在
        $user = AuthUsers::where('email', $request->email)->first();
        if ($user) {
            $code = random_int(100000, 999999);
            Cache::put('forgot_pwd_code_'.$request->email, $code, 300);
        
            Mail::raw("你的重設密碼驗證碼是：$code （5 分鐘內有效）", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('重設密碼驗證碼');
            });
        }
        
        // 🔐 不論有沒有帳號，回傳一樣的訊息
        return response()->json([
            'message' => '如果此電子郵件存在，驗證碼已寄出'
        ]);
    }

    // 驗證碼確認
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $email = $request->email;
        $inputOtp = $request->otp;

        // 從 Cache 取 OTP
        $cachedOtp = Cache::get('forgot_pwd_code_'.$email);

        // ✅ OTP 不存在或過期
        if (!$cachedOtp || $cachedOtp != $inputOtp) {
            return response()->json([
                'message' => '驗證碼錯誤或已過期'
            ], 400);
        }

        // ✅ OTP 正確 → 可以清掉 Cache，避免重複使用
        Cache::forget('forgot_pwd_code_'.$email);

        // 可選：生成一個臨時 token 讓前端重設密碼
        $resetToken = bin2hex(random_bytes(16));
        Cache::put('reset_token_'.$email, $resetToken, 600); // 10 分鐘有效

        return response()->json([
            'message' => '驗證成功',
            'reset_token' => $resetToken
        ]);
    }

    // 重設密碼
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $email = $request->email;
        $resetToken = $request->reset_token;

        // 1️⃣ 檢查 token
        $cachedToken = Cache::get('reset_token_'.$email);
        if (!$cachedToken || $cachedToken !== $resetToken) {
            return response()->json([
                'message' => '驗證碼錯誤或已過期'
            ], 400);
        }

        // 2️⃣ 找使用者
        $user = AuthUsers::where('email', $email)->first();

        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // 3️⃣ 刪除 token
        Cache::forget('reset_token_'.$email);

        return response()->json([
            'message' => '密碼已重設（如果電子郵件存在）'
        ]);
    }

    // 登入
    public function login(Request $request)
    {
        // // 1️⃣ 驗證必填欄位（帳號、密碼、captcha token）
        // $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required',
        //     'g-recaptcha-response' => 'required|captcha', // <- 直接用 captcha 驗證
        // ]);

        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        if (!app()->environment('local')) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        // 如果是 local 環境測試，不驗證 captcha

        $request->validate($rules);

        // 3️⃣ 驗證帳號密碼
        $authuser = AuthUsers::where('email', $request->email)->first();

        if (!$authuser || !Hash::check($request->password, $authuser->password)) {
            return response()->json(['message' => '帳號或密碼錯誤'], 401);
        }

        if ($authuser->is_active == 0) {
            return response()->json(['message' => '帳號已被停用'], 403);
        }

        // 4️⃣ 登入成功 → 建立 personal_access_token
        $token = $authuser->createToken('web')->plainTextToken;

        return response()->json([
            'message' => '登入成功',
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
       $user = $request->user(); // 透過 Sanctum 取得 AuthUsers

       if (!$user) {
           return response()->json([
               'success' => false,
               'message' => '使用者未登入',
           ], 401);
       }

       // 刪掉當前 token
       $user->currentAccessToken()?->delete();

       return response()->json([
           'success' => true,
           'message' => '已登出',
       ]);
    }

    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['success' => true, 'message' => '已登出所有裝置']);
    }

    public function deactivate(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '未登入',
            ], 401);
        }

        // 停用帳號
        $user->is_active = 0;
        $user->save();

        // 🔥 刪除所有 token（強制登出所有裝置）
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => '帳號已停用',
        ]);
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => '密碼錯誤'], 401);
        }

        $expireAt = now()->addSeconds(300)->timestamp * 1000;
        return response()->json([
            'success' => true,
            'expireAt' => $expireAt
        ]);
    }

    public function refresh()
    {
        $expireAt = now()->addSeconds(300)->timestamp * 1000;

        return response()->json([
            'expireAt' => $expireAt
        ]);
    }

    public function serverTime()
    {
        return response()->json([
            'serverTime' => now()->timestamp * 1000
        ]);
    }

    public function user(Request $request)
    {
        $auth = $request->user(); // AuthUser
        $user = $auth->user; // 可能是 null

        if ($user) {
            $user->load('roles');
            $userData = [
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'seat_number' => $user->seat_number,
                'roles' => $user->roles->pluck('role_name'),
            ];
        } else {
            $userData = null; // 或者給預設空陣列 []
        }

    
        return response()->json([
            'user' => $userData,
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

    public function profile(Request $request)
    {
        $user = $request->user(); // 取得登入使用者
        // 查 users_view
        $profileData = UsersView::where('user_id', $user->user_id)
            ->first(); // 取單筆

        if (!$profileData) {
            return response()->json([
                'success' => true,
                'hasProfile' => false,
                'message' => '使用者尚未建立個人資料',
                'profile' => null
            ], 200);
        }
        // // 假設 profileData.skills = [1,2,3,4,5]
        $skillIds = $profileData->skills ? array_map('intval', explode(',', $profileData->skills)) : []; // 轉成陣列
        
        $profileData->skills = $skillIds;

        return response()->json([
            'success' => true,
            'profile' => $profileData
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $user = $request->user(); // 透過 Sanctum 取得登入使用者

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '使用者未登入'
            ], 401);
        }

        $userdata = Users::where('id', $user->user_id)->first();

        // 驗證欄位
        $validated = $request->validate([
            'avatar' => 'nullable|string|max:255',
        ]);

        try {
            $userdata->avatar = $validated['avatar'] ?? $userdata->avatar;

            $userdata->save();

            return response()->json([
                'success' => true,
                'message' => '個人資料更新成功',
                'avatar' => $userdata->avatar
            ]);

        } catch (\Exception $e) {
            Log::error("UpdateProfile error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '更新個人資料失敗，請稍後再試'
            ], 500);
        }
    }

    public function updateprofile(Request $request)
    {
        $user = $request->user(); // 透過 Sanctum 取得登入使用者

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '使用者未登入'
            ], 401);
        }

        $userdata = Users::where('id', $user->user_id)->first();

        // 驗證欄位
        $validated = $request->validate([
            'profiledata.user_name' => 'required|string|max:20',
            'profiledata.user_en_name' => 'nullable|string|max:25',
            'profiledata.user_nick_name' => 'nullable|string|max:20',
            'profiledata.position_id' => 'nullable|integer',
            'profiledata.user_title' => 'nullable|string|max:30',
            'profiledata.seat_number' => 'required|integer|min:1',
            'profiledata.phone' => 'nullable|string|max:15',
            'profiledata.github' => 'nullable|url|max:80',
            'profiledata.linkedin' => 'nullable|url|max:255',
            'profiledata.bio' => 'nullable|string|max:500',
        ]);

        try {
            $profileData = $validated['profiledata'];
            $userdata->user_name = $profileData['user_name'] ?? $userdata->user_name;
            $userdata->user_en_name = $profileData['user_en_name'] ?? $userdata->user_en_name;
            $userdata->seat_number = $profileData['seat_number'] ?? $userdata->seat_number;
            $userdata->user_nick_name = $profileData['user_nick_name'] ?? $userdata->user_nick_name;
            $userdata->position_id = $profileData['position_id'] ?? $userdata->position_id;
            $userdata->user_title = $profileData['user_title'] ?? $userdata->user_title;
            $userdata->phone = $profileData['phone'] ?? $userdata->phone;
            $userdata->github = $profileData['github'] ?? $userdata->github;
            $userdata->linkedin = $profileData['linkedin'] ?? $userdata->linkedin;
            $userdata->bio = $profileData['bio'] ?? $userdata->bio;

            $userdata->save();

            return response()->json([
                'success' => true,
                'message' => '個人資料更新成功',
                'profile' => $userdata
            ]);

        } catch (\Exception $e) {
            Log::error("UpdateProfile error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '更新個人資料失敗，請稍後再試'
            ], 500);
        }
    }

    public function getAllSkills(Request $request)
    {
        try {
            $skills = Skills::where('delete_flag',0)
                            ->get();
        
            return response()->json([
                'success' => true,
                'AllSkills' => $skills,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function GetPositions(Request $request)
    {
        try {
            $AllPositions = Positions::where('delete_flag',0)
                            ->get();
        
            return response()->json([
                'success' => true,
                'AllPositions' => $AllPositions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUserSkills(Request $request)
    {
        $user = $request->user();

        // 驗證 skillsdata.skills
        $validated = $request->validate([
            'skillsdata.skills' => 'required|string',
        ]);

        // 更新使用者 skills
        $userdata = Users::where('id', $user->user_id)->first();
        $userdata->skills = $validated['skillsdata']['skills'];
        $userdata->save();

        return response()->json([
            'success' => true,
            'message' => '技能更新成功'
        ]);
    }

    public function addSkill(Request $request)
    {
        $validated = $request->validate([
            'skill_name' => 'required|string|max:50|unique:skills,skill_name',
        ]);

        $skill = Skills::create([
            'skill_name' => $validated['skill_name'],
        ]);

        return response()->json([
            'success' => true,
            'skill' => $skill, // 包含 id & skill_name
            'message' => '技能新增成功'
        ]);
    }

    public function setSeatNumber(Request $request)
    {
        $request->validate([
            'seat_number' => 'required|integer|min:1'
        ]);

        $seatNumber = intval($request->seat_number);

        // Step 1：檢查是否有其他使用者已經使用這個座號
        $exists = AuthUsers::where('user_id', $seatNumber)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => '此座號已被其他同學使用，請選擇其他座號'
            ], 409);
        }

        $user = $request->user();
        // Step 2：檢查使用者是否已經有座號
        if ($user->user_id) {
            return response()->json([
                'success' => false,
                'message' => '該帳號座號已設定，無法修改'
            ], 409);
        }

        // Step 3：寫入座號
        $user->user_id = $request->seat_number; // 寫入 auth_users
        $user->save();

        // Step 4：寫入角色 role_id = 3 學生
        UserRoles::firstOrCreate(
            [
                'user_id' => $user->user_id,
                'role_id' => 3,
            ]
        );

        // Step 5：讀取使用者 profile
        $profileData = UsersView::where('user_id', $user->user_id)
            ->first(); // 取單筆

        if (!$profileData) {
            return response()->json([
                'success' => true,
                'hasProfile' => false,
                'message' => '使用者尚未建立個人資料',
                'profile' => null
            ], 200);
        }

        // Step 6：處理 skills
        // // 假設 profileData.skills = [1,2,3,4,5]
        $skillIds = $profileData->skills ? array_map('intval', explode(',', $profileData->skills)) : []; // 轉成陣列
        
        $profileData->skills = $skillIds;

        // Step 7：// 重新抓使用者，確保 roles 也抓到
        $userModel = Users::with('roles')
            ->where('id', $user->user_id) // 用剛設定的 seat_number
            ->first();

        $userData = null;
        if ($userModel) {
            $userData = [
                'user_name' => $userModel->user_name,
                'seat_number' => $userModel->user_id,
                'roles' => $userModel->roles->pluck('role_name')->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => '座號設定成功',
            'profile' => $profileData,
            'user' => $userData
        ]);
    }
    
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // 驗證
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:4',
            'confirm_password' => 'required|string|min:4',
        ]);

        // 驗證舊密碼
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => '舊密碼錯誤',
            ], 422);
        }
        
        // 驗證新密碼和確認密碼是否一致
        if ($request->new_password !== $request->confirm_password) {
            return response()->json([
                'success' => false,
                'message' => '新密碼與確認密碼不一致',
            ], 422);
        }

        // 更新密碼
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => '密碼更新成功',
        ]);
    }

    public function getAllStudents()
    {
        try {
            $AllStudents = UsersView::where('seat_number', '!=', '')
                ->whereNotNull('seat_number')
                ->get();

            foreach ($AllStudents as $student) {
                $skillIds = $student->skills 
                    ? array_map('intval', explode(',', $student->skills)) 
                    : [];

                $student->skills = $skillIds;
            }

            return response()->json($AllStudents, 200);
        } catch (\Exception $e) {
            Log::error('GetAllStudents Error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
