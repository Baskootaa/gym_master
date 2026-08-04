<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$days = isset($_GET['days']) && ctype_digit($_GET['days']) ? (int) $_GET['days'] : 7;

try {
    $stmt = $pdo->prepare(
        "SELECT * FROM members
         WHERE subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
         AND status = 'نشط'
         ORDER BY subscription_end ASC"
    );
    $stmt->execute([$days]);
    $members = $stmt->fetchAll();
} catch (Exception $e) {
    $members = [];
}
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">اشتراكات توشك على الانتهاء</h3>
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
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                        الاشتراكات المنتهية خلال
                    </h3>
                    <div class="card-tools" style="width: 220px;">
                        <form method="GET" action="expiring.php" class="d-flex">
                            <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="3" <?= $days === 3 ? 'selected' : '' ?>>خلال 3 أيام</option>
                                <option value="7" <?= $days === 7 ? 'selected' : '' ?>>خلال أسبوع</option>
                                <option value="14" <?= $days === 14 ? 'selected' : '' ?>>خلال أسبوعين</option>
                                <option value="30" <?= $days === 30 ? 'selected' : '' ?>>خلال شهر</option>
                            </select>
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
                                    <th>نوع الاشتراك</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الأيام المتبقية</th>
                                    <th class="text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($members)): ?>
                                    <?php foreach ($members as $index => $m): ?>
                                        <?php
                                        $end = new DateTime($m['subscription_end']);
                                        $today = new DateTime('today');
                                        $remaining = (int) $today->diff($end)->format('%r%a');
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($m['full_name']) ?></td>
                                            <td><?= htmlspecialchars($m['phone']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['membership_type']) ?></span></td>
                                            <td><?= htmlspecialchars($m['subscription_end']) ?></td>
                                            <td>
                                                <?php if ($remaining <= 2): ?>
                                                    <span class="badge bg-danger"><?= $remaining ?> يوم</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><?= $remaining ?> يوم</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="/member-edit.php?id=<?= (int) $m['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary" title="تجديد / تعديل">
                                                    <i class="bi bi-arrow-repeat"></i> تجديد
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-3 text-muted">لا توجد اشتراكات توشك على الانتهاء حاليًا.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    الإجمالي: <?= count($members) ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
