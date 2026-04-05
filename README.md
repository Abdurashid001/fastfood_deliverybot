# Fastfood_deliverybot - Telegram Bot & Mini App (Laravel 11)

Ushbu loyiha zamonaviy va yuqori tezlikda ishlovchi to'liq funksional ovqat yetkazib berish (Food Delivery) Telegram Boti hisoblanadi. Dastur mukammal UI (User Interface) ni taqdim etuvchi React/JS aralash `Telegram Mini Web App` hamda Laravel 11 asosida ishlovchi kuchli Admin paneliga va Long Polling tizimiga ega.

## 🚀 Asosiy Imkoniyatlari (Features)

1. **Telegram Web App va 3D Dizayn**
   - Foydalanuvchilar oddiy tugmalarni bosib o'tirmaydi, balki to'g'ridan to'g'ri chiroyli interfeysli Web Mini-App ga kirib mahsulotlarni ko'radi. Savatchaga soladi va dinamik hisoblarni amalga oshiradi. 
2. **Kengaytirilgan Ro'yxatdan O'tish**
   - Ikki xil til: 🇺🇿 O'zbek va 🇷🇺 Rus tillarini avtomatik qo'llab-quvvatlaydi.
   - Manzil uchun GPS (Location) karta xaritasi yuborish imkoniyati.
   - Telefon raqamni Contact-ulashib yoki qo'lda kiritish orqali olish.
3. **Admin va Mijoz Paneli**
   - Bitta xat orqali Admin real-vaqt rejimida Mijoz buyurtmasini "Qabul qiladi", Unga "Tayyorlanish Daqiqasini" beradi va "Yo'lga Chiqaradi". Mantiq to'liq bir-biriga (Webhooksiz) uzluksiz bog'langan.
4. **Masofaviy Yetkazib Berish Narxi (Delivery Calculator)**
   - Mijoz yuborgan xarita manzili va Restoran manzili o'rtasidagi masofa Haversine formulyasi asosida topiladi va shunga qarab dinamik yetkazib berish narxi (Delivery Price) chekga qo'shiladi. 
5. **Dashboard & Tarix (History)**
   - Botdagi mijoz `/history` deya barcha qilingan xaridlarni va holatlarini kuzata oladi.
   - Web uchun khususiy nafis Admin Panel qilingan (`http://localhost:8000/admin`), unda barcha mijozlar, daromad grafiklari va foyda to'xtovsiz jonli ko'rsatilib turiladi.

## 🛠 Texnologiyalar
- **Backend:** Laravel 11 (PHP 8.2+)
- **Database:** SQLite (yoki MySQL)
- **Frontend (Web App):** JavaScript (Telegram Web App API), Vanilla CSS
- **Admin Panel UI:** TailwindCSS, Glassmorphism dizayn
- **Bot Method:** Long Polling (Cheksiz ishlash rejimi - `bot:run`)

---

## 💻 Mahalliy Serverda Ishga Tushirish (Local Development)

Quyida loyihani o'zingizning shaxsiy kompyuteringizda qanday qilib ishga tushirishingiz mumkinligi keltirilgan:

### 1-qadam: Talablar (Requirements)
Sizga kerak bo'ladi:
- PHP >= 8.2
- Composer
- Ngrok (Bot Localhost'dagi WebApp ni topishi uchun zarur)

### 2-qadam: Sozlamalar va `.env`
Barcha kerakli kutubxonalarni o'rnating:
```bash
composer install
```
Loyihadagi `.env.example` nusxasidan `.env` nomli asosiy fayl yarating.
Va unda Telegram Tokeningiz va WebApp yuzini to'g'irlang:
```env
TELEGRAM_BOT_TOKEN="BOTFATHER_DAN_OLINGAN_TOKENINGIZ_SHU_YERDA"
TELEGRAM_WEBAPP_URL="https://[ngrok-ulashingiz].ngrok-free.dev/webapp"
```

### 3-qadam: Jadval (Baza) yaratish
Buyurtmalar va Foydalanuvchilarni saqlash uchun migratsiyalarni ishlating:
```bash
php artisan migrate
```

### 4-qadam: Botni Qanday yurgizish kerak (2 ta oyna orqali)?
Siz ayni vaqtda ham Web serverni, ham Bot Polling komandasini parallel ishga tushirishingiz shart. Buning uchun 2 ta xitoy oyna terminal ochasiz:

**1-Terminal:** (U Web oynani, JS Web APPni va ngrokni ishlatish uchun)
```bash
php artisan serve
```
**2-Terminal:** (U Telegram API bn ulanib, sekundiga kelgan so'rovni tinglash uchun)
```bash
php artisan bot:run
```

Tayyor! `t.me/SizningBotingiz` manziliga kiring va barchasini to'liq tekshiring. Bot Admin panelini ko'rish uchun serverdagi `localhost:8000/admin` sahifasiga tashrif buyuring.

---

## 🌍 Haqiqiy VPS Serverga Yuklash (Deployment)

Faqatgina to'laqonli Virtual Maxsus Server (VPS) da (Masalan: *Oracle Cloud, DigitalOcean, Hostinger*) bot bexato ishlay oladi. Vercel yoki InfinityFree kabi maxsus bo'lmagan xosting tarmoqlarida bu xil kompyuterda mutlaqo ishlata olmaysiz!

VPS serverga loyihani olib bo'lgach, botingiz terminal yopilsa-da kechayu-kunduz ishlab yotishi (background-worker) uchun **Supervisor** degan kutubxonani ornatasiz.

**Ubuntu Linux uchun qisqacha ko'rsatma:**

1) `sudo apt install supervisor`
2) Sozlama yaratamiz: `sudo nano /etc/supervisor/conf.d/foodbot.conf`
3) Fayl ichiga yozasiz:
   ```ini
   [program:foodbot]
   process_name=%(program_name)s
   command=php /var/www/food_bot/artisan bot:run
   autostart=true
   autorestart=true
   user=root
   redirect_stderr=true
   stdout_logfile=/var/www/food_bot/storage/logs/bot.log
   ```
4) Keyin uni ishga tushiramiz:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start foodbot
   ```
Ana endi terminalni yopib ketsangiz ham kompyuteringiz tun-u kun xaridlar qabul qilishga shay! 🚀
