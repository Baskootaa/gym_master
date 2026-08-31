<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// الاتصال بقاعدة البيانات للجلب الديناميكي للإشعارات وبيانات المستخدم
require_once __DIR__ . '/../config/db.php';

$displayName = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'مدير النظام';
$displayRole = $_SESSION['user_role'] ?? 'Admin';
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

// جلب صورة المستخدم الحالية من قاعدة البيانات لتعرض في الهيدر والـ Navbar
$header_avatar = '';
if ($user_id > 0 && isset($pdo)) {
    try {
        $stmt_avatar = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
        $stmt_avatar->execute([$user_id]);
        $user_data_hdr = $stmt_avatar->fetch(PDO::FETCH_ASSOC);
        if ($user_data_hdr && !empty($user_data_hdr['photo'])) {
            $header_avatar = $user_data_hdr['photo'];
        }
    } catch (Exception $e) {
        // تجاهل الخطأ واستخدام الافتراضي عند التعذر
    }
}

// تحديد مسار عرض الصورة في الهيدر
$header_avatar_display = '';
if (!empty($header_avatar)) {
    if (strpos($header_avatar, 'data:image') === 0 || strpos($header_avatar, 'data:') === 0) {
        $header_avatar_display = $header_avatar;
    } else {
        $header_avatar_display = 'assets/img/' . $header_avatar;
    }
} else {
    $header_avatar_display = 'assets/img/user2-160x160.jpg';
}

// --- استعلامات جرس الإشعارات الديناميكية ---
$expiringCount = 0;
$newMembersCount = 0;

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    // 1. حساب الاشتراكات المنتهية اليوم أو خلال الـ 3 أيام القادمة
    $expQuery = $conn->query("SELECT COUNT(*) as total FROM subscriptions WHERE end_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)");
    if ($expQuery) {
        $expiringCount = (int)$expQuery->fetch_assoc()['total'];
    }

    // 2. حساب الأعضاء الجدد المسجلين اليوم
    $newQuery = $conn->query("SELECT COUNT(*) as total FROM members WHERE DATE(join_date) = CURRENT_DATE()");
    if ($newQuery) {
        $newMembersCount = (int)$newQuery->fetch_assoc()['total'];
    }
}

