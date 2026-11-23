
<?php
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../remember_me.php';

// Revoke the administrator's remember-me token.
forgetRememberToken($pdo, 'admin');

// End only the administrator's session.
// Preserve the regular user's session if it shares this PHP session.
unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_username'],
    $_SESSION['admin_login_csrf']
);

// Invalidate the old session ID.
session_regenerate_id(true);

// Return to the admin login page.
header('Location: login.php');
exit;
