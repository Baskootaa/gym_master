<?php
$active_page = 'trainers';
require_once 'config/db.php';

$errors = [];

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    header('Location: trainers.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM trainers WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header('Location: trainers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name'] ?? '');
    $specialty  = trim($_POST['specialty'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $experience = $_POST['experience_years'] ?? '';
    $status     = $_POST['status'] ?? 'نشط';
    $photo      = $trainer['photo']; 

    if ($name === '') {
        $errors[] = 'اسم المدرب مطلوب.';
    }
    if ($specialty === '') {
        $errors[] = 'التخصص مطلوب.';
    }
    if ($phone === '') {
        $errors[] = 'رقم التليفون مطلوب.';
    } elseif (!preg_match('/^01[0-9]{9}$/', $phone)) {
        $errors[] = 'رقم التليفون لازم يبدأ بـ 01 ويتكون من 11 رقم.';
    }
    if (!is_numeric($experience) || $experience < 0) {
        $errors[] = 'سنوات الخبرة لازم تكون رقم موجب.';
    }
    if (!in_array($status, ['نشط', 'إجازة'], true)) {
        $errors[] = 'حالة المدرب غير صحيحة.';
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSizeBytes       = 2 * 1024 * 1024;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'حصل خطأ أثناء رفع الصورة، حاول تاني.';
        } else {
            $tmpPath      = $_FILES['photo']['tmp_name'];
            $originalName = $_FILES['photo']['name'];
            $fileSize     = $_FILES['photo']['size'];
            $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'صيغة الصورة لازم تكون jpg أو jpeg أو png أو webp.';
            } elseif ($fileSize > $maxSizeBytes) {
                $errors[] = 'حجم الصورة أكبر من 2 ميجابايت.';
            } elseif (@getimagesize($tmpPath) === false) {
                $errors[] = 'الملف المرفوع مش صورة صحيحة.';
            } else {
                $newFileName = 'trainer_' . uniqid() . '.' . $extension;
                $uploadDir   = __DIR__ . '/assets/img/trainers/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                if (move_uploaded_file($tmpPath, $uploadDir . $newFileName)) {
                    $oldPhoto = $trainer['photo'];
                    $photo    = 'trainers/' . $newFileName;
                } else {
                    $errors[] = 'فشل حفظ الصورة الجديدة على السيرفر.';
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = 'UPDATE trainers
                SET name = :name,
                    specialty = :specialty,
                    phone = :phone,
                    experience_years = :experience_years,
                    photo = :photo,
                    status = :status
                WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'             => $name,
            ':specialty'        => $specialty,
            ':phone'            => $phone,
            ':experience_years' => (int) $experience,
            ':photo'            => $photo,
            ':status'           => $status,
            ':id'               => (int) $id,
        ]);

        if (isset($oldPhoto) && strpos($oldPhoto, 'trainers/') === 0) {
            $oldPath = __DIR__ . '/assets/img/' . $oldPhoto;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        header('Location: trainers.php?updated=1');
        exit;
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
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
          <h3 class="mb-0"><i class="bi bi-pencil-square me-2"></i>تعديل بيانات المدرب</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="./index.php">الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="./trainers.php">قائمة المدربين</a></li>
            <li class="breadcrumb-item active" aria-current="page">تعديل بيانات المدرب</li>
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

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">بيانات المدرب</h3>
            </div>
            <form method="post" action="./trainer-edit.php?id=<?php echo (int) $trainer['id']; ?>" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?php echo (int) $trainer['id']; ?>" />
              <div class="card-body">

                <div class="mb-3">
                  <label class="form-label">اسم المدرب</label>
                  <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? $trainer['name']); ?>"
                    required
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label">التخصص</label>
                  <input
                    type="text"
                    name="specialty"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['specialty'] ?? $trainer['specialty']); ?>"
                    required
                  />
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">رقم التليفون</label>
                    <input
                      type="text"
                      name="phone"
                      class="form-control"
                      value="<?php echo htmlspecialchars($_POST['phone'] ?? $trainer['phone']); ?>"
                      placeholder="01xxxxxxxxx"
                      required
                    />
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">سنوات الخبرة</label>
                    <input
                      type="number"
                      name="experience_years"
                      class="form-control"
                      min="0"
                      value="<?php echo htmlspecialchars($_POST['experience_years'] ?? $trainer['experience_years']); ?>"
                      required
                    />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">الحالة</label>
                  <?php $currentStatus = $_POST['status'] ?? $trainer['status']; ?>
                  <select name="status" class="form-select">
                    <option value="نشط" <?php echo ($currentStatus === 'نشط') ? 'selected' : ''; ?>>نشط</option>
                    <option value="إجازة" <?php echo ($currentStatus === 'إجازة') ? 'selected' : ''; ?>>إجازة</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">تغيير الصورة (اختياري)</label>
                  <input type="file" name="photo" class="form-control" accept="image/png, image/jpeg, image/webp" />
                  <div class="form-text">سيبها فاضية لو عايز تسيب الصورة الحالية زي ما هي.</div>
                </div>

              </div>
              <div class="card-footer text-end">
                <a href="./trainers.php" class="btn btn-secondary">إلغاء</a>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check-lg me-1"></i> حفظ التعديلات
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">الصورة الحالية</h3>
            </div>
            <div class="card-body text-center">
              <img
                src="./assets/img/<?php echo htmlspecialchars($trainer['photo']); ?>"
                alt="صورة <?php echo htmlspecialchars($trainer['name']); ?>"
                class="img-fluid img-circle"
                style="width: 150px; height: 150px; object-fit: cover;"
              />
            </div>
          </div>
        </div>
      </div>

    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>