// حساب إجمالي التنبيهات مع إضافة تنبيه الملاحظة المالية للمدير فقط
$totalNotifications = $expiringCount + $newMembersCount + ($isAdmin ? 1 : 0);
?>
<!doctype html>
<html lang="ar" dir="rtl">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>لوحة التحكم | نظام إدارة الجيم</title>
      <link rel="manifest" href="manifest.json">
      <meta name="theme-color" content="#000000">
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->

    <!--begin::Primary Meta Tags-->
    <meta name="title" content="لوحة التحكم | نظام إدارة الجيم" />
    <meta name="description" content="نظام متكامل لإدارة اشتراكات وأعضاء الجيم" />
    <!--end::Primary Meta Tags-->

    <!--begin::Accessibility Features-->
    <link rel="preload" href="css/adminlte.rtl.css" as="style" />
    <!--end::Accessibility Features-->

    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media = 'all'"
    />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::Required Plugin(AdminLTE RTL)-->
    <link rel="stylesheet" href="css/adminlte.rtl.css" />
    <!--end::Required Plugin(AdminLTE RTL)-->

    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />

    <!-- jsvectormap -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />

    <!--begin::Custom Fixes & Accessibility Styles-->
    <style>
      /* 1. رفع طبقة السايدبار ليكون فوق المحتوى دائماً */
      html[dir="rtl"] .app-sidebar,
      body[dir="rtl"] .app-sidebar,
      .app-sidebar {
        z-index: 1038 !important;
      }

      /* 2. إخفاء وإظهار السايدبار في وضع الـ Collapse لجهة اليمين */
      @media (min-width: 992px) {
        body.sidebar-collapse .app-sidebar,
        .sidebar-collapse .app-sidebar {
          transform: translate3d(100%, 0, 0) !important;
        }

        body.sidebar-collapse .app-main,
        body.sidebar-collapse .app-header,
        body.sidebar-collapse .app-footer,
        .sidebar-collapse .app-main,
        .sidebar-collapse .app-header,
        .sidebar-collapse .app-footer {
          margin-right: 0 !important;
          margin-left: 0 !important;
        }
      }

      /* 3. نقل أرقام وأيقونات كروت الإحصائيات (Small Box) بعيداً عن التداخل */
      html[dir="rtl"] .small-box .icon,
      body[dir="rtl"] .small-box .icon,
      [dir="rtl"] .small-box .icon,
      .small-box > .icon {
        right: auto !important;
        left: 15px !important;
        top: 15px !important;
      }

      html[dir="rtl"] .small-box .inner h3,
      body[dir="rtl"] .small-box .inner h3 {
        position: relative;
        z-index: 2;
      }

      /* 4. تنسيق بطاقات إمكانية الوصول */
      .accessibility-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
      }
      .accessibility-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
        border-color: #0d6efd;
      }
      body.high-contrast-mode {
        background-color: #000 !important;
        color: #fff !important;
      }
      body.high-contrast-mode .card, 
      body.high-contrast-mode .navbar,
      body.high-contrast-mode .app-sidebar {
        background-color: #1a1a1a !important;
        color: #fff !important;
      }
      body.large-cursor-mode, body.large-cursor-mode * {
        cursor: pointer !important;
      }
      body.readable-font-mode * {
        font-family: Arial, sans-serif !important;
        letter-spacing: 0.5px !important;
      }
    </style>
    <!--end::Custom Fixes-->
  </head>
  <!--end::Head-->

  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>

            <li class="nav-item d-none d-md-block">
              <a href="index.php" class="nav-link">
                <i class="bi bi-house-door me-1" aria-hidden="true"></i>
                الرئيسية
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="check-in.php" class="nav-link">
                <i class="bi bi-qr-code-scan me-1" aria-hidden="true"></i>
                تسجيل الدخول السريع
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            
            <!--begin::Accessibility Button-->
            <li class="nav-item align-self-center me-2">
              <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 px-2 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#accessibilityDrawer" aria-controls="accessibilityDrawer">
                <i class="bi bi-universal-access fs-5"></i>
                <span class="d-none d-lg-inline">إمكانية الوصول</span>
              </button>
            </li>

            <!--begin::Navbar Search Button (يفتح نافذة مودال)-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#searchModal" role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>

            <!--begin::Gym Notifications Dropdown Menu (مربوط بقاعدة البيانات)-->
            <li class="nav-item dropdown">
              <a class="nav-link" data-bs-toggle="dropdown" href="#">
                <i class="bi bi-bell-fill"></i>
                <?php if ($totalNotifications > 0): ?>
                  <span class="navbar-badge badge text-bg-danger"><?= $totalNotifications ?></span>
                <?php endif; ?>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <span class="dropdown-item dropdown-header"><?= $totalNotifications ?> تنبيهات هامة</span>
                
                <div class="dropdown-divider"></div>
                <a href="expiring.php" class="dropdown-item">
                  <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> <?= $expiringCount ?> اشتراكات تنتهي قريباً
                  <span class="float-end text-secondary fs-7">مباشر</span>
                </a>
                
                <div class="dropdown-divider"></div>
                <a href="members.php" class="dropdown-item">
                  <i class="bi bi-person-plus-fill me-2 text-success"></i> <?= $newMembersCount ?> أعضاء جدد تم تسجيلهم اليوم
                  <span class="float-end text-secondary fs-7">اليوم</span>
                </a>
                
                <?php if ($isAdmin): ?>
                <div class="dropdown-divider"></div>
                <a href="finance.php" class="dropdown-item">
                  <i class="bi bi-cash-stack me-2 text-info"></i> تقرير الدخل اليومي جاهز
                  <span class="float-end text-secondary fs-7">مباشر</span>
                </a>
                <?php endif; ?>

                <div class="dropdown-divider"></div>
                <a href="notifications.php" class="dropdown-item dropdown-footer">عرض كل التنبيهات</a>
              </div>
            </li>

            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
              </a>
            </li>

            <!--begin::Color Mode Toggle-->
            <li class="nav-item dropdown">
              <a class="nav-link" href="#" id="bd-theme" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 8rem">
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
                    <i class="bi bi-sun-fill me-2"></i> فاتح (Light)
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
                    <i class="bi bi-moon-fill me-2"></i> داكن (Dark)
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto">
                    <i class="bi bi-circle-half me-2"></i> تلقائي (Auto)
                  </button>
                </li>
              </ul>
            </li>

            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img src="<?= $header_avatar_display ?>" class="user-image rounded-circle shadow" alt="User Image" style="object-fit: cover;" />
                <span class="d-none d-md-inline"><?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($displayRole) ?>)</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                  <img src="<?= $header_avatar_display ?>" class="rounded-circle shadow" alt="User Image" style="object-fit: cover;" />
                  <p>
                    <?= htmlspecialchars($displayName) ?> - <?= htmlspecialchars($displayRole) ?>
                  </p>
                </li>
                <li class="user-body">
                  <div class="row text-center">
                    <?php if ($isAdmin): ?>
                    <div class="col-4">
                      <a href="analytics.php" class="btn btn-link btn-sm text-decoration-none">التقارير</a>
                    </div>
                    <div class="col-4">
                      <a href="finance.php" class="btn btn-link btn-sm text-decoration-none">الخزينة</a>
                    </div>
                    <div class="col-4">
                      <a href="settings.php" class="btn btn-link btn-sm text-decoration-none">الإعدادات</a>
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                      <span class="text-muted fs-7">حساب موظف (Staff)</span>
                    </div>
                    <?php endif; ?>
                  </div>
                </li>
                <li class="user-footer">
                  <a href="profile.php" class="btn btn-outline-secondary">الملف الشخصي</a>
                  <a href="logout.php" class="btn btn-outline-danger float-end">تسجيل الخروج</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>
      <!--end::Header-->

      <!--begin::Search Modal (نافذة البحث السريع)-->
      <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="searchModalLabel"><i class="bi bi-search me-2"></i>البحث عن عضو</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="members.php" method="GET">
              <div class="modal-body">
                <div class="input-group">
                  <input type="text" name="search" class="form-control" placeholder="ادخل اسم العضو أو رقم الهاتف..." required autofocus>
                  <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> بحث</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!--end::Search Modal-->

      <!--begin::Accessibility Offcanvas Drawer-->
      <div class="offcanvas offcanvas-start" tabindex="-1" id="accessibilityDrawer" aria-labelledby="accessibilityDrawerLabel">
        <div class="offcanvas-header bg-primary text-white">
          <h5 class="offcanvas-title d-flex align-items-center gap-2" id="accessibilityDrawerLabel">
            <i class="bi bi-universal-access"></i> إمكانية الوصول
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body bg-body-tertiary">
          <div class="row g-3">
            <div class="col-6">
              <div class="card text-center p-3 h-100 shadow-sm accessibility-card" onclick="toggleContrast()" style="cursor: pointer;">
                <i class="bi bi-circle-half fs-2 my-2 text-primary"></i>
                <h6 class="card-title fs-6 mb-0">تباين الألوان</h6>
              </div>
            </div>
            <div class="col-6">
              <div class="card text-center p-3 h-100 shadow-sm accessibility-card" onclick="toggleFontSize()" style="cursor: pointer;">
                <i class="bi bi-type fs-2 my-2 text-primary"></i>
                <h6 class="card-title fs-6 mb-0">حجم الخط</h6>
              </div>
            </div>
            <div class="col-6">
              <div class="card text-center p-3 h-100 shadow-sm accessibility-card" onclick="toggleLargeCursor()" style="cursor: pointer;">
                <i class="bi bi-cursor-fill fs-2 my-2 text-primary"></i>
                <h6 class="card-title fs-6 mb-0">مؤشر كبير</h6>
              </div>
            </div>
            <div class="col-6">
              <div class="card text-center p-3 h-100 shadow-sm accessibility-card" onclick="toggleReadableFont()" style="cursor: pointer;">
                <i class="bi bi-fonts fs-2 my-2 text-primary"></i>
                <h6 class="card-title fs-6 mb-0">خط مقروء</h6>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!--end::Accessibility Offcanvas Drawer-->

      <script>
        function toggleContrast() { document.body.classList.toggle('high-contrast-mode'); }
        let isLargeFont = false;
        function toggleFontSize() {
            isLargeFont = !isLargeFont;
            document.body.style.fontSize = isLargeFont ? '115%' : '100%';
        }
        function toggleLargeCursor() { document.body.classList.toggle('large-cursor-mode'); }
        function toggleReadableFont() { document.body.classList.toggle('readable-font-mode'); }
      </script>
      