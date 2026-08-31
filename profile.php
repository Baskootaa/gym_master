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
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $avatar_filename = null;

    // معالجة رفع الصورة الشخصية إذا تم اختيار صورة
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['avatar']['tmp_name'];
        $fileName      = $_FILES['avatar']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // توجيه مسار الرفع إلى مجلد assets/img/avatars/ ليتوافق مع هيكل المجلدات الحقيقي على السيرفر
            $uploadFileDir = __DIR__ . '/assets/img/avatars/';
            
            // التأكد من وجود المجلد ومنح صلاحيات الكتابة لمنع خطأ Permission Denied نهائياً
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0777, true);
            }
            @chmod($uploadFileDir, 0777);
            
            // تسمية الصورة باسم نظيف وثابت يعتمد على الـ ID الخاص بالمستخدم وامتداد الملف فقط
            $newFileName = 'avatar_' . $user_id . '.' . $fileExtension;
            $dest_path   = $uploadFileDir . $newFileName;
            
            // حذف أي امتدادات أخرى قديمة لنفس المستخدم لو تم تغيير صيغة الصورة
            foreach ($allowedExtensions as $ext) {
                $oldFile = $uploadFileDir . 'avatar_' . $user_id . '.' . $ext;
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // حفظ المسار النسبي ليطابق الاستدعاء الصحيح (avatars/filename.ext)
                $avatar_filename = 'avatars/' . $newFileName;
            }
        }
    }

    if (!empty($name) && !empty($email) && $user_id > 0) {
        try {
            // التحقق مما إذا كان سيتم تحديث كلمة المرور وصورة البروفايل أم لا
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                if ($avatar_filename !== null) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, photo = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $hashedPassword, $avatar_filename, $user_id]);
                    } catch (Exception $ex) {
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ?, photo = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $phone, $hashedPassword, $avatar_filename, $user_id]);
                        } catch (Exception $e2) {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, photo = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $hashedPassword, $avatar_filename, $user_id]);
                        }
                    }
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $hashedPassword, $user_id]);
                    } catch (Exception $ex) {
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $phone, $hashedPassword, $user_id]);
                        } catch (Exception $e2) {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $hashedPassword, $user_id]);
                        }
                    }
                }
            } else {
                if ($avatar_filename !== null) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $avatar_filename, $user_id]);
                    } catch (Exception $ex) {
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $phone, $avatar_filename, $user_id]);
                        } catch (Exception $e2) {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, photo = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $avatar_filename, $user_id]);
                        }
                    }
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $phone, $user_id]);
                    } catch (Exception $ex) {
                        try {
                            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $phone, $user_id]);
                        } catch (Exception $e2) {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $user_id]);
                        }
                    }
                }
            }

            // تحديث قيم الجلسة فوراً لتنعكس في الهيدر والقوائم
            if (isset($_SESSION['full_name'])) $_SESSION['full_name'] = $name;
            if (isset($_SESSION['name'])) $_SESSION['name'] = $name;
            if (isset($_SESSION['user_name'])) $_SESSION['user_name'] = $name;
            $_SESSION['email'] = $email;
            if ($avatar_filename !== null) {
                $_SESSION['avatar'] = 'assets/img/' . $avatar_filename;
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

// جلب بيانات المستخدم الحالية لعرضها في مدخلات الفورم
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
}

// تحديد القيم الحالية من الحقول المتاحة (دعم عمود photo أو avatar)
$current_name = $user['full_name'] ?? $user['name'] ?? $_SESSION['full_name'] ?? $_SESSION['name'] ?? '';
$current_email = $user['email'] ?? $_SESSION['email'] ?? '';
$current_phone = $user['phone'] ?? $user['mobile'] ?? $user['telephone'] ?? '';
$current_avatar_db = $user['photo'] ?? $user['avatar'] ?? '';

// تجهيز مسار الصورة لعرضها في صفحة البروفايل بشكل سليم
$current_avatar_display = '';
if (!empty($current_avatar_db)) {
    if (strpos($current_avatar_db, 'assets/img/') === 0) {
        $current_avatar_display = BASE_URL . $current_avatar_db;
    } elseif (strpos($current_avatar_db, 'uploads/') === 0) {
        $current_avatar_display = BASE_URL . $current_avatar_db;
    } else {
        $current_avatar_display = BASE_URL . 'assets/img/' . $current_avatar_db;
    }
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
                                    <?php if (!empty($current_avatar_display)): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">الصورة الحالية:</small><br>
                                            <img src="<?= htmlspecialchars($current_avatar_display) ?>?v=<?php echo time(); ?>" alt="Avatar" class="rounded-circle mt-1" width="60" height="60" style="object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
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
