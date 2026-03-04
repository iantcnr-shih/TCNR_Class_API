<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 先不做登入驗證
    }

    protected function prepareForValidation(): void
    {
        // 避免前端送 "" 造成 nullable/integer 行為不一致
        if ($this->has('food_id') && $this->input('food_id') === '') {
            $this->merge(['food_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,shop_id'],
            'food_id' => ['nullable', 'integer', 'exists:foods,food_id'],
            'user_id' => ['required', 'integer', 'exists:users,user_id'],
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $shopId = $this->input('shop_id');
            $foodId = $this->input('food_id');

            // 店家評價：food_id = null 不需要驗證
            if ($foodId === null) {
                return;
            }

            // 驗證 food 是否屬於 shop（foods -> menu_categories）
            $foodShopId = DB::table('foods as f')
                ->join('menu_categories as mc', 'mc.menu_categorie_id', '=', 'f.menu_category_id')
                ->where('f.food_id', $foodId)
                ->value('mc.shop_id');

            // rules() 的 exists 通常會先擋，但保守處理（資料缺 mapping 時）
            if ($foodShopId === null) {
                $validator->errors()->add('food_id', 'food_id not found or category mapping missing');
                return;
            }

            if ((int) $foodShopId !== (int) $shopId) {
                $validator->errors()->add('food_id', 'food_id does not belong to the given shop_id');
            }
        });
    }
}
