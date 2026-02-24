<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerControl extends Model
{
    // 指定該模型對應的資料表
    protected $table = 'manager_control'; // 資料表的名稱

    // 設定主鍵
    protected $primaryKey = 'row_id';

    // 自動管理的時間戳
    public $timestamps = true; // 自動尋找 created_at 和 updated_at 欄位

    // 如果資料表有特定的欄位名稱，將其設定在 $fillable 或 $guarded
    protected $fillable = ['row_id', 'c_date', 'c_title', 'c_value', 'c_remark'];
}