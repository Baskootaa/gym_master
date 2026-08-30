<?php
// 1. استدعاء ملف الاتصال بقاعدة البيانات الصحيح
require_once 'config/db.php';

// منع الزائر غير المسجل من دخول الصفحة
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// 2. جلب الإعدادات العامة
try {
    $settings_stmt = $pdo->query("SELECT * FROM system_settings WHERE id=1");
    $sys_settings = $settings_stmt ? $settings_stmt->fetch(PDO::FETCH_ASSOC) : null;
} catch (PDOException $e) {
    $sys_settings = null;
}

// القيم الافتراضية للإعدادات في حال عدم وجود الجدول
$sys_settings = $sys_settings ?: [
    'gym_name'          => 'Gym Master',
    'phone'             => '01000000000',
    'currency'          => 'ج.م', 
    'open_time'         => '08:00:00', 
    'close_time'        => '00:00:00', 
    'tax_rate'          => '14.00',
    'invoice_message' => 'نتمنى لكم تمريناً سعيداً'
];
$currency = $sys_settings['currency'];

// 3. جلب الإحصائيات مباشرة وحصرياً من جدول الاشتراكات (subscriptions) بناءً على أحدث اشتراك لكل عضو
try {
    $total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
    
    // استعلام فرعي لجلب أحدث اشتراك لكل عضو من جدول subscriptions حصرياً بناءً على أكبر ID
    $latestSubQuery = "
        SELECT s.member_id, s.end_date 
        FROM subscriptions s
        INNER JOIN (
            SELECT member_id, MAX(id) AS max_id 
            FROM subscriptions 
            GROUP BY member_id
        ) latest ON s.id = latest.max_id
    ";

    // اشتراكات نشطة (تأخذ من جدول الاشتراكات مباشرة لتصبح 5 بدقة)
    $active_subs = $pdo->query("
        SELECT COUNT(*) FROM ({$latestSubQuery}) AS sub 
        WHERE sub.end_date >= CURRENT_DATE()
    ")->fetchColumn();
    
    // اشتراكات منتهية من جدول الاشتراكات
    $expired_subs = $pdo->query("
        SELECT COUNT(*) FROM ({$latestSubQuery}) AS sub 
        WHERE sub.end_date < CURRENT_DATE()
    ")->fetchColumn();
    
    // تنتهي هذا الأسبوع
    $expiring_soon = $pdo->query("
        SELECT COUNT(*) FROM ({$latestSubQuery}) AS sub 
        WHERE sub.end_date >= CURRENT_DATE() AND DATEDIFF(sub.end_date, CURRENT_DATE()) <= 7
    ")->fetchColumn();
    
    // جلب آخر 4 تسجيلات دخول لليوم الحالي (Today Check-ins)
    $todayStmt = $pdo->prepare("
        SELECT c.id, c.check_in_time, m.full_name AS member_name, m.status AS member_status,
               (SELECT p.name FROM subscriptions s
                JOIN packages p ON p.id = s.package_id
                WHERE s.member_id = m.id
                ORDER BY s.id DESC LIMIT 1) AS package_name
        FROM check_ins c
        JOIN members m ON m.id = c.member_id
        WHERE DATE(c.check_in_time) = CURRENT_DATE()
        ORDER BY c.check_in_time DESC
        LIMIT 4
    ");
    $todayStmt->execute();
    $todayCheckIns = $todayStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $total_members = $active_subs = $expired_subs = $expiring_soon = 0;
    $todayCheckIns = [];
}
?>

<?php $active_page = 'index'; ?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">لوحة تحكم (<?php echo htmlspecialchars($sys_settings['gym_name']); ?>)</h3>
          <p class="text-muted mb-0 mt-1">
            <i class="bi bi-telephone-fill me-1"></i> للتواصل: <?php echo htmlspecialchars($sys_settings['phone']); ?>
          </p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
            <li class="breadcrumb-item active" aria-current="page">لوحة التحكم</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <!--end::App Content Header-->

  <!--begin::App Content-->
  <div class="app-content">
    <div class="container-fluid">
      <!--begin::Row (Stats Widgets)-->
      <div class="row">
        <!-- Card 1: Total Members -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-primary">
            <div class="inner">
              <h3><?php echo number_format($total_members); ?></h3>
              <p>إجمالي الأعضاء (Total Members)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M4.5 4.5a3 3 0 00-3 3v9a3 3 0 003 3h15a3 3 0 003-3v-9a3 3 0 00-3-3h-15zM12 7.5a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5zM6 16.5a4.5 4.5 0 018.3-2.204.75.75 0 01-.106.918l-.208.208a.75.75 0 01-1.06 0L12 14.44l-1.926.982a.75.75 0 01-1.06 0l-.208-.208a.75.75 0 01-.106-.918A4.485 4.485 0 016 16.5z"></path>
            </svg>
            <a href="members.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
              عرض كل الأعضاء <i class="bi bi-arrow-right-circle"></i>
            </a>
          </div>
        </div>

        <!-- Card 2: Active Subscriptions -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-success">
            <div class="inner">
              <h3><?php echo number_format($active_subs); ?></h3>
              <p>اشتراكات نشطة (Active)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path clip-rule="evenodd" fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"></path>
            </svg>
            <a href="subscriptions.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
              عرض الاشتراكات <i class="bi bi-arrow-right-circle"></i>
            </a>
          </div>
        </div>

        <!-- Card 3: Expiring Soon -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-warning">
            <div class="inner">
              <h3><?php echo number_format($expiring_soon); ?></h3>
              <p>تنتهي هذا الأسبوع (Expiring Soon)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path clip-rule="evenodd" fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .2.079.39.22.53l3 3a.75.75 0 001.06-1.06l-2.78-2.78V6z"></path>
            </svg>
            <a href="expiring.php" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
              متابعة التجديدات <i class="bi bi-arrow-right-circle"></i>
            </a>
          </div>
        </div>

        <!-- Card 4: Expired Subscriptions -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-danger">
            <div class="inner">
              <h3><?php echo number_format($expired_subs); ?></h3>
              <p>اشتراكات منتهية (Expired)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path clip-rule="evenodd" fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z"></path>
            </svg>
            <a href="expiring.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
              التفاصيل <i class="bi bi-arrow-right-circle"></i>
            </a>
          </div>
        </div>
      </div>
      <!--end::Row-->

      <!--begin::Main Row-->
      <div class="row">
        <!-- Start Col (Left Section) -->
        <div class="col-lg-8 connectedSortable">
          
          <!-- Table: Recent Check-ins -->
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-person-check-fill me-2"></i>تسجيلات الدخول اليوم (Today Check-ins)</h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle text-center">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>اسم العضو</th>
                      <th>نوع الاشتراك</th>
                      <th>وقت الدخول</th>
                      <th>حالة الاشتراك</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($todayCheckIns)): ?>
                      <?php foreach ($todayCheckIns as $index => $row): ?>
                        <tr>
                          <td><?php echo $index + 1; ?></td>
                          <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                          <td>
                            <?php 
                               if (!empty($row['package_name'])) {
                                   echo htmlspecialchars($row['package_name']); 
                               } else {
                                   echo '<span class="text-muted">بدون باقة</span>';
                               }
                            ?>
                          </td>
                          <td><?php echo date('h:i A', strtotime($row['check_in_time'])); ?></td>
                          <td>
                            <?php if (isset($row['member_status']) && $row['member_status'] == 'active'): ?>
                                <span class="badge bg-success">نشط</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غير نشط</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" class="text-muted py-4">لم يتم تسجيل دخول أي عضو اليوم بعد</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer text-end">
              <a href="check-in.php" class="btn btn-sm btn-outline-primary">عرض كل سجلات الدخول</a>
            </div>
          </div>
          <!-- /.card -->

          <!-- Card: Financial & Invoice Info -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h3 class="card-title text-primary"><i class="bi bi-receipt-cutoff me-2"></i>إعدادات الفواتير الحالية</h3>
            </div>
            <div class="card-body">
              <div class="row text-center">
                  <div class="col-md-4 border-end">
                      <h6 class="text-muted">الضريبة المضافة</h6>
                      <h4 class="fw-bold"><?php echo htmlspecialchars($sys_settings['tax_rate']); ?>%</h4>
                  </div>
                  <div class="col-md-4 border-end">
                      <h6 class="text-muted">العملة الافتراضية</h6>
                      <h4 class="fw-bold"><?php echo htmlspecialchars($currency); ?></h4>
                  </div>
                  <div class="col-md-4">
                      <h6 class="text-muted">رسالة الفواتير</h6>
                      <p class="mb-0 text-success fw-bold">"<?php echo htmlspecialchars($sys_settings['invoice_message']); ?>"</p>
                  </div>
              </div>
            </div>
          </div>

        </div>
        <!-- /.col -->

        <!-- Start Col (Right Section) -->
        <div class="col-lg-4 connectedSortable">
          
          <!-- Card: Quick Actions (متاحة فقط للـ Admin والـ Staff) -->
          <?php if (hasRole(['admin', 'staff'])): ?>
          <div class="card mb-4">
            <div class="card-header bg-dark text-white">
              <h3 class="card-title"><i class="bi bi-lightning-charge-fill me-2"></i>إجراءات سريعة</h3>
            </div>
            <div class="card-body d-grid gap-2">
              <a href="add-member.php" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus-fill me-2"></i>إضافة عضو جديد
              </a>
              <a href="subscriptions.php" class="btn btn-success btn-lg">
                <i class="bi bi-arrow-repeat me-2"></i>تجديد اشتراك
              </a>
              <a href="check-in.php" class="btn btn-info text-white btn-lg">
                <i class="bi bi-qr-code-scan me-2"></i>تسجيل دخول عضو
              </a>
              <a href="create-invoice.php" class="btn btn-warning btn-lg">
                <i class="bi bi-receipt me-2"></i>إنشاء فاتورة جديدة
              </a>
            </div>
          </div>
          <?php endif; ?>

          <!-- Card: Working Hours -->
          <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
              <h3 class="card-title"><i class="bi bi-clock me-2"></i>مواعيد العمل</h3>
            </div>
            <div class="card-body text-center">
                <h5>يومياً</h5>
                <p class="mb-0 fs-5 text-success fw-bold">
                    من <?php echo date("h:i A", strtotime($sys_settings['open_time'])); ?> 
                    إلى <?php echo date("h:i A", strtotime($sys_settings['close_time'])); ?>
                </p>
            </div>
          </div>

        </div>
        <!-- /.col -->
      </div>
      <!-- /.row (main row) -->
    </div>
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>
