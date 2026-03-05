<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    public $timestamps = false;
    protected $fillable = ['seat_number', 'avatar', 'user_name', 'user_en_name', 'user_nick_name', 'position_id', 'user_title', 'bio', 'phone', 'github', 'linkedin', 'skills'];

    public function authAccounts()
    {
        return $this->hasMany(AuthUsers::class);
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

    public function position()
    {
        return $this->belongsTo(Positions::class, 'position_id');
    }
}
