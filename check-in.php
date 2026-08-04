<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int) ($_POST['member_id'] ?? 0);

    if ($member_id <= 0) {
        $errors[] = 'من فضلك اختار عضو';
    } else {
        $stmt = $pdo->prepare('INSERT INTO check_ins (member_id) VALUES (:member_id)');
        $stmt->execute(['member_id' => $member_id]);
        $success = true;
    }
}

$members = $pdo->query('SELECT id, full_name FROM members ORDER BY full_name ASC')->fetchAll();

$todayCheckins = $pdo->query(
    "SELECT c.id, c.check_in_time, m.full_name,
            (SELECT p.name FROM subscriptions s
             JOIN packages p ON p.id = s.package_id
             WHERE s.member_id = m.id
             ORDER BY s.end_date DESC LIMIT 1) AS package_name,
            (SELECT s.end_date FROM subscriptions s
             WHERE s.member_id = m.id
             ORDER BY s.end_date DESC LIMIT 1) AS end_date
     FROM check_ins c
     JOIN members m ON m.id = c.member_id
     WHERE DATE(c.check_in_time) = CURDATE()
     ORDER BY c.check_in_time DESC"
)->fetchAll();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">تسجيل دخول عضو</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
            <li class="breadcrumb-item active">تسجيل دخول عضو</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <?php if ($success): ?>
        <div class="alert alert-success py-2">تم تسجيل الحضور بنجاح ✅</div>
      <?php endif; ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>

      <div class="row">
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header bg-info text-white">
              <h3 class="card-title"><i class="bi bi-qr-code-scan me-2"></i>تسجيل حضور</h3>
            </div>
            <div class="card-body">
              <?php if (empty($members)): ?>
                <p class="text-secondary">لازم تضيف أعضاء الأول من <a href="member-add.php">هنا</a></p>
              <?php else: ?>
                <form method="POST" action="check-in.php">
                  <div class="mb-3">
                    <label class="form-label">اختار العضو</label>
                    <select name="member_id" class="form-select" required>
                      <option value="">-- اختار عضو --</option>
                      <?php foreach ($members as $member): ?>
                        <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-info text-white w-100 btn-lg">
                    <i class="bi bi-check-circle me-1"></i> تسجيل الحضور
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="bi bi-person-check-fill me-2"></i>تسجيلات الدخول اليوم (<?= count($todayCheckins) ?>)</h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>اسم العضو</th>
                      <th>نوع الاشتراك</th>
                      <th>وقت الدخول</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($todayCheckins)): ?>
                      <tr><td colspan="4" class="text-center text-secondary py-4">مفيش تسجيلات دخول النهاردة لسه</td></tr>
                    <?php else: ?>
                      <?php foreach ($todayCheckins as $i => $checkin): ?>
                        <tr>
                          <td><?= $i + 1 ?></td>
                          <td><?= htmlspecialchars($checkin['full_name']) ?></td>
                          <td><?= htmlspecialchars($checkin['package_name'] ?? 'بدون اشتراك') ?></td>
                          <td><?= date('h:i A', strtotime($checkin['check_in_time'])) ?></td>
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
