<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    // 指定該模型對應的資料表
    protected $table = 'orders'; // 資料表的名稱
    protected $primaryKey = 'order_id';
    public $incrementing = true; // 自動遞增
    protected $dates = ['order_date'];
    // 自動管理的時間戳
    public $timestamps = true; // 自動尋找 created_at 和 updated_at 欄位

    // 如果資料表有特定的欄位名稱，將其設定在 $fillable 或 $guarded
    protected $fillable = ['order_id', 'order_date', 'order_type', 'order_round', 'seat_number', 'food_id', 'quantity', 'is_paid', 'user_ip', 'delete_flag', 'remark'];
}