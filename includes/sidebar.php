<?php
// الحصول على اسم الملف الحالي لتحديد الرابط النشط تلقائياً
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <!--begin::Brand Link-->
    <a href="<?= BASE_URL ?>index.php" class="brand-link">
      <!--begin::Brand Image-->
      <i class="bi bi-activity text-danger fs-3 me-2"></i>
      <!--end::Brand Image-->
      <!--begin::Brand Text-->
      <span class="brand-text fw-bold">GYM MASTER</span>
      <!--end::Brand Text-->
    </a>
    <!--end::Brand Link-->
  </div>
  <!--end::Sidebar Brand-->

  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        role="navigation"
        aria-label="Main navigation"
        data-accordion="false"
        id="navigation"
      >
        <!-- الرئيسية -->
        <li class="nav-item">
          <a href="<?= BASE_URL ?>index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>لوحة التحكم</p>
          </a>
        </li>

        <!-- تسجيل الدخول السريع (متاح فقط للـ Admin والـ Staff) -->
        <?php if (hasRole(['admin', 'staff'])): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>check-in.php" class="nav-link <?= ($currentPage == 'check-in.php') ? 'active' : '' ?>">
            <i class="nav-icon bi bi-qr-code-scan text-success"></i>
            <p>تسجيل دخول عضو</p>
          </a>
        </li>
        <?php endif; ?>

        <li class="nav-header">إدارة الجيم</li>

        <!-- إدارة الأعضاء -->
        <?php $membersPages = ['members.php', 'add-member.php', 'expiring.php', 'member-edit.php']; ?>
        <li class="nav-item <?= in_array($currentPage, $membersPages) ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= in_array($currentPage, $membersPages) ? 'active' : '' ?>">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>
              الأعضاء
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?= BASE_URL ?>members.php" class="nav-link <?= ($currentPage == 'members.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>كل الأعضاء</p>
              </a>
            </li>
            <?php if (hasRole(['admin', 'staff'])): ?>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>add-member.php" class="nav-link <?= ($currentPage == 'add-member.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>إضافة عضو جديد</p>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>expiring.php" class="nav-link <?= ($currentPage == 'expiring.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle text-warning"></i>
                <p>اشتراكات توشك على الانتهاء</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- الباقات والاشتراكات (متاحة للـ Admin والـ Staff فقط) -->
        <?php if (hasRole(['admin', 'staff'])): ?>
        <?php $subPages = ['packages.php', 'subscriptions.php']; ?>
        <li class="nav-item <?= in_array($currentPage, $subPages) ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= in_array($currentPage, $subPages) ? 'active' : '' ?>">
            <i class="nav-icon bi bi-card-checklist"></i>
            <p>
              الباقات والاشتراكات
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?= BASE_URL ?>packages.php" class="nav-link <?= ($currentPage == 'packages.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>أنواع الباقات</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>subscriptions.php" class="nav-link <?= ($currentPage == 'subscriptions.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>سجل الاشتراكات</p>
              </a>
            </li>
          </ul>
        </li>
        <?php endif; ?>

        <!-- المدربين والتمارين -->
        <?php $trainerPages = ['trainers.php', 'trainer-add.php', 'trainer-edit.php', 'schedules.php', 'session-add.php']; ?>
        <li class="nav-item <?= in_array($currentPage, $trainerPages) ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= in_array($currentPage, $trainerPages) ? 'active' : '' ?>">
            <i class="nav-icon bi bi-person-badge-fill"></i>
            <p>
              الكباتن والمدربين
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?= BASE_URL ?>trainers.php" class="nav-link <?= ($currentPage == 'trainers.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>قائمة المدربين</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>schedules.php" class="nav-link <?= ($currentPage == 'schedules.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>جدول الحصص والتمارين</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- الخزينة والمالية (متاحة فقط للـ Admin والـ Staff) -->
        <?php if (hasRole(['admin', 'staff'])): ?>
        <li class="nav-header">المالية والمبيعات</li>

        <?php $financePages = ['finance.php', 'add-expense.php', 'reports.php']; ?>
        <li class="nav-item <?= in_array($currentPage, $financePages) ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= in_array($currentPage, $financePages) ? 'active' : '' ?>">
            <i class="nav-icon bi bi-cash-stack"></i>
            <p>
              المالية والخزينة
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?= BASE_URL ?>finance.php" class="nav-link <?= ($currentPage == 'finance.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>سجل المدفوعات</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>add-expense.php" class="nav-link <?= ($currentPage == 'add-expense.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>تسجيل مصروف جديد</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>reports.php" class="nav-link <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>تقارير الخزينة</p>
              </a>
            </li>
          </ul>
        </li>
        <?php endif; ?>

        <!-- المتجر والمكملات -->
        <?php $shopPages = ['products.php', 'pos.php']; ?>
        <kbd class="d-none"></kbd>
        <li class="nav-item <?= in_array($currentPage, $shopPages) ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= in_array($currentPage, $shopPages) ? 'active' : '' ?>">
            <i class="nav-icon bi bi-shop"></i>
            <p>
              المتجر والمكملات
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?= BASE_URL ?>products.php" class="nav-link <?= ($currentPage == 'products.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>المنتجات والخدمات</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?= BASE_URL ?>pos.php" class="nav-link <?= ($currentPage == 'pos.php') ? 'active' : '' ?>">
                <i class="nav-icon bi bi-circle"></i>
                <p>نقطة بيع (POS)</p>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-header">الحساب والشخصي</li>

        <!-- تسجيل الخروج -->
        <li class="nav-item mt-3">
          <a href="<?= BASE_URL ?>logout.php" class="nav-link text-danger">
            <i class="nav-icon bi bi-box-arrow-right"></i>
            <p>تسجيل الخروج</p>
          </a>سم
        </li>
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->\
