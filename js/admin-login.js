
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    if (!passwordInput || !toggleButton) return;

    const eyeIcon = `
        <svg viewBox="0 0 24 24" width="22" height="22"
             fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>`;

    const eyeOffIcon = `
        <svg viewBox="0 0 24 24" width="22" height="22"
             fill="none" stroke="currentColor" stroke-width="1.8"
             stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <path d="M3 3 21 21"/>
            <path d="M10.6 5.2A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7s-3.6 7-10 7a10 10 0 0 1-4-.8"/>
            <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
        </svg>`;

    function updateIcon() {
        const isHidden = passwordInput.type === 'password';
        const isArabic = document.documentElement.lang === 'ar';

        toggleButton.innerHTML = isHidden ? eyeOffIcon : eyeIcon;

        const label = isHidden
            ? (isArabic ? 'إظهار كلمة المرور' : 'Show password')
            : (isArabic ? 'إخفاء كلمة المرور' : 'Hide password');

        toggleButton.setAttribute('aria-label', label);
        toggleButton.setAttribute('title', label);
    }

    toggleButton.addEventListener('click', function () {
        passwordInput.type =
            passwordInput.type === 'password' ? 'text' : 'password';

        updateIcon();
    });

    updateIcon();
});
