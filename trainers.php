<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_page = 'trainers';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/db.php';

// التحقق من صلاحية المستخدم (أدمن أو موظف)
$isStaffOrAdmin = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true);

$stmt = $pdo->query('SELECT * FROM trainers ORDER BY id DESC');
$trainers = $stmt->fetchAll();
?>

<!--begin::App Main-->
<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
      <!--begin::Row-->
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0"><i class="bi bi-person-badge-fill me-2"></i>قائمة المدربين (Trainers)</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">الرئيسية</a></li>
            <li class="breadcrumb-item">الكباتن والمدربين</li>
            <li class="breadcrumb-item active" aria-current="page">قائمة المدربين</li>
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

      <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle-fill me-2"></i> تمت إضافة المدرب بنجاح.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle-fill me-2"></i> تم تحديث بيانات المدرب بنجاح.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <i class="bi bi-check-circle-fill me-2"></i> تم حذف المدرب بنجاح.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!--begin::Toolbar-->
      <?php if ($isStaffOrAdmin): ?>
        <div class="mb-3">
          <a href="<?php echo BASE_URL; ?>trainer-add.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> إضافة مدرب جديد
          </a>
        </div>
      <?php endif; ?>
      <!--end::Toolbar-->

      <!--begin::Row (Trainer Cards)-->
      <div class="row">
        <?php if (empty($trainers)): ?>
          <div class="col-12">
            <div class="alert alert-info">لا يوجد مدربين مسجلين حاليًا.</div>
          </div>
        <?php endif; ?>

        <?php foreach ($trainers as $trainer): ?>
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile text-center">
                <div class="text-center">
                  <?php 
                    $trainerPhoto = $trainer['photo'] ?? '';
                    // فحص إذا كانت الصورة مسار ملف عادي وليست Base64 تالفة
                    if (!empty($trainerPhoto) && strpos($trainerPhoto, 'data:image') === false) {
                        $trainerImageSrc = BASE_URL . 'assets/img/' . $trainerPhoto;
                    } else {
                        $trainerImageSrc = BASE_URL . 'assets/img/default-150x150.png';
                    }
                  ?>
                  <img
                    class="profile-user-img img-fluid img-circle"
                    src="<?php echo $trainerImageSrc; ?>?v=<?php echo time(); ?>"
                    alt="صورة <?php echo htmlspecialchars($trainer['name'] ?? ''); ?>"
                    style="width: 100px; height: 100px; object-fit: cover;"
                  />
                </div>

                <h3 class="profile-username text-center mt-3">
                  <?php echo htmlspecialchars($trainer['name'] ?? ''); ?>
                </h3>

                <p class="text-center">
                  <?php if (($trainer['status'] ?? '') === 'نشط'): ?>
                    <span class="badge text-bg-success">نشط</span>
                  <?php else: ?>
                    <span class="badge text-bg-warning text-dark"><?php echo htmlspecialchars($trainer['status'] ?? ''); ?></span>
                  <?php endif; ?>
                </p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b><i class="bi bi-bullseye me-1"></i> التخصص</b>
                    <span class="float-end"><?php echo htmlspecialchars($trainer['specialty'] ?? ''); ?></span>
                  </li>
                  <li class="list-group-item">
                    <b><i class="bi bi-award-fill me-1"></i> سنوات الخبرة</b>
                    <span class="float-end"><?php echo (int) ($trainer['experience_years'] ?? 0); ?> سنوات</span>
                  </li>
                  <li class="list-group-item">
                    <b><i class="bi bi-telephone-fill me-1"></i> التليفون</b>
                    <span class="float-end"><?php echo htmlspecialchars($trainer['phone'] ?? ''); ?></span>
                  </li>
                </ul>

                <div class="d-flex gap-2">
                  <a href="<?php echo BASE_URL; ?>schedules.php" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-calendar-week me-1"></i> الجدول
                  </a>

                  <?php if ($isStaffOrAdmin): ?>
                    <a href="<?php echo BASE_URL; ?>trainer-edit.php?id=<?php echo (int) $trainer['id']; ?>" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <a
                      href="<?php echo BASE_URL; ?>trainer-delete.php?id=<?php echo (int) $trainer['id']; ?>"
                      class="btn btn-outline-danger btn-sm"
                      onclick="return confirm('متأكد إنك عايز تحذف <?php echo htmlspecialchars($trainer['name'] ?? '', ENT_QUOTES); ?>؟');"
                    >
                      <i class="bi bi-trash"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <!--end::Row-->

    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>
