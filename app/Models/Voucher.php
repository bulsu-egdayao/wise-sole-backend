<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_spend',
        'max_discount',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_spend' => 'float',
        'max_discount' => 'float',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isValidFor(float $subtotal): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This voucher is no longer active.'];
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return ['valid' => false, 'message' => 'This voucher has already expired.'];
        }

        if ($this->min_spend > 0 && $subtotal < $this->min_spend) {
            return [
                'valid' => false,
                'message' => 'Minimum spend of ₱' . number_format($this->min_spend, 2) . ' required to use this voucher.'
            ];
        }

        return ['valid' => true];
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = 0;
        if ($this->discount_type === 'percent') {
            $discount = ($subtotal * $this->discount_value) / 100;
            if ($this->max_discount && $this->max_discount > 0) {
                $discount = min($discount, $this->max_discount);
            }
        } else {
            $discount = min($this->discount_value, $subtotal);
        }
        return round($discount, 2);
    }
}
