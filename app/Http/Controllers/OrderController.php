<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Detect upload error before validator fails with generic message
        if ($request->hasFile('receipt_image') && !$request->file('receipt_image')->isValid()) {
            $errCode = $request->file('receipt_image')->getError();
            $errMsg = match ($errCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Receipt image exceeds upload size limit. Please upload a compressed screenshot.',
                default => 'The receipt image failed to upload from your device.',
            };
            return response()->json(['message' => $errMsg, 'errors' => ['receipt_image' => [$errMsg]]], 422);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:120',
            'customer_email' => 'nullable|email|max:120',
            'customer_phone' => 'required|string|max:30',
            'delivery_address' => 'required|string|max:500',
            'delivery_city' => 'nullable|string|max:100',
            'delivery_province' => 'nullable|string|max:100',
            'delivery_notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:gcash,maribank,cod',
            'payment_reference' => 'nullable|string|max:100',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic|max:12288',
            'voucher_code' => 'nullable|string|max:30',
            'courier_name' => 'nullable|string|max:50',
            'items' => 'required|string', // JSON encoded string for multipart form compatibility
        ]);

        $items = json_decode($validated['items'], true);
        if (!is_array($items) || empty($items)) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        // Upload receipt to Cloudinary if provided
        $receiptUrl = null;
        if ($request->hasFile('receipt_image')) {
            try {
                $receiptUrl = CloudinaryService::upload($request->file('receipt_image'), 'orders/receipts');
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to upload receipt: ' . $e->getMessage()], 500);
            }
        }

        return DB::transaction(function () use ($request, $validated, $items, $receiptUrl) {
            $subtotal = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                $product = $productId ? Product::with('images')->find($productId) : null;
                
                $price = $product 
                    ? (float) ($product->sale_price && $product->sale_price > 0 ? $product->sale_price : $product->price)
                    : (float) ($item['price'] ?? 0);
                
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $subtotal += ($price * $qty);

                $imagePath = null;
                if ($product && $product->images && $product->images->isNotEmpty()) {
                    $primary = $product->images->firstWhere('is_primary', true) ?: $product->images->first();
                    $imagePath = $primary ? $primary->image_path : null;
                } else {
                    $imagePath = $item['image_path'] ?? null;
                }

                $preparedItems[] = [
                    'product_id' => $product ? $product->id : null,
                    'product_name' => $product ? $product->name : ($item['product_name'] ?? 'Custom Item'),
                    'size' => $item['size'] ?? null,
                    'price' => $price,
                    'quantity' => $qty,
                    'image_path' => $imagePath,
                ];
            }

            // Calculate voucher discount
            $discountAmount = 0;
            $appliedVoucherCode = null;
            if (!empty($validated['voucher_code'])) {
                $code = strtoupper(trim($validated['voucher_code']));
                $voucher = Voucher::where('code', $code)->first();
                if ($voucher) {
                    $check = $voucher->isValidFor($subtotal);
                    if ($check['valid']) {
                        $discountAmount = $voucher->calculateDiscount($subtotal);
                        $appliedVoucherCode = $voucher->code;
                    }
                }
            }

            // COD orders have standard LBC Express shipping fee (₱150)
            $isCod = ($validated['payment_method'] === 'cod');
            $shippingFee = $isCod ? 150 : 0;
            $totalAmount = max(0, $subtotal - $discountAmount + $shippingFee);
            $courierName = $validated['courier_name'] ?? ($isCod ? 'LBC Express' : null);

            $order = Order::create([
                'user_id' => $request->user()?->id,
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'delivery_address' => $validated['delivery_address'],
                'delivery_city' => $validated['delivery_city'] ?? null,
                'delivery_province' => $validated['delivery_province'] ?? null,
                'delivery_notes' => $validated['delivery_notes'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'voucher_code' => $appliedVoucherCode,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'receipt_image_path' => $receiptUrl,
                'courier_name' => $courierName,
                'status' => 'pending_verification',
            ]);

            foreach ($preparedItems as $pItem) {
                $order->items()->create($pItem);
            }

            return response()->json($order->load('items'), 201);
        });
    }

    public function myOrders(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([], 401);
        }

        // Match by user_id OR customer email
        $orders = Order::with('items')
            ->where(function ($q) use ($user) {
                if (isset($user->id)) {
                    $q->where('user_id', $user->id);
                }
                if ($user->email) {
                    $q->orWhere('customer_email', $user->email);
                }
            })
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function track(string $orderNumber)
    {
        $cleanNumber = strtoupper(trim($orderNumber));
        $order = Order::with('items')->where('order_number', $cleanNumber)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found. Please double check your Order Number.'], 404);
        }

        // Return order with masked phone for privacy
        $maskedPhone = $order->customer_phone;
        if (strlen($maskedPhone) > 4) {
            $maskedPhone = substr($maskedPhone, 0, 3) . '****' . substr($maskedPhone, -4);
        }

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $maskedPhone,
            'delivery_address' => $order->delivery_address,
            'delivery_city' => $order->delivery_city,
            'delivery_province' => $order->delivery_province,
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'voucher_code' => $order->voucher_code,
            'shipping_fee' => $order->shipping_fee,
            'total_amount' => $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'receipt_image_path' => $order->receipt_image_path,
            'courier_name' => $order->courier_name,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url ?: Order::generateTrackingUrl($order->courier_name, $order->tracking_number),
            'status' => $order->status,
            'created_at' => $order->created_at,
            'items' => $order->items,
        ]);
    }

    public function index(Request $request)
    {
        $query = Order::with('items')->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_name', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_phone', 'ILIKE', "%{$search}%")
                  ->orWhere('tracking_number', 'ILIKE', "%{$search}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    public function show(Order $order)
    {
        return response()->json($order->load('items'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending_verification,payment_verified,shipped,delivered,cancelled',
            'courier_name' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:100',
            'tracking_url' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Auto-generate tracking URL when courier + tracking number are provided
            if (!empty($validated['courier_name']) && !empty($validated['tracking_number'])) {
                $generatedUrl = Order::generateTrackingUrl($validated['courier_name'], $validated['tracking_number']);
                // Use the manually provided URL if present, otherwise use the auto-generated one
                $validated['tracking_url'] = ($validated['tracking_url'] ?? null) ?: $generatedUrl;
            }

            $order->update($validated);

            return response()->json($order->load('items'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
