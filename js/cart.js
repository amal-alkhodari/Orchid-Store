document.addEventListener('DOMContentLoaded', () => {
    let cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];

    window.addToCart = function (product) {
        const existingItem = cart.find(item =>
            String(item.id) === String(product.id) &&
            item.size === product.size &&
            (item.color || '') === (product.color || '')
        );

        if (existingItem) {
            existingItem.quantity += product.quantity;
        } else {
            cart.push({
                id: product.id,
                name: currentLang === 'ar' ? product.nameAr : product.nameEn,
                nameAr: product.nameAr,
                nameEn: product.nameEn,
                price: product.price,
                img: product.img || product.image || 'bg-p1',
                size: product.size,
                color: product.color || '',
                quantity: product.quantity
            });
        }

        saveCart();
        renderCart();

        if (cartDrawer) cartDrawer.classList.add('active');
        if (cartOverlay) cartOverlay.classList.add('active');
    };

    const badge = document.querySelector('.badge');
    const drawerCount = document.querySelector('.cart-drawer-count');
    const quickAddButtons = document.querySelectorAll('.quick-add-btn');
    
    const cartBtn = document.querySelector('.cart-btn');
    const cartDrawer = document.getElementById('cartDrawer');
    const cartOverlay = document.getElementById('cartOverlay');
    const closeCartBtn = document.getElementById('closeCart');
    const cartItemsContainer = document.querySelector('.cart-items-container');
    const totalPriceElem = document.querySelector('.total-price');

    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('lang') || document.documentElement.lang || 'ar';
    const emptyMsg = currentLang === 'en' ? 'Your cart is empty' : 'سلة التسوق فارغة';

    const productTranslations = {
        'bg-p1': { ar: 'بلوزة خريفية ناعمة', en: 'Soft Autumn Blouse' },
        'bg-p2': { ar: 'عباية سوداء بلمسة عصرية', en: 'Modern Black Abaya' },
        'bg-p3': { ar: 'فستان سهرة مخملي أنيق', en: 'Elegant Velvet Evening Dress' }
    };

    function saveCart() {
        localStorage.setItem('orchid_cart', JSON.stringify(cart));
    }

    function renderCart() {
        if (!cartItemsContainer) return;

        cartItemsContainer.innerHTML = '';
        let totalCount = 0;
        let totalPrice = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `<p class="empty-cart-msg" style="text-align:center; color:#888; padding:20px;">${emptyMsg}</p>`;
        } else {
            cart.forEach((item, index) => {
                totalCount += item.quantity;
                totalPrice += (Number(item.price) || 0) * Number(item.quantity);

                let displayListName = currentLang === 'en' ? (item.nameEn || item.name) : (item.nameAr || item.name);
                if (productTranslations[item.img]) {
                    displayListName = productTranslations[item.img][currentLang];
                }

                let rawImg = (item.img || item.image || '').trim();
                let imgSrc = rawImg.replace(/^\/+/, '');
                if (imgSrc !== '' && !imgSrc.includes('/') && !imgSrc.startsWith('http') && !imgSrc.startsWith('bg-') && !imgSrc.startsWith('data:')) {
                    imgSrc = 'assets/images/' + imgSrc;
                }

                let imgContent = '';
                if (imgSrc && !imgSrc.startsWith('bg-')) {
                    imgContent = `
                        <img
                            src="${imgSrc}"
                            alt="${currentLang === 'ar' ? (item.nameAr || item.name || '') : (item.nameEn || item.name || '')}"
                            style="width:60px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;"
                            onerror="this.style.display='none';"
                        >
                    `;
                } else {
                    imgContent = `
                        <div
                            class="${imgSrc || 'bg-p1'}"
                            style="width:60px;height:60px;border-radius:8px;background-size:cover;background-position:center;flex-shrink:0;"
                        ></div>
                    `;
                }

                const itemElement = document.createElement('div');
                itemElement.classList.add('cart-item');
                itemElement.style.cssText = "display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border-bottom: 1px solid #eee; gap: 10px;";
                
                let textDirection = currentLang === 'en' ? 'text-align: left;' : 'text-align: right;';

                itemElement.innerHTML = `
                    ${currentLang === 'en' ? imgContent : ''}
                    <div class="cart-item-details" style="flex: 1; ${textDirection}">
                        <h4 style="font-size: 14px; margin-bottom: 5px; color: #333;">${displayListName}</h4>
                        ${item.size ? `<div class="cart-item-size" style="font-size:13px;color:#666;margin:4px 0;">${currentLang === 'ar' ? 'المقاس:' : 'Size:'} ${item.size}</div>` : ''}
                        ${item.color ? `<div class="cart-item-color" style="font-size:13px;color:#666;margin:4px 0;">${currentLang === 'ar' ? 'اللون:' : 'Color:'} ${item.color}</div>` : ''}
                        <span class="cart-item-price" style="color: #4f1325; font-weight: bold;">₪${Number(item.price).toFixed(2)}</span>
                        <div class="cart-item-qty" style="display: flex; align-items: center; margin-top: 5px; ${currentLang === 'en' ? 'justify-content: flex-start;' : 'justify-content: flex-end;'}">
                            <button class="qty-btn minus" data-index="${index}" style="padding: 2px 8px; background: #f0f0f0; border: none; cursor: pointer; border-radius: 4px;">-</button>
                            <span style="margin: 0 10px; font-weight: 600;">${item.quantity}</span>
                            <button class="qty-btn plus" data-index="${index}" style="padding: 2px 8px; background: #f0f0f0; border: none; cursor: pointer; border-radius: 4px;">+</button>
                        </div>
                    </div>
                    ${currentLang === 'ar' ? imgContent : ''}
                    <button class="remove-item" data-index="${index}" style="background: none; border: none; color: #ff5c5c; cursor: pointer; font-size: 16px;"><i class="fa-solid fa-trash-can"></i></button>
                `;
                cartItemsContainer.appendChild(itemElement);
            });
        }

        if (badge) {
            badge.textContent = totalCount;
            badge.style.display = totalCount === 0 ? 'none' : 'flex';
        }
        if (drawerCount) drawerCount.textContent = totalCount;
        if (totalPriceElem) totalPriceElem.textContent = `₪${totalPrice.toFixed(2)}`;

        const fullCartLink = document.getElementById('fullCartLink');
        if (fullCartLink) {
            fullCartLink.href = `cart.php?lang=${currentLang}`;
        }
        document.querySelectorAll('#cartDrawer [data-ar]').forEach(el => {
            el.textContent = currentLang === 'en' ? el.getAttribute('data-en') : el.getAttribute('data-ar');
        });

        const checkoutLink = document.getElementById('checkoutLink');
        if (checkoutLink) {
            checkoutLink.href = `checkout.php?lang=${currentLang}`;
        }

        saveCart();
    }

    if (cartItemsContainer) {
        cartItemsContainer.addEventListener('click', (e) => {
            const btnPlus = e.target.closest('.plus');
            const btnMinus = e.target.closest('.minus');
            const btnRemove = e.target.closest('.remove-item');

            if (btnPlus) {
                const index = btnPlus.getAttribute('data-index');
                cart[index].quantity++;
                renderCart();
            }
            if (btnMinus) {
                const index = btnMinus.getAttribute('data-index');
                if (cart[index].quantity > 1) {
                    cart[index].quantity--;
                } else {
                    cart.splice(index, 1);
                }
                renderCart();
            }
            if (btnRemove) {
                const index = btnRemove.getAttribute('data-index');
                cart.splice(index, 1);
                renderCart();
            }
        });
    }

    if (cartBtn) {
        cartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (cartDrawer) cartDrawer.classList.add('active');
            if (cartOverlay) cartOverlay.classList.add('active');
        });
    }

    const closeDrawer = () => {
        if (cartDrawer) cartDrawer.classList.remove('active');
        if (cartOverlay) cartOverlay.classList.remove('active');
    };

    if (closeCartBtn) closeCartBtn.addEventListener('click', closeDrawer);
    if (cartOverlay) cartOverlay.addEventListener('click', closeDrawer);

    quickAddButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const card = button.closest('.product-card');
            if (!card) return;

            const titleElem = card.querySelector('.product-title, h3, h4, .title');
            const productName = titleElem ? titleElem.textContent.trim() : 'منتج';

            const priceElem = card.querySelector('.price, .product-price, .new-price, .current-price');
            const priceText = priceElem ? priceElem.textContent : '0';
            const price = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
            
            const realImg = button.getAttribute('data-image') || button.getAttribute('data-product-image') || card.querySelector('img')?.getAttribute('src') || 'bg-p1';
            const productId = Date.now(); 

            const existingItem = cart.find(item => item.name === productName && item.img === realImg);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ 
                    id: productId, 
                    name: productName, 
                    nameAr: productName,
                    nameEn: productName,
                    price: price, 
                    img: realImg, 
                    quantity: 1 
                });
            }

            renderCart();

            if (cartDrawer) cartDrawer.classList.add('active');
            if (cartOverlay) cartOverlay.classList.add('active');
        });
    });

    renderCart();
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.category-card').forEach(card => {
        const container = card.querySelector('.category-images-container');
        if (!container) return;
        
        const images = container.querySelectorAll('img');
        const totalImages = images.length;
        if (totalImages === 0) return;
        let currentIndex = 0;

        setInterval(() => {
            currentIndex = (currentIndex + 1) % totalImages;
            const offset = currentIndex * 100;
            const isRTL = document.documentElement.dir === 'rtl';

            if (isRTL) {
                container.style.transform = `translateX(${offset}%)`;
            } else {
                container.style.transform = `translateX(-${offset}%)`;
            }
        }, 3000);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('cartPageContent')) {
        renderFullCartPage();
    }
});

