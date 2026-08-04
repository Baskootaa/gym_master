<?php
/**
 * حارس الجلسة (Session Guard)
 * يتحط في أول أي صفحة محمية (زي index.php) قبل أي HTML
 * لو المستخدم مش مسجل دخول، يتحول على صفحة اللوجين فورًا
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
