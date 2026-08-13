<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegitimacyProof extends Model
{
    use HasFactory;

    protected $fillable = ['transaction_category_id', 'image_path', 'caption', 'sort_order'];

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }
}