<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
checkAccess(['admin', 'staff']);

// 1. حظر الوصول لغير الأدمن والموظفين (Staff)
$userRole = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($userRole, ['admin', 'staff'], true)) {
    header('Location: trainers.php');
    exit;
}

$active_page = 'trainers';
$errors = [];
$photo  = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name'] ?? '');
    $specialty  = trim($_POST['specialty'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $experience = $_POST['experience_years'] ?? '';
    $status     = $_POST['status'] ?? 'نشط';

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
    $maxSizeBytes       = 2 * 1024 * 1024; // 2 ميجا

    $tmpPath      = '';
    $extension    = '';

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'لازم ترفع صورة للمدرب.';
    } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
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
        }
    }

    if (empty($errors)) {
        // الخطوة أ: إدخال بيانات المدرب الأول في قاعدة البيانات للحصول على الـ ID الخاص به
        $sql = 'INSERT INTO trainers (name, specialty, phone, experience_years, photo, status)
                VALUES (:name, :specialty, :phone, :experience_years, :photo, :status)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'             => $name,
            ':specialty'        => $specialty,
            ':phone'            => $phone,
            ':experience_years' => (int) $experience,
            ':photo'            => '', // قيمة مؤقتة لحين حفظ الصورة بالـ ID
            ':status'           => $status,
        ]);

        $newTrainerId = $pdo->lastInsertId();

        // الخطوة ب: استخدام مسار نسبي مباشر وآمن تماماً لتجنب مشاكل الصلاحيات على بيئة Render
        $newFileName = 'trainer_' . $newTrainerId . '.' . $extension;
        $uploadDir   = 'assets/img/trainers/';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        if (move_uploaded_file($tmpPath, $uploadDir . $newFileName)) {
            $photo = 'trainers/' . $newFileName;

            // الخطوة ج: تحديث حقل الصورة في القاعدة بالمسار الصحيح النهائي
            $updateStmt = $pdo->prepare('UPDATE trainers SET photo = :photo WHERE id = :id');
            $updateStmt->execute([
                ':photo' => $photo,
                ':id'    => $newTrainerId
            ]);
        } else {
            $errors[] = 'فشل حفظ الصورة على السيرفر (مشكلة في مسار الحفظ أو الصلاحيات).';
        }

        if (empty($errors)) {
            header('Location: trainers.php?added=1');
            exit;
        }
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
          <h3 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>إضافة مدرب جديد</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="./index.php">الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="./trainers.php">قائمة المدربين</a></li>
            <li class="breadcrumb-item active" aria-current="page">إضافة مدرب جديد</li>
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

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">بيانات المدرب</h3>
        </div>
        <form method="post" action="./trainer-add.php" enctype="multipart/form-data">
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label">اسم المدرب</label>
              <input
                type="text"
                name="name"
                class="form-control"
                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                required
              />
            </div>

            <div class="mb-3">
              <label class="form-label">التخصص</label>
              <input
                type="text"
                name="specialty"
                class="form-control"
                value="<?php echo htmlspecialchars($_POST['specialty'] ?? ''); ?>"
                placeholder="مثال: كمال أجسام، يوجا، سباحة..."
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
                  value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
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
                  value="<?php echo htmlspecialchars($_POST['experience_years'] ?? '0'); ?>"
                  required
                />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">الحالة</label>
              <select name="status" class="form-select">
                <option value="نشط" <?php echo (($_POST['status'] ?? '') === 'نشط') ? 'selected' : ''; ?>>نشط</option>
                <option value="إجازة" <?php echo (($_POST['status'] ?? '') === 'إجازة') ? 'selected' : ''; ?>>إجازة</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">صورة المدرب</label>
              <input type="file" name="photo" class="form-control" accept="image/png, image/jpeg, image/webp" required />
              <div class="form-text">الصيغ المسموحة: jpg، jpeg، png، webp — بحد أقصى 2 ميجابايت.</div>
            </div>

          </div>
          <div class="card-footer text-end">
            <a href="./trainers.php" class="btn btn-secondary">إلغاء</a>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i> حفظ المدرب
            </button>
          </div>
        </form>
      </div>

    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content-->
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>
