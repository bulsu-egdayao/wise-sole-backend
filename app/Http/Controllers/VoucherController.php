<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code = strtoupper(trim($validated['code']));
        $subtotal = (float) $validated['subtotal'];

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid voucher code.'
            ], 404);
        }

        $check = $voucher->isValidFor($subtotal);
        if (!$check['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $check['message'],
            ], 422);
        }

        $discount = $voucher->calculateDiscount($subtotal);

        return response()->json([
            'valid' => true,
            'code' => $voucher->code,
            'discount_type' => $voucher->discount_type,
            'discount_value' => $voucher->discount_value,
            'discount_amount' => $discount,
            'min_spend' => $voucher->min_spend,
            'new_total' => max(0, $subtotal - $discount),
        ]);
    }

    public function index()
    {
        return response()->json(Voucher::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code|max:30',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:1',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_spend'] = $validated['min_spend'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $voucher = Voucher::create($validated);
        return response()->json($voucher, 201);
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:30|unique:vouchers,code,' . $voucher->id,
            'discount_type' => 'sometimes|required|in:percent,fixed',
            'discount_value' => 'sometimes|required|numeric|min:1',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        $voucher->update($validated);
        return response()->json($voucher);
    }

    public function toggleActive(Voucher $voucher)
    {
        $voucher->is_active = !$voucher->is_active;
        $voucher->save();
        return response()->json($voucher);
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return response()->json(['message' => 'Voucher deleted successfully']);
    }
}
