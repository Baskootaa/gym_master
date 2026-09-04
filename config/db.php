<?php

// تحديد BASE_URL ديناميكياً لتناسب البيئة المحلية (XAMPP) أو السحابية (Render)
if ($_SERVER['HTTP_HOST'] == 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    define('BASE_URL', '/gym_master/');
} else {
    define('BASE_URL', '/');
}

// بدء الجلسة إذا لم تكن مبدوءة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// منع إعادة الاتصال والدوال إذا كانت معرفة مسبقاً
if (isset($conn) && $conn instanceof mysqli && isset($pdo) && $pdo instanceof PDO) {
    return;
}

// قراءة بيانات الاتصال (من متغيرات البيئة أو القيم الافتراضية)
$host = getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net';
$port = getenv('DB_PORT') ?: '50569';
$dbname = getenv('DB_DATABASE') ?: 'railway';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'FEUxYixXuaqMLNnlpGZnYEiOtCxMBVq';         

// ----------------------------------------------------
// 1. الاتصال باستخدام MySQLi
// ----------------------------------------------------
$conn = new mysqli($host, $username, $password, $dbname, (int)$port);

if ($conn->connect_error) {
    die("خطأ في الاتصال بقاعدة البيانات (MySQLi): " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ----------------------------------------------------
// 2. الاتصال باستخدام PDO
// ----------------------------------------------------
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    
    $pdo->exec("SET time_zone = '+02:00'");

} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات (PDO): " . $e->getMessage());
}

// ----------------------------------------------------
// 3. دوال فحص الصلاحيات
// ----------------------------------------------------
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
