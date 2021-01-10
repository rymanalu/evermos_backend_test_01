<?php

namespace App\Rules;

use App\Cart;
use App\Product;
use Illuminate\Contracts\Validation\Rule;

class CheckStock implements Rule
{
    protected $productId;

    protected $checkout = false;

    protected $qty = 0;

    public function __construct($productId, $checkout = false)
    {
        $this->productId = $productId;
        $this->checkout = $checkout;
    }

    public function passes($attribute, $value)
    {
        if (! is_numeric($this->productId)) {
            return false;
        }

        $product = Product::sharedLock()->find($this->productId);

        if ($product === null) {
            return false;
        }

        if ($this->checkout) {
            $this->qty = $product->qty;
        } else {
            $qtyInCart = Cart::sharedLock()
                ->where('product_id', $this->productId)
                ->sum('qty');

            $this->qty = $product->qty - $qtyInCart;
        }

        return $value <= $this->qty;
    }

    public function message()
    {
        return trans('validation.max.numeric', ['max' => $this->qty]);
    }
}
