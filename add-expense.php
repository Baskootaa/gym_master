<?php

// استدعاء ملفات النظام مع الخروج خطوة للخلف (/../)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';


$success = '';
$error = '';

// معالجة الفورمة عند الضغط على حفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']);
    $amount   = floatval($_POST['amount']);
    $category = $_POST['category'];
    $notes    = trim($_POST['notes']);

    if (!empty($title) && $amount > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (title, amount, category, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $amount, $category, $notes]);
            $success = "تم تسجيل المصروف بنجاح!";
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء الحفظ: " . $e->getMessage();
        }
    } else {
        $error = "يرجى ملء جميع الحقول المطلوبة بشكل صحيح.";
    }
}
?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">تسجيل مصروف جديد</h3>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card card-primary card-outline col-md-8 mx-auto">
                <div class="card-header">
                    <h3 class="card-title">بيانات المصروف</h3>
                </div>
                
                <form action="" method="POST">
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">عنوان المصروف <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="مثال: صيانة جهاز المشاية" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">المبلغ (ج.م) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الفئة</label>
                                <select name="category" class="form-select">
                                    <option value="صيانة">صيانة وأجهزة</option>
                                    <option value="فواتير">فواتير (كهرباء/ماء/إنترنت)</option>
                                    <option value="رواتب">رواتب ومكافآت</option>
                                    <option value="أخرى">مشتريات أخرى</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ملاحظات إضافية</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="تفاصيل أكثر إن وجدت..."></textarea>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a href="index.php" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> حفظ المصروف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>