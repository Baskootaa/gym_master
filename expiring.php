<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
checkAccess(['admin', 'staff', 'user']);

$canRenew = isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true);

$days = isset($_GET['days']) && ctype_digit($_GET['days']) ? (int)$_GET['days'] : 30;

try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               sub_latest.end_date AS subscription_end, 
               sub_latest.membership_type, 
               DATEDIFF(sub_latest.end_date, CURDATE()) AS days_left 
        FROM members m
        INNER JOIN (
            SELECT s.member_id, s.end_date, p.name AS membership_type, s.id
            FROM subscriptions s
            LEFT JOIN packages p ON s.package_id = p.id
            INNER JOIN (
                SELECT member_id, MAX(id) AS max_id
                FROM subscriptions
                GROUP BY member_id
            ) latest ON s.id = latest.max_id
        ) sub_latest ON m.id = sub_latest.member_id
        WHERE DATEDIFF(sub_latest.end_date, CURDATE()) <= ?
        ORDER BY sub_latest.end_date ASC
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
                    <h3 class="mb-0">الاشتراكات المنتهية والقريبة على الانتهاء</h3>
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
                        الاشتراكات خلال الفترة (المنتهية والقادمة)
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
                    <table class="table table-striped table-hover m-0 text-center">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>رقم الهاتف</th>
                                <th>نوع الاشتراك</th>
                                <th>تاريخ الانتهاء</th>
                                <th>الحالة / الأيام المتبقية</th>
                                <?php if ($canRenew): ?>
                                    <th>إجراءات</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($expiringMembers)): ?>
                                <?php foreach ($expiringMembers as $index => $member): 
                                    $daysLeft = (int)$member['days_left'];
                                    $isExpired = $daysLeft < 0;
                                ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($member['full_name']) ?></td>
                                        <td><?= htmlspecialchars($member['phone']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($member['membership_type'] ?? 'اشتراك') ?></span></td>
                                        <td><?= htmlspecialchars($member['subscription_end']) ?></td>
                                        <td>
                                            <?php if ($isExpired): ?>
                                                <span class="badge bg-danger">
                                                    منتهي منذ <?= abs($daysLeft) ?> يوم
                                                </span>
                                            <?php elseif ($daysLeft === 0): ?>
                                                <span class="badge bg-warning text-dark">
                                                    ينتهي اليوم!
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    متبقي <?= $daysLeft ?> يوم
                                                </span>
                                            <?php endif; ?>
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
                                    <td colspan="<?= $canRenew ? 7 : 6 ?>" class="text-center py-4 text-muted">
                                        لا توجد اشتراكات منتهية أو توشك على الانتهاء خلال هذه الفترة.
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
