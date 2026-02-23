<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WdayShopView extends Model
{
    // 指定該模型對應的檢視表名稱
    protected $table = 'wday_shop_view'; // 檢視表的名稱

    // 由於這是檢視表，通常不需要 Eloquent 自動管理的時間戳
    public $timestamps = false; // 這樣 Eloquent 就不會自動尋找 created_at 和 updated_at 欄位

    // 如果檢視表有特定的欄位名稱，將其設定在 $fillable 或 $guarded
    protected $fillable = ['wday_shop_id', 'wday', 'shop_id', 'shop_name'];
}