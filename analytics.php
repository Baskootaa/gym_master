<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) !== 'admin') {
    $_SESSION['error'] = 'عذراً، لا تمتلك الصلاحية للدخول إلى صفحة التقارير والمالية.';
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("خطأ في الاتصال بقاعدة البيانات (PDO غير معرف).");
}

try {
    // 1. إحصائيات الأعضاء والأعضاء الجدد
    $total_members_stmt = $pdo->query("SELECT COUNT(*) as count FROM members");
    $total_members = $total_members_stmt ? $total_members_stmt->fetch(PDO::FETCH_ASSOC)['count'] : 0;

    $new_members_stmt = $pdo->query("SELECT COUNT(*) as count FROM members WHERE MONTH(join_date) = MONTH(CURRENT_DATE()) AND YEAR(join_date) = YEAR(CURRENT_DATE())");
    $new_members = $new_members_stmt ? $new_members_stmt->fetch(PDO::FETCH_ASSOC)['count'] : 0;

    // 2. إحصائيات الاشتراكات (بناءً على أحدث اشتراك لكل عضو لضمان عدم تكرار السجلات القديمة)
    $active_subs = 0;
    $expired_subs = 0;

    $subs_table_check = $pdo->query("SHOW TABLES LIKE 'subscriptions'");
    if ($subs_table_check && $subs_table_check->rowCount() > 0) {
        // استعلام ذكي يجلب حالة أحدث اشتراك لكل عضو بناءً على تاريخ النهاية أو الـ ID الأكبر
        $query_latest_subs = "
            SELECT 
                SUM(CASE WHEN end_date >= CURDATE() THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN end_date < CURDATE() THEN 1 ELSE 0 END) as expired_count
            FROM subscriptions s1
            WHERE s1.id = (
                SELECT MAX(s2.id) 
                FROM subscriptions s2 
                WHERE s2.member_id = s1.member_id
            )
        ";
        
        $stmt_subs = $pdo->query($query_latest_subs);
        if ($stmt_subs) {
            $subs_data = $stmt_subs->fetch(PDO::FETCH_ASSOC);
            $active_subs = $subs_data['active_count'] ?? 0;
            $expired_subs = $subs_data['expired_count'] ?? 0;
        }
    }

    // 3. حساب إيرادات مبيعات المتجر (POS) فقط من جدول sales
    $total_revenue = 0;
    $sales_table_check = $pdo->query("SHOW TABLES LIKE 'sales'");
    if ($sales_table_check && $sales_table_check->rowCount() > 0) {
        $sales_query = $pdo->query("SELECT SUM(total_price) as total FROM sales");
        if ($sales_query) {
            $total_revenue = $sales_query->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        }
    }

    // 4. العملة
    $settings_query = $pdo->query("SELECT currency FROM system_settings WHERE id=1");
    $currency = 'ج.م';
    if ($settings_query) {
        $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
        if ($settings && !empty($settings['currency'])) {
            $currency = $settings['currency'];
        }
    }

} catch (Exception $e) {
    $total_members = 0;
    $new_members = 0;
    $active_subs = 0;
    $expired_subs = 0;
    $total_revenue = 0;
    $currency = 'ج.م';
}
?>

<?php $active_page = 'analytics'; ?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
              <span class="info-box-text">إجمالي الإيرادات (المتجر)</span>
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

      <div class="row">
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
