<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. فحص تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. تقييد الوصول: السماح فقط للمسؤول (admin) وحظر الموظفين (staff)
if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) !== 'admin') {
    $_SESSION['error'] = 'عذراً، لا تمتلك الصلاحية للدخول إلى صفحة التقارير والمالية.';
    header('Location: index.php');
    exit;
}

// 3. استدعاء ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/config/db.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    die("خطأ في الاتصال بقاعدة البيانات.");
}

// 4. إحصائيات الأعضاء
$total_members_query = $conn->query("SELECT COUNT(*) as count FROM members");
$total_members = $total_members_query ? $total_members_query->fetch_assoc()['count'] : 0;

$new_members_query = $conn->query("SELECT COUNT(*) as count FROM members WHERE MONTH(join_date) = MONTH(CURRENT_DATE()) AND YEAR(join_date) = YEAR(CURRENT_DATE())");
$new_members = $new_members_query ? $new_members_query->fetch_assoc()['count'] : 0;

// 5. إحصائيات الاشتراكات
$active_subs_query = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
$active_subs = $active_subs_query ? $active_subs_query->fetch_assoc()['count'] : 0;

$expired_subs_query = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'expired'");
$expired_subs = $expired_subs_query ? $expired_subs_query->fetch_assoc()['count'] : 0;

// 6. حساب إجمالي الإيرادات الشامل (الاشتراكات + الفواتير/المبيعات)
$total_subs_revenue = 0;
$subs_check = $conn->query("SHOW TABLES LIKE 'subscriptions'");
if ($subs_check && $subs_check->num_rows > 0) {
    // معرفة اسم عامود السعر الموجود في جدول الاشتراكات تلقائياً
    $column_to_use = null;
    $col_check = $conn->query("SHOW COLUMNS FROM subscriptions");
    if ($col_check) {
        while ($col = $col_check->fetch_assoc()) {
            if (in_array($col['Field'], ['price', 'cost', 'amount'])) {
                $column_to_use = $col['Field'];
                break;
            }
        }
    }
    
    // إذا وجد العامود يحسب المجموع، وإلا يتجاوزه لعدم حدوث خطأ
    if ($column_to_use) {
        $subs_query = $conn->query("SELECT SUM(`$column_to_use`) as total FROM subscriptions");
        $total_subs_revenue = $subs_query ? ($subs_query->fetch_assoc()['total'] ?? 0) : 0;
    }
}

$total_inv_revenue = 0;
$invoices_check = $conn->query("SHOW TABLES LIKE 'invoices'");
if ($invoices_check && $invoices_check->num_rows > 0) {
    // جلب مجموع الفواتير
    $inv_query = $conn->query("SELECT SUM(amount) as total FROM invoices WHERE type != 'subscription'");
    if (!$inv_query) {
        $inv_query = $conn->query("SELECT SUM(amount) as total FROM invoices");
    }
    $total_inv_revenue = $inv_query ? ($inv_query->fetch_assoc()['total'] ?? 0) : 0;
} else {
    // فحص جدول المدفوعات كبديل
    $payments_check = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($payments_check && $payments_check->num_rows > 0) {
        $payments_query = $conn->query("SELECT SUM(amount) as total FROM payments");
        $total_inv_revenue = $payments_query ? ($payments_query->fetch_assoc()['total'] ?? 0) : 0;
    }
}

// الإيرادات الكلية
$total_revenue = $total_subs_revenue + $total_inv_revenue;

// جلب العملة من الإعدادات
$settings_query = $conn->query("SELECT currency FROM system_settings WHERE id=1");
$currency = ($settings_query && $settings_query->num_rows > 0) ? ($settings_query->fetch_assoc()['currency'] ?? 'ج.م') : 'ج.م';
?>

<?php $active_page = 'analytics'; ?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- استدعاء مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!--begin::App Main-->
<main class="app-main">
  <div class="app-content-header d-print-none">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>التقارير والإحصائيات والرسوم البيانية</h3>
        </div>
        <div class="col-sm-6 text-end">
          <a href="index.php" class="btn btn-primary me-2">
            <i class="bi bi-house-door-fill me-2"></i>لوحة التحكم
          </a>
          <button onclick="window.print()" class="btn btn-dark">
            <i class="bi bi-printer-fill me-2"></i>طباعة التقرير
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      
      <!-- الكروت العلوية -->
      <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
          <div class="info-box bg-primary text-white mb-4 shadow-sm">
            <span class="info-box-icon"><i class="bi bi-people"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">إجمالي الأعضاء</span>
              <span class="info-box-number fs-4"><?php echo number_format($total_members); ?></span>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 col-sm-6 col-12">
          <div class="info-box bg-success text-white mb-4 shadow-sm">
            <span class="info-box-icon"><i class="bi bi-cash-stack"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">إجمالي الإيرادات</span>
              <span class="info-box-number fs-4"><?php echo number_format($total_revenue, 2); ?> <?php echo htmlspecialchars($currency); ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
          <div class="info-box bg-info text-white mb-4 shadow-sm">
            <span class="info-box-icon"><i class="bi bi-check-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">اشتراكات نشطة</span>
              <span class="info-box-number fs-4"><?php echo number_format($active_subs); ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
          <div class="info-box bg-danger text-white mb-4 shadow-sm">
            <span class="info-box-icon"><i class="bi bi-x-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">اشتراكات منتهية</span>
              <span class="info-box-number fs-4"><?php echo number_format($expired_subs); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- قسم الرسوم البيانية -->
      <div class="row">
        <div class="col-md-6">
          <div class="card card-outline card-primary mb-4 shadow-sm">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-pie-chart-fill me-2"></i>توزيع حالة الاشتراكات</h3>
            </div>
            <div class="card-body d-flex justify-content-center">
              <div style="width: 300px; height: 300px;">
                <canvas id="subscriptionsChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card card-outline card-success mb-4 shadow-sm">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-bar-chart-fill me-2"></i>إحصائيات عامة للنظام</h3>
            </div>
            <div class="card-body d-flex justify-content-center">
              <div style="width: 100%; height: 300px;">
                <canvas id="barChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<script>
  document.addEventListener("DOMContentLoaded", function () {
      const ctxPie = document.getElementById('subscriptionsChart').getContext('2d');
      new Chart(ctxPie, {
          type: 'doughnut',
          data: {
              labels: ['اشتراكات نشطة', 'اشتراكات منتهية'],
              datasets: [{
                  data: [<?php echo (int)$active_subs; ?>, <?php echo (int)$expired_subs; ?>],
                  backgroundColor: ['#28a745', '#dc3545'],
                  borderWidth: 2
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false
          }
      });

      const ctxBar = document.getElementById('barChart').getContext('2d');
      new Chart(ctxBar, {
          type: 'bar',
          data: {
              labels: ['إجمالي الأعضاء', 'الأعضاء الجدد', 'الاشتراكات النشطة', 'الاشتراكات المنتهية'],
              datasets: [{
                  label: 'العدد',
                  data: [
                      <?php echo (int)$total_members; ?>, 
                      <?php echo (int)$new_members; ?>, 
                      <?php echo (int)$active_subs; ?>, 
                      <?php echo (int)$expired_subs; ?>
                  ],
                  backgroundColor: ['#007bff', '#17a2b8', '#28a745', '#dc3545'],
                  borderWidth: 1
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                  y: {
                      beginAtZero: true,
                      ticks: {
                          precision: 0
                      }
                  }
              }
          }
      });
  });
</script>

<?php require_once 'includes/footer.php'; ?>