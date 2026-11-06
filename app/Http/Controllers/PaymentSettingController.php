<?php

namespace App\Http\Controllers;

use App\Models\SiteImage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class PaymentSettingController extends Controller
{
    public function index()
    {
        $gcashQr = SiteImage::where('key', 'payment_gcash_qr')->value('image_path')
            ?: 'https://res.cloudinary.com/xrsfekgk/image/upload/v1793934903/site/ksaefvjr0xj3d86forpk.jpg';
        $maribankQr = SiteImage::where('key', 'payment_maribank_qr')->value('image_path')
            ?: 'https://res.cloudinary.com/xrsfekgk/image/upload/v1793934905/site/s6vb8vawxl8pm0c0v53l.jpg';

        return response()->json([
            'gcash_number' => SiteSetting::get('gcash_number', '09692891708'),
            'gcash_name' => SiteSetting::get('gcash_name', 'Ezekiel Dayao'),
            'gcash_qr_url' => $gcashQr,

            'maribank_number' => SiteSetting::get('maribank_number', '09692891708'),
            'maribank_name' => SiteSetting::get('maribank_name', 'Ezekiel Dayao'),
            'maribank_qr_url' => $maribankQr,

            'shipping_note' => SiteSetting::get('shipping_note', 'Standard shipping takes 2-4 business days for Metro Manila, 4-7 business days for Provincial.'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gcash_number' => 'nullable|string|max:50',
            'gcash_name' => 'nullable|string|max:100',
            'maribank_number' => 'nullable|string|max:50',
            'maribank_name' => 'nullable|string|max:100',
            'shipping_note' => 'nullable|string|max:500',
        ]);

        foreach ($validated as $key => $value) {
            SiteSetting::set($key, $value);
        }

        return $this->index();
    }
}
