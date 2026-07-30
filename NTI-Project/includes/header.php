<!doctype html>
<html lang="ar" dir="rtl">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>لوحة التحكم | نظام إدارة الجيم</title>

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
    <link rel="preload" href="./css/adminlte.rtl.css" as="style" />
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
    <link rel="stylesheet" href="./css/adminlte.rtl.css" />
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

    <!--begin::Custom Fixes (RTL Sidebar & Small Box Icons)-->
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
        left: 15px !important; /* نقل الأيقونة لأقصى اليسار بعيداً عن الرقم */
        top: 15px !important;
      }

      html[dir="rtl"] .small-box .inner h3,
      body[dir="rtl"] .small-box .inner h3 {
        position: relative;
        z-index: 2; /* إظهار الرقم فوق الأيقونة */
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
              <a href="./index.php" class="nav-link">
                <i class="bi bi-house-door me-1" aria-hidden="true"></i>
                الرئيسية
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="./check-in.php" class="nav-link">
                <i class="bi bi-qr-code-scan me-1" aria-hidden="true"></i>
                تسجيل الدخول السريع
              </a>
            </li>
          </ul>
          <!--end::Start Navbar Links-->

          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Navbar Search-->
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>
            <!--end::Navbar Search-->

            <!--begin::Gym Notifications Dropdown Menu-->
            <li class="nav-item dropdown">
              <a class="nav-link" data-bs-toggle="dropdown" href="#">
                <i class="bi bi-bell-fill"></i>
                <span class="navbar-badge badge text-bg-danger">3</span>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <span class="dropdown-item dropdown-header">3 تنبيهات هامة</span>
                <div class="dropdown-divider"></div>
                <a href="expiring.php" class="dropdown-item">
                  <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> 5 اشتراكات تنتهي اليوم
                  <span class="float-end text-secondary fs-7">الآن</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="members.php" class="dropdown-item">
                  <i class="bi bi-person-plus-fill me-2 text-success"></i> 3 أعضاء جدد تم تسجيلهم
                  <span class="float-end text-secondary fs-7">منذ ساعة</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="payments.php" class="dropdown-item">
                  <i class="bi bi-cash-stack me-2 text-info"></i> تقرير الدخل اليومي جاهز
                  <span class="float-end text-secondary fs-7">منذ ساعتين</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="notifications.php" class="dropdown-item dropdown-footer">عرض كل التنبيهات</a>
              </div>
            </li>
            <!--end::Notifications Dropdown Menu-->

            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
              </a>
            </li>
            <!--end::Fullscreen Toggle-->

            <!--begin::Color Mode Toggle-->
            <li class="nav-item dropdown">
              <a
                class="nav-link"
                href="#"
                id="bd-theme"
                aria-label="Toggle color scheme"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
              </a>
              <ul
                class="dropdown-menu dropdown-menu-end"
                aria-labelledby="bd-theme"
                style="--bs-dropdown-min-width: 8rem"
              >
                <li>
                  <button
                    type="button"
                    class="dropdown-item d-flex align-items-center"
                    data-bs-theme-value="light"
                    aria-pressed="false"
                  >
                    <i class="bi bi-sun-fill me-2"></i>
                    فاتح (Light)
                    <i class="bi bi-check-lg ms-auto d-none"></i>
                  </button>
                </li>
                <li>
                  <button
                    type="button"
                    class="dropdown-item d-flex align-items-center"
                    data-bs-theme-value="dark"
                    aria-pressed="false"
                  >
                    <i class="bi bi-moon-fill me-2"></i>
                    داكن (Dark)
                    <i class="bi bi-check-lg ms-auto d-none"></i>
                  </button>
                </li>
                <li>
                  <button
                    type="button"
                    class="dropdown-item d-flex align-items-center active"
                    data-bs-theme-value="auto"
                    aria-pressed="true"
                  >
                    <i class="bi bi-circle-half me-2"></i>
                    تلقائي (Auto)
                    <i class="bi bi-check-lg ms-auto d-none"></i>
                  </button>
                </li>
              </ul>
            </li>
            <!--end::Color Mode Toggle-->

            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="./assets/img/user2-160x160.jpg"
                  class="user-image rounded-circle shadow"
                  alt="User Image"
                />
                <span class="d-none d-md-inline">مدير الجيم (Admin)</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <!--begin::User Image-->
                <li class="user-header text-bg-primary">
                  <img
                    src="./assets/img/user2-160x160.jpg"
                    class="rounded-circle shadow"
                    alt="User Image"
                  />
                  <p>
                    أحمد علي - مدير النظام
                    <small>مسؤول النظام منذ 2024</small>
                  </p>
                </li>
                <!--end::User Image-->
                <!--begin::Menu Body-->
                <li class="user-body">
                  <!--begin::Row-->
                  <div class="row text-center">
                    <div class="col-4">
                      <a href="reports.php" class="btn btn-link btn-sm text-decoration-none">التقارير</a>
                    </div>
                    <div class="col-4">
                      <a href="payments.php" class="btn btn-link btn-sm text-decoration-none">الخزينة</a>
                    </div>
                    <div class="col-4">
                      <a href="settings.php" class="btn btn-link btn-sm text-decoration-none">الإعدادات</a>
                    </div>
                  </div>
                  <!--end::Row-->
                </li>
                <!--end::Menu Body-->
                <!--begin::Menu Footer-->
                <li class="user-footer">
                  <a href="profile.php" class="btn btn-outline-secondary">الملف الشخصي</a>
                  <a href="logout.php" class="btn btn-outline-danger float-end">تسجيل الخروج</a>
                </li>
                <!--end::Menu Footer-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->