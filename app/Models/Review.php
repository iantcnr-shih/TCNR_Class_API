<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'review_id';

    // created_at 和 updated_at 欄位存在 DB, Laravel 可直接使用
    public $timestamps = true;

    // 避免 mass assignment 的問題，指定可填充的欄位
    protected $fillable = [
        'shop_id',
        'food_id',
        'user_id',
        'rating',
        'comment',
    ];

    // 關聯
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function food()
    {
        return $this->belongsTo(Foods::class, 'food_id', 'food_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shops::class, 'shop_id', 'shop_id');
    }
}
