<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_page = 'schedules';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/db.php';

// التحقق من صلاحية المستخدم (أدمن أو موظف)
$isStaffOrAdmin = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true);

$dayNames = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];

$sql = "SELECT s.*, t.name AS trainer_name
        FROM schedule_sessions s
        JOIN trainers t ON s.trainer_id = t.id
        ORDER BY FIELD(s.day_name, 'السبت','الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس'), s.sort_order";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

$schedule = array_fill_keys($dayNames, []);
foreach ($rows as $row) {
    if (isset($schedule[$row['day_name']])) {
        $schedule[$row['day_name']][] = $row;
    }
}
?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0"><i class="bi bi-calendar-week me-2"></i>جدول الحصص والتمارين (Schedules)</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="./index.php">الرئيسية</a></li>
            <li class="breadcrumb-item">الكباتن والمدربين</li>
            <li class="breadcrumb-item active" aria-current="page">جدول الحصص والتمارين</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <!--end::App Content Header-->

  <!--begin::App Content-->
  <div class="app-content">
    <div class="container-fluid">

      <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle-fill me-2"></i> تمت إضافة الحصة بنجاح.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle-fill me-2"></i> تم تحديث الحصة بنجاح.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle-fill me-2"></i> تم حذف الحصة بنجاح.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($isStaffOrAdmin): ?>
        <div class="mb-3">
          <a href="./session-add.php" class="btn btn-primary">
            <i class="bi bi-calendar-plus me-1"></i> إضافة حصة جديدة
          </a>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-list-check me-2"></i>حصص الأسبوع</h3>
        </div>
        <div class="card-body">

          <!--begin::Day Tabs-->
          <ul class="nav nav-tabs" role="tablist">
            <?php foreach ($dayNames as $index => $day): ?>
              <li class="nav-item" role="presentation">
                <button
                  class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>"
                  id="tab-<?php echo $index; ?>"
                  data-bs-toggle="tab"
                  data-bs-target="#day-<?php echo $index; ?>"
                  type="button"
                  role="tab"
                >
                  <?php echo htmlspecialchars($day); ?>
                  <span class="badge text-bg-secondary ms-1"><?php echo count($schedule[$day]); ?></span>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>
          <!--end::Day Tabs-->

          <!--begin::Day Tab Panes-->
          <div class="tab-content pt-3">
            <?php foreach ($dayNames as $index => $day): ?>
              <div
                class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>"
                id="day-<?php echo $index; ?>"
                role="tabpanel"
              >
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>الوقت</th>
                        <th>اسم الحصة</th>
                        <th>المدرب</th>
                        <th>القاعة</th>
                        <th>الحالة</th>
                        <?php if ($isStaffOrAdmin): ?>
                          <th class="text-center">الإجراءات</th>
                        <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($schedule[$day])): ?>
                        <tr>
                          <td colspan="<?php echo $isStaffOrAdmin ? '6' : '5'; ?>" class="text-center text-secondary py-4">
                            لا توجد حصص مجدولة في هذا اليوم
                          </td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($schedule[$day] as $session): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($session['time_range']); ?></td>
                            <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                            <td><?php echo htmlspecialchars($session['trainer_name']); ?></td>
                            <td><?php echo htmlspecialchars($session['room']); ?></td>
                            <td>
                              <?php if ($session['status'] === 'متاح'): ?>
                                <span class="badge text-bg-success">متاح</span>
                              <?php else: ?>
                                <span class="badge text-bg-danger">مكتمل</span>
                              <?php endif; ?>
                            </td>
                            <?php if ($isStaffOrAdmin): ?>
                              <td class="text-center">
                                <a href="./session-edit.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-warning" title="تعديل">
                                  <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="./session-delete.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذه الحصة؟');">
                                  <i class="bi bi-trash"></i>
                                </a>
                              </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <!--end::Day Tab Panes-->

        </div>
      </div>
      <!-- /.card -->

    </div>
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>