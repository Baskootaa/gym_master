<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$message = '';
$messageType = '';

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $avatar_base64 = null;

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['avatar']['tmp_name'];
        $fileName      = $_FILES['avatar']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $imageData = file_get_contents($fileTmpPath);
            $mimeType  = mime_content_type($fileTmpPath);
            $avatar_base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
    }

    if (!empty($name) && !empty($email) && $user_id > 0) {
        try {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                if ($avatar_base64 !== null) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $hashedPassword, $avatar_base64, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $hashedPassword, $user_id]);
                }
            } else {
                if ($avatar_base64 !== null) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $avatar_base64, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $user_id]);
                }
            }

            if (isset($_SESSION['full_name'])) $_SESSION['full_name'] = $name;
            if (isset($_SESSION['name'])) $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            if ($avatar_base64 !== null) {
                $_SESSION['avatar'] = $avatar_base64;
                $_SESSION['photo'] = $avatar_base64;
            }

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

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
}

$current_name = $user['full_name'] ?? $user['name'] ?? $_SESSION['full_name'] ?? $_SESSION['name'] ?? '';
$current_email = $user['email'] ?? $_SESSION['email'] ?? '';
$current_phone = $user['phone'] ?? $user['mobile'] ?? $user['telephone'] ?? '';
$current_avatar_db = $user['photo'] ?? $user['avatar'] ?? $_SESSION['avatar'] ?? $_SESSION['photo'] ?? '';

// تجهيز عرض الصورة بطريقة آمنة تماماً لتجنب أي مشاكل في الـ URL
$current_avatar_display = '';
if (!empty($current_avatar_db)) {
    if (strpos($current_avatar_db, 'data:image') === 0 || strpos($current_avatar_db, 'data:') === 0) {
        $current_avatar_display = $current_avatar_db;
    } else {
        $current_avatar_display = BASE_URL . 'assets/img/' . $current_avatar_db;
    }
} else {
    $current_avatar_display = BASE_URL . 'assets/img/user2-160x160.jpg';
}

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
                            <form method="POST" action="profile.php" enctype="multipart/form-data">
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

                                <div class="mb-3">
                                    <label class="form-label fw-bold">الصورة الشخصية (اختياري)</label>
                                    <input type="file" name="avatar" class="form-control" accept="image/*">
                                    <div class="mt-2">
                                        <small class="text-muted">الصورة الحالية:</small><br>
                                        <img src="<?= $current_avatar_display; ?>?v=<?php echo time(); ?>" alt="Avatar" class="rounded-circle mt-1" width="60" height="60" style="object-fit: cover;">
                                    </div>
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
