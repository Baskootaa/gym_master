<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// حماية الصفحة: إعادة التوجيه لصفحة الأعضاء إذا لم يكن المستخدم admin أو staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    $_SESSION['error'] = 'غير مصرح لك بالوصول لصفحة تعديل بيانات الأعضاء.';
    header("Location: members.php");
    exit();
}

require_once __DIR__ . '/config/db.php';
if (function_exists('checkAccess')) {
    checkAccess(['admin', 'staff']);
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "لم يتم تحديد العضو المطلوب تعديله.";
    header("Location: members.php");
    exit();
}

$editId = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_member'])) {
    $full_name          = trim($_POST['full_name'] ?? '');
    $phone              = trim($_POST['phone'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $gender             = $_POST['gender'] ?? 'male';
    $birth_date         = $_POST['birth_date'] ?? '';
    $address            = trim($_POST['address'] ?? '');
    $membership_type    = $_POST['membership_type'] ?? 'شهري';
    $subscription_start = $_POST['subscription_start'] ?? '';
    $subscription_end   = $_POST['subscription_end'] ?? '';
    $status             = $_POST['status'] ?? 'active';
    $notes              = trim($_POST['notes'] ?? '');
    $photo_data         = $_POST['photo'] ?? ''; 

    if ($full_name === '' || $phone === '' || $subscription_start === '' || $subscription_end === '') {
        $_SESSION['error'] = "من فضلك املأ كل الحقول المطلوبة (الاسم، الهاتف، تاريخ بداية ونهاية الاشتراك).";
    } elseif ($subscription_end < $subscription_start) {
        $_SESSION['error'] = "تاريخ نهاية الاشتراك لازم يكون بعد تاريخ البداية.";
    } else {
        try {
            $stmt_old = $pdo->prepare("SELECT photo FROM members WHERE id = ?");
            $stmt_old->execute([$editId]);
            $old_member = $stmt_old->fetch();
            $final_photo = $old_member['photo'] ?? null;

            // لو تم طلب حذف الصورة
            if ($photo_data === 'DELETE') {
                $final_photo = null;
            } 
            // لو تم التقاط صورة جديدة بالكاميرا (Base64) - يتم تخزينها مباشرة لتجنب قيود السيرفر السحابي
            elseif (!empty($photo_data) && strpos($photo_data, 'data:image') === 0) {
                $final_photo = $photo_data;
            } 
            // الحفاظ على القديمة لو لم يتم التغيير
            elseif (!empty($photo_data)) {
                $final_photo = $photo_data;
            }

            $stmt = $pdo->prepare(
                "UPDATE members SET full_name = ?, phone = ?, email = ?, gender = ?, birth_date = ?,
                 address = ?, membership_type = ?, subscription_start = ?, subscription_end = ?,
                 status = ?, notes = ?, photo = ? WHERE id = ?"
            );
            $stmt->execute([
                $full_name, $phone, ($email !== '' ? $email : null), $gender,
                ($birth_date !== '' ? $birth_date : null), ($address !== '' ? $address : null),
                $membership_type, $subscription_start, $subscription_end, $status,
                ($notes !== '' ? $notes : null), $final_photo, $editId,
            ]);
            $_SESSION['message'] = "تم تعديل بيانات العضو بنجاح!";
            header("Location: members.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "حدث خطأ أثناء حفظ التعديلات: " . $e->getMessage();
        }
    }
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$editId]);
        $member = $stmt->fetch();
        if (!$member) {
            $_SESSION['error'] = "العضو المطلوب غير موجود.";
            header("Location: members.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "حدث خطأ أثناء جلب بيانات العضو: " . $e->getMessage();
        header("Location: members.php");
        exit();
    }
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">تعديل بيانات عضو</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <a href="./members.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-right me-1"></i> رجوع لقائمة الأعضاء
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">تعديل بيانات العضو: <?= htmlspecialchars($member['full_name'] ?? '') ?></h3>
                </div>
                <form method="POST" action="./member-edit.php?id=<?= $editId ?>">
                    <div class="card-body">
                        
                        <!-- قسم التحكم في صورة العضو والكاميرا -->
                        <div class="row mb-4 text-center">
                            <div class="col-12">
                                <label class="form-label d-block fw-bold">صورة العضو</label>
                                
                                <!-- منطقة عرض الصورة الحالية -->
                                <div class="mb-3">
                                    <?php 
                                    $photo_src = !empty($member['photo']) ? $member['photo'] : '';
                                    if ($photo_src && strpos($photo_src, 'http') !== 0 && strpos($photo_src, 'data:image') !== 0 && strpos($photo_src, '/') !== 0) {
                                        $photo_src = './' . $photo_src;
                                    }
                                    ?>
                                    <div id="preview-container" style="display: <?= !empty($member['photo']) ? 'block' : 'none' ?>;">
                                        <img id="photo-preview" src="<?= htmlspecialchars($photo_src) ?>" alt="صورة العضو" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;">
                                        <div class="mt-2">
                                            <button type="button" id="delete-photo-btn" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash-fill me-1"></i> حذف الصورة
                                            </button>
                                        </div>
                                    </div>
                                    <div id="no-photo-text" class="text-muted" style="display: <?= empty($member['photo']) ? 'block' : 'none' ?>;">
                                        <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                                            <i class="bi bi-person-fill fs-1"></i>
                                        </div>
                                        <p class="small mt-1">لا توجد صورة</p>
                                    </div>
                                </div>

                                <!-- أزرار تشغيل الكاميرا والتقاط الصورة -->
                                <div class="mb-3">
                                    <button type="button" id="open-camera-btn" class="btn btn-primary btn-sm me-2">
                                        <i class="bi bi-camera-video-fill me-1"></i> فتح الكاميرا
                                    </button>
                                    <button type="button" id="capture-btn" class="btn btn-success btn-sm" style="display: none;">
                                        <i class="bi bi-camera-fill me-1"></i> التقاط الصورة
                                    </button>
                                    <button type="button" id="close-camera-btn" class="btn btn-secondary btn-sm ms-2" style="display: none;">
                                        إغلاق الكاميرا
                                    </button>
                                </div>

                                <!-- شاشة الفيديو (مخفية افتراضياً حتى يتم الضغط على فتح الكاميرا) -->
                                <div id="camera-container" class="mb-3 d-flex justify-content-center" style="display: none !important;">
                                    <div style="width: 200px; height: 150px; background: #000; border-radius: 8px; overflow: hidden; position: relative;" class="border">
                                        <video id="camera-stream" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                                        <canvas id="camera-canvas" style="display:none;"></canvas>
                                    </div>
                                </div>

                                <input type="hidden" name="photo" id="photo-input" value="<?= htmlspecialchars($member['photo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم بالكامل <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?= htmlspecialchars($member['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($member['phone'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($member['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">النوع</label>
                                <select name="gender" class="form-select">
                                    <option value="male" <?= (($member['gender'] ?? 'male') === 'male') ? 'selected' : '' ?>>ذكر</option>
                                    <option value="female" <?= (($member['gender'] ?? '') === 'female') ? 'selected' : '' ?>>أنثى</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">تاريخ الميلاد</label>
                                <input type="date" name="birth_date" class="form-control"
                                       value="<?= htmlspecialchars($member['birth_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?= htmlspecialchars($member['address'] ?? '') ?>">
                        </div>
                        <hr>
                        <h6 class="text-muted mb-3">بيانات الاشتراك</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع الاشتراك</label>
                                <select name="membership_type" class="form-select">
                                    <?php foreach (['شهري', '3 شهور', '6 شهور', 'سنوي', 'حصة يومية'] as $type): ?>
                                        <option value="<?= $type ?>" <?= (($member['membership_type'] ?? '') === $type) ? 'selected' : '' ?>>
                                            <?= $type ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ بداية الاشتراك <span class="text-danger">*</span></label>
                                <input type="date" name="subscription_start" class="form-control"
                                       value="<?= htmlspecialchars($member['subscription_start'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ نهاية الاشتراك <span class="text-danger">*</span></label>
                                <input type="date" name="subscription_end" class="form-control"
                                       value="<?= htmlspecialchars($member['subscription_end'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">حالة الاشتراك</label>
                                <select name="status" class="form-select">
                                    <?php 
                                    $statuses = [
                                        'active' => 'نشط',
                                        'expired' => 'منتهي',
                                        'suspended' => 'موقوف'
                                    ];
                                    foreach ($statuses as $key => $label): 
                                    ?>
                                        <option value="<?= $key ?>" <?= (($member['status'] ?? 'active') === $key) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">ملاحظات</label>
                                <input type="text" name="notes" class="form-control"
                                       value="<?= htmlspecialchars($member['notes'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="./members.php" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" name="update_member" class="btn btn-warning">
                            <i class="bi bi-check-circle me-1"></i> حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const video = document.getElementById('camera-stream');
    const canvas = document.getElementById('camera-canvas');
    const openCameraBtn = document.getElementById('open-camera-btn');
    const closeCameraBtn = document.getElementById('close-camera-btn');
    const captureBtn = document.getElementById('capture-btn');
    const cameraContainer = document.getElementById('camera-container');
    const photoInput = document.getElementById('photo-input');
    const photoPreview = document.getElementById('photo-preview');
    const previewContainer = document.getElementById('preview-container');
    const noPhotoText = document.getElementById('no-photo-text');
    const deletePhotoBtn = document.getElementById('delete-photo-btn');

    let mediaStream = null;

    // فتح الكاميرا عند الضغط على الزر
    openCameraBtn.addEventListener('click', function () {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(function (stream) {
                mediaStream = stream;
                video.srcObject = stream;
                cameraContainer.style.setProperty('display', 'flex', 'important');
                captureBtn.style.display = 'inline-block';
                closeCameraBtn.style.display = 'inline-block';
                openCameraBtn.style.display = 'none';
            })
            .catch(function (err) {
                alert("تعسّر فتح الكاميرا، تأكد من الصلاحيات.");
                console.error("خطأ في تشغيل الكاميرا: ", err);
            });
    });

    // إغلاق الكاميرا
    function stopCamera() {
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            mediaStream = null;
        }
        cameraContainer.style.setProperty('display', 'none', 'important');
        captureBtn.style.display = 'none';
        closeCameraBtn.style.display = 'none';
        openCameraBtn.style.display = 'inline-block';
    }

    closeCameraBtn.addEventListener('click', stopCamera);

    // التقاط الصورة
    captureBtn.addEventListener('click', function () {
        canvas.width = video.videoWidth || 320;
        canvas.height = video.videoHeight || 240;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataURL = canvas.toDataURL('image/jpeg');
        photoInput.value = dataURL;
        
        photoPreview.src = dataURL;
        previewContainer.style.display = 'block';
        noPhotoText.style.display = 'none';

        stopCamera();
    });

    // حذف الصورة
    deletePhotoBtn.addEventListener('click', function () {
        photoInput.value = 'DELETE';
        previewContainer.style.display = 'none';
        noPhotoText.style.display = 'block';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
