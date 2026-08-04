<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "لم يتم تحديد العضو المطلوب تعديله.";
    header("Location: members.php");
    exit();
}
$editId = (int) $_GET['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_member'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? 'ذكر';
    $birth_date = $_POST['birth_date'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $membership_type = $_POST['membership_type'] ?? 'شهري';
    $subscription_start = $_POST['subscription_start'] ?? '';
    $subscription_end = $_POST['subscription_end'] ?? '';
    $status = $_POST['status'] ?? 'نشط';
    $notes = trim($_POST['notes'] ?? '');
    if ($full_name === '' || $phone === '' || $subscription_start === '' || $subscription_end === '') {
        $_SESSION['error'] = "من فضلك املأ كل الحقول المطلوبة (الاسم، الهاتف، تاريخ بداية ونهاية الاشتراك).";
    } elseif ($subscription_end < $subscription_start) {
        $_SESSION['error'] = "تاريخ نهاية الاشتراك لازم يكون بعد تاريخ البداية.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE members SET full_name = ?, phone = ?, email = ?, gender = ?, birth_date = ?,
                 address = ?, membership_type = ?, subscription_start = ?, subscription_end = ?,
                 status = ?, notes = ? WHERE id = ?"
            );
            $stmt->execute([
                $full_name, $phone, ($email !== '' ? $email : null), $gender,
                ($birth_date !== '' ? $birth_date : null), ($address !== '' ? $address : null),
                $membership_type, $subscription_start, $subscription_end, $status,
                ($notes !== '' ? $notes : null), $editId,
            ]);
            $_SESSION['message'] = "تم تعديل بيانات العضو بنجاح!";
            header("Location: members.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "حدث خطأ أثناء حفظ التعديلات: " . $e->getMessage();
        }
    }
    $member = compact(
        'full_name', 'phone', 'email', 'gender', 'birth_date', 'address',
        'membership_type', 'subscription_start', 'subscription_end', 'status', 'notes'
    );
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
                    <a href="/members.php" class="btn btn-outline-secondary">
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
                    <h3 class="card-title">تعديل بيانات العضو: <?= htmlspecialchars($member['full_name']) ?></h3>
                </div>
                <form method="POST" action="/member-edit.php?id=<?= $editId ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم بالكامل <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?= htmlspecialchars($member['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($member['phone']) ?>" required>
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
                                    <option value="ذكر" <?= ($member['gender'] === 'ذكر') ? 'selected' : '' ?>>ذكر</option>
                                    <option value="أنثى" <?= ($member['gender'] === 'أنثى') ? 'selected' : '' ?>>أنثى</option>
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
                                       value="<?= htmlspecialchars($member['notes'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="/members.php" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" name="update_member" class="btn btn-warning">
                            <i class="bi bi-check-circle me-1"></i> حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
