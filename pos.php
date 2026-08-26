<?php

// 1. بدء الجلسة لتخزين الرسائل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// حماية الصفحة: السماح فقط للأدمن والموظف بالوصول
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    $_SESSION['error'] = 'غير مصرح لك بالوصول لصفحة نقطة البيع (POS).';
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/config/db.php';
checkAccess(['admin', 'staff']);

// 2. تسجيل عملية بيع جديدة (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id > 0 && $quantity > 0) {
        try {
            // جلب المنتج وتأكد من وجود كمية
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if ($product && $product['quantity'] >= $quantity) {
                $total_price = $product['price'] * $quantity;

                // بدء معاملة (Transaction) لضمان تنفيذ البيع والخصم معاً
                $pdo->beginTransaction();

                // 1. تسجيل عملية البيع
                $insertSale = $pdo->prepare("INSERT INTO sales (product_id, quantity, total_price) VALUES (?, ?, ?)");
                $insertSale->execute([$product_id, $quantity, $total_price]);

                // 2. خصم الكمية من المخزن
                $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $updateStock->execute([$quantity, $product_id]);

                $pdo->commit();

                $_SESSION['message'] = "تمت عملية البيع بنجاح! الإجمالي: " . number_format($total_price, 2) . " ج.م";
            } else {
                $_SESSION['error'] = "عذراً، الكمية المطلوبة غير متوفرة في المخزن.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "حدث خطأ أثناء البيع: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى اختيار المنتج وتحديد كمية صحيحة.";
    }

    // إعادة التوجيه لنفس الصفحة للحد من تكرار البيع عند Refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3. استخراج الرسائل من الجلسة ومسحها
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// 4. استدعاء المكونات الرأسية
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 5. جلب المنتجات المتاحة للبيع وآخر المبيعات (تعديل الجلب إلى LIMIT 10)
try {
    $products = $pdo->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC")->fetchAll();
    $recentSales = $pdo->query("SELECT s.*, p.name as product_name FROM sales s JOIN products p ON s.product_id = p.id ORDER BY s.id DESC LIMIT 10")->fetchAll();
} catch (Exception $e) {
    $products = [];
    $recentSales = [];
}
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">نقطة بيع المنتجات والمكملات (POS)</h3>
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

            <div class="row">
                <!-- فورم عملية البيع السريعة -->
                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-cart-check me-1"></i> فاتورة بيع جديدة</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">اختر المنتج / المكمل</label>
                                    <select name="product_id" class="form-select form-select-lg" required>
                                        <option value="">-- اختر من قائمة المنتجات المتاحة --</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= htmlspecialchars($p['name']) ?> - (<?= number_format($p['price'], 2) ?> ج.م) - المتبقي: <?= $p['quantity'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">الكمية المباعة</label>
                                    <input type="number" name="quantity" class="form-control form-control-lg" value="1" min="1" required>
                                </div>
                                <button type="submit" name="process_sale" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-cash-register me-1"></i> إتمام عملية البيع
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- آخر المبيعات المسجلة -->
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-clock-history me-1"></i> آخر العمليات المباعة</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>الإجمالي</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grand_total = 0;
                                    if (!empty($recentSales)): 
                                    ?>
                                        <?php foreach ($recentSales as $sale): 
                                            $grand_total += $sale['total_price'];
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($sale['product_name']) ?></td>
                                                <td><?= $sale['quantity'] ?></td>
                                                <td class="text-success fw-bold"><?= number_format($sale['total_price'], 2) ?> ج.م</td>
                                                <td><small class="text-muted"><?= date('H:i - Y/m/d', strtotime($sale['created_at'])) ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">لا توجد مبيعات حالياً.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($recentSales)): ?>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="2" class="text-end">الإجمالي الكلي (لآخر عمليات):</th>
                                            <th colspan="2" class="text-success fw-bold fs-5"><?= number_format($grand_total, 2) ?> ج.م</th>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
