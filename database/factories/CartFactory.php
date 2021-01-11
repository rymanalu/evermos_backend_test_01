<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Cart;
use App\Product;
use App\User;
use Faker\Generator as Faker;

$factory->define(Cart::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'product_id' => function () {
            return factory(Product::class)->create()->id;
        },
        'qty' => $faker->numberBetween(1, 5),
    ];
});
