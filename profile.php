<?php
session_start();
require_once __DIR__ . '/config/db.php';

// حماية الصفحة: إعادة التوجيه لصفحة الدخول إن لم يتم تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// جلب بيانات المستخدم الحالي من قاعدة البيانات
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// معالجة تحديث البيانات عند حفظ النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (!empty($name) && !empty($email)) {
        $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $name, $email, $userId);
        
        if ($updateStmt->execute()) {
            $_SESSION['user_name'] = $name; // تحديث اسم المستخدم في الجلسة فوراً
            $message = "تم تحديث البيانات بنجاح!";
            $user['name'] = $name;
            $user['email'] = $email;
        } else {
            $error = "حدث خطأ أثناء التحديث.";
        }
    } else {
        $error = "جميع الحقول مطلوبة.";
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">الملف الشخصي</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">تعديل البيانات الشخصية</h3></div>
            <form method="POST" action="profile.php">
              <div class="card-body">
                <?php if ($message): ?>
                  <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                  <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="mb-3">
                  <label for="name" class="form-label">الاسم بالكامل</label>
                  <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">البريد الإلكتروني</label>
                  <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
              </div>
              <div class="card-footer">
                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>