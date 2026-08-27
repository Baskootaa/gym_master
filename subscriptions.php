<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$errors = [];

// حذف اشتراك (متاح فقط للأدمن والموظف للأمان)
if (isset($_GET['delete'])) {
    if (hasRole(['admin', 'staff'])) {
        $stmt = $pdo->prepare('DELETE FROM subscriptions WHERE id = :id');
        $stmt->execute(['id' => (int) $_GET['delete']]);
        header('Location: subscriptions.php?deleted=1');
        exit;
    }
}

// إضافة اشتراك جديد (متاح فقط للأدمن والموظف)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hasRole(['admin', 'staff'])) {
        $member_id  = (int) ($_POST['member_id'] ?? 0);
        $package_id = (int) ($_POST['package_id'] ?? 0);
        $start_date = $_POST['start_date'] ?? date('Y-m-d');

        if ($member_id <= 0 || $package_id <= 0) {
            $errors[] = 'من فضلك اختار العضو والباقة';
        } else {
            // جلب تفاصيل الباقة (الأيام والسعر)
            $stmt = $pdo->prepare('SELECT duration_days, price FROM packages WHERE id = :id');
            $stmt->execute(['id' => $package_id]);
            $package = $stmt->fetch();

            if (!$package) {
                $errors[] = 'الباقة دي مش موجودة';
            } else {
                $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $package['duration_days'] . ' days'));
                $price    = $package['price'] ?? 0;

                // إدراج الاشتراك مع حفظ سعر الباقات المختلفة (حصة، شهر، سنة)
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO subscriptions (member_id, package_id, start_date, end_date, price, status)
                         VALUES (:member_id, :package_id, :start_date, :end_date, :price, "active")'
                    );
                    $stmt->execute([
                        'member_id'  => $member_id,
                        'package_id' => $package_id,
                        'start_date' => $start_date,
                        'end_date'   => $end_date,
                        'price'      => $price,
                    ]);
                } catch (PDOException $e) {
                    // في حال عدم وجود عامود price في جدول الاشتراكات يتم حفظ التكلفة في عامود cost أو الحفظ بدون العامود
                    $stmt = $pdo->prepare(
                        'INSERT INTO subscriptions (member_id, package_id, start_date, end_date)
                         VALUES (:member_id, :package_id, :start_date, :end_date)'
                    );
                    $stmt->execute([
                        'member_id'  => $member_id,
                        'package_id' => $package_id,
                        'start_date' => $start_date,
                        'end_date'   => $end_date,
                    ]);
                }

                // تحديث حالة العضو فقط في جدول الأعضاء أوتوماتيكياً (تم إزالة subscription_end_date لعدم وجود العمود في جدول members)
                $update_member = $pdo->prepare('UPDATE members SET status = "active" WHERE id = :member_id');
                $update_member->execute([
                    'member_id'  => $member_id
                ]);

                header('Location: subscriptions.php?added=1');
                exit;
            }
        }
    }
}

$members  = $pdo->query('SELECT id, full_name FROM members ORDER BY full_name ASC')->fetchAll();
$packages = $pdo->query('SELECT id, name, duration_days, price FROM packages ORDER BY name ASC')->fetchAll();

