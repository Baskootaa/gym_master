<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">مركز التنبيهات والإشعارات</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="/index.php">الرئيسية</a></li>
            <li class="breadcrumb-item active" aria-current="page">الإشعارات</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <!--end::App Content Header-->

  <!--begin::App Content-->
  <div class="app-content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h5 class="card-title m-0"><i class="bi bi-bell-fill me-2"></i>التنبيهات الحالية</h5>
        </div>
        <div class="card-body">
          <div class="list-group">
            
            <a href="/expiring.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <div>
                <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
                <strong>الاشتراكات المنتهية قريباً:</strong> يوجد <strong><?= $expiringCount ?></strong> اشتراكات تنتهي خلال الـ 3 أيام القادمة.
              </div>
              <span class="badge bg-warning rounded-pill">متابعة</span>
            </a>

            <a href="/members.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <div>
                <i class="bi bi-person-plus-fill text-success me-2 fs-5"></i>
                <strong>الأعضاء الجدد:</strong> تم تسجيل <strong><?= $newMembersCount ?></strong> أعضاء جدد اليوم.
              </div>
              <span class="badge bg-success rounded-pill">عرض الأعضاء</span>
            </a>

            <?php if ($isAdmin): ?>
            <a href="/finance/index.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <div>
                <i class="bi bi-cash-stack text-info me-2 fs-5"></i>
                <strong>التقرير المالي:</strong> تقرير الدخل اليومي جاهز للمراجعة.
              </div>
              <span class="badge bg-info rounded-pill">الخزينة</span>
            </a>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php
require_once __DIR__ . '/includes/footer.php';
?>