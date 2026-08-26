<?php
session_start();

// لو المستخدم مسجل دخول بالفعل، رجّعه على الداش بورد
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $errors[] = 'من فضلك أدخل البريد الإلكتروني';
    } else {
        // التحقق من وجود الإيميل في جدول المستخدمين
        $stmt = $conn->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // هنا لحماية المشروع وسهولة الاستخدام على السيرفر، بنسمح بتوجيهه لصفحة إعادة تعيين الباسورد مباشرة أو عرض رسالة نجاح
            // ممكن نحفظ الـ email في الـ Session عشان نستخدمه في صفحة تغيير الباسورد الجديدة
            $_SESSION['reset_email'] = $email;
            header('Location: reset-password.php');
            exit;
        } else {
            $errors[] = 'هذا البريد الإلكتروني غير مسجل لدينا';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>نسيت الباسورد | نظام إدارة الجيم</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/adminlte.rtl.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #1a1a1a; }
    .login-box { width: 100%; max-width: 400px; padding: 15px; }
    .login-logo { text-align: center; margin-bottom: 20px; color: #fff; }
    .login-logo i { color: #dc3545; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-logo">
      <i class="bi bi-activity fs-1"></i>
      <h2 class="fw-bold mt-2">GYM MASTER</h2>
    </div>
    <div class="card">
      <div class="card-body login-card-body">
        <p class="login-box-msg">استعادة كلمة المرور</p>

        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="forgot-password.php">
          <div class="input-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="أدخل البريد الإلكتروني" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <div class="input-group-text"><i class="bi bi-envelope"></i></div>
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100">تحقق من الإيميل</button>
            </div>
          </div>
        </form>

        <p class="mt-3 mb-0 text-center">
          <a href="login.php">تذكرت كلمة المرور؟ تسجيل الدخول</a>
        </p>
      </div>
    </div>
  </div>
</body>
</html>