$subscriptions = $pdo->query(
    'SELECT s.*, m.full_name, p.name AS package_name, p.price AS package_price
     FROM subscriptions s
     JOIN members m ON m.id = s.member_id
     JOIN packages p ON p.id = s.package_id
     ORDER BY s.id DESC'
)->fetchAll();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">سجل الاشتراكات</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
            <li class="breadcrumb-item active">سجل الاشتراكات</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <?php if (!empty($_GET['added'])): ?>
        <div class="alert alert-success py-2">تم تسجيل الاشتراك بنجاح</div>
      <?php endif; ?>
      <?php if (!empty($_GET['deleted'])): ?>
        <div class="alert alert-warning py-2">تم حذف الاشتراك</div>
      <?php endif; ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>

      <div class="row">
        <!-- فورم إضافة اشتراك (يظهر فقط للأدمن والموظف) -->
        <?php if (hasRole(['admin', 'staff'])): ?>
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-plus-circle me-2"></i>تسجيل اشتراك جديد</h3>
            </div>
            <div class="card-body">
              <?php if (empty($members)): ?>
                <p class="text-secondary">لازم تضيف أعضاء الأول من <a href="member-add.php">هنا</a></p>
              <?php elseif (empty($packages)): ?>
                <p class="text-secondary">لازم تضيف باقات الأول من <a href="packages.php">هنا</a></p>
              <?php else: ?>
                <form method="POST" action="subscriptions.php">
                  <div class="mb-3">
                    <label class="form-label">العضو</label>
                    <select name="member_id" class="form-select" required>
                      <option value="">-- اختار عضو --</option>
                      <?php foreach ($members as $member): ?>
                        <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">الباقة</label>
                    <select name="package_id" class="form-select" required>
                      <option value="">-- اختار باقة --</option>
                      <?php foreach ($packages as $package): ?>
                        <option value="<?= $package['id'] ?>">
                          <?= htmlspecialchars($package['name']) ?> (<?= $package['duration_days'] ?> يوم) - <?= number_format($package['price'], 2) ?> ج.م
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">تاريخ البداية</label>
                    <input type="date" name="start_date" class="form-control" required
                           value="<?= date('Y-m-d') ?>">
                  </div>
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle me-1"></i> حفظ الاشتراك
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- قائمة الاشتراكات: تأخذ المساحة الكاملة 12 للمستخدم العادي، أو 8 لو ظهر بجانبها الفورم -->
        <div class="<?= hasRole(['admin', 'staff']) ? 'col-lg-8' : 'col-lg-12' ?>">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-card-checklist me-2"></i>كل الاشتراكات (<?= count($subscriptions) ?>)</h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>العضو</th>
                      <th>الباقة</th>
                      <th>السعر</th>
                      <th>البداية</th>
                      <th>النهاية</th>
                      <th>الحالة</th>
                      <?php if (hasRole(['admin', 'staff'])): ?>
                      <th></th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($subscriptions)): ?>
                      <tr><td colspan="<?= hasRole(['admin', 'staff']) ? 7 : 6 ?>" class="text-center text-secondary py-4">مفيش اشتراكات مسجلة لسه</td></tr>
                    <?php else: ?>
                      <?php foreach ($subscriptions as $sub):
                        $today = new DateTime();
                        $end   = new DateTime($sub['end_date']);
                        $daysLeft = (int) $today->diff($end)->format('%r%a');

                        if ($daysLeft < 0) {
                            $statusLabel = 'منتهي';
                            $statusClass = 'bg-danger';
                        } elseif ($daysLeft <= 7) {
                            $statusLabel = 'ينتهي قريباً';
                            $statusClass = 'bg-warning text-dark';
                        } else {
                            $statusLabel = 'نشط';
                            $statusClass = 'bg-success';
                        }
                        
                        $displayPrice = $sub['price'] ?? $sub['package_price'] ?? 0;
                      ?>
                        <tr>
                          <td><?= htmlspecialchars($sub['full_name']) ?></td>
                          <td><?= htmlspecialchars($sub['package_name']) ?></td>
                          <td><?= number_format($displayPrice, 2) ?> ج.م</td>
                          <td><?= htmlspecialchars($sub['start_date']) ?></td>
                          <td><?= htmlspecialchars($sub['end_date']) ?></td>
                          <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                          <?php if (hasRole(['admin', 'staff'])): ?>
                          <td>
                            <a href="subscriptions.php?delete=<?= $sub['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('متأكد إنك عايز تحذف الاشتراك ده؟');">
                              <i class="bi bi-trash"></i>
                            </a>
                          </td>
                          <?php endif; ?>
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
