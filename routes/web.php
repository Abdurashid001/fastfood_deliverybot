<?php

use Illuminate\Support\Facades\Route;
use App\Models\Order;

Route::get('/', function () {
    return "Bot Server ishlamoqda. Telegram Web App uchun /webapp manziliga o'ting.";
});

Route::get('/webapp', function () {
    return view('webapp');
});

Route::get('/admin', function () {
    $orders = collect();
    $stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'completed' => 0,
        'pending' => 0
    ];

    try {
        $orders = Order::orderBy('created_at', 'desc')->get();
        
        $stats['total_orders'] = $orders->count();
        $stats['total_revenue'] = $orders->whereIn('status', ['✅ Yetkazildi'])->sum('total');
        $stats['completed'] = $orders->where('status', '✅ Yetkazildi')->count();
        $stats['pending'] = $orders->whereNotIn('status', ['✅ Yetkazildi', '🔴 Bekor qilindi'])->count();
        $stats['total_users'] = \App\Models\BotUser::count();
        $bot_users = \App\Models\BotUser::orderBy('created_at', 'desc')->get();
    } catch (\Exception $e) {
        // Table hasn't been created or migration failed
    }

    return view('admin', compact('orders', 'stats', 'bot_users'));
});
