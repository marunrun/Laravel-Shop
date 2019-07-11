<?php

namespace App\Http\Requests;

use App\Models\CrowdfundingProduct;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Validation\Rule;

class CrowdFundingOrderRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$sku = ProductSku::find($value)) {
                        return $fail('该商品不存在');
                    }

                    if (Product::TYPE_CROWDFUNDING !== $sku->product->type) {
                        return $fail('该商品不是众筹商品');
                    }

                    if (!$sku->product->on_sale) {
                        return $fail('该商品未上架');
                    }


                    if (CrowdfundingProduct::STATUS_FUNDING !== $sku->product->crowdfunding->status) {
                        return $fail('众筹已结束');
                    }

                    if (0 === $sku->stock) {
                        return $fail('该商品已售完');
                    }

                    if ($this->input('amount') > 0 && $sku->stock < $this->input('amount')) {
                        return $fail('该商品的库存不足');
                    }

                    return true;
                },
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
