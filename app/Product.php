<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'qty',
    ];

    protected $casts = [
        'price' => 'integer',
    ];
}
