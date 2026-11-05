<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->text('delivery_address');
            $table->string('delivery_city')->nullable();
            $table->string('delivery_province')->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('voucher_code')->nullable();
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            $table->string('payment_method')->default('gcash'); // 'gcash', 'maribank'
            $table->string('payment_reference')->nullable();
            $table->string('receipt_image_path')->nullable();

            $table->string('courier_name')->nullable(); // 'J&T Express', 'Flash Express', 'LBC Express', 'Lalamove', 'Other'
            $table->string('tracking_number')->nullable();
            $table->text('tracking_url')->nullable();
            $table->string('status')->default('pending_verification'); // 'pending_verification', 'payment_verified', 'shipped', 'delivered', 'cancelled'
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->string('size')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