function renderFullCartPage() {
    const container = document.getElementById('cartPageContent');
    let cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
    const currentLang = window.ORCHID_LANG || 'ar';

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart-message" style="text-align:center; padding:50px 20px;">
                <i class="fa-solid fa-cart-shopping" style="font-size:52px; color:#D8C2B5; margin-bottom:15px;"></i>
                <h3 style="color:#581D2C; font-size:22px; font-weight:800; margin-bottom:10px;">${currentLang === 'ar' ? 'سلة التسوق فارغة حالياً' : 'Your cart is empty'}</h3>
                <p style="color:#7C6A70; font-size:14px; margin-bottom:10px;">${currentLang === 'ar' ? 'لم تقومي بإضافة أي منتجات إلى السلة بعد.' : 'You have not added any products to your cart yet.'}</p>
            </div>
        `;
        return;
    }

    let subtotal = 0;
    let html = `<div class="cart-items-cards-wrapper" style="display: flex; flex-direction: column; gap: 16px;">`;

    cart.forEach((item, index) => {
        let itemTotal = (Number(item.price) || 0) * Number(item.quantity);
        subtotal += itemTotal;
        let itemName = currentLang === 'ar' ? (item.nameAr || item.name) : (item.nameEn || item.name);

        let rawImg = (item.img || item.image || '').trim();
        let imgSrc = rawImg.replace(/^\/+/, '');
        if (imgSrc !== '' && !imgSrc.includes('/') && !imgSrc.startsWith('http') && !imgSrc.startsWith('bg-') && !imgSrc.startsWith('data:')) {
            imgSrc = 'assets/images/' + imgSrc;
        }

        const fallbackBox = `<div style="width:75px; height:85px; border-radius:12px; background:#FAF1F3; border:1px solid #E8DFDC; display:grid; place-items:center; color:#581D2C; font-size:22px; flex-shrink:0;"><i class="fa-solid fa-shirt"></i></div>`;

        html += `
            <div class="cart-card-item" style="background:#FFFFFF; border:1px solid #E8DFDC; border-radius:18px; padding:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; position:relative; box-shadow:0 6px 18px rgba(88,29,44,0.04);">
                
                <button onclick="removePageCartItem(${index})" style="position:absolute; top:14px; left:14px; background:#FFF2F2; border:none; color:#D93848; width:34px; height:34px; border-radius:50%; cursor:pointer; display:grid; place-items:center; font-size:14px;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>

                <div style="display:flex; align-items:center; gap:16px; flex:1;">
                    ${imgSrc && !imgSrc.startsWith('bg-')
                        ? `<img src="${imgSrc}" alt="${itemName}" style="width:75px; height:85px; object-fit:cover; border-radius:12px; border:1px solid #E8DFDC; flex-shrink:0;" onerror="this.outerHTML='<div style=\\'width:75px;height:85px;border-radius:12px;background:#FAF1F3;border:1px solid #E8DFDC;display:grid;place-items:center;color:#581D2C;font-size:22px;flex-shrink:0;\\'><i class=\\'fa-solid fa-shirt\\'>\\/i><\\/div>'">`
                        : fallbackBox
                    }
                    <div>
                        <h4 style="margin:0 0 6px; font-size:16px; font-weight:800; color:#2B181E;">${itemName}</h4>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:6px;">
                            ${item.size ? `<span style="background:#F5EAEB; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; color:#581D2C;">${currentLang === 'ar' ? 'المقاس:' : 'Size:'} ${item.size}</span>` : ''}
                            ${item.color ? `<span style="background:#FAF7F4; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; color:#7C6A70; border:1px solid #E8DFDC;">${currentLang === 'ar' ? 'اللون:' : 'Color:'} ${item.color}</span>` : ''}
                        </div>
                        <span style="font-size:13px; color:#7C6A70; font-weight:600;">$${Number(item.price).toFixed(2)}</span>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:10px;">
                    <span style="font-size:17px; font-weight:900; color:#581D2C;">$${itemTotal.toFixed(2)}</span>
                    <div style="display:inline-flex; align-items:center; background:#FAF7F4; border:1px solid #E8DFDC; border-radius:50px; padding:3px 10px; gap:10px;">
                        <button onclick="updatePageCartQty(${index}, -1)" style="background:none; border:none; color:#581D2C; font-weight:bold; cursor:pointer; font-size:15px;">-</button>
                        <span style="font-weight:800; font-size:14px; color:#2B181E;">${item.quantity}</span>
                        <button onclick="updatePageCartQty(${index}, 1)" style="background:none; border:none; color:#581D2C; font-weight:bold; cursor:pointer; font-size:15px;">+</button>
                    </div>
                </div>

            </div>
        `;
    });

    html += `</div>`;

    html += `
        <div class="cart-summary-box" style="margin-top:24px; display:flex; justify-content:space-between; align-items:center; padding:22px 26px; background:#FFFFFF; border:1px solid #E8DFDC; border-radius:18px; box-shadow:0 8px 22px rgba(88,29,44,0.05);">
            <div style="font-size:18px; font-weight:800; color:#2B181E;">
                ${currentLang === 'ar' ? 'المجموع الكلي:' : 'Total:'} <span style="color:#581D2C; font-size:22px; font-weight:900;">$${subtotal.toFixed(2)}</span>
            </div>
            <button type="button" class="btn-checkout" style="width:auto; padding:13px 32px; background:#581D2C; color:#fff; border:none; border-radius:50px; cursor:pointer; font-weight:800; font-size:14px;">
                ${currentLang === 'ar' ? 'إتمام الطلب' : 'Checkout'}
            </button>
        </div>
    `;

    container.innerHTML = html;
}

window.updatePageCartQty = function(index, change) {
    let cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
    if (cart[index]) {
        cart[index].quantity += change;
        if (cart[index].quantity <= 0) {
            cart[index].quantity = 1;
        }
        localStorage.setItem('orchid_cart', JSON.stringify(cart));
        renderFullCartPage();
    }
}

window.removePageCartItem = function(index) {
    let cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
    cart.splice(index, 1);
    localStorage.setItem('orchid_cart', JSON.stringify(cart));
    renderFullCartPage();
}

document.addEventListener('click', function(e) {
    const checkoutBtn = e.target.closest('.btn-checkout');
    if (checkoutBtn) {
        let cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
        if (cart.length === 0) {
            alert(window.ORCHID_LANG === 'ar' ? 'السلة فارغة!' : 'Cart is empty!');
            return;
        }
        const currentLang = window.ORCHID_LANG || window.ORCHID_LANG_PAGE || 'ar';
        window.location.href = `checkout.php?lang=${currentLang}`;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    renderCheckoutSummary();
});

function renderCheckoutSummary() {
    const listContainer = document.getElementById('checkoutItemsList');
    const totalContainer = document.getElementById('checkoutTotalAmount');

    if (!listContainer || !totalContainer) return;

    let cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
    const currentLang = window.ORCHID_LANG || 'ar';

    if (cart.length === 0) {
        listContainer.innerHTML = `
            <p style="color:#777; text-align:center;">
                ${currentLang === 'ar' ? 'السلة فارغة' : 'Cart is empty'}
            </p>
        `;
        totalContainer.textContent = '$0.00';
        return;
    }

    let html = '';
    let total = 0;

    cart.forEach(item => {
        let itemTotal = (Number(item.price) || 0) * Number(item.quantity);
        total += itemTotal;

        let itemName = currentLang === 'ar'
            ? (item.nameAr || item.name)
            : (item.nameEn || item.name);

        let sizeText = currentLang === 'ar' ? 'المقاس:' : 'Size:';
        let colorText = currentLang === 'ar' ? 'اللون:' : 'Color:';
        let quantityText = currentLang === 'ar' ? 'الكمية:' : 'Qty:';

        html += `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:14px; border-bottom:1px solid #f5f5f5; padding-bottom:8px;">
                <div>
                    <span style="font-weight:600; display:block; color:#333;">${itemName}</span>
                    <span style="color:#777; font-size:12px;">
                        ${sizeText} ${item.size || '-'} | ${colorText} ${item.color || '-'} | ${quantityText} ${item.quantity}
                    </span>
                </div>
                <span style="font-weight:600; color:#581D2C;">$${itemTotal.toFixed(2)}</span>
            </div>
        `;
    });

    listContainer.innerHTML = html;
    totalContainer.textContent = `$${total.toFixed(2)}`;
}

window.submitOrder = async function(e) {
    e.preventDefault();

    const currentLang = window.ORCHID_LANG || 'ar';
    const cart = JSON.parse(localStorage.getItem('orchid_cart') || '[]');

    const showMessage = (ar, en) => {
        alert(currentLang === 'ar' ? ar : en);
    };

    if (!Array.isArray(cart) || cart.length === 0) {
        showMessage('السلة فارغة!', 'Your cart is empty!');
        return;
    }

    const fullNameInput = document.getElementById('fullName');
    const phoneInput = document.getElementById('phone');
    const addressInput = document.getElementById('address');

    if (!fullNameInput || !phoneInput || !addressInput) return;

    const fullName = fullNameInput.value.trim();
    const phone = phoneInput.value.trim();
    const address = addressInput.value.trim();

    const payment = document.querySelector('input[name="payment"]:checked')?.value || 'cod';

    if (!fullName || !phone || !address) {
        showMessage('يرجى تعبئة جميع البيانات المطلوبة.', 'Please fill in all required fields.');
        return;
    }

    const submitButton = document.querySelector('#checkoutForm button[type="submit"]');
    if (submitButton) submitButton.disabled = true;

    try {
        const response = await fetch('save_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                fullName,
                phone,
                address,
                payment,
                items: cart
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || (currentLang === 'ar' ? 'تعذر حفظ الطلب.' : 'Could not save the order.'));
        }

        localStorage.removeItem('orchid_cart');

        showMessage(
            result.message || 'تم حفظ طلبك بنجاح!',
            result.message || 'Your order was saved successfully!'
        );

        window.location.href = 'orders.php?lang=' + encodeURIComponent(currentLang);

    } catch (error) {
        showMessage(
            error.message || 'حدث خطأ أثناء حفظ الطلب.',
            error.message || 'An error occurred while saving your order.'
        );
    } finally {
        if (submitButton) submitButton.disabled = false;
    }
}