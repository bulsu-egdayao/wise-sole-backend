<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'contact',
        'message',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}