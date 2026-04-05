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
            $table->string('chat_id');
            $table->string('phone');
            $table->string('location_type')->nullable(); // map yoki text
            $table->text('location_data')->nullable(); // json yoki string
            $table->text('items'); // JSON of ordered items
            $table->string('payment_type'); // naqd, ucard, click
            $table->decimal('items_total', 10, 2);
            $table->decimal('delivery_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('kutilmoqda'); // kutilmoqda, tayyorlanmoqda, yolda, yetkazildi, bekor_qilindi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
