<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$success = null;
$error = null;

// معالجة الحفظ عند إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $client_name = !empty($client_name) ? $client_name : 'عميل نقدي';
    $amount      = (float) ($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($amount > 0) {
        try {
            // استخدام PDO المتوافق مع بقية المشروع
            $stmt = $pdo->prepare(
                "INSERT INTO invoices (member_id, client_name, type, amount, total_amount, description, created_at) 
                 VALUES (NULL, :client_name, 'service', :amount, :total_amount, :description, NOW())"
            );
            
            $stmt->execute([
                'client_name'  => $client_name,
                'amount'       => $amount,
                'total_amount' => $amount,
                'description'  => $description
            ]);

            $success = "تم إنشاء الفاتورة بنجاح!";
        } catch (PDOException $e) {
            // في حال عدم وجود أعمدة client_name أو description في الجدول، يتم الإدراج بالأعمدة الأساسية
            try {
                $stmtFallback = $pdo->prepare(
                    "INSERT INTO invoices (member_id, type, amount, total_amount, created_at) 
                     VALUES (NULL, 'service', :amount, :total_amount, NOW())"
                );
                $stmtFallback->execute([
                    'amount'       => $amount,
                    'total_amount' => $amount
                ]);
                $success = "تم إنشاء الفاتورة بنجاح!";
            } catch (PDOException $ex) {
                $error = "حدث خطأ أثناء حفظ الفاتورة: " . $ex->getMessage();
            }
        }
    } else {
        $error = "برجاء إدخال مبلغ صحيح أكبر من الصفر.";
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3><i class="bi bi-receipt me-2"></i>إنشاء فاتورة جديدة</h3>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">اسم العميل / العضو (اختياري)</label>
                            <input type="text" name="client_name" class="form-control" placeholder="عميل نقدي">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">المبلغ (ج.م)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف / البيان</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="مبيعات / خدمات أخرى"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> حفظ وإصدار الفاتورة</button>
                        <a href="index.php" class="btn btn-secondary">إلغاء</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>