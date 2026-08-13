<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
  protected $fillable = [
    'name',
    'slug',
    'description',
    'price',
    'sale_price',
    'category_id',
    'product_type_id',
    'stock',
    'is_available',
    'is_featured',
    'is_new',
];

  protected $casts = [
    'price' => 'decimal:2',
    'sale_price' => 'decimal:2',
    'is_available' => 'boolean',
    'is_featured' => 'boolean',
    'is_new' => 'boolean',
];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

   public function sizes()
{
    return $this->hasMany(ProductSize::class);
}

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class);
    }
}