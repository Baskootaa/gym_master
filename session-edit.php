<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// حماية الصفحة للأدمن والموظف فقط
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header("Location: schedules.php");
    exit();
}

require_once 'config/db.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: schedules.php");
    exit();
}

$id = (int)$_GET['id'];
$error = '';

// جلب المدربين للقائمة المنسدلة
$trainers = $pdo->query("SELECT id, name FROM trainers ORDER BY name")->fetchAll();
$dayNames = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_name = trim($_POST['session_name'] ?? '');
    $trainer_id   = $_POST['trainer_id'] ?? '';
    $day_name     = $_POST['day_name'] ?? '';
    $time_range   = trim($_POST['time_range'] ?? '');
    $room         = trim($_POST['room'] ?? '');
    $status       = $_POST['status'] ?? 'متاح';
    $sort_order   = (int)($_POST['sort_order'] ?? 0);

    if ($session_name === '' || $trainer_id === '' || $day_name === '' || $time_range === '') {
        $error = 'من فضلك املأ جميع الحقول الإجبارية.';
    } else {
        $stmt = $pdo->prepare("UPDATE schedule_sessions SET session_name = ?, trainer_id = ?, day_name = ?, time_range = ?, room = ?, status = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$session_name, $trainer_id, $day_name, $time_range, $room, $status, $sort_order, $id]);
        header("Location: schedules.php?updated=1");
        exit();
    }
} else {
    $stmt = $pdo->prepare("SELECT * FROM schedule_sessions WHERE id = ?");
    $stmt->execute([$id]);
    $session = $stmt->fetch();
    if (!$session) {
        header("Location: schedules.php");
        exit();
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h3>تعديل الحصة التدريبية</h3>
    </div>
  </div>
  <div class="app-content">
    <div class="container-fluid">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>
      <div class="card">
        <form method="POST">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">اسم الحصة *</label>
              <input type="text" name="session_name" class="form-control" value="<?php echo htmlspecialchars($session['session_name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">المدرب *</label>
              <select name="trainer_id" class="form-select" required>
                <اختر المدرب</option>
                <?php foreach ($trainers as $trainer): ?>
                  <option value="<?php echo $trainer['id']; ?>" <?php echo (($session['trainer_id'] ?? '') == $trainer['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($trainer['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">اليوم *</label>
              <select name="day_name" class="form-select" required>
                <?php foreach ($dayNames as $day): ?>
                  <option value="<?php echo $day; ?>" <?php echo (($session['day_name'] ?? '') === $day) ? 'selected' : ''; ?>>
                    <?php echo $day; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">الوقت (مثال: 06:00 ص - 07:00 ص) *</label>
              <input type="text" name="time_range" class="form-control" value="<?php echo htmlspecialchars($session['time_range'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">القاعة</label>
              <input type="text" name="room" class="form-control" value="<?php echo htmlspecialchars($session['room'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">الحالة</label>
              <select name="status" class="form-select">
                <option value="متاح" <?php echo (($session['status'] ?? '') === 'متاح') ? 'selected' : ''; ?>>متاح</option>
                <option value="مكتمل" <?php echo (($session['status'] ?? '') === 'مكتمل') ? 'selected' : ''; ?>>مكتمل</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">الترتيب</label>
              <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($session['sort_order'] ?? 0); ?>">
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-warning">حفظ التعديلات</button>
            <a href="schedules.php" class="btn btn-secondary">إلغاء</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>