<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use App\Models\BotUser;

class BotRun extends Command
{
    protected $signature = 'bot:run';
    protected $description = 'Telegram botni uzluksiz ishlash rejimida ishga tushirish (Long Polling)';

    private $restLat = 41.311081;
    private $restLon = 69.279737;

    // LUG'AT
    private $lang = [
        'ask_phone' => ['uz' => "Iltimos, telefon raqamingizni kiriting (Tugmani bosish orqali yoki shunchaki yozib yuborishingiz mumkin):", 'ru' => "Пожалуйста, поделитесь номером телефона (Нажав кнопку или просто введите вручную):"],
        'btn_phone' => ['uz' => "📱 Raqamni yuborish", 'ru' => "📱 Отправить номер"],
        'ask_location' => ['uz' => "Endi yetkazib berish manzilini hal qilamiz:\n\n1. 📍 Pastdagi tugmani bossangiz - hozirgi joylashuvingiz.\n2. 📎 \"Skrepka\" orqali ixtiyoriy xarita.\n3. ⌨️ Yoki shunchaki matn yozing:", 'ru' => "Теперь определим адрес доставки:\n\n1. 📍 Кнопка ниже - текущая геопозиция.\n2. 📎 \"Скрепка\" - любая точка на карте.\n3. ⌨️ Или просто напишите адрес текстом:"],
        'btn_loc' => ['uz' => "📍 Hozirgi joylashuvni yuborish", 'ru' => "📍 Отправить текущее местоположение"],
        'ask_payment' => ['uz' => "💳 To'lov turini tanlang:", 'ru' => "💳 Выберите способ оплаты:"],
        'btn_cash' => ['uz' => "💵 Naqd pul", 'ru' => "💵 Наличные"],
        'done' => ['uz' => "🎉 Hammasi tayyor!\n- /history : Oldingi buyurtmalar\n\nMenyuni ochib buyurtma bering:", 'ru' => "🎉 Всё готово!\n- /history : Ваши прошлые заказы\n\nОткройте меню чтобы сделать заказ:"],
        'btn_menu' => ['uz' => "🍔 Menyu ochish", 'ru' => "🍔 Открыть меню"],
        'rcv_check' => ['uz' => "Sizning chekingiz va buyurtmangiz:\n\n", 'ru' => "Ваш чек и заказ:\n\n"],
        'wait_status' => ['uz' => "⏳ *Holat:* Kutilmoqda (Arizangiz qabul qilindi, endi Admin tasdiqlab, vaqtni kiritishi kutilmoqda...)", 'ru' => "⏳ *Статус:* Ожидание (Ваш заказ принят Администратором, ожидается установка времени...)"],
        'prep_status' => ['uz' => "👨‍🍳 Buyurtmangiz (#%s) oshxonada 🔵 TAYYORLASHGA QABUL QILINDI!\n\n⏳ Tasdiqlangan yetkazish vaqti: **taxminan %s daqiqada yetkiziladi!**", 'ru' => "👨‍🍳 Ваш заказ (#%s) 🔵 ПРИНЯТ К ПРИГОТОВЛЕНИЮ!\n\n⏳ Подтвержденное время доставки: **примерно через %s минут!**"],
        'otw_status' => ['uz' => "🚚 Buyurtma (#%s) 🟢 YO'LGA CHIQDI!\n\nYetib kelgach pastdagi tugmani bosing:", 'ru' => "🚚 Заказ (#%s) 🟢 ОТПРАВЛЕН К ВАМ!\n\nПо прибытии нажмите кнопку ниже:"],
        'btn_rcv' => ['uz' => "✅ Men taomni qabul qilib oldim", 'ru' => "✅ Я получил(а) еду"],
        'rej_status' => ['uz' => "❌ Kechirasiz, buyurtmangiz (#%s) bekor qilindi.", 'ru' => "❌ Извините, ваш заказ (#%s) был отменен."]
    ];

