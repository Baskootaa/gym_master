<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$errors = [];

// حذف باقة
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM packages WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['delete']]);
    header('Location: packages.php?deleted=1');
    exit;
}

// إضافة باقة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $duration_days = (int) ($_POST['duration_days'] ?? 0);
    $price         = (float) ($_POST['price'] ?? 0);

    if ($name === '' || $duration_days <= 0 || $price <= 0) {
        $errors[] = 'من فضلك املأ كل الحقول بشكل صحيح';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO packages (name, duration_days, price) VALUES (:name, :duration_days, :price)'
        );
        $stmt->execute([
            'name'          => $name,
            'duration_days' => $duration_days,
            'price'         => $price,
        ]);
        header('Location: packages.php?added=1');
        exit;
    }
}

$packages = $pdo->query('SELECT * FROM packages ORDER BY price ASC')->fetchAll();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">أنواع الباقات</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
            <li class="breadcrumb-item active">الباقات والاشتراكات</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <?php if (!empty($_GET['added'])): ?>
        <div class="alert alert-success py-2">تمت إضافة الباقة بنجاح</div>
      <?php endif; ?>
      <?php if (!empty($_GET['deleted'])): ?>
        <div class="alert alert-warning py-2">تم حذف الباقة</div>
      <?php endif; ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>

      <div class="row">
        <!-- فورم إضافة باقة -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-plus-circle me-2"></i>إضافة باقة جديدة</h3>
            </div>
            <div class="card-body">
              <form method="POST" action="packages.php">
                <div class="mb-3">
                  <label class="form-label">اسم الباقة</label>
                  <input type="text" name="name" class="form-control" required
                         placeholder="مثال: اشتراك شهري">
                </div>
                <div class="mb-3">
                  <label class="form-label">المدة (بالأيام)</label>
                  <input type="number" name="duration_days" class="form-control" required min="1"
                         placeholder="مثال: 30">
                </div>
                <div class="mb-3">
                  <label class="form-label">السعر (ج.م)</label>
                  <input type="number" name="price" class="form-control" required min="1" step="0.01"
                         placeholder="مثال: 500">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-check-circle me-1"></i> حفظ الباقة
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- قائمة الباقات -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-card-checklist me-2"></i>الباقات المتاحة (<?= count($packages) ?>)</h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>اسم الباقة</th>
                      <th>المدة</th>
                      <th>السعر</th>
                      <th>إجراءات</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($packages)): ?>
                      <tr><td colspan="5" class="text-center text-secondary py-4">مفيش باقات مضافة لسه</td></tr>
                    <?php else: ?>
                      <?php foreach ($packages as $package): ?>
                        <tr>
                          <td><?= $package['id'] ?></td>
                          <td><?= htmlspecialchars($package['name']) ?></td>
                          <td><?= $package['duration_days'] ?> يوم</td>
                          <td><span class="badge bg-primary"><?= number_format($package['price'], 2) ?> ج.م</span></td>
                          <td>
                            <a href="packages.php?delete=<?= $package['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('متأكد إنك عايز تحذف الباقة دي؟');">
                              <i class="bi bi-trash"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
