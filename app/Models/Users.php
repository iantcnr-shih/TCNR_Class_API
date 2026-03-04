<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id'; // 如果主鍵不是 id
    public $timestamps = false;
    protected $fillable = ['seat_number', 'user_name', 'user_en_name', 'user_nick_name', 'bio', 'phone', 'github', 'linkedin', 'skills'];

    public function authAccounts()
    {
        return $this->hasMany(AuthUser::class);
    }

    public function roles()
    {
        return $this->belongsToMany(
            Roles::class,       // 關聯 Model
            'user_roles',      // pivot table
            'user_id',         // user 外鍵在 pivot
            'role_id'          // role 外鍵在 pivot
        );
    }
}
