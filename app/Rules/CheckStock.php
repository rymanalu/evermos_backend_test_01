<?php

namespace App\Rules;

use App\Cart;
use App\Product;
use Illuminate\Contracts\Validation\Rule;

class CheckStock implements Rule
{
    protected $productId;

    protected $except = 0;

    protected $checkout = false;

    protected $qty = 0;

    public function __construct($productId, $except = 0, $checkout = false)
    {
        $this->productId = $productId;
        $this->except = $except;
        $this->checkout = $checkout;
    }

    public function passes($attribute, $value)
    {
        if (! is_numeric($this->productId)) {
            return false;
        }

        $product = Product::find($this->productId);

        if ($product === null) {
            return false;
        }

        if ($this->checkout) {
            $this->qty = $product->qty;
        } else {
            $qtyInCartQuery = Cart::sharedLock()->where('product_id', $this->productId);

            if (is_numeric($this->except)) {
                $qtyInCartQuery = $qtyInCartQuery->where('id', '!=', $this->except);
            }

            $qtyInCart = $qtyInCartQuery->sum('qty');

            $this->qty = $product->qty - $qtyInCart;
        }

        return $value <= $this->qty;
    }

    public function message()
    {
        return trans('validation.max.numeric', ['max' => $this->qty]);
    }
}
