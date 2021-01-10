<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Product;
use Faker\Generator as Faker;

$factory->define(Product::class, function (Faker $faker) {
    return [
        'name' => $faker->words(3, true),
        'price' => $faker->numberBetween(10000, 999999),
        'qty' => $faker->numberBetween(1, 10),
    ];
});
