<?php
// التأكد من عدم إنشاء أكثر من اتصال إذا تم استدعاء الملف أكثر من مرة
if (isset($pdo) && $pdo instanceof PDO) {
    return;
}

// بيانات الاتصال بقاعدة البيانات
$host     = 'localhost';
$dbname   = 'gym_master'; // اسم قاعدة البيانات الموحد للفريق
$username = 'root';
$password = '';           // في XAMPP يكون الباسورد فارغ افتراضياً

try {
    // إنشاء كائن PDO للاتصال
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // إظهار الأخطاء في شكل Exceptions لتسهيل الـ Debugging
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // إرجاع البيانات في شكل Array مفهرسة بأسماء الأعمدة
        PDO::ATTR_EMULATE_PREPARES   => false,                 // حماية إضافية وتعزيز الأداء ضد SQL Injection
    ]);
} catch (PDOException $e) {
    // في حالة فشل الاتصال يتم إيقاف التنفيذ وإظهار السبب
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}