<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
      <!--begin::Row-->
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">لوحة تحكم الجيم (Gym Dashboard)</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
            <li class="breadcrumb-item active" aria-current="page">لوحة التحكم</li>
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
      <!--begin::Row (Stats Widgets)-->
      <div class="row">
        <!-- Card 1: Total Members -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-primary">
            <div class="inner">
              <h3>240</h3>
              <p>إجمالي الأعضاء (Total Members)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
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
              <h3>185</h3>
              <p>اشتراكات نشطة (Active)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
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
              <h3>14</h3>
              <p>تنتهي هذا الأسبوع (Expiring Soon)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
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
              <h3>41</h3>
              <p>اشتراكات منتهية (Expired)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path clip-rule="evenodd" fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z"></path>
            </svg>
            <a href="expired.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
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
          
          <!-- Table: Recent Check-ins / Today's Attendance -->
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-person-check-fill me-2"></i>تسجيلات الدخول اليوم (Today Check-ins)</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                  <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                  <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                </button>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
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
                    <tr>
                      <td>1</td>
                      <td>أحمد محمود</td>
                      <td>VIP (سنوي)</td>
                      <td>05:30 م</td>
                      <td><span class="badge bg-success">نشط</span></td>
                    </tr>
                    <tr>
                      <td>2</td>
                      <td>محمد علي</td>
                      <td>شهري (كمال اجسام)</td>
                      <td>05:15 م</td>
                      <td><span class="badge bg-success">نشط</span></td>
                    </tr>
                    <tr>
                      <td>3</td>
                      <td>عمر خالد</td>
                      <td>3 شهور</td>
                      <td>04:45 م</td>
                      <td><span class="badge bg-warning text-dark">ينتهي قريباً</span></td>
                    </tr>
                    <tr>
                      <td>4</td>
                      <td>مصطفى إبراهيم</td>
                      <td>حصة واحدة (Daily Pass)</td>
                      <td>04:10 م</td>
                      <td><span class="badge bg-info">يومي</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer text-end">
              <a href="attendance.php" class="btn btn-sm btn-outline-primary">عرض كل سجلات الدخول</a>
            </div>
          </div>
          <!-- /.card -->

        </div>
        <!-- /.col -->

        <!-- Start Col (Right Section) -->
        <div class="col-lg-4 connectedSortable">
          
          <!-- Card: Quick Actions -->
          <div class="card mb-4">
            <div class="card-header bg-dark text-white">
              <h3 class="card-title"><i class="bi bi-lightning-charge-fill me-2"></i>إجراءات سريعة</h3>
            </div>
            <div class="card-body d-grid gap-2">
              <a href="add-member.php" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus-fill me-2"></i>إضافة عضو جديد
              </a>
              <a href="renew-subscription.php" class="btn btn-success btn-lg">
                <i class="bi bi-arrow-repeat me-2"></i>تجديد اشتراك
              </a>
              <a href="check-in.php" class="btn btn-info text-white btn-lg">
                <i class="bi bi-qr-code-scan me-2"></i>تسجيل دخول عضو
              </a>
            </div>
          </div>

          <!-- Card: Membership Packages Summary -->
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-card-checklist me-2"></i>أنواع الباقات المتاحة</h3>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  اشتراك شهري (Month Pass)
                  <span class="badge bg-primary rounded-pill">500 ج.م</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  اشتراك 3 شهور (Quarterly)
                  <span class="badge bg-primary rounded-pill">1350 ج.م</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  اشتراك سنوي (Annual VIP)
                  <span class="badge bg-primary rounded-pill">4500 ج.م</span>
                </li>
              </ul>
            </div>
          </div>

        </div>
        <!-- /.col -->
      </div>
      <!-- /.row (main row) -->
    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>