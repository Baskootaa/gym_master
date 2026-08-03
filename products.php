<?php
// 1. بدء الجلسة لتخزين رسائل النجاح والخطأ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// 2. معالجة نموذج إضافة منتج جديد (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'مكملات');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);

    if (!empty($name) && $price >= 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $category, $price, $quantity]);
            
            // حفظ الرسالة في Session
            $_SESSION['message'] = "تمت إضافة المنتج بنجاح!";
        } catch (Exception $e) {
            $_SESSION['error'] = "حدث خطأ أثناء حفظ المنتج: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى ملء البيانات المطلوبة بشكل صحيح.";
    }

    // إعادة التوجيه لمنع إعادة إرسال البيانات عند عمل Refresh (PRG Pattern)
    header("Location: products.php");
    exit();
}

// 3. استخراج الرسائل من الجلسة ومسحها
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// 4. استدعاء المكونات الرأسية
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 5. جلب المنتجات من قاعدة البيانات
try {
    $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    $products = [];
}
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">إدارة المنتجات والخدمات</h3>
                </div>
                <div class="col-sm-6 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle me-1"></i> إضافة منتج جديد
                    </button>
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
                    <h3 class="card-title">قائمة المكملات والمنتجات</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover m-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المنتج / الخدمة</th>
                                <th>الفئة</th>
                                <th>السعر</th>
                                <th>الكمية المتاحة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($item['category']) ?></span></td>
                                        <td class="text-success fw-bold"><?= number_format($item['price'], 2) ?> ج.م</td>
                                        <td><?= number_format($item['quantity']) ?></td>
                                        <td>
                                            <?php if ($item['quantity'] > 5): ?>
                                                <span class="badge bg-success">متوفر</span>
                                            <?php elseif ($item['quantity'] > 0): ?>
                                                <span class="badge bg-warning">مخزون منخفض</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">نفذت الكمية</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">لا توجد منتجات مسجلة حالياً.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal إضافة منتج -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="products.php">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة منتج أو مكمل جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المنتج</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: واي بروتين 1 كجم" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الفئة</label>
                        <select name="category" class="form-select">
                            <option value="مكملات">مكملات غذائية</option>
                            <option value="مشروبات">مشروبات ومياه</option>
                            <option value="أدوات رياضة">أدوات وملابس رياضية</option>
                            <option value="خدمة">خدمة (إنبودي / ساونا)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">السعر (ج.م)</label>
                            <input type="number" step="0.5" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الكمية بالمخزن</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="add_product" class="btn btn-primary">حفظ المنتج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>