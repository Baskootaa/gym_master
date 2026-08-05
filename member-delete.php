<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// حماية الصفحة: السماح فقط للـ admin أو الـ staff بالحذف
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    $_SESSION['error'] = 'غير مصرح لك بالوصول لصفحة حذف الأعضاء.';
    header("Location: members.php");
    exit();
}

require_once __DIR__ . '/config/db.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "لم يتم تحديد العضو المطلوب حذفه.";
    header("Location: members.php");
    exit();
}

$deleteId = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$deleteId]);
        $_SESSION['message'] = "تم حذف العضو بنجاح.";
    } catch (Exception $e) {
        $_SESSION['error'] = "حدث خطأ أثناء حذف العضو: " . $e->getMessage();
    }
    header("Location: members.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$deleteId]);
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

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">حذف عضو</h3>
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
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                تأكيد حذف العضو
                            </h3>
                        </div>
                        <div class="card-body">
                            <p>هل أنت متأكد من حذف بيانات العضو التالي؟ لا يمكن التراجع عن هذا الإجراء.</p>
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>الاسم</span>
                                    <strong><?= htmlspecialchars($member['full_name'] ?? '') ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>رقم الهاتف</span>
                                    <strong><?= htmlspecialchars($member['phone'] ?? '') ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>نوع الاشتراك</span>
                                    <strong><?= htmlspecialchars($member['membership_type'] ?? '') ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>تاريخ نهاية الاشتراك</span>
                                    <strong><?= htmlspecialchars($member['subscription_end'] ?? '') ?></strong>
                                </li>
                            </ul>
                            <form method="POST" action="./member-delete.php?id=<?= $deleteId ?>">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="./members.php" class="btn btn-secondary">إلغاء</a>
                                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                                        <i class="bi bi-trash-fill me-1"></i> نعم، احذف العضو
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>