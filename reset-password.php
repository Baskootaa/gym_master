<?php
session_start();

// لو المستخدم مسجل دخول أو مفيش إيميل محفوظ في السيشن لعملية الاستعادة، رجّعه لصفحة اللوجن
if (!empty($_SESSION['user_id']) || empty($_SESSION['reset_email'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $email        = $_SESSION['reset_email'];

    if ($password === '' || $confirm_pass === '') {
        $errors[] = 'من فضلك املأ كل الحقول';
    } elseif ($password !== $confirm_pass) {
        $errors[] = 'كلمتا المرور غير متطابقتين';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب ألا تقل عن 6 أحرف';
    } else {
        // تشفير الباسورد الجديد
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // تحديث الباسورد في قاعدة البيانات
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->bind_param('ss', $hashed_password, $email);
        
        if ($stmt->execute()) {
            // مسح إيميل الاستعادة من السيشن وتوجيه المستخدم للوجن مع رسالة نجاح (ممكن نعملها بـ query parameter)
            unset($_SESSION['reset_email']);
            header('Location: login.php?reset=success');
            exit;
        } else {
            $errors[] = 'حدث خطأ ما، حاول مرة أخرى';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>إعادة تعيين كلمة المرور | نظام إدارة الجيم</title>
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
        <p class="login-box-msg">إدخال كلمة المرور الجديدة</p>

        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="reset-password.php">
          <div class="input-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="كلمة المرور الجديدة" required>
            <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
          </div>
          
          <div class="input-group mb-3">
            <input type="password" name="confirm_password" class="form-control" placeholder="تأكيد كلمة المرور" required>
            <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100">تغيير كلمة المرور</button>
            </div>
          </div>
        </form>

        <p class="mt-3 mb-0 text-center">
          <a href="login.php">العودة لتسجيل الدخول</a>
        </p>
      </div>
    </div>
  </div>
</body>
</html>
