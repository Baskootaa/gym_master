<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الداتابيز والذي يحتوي على دوال الحماية والصلاحيات
require_once __DIR__ . '/config/db.php';

// حماية الصفحة: السماح للأدمن (admin) والموظف (staff) فقط بإضافة الأعضاء
checkAccess(['admin', 'staff']);

$member = [
    'full_name'          => '',
    'phone'              => '',
    'email'              => '',
    'gender'             => 'male',
    'birth_date'         => '',
    'address'            => '',
    'membership_type'    => 'شهري',
    'subscription_start' => date('Y-m-d'),
    'subscription_end'   => '',
    'status'             => 'نشط',
    'notes'              => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $member['full_name']          = trim($_POST['full_name'] ?? '');
    $member['phone']              = trim($_POST['phone'] ?? '');
    $member['email']              = trim($_POST['email'] ?? '');
    $member['gender']             = $_POST['gender'] ?? 'male';
    $member['birth_date']         = $_POST['birth_date'] ?? '';
    $member['address']            = trim($_POST['address'] ?? '');
    $member['membership_type']    = $_POST['membership_type'] ?? 'شهري';
    $member['subscription_start'] = $_POST['subscription_start'] ?? '';
    $member['subscription_end']   = $_POST['subscription_end'] ?? '';
    $member['status']             = $_POST['status'] ?? 'نشط';
    $member['notes']              = trim($_POST['notes'] ?? '');

    // استلام صورة الكاميرا المبعثرة كـ Base64
    $webcamImage = $_POST['webcam_image'] ?? '';

    if ($member['full_name'] === '' || $member['phone'] === '' || $member['subscription_start'] === '' || $member['subscription_end'] === '') {
        $_SESSION['error'] = "من فضلك املأ كل الحقول المطلوبة (الاسم، الهاتف، تاريخ بداية ونهاية الاشتراك).";
    } elseif ($member['subscription_end'] < $member['subscription_start']) {
        $_SESSION['error'] = "تاريخ نهاية الاشتراك لازم يكون بعد تاريخ البداية.";
    } else {
        try {
            // 1. إدخال البيانات مبدئياً للحصول على الـ ID الخاص بالعضو
            $stmt = $pdo->prepare(
                "INSERT INTO members (full_name, phone, email, gender, birth_date, address,
                 membership_type, subscription_start, subscription_end, status, notes, photo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $member['full_name'],
                $member['phone'],
                ($member['email'] !== '' ? $member['email'] : null),
                $member['gender'],
                ($member['birth_date'] !== '' ? $member['birth_date'] : null),
                ($member['address'] !== '' ? $member['address'] : null),
                $member['membership_type'],
                $member['subscription_start'],
                $member['subscription_end'],
                $member['status'],
                ($member['notes'] !== '' ? $member['notes'] : null),
                '', // قيمة مؤقتة للصورة لحين حفظها بالـ ID
            ]);

            $newMemberId = $pdo->lastInsertId();
            $photoPath = null;

            // 2. معالجة وحفظ صورة الكاميرا إذا تم التقاطها
            if (!empty($webcamImage) && preg_match('/^data:image\/(\w+);base64,/', $webcamImage, $type)) {
                $data = substr($webcamImage, strpos($webcamImage, ',') + 1);
                $data = base64_decode($data);

                if ($data !== false) {
                    $extension = strtolower($type[1]);
                    if ($extension === 'jpeg') {
                        $extension = 'jpg';
                    }
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        $newFileName = 'member_' . $newMemberId . '.' . $extension;
                        $uploadDir = __DIR__ . '/assets/img/members/';

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        if (file_put_contents($uploadDir . $newFileName, $data) !== false) {
                            $photoPath = 'members/' . $newFileName;

                            // تحديث مسار الصورة في قاعدة البيانات بالاسم المرتب
                            $updateStmt = $pdo->prepare("UPDATE members SET photo = ? WHERE id = ?");
                            $updateStmt->execute([$photoPath, $newMemberId]);
                        }
                    }
                }
            }

            $_SESSION['message'] = "تمت إضافة العضو بنجاح!";
            header("Location: members.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "حدث خطأ أثناء حفظ بيانات العضو: " . $e->getMessage();
        }
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
                    <h3 class="mb-0">إضافة عضو جديد</h3>
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

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">بيانات العضو الجديد</h3>
                </div>
                <form method="POST" action="" id="add-member-form">
                    <div class="card-body">
                        
                        <!-- قسم التقاط الصورة بالكاميرا -->
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <label class="form-label d-block fw-bold mb-2">صورة العضو (التقاط بالكاميرا)</label>
                                
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    
                                    <!-- أزرار التحكم بفتح وإغلاق الكاميرا -->
                                    <div class="mb-2">
                                        <button type="button" id="toggle-camera-btn" class="btn btn-sm btn-primary">
                                            <i class="bi bi-camera-video me-1"></i> فتح الكاميرا
                                        </button>
                                    </div>

                                    <!-- شاشة عرض الكاميرا (مخفية افتراضياً لحين فتحها) -->
                                    <div id="camera-container" class="position-relative border rounded overflow-hidden bg-dark mb-2" style="width: 240px; height: 180px; display: none;">
                                        <video id="webcam" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                                        <canvas id="canvas" style="display: none;"></canvas>
                                        <img id="photo-preview" src="" alt="معاينة الصورة" style="display: none; width: 100%; height: 100%; object-fit: cover;" />
                                    </div>

                                    <!-- أزرار التقاط الصورة أو إعادة التقاطها -->
                                    <div class="btn-group" role="group" id="camera-controls" style="display: none;">
                                        <button type="button" id="snap-btn" class="btn btn-sm btn-success">
                                            <i class="bi bi-camera me-1"></i> التقاط الصورة
                                        </button>
                                        <button type="button" id="retake-btn" class="btn btn-sm btn-warning" style="display: none;">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> إعادة التقاط
                                        </button>
                                    </div>

                                    <!-- حقل مخفي لتخزين الصورة بصيغة Base64 وإرسالها مع الـ Form -->
                                    <input type="hidden" name="webcam_image" id="webcam_image" value="">
                                </div>
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم بالكامل <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control"
                                       placeholder="مثال: أحمد محمود" value="<?= htmlspecialchars($member['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control"
                                       placeholder="01xxxxxxxxx" value="<?= htmlspecialchars($member['phone']) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="example@mail.com" value="<?= htmlspecialchars($member['email']) ?>">
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
                                       value="<?= htmlspecialchars($member['birth_date']) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="address" class="form-control"
                                   placeholder="المحافظة / المنطقة" value="<?= htmlspecialchars($member['address']) ?>">
                        </div>
                        <hr>
                        <h6 class="text-muted mb-3">بيانات الاشتراك</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع الاشتراك</label>
                                <select name="membership_type" class="form-select">
                                    <?php foreach (['شهري', '3 شهور', '6 شهور', 'سنوي', 'حصة يومية'] as $type): ?>
                                        <option value="<?= $type ?>" <?= ($member['membership_type'] === $type) ? 'selected' : '' ?>>
                                            <?= $type ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ بداية الاشتراك <span class="text-danger">*</span></label>
                                <input type="date" name="subscription_start" class="form-control"
                                       value="<?= htmlspecialchars($member['subscription_start']) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ نهاية الاشتراك <span class="text-danger">*</span></label>
                                <input type="date" name="subscription_end" class="form-control"
                                       value="<?= htmlspecialchars($member['subscription_end']) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">حالة الاشتراك</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['نشط', 'منتهي', 'موقوف'] as $st): ?>
                                        <option value="<?= $st ?>" <?= ($member['status'] === $st) ? 'selected' : '' ?>>
                                            <?= $st ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">ملاحظات</label>
                                <input type="text" name="notes" class="form-control"
                                       placeholder="أي ملاحظات إضافية عن العضو" value="<?= htmlspecialchars($member['notes']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="./members.php" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" name="add_member" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> إضافة العضو
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- جافاسكريبت لتشغيل وإيقاف الكاميرا والتقاط الصورة -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const photoPreview = document.getElementById('photo-preview');
    const toggleCameraBtn = document.getElementById('toggle-camera-btn');
    const cameraContainer = document.getElementById('camera-container');
    const cameraControls = document.getElementById('camera-controls');
    const snapBtn = document.getElementById('snap-btn');
    const retakeBtn = document.getElementById('retake-btn');
    const webcamImageInput = document.getElementById('webcam_image');
    const form = document.getElementById('add-member-form');
    
    let streamInstance = null;
    let isCameraOpen = false;

    // زر فتح / إغلاق الكاميرا
    toggleCameraBtn.addEventListener('click', function () {
        if (!isCameraOpen) {
            // فتح الكاميرا
            navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } })
                .then(function (stream) {
                    streamInstance = stream;
                    video.srcObject = stream;
                    video.style.display = 'block';
                    photoPreview.style.display = 'none';
                    cameraContainer.style.display = 'block';
                    cameraControls.style.display = 'block';
                    
                    toggleCameraBtn.innerHTML = '<i class="bi bi-camera-video-off me-1"></i> إغلاق الكاميرا';
                    toggleCameraBtn.classList.remove('btn-primary');
                    toggleCameraBtn.classList.add('btn-danger');
                    isCameraOpen = true;
                })
                .catch(function (err) {
                    console.error("تعذر الوصول إلى الكاميرا: ", err);
                    alert("عذراً، لا يمكن تشغيل الكاميرا. تأكد من إعطاء الصلاحية للمتصفح.");
                });
        } else {
            // إغلاق الكاميرا تماماً وإيقاف البث
            stopCamera();
            cameraContainer.style.display = 'none';
            cameraControls.style.display = 'none';
            
            toggleCameraBtn.innerHTML = '<i class="bi bi-camera-video me-1"></i> فتح الكاميرا';
            toggleCameraBtn.classList.remove('btn-danger');
            toggleCameraBtn.classList.add('btn-primary');
            isCameraOpen = false;
        }
    });

    // دالة لإيقاف مسارات الفيديو
    function stopCamera() {
        if (streamInstance) {
            streamInstance.getTracks().forEach(track => track.stop());
            streamInstance = null;
        }
    }

    // زر التقاط الصورة
    snapBtn.addEventListener('click', function () {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth || 320;
        canvas.height = video.videoHeight || 240;
        
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataURL = canvas.toDataURL('image/jpeg', 0.85);
        webcamImageInput.value = dataURL;

        // إيقاف بث الفيديو مؤقتاً وعرض المعاينة الثابتة
        photoPreview.src = dataURL;
        photoPreview.style.display = 'block';
        video.style.display = 'none';

        snapBtn.style.display = 'none';
        retakeBtn.style.display = 'inline-block';
    });

    // زر إعادة التقاط الصورة
    retakeBtn.addEventListener('click', function () {
        webcamImageInput.value = '';
        photoPreview.src = '';
        photoPreview.style.display = 'none';
        video.style.display = 'block';

        retakeBtn.style.display = 'none';
        snapBtn.style.display = 'inline-block';
    });

    // إيقاف تشغيل الكاميرا تماماً عند إرسال الفورم لمنع التعليق
    form.addEventListener('submit', function () {
        stopCamera();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
