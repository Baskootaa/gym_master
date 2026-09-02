<?php
// ضبط المنطقة الزمنية للقاهرة مباشرة
date_default_timezone_set('Africa/Cairo');

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
    $member_id = intval($_POST['member_id'] ?? 0);

    if ($product_id > 0 && $quantity > 0 && $member_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if ($product && $product['quantity'] >= $quantity) {
                $total_price = $product['price'] * $quantity;

                $pdo->beginTransaction();

                $insertSale = $pdo->prepare("INSERT INTO sales (member_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
                $insertSale->execute([$member_id, $product_id, $quantity, $total_price]);

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
        $_SESSION['error'] = "يرجى اختيار العضو، المنتج، وتحديد كمية صحيحة.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3. تعديل كمية عملية بيع سابقة مع تسوية المخزن تلقائياً
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sale'])) {
    $sale_id = intval($_POST['sale_id'] ?? 0);
    $new_qty = intval($_POST['new_quantity'] ?? 0);

    if ($sale_id > 0 && $new_qty > 0) {
        try {
            $pdo->beginTransaction();

            // جلب تفاصيل عملية البيع القديمة
            $saleStmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
            $saleStmt->execute([$sale_id]);
            $oldSale = $saleStmt->fetch();

            if (!$oldSale) {
                throw new Exception("عملية البيع غير موجودة.");
            }

            $product_id = $oldSale['product_id'];
            $old_qty = $oldSale['quantity'];
            $diff = $new_qty - $old_qty; // الفرق في الكمية

            // جلب المنتج وسعره والكمية الحالية في المخزن
            $prodStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $prodStmt->execute([$product_id]);
            $product = $prodStmt->fetch();

            if (!$product) {
                throw new Exception("المنتج غير موجود.");
            }

            // لو زودنا الكمية في البيع، لازم نتأكد إن المخزن فيه كفاية ونخصم الإضافي
            if ($diff > 0 && $product['quantity'] < $diff) {
                throw new Exception("الكمية المطلوبة غير متوفرة في المخزن.");
            }

            // تحديث مخزن المنتجات (عكس الفرق)
            $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $updateStock->execute([$diff, $product_id]);

            // حساب الإجمالي الجديد بناءً على السعر الحالي للمنتج
            $new_total_price = $product['price'] * $new_qty;

            // تحديث جدول المبيعات
            $updateSale = $pdo->prepare("UPDATE sales SET quantity = ?, total_price = ? WHERE id = ?");
            $updateSale->execute([$new_qty, $new_total_price, $sale_id]);

            $pdo->commit();
            $_SESSION['message'] = "تم تحديث كمية البيع وتعديل المخزن بنجاح!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "خطأ أثناء التعديل: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "يرجى إدخال كمية صحيحة للتعديل.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. إلغاء/حذف عملية بيع وإرجاع الكمية للمخزن تلقائياً
if (isset($_GET['delete_sale_id'])) {
    $delete_id = intval($_GET['delete_sale_id']);
    if ($delete_id > 0) {
        try {
            $pdo->beginTransaction();

            // جلب تفاصيل البيع لإرجاع الكمية للمخزن
            $saleStmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
            $saleStmt->execute([$delete_id]);
            $sale = $saleStmt->fetch();

            if ($sale) {
                // إرجاع الكمية للمخزن
                $restoreStock = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $restoreStock->execute([$sale['quantity'], $sale['product_id']]);

                // حذف سجل البيع
                $delStmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
                $delStmt->execute([$delete_id]);
            }

            $pdo->commit();
            $_SESSION['message'] = "تم إلغاء عملية البيع وإرجاع الكمية للمخزن بنجاح!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "حدث خطأ أثناء إلغاء البيع: " . $e->getMessage();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 5. استخراج الرسائل من الجلسة ومسحها
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// 6. استدعاء المكونات الرأسية
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 7. جلب البيانات
try {
    $members = $pdo->query('
        SELECT DISTINCT m.id, m.full_name, m.phone 
        FROM members m
        JOIN subscriptions s ON s.member_id = m.id
        WHERE s.end_date >= CURDATE()
        ORDER BY m.full_name ASC
    ')->fetchAll();

    $products = $pdo->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC")->fetchAll();
    
    $recentSales = $pdo->query("
        SELECT s.*, p.name as product_name, m.full_name as member_name 
        FROM sales s 
        JOIN products p ON s.product_id = p.id 
        LEFT JOIN members m ON s.member_id = m.id 
        ORDER BY s.id DESC LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    $members = [];
    $products = [];
    $recentSales = [];
}
?>

<!-- إضافة ملفات مكتبة Select2 للبحث السريع (CSS) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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
                <div class="col-md-5">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-cart-check me-1"></i> فاتورة بيع جديدة</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">اختر العضو</label>
                                    <select name="member_id" id="memberSelect" class="form-select" required>
                                        <option value="">-- ابحث بالاسم أو رقم الهاتف --</option>
                                        <?php foreach ($members as $member): ?>
                                            <option value="<?= $member['id'] ?>">
                                                <?= htmlspecialchars($member['full_name']) ?> (<?= htmlspecialchars($member['phone']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

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

                <!-- آخر المبيعات المسجلة مع أزرار التعديل والإلغاء -->
                <div class="col-md-7">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-clock-history me-1"></i> آخر العمليات المباعة</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped m-0 text-center align-middle">
                                <thead>
                                    <tr>
                                        <th>العضو</th>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>الإجمالي</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
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
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($sale['member_name'] ?? 'عضو محذوف') ?></td>
                                                <td><?= htmlspecialchars($sale['product_name']) ?></td>
                                                <td><?= $sale['quantity'] ?></td>
                                                <td class="text-success fw-bold"><?= number_format($sale['total_price'], 2) ?> ج.م</td>
                                                <td><small class="text-muted"><?= date('H:i - Y/m/d', strtotime($sale['created_at'])) ?></small></td>
                                                <td>
                                                    <!-- زر تعديل الكمية -->
                                                    <button class="btn btn-sm btn-warning text-white edit-sale-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editSaleModal"
                                                            data-id="<?= $sale['id'] ?>"
                                                            data-qty="<?= $sale['quantity'] ?>"
                                                            data-product="<?= htmlspecialchars($sale['product_name']) ?>">
                                                        <i class="bi bi-pencil-square"></i> تعديل
                                                    </button>
                                                    <!-- زر إلغاء/حذف البيع -->
                                                    <a href="pos.php?delete_sale_id=<?= $sale['id'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('هل أنت متأكد من إلغاء عملية البيع وإرجاع الكمية للمخزن؟');">
                                                        <i class="bi bi-trash"></i> إلغاء
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-3 text-muted">لا توجد مبيعات حالياً.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($recentSales)): ?>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="3" class="text-end">الإجمالي الكلي (لآخر عمليات):</th>
                                            <th colspan="3" class="text-success fw-bold fs-5 text-center"><?= number_format($grand_total, 2) ?> ج.م</th>
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

<!-- Modal تعديل كمية البيع -->
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="sale_id" id="edit_sale_id">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل كمية البيع للمنتج: <span id="edit_product_name" class="text-success"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">الكمية الجديدة</label>
                        <input type="number" name="new_quantity" id="edit_new_quantity" class="form-control form-control-lg" min="1" required>
                        <small class="text-muted">ملاحظة: تعديل الكمية سيقوم بتحديث المخزن والأرباح تلقائياً.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="update_sale" class="btn btn-warning text-white">تحديث الكمية</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- تشغيل مكتبة Select2 وجعلها تفاعلية -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function() {
      $('#memberSelect').select2({
          theme: 'bootstrap-5',
          placeholder: '-- ابحث بالاسم أو رقم الهاتف --',
          allowClear: true
      });

      // تمرير بيانات البيع لمودال التعديل
      const editSaleButtons = document.querySelectorAll('.edit-sale-btn');
      editSaleButtons.forEach(button => {
          button.addEventListener('click', function() {
              document.getElementById('edit_sale_id').value = this.getAttribute('data-id');
              document.getElementById('edit_new_quantity').value = this.getAttribute('data-qty');
              document.getElementById('edit_product_name').innerText = this.getAttribute('data-product');
          });
      });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
