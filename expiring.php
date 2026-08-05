<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



require_once __DIR__ . '/config/db.php';
checkAccess(['admin', 'staff', 'user']);

// التحقق مما إذا كان المستخدم يملك صلاحية التجديد (Admin أو Staff)
$canRenew = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true);

// تحديد عدد الأيام المتبقية للتصفية (افتراضياً 30 يوم)
$days = isset($_GET['days']) && ctype_digit($_GET['days']) ? (int)$_GET['days'] : 30;

// جلب الأعضاء الذين تُوشِك اشتراكاتهم على الانتهاء خلال الأيام المحددة
try {
    $stmt = $pdo->prepare("
        SELECT *, DATEDIFF(subscription_end, CURDATE()) AS days_left 
        FROM members 
        WHERE subscription_end >= CURDATE() 
          AND DATEDIFF(subscription_end, CURDATE()) <= ?
        ORDER BY subscription_end ASC
    ");
    $stmt->execute([$days]);
    $expiringMembers = $stmt->fetchAll();
} catch (Exception $e) {
    $expiringMembers = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">اشتراكات توشك على الانتهاء</h3>
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
            <div class="card card-outline card-warning">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                        الاشتراكات المنتهية خلال
                    </h3>
                    <div class="card-tools">
                        <select class="form-select form-select-sm" onchange="location = this.value;">
                            <option value="expiring.php?days=7" <?= $days == 7 ? 'selected' : '' ?>>خلال أسبوع</option>
                            <option value="expiring.php?days=15" <?= $days == 15 ? 'selected' : '' ?>>خلال 15 يوم</option>
                            <option value="expiring.php?days=30" <?= $days == 30 ? 'selected' : '' ?>>خلال شهر</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover m-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>رقم الهاتف</th>
                                <th>نوع الاشتراك</th>
                                <th>تاريخ الانتهاء</th>
                                <th>الأيام المتبقية</th>
                                <?php if ($canRenew): ?>
                                    <th>إجراءات</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($expiringMembers)): ?>
                                <?php foreach ($expiringMembers as $index => $member): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($member['full_name']) ?></td>
                                        <td><?= htmlspecialchars($member['phone']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($member['membership_type']) ?></span></td>
                                        <td><?= htmlspecialchars($member['subscription_end']) ?></td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?= $member['days_left'] ?> يوم
                                            </span>
                                        </td>
                                        <?php if ($canRenew): ?>
                                            <td>
                                                <a href="./member-edit.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-arrow-repeat me-1"></i> تجديد
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $canRenew ? 7 : 6 ?>" class="text-center py-3 text-muted">
                                        لا توجد اشتراكات توشك على الانتهاء خلال هذه الفترة.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted">
                    الإجمالي: <?= count($expiringMembers) ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>