<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Roles extends Model
{
    protected $table = 'roles'; // 對應你的資料表名稱
    public $timestamps = false; // 如果沒有 created_at / updated_at

    public function users()
    {
        return $this->belongsToMany(Users::class, 'user_roles', 'role_id', 'user_id');
    }
}
