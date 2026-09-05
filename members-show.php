<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تصحيح مسار ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/config/db.php';

// تصحيح مسار الهيدر ليطابق باقي صفحات السيستم
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member = null;

if ($id > 0 && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $member = null;
    }
}

if (!$member) {
    echo '<main class="app-main"><div class="app-content"><div class="container-fluid mt-5"><div class="alert alert-danger text-center">عذراً، العضو غير موجود أو تم حذفه!</div><div class="text-center"><a href="members.php" class="btn btn-secondary">العودة لقائمة الأعضاء</a></div></div></div></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// حساب العمر بدقة
$age = 'غير متوفر';
if (!empty($member['birth_date']) && $member['birth_date'] !== '0000-00-00') {
    $birthDate = new DateTime($member['birth_date']);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
}

// معالجة الصورة
$photoDisplay = 'assets/img/user2-160x160.jpg';
if (!empty($member['photo'])) {
    if (strpos($member['photo'], 'data:image') === 0) {
        $photoDisplay = $member['photo'];
    } else {
        $photoDisplay = 'assets/img/' . $member['photo'];
    }
}
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">الملف الشخصي للعضو</h3>
        </div>
        <div class="col-sm-6 text-start">
          <a href="members.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right me-1"></i> رجوع للقائمة</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card card-primary card-outline shadow-sm">
            <div class="card-body box-profile text-center py-4">
              
              <div class="mb-3">
                <img src="<?= $photoDisplay ?>" class="rounded-circle shadow" alt="صورة العضو" style="width: 130px; height: 130px; object-fit: cover; border: 3px solid #0d6efd;">
              </div>

              <h3 class="profile-username text-center fw-bold"><?= htmlspecialchars($member['full_name']) ?></h3>
              <p class="text-muted text-center"><?= htmlspecialchars($member['membership_type'] ?? 'عضو مسجل') ?></p>

              <ul class="list-group list-group-unbordered mb-3 text-start">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>رقم الهاتف</b>
                  <span class="text-muted" style="direction: ltr;"><?= htmlspecialchars($member['phone']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>البريد الإلكتروني</b>
                  <span class="text-muted"><?= htmlspecialchars($member['email'] ?? 'غير متوفر') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>النوع</b>
                  <span class="text-muted"><?= ($member['gender'] == 'male') ? 'ذكر' : 'أنثى' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>العمر</b>
                  <span class="text-muted fw-bold text-primary"><?= $age ?> سنة</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>العنوان</b>
                  <span class="text-muted"><?= htmlspecialchars($member['address'] ?? 'غير متوفر') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>تاريخ الانضمام</b>
                  <span class="text-muted"><?= htmlspecialchars($member['join_date']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <b>حالة الاشتراك</b>
                  <span class="badge <?= ($member['status'] == 'active') ? 'bg-success' : 'bg-danger' ?>">
                    <?= ($member['status'] == 'active') ? 'نشط' : 'منتهي' ?>
                  </span>
                </li>
              </ul>

              <div class="d-flex justify-content-center gap-2">
                <a href="member-edit.php?id=<?= $member['id'] ?>" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i> تعديل البيانات</a>
                <a href="members.php" class="btn btn-outline-secondary">إغلاق</a>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
