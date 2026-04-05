<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>FastFood Delivery - Web App</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #181819;
            --text-color: #ffffff;
            --btn-add: #f8a917;
            --btn-add-text: #ffffff;
            --btn-minus: #e64d44;
        }
        body {
            background-color: var(--tg-theme-bg-color, var(--bg-color));
            color: var(--tg-theme-text-color, var(--text-color));
            font-family: 'Roboto', -apple-system, sans-serif;
            margin: 0; padding: 0;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        .app-container {
            padding: 20px 12px;
            padding-bottom: 90px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px 8px;
        }
        .product-item {
            display: flex; flex-direction: column; align-items: center;
        }
        .product-image-container {
            position: relative; width: 76px; height: 76px; margin-bottom: 8px;
        }
        .product-image {
            width: 100%; height: 100%; object-fit: contain;
            transition: transform 0.1s;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }
        .product-image-container:active .product-image { transform: scale(0.92); }
        .product-badge {
            position: absolute; top: -6px; right: -6px;
            background: var(--btn-add); color: #fff;
            font-size: 13px; font-weight: 700; width: 20px; height: 20px;
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            opacity: 0; transform: scale(0);
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 10;
        }
        .product-badge.visible { opacity: 1; transform: scale(1); }
        .product-info {
            font-size: 12px; font-weight: 500; text-align: center; margin-bottom: 10px; white-space: nowrap;
        }
        .product-controls { width: 100%; max-width: 80px; height: 28px; }
        .btn {
            border: none; border-radius: 6px; font-weight: 700; color: var(--btn-add-text);
            cursor: pointer; font-family: inherit; display: flex; justify-content: center; align-items: center;
        }
        .btn:active { filter: brightness(0.8); }
        .btn-add { width: 100%; height: 100%; background-color: var(--btn-add); font-size: 12px; letter-spacing: 0.5px; }
        .qty-controls { display: none; width: 100%; height: 100%; justify-content: space-between; }
        .qty-controls.active { display: flex; }
        .btn-qty { width: 45%; height: 100%; font-size: 18px; }
        .btn-minus { background-color: var(--btn-minus); }
        .btn-plus { background-color: var(--btn-add); }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="products-grid" id="products-grid"></div>
        <div class="cart-view" id="cart-view" style="display:none;"></div>
    </div>
    <script>
        const tg = window.Telegram.WebApp;
        tg.expand();
        tg.setHeaderColor('bg_color');

        const products = [
            { id: 1, name: "Burger", price: 4.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Hamburger/3D/hamburger_3d.png" },
            { id: 2, name: "Fries", price: 1.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/French%20fries/3D/french_fries_3d.png" },
            { id: 3, name: "Hotdog", price: 3.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Hot%20dog/3D/hot_dog_3d.png" },
            { id: 4, name: "Taco", price: 3.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Taco/3D/taco_3d.png" },
            { id: 5, name: "Pizza", price: 7.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Pizza/3D/pizza_3d.png" },
            { id: 6, name: "Donut", price: 1.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Doughnut/3D/doughnut_3d.png" },
            { id: 7, name: "Popcorn", price: 1.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Popcorn/3D/popcorn_3d.png" },
            { id: 8, name: "Coke", price: 1.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Cup%20with%20straw/3D/cup_with_straw_3d.png" },
            { id: 9, name: "Cake", price: 10.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Shortcake/3D/shortcake_3d.png" },
            { id: 10, name: "Sushi", price: 5.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Sushi/3D/sushi_3d.png" },
            { id: 11, name: "Salad", price: 4.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Green%20salad/3D/green_salad_3d.png" },
            { id: 12, name: "Sandwich", price: 3.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Sandwich/3D/sandwich_3d.png" },
            { id: 13, name: "Burrito", price: 4.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Burrito/3D/burrito_3d.png" },
            { id: 14, name: "Pancakes", price: 3.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Pancakes/3D/pancakes_3d.png" },
            { id: 15, name: "Ice Cream", price: 2.49, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Soft%20ice%20cream/3D/soft_ice_cream_3d.png" },
            { id: 16, name: "Pretzel", price: 2.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Pretzel/3D/pretzel_3d.png" },
            { id: 17, name: "Coffee", price: 1.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Hot%20beverage/3D/hot_beverage_3d.png" },
            { id: 18, name: "Waffle", price: 2.99, image: "https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Waffle/3D/waffle_3d.png" }
        ];

        let cart = {};
        const productsGrid = document.getElementById('products-grid');

        function renderProducts() {
            productsGrid.innerHTML = '';
            products.forEach(product => {
                const qty = cart[product.id] || 0;
                const item = document.createElement('div');
                item.className = 'product-item';
                item.innerHTML = `
                    <div class="product-image-container">
                        <span class="product-badge ${qty > 0 ? 'visible' : ''}">${qty}</span>
                        <img src="${product.image}" class="product-image" alt="${product.name}">
                    </div>
                    <div class="product-info">${product.name} · $${product.price.toFixed(2)}</div>
                    <div class="product-controls">
                        ${qty === 0 ? `<button class="btn btn-add" onclick="updateCart(${product.id}, 1)">ADD</button>` : 
                        `<div class="qty-controls active">
                            <button class="btn btn-qty btn-minus" onclick="updateCart(${product.id}, -1)">-</button>
                            <button class="btn btn-qty btn-plus" onclick="updateCart(${product.id}, 1)">+</button>
                        </div>`}
                    </div>
                `;
                productsGrid.appendChild(item);
            });
        }

        window.updateCart = function(productId, change) {
            if (!cart[productId]) cart[productId] = 0;
            cart[productId] += change;
            if (cart[productId] <= 0) delete cart[productId];
            if (tg.HapticFeedback) tg.HapticFeedback.impactOccurred('light');
            updateUI();
        };

        function calculateTotal() {
            let total = 0, count = 0;
            for (const [id, qty] of Object.entries(cart)) {
                const p = products.find(p => p.id == id);
                if (p) { total += p.price * qty; count += qty; }
            }
            return { total, count };
        }

        let inCartView = false;

        function updateUI() {
            if (!inCartView) {
                renderProducts();
            }
            const { total, count } = calculateTotal();
            if (count > 0) {
                if (!inCartView) {
                    tg.MainButton.setText("VIEW ORDER");
                } else {
                    tg.MainButton.setText("TASDIQLASH 🟢 ($" + total.toFixed(2) + ")");
                }
                tg.MainButton.color = tg.themeParams.button_color || '#31b545';
                tg.MainButton.textColor = tg.themeParams.button_text_color || '#ffffff';
                if (!tg.MainButton.isVisible) tg.MainButton.show();
            } else {
                tg.MainButton.hide();
                if (inCartView) backToMenu();
            }
        }

        function renderCartView() {
            let html = '<h2 style="margin-top: 0;">Savatcha</h2>';
            const { total } = calculateTotal();
            for (const [id, qty] of Object.entries(cart)) {
                const product = products.find(p => p.id == id);
                if (product) {
                    html += `
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; align-items:center; background:rgba(255,255,255,0.05); padding:10px; border-radius:8px;">
                        <div style="display:flex; align-items:center;">
                            <img src="${product.image}" style="width:40px; height:40px; margin-right:12px;">
                            <div>
                                <div style="font-weight:bold;">${product.name}</div>
                                <div style="font-size:12px; color:#aaa;">$${product.price.toFixed(2)} x ${qty}</div>
                            </div>
                        </div>
                        <div style="font-weight:bold;">$${(product.price * qty).toFixed(2)}</div>
                    </div>`;
                }
            }
            html += `<hr style="border-color:rgba(255,255,255,0.1); margin:16px 0;"><div style="display:flex; justify-content:space-between; font-size:18px; font-weight:bold;"><span>Jami:</span><span>$${total.toFixed(2)}</span></div>`;
            document.getElementById('cart-view').innerHTML = html;
        }

        function backToMenu() {
            inCartView = false;
            document.getElementById('cart-view').style.display = 'none';
            document.getElementById('products-grid').style.display = 'grid';
            tg.BackButton.hide();
            updateUI();
        }

        Telegram.WebApp.onEvent('backButtonClicked', backToMenu);

        Telegram.WebApp.onEvent('mainButtonClicked', function() {
            const { total } = calculateTotal();
            
            if (!inCartView) {
                // Savatcha ko'rinishiga o'tish
                inCartView = true;
                document.getElementById('products-grid').style.display = 'none';
                document.getElementById('cart-view').style.display = 'block';
                renderCartView();
                tg.BackButton.show();
                updateUI();
                return;
            }

            // Haqiqiy jo'natish (Tasdiqlash)
            const orderItems = {};
            for (const [id, qty] of Object.entries(cart)) {
                const itemName = products.find(p => p.id == id).name;
                orderItems[itemName] = qty;
            }
            
            tg.sendData(JSON.stringify({
                action: 'order',
                items: orderItems,
                total: total.toFixed(2)
            }));
        });

        updateUI();
    </script>
</body>
</html>
