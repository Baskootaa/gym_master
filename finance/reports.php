<?php
// 1. استدعاء قواعد البيانات والهيدر
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// 2. حساب إجمالي المصروفات من جدول expenses
try {
    $stmtExpenses = $pdo->query("SELECT SUM(amount) FROM expenses");
    $totalExpenses = $stmtExpenses->fetchColumn() ?: 0;
} catch (Exception $e) {
    $totalExpenses = 0;
}

// 3. حساب إجمالي الإيرادات (مجهز للربط مع جدول الاشتراكات subscriptions إذا توفر)
try {
    // لو عندكم جدول اسمه subscriptions وفيه عامود price أو price_paid
    $stmtIncomes = $pdo->query("SELECT SUM(price) FROM subscriptions");
    $totalIncomes = $stmtIncomes->fetchColumn() ?: 0;
} catch (Exception $e) {
    $totalIncomes = 0; // في حال عدم وجود جدول الاشتراكات بعد
}

// 4. حساب صافي الخزينة
$netProfit = $totalIncomes - $totalExpenses;
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3 class="mb-0">تقارير الخزينة والمالية</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3">
                <!-- كارت الإيرادات -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="small-box text-bg-success p-3 rounded shadow-sm">
                        <div class="inner">
                            <h3><?= number_format($totalIncomes, 2) ?> <small class="fs-6">ج.م</small></h3>
                            <p class="mb-0 fs-6">إجمالي الإيرادات (الاشتراكات)</p>
                        </div>
                    </div>
                </div>

                <!-- كارت المصروفات -->
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="small-box text-bg-danger p-3 rounded shadow-sm">
                        <div class="inner">
                            <h3><?= number_format($totalExpenses, 2) ?> <small class="fs-6">ج.م</small></h3>
                            <p class="mb-0 fs-6">إجمالي المصروفات</p>
                        </div>
                    </div>
                </div>

                <!-- كارت صافي الخزينة -->
                <div class="col-lg-4 col-12">
                    <div class="small-box <?= $netProfit >= 0 ? 'text-bg-info' : 'text-bg-warning' ?> p-3 rounded shadow-sm">
                        <div class="inner">
                            <h3><?= number_format($netProfit, 2) ?> <small class="fs-6">ج.م</small></h3>
                            <p class="mb-0 fs-6">صافي الخزينة المتبقي</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>