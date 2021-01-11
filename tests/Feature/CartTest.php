<?php

namespace Tests\Feature;

use App\Cart;
use App\Product;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
{
    use InteractsWithDatabase, RefreshDatabase, WithFaker;

    /** @test */
    public function it_returns_all_items_in_user_cart()
    {
        // Empty cart test...
        $userA = factory(User::class)->create();
        $responseA = $this->getJson('/api/cart?user_id='.$userA->id);
        $responseA->assertSuccessful();
        $responseA->assertJson([
            'data' => [],
        ]);

        // Filled cart test...
        $product = factory(Product::class)->create(['qty' => 5]);
        $userB = factory(User::class)->create();
        $this->postJson('/api/cart', [
            'user_id' => $userB->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);
        $responseB = $this->getJson('/api/cart?user_id='.$userB->id);
        $responseB->assertSuccessful();
        $responseB->assertJson([
            'data' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                ],
            ],
        ]);
    }

    /** @test */
    public function it_adds_item_to_user_cart()
    {
        $user = factory(User::class)->create();
        $product = factory(Product::class)->create(['qty' => 5]);

        $this->assertDatabaseMissing('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/cart', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    /** @test */
    public function it_updates_item_to_user_cart()
    {
        $user = factory(User::class)->create();
        $product = factory(Product::class)->create(['qty' => 5]);
        $cart = factory(Cart::class)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response = $this->putJson('/api/cart', [
            'id' => $cart->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);
    }

    /** @test */
    public function it_checkouts_user_cart()
    {
        $user = factory(User::class)->create();
        $product = factory(Product::class)->create(['qty' => 5]);
        factory(Cart::class)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('order_items', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response = $this->postJson('/api/cart/checkout', [
            'user_id' => $user->id,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);
    }

    /** @test */
    public function it_handles_concurrent_requests_when_add_item_to_cart()
    {
        // Throws exception because there is an invalid request because it passes the qty...
        $this->expectException(ClientException::class);

        $users = factory(User::class, 3)->create();
        $product = factory(Product::class)->create(['qty' => 5]);

        $client = new Client(['base_uri' => env('APP_URL')]);

        $promises = [
            'cartA' => $client->postAsync(
                '/api/cart',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'user_id' => $users[0]->id,
                        'product_id' => $product->id,
                        'qty' => 3,
                    ],
                ]
            ),
            'cartB' => $client->postAsync(
                '/api/cart',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'user_id' => $users[1]->id,
                        'product_id' => $product->id,
                        'qty' => 2,
                    ],
                ]
            ),
            'cartC' => $client->postAsync(
                '/api/cart',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'user_id' => $users[2]->id,
                        'product_id' => $product->id,
                        'qty' => 1,
                    ],
                ]
            ),
        ];

        Promise\Utils::unwrap($promises);
    }
}
