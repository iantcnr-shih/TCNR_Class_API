<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Access extends Authenticatable
{
    use HasApiTokens;
    protected $table = 'access';

    protected $primaryKey = 'access_id'; // 如果你的主鍵不是 id 一定要寫

    protected $fillable = [
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];
}