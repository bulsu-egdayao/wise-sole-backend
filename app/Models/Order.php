<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'delivery_city',
        'delivery_province',
        'delivery_notes',
        'subtotal',
        'discount_amount',
        'voucher_code',
        'shipping_fee',
        'total_amount',
        'payment_method',
        'payment_reference',
        'receipt_image_path',
        'courier_name',
        'tracking_number',
        'tracking_url',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'shipping_fee' => 'float',
        'total_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'WS';
        $date = now()->format('ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        return "{$prefix}-{$date}-{$random}";
    }

    public static function generateTrackingUrl(?string $courier, ?string $trackingNumber): ?string
    {
        if (!$trackingNumber) {
            return null;
        }

        $trackingNumber = trim($trackingNumber);
        if (str_starts_with($trackingNumber, 'http://') || str_starts_with($trackingNumber, 'https://')) {
            return $trackingNumber;
        }

        return match ($courier) {
            'J&T Express' => "https://www.jtexpress.ph/trajectoryQuery?bills={$trackingNumber}",
            'Flash Express' => "https://www.flashexpress.ph/tracking/?se={$trackingNumber}",
            'LBC Express' => "https://www.lbcexpress.com/track/?tracking_no={$trackingNumber}",
            default => null,
        };
    }
}
