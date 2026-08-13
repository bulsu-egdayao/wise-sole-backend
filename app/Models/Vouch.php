<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vouch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rating', 'message', 'status'];

    protected $casts = [
        'rating' => 'integer',
    ];
}