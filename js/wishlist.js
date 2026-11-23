document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'orchid_wishlist';

    function getWishlist() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    function saveWishlist(list) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    }

    function extractProductId(btn, card) {
        const urlId = new URLSearchParams(window.location.search).get('id');
        return (
            btn.dataset.wishlistId ||
            btn.dataset.productId ||
            btn.dataset.id ||
            (card && (card.dataset.productId || card.dataset.id)) ||
            urlId ||
            null
        );
    }

    function syncHearts() {
        const list = getWishlist();
        document.querySelectorAll('button, a').forEach(btn => {
            if (btn.closest('header, nav, .cart-topbar, .cart-header')) return;
            const heartIcon = btn.querySelector('.fa-heart');
            if (!heartIcon) return;

            const card = btn.closest('.product-card, article, .card, .product-item');
            const id = extractProductId(btn, card);

            if (id && list.some(item => String(item.id) === String(id))) {
                btn.classList.add('active', 'liked');
                heartIcon.classList.remove('fa-regular');
                heartIcon.classList.add('fa-solid');
                heartIcon.style.color = '#581D2C';
            }
        });
    }

    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('button, a');
        if (!btn) return;

        if (btn.closest('header, nav, .cart-topbar, .cart-header')) return;

        const heartIcon = btn.querySelector('.fa-heart');
        if (!heartIcon) return;

        e.preventDefault();
        e.stopPropagation();

        const card = btn.closest('.product-card, article, .card, .product-item');
        const scope = card || document;

        const id = extractProductId(btn, card) || String(Date.now());
        const nameEl = scope.querySelector('h1, h2, h3, .product-title, .product-name');
        const priceEl = scope.querySelector('.price, .product-price, .current-price, [class*="price"]');
        const imgEl = scope.querySelector('.main-image img, .product-gallery img, img');

        const name = btn.dataset.name || (nameEl ? nameEl.innerText.trim() : 'منتج أوركيد');
        const rawPrice = btn.dataset.price || (priceEl ? priceEl.innerText.replace(/[^0-9.]/g, '') : '0');
        const image = btn.dataset.image || (imgEl ? imgEl.getAttribute('src') : '');

        let list = getWishlist();
        const existingIdx = list.findIndex(item => String(item.id) === String(id));
        const isRemoving = existingIdx > -1;

        if (isRemoving) {
            list.splice(existingIdx, 1);
            btn.classList.remove('active', 'liked');
            heartIcon.classList.remove('fa-solid');
            heartIcon.classList.add('fa-regular');
            heartIcon.style.color = '';
        } else {
            list.push({
                id: String(id),
                name: name,
                price: parseFloat(rawPrice) || 0,
                image: image
            });
            btn.classList.add('active', 'liked');
            heartIcon.classList.remove('fa-regular');
            heartIcon.classList.add('fa-solid');
            heartIcon.style.color = '#581D2C';
        }

        // إرسال الإعجاب لقاعدة البيانات ليظهر في لوحة الأدمن
        fetch('wishlist_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${encodeURIComponent(id)}&action=${isRemoving ? 'remove' : 'add'}`
        }).catch(() => {});

        saveWishlist(list);
    });

    syncHearts();
});
