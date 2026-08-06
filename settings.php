<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// استدعاء ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// التحقق من تسجيل الدخول
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// التحقق مما إذا كان المستخدم الحالي Admin
$isAdmin = false;
$check_admin_stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$check_admin_stmt->bind_param("i", $user_id);
$check_admin_stmt->execute();
$current_user_role_res = $check_admin_stmt->get_result()->fetch_assoc();
if ($current_user_role_res && strtolower($current_user_role_res['role']) === 'admin') {
    $isAdmin = true;
}

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
// 2. معالجة تحديث البروفايل أو بيانات أي مستخدم (للأدمن)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $target_user_id = ($isAdmin && !empty($_POST['target_user_id'])) ? intval($_POST['target_user_id']) : $user_id;
    
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone_num = trim($_POST['phone'] ?? '');
    $role      = trim($_POST['role'] ?? 'Staff');
    $password  = $_POST['password'] ?? '';

    if (!empty($full_name) && !empty($email)) {
        // التحقق من الأعمدة المتاحة في جدول users لمنع أي خطأ
        $has_full_name = false;
        $chk_col = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
        if ($chk_col && $chk_col->num_rows > 0) {
            $has_full_name = true;
        }

        $has_phone = false;
        $chk_ph = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if ($chk_ph && $chk_ph->num_rows > 0) {
            $has_phone = true;
        }

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($has_full_name && $has_phone) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $full_name, $email, $phone_num, $role, $hashed_password, $target_user_id);
            } elseif ($has_full_name) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $role, $hashed_password, $target_user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $email, $role, $hashed_password, $target_user_id);
            }
        } else {
            if ($has_full_name && $has_phone) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $phone_num, $role, $target_user_id);
            } elseif ($has_full_name) {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $full_name, $email, $role, $target_user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssi", $email, $role, $target_user_id);
            }
        }

        if ($stmt->execute()) {
            if ($target_user_id == $user_id) {
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_role'] = $role;
            }
            $success_msg = "تم تحديث البيانات بنجاح!";
        } else {
            $error_msg = "حدث خطأ أثناء تحديث البيانات.";
        }
    } else {
        $error_msg = "يرجى كتابة الاسم والبريد الإلكتروني بشكل صحيح.";
    }
}

// جلب الإعدادات الحالية
$settings_result = $conn->query("SELECT * FROM system_settings WHERE id=1");
$settings = $settings_result ? $settings_result->fetch_assoc() : [];

// جلب الباقات الحالية
$packages_result = $conn->query("SELECT * FROM packages");

// فحص الأعمدة المتاحة لجلب بيانات المستخدمين بدون أخطاء
$col_check_fn = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
$has_fn = ($col_check_fn && $col_check_fn->num_rows > 0);

$col_check_ph = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
$has_ph = ($col_check_ph && $col_check_ph->num_rows > 0);

$name_field = $has_fn ? "full_name" : "email";
$phone_field = $has_ph ? "phone" : "'' as phone";

// جلب قائمة كل المستخدمين في حال كان الأدمن هو من يدخل الصفحة
$all_users = [];
if ($isAdmin) {
    $users_res = $conn->query("SELECT id, $name_field as display_name, email, $phone_field, role FROM users ORDER BY id ASC");
    if ($users_res) {
        while ($row = $users_res->fetch_assoc()) {
            $all_users[] = $row;
        }
    }
}

// جلب بيانات المستخدم الحالي افتراضياً
$user_stmt = $conn->prepare("SELECT id, $name_field as display_name, email, $phone_field, role FROM users WHERE id = ? LIMIT 1");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();
if (!$current_user) {
    $current_user = ['display_name' => '', 'email' => '', 'phone' => '', 'role' => 'Staff'];
}
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
          <!-- قسم تعديل الملف الشخصي وصلاحيات المستخدمين  -->
          <!-- ========================================== -->
          <div class="card card-outline card-info mb-4 shadow-sm">
            <div class="card-header bg-info text-white">
              <h3 class="card-title mb-0"><i class="bi bi-person-circle me-2"></i>تعديل الحسابات وصلاحيات المستخدمين (البروفايل)</h3>
            </div>
            <form action="settings.php" method="POST">
              <div class="card-body">
                <div class="row">
                  
                  <?php if ($isAdmin): ?>
                  <!-- اختيار المستخدم في حال كان الأدمن هو من يتصفح الصفحة -->
                  <div class="col-md-12 mb-3">
                    <label class="form-label font-weight-bold text-primary">اختر المستخدم للتعديل على بياناته أو صلاحيته:</label>
                    <select class="form-select border-primary" id="target_user_select" name="target_user_id">
                        <?php foreach($all_users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" 
                                data-email="<?php echo htmlspecialchars($u['email']); ?>" 
                                data-phone="<?php echo htmlspecialchars($u['phone'] ?? ''); ?>" 
                                data-role="<?php echo htmlspecialchars($u['role']); ?>"
                                data-name="<?php echo htmlspecialchars($u['display_name']); ?>"
                                <?php echo ($u['id'] == $user_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['display_name']); ?> (<?php echo htmlspecialchars($u['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endif; ?>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">الاسم بالكامل</label>
                    <input type="text" class="form-control" id="full_name_input" name="full_name" value="<?php echo htmlspecialchars($current_user['display_name'] ?? ''); ?>" required>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email_input" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">رقم المحمول (الهاتف)</label>
                    <input type="text" class="form-control" id="phone_input" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" placeholder="أدخل رقم المحمول">
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">الصفة / المسمى (Role)</label>
                    <select class="form-select" id="role_select" name="role">
                      <option value="Staff">Staff (موظف / كابتن)</option>
                      <option value="user">متدرب (User / يوزر عادي)</option>
                      <option value="Admin">Admin (مدير النظام)</option>
                    </select>
                  </div>

                  <div class="col-md-12 mb-3">
                    <label class="form-label font-weight-bold">كلمة المرور الجديدة (اتركها فارغة إذا لم ترد التغيير)</label>
                    <input type="password" class="form-control" name="password" placeholder="••••••••">
                  </div>

                </div>
              </div>
              <div class="card-footer text-end bg-light">
                <button type="submit" name="update_profile" class="btn btn-info text-white px-4">
                  <i class="bi bi-person-check-fill me-2"></i>حفظ البيانات والصلاحية
                </button>
              </div>
            </form>
          </div>

          <!-- ========================================== -->
          <!-- قسم إعدادات الجيم العامة                  -->
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const userSelect = document.getElementById("target_user_select");
    if (userSelect) {
        function updateFormFields() {
            const selectedOption = userSelect.options[userSelect.selectedIndex];
            document.getElementById("full_name_input").value = selectedOption.getAttribute("data-name") || "";
            document.getElementById("email_input").value = selectedOption.getAttribute("data-email") || "";
            document.getElementById("phone_input").value = selectedOption.getAttribute("data-phone") || "";
            
            const roleVal = selectedOption.getAttribute("data-role") || "Staff";
            const roleSelect = document.getElementById("role_select");
            for (let i = 0; i < roleSelect.options.length; i++) {
                if (roleSelect.options[i].value.toLowerCase() === roleVal.toLowerCase()) {
                    roleSelect.selectedIndex = i;
                    break;
                }
            }
        }
        userSelect.addEventListener("change", updateFormFields);
        updateFormFields();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

