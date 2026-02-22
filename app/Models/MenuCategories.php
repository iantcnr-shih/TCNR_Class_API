<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategories extends Model
{
    // 指定該模型對應的資料表
    protected $table = 'menu_categories'; // 資料表的名稱

    // 不需要 Eloquent 自動管理的時間戳
    public $timestamps = false; // 這樣 Eloquent 就不會自動尋找 created_at 和 updated_at 欄位

    // 如果資料表有特定的欄位名稱，將其設定在 $fillable 或 $guarded
    protected $fillable = ['menu_category_id', 'shop_id', 'category_number', 'category_name'];
}