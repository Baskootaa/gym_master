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
    'gender'             => 'ذكر',
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
    $member['gender']             = $_POST['gender'] ?? 'ذكر';
    $member['birth_date']         = $_POST['birth_date'] ?? '';
    $member['address']            = trim($_POST['address'] ?? '');
    $member['membership_type']    = $_POST['membership_type'] ?? 'شهري';
    $member['subscription_start'] = $_POST['subscription_start'] ?? '';
    $member['subscription_end']   = $_POST['subscription_end'] ?? '';
    $member['status']             = $_POST['status'] ?? 'نشط';
    $member['notes']              = trim($_POST['notes'] ?? '');

    if ($member['full_name'] === '' || $member['phone'] === '' || $member['subscription_start'] === '' || $member['subscription_end'] === '') {
        $_SESSION['error'] = "من فضلك املأ كل الحقول المطلوبة (الاسم، الهاتف، تاريخ بداية ونهاية الاشتراك).";
    } elseif ($member['subscription_end'] < $member['subscription_start']) {
        $_SESSION['error'] = "تاريخ نهاية الاشتراك لازم يكون بعد تاريخ البداية.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO members (full_name, phone, email, gender, birth_date, address,
                 membership_type, subscription_start, subscription_end, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
            ]);
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
                <form method="POST" action="">
                    <div class="card-body">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>