<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersView extends Model
{
    protected $table = 'users_view';
    protected $primaryKey = 'user_id'; // 如果主鍵不是 id
    public $timestamps = false;
}
