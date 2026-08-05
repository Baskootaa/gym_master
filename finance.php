<?php
// 1. استدعاء قاعدة البيانات
// استدعاء ملفات النظام مع الخروج خطوة للخلف (/../)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 3. جلب بيانات المصروفات من الداتابيز
try {
    $stmt = $pdo->query("SELECT * FROM expenses ORDER BY id DESC");
    $expenses = $stmt->fetchAll();
} catch (Exception $e) {
    $expenses = []; // في حالة عدم وجود الجدول أو حدوث خطأ
}
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">إدارة الخزينة والمالية</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <!-- تم تعديل المسار هنا إلى /finance/add-expense.php لمنع خطأ Not Found -->
                    <a href="/finance/add-expense.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> إضافة مصروف جديد
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">سجل المصروفات والخرجات</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover m-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>عنوان المصروف</th>
                                <th>الفئة</th>
                                <th>المبلغ</th>
                                <th>التاريخ</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($expenses)): ?>
                                <?php foreach ($expenses as $index => $expense): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($expense['title'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= htmlspecialchars($expense['category'] ?? 'عام') ?>
                                            </span>
                                        </td>
                                        <td class="text-danger fw-bold">
                                            <?= number_format($expense['amount'] ?? 0, 2) ?> ج.م
                                        </td>
                                        <td><?= htmlspecialchars($expense['created_at'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($expense['notes'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">لا توجد مصروفات مسجلة حتى الآن.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>