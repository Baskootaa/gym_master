<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/config/db.php';

// التحقق من تسجيل الدخول
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// ----------------------------------------------------
// 1. معالجة حفظ إعدادات النظام العامة
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    
    $gym_name = $conn->real_escape_string($_POST['gym_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $tax_rate = floatval($_POST['tax_rate']);
    $invoice_message = $conn->real_escape_string($_POST['invoice_message']);
    $open_time = $conn->real_escape_string($_POST['open_time']);
    $close_time = $conn->real_escape_string($_POST['close_time']);
    $currency = $conn->real_escape_string($_POST['currency']);

    $update_settings = "UPDATE system_settings SET 
                        gym_name='$gym_name', 
                        phone='$phone',
                        tax_rate='$tax_rate',
                        invoice_message='$invoice_message',
                        open_time='$open_time',
                        close_time='$close_time',
                        currency='$currency'
                        WHERE id=1";
    
    $conn->query($update_settings);

    // تحديث أسعار الباقات
    if (isset($_POST['pkg_id']) && isset($_POST['pkg_price'])) {
        foreach ($_POST['pkg_id'] as $key => $p_id) {
            $p_price = $_POST['pkg_price'][$key];
            $clean_id = intval($p_id);
            $clean_price = intval($p_price);
            $conn->query("UPDATE packages SET price='$clean_price' WHERE id='$clean_id'");
        }
    }
    
    $success_msg = "تم حفظ إعدادات النظام العامة وتحديث الباقات بنجاح!";
}

// ----------------------------------------------------
// 2. معالجة تحديث البروفايل الشخصي (بيانات المستخدم الحالي)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = trim($_POST['role'] ?? 'Staff');
    $password  = $_POST['password'] ?? '';

    if (!empty($full_name) && !empty($email)) {
        if (!empty($password)) {
            // تحديث مع كلمة السر
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $role, $hashed_password, $user_id);
        } else {
            // تحديث بدون تغيير كلمة السر
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $role, $user_id);
        }

        if ($stmt->execute()) {
            // تحديث الجلسة بالبيانات الجديدة مباشرة
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_role'] = $role;
            $success_msg = "تم تحديث بياناتك الشخصية بنجاح!";
        } else {
            $error_msg = "حدث خطأ أثناء تحديث البيانات الشخصية.";
        }
    } else {
        $error_msg = "يرجى كتابة الاسم والبريد الإلكتروني بشكل صحيح.";
    }
}

// جلب الإعدادات الحالية لعرضها في الفورم
$settings_result = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings = $settings_result ? $settings_result->fetch_assoc() : [];

// جلب الباقات الحالية
$packages_result = $conn->query("SELECT * FROM packages");

// جلب بيانات المستخدم الحالي
$user_stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ? LIMIT 1");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();
?>

<?php $active_page = 'settings'; ?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!--begin::App Main-->
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0">إعدادات النظام والبروفايل</h3>
        </div>
        <div class="col-sm-6 text-end">
          <a href="index.php" class="btn btn-primary">
              <i class="bi bi-house-door-fill me-2"></i>لوحة التحكم
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-10 mx-auto">
            
          <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_msg); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <!-- ========================================== -->
          <!-- قسم تعديل الملف الشخصي (Profile Settings)   -->
          <!-- ========================================== -->
          <div class="card card-outline card-info mb-4 shadow-sm">
            <div class="card-header bg-info text-white">
              <h3 class="card-title mb-0"><i class="bi bi-person-circle me-2"></i>تعديل الحساب الشخصي (البروفايل)</h3>
            </div>
            <form action="settings.php" method="POST">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">الاسم بالكامل</label>
                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">البريد الإلكتروني</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">الصفة / المسمى (Role)</label>
                    <select class="form-select" name="role">
                      <option value="Staff" <?php echo (($current_user['role'] ?? '') == 'Staff') ? 'selected' : ''; ?>>Staff (موظف / كابتن)</option>
                      <option value="Trainee" <?php echo (($current_user['role'] ?? '') == 'Trainee' || ($current_user['role'] ?? '') == 'متدرب') ? 'selected' : ''; ?>>متدرب (Trainee)</option>
                      <option value="Admin" <?php echo (($current_user['role'] ?? '') == 'Admin') ? 'selected' : ''; ?>>Admin (مدير النظام)</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">كلمة المرور الجديدة (اتركها فارغة إذا لم ترد التغيير)</label>
                    <input type="password" class="form-control" name="password" placeholder="••••••••">
                  </div>
                </div>
              </div>
              <div class="card-footer text-end bg-light">
                <button type="submit" name="update_profile" class="btn btn-info text-white px-4">
                  <i class="bi bi-person-check-fill me-2"></i>حفظ بيانات البروفايل
                </button>
              </div>
            </form>
          </div>

          <!-- ========================================== -->
          <!-- قسم إعدادات الجيم العامة                      -->
          <!-- ========================================== -->
          <div class="card card-primary mb-4 shadow-sm">
            <div class="card-header bg-dark text-white">
              <h3 class="card-title mb-0"><i class="bi bi-sliders me-2"></i>لوحة التحكم في إعدادات الجيم العامة</h3>
            </div>
            
            <form action="settings.php" method="POST">
              <div class="card-body">
                  
                <!-- البيانات الأساسية -->
                <h5 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-building me-2"></i>البيانات الأساسية</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">اسم الجيم</label>
                      <input type="text" class="form-control" name="gym_name" value="<?php echo htmlspecialchars($settings['gym_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">رقم التواصل</label>
                      <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- الإعدادات المالية والفواتير -->
                <h5 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-receipt me-2"></i>الإعدادات المالية والفواتير</h5>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">العملة الافتراضية</label>
                      <select class="form-select" name="currency">
                          <option value="ج.م" <?php echo (isset($settings['currency']) && $settings['currency'] == 'ج.م') ? 'selected' : ''; ?>>جنيه مصري (ج.م)</option>
                          <option value="$" <?php echo (isset($settings['currency']) && $settings['currency'] == '$') ? 'selected' : ''; ?>>دولار أمريكي ($)</option>
                          <option value="ريال" <?php echo (isset($settings['currency']) && $settings['currency'] == 'ريال') ? 'selected' : ''; ?>>ريال (SAR)</option>
                      </select>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label">نسبة ضريبة القيمة المضافة (%)</label>
                      <div class="input-group">
                          <input type="number" step="0.01" class="form-control" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '14'); ?>">
                          <span class="input-group-text">%</span>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label">رسالة أسفل الفاتورة</label>
                      <input type="text" class="form-control" name="invoice_message" value="<?php echo htmlspecialchars($settings['invoice_message'] ?? 'نتمنى لكم تمريناً سعيداً'); ?>">
                    </div>
                </div>

                <!-- مواعيد العمل -->
                <h5 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-clock me-2"></i>مواعيد العمل (Working Hours)</h5>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">موعد الفتح</label>
                      <input type="time" class="form-control" name="open_time" value="<?php echo htmlspecialchars($settings['open_time'] ?? '08:00'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">موعد الإغلاق</label>
                      <input type="time" class="form-control" name="close_time" value="<?php echo htmlspecialchars($settings['close_time'] ?? '00:00'); ?>">
                    </div>
                </div>

                <!-- أسعار الباقات -->
                <h5 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-tags-fill me-2"></i>أسعار باقات الاشتراك</h5>
                <?php if ($packages_result && $packages_result->num_rows > 0): ?>
                  <?php while($pkg = $packages_result->fetch_assoc()): ?>
                  <div class="row mb-2 align-items-center">
                      <div class="col-md-6">
                          <label class="form-label mb-0 fw-bold"><?php echo htmlspecialchars($pkg['name']); ?></label>
                          <input type="hidden" name="pkg_id[]" value="<?php echo $pkg['id']; ?>">
                      </div>
                      <div class="col-md-6">
                          <div class="input-group">
                              <input type="number" class="form-control" name="pkg_price[]" value="<?php echo $pkg['price']; ?>" required>
                              <span class="input-group-text"><?php echo htmlspecialchars($settings['currency'] ?? 'ج.م'); ?></span>
                          </div>
                      </div>
                  </div>
                  <?php endwhile; ?>
                <?php endif; ?>

              </div>
              <div class="card-footer text-end bg-light">
                <button type="submit" name="save_settings" class="btn btn-primary btn-lg px-5">
                  <i class="bi bi-save me-2"></i>حفظ إعدادات النظام
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
<!--end::App Main-->

<?php require_once 'includes/footer.php'; ?>