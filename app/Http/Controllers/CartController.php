<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Http\Requests\AddCartRequest;
use App\Http\Requests\CheckoutCartRequest;
use App\Http\Requests\EditCartRequest;
use App\Http\Requests\GetCartRequest;
use App\Order;
use App\Product;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index(GetCartRequest $request)
    {
        $user = User::find($request->user_id);

        return response()->api($user->cart->toArray());
    }

    public function store(AddCartRequest $request)
    {
        DB::beginTransaction();

        try {
            $productInCart = Cart::lockForUpdate()
                ->where('user_id', $request->user_id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($productInCart) {
                $productInCart->increment('qty', $request->qty);
            } else {
                Cart::create([
                    'user_id' => $request->user_id,
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
            $cart = Cart::lockForUpdate()->findOrFail($request->id);

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

    public function checkout(CheckoutCartRequest $request)
    {
        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $request->user_id,
                'total' => 0,
            ]);

            $total = 0;
            $shoppingCart = Cart::lockForUpdate()
                ->where('user_id', $request->user_id)
                ->get();

            foreach ($shoppingCart as $cart) {
                $product = Product::findOrFail($cart->product_id);

                $subTotal = $product->price * $cart->qty;

                $order->items()->create([
                    'product_id' => $product->id,
                    'qty' => $cart->qty,
                    'total' => $subTotal,
                ]);

                $total += $subTotal;
            }

            $order->update(['total' => $total]);

            Cart::where('user_id', $request->user_id)->delete();

            DB::commit();

            return response()->api(['meta_message' => 'Checkout cart succeed']);
        } catch (Exception $err) {
            $message = 'Failed to checkout user\'s cart';

            DB::rollBack();

            Log::error($message, ['error' => $err]);

            return response()->api(['meta_message' => $message], 500);
        }
    }
}
