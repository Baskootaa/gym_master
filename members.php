<?php
/**
 * صفحة إدارة وعرض الأعضاء - GYM MASTER
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

if (function_exists('checkAccess')) {
    checkAccess(['admin', 'staff', 'user']);
}

$isStaffOrAdmin = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true);

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$search = trim($_GET['search'] ?? '');
$members = [];

try {
    // استعلام دقيق يربط العضو بآخر اشتراك تم تسجيله له حصرياً عبر أكبر ID في جدول subscriptions لتحديث البيانات فوراً
    $sql = "SELECT m.*, 
                   COALESCE(sub_latest.end_date, m.subscription_end) AS subscription_end,
                   COALESCE(p.name, m.membership_type, 'بدون اشتراك') AS membership_type,
                   CASE 
                       WHEN sub_latest.end_date IS NOT NULL AND sub_latest.end_date >= CURDATE() THEN 'نشط'
                       WHEN sub_latest.end_date IS NOT NULL AND sub_latest.end_date < CURDATE() THEN 'منتهي'
                       WHEN m.subscription_end IS NOT NULL AND m.subscription_end >= CURDATE() THEN 'نشط'
                       ELSE 'منتهي'
                   END AS calculated_status
            FROM members m
            LEFT JOIN (
                SELECT s.member_id, s.package_id, s.end_date
                FROM subscriptions s
                INNER JOIN (
                    SELECT member_id, MAX(id) AS max_id
                    FROM subscriptions
                    GROUP BY member_id
                ) latest ON s.id = latest.max_id
            ) sub_latest ON m.id = sub_latest.member_id
            LEFT JOIN packages p ON sub_latest.package_id = p.id";

    if ($search !== '') {
        $sql .= " WHERE m.full_name LIKE ? OR m.phone LIKE ?";
        $sql .= " ORDER BY m.id DESC";
        $stmt = $pdo->prepare($sql);
        $like = "%$search%";
        $stmt->execute([$like, $like]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql .= " ORDER BY m.id DESC";
        $stmt = $pdo->query($sql);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "حدث خطأ أثناء جلب بيانات الأعضاء: " . $e->getMessage();
    $members = [];
}
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">قائمة الأعضاء</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <?php if ($isStaffOrAdmin): ?>
                        <a href="./add-member.php" class="btn btn-primary">
                            <i class="bi bi-person-plus-fill me-1"></i> إضافة عضو جديد
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">كل الأعضاء المسجلين</h3>
                    <div class="card-tools" style="width: 280px;">
                        <form method="GET" action="members.php" class="d-flex">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="بحث بالاسم أو رقم الهاتف..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if ($search !== ''): ?>
                                <a href="./members.php" class="btn btn-sm btn-outline-secondary ms-1">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover m-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم</th>
                                    <th>رقم الهاتف</th>
                                    <th>النوع</th>
                                    <th>نوع الاشتراك</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الحالة</th>
                                    <?php if ($isStaffOrAdmin): ?>
                                        <th class="text-center">إجراءات</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $index => $m): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($m['full_name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($m['phone'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($m['gender'] ?? '') ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['membership_type'] ?? 'بدون اشتراك') ?></span></td>
                                            <td><?= htmlspecialchars($m['subscription_end'] ?? '-') ?></td>
                                            <td>
                                               <?php 
                                                $status = $m['calculated_status'] ?? 'منتهي'; 
                                                ?>
                                                <?php if ($status === 'نشط'): ?>
                                                    <span class="badge bg-success">نشط</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">منتهي</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($isStaffOrAdmin): ?>
                                                <td class="text-center">
                                                    <a href="./member-edit.php?id=<?= (int) $m['id'] ?>"
                                                       class="btn btn-sm btn-outline-warning" title="تعديل">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="./member-delete.php?id=<?= (int) $m['id'] ?>"
                                                       class="btn btn-sm btn-outline-danger" title="حذف"
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا العضو؟');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= $isStaffOrAdmin ? '8' : '7' ?>" class="text-center py-3 text-muted">لا يوجد أعضاء مسجلين حالياً.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    إجمالي الأعضاء: <?= count($members) ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
