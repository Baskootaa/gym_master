<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$search = trim($_GET['search'] ?? '');
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE full_name LIKE ? OR phone LIKE ? ORDER BY id DESC");
        $like = "%$search%";
        $stmt->execute([$like, $like]);
        $members = $stmt->fetchAll();
    } else {
        $members = $pdo->query("SELECT * FROM members ORDER BY id DESC")->fetchAll();
    }
} catch (Exception $e) {
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
                    <a href="/add-member.php" class="btn btn-primary">
                        <i class="bi bi-person-plus-fill me-1"></i> إضافة عضو جديد
                    </a>
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
                                <a href="/members.php" class="btn btn-sm btn-outline-secondary ms-1">
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
                                    <th class="text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $index => $m): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($m['full_name']) ?></td>
                                            <td><?= htmlspecialchars($m['phone']) ?></td>
                                            <td><?= htmlspecialchars($m['gender']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['membership_type']) ?></span></td>
                                            <td><?= htmlspecialchars($m['subscription_end']) ?></td>
                                            <td>
                                                <?php if ($m['status'] === 'نشط'): ?>
                                                    <span class="badge bg-success">نشط</span>
                                                <?php elseif ($m['status'] === 'موقوف'): ?>
                                                    <span class="badge bg-warning text-dark">موقوف</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">منتهي</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="/member-edit.php?id=<?= (int) $m['id'] ?>"
                                                   class="btn btn-sm btn-outline-warning" title="تعديل">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="/member-delete.php?id=<?= (int) $m['id'] ?>"
                                                   class="btn btn-sm btn-outline-danger" title="حذف">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-3 text-muted">لا يوجد أعضاء مسجلين حالياً.</td>
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
