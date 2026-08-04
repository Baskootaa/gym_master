<?php
session_start();

// لو المستخدم مسجل دخول بالفعل، رجّعه على الداش بورد على طول
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'من فضلك املأ كل الحقول';
    } else {
        // التحقق باستخدام MySQLi المتوافق مع ملف db.php
        $stmt = $conn->prepare('SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // نجاح تسجيل الدخول
            session_regenerate_id(true); // حماية ضد Session Fixation
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name']; // حفظ اسم المستخدم Dynamic في الجلسة
            $_SESSION['user_role'] = $user['role'] ?? 'Admin';

            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'الإيميل أو الباسورد غلط';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>تسجيل الدخول | نظام إدارة الجيم</title>
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
        <p class="login-box-msg">سجّل دخولك عشان تبدأ الشغل</p>

        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <?php if (!empty($_GET['registered'])): ?>
          <div class="alert alert-success py-2">تم إنشاء الحساب بنجاح، سجّل دخولك دلوقتي</div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <div class="input-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="الإيميل" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <div class="input-group-text"><i class="bi bi-envelope"></i></div>
          </div>
          <div class="input-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="الباسورد" required>
            <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
          </div>
          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100">دخول</button>
            </div>
          </div>
        </form>

        <p class="mt-3 mb-0 text-center">
          <a href="register.php">مفيش عندك حساب؟ اعمل واحد جديد</a>
        </p>
      </div>
    </div>
  </div>
</body>
</html>