<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <!--begin::Brand Link-->
    <a href="./index.php" class="brand-link">
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
          <a href="./index.php" class="nav-link active">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>لوحة التحكم</p>
          </a>
        </li>

        <!-- تسجيل الدخول السريع -->
        <li class="nav-item">
          <a href="./check-in.php" class="nav-link">
            <i class="nav-icon bi bi-qr-code-scan text-success"></i>
            <p>تسجيل دخول عضو</p>
          </a>
        </li>

        <li class="nav-header">إدارة الجيم</li>

        <!-- إدارة الأعضاء -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>
              الأعضاء
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./members-list.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>كل الأعضاء</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./member-add.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>إضافة عضو جديد</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./expiring-subscriptions.php" class="nav-link">
                <i class="nav-icon bi bi-circle text-warning"></i>
                <p>اشتراكات توشك على الانتهاء</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- الباقات والاشتراكات -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-card-checklist"></i>
            <p>
              الباقات والاشتراكات
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./packages.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>أنواع الباقات</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./subscriptions-log.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>سجل الاشتراكات</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- المدربين والتمارين -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-person-badge-fill"></i>
            <p>
              الكباتن والمدربين
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./trainers.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>قائمة المدربين</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./schedules.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>جدول الحصص والتمارين</p>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-header">المالية والمبيعات</li>

        <!-- الخزينة والمالية -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-cash-stack"></i>
            <p>
              المالية والخزينة
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./payments.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>سجل المدفوعات</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./expenses.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>المصروفات</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- المتجر والمكملات -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-shop"></i>
            <p>
              المتجر والمكملات
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./products.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>المنتجات والخدمات</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./pos.php" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>نقطة بيع (POS)</p>
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-header">الإعدادات والتقارير</li>

        <!-- التقارير -->
        <li class="nav-item">
          <a href="./reports.php" class="nav-link">
            <i class="nav-icon bi bi-graph-up-arrow"></i>
            <p>التقارير والإحصائيات</p>
          </a>
        </li>

        <!-- الإعدادات -->
        <li class="nav-item">
          <a href="./settings.php" class="nav-link">
            <i class="nav-icon bi bi-gear-fill"></i>
            <p>إعدادات النظام</p>
          </a>
        </li>

        <!-- تسجيل الخروج -->
        <li class="nav-item mt-3">
          <a href="./logout.php" class="nav-link text-danger">
            <i class="nav-icon bi bi-box-arrow-right"></i>
            <p>تسجيل الخروج</p>
          </a>
        </li>
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>
<!--end::Sidebar-->