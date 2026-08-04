<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';

$errors = [];
$full_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ==== Validation ====
    if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $errors[] = 'من فضلك املأ كل الحقول';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'صيغة الإيميل غلط';
    }
    if (strlen($password) < 6) {
        $errors[] = 'الباسورد لازم يكون 6 حروف/أرقام على الأقل';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'الباسورد وتأكيد الباسورد مش متطابقين';
    }

    // ==== تأكد إن الإيميل مش مستخدم قبل كده ====
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'الإيميل ده مسجل بالفعل';
        }
    }

    // ==== إنشاء الحساب ====
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)'
        );
        $stmt->execute([
            'full_name' => $full_name,
            'email'     => $email,
            'password'  => $hashedPassword,
            'role'      => 'staff',
        ]);

        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>حساب جديد | نظام إدارة الجيم</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/adminlte.rtl.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #1a1a1a; }
    .login-box { width: 100%; max-width: 420px; padding: 15px; }
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
        <p class="login-box-msg">اعمل حساب جديد</p>

        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="register.php">
          <div class="input-group mb-3">
            <input type="text" name="full_name" class="form-control" placeholder="الاسم بالكامل" required
                   value="<?= htmlspecialchars($full_name) ?>">
            <div class="input-group-text"><i class="bi bi-person"></i></div>
          </div>
          <div class="input-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="الإيميل" required
                   value="<?= htmlspecialchars($email) ?>">
            <div class="input-group-text"><i class="bi bi-envelope"></i></div>
          </div>
          <div class="input-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="الباسورد" required>
            <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
          </div>
          <div class="input-group mb-3">
            <input type="password" name="confirm_password" class="form-control" placeholder="تأكيد الباسورد" required>
            <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
          </div>
          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-success w-100">إنشاء الحساب</button>
            </div>
          </div>
        </form>

        <p class="mt-3 mb-0 text-center">
          <a href="login.php">عندك حساب بالفعل؟ سجّل دخولك</a>
        </p>
      </div>
    </div>
  </div>
</body>
</html>
