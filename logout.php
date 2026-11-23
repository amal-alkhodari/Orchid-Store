
<?php
session_start();

// إنهاء جلسة المستخدم
$_SESSION = [];
session_destroy();

// العودة إلى صفحة تسجيل الدخول
header('Location: index.php');
exit;
