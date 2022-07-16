<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function product_variant_1()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'product_variant_one');
    }

    public function product_variant_2()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'product_variant_two');
    }

    public function product_variant_3()
    {
        return $this->hasOne(ProductVariant::class, 'id', 'product_variant_three');
    }

}
