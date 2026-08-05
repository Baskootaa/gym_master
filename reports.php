<?php
// 1. استدعاء قواعد البيانات والهيدر
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 2. حساب إجمالي المصروفات من جدول expenses
try {
    $stmtExpenses = $pdo->query("SELECT SUM(amount) FROM expenses");
    $totalExpenses = $stmtExpenses->fetchColumn() ?: 0;
} catch (Exception $e) {
    $totalExpenses = 0;
}

// 3. حساب إيرادات الاشتراكات (ربط جدول الاشتراكات مع الباقات)
try {
    // يحسب مجموع أسعار الباقات لجميع الاشتراكات المسجلة
    $stmtSubsIncomes = $pdo->query("
        SELECT SUM(p.price) 
        FROM subscriptions s 
        JOIN packages p ON s.package_id = p.id
    ");
    $subscriptionsIncomes = $stmtSubsIncomes->fetchColumn() ?: 0;
} catch (Exception $e) {
    // في حال وجود حقل price المباشر داخل جدول subscriptions بدون JOIN
    try {
        $stmtSubsIncomes = $pdo->query("SELECT SUM(price) FROM subscriptions");
        $subscriptionsIncomes = $stmtSubsIncomes->fetchColumn() ?: 0;
    } catch (Exception $ex) {
        $subscriptionsIncomes = 0;
    }
}

// 4. حساب إيرادات مبيعات المنتجات والمكملات (POS) من جدول sales وعامود total_price
try {
    $stmtPosIncomes = $pdo->query("SELECT SUM(total_price) FROM sales");
    $posIncomes = $stmtPosIncomes->fetchColumn() ?: 0;
} catch (Exception $e) {
    // محاولة احتياطية في حال وجود جدول pos_sales
    try {
        $stmtPosIncomes = $pdo->query("SELECT SUM(total_price) FROM pos_sales");
        $posIncomes = $stmtPosIncomes->fetchColumn() ?: 0;
    } catch (Exception $ex) {
        $posIncomes = 0;
    }
}

// 5. إجمالي الإيرادات الكلي (الاشتراكات + مبيعات POS)
$totalIncomes = $subscriptionsIncomes + $posIncomes;

// 6. حساب صافي الخزينة المتبقي
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
                            <p class="mb-0 fs-6">إجمالي الإيرادات (الاشتراكات + المبيعات)</p>
                            <small class="d-block mt-1 text-white-50">
                                اشتراكات: <?= number_format($subscriptionsIncomes, 2) ?> | مبيعات: <?= number_format($posIncomes, 2) ?>
                            </small>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>