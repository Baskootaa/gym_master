<?php
    
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['HTTP_HOST'] == 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    define('BASE_URL', '/gym_master/');
} else {
    define('BASE_URL', '/');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($conn) && $conn instanceof mysqli && isset($pdo) && $pdo instanceof PDO) {
    return;
}

// قراءة بيانات الاتصال مباشرة من متغيرات البيئة على Render مع استخدام root كقيمة افتراضية
$host = 'sql313.infinityfree.com';
$dbname = 'if0_42679699_gym_master';
$username = 'if0_42679699';
$password = '42bbahd8be9SL1h'; // تأكد إن الباسورد ده هو الباسورد الحالي لحساب الـ MySQL في اللوحة
// 1. الاتصال باستخدام MySQLi
$conn = new mysqli($host, $username, $password, $dbname );

if ($conn->connect_error) {
    die("خطأ في الاتصال بقاعدة البيانات (MySQLi): " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// 2. الاتصال باستخدام PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");

} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات (PDO): " . $e->getMessage());
}

// دوال الصلاحيات
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('hasRole')) {
    function hasRole($allowedRoles) {
        if (!isLoggedIn()) {
            return false;
        }
        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        $userRole = $_SESSION['role'] ?? 'user';
        return in_array($userRole, $allowedRoles, true);
    }
}

if (!function_exists('checkAccess')) {
    function checkAccess($allowedRoles) {
        if (!isLoggedIn()) {
            header("Location: " . BASE_URL . "login.php");
            exit();
        }

        if (!hasRole($allowedRoles)) {
            $_SESSION['error'] = "غير مصرح لك بالوصول لصفحة " . basename($_SERVER['PHP_SELF']);
            header("Location: " . BASE_URL . "members.php");
            exit();
        }
    }
}
?>