    private function __($key, $langCode) {
        $l = str_contains($langCode, 'Русский') ? 'ru' : 'uz';
        return $this->lang[$key][$l] ?? $key;
    }

    public function handle()
    {
        $this->info("Bot uzluksiz ishlash rejimida yurgizilmoqda...");
        
        $token = env('TELEGRAM_BOT_TOKEN', '8659693975:AAEm1nVIW-nb7x2n0Qqzs24N_Fia1ZLh4CE'); 
        $webAppUrl = env('TELEGRAM_WEBAPP_URL', 'https://unsour-suggestibly-tonie.ngrok-free.dev/webapp');
        
        $offset = 0;

        while (true) {
            $response = Http::timeout(35)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                'offset' => $offset,
                'timeout' => 30
            ]);

            if ($response->successful() && isset($response['result'])) {
                foreach ($response['result'] as $update) {
                    $offset = $update['update_id'] + 1;

                    // 1. ODDY XABARLAR
                    if (isset($update['message'])) {
                        $message = $update['message'];
                        $chatId = $message['chat']['id'];
                        $text = $message['text'] ?? '';
                        $contact = $message['contact'] ?? null;
                        $location = $message['location'] ?? null;

                        $admins = Cache::get('bot_admins', []);

                        if ($text === '/admin') {
                            if (!in_array($chatId, $admins)) {
                                $admins[] = $chatId;
                                Cache::put('bot_admins', $admins);
                                $this->sendMessage($token, $chatId, "[🛡 Admin paneli]\n✅ Tabriklaymiz! Siz tizimga **ADMIN** sifatida qo'shildingiz.");
                            } else {
                                $this->sendMessage($token, $chatId, "[🛡 Admin paneli]\nSiz allaqachon Adminsiz!");
                            }
                            continue;
                        }

                        $userLang = Cache::get("user_{$chatId}_lang", 'uz');
                        $state = Cache::get("user_{$chatId}_state", 'start');

                        if ($text === '/history') {
                            $orders = Order::where('chat_id', $chatId)->orderBy('created_at', 'desc')->limit(5)->get();
                            if ($orders->isEmpty()) {
                                $this->sendMessage($token, $chatId, "Baza bo'sh. / Пусто.");
                            } else {
                                $hist = "📜 *History:*\n\n";
                                foreach ($orders as $o) {
                                    $hist .= "📦 #{$o->id} | {$o->created_at->format('d.m.Y H:i')}\nJami: \${$o->total} | Holat: *{$o->status}*\n------------------\n";
                                }
                                $this->sendMessage($token, $chatId, $hist);
                            }
                            continue;
                        }

                        if ($text === '/start') {
                            $existingUser = BotUser::where('chat_id', $chatId)->first();
                            
                            if ($existingUser) {
                                Cache::put("user_{$chatId}_lang", $existingUser->language);
                                $userLang = $existingUser->language;
                                
                                $locRaw = json_decode($existingUser->location_data, true);
                                if (isset($locRaw['type']) && $locRaw['type'] === 'map') {
                                    $lat = $locRaw['data']['latitude'];
                                    $lon = $locRaw['data']['longitude'];
                                    $locUrl = "https://maps.google.com/?q={$lat},{$lon}";
                                    $locText = "[📍 Xarita (Google Maps)]({$locUrl})";
                                } else {
                                    $locText = $locRaw['data'] ?? 'Kiritilmagan';
                                }
                                
                                $msg = str_contains($userLang, 'Русский') ? 
                                    "👋 С возвращением, {$existingUser->first_name}!\n\n📋 **Ваши текущие данные:**\n📞 Номер: {$existingUser->phone}\n📍 Адрес: {$locText}\n💳 Оплата: {$existingUser->payment_preference}\n\nХотите изменить эти данные?" : 
                                    "👋 Qaytganingiz bilan, {$existingUser->first_name}!\n\n📋 **Sizning ma'lumotlaringiz:**\n📞 Raqam: {$existingUser->phone}\n📍 Manzil: {$locText}\n💳 To'lov: {$existingUser->payment_preference}\n\nUshbu ma'lumotlarni o'zgartirmoqchimisiz?";
                                
                                $keyboard = [
                                    'inline_keyboard' => [
                                        [
                                            ['text' => str_contains($userLang, 'Русский') ? "✅ Нет, оставить как есть" : "✅ Yo'q, hozirgisida qolsin", 'callback_data' => "keep_profile_{$chatId}"]
                                        ],
                                        [
                                            ['text' => str_contains($userLang, 'Русский') ? "🔄 Да, изменить данные" : "🔄 Ha, boshqatdan o'zgartirish", 'callback_data' => "reset_profile_{$chatId}"]
                                        ]
                                    ]
                                ];
                                $this->sendMessage($token, $chatId, $msg, $keyboard);
                                continue;
                            }
                        }

                        if ($text === '/start' || $text === '/restart' || $text === 'restart') {
                            $state = 'language';
                            Cache::put("user_{$chatId}_state", $state);
                            
                            $keyboard = [
                                'keyboard' => [[['text' => '🇺🇿 O\'zbekcha'], ['text' => '🇷🇺 Русский']]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            $this->sendMessage($token, $chatId, "Assalomu alaykum! Yangi profil yaratamiz. Tilni tanlang / Выберите язык:", $keyboard);
                            continue;
                        }

                        if ($state === 'language' && in_array($text, ['🇺🇿 O\'zbekcha', '🇷🇺 Русский'])) {
                            Cache::put("user_{$chatId}_lang", $text);
                            $userLang = $text;
                            $state = 'phone';
                            Cache::put("user_{$chatId}_state", $state);
                            
                            $keyboard = [
                                'keyboard' => [[['text' => $this->__('btn_phone', $userLang), 'request_contact' => true]]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            $this->sendMessage($token, $chatId, $this->__('ask_phone', $userLang), $keyboard);
                            continue;
                        }

                        if ($state === 'phone' && ($contact || (!empty($text) && !str_starts_with($text, '/')))) {
                            // Formatni tozalaymiz
                            $phoneStr = $contact ? $contact['phone_number'] : $text;
                            
                            Cache::put("user_{$chatId}_phone", $phoneStr);
                            $state = 'location';
                            Cache::put("user_{$chatId}_state", $state);

                            $keyboard = [
                                'keyboard' => [[['text' => $this->__('btn_loc', $userLang), 'request_location' => true]]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            $this->sendMessage($token, $chatId, $this->__('ask_location', $userLang), $keyboard);
                            continue;
                        }

                        if ($state === 'location' && ($location || (!empty($text) && !str_starts_with($text, '/')))) {
                            if ($location) {
                                $savedLoc = ['type' => 'map', 'data' => $location];
                            } else {
                                $savedLoc = ['type' => 'text', 'data' => $text];
                            }
                            Cache::put("user_{$chatId}_location", json_encode($savedLoc));
                            
                            $state = 'payment'; 
                            Cache::put("user_{$chatId}_state", $state);
                            
                            $keyboard = [
                                'keyboard' => [
                                    [['text' => $this->__('btn_cash', $userLang)]], 
                                    [['text' => '🔵 Click'], ['text' => '🟢 Payme']]
                                ],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            $this->sendMessage($token, $chatId, $this->__('ask_payment', $userLang), $keyboard);
                            continue;
                        }

                        if ($state === 'payment' && in_array($text, ['💵 Naqd pul', '💵 Наличные', '🔵 Click', '🟢 Payme'])) {
                            Cache::put("user_{$chatId}_payment", $text);
                            $state = 'done';
                            Cache::put("user_{$chatId}_state", $state);

                            BotUser::updateOrCreate(
                                ['chat_id' => $chatId],
                                [
                                    'first_name' => $message['chat']['first_name'] ?? 'Noma\'lum',
                                    'username' => $message['chat']['username'] ?? null,
                                    'phone' => Cache::get("user_{$chatId}_phone", ''),
                                    'language' => $userLang,
                                    'payment_preference' => $text,
                                    'location_data' => Cache::get("user_{$chatId}_location", '')
                                ]
                            );

                            $keyboard = [
                                'keyboard' => [[['text' => $this->__('btn_menu', $userLang), 'web_app' => ['url' => $webAppUrl]]]],
                                'resize_keyboard' => true
                            ];
                            $this->sendMessage($token, $chatId, $this->__('done', $userLang), $keyboard);
                            continue;
                        }

                        // WebAppdan olingan Chek
                        if (isset($message['web_app_data'])) {
                            $data = json_decode($message['web_app_data']['data'], true);
                            if (isset($data['action']) && $data['action'] === 'order') {
                                $itemsTotal = floatval($data['total']);
                                $items = $data['items'];
                                
                                $phone = Cache::get("user_{$chatId}_phone", "+998...");
                                $payment = Cache::get("user_{$chatId}_payment", "Naqd");
                                $loc = json_decode(Cache::get("user_{$chatId}_location", "{}"), true);
                                $deliveryPrice = 0;
                                
                                if (isset($loc['type']) && $loc['type'] === 'map') {
                                    $lat = $loc['data']['latitude'];
                                    $lon = $loc['data']['longitude'];
                                    $locUrl = "https://maps.google.com/?q={$lat},{$lon}";
                                    $locDisplay = "[📍 Xarita / Карта]({$locUrl})";
                                    
                                    $distanceKm = $this->calculateDistance($this->restLat, $this->restLon, $lat, $lon);
                                    if ($distanceKm < 3) {
                                        $deliveryPrice = 1.00;
                                    } else {
                                        $deliveryPrice = 1.00 + ($distanceKm * 0.50);
                                    }
                                } else {
                                    $locDisplay = "`" . ($loc['data'] ?? 'Noma\'lum') . "`";
                                    $deliveryPrice = 2.00; 
                                }
                                
                                $finalTotal = $itemsTotal + $deliveryPrice;

                                $order = Order::create([
                                    'chat_id' => $chatId,
                                    'phone' => $phone,
                                    'location_type' => $loc['type'] ?? 'text',
                                    'location_data' => json_encode($loc['data'] ?? ''),
                                    'items' => json_encode($items),
                                    'payment_type' => $payment,
                                    'items_total' => $itemsTotal,
                                    'delivery_price' => $deliveryPrice,
                                    'total' => $finalTotal,
                                    'status' => '🟡 Kutilmoqda'
                                ]);
                                
                                $textMsg = "🧾 *Buyurtma #{$order->id}*\n\n";
                                foreach ($items as $name => $qty) {
                                    $textMsg .= "• $name - $qty ta\n";
                                }
                                $textMsg .= "\n*Ovqatlar / Еда:* \${$itemsTotal}";
                                $textMsg .= "\n*Yetkazib berish:* \${$deliveryPrice} 🚚";
                                $textMsg .= "\n\n*JAMI HISOB:* \${$finalTotal}💰";
                                $textMsg .= "\n*To'lov turi:* {$payment}";
                                $textMsg .= "\n*Mijoz Telefon:* {$phone}\n*Manzil:* {$locDisplay}";

                                // 1. MIJOZ UCHUN XABAR
                                $this->sendMessage($token, $chatId, "[👤 Mijoz Paneli]\n".$this->__('rcv_check', $userLang).$textMsg."\n\n".$this->__('wait_status', $userLang));

                                // 2. ADMIN UCHUN XABAR
                                $adminKeyboard = [
                                    'inline_keyboard' => [
                                        [
                                            ['text' => "✅ Qabul qilish", 'callback_data' => "acc_{$chatId}_{$order->id}"],
                                            ['text' => "❌ Radd etish", 'callback_data' => "rej_{$chatId}_{$order->id}"]
                                        ]
                                    ]
                                ];

                                $currentAdmins = empty($admins) ? [$chatId] : $admins; // Agar umuman admin bo'lmasa 
                                foreach ($currentAdmins as $adminId) {
                                    $prefix = ($adminId == $chatId) ? "[🛡 Admin Paneli] (Bu o'zingizning buyurtmangiz! Test rejimi!)\n\n" : "[🛡 Admin Paneli]\n\n";
                                    $this->sendMessage($token, $adminId, $prefix . "🛎 YANGI BUYURTMA!\n\n" . $textMsg, $adminKeyboard);
                                }
                            }
                        }
                    }

                    // 2. TUGMALAR (INLINE CALLBACK_QUERY)
                    if (isset($update['callback_query'])) {
                        $callback = $update['callback_query'];
                        $queryId = $callback['id'];
                        $data = $callback['data'];
                        $chatId = $callback['message']['chat']['id'];
                        $messageId = $callback['message']['message_id'];
                        $originalText = $callback['message']['text'] ?? 'Buyurtma paneli';

                        // Foydalanuvchi ma'lumotlarini qoldirish
                        if (str_starts_with($data, 'keep_profile_')) {
                            $clientId = explode('_', $data)[2];
                            $userLang = Cache::get("user_{$clientId}_lang", 'uz');
                            
                            $state = 'done';
                            Cache::put("user_{$clientId}_state", $state);
                            
                            $this->editMessage($token, $chatId, $messageId, str_contains($userLang, 'Русский') ? "✅ Данные сохранены без изменений." : "✅ Ma'lumotlar o'zgarishsiz qoldirildi.", ['inline_keyboard' => []]);
                            
                            $keyboard = [
                                'keyboard' => [[['text' => $this->__('btn_menu', $userLang), 'web_app' => ['url' => $webAppUrl]]]],
                                'resize_keyboard' => true
                            ];
                            $this->sendMessage($token, $clientId, $this->__('done', $userLang), $keyboard);
                            $this->answerCallback($token, $queryId, "Saqlandi");
                            continue;
                        }

                        // Foydalanuvchi ma'lumotlarini yangilash
                        if (str_starts_with($data, 'reset_profile_')) {
                            $clientId = explode('_', $data)[2];
                            
                            $state = 'language';
                            Cache::put("user_{$clientId}_state", $state);
                            
                            $this->editMessage($token, $chatId, $messageId, "🔄", ['inline_keyboard' => []]);
                            
                            $keyboard = [
                                'keyboard' => [[['text' => '🇺🇿 O\'zbekcha'], ['text' => '🇷🇺 Русский']]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            $this->sendMessage($token, $clientId, "Tilni tanlang / Выберите язык:", $keyboard);
                            $this->answerCallback($token, $queryId, "Qaytadan boshlandi");
                            continue;
                        }

                        if (str_starts_with($data, 'acc_')) {
                            list($_, $clientId, $orderId) = explode('_', $data);

                            $newKeyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => "15 min", 'callback_data' => "time_15_{$clientId}_{$orderId}"],
                                        ['text' => "30 min", 'callback_data' => "time_30_{$clientId}_{$orderId}"]
                                    ],
                                    [
                                        ['text' => "45 min", 'callback_data' => "time_45_{$clientId}_{$orderId}"],
                                        ['text' => "1 soat", 'callback_data' => "time_60_{$clientId}_{$orderId}"]
                                    ]
                                ]
                            ];
                            $this->editMessage($token, $chatId, $messageId, "⏱ **Mijozga qancha vaqtda yetkaziladi? Vaqtni tanlang!**\n\n".$originalText, $newKeyboard);
                            $this->answerCallback($token, $queryId, "Vaqtni tanlang!");
                        }

                        if (str_starts_with($data, 'time_')) {
                            $parts = explode('_', $data);
                            $minutes = $parts[1];
                            $clientId = $parts[2];
                            $orderId = $parts[3];

                            Order::where('id', $orderId)->update(['status' => '🔵 Tayyorlanmoqda']);

                            $newKeyboard = [
                                'inline_keyboard' => [
                                    [['text' => "🚚 Yo'lga chiqarish", 'callback_data' => "otw_{$clientId}_{$orderId}"]]
                                ]
                            ];
                            $this->editMessage($token, $chatId, $messageId, "✅ **Tayyorlanmoqda ({$minutes} min)**\nTugagach Yo'lga chiqarishni bosing.\n\n".$originalText, $newKeyboard);
                            
                            $clientLang = Cache::get("user_{$clientId}_lang", 'uz');
                            $msg = sprintf($this->__('prep_status', $clientLang), $orderId, $minutes);
                            $this->sendMessage($token, $clientId, "[👤 Mijoz Paneli]\n" . $msg);
                            
                            $this->answerCallback($token, $queryId, "Vaqt kiritildi: {$minutes} min");
                        }

                        if (str_starts_with($data, 'rej_')) {
                            list($_, $clientId, $orderId) = explode('_', $data);
                            Order::where('id', $orderId)->update(['status' => '🔴 Bekor qilindi']);
                            
                            $this->editMessage($token, $chatId, $messageId, "❌ **Siz bu buyurtmani RAD ETDINGIZ!**\n\n".$originalText, ['inline_keyboard' => []]); 
                            
                            $clientLang = Cache::get("user_{$clientId}_lang", 'uz');
                            $msg = sprintf($this->__('rej_status', $clientLang), $orderId);
                            $this->sendMessage($token, $clientId, "[👤 Mijoz Paneli]\n" . $msg);
                            
                            $this->answerCallback($token, $queryId, "Radd etildi!");
                        }

                        if (str_starts_with($data, 'otw_')) {
                            list($_, $clientId, $orderId) = explode('_', $data);
                            Order::where('id', $orderId)->update(['status' => '🟢 Yetkazilmoqda']);

                            $this->editMessage($token, $chatId, $messageId, "🚚 **Kuryer yuborildi. Mijoz tasdig'i kutilmoqda...**\n\n".$originalText, ['inline_keyboard' => []]); 
                            
                            $clientLang = Cache::get("user_{$clientId}_lang", 'uz');
                            $clientKeyboard = [
                                'inline_keyboard' => [
                                    [['text' => $this->__('btn_rcv', $clientLang), 'callback_data' => "rcv_{$chatId}_{$orderId}"]]
                                ]
                            ];
                            $msg = sprintf($this->__('otw_status', $clientLang), $orderId);
                            $this->sendMessage($token, $clientId, "[👤 Mijoz Paneli]\n" . $msg, $clientKeyboard);
                            
                            $this->answerCallback($token, $queryId, "Mijozga xabar ketdi!");
                        }

                        if (str_starts_with($data, 'rcv_')) {
                            list($_, $adminId, $orderId) = explode('_', $data);
                            Order::where('id', $orderId)->update(['status' => '✅ Yetkazildi']);

                            $this->editMessage($token, $chatId, $messageId, "🎉 Rahmat! Buyurtma (#{$orderId}) ✅ YAKUNLANDI. Yoqimli ishtaha!", ['inline_keyboard' => []]);
                            
                            $this->sendMessage($token, $adminId, "[🛡 Admin Paneli]\n🏁 **Buyurtma (#{$orderId}) G'ALABA!**\nMijoz buyurtmani qo'liga oldi va tasdiqladi.");
                            $this->answerCallback($token, $queryId, "Buyurtma yopildi!");
                        }
                    }
                }
            }
            usleep(100000);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
        return round($d, 1);
    }

    private function sendMessage($token, $chatId, $text, $keyboard = null) {
        $post = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'];
        if ($keyboard) { $post['reply_markup'] = json_encode($keyboard); }
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", $post);
    }

    private function editMessage($token, $chatId, $messageId, $text, $keyboard = null) {
        $post = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text, 'parse_mode' => 'Markdown'];
        if ($keyboard !== null) { $post['reply_markup'] = json_encode($keyboard); }
        Http::post("https://api.telegram.org/bot{$token}/editMessageText", $post);
    }

    private function answerCallback($token, $queryId, $text) {
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $queryId,
            'text' => $text
        ]);
    }
}
