<?php

// تحديد BASE_URL ديناميكياً لتناسب البيئة المحلية (XAMPP) أو السحابية (Render)
if ($_SERVER['HTTP_HOST'] == 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    // استخرج المسار الفرعي المحلي تلقائياً بناءً على مكان وجود الملف
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // إذا كان المشروع داخل مجلد فرعي محلي
    define('BASE_URL', '/gym_master/');
} else {
    // على السيرفر السحابي (Render) الملفات في الـ Root مباشرة
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

// بيانات الاتصال بقاعدة البيانات (تم التحديث لدعم بيئة السحاب مثل Render أو الاستضافات الخارجية)
$host = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_DATABASE') ?: 'gym_master'; 
$username = getenv('DB_USERNAME') ?: 'gym_user';
$password = getenv('DB_PASSWORD') ?: 'gym12345';        

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

// ----------------------------------------------------
// 3. دوال فحص الصلاحيات وتنظيم أدوار المستخدمين
// ----------------------------------------------------

/**
 * التحقق مما إذا كان المستخدم قد قام بتسجيل الدخول
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

/**
 * التحقق مما إذا كان للمستخدم دور معين أو أكثر من دور
 * 
 * @param array|string $allowedRoles الأدوار المسموح لها مثل ['admin', 'staff'] أو 'admin'
 * @return bool
 */
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

/**
 * دالة حماية الصفحات: تمنع الوصول للغير مصرح لهم وتوجههم للصفحة الرئيسية أو صفحة الدخول
 * 
 * @param array|string $allowedRoles الأدوار المسموح لها بفتح الصفحة
 */
if (!function_exists('checkAccess')) {
    function checkAccess($allowedRoles) {
        if (!isLoggedIn()) {
            header("Location: " . BASE_URL . "login.php");
            exit();
        }

        if (!hasRole($allowedRoles)) {
            $_SESSION['error'] = "غير مصرح لك بالوصول لصفحة " . basename($_SERVER['PHP_SELF']);
            
            // توجيه المستخدم حسب دوره عند محاولة الوصول لصفحة غير مصرحة
            header("Location: " . BASE_URL . "members.php");
            exit();
        }
    }
}

?>
