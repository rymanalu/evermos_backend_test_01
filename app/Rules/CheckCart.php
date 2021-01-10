<?php

namespace App\Rules;

use App\Cart;
use Illuminate\Contracts\Validation\Rule;

class CheckCart implements Rule
{
    protected $userId;

    public function passes($attribute, $value)
    {
        return Cart::where('user_id', '=', $value)->count() > 0;
    }

    public function message()
    {
        return trans('validation.exists');
    }
}
