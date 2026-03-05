<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Positions extends Model
{
    // 指定該模型對應的資料表
    protected $table = 'positions'; // 資料表的名稱
    public $incrementing = true; // 自動遞增
    // 自動管理的時間戳
    public $timestamps = false; // 自動尋找 created_at 和 updated_at 欄位

    // 如果資料表有特定的欄位名稱，將其設定在 $fillable 或 $guarded
    protected $fillable = ['position_name', 'delete_flag'];
}