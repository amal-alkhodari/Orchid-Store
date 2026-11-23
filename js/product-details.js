
document.addEventListener('DOMContentLoaded', function () {
    // اختيار المقاس
    const sizeButtons = document.querySelectorAll('.size-btn');
    let selectedSize = '';

    const activeSizeBtn = document.querySelector('.size-btn.active');

    if (activeSizeBtn) {
        selectedSize = activeSizeBtn.textContent.trim();
    }

    sizeButtons.forEach(button => {
        button.addEventListener('click', function () {
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            selectedSize = this.textContent.trim();
        });
    });

    // اختيار اللون
    const colorButtons = document.querySelectorAll('.color-btn');
    let selectedColor = null;

    function getColorName(button) {
        const lang = window.ORCHID_LANG || 'ar';

        return lang === 'ar'
            ? button.dataset.colorAr
            : button.dataset.colorEn;
    }

    const activeColorBtn = document.querySelector('.color-btn.active');

    if (activeColorBtn) {
        selectedColor = getColorName(activeColorBtn);
    }

    colorButtons.forEach(button => {
        button.addEventListener('click', function () {
            colorButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            selectedColor = getColorName(this);
        });
    });

    // التحكم بالكمية
    const pdMinus = document.getElementById('pdMinus');
    const pdPlus = document.getElementById('pdPlus');
    const pdQtyNum = document.getElementById('pdQtyNum');

    if (pdMinus && pdPlus && pdQtyNum) {
        pdMinus.addEventListener('click', function () {
            const qty = parseInt(pdQtyNum.textContent, 10) || 1;

            if (qty > 1) {
                pdQtyNum.textContent = qty - 1;
            }
        });

        pdPlus.addEventListener('click', function () {
            const qty = parseInt(pdQtyNum.textContent, 10) || 1;
            pdQtyNum.textContent = qty + 1;
        });
    }

    // إضافة المنتج للسلة
    const addToCartBtn = document.querySelector('.add-to-cart-pd-btn');

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function () {
            const id = this.dataset.id;
            const nameAr = this.dataset.nameAr;
            const nameEn = this.dataset.nameEn;
            const price = parseFloat(this.dataset.price);
            const img = this.dataset.img;
            const quantity = parseInt(pdQtyNum?.textContent, 10) || 1;

            if (colorButtons.length > 0 && !selectedColor) {
                alert(window.ORCHID_LANG === 'ar'
                    ? 'اختاري اللون أولًا.'
                    : 'Please select a color first.');
                return;
            }

            if (typeof window.addToCart === 'function') {
                window.addToCart({
                    id: id,
                    nameAr: nameAr,
                    nameEn: nameEn,
                    price: price,
                    img: img,
                    size: selectedSize,
                    color: selectedColor,
                    quantity: quantity
                });
            } else {
                console.error('addToCart function is not defined in cart.js');
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    const colorBtns = document.querySelectorAll('.color-btn');
    const mainImg = document.getElementById('productDetailImage');

    colorBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const newImg = btn.getAttribute('data-image');
            if (mainImg && newImg) {
                mainImg.src = newImg;
            }
        });
    });
});
