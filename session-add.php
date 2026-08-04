<?php
$active_page = 'schedules';
require_once 'config/db.php';

$errors = [];

$trainers = $pdo->query('SELECT id, name FROM trainers ORDER BY name')->fetchAll();

$dayOptions = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $trainerId   = $_POST['trainer_id'] ?? '';
    $dayName     = $_POST['day_name'] ?? '';
    $startTime   = trim($_POST['start_time'] ?? '');
    $endTime     = trim($_POST['end_time'] ?? '');
    $sessionName = trim($_POST['session_name'] ?? '');
    $room        = trim($_POST['room'] ?? '');
    $status      = $_POST['status'] ?? 'متاح';

    if (!is_numeric($trainerId)) {
        $errors[] = 'لازم تختار مدرب.';
    }
    if (!in_array($dayName, $dayOptions, true)) {
        $errors[] = 'اليوم غير صحيح.';
    }
    if ($startTime === '' || $endTime === '') {
        $errors[] = 'وقت البداية والنهاية مطلوبين.';
    }
    if ($sessionName === '') {
        $errors[] = 'اسم الحصة مطلوب.';
    }
    if ($room === '') {
        $errors[] = 'اسم القاعة مطلوب.';
    }
    if (!in_array($status, ['متاح', 'مكتمل'], true)) {
        $errors[] = 'حالة الحصة غير صحيحة.';
    }

    if (empty($errors)) {
        $timeRange = date('h:i', strtotime($startTime)) . ' ' . (date('A', strtotime($startTime)) === 'AM' ? 'ص' : 'م')
                   . ' - ' . date('h:i', strtotime($endTime)) . ' ' . (date('A', strtotime($endTime)) === 'AM' ? 'ص' : 'م');

        $orderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM schedule_sessions WHERE day_name = :day');
        $orderStmt->execute([':day' => $dayName]);
        $nextOrder = $orderStmt->fetch()['next_order'];

        $sql = 'INSERT INTO schedule_sessions (trainer_id, day_name, time_range, session_name, room, status, sort_order)
                VALUES (:trainer_id, :day_name, :time_range, :session_name, :room, :status, :sort_order)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':trainer_id'   => (int) $trainerId,
            ':day_name'     => $dayName,
            ':time_range'   => $timeRange,
            ':session_name' => $sessionName,
            ':room'         => $room,
            ':status'       => $status,
            ':sort_order'   => $nextOrder,
        ]);

        header('Location: schedules.php?added=1');
        exit;
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
      <!--begin::Row-->
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>إضافة حصة جديدة</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="./index.php">الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="./schedules.php">جدول الحصص والتمارين</a></li>
            <li class="breadcrumb-item active" aria-current="page">إضافة حصة جديدة</li>
          </ol>
        </div>
      </div>
      <!--end::Row-->
    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content Header-->

  <!--begin::App Content-->
  <div class="app-content">
    <!--begin::Container-->
    <div class="container-fluid">

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (empty($trainers)): ?>
        <div class="alert alert-warning">
          <a href="./trainer-add.php" class="alert-link">إضافة مدرب الآن</a>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">بيانات الحصة</h3>
        </div>
        <form method="post" action="./session-add.php">
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label">المدرب</label>
              <select name="trainer_id" class="form-select" required <?php echo empty($trainers) ? 'disabled' : ''; ?>>
                <option value="">-- اختر المدرب --</option>
                <?php foreach ($trainers as $trainer): ?>
                  <option
                    value="<?php echo (int) $trainer['id']; ?>"
                    <?php echo (($_POST['trainer_id'] ?? '') == $trainer['id']) ? 'selected' : ''; ?>
                  >
                    <?php echo htmlspecialchars($trainer['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">اليوم</label>
              <select name="day_name" class="form-select" required>
                <option value="">-- اختر اليوم --</option>
                <?php foreach ($dayOptions as $day): ?>
                  <option value="<?php echo htmlspecialchars($day); ?>" <?php echo (($_POST['day_name'] ?? '') === $day) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($day); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">من الساعة</label>
                <input type="time" name="start_time" class="form-control" value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>" required />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">إلى الساعة</label>
                <input type="time" name="end_time" class="form-control" value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>" required />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">اسم الحصة</label>
              <input
                type="text"
                name="session_name"
                class="form-control"
                value="<?php echo htmlspecialchars($_POST['session_name'] ?? ''); ?>"
                placeholder="مثال: كروس فيت، يوجا، سباحة..."
                required
              />
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">القاعة</label>
                <input
                  type="text"
                  name="room"
                  class="form-control"
                  value="<?php echo htmlspecialchars($_POST['room'] ?? ''); ?>"
                  placeholder="مثال: قاعة 1، صالة الأوزان..."
                  required
                />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">حالة الحصة</label>
                <select name="status" class="form-select">
                  <option value="متاح" <?php echo (($_POST['status'] ?? '') === 'متاح') ? 'selected' : ''; ?>>متاح</option>
                  <option value="مكتمل" <?php echo (($_POST['status'] ?? '') === 'مكتمل') ? 'selected' : ''; ?>>مكتمل</option>
                </select>
              </div>
            </div>

          </div>
          <div class="card-footer text-end">
            <a href="./schedules.php" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary" <?php echo empty($trainers) ? 'disabled' : ''; ?>>
              <i class="bi bi-check-lg me-1"></i> حفظ الحصة
            </button>
          </div>
        </form>
      </div>

    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>
