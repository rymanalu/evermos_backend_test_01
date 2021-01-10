<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Http\Requests\AddCartRequest;
use App\Http\Requests\EditCartRequest;
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

    public function store(AddCartRequest $request)
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

    public function update(EditCartRequest $request)
    {
        DB::beginTransaction();

        try {
            $cart = Cart::findOrFail($request->id);

            $cart->update([
                'product_id' => $request->product_id,
                'qty' => $request->qty,
            ]);

            DB::commit();

            return response()->api(['meta_message' => 'Product updated in cart']);
        } catch (Exception $err) {
            $message = 'Failed to update a product in user\'s cart';

            DB::rollBack();

            Log::error($message, ['error' => $err]);

            return response()->api(['meta_message' => $message], 500);
        }
    }
}
