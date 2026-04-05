<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f1115; color: #e2e8f0; }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }
        .status-badge { padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .status-yellow { background: rgba(234, 179, 8, 0.15); color: #fbbf24; border: 1px solid rgba(234, 179, 8, 0.3); }
        .status-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .status-green { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .status-red { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body class="min-h-screen pb-12">

    <nav class="glass-card sticky top-0 z-50 px-6 py-4 flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-orange-400 to-red-500 flex items-center justify-center text-xl shadow-lg">🍔</div>
            <h1 class="text-2xl font-bold tracking-tight text-white"><span class="text-orange-400 font-medium">Admin</span></h1>
        </div>
        <div class="text-sm text-gray-400">Laravel v11 API</div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="glass-card rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">👥</div>
                <h3 class="text-slate-400 text-sm font-medium">Barcha Mijozlar</h3>
                <p class="text-3xl font-bold text-white mt-2">{{ $stats['total_users'] ?? 0 }}</p>
            </div>
            <div class="glass-card rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">📦</div>
                <h3 class="text-slate-400 text-sm font-medium">Jami Buyurtmalar</h3>
                <p class="text-3xl font-bold text-white mt-2">{{ $stats['total_orders'] }}</p>
            </div>
            <div class="glass-card rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">💰</div>
                <h3 class="text-slate-400 text-sm font-medium">Umumiy Daromad</h3>
                <p class="text-3xl font-bold text-emerald-400 mt-2">${{ number_format($stats['total_revenue'], 2) }}</p>
            </div>
            <div class="glass-card rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">⏳</div>
                <h3 class="text-slate-400 text-sm font-medium">Kutilmoqda</h3>
                <p class="text-3xl font-bold text-amber-400 mt-2">{{ $stats['pending'] }}</p>
            </div>
            <div class="glass-card rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">✅</div>
                <h3 class="text-slate-400 text-sm font-medium">Muvaffaqiyatli</h3>
                <p class="text-3xl font-bold text-blue-400 mt-2">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-white/5 flex justify-between items-center bg-white/5">
                <h2 class="text-lg font-semibold text-white">Buyurtmalar Tarixi</h2>
                <button onclick="window.location.reload()" class="text-sm bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">🔄 Yangilash</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs uppercase tracking-wider text-slate-400 bg-black/20">
                            <th class="px-6 py-4 font-semibold">ID</th>
                            <th class="px-6 py-4 font-semibold">Mijoz / Raqam</th>
                            <th class="px-6 py-4 font-semibold">Taomlar</th>
                            <th class="px-6 py-4 font-semibold">To'lov & Daromad</th>
                            <th class="px-6 py-4 font-semibold">Manzil</th>
                            <th class="px-6 py-4 font-semibold text-right">Holati</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm">
                        @forelse($orders as $order)
                        <tr class="hover:bg-white/[0.02] transition">
                            <td class="px-6 py-4 font-medium text-white">#{{ $order->id }}<br><span class="text-xs text-gray-500 font-normal">{{ $order->created_at->format('H:i, d M') }}</span></td>
                            <td class="px-6 py-4">
                                <div class="text-white">{{ $order->phone }}</div>
                                <div class="text-xs text-gray-400">Telegram_ID: {{ $order->chat_id }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-300">
                                @php
                                    $items = json_decode($order->items, true) ?: [];
                                    $itemStrings = [];
                                    foreach($items as $name => $qty) { $itemStrings[] = "$name x$qty"; }
                                    echo implode('<br>', $itemStrings);
                                @endphp
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-white font-semibold">${{ $order->total }}</div>
                                <div class="text-xs text-gray-400">{{ $order->payment_type }}</div>
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate text-gray-300">
                                @if($order->location_type == 'map')
                                    @php $loc = json_decode($order->location_data, true); @endphp
                                    <a href="https://maps.google.com/?q={{ $loc['latitude'] }},{{ $loc['longitude'] }}" target="_blank" class="text-blue-400 hover:text-blue-300 hover:underline flex items-center gap-1">
                                        📍 Geofazoviy Koordinata (Xarita)
                                    </a>
                                @else
                                    ⌨️ {{ trim($order->location_data, '"') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @php
                                    $statusClass = 'status-yellow';
                                    if(str_contains($order->status, 'Tayyorlanmoqda') || str_contains($order->status, 'Yolda') || str_contains($order->status, 'Yetkazilmoqda')) $statusClass = 'status-blue';
                                    if(str_contains($order->status, 'Yetkazildi')) $statusClass = 'status-green';
                                    if(str_contains($order->status, 'Bekor qilindi')) $statusClass = 'status-red';
                                @endphp
                                <span class="status-badge inline-block {{ $statusClass }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <div class="text-4xl mb-3">👻</div>
                                Hozircha hech qanday buyurtma mavjud emas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Table -->
        <div class="glass-card rounded-2xl overflow-hidden mt-8">
            <div class="px-6 py-5 border-b border-white/5 bg-white/5">
                <h2 class="text-lg font-semibold text-white">Ro'yxatdan O'tgan Mijozlar Bazasidagilar (Userlar)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 text-xs uppercase tracking-wider text-slate-400 bg-black/20">
                            <th class="px-6 py-4 font-semibold">Ro'yxatdan O'tgan Sana</th>
                            <th class="px-6 py-4 font-semibold">Mijoz Ismi / Username</th>
                            <th class="px-6 py-4 font-semibold">Telegram ID</th>
                            <th class="px-6 py-4 font-semibold">Telefon & To'lov</th>
                            <th class="px-6 py-4 font-semibold">Manzil / Xarita turi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm">
                        @if(isset($bot_users))
                            @forelse($bot_users as $user)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-4 text-gray-400">{{ $user->created_at->format('H:i, d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-medium">{{ $user->first_name ?: 'Noma\'lum' }}</div>
                                    <div class="text-xs text-blue-400">{{ $user->username ? '@'.$user->username : '' }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $user->chat_id }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-semibold">{{ $user->phone }}</div>
                                    <div class="text-xs text-emerald-400">{{ $user->payment_preference }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-300 max-w-xs truncate">
                                    {{ $user->location_data ? '✅ Manzil mavjud' : '❌ Saqlanmagan' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">Hech qanday foydalanuvchi topilmadi</td>
                            </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
