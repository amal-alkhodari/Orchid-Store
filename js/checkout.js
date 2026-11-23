
document.addEventListener('DOMContentLoaded', function () {
    renderCheckoutSummary();

    const checkoutForm = document.getElementById('checkoutForm');

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', submitOrder);
    }
});

function getCheckoutLanguage() {
    return document.body.dataset.lang || 'ar';
}

function renderCheckoutSummary() {
    const listContainer = document.getElementById('checkoutItemsList');
    const totalContainer = document.getElementById('checkoutTotalAmount');

    if (!listContainer || !totalContainer) {
        return;
    }

    let cart = [];

    try {
        cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
    } catch (error) {
        cart = [];
    }

    const currentLang = getCheckoutLanguage();

    if (cart.length === 0) {
        listContainer.textContent = listContainer.dataset.emptyText;
        listContainer.style.color = '#777';
        listContainer.style.textAlign = 'center';
        totalContainer.textContent = '$0.00';
        return;
    }

    listContainer.style.color = '';
    listContainer.style.textAlign = '';

    let total = 0;

    listContainer.replaceChildren();

    cart.forEach(function (item) {
        const price = Number(item.price) || 0;
        const quantity = Math.max(0, Number(item.quantity) || 0);
        const itemTotal = price * quantity;

        total += itemTotal;

        const itemName = currentLang === 'ar'
            ? (item.nameAr || item.name || '')
            : (item.nameEn || item.name || '');

        const row = document.createElement('div');
        row.style.cssText =
            'display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:14px; border-bottom:1px solid #f5f5f5; padding-bottom:8px;';

        const details = document.createElement('div');

        const name = document.createElement('span');
        name.textContent = itemName;
        name.style.cssText = 'font-weight:600; display:block; color:#333;';

        const info = document.createElement('span');
        info.style.cssText = 'color:#777; font-size:12px;';
        info.textContent =
            `${listContainer.dataset.sizeText} ${item.size || '-'} | ` +
            `${listContainer.dataset.qtyText} ${quantity}`;

        const priceElement = document.createElement('span');
        priceElement.textContent = `$${itemTotal.toFixed(2)}`;
        priceElement.style.cssText = 'font-weight:600; color:#800080;';

        details.appendChild(name);
        details.appendChild(info);

        row.appendChild(details);
        row.appendChild(priceElement);

        listContainer.appendChild(row);
    });

    totalContainer.textContent = `$${total.toFixed(2)}`;
}

function submitOrder(event) {
    event.preventDefault();

    let cart = [];

    try {
        cart = JSON.parse(localStorage.getItem('orchid_cart')) || [];
    } catch (error) {
        cart = [];
    }

    const currentLang = getCheckoutLanguage();

    if (cart.length === 0) {
        alert(
            currentLang === 'ar'
                ? 'لا توجد منتجات في السلة!'
                : 'Your cart is empty!'
        );
        return;
    }

    const paymentMethod = document.querySelector(
        'input[name="payment"]:checked'
    );

    if (!paymentMethod) {
        alert(
            currentLang === 'ar'
                ? 'يرجى اختيار طريقة الدفع.'
                : 'Please select a payment method.'
        );
        return;
    }

    /*
     * يحافظ هذا الجزء على سلوك التأكيد الحالي.
     * لا ينفذ دفعًا إلكترونيًا أو يحفظ الطلب في قاعدة بيانات.
     */
    localStorage.removeItem('orchid_cart');

    const successMsg = currentLang === 'ar'
        ? 'تم استلام طلبك بنجاح! شكراً لتسوقك معنا في متجر أوركيد 🌸'
        : 'Order placed successfully! Thank you for shopping with Orchid 🌸';

    alert(successMsg);

    window.location.href = `index.php?lang=${currentLang}`;
}
