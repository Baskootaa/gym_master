<?php
/**
 * حارس الجلسة (Session Guard المحدث)
 * يتحط في أول أي صفحة محمية قبل أي HTML
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التأكد من وجود أي من مفاتيح المعرف الشائعة في الجلسة
$logged_in_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

if (empty($logged_in_id)) {
    // إعادة توجيه لصفحة اللوجين لو لم يتم العثور على الجلسة
    header('Location: login.php');
    exit;
}

// لضمان استقرار التطبيق وتوحيد المفتاح برمجياً
$_SESSION['user_id'] = $logged_in_id;
