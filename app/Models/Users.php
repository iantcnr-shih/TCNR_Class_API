<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// class Users extends Model
// {
//     use HasFactory;

//     protected $table = 'users';
//     protected $primaryKey = 'user_id';

//     protected $fillable = [
//         'user_name',
//         'seat_number',
//         'access_id',
//     ];
//     // 關聯回登入帳號
//     public function access()
//     {
//         return $this->belongsTo(Access::class, 'access_id', 'access_id');
//     }

//     public function roles()
//     {
//         return $this->belongsToMany(
//             Role::class,
//             'user_roles',
//             'user_id',
//             'role_id'
//         );
//     }
// }

class Users extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id'; // 如果主鍵不是 id
    public $timestamps = false;
    protected $fillable = ['user_name', 'seat_number'];

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
