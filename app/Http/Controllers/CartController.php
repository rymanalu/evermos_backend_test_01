<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Http\Requests\CartRequest;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index()
    {
        $user = User::first();

        return response()->api($user->cart->toArray());
    }

    public function store(CartRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::first();

            $productInCart = Cart::where('user_id', $user->id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($productInCart) {
                $productInCart->increment('qty', $request->qty);
            } else {
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $request->product_id,
                    'qty' => $request->qty,
                ]);
            }

            DB::commit();

            return response()->api(['meta_message' => 'Product added to cart']);
        } catch (Exception $err) {
            $message = 'Failed to add a product to user\'s cart';

            DB::rollBack();

            Log::error($message, ['error' => $err]);

            return response()->api(['meta_message' => $message], 500);
        }
    }
}
