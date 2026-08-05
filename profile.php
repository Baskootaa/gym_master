<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$message = '';
$messageType = '';

// جلب معرف المستخدم الحالي من الجلسة
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

// معالجة تحديث البيانات عند إرسال الفورم (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($name) && !empty($email) && $user_id > 0) {
        try {
            // التحقق مما إذا كان سيتم تحديث كلمة المرور أم لا
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // محاولة التحديث مع كلمة المرور ورقم الهاتف باستخدام full_name أولاً ثم name
                try {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $hashedPassword, $user_id]);
                } catch (Exception $ex) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $hashedPassword, $user_id]);
                    } catch (Exception $e2) {
                        // لو عامود phone مش موجود في جدول users
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $hashedPassword, $user_id]);
                    }
                }
            } else {
                // التحديث بدون تغيير كلمة المرور
                try {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $user_id]);
                } catch (Exception $ex) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $user_id]);
                    } catch (Exception $e2) {
                        // لو عامود phone مش موجود في جدول users
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $user_id]);
                    }
                }
            }

            // تحديث قيم الجلسة فوراً لتنعكس في الهيدر والقوائم
            if (isset($_SESSION['full_name'])) $_SESSION['full_name'] = $name;
            if (isset($_SESSION['name'])) $_SESSION['name'] = $name;
            if (isset($_SESSION['user_name'])) $_SESSION['user_name'] = $name;
            $_SESSION['email'] = $email;

            $message = "تم تحديث البيانات الشخصية بنجاح!";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "حدث خطأ أثناء التحديث: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "يرجى ملء جميع الحقول المطلوبة (الاسم والبريد)!";
        $messageType = "warning";
    }
}

// جلب بيانات المستخدم الحالية لعرضها في مدخلات الفورم
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
}

// تحديد القيم الحالية من الحقول المتاحة
$current_name = $user['full_name'] ?? $user['name'] ?? $_SESSION['full_name'] ?? $_SESSION['name'] ?? '';
$current_email = $user['email'] ?? $_SESSION['email'] ?? '';
$current_phone = $user['phone'] ?? $user['mobile'] ?? $user['telephone'] ?? '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">الملف الشخصي</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card card-primary card-outline">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">تعديل البيانات الشخصية</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">الاسم بالكامل</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($current_name) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($current_email) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">رقم المحمول</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($current_phone) ?>" placeholder="أدخل رقم الهاتف">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">كلمة المرور الجديدة</label>
                                    <input type="password" name="password" class="form-control" placeholder="اتركها فارغة إذا لم ترد التغيير">
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    حفظ التغييرات
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
