<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('username')->nullable();
            $table->string('phone')->nullable();
            $table->string('language')->nullable();
            $table->string('payment_preference')->nullable();
            $table->text('location_data')->nullable(); // JSON map/text
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
