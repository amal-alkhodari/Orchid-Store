document.addEventListener('DOMContentLoaded', function () {
    const accountBtn = document.getElementById('accountBtn');
    const accountMenu = document.getElementById('accountMenu');

    if (!accountBtn || !accountMenu) {
        return;
    }

    function openAccountMenu() {
        accountMenu.hidden = false;
        accountBtn.setAttribute('aria-expanded', 'true');
    }

    function closeAccountMenu() {
        accountMenu.hidden = true;
        accountBtn.setAttribute('aria-expanded', 'false');
    }

    accountBtn.addEventListener('click', function (event) {
        event.stopPropagation();

        if (accountMenu.hidden) {
            openAccountMenu();
        } else {
            closeAccountMenu();
        }
    });

    accountMenu.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        closeAccountMenu();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAccountMenu();
            accountBtn.focus();
        }
    });
});