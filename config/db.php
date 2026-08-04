<?php
// منع إعادة الاتصال إذا كان معرفاً مسبقاً
if (isset($conn) && $conn instanceof mysqli && isset($pdo) && $pdo instanceof PDO) {
    return;
}

// بيانات الاتصال بقاعدة البيانات
$host     = 'localhost';
$dbname   = 'gym_master'; // اسم قاعدة البيانات
$username = 'root';
$password = '';         // افتراضي XAMPP

// ----------------------------------------------------
// 1. الاتصال باستخدام MySQLi (لتوافق متغير $conn)
// ----------------------------------------------------
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("خطأ في الاتصال بقاعدة البيانات (MySQLi): " . $conn->connect_error);
}

// ضبط الترميز للغة العربية
$conn->set_charset("utf8mb4");

// ----------------------------------------------------
// 2. الاتصال باستخدام PDO (لتوافق متغير $pdo)
// ----------------------------------------------------
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات (PDO): " . $e->getMessage());
}
?>