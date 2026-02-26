<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class AuthUsers extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'auth_users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',       // 綁定 users 表
        'provider',      // local / google / github
        'provider_uid',  // OAuth provider 的 uid
        'email',         // local / google / github email
        'password',      // local 專用
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * 關聯到真實使用者
     */
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'user_id'); 
        // 第一個 user_id 是 auth_users.user_id
        // 第二個 user_id 是 users.user_id
    }
}