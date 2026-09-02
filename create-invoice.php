<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("خطأ في الاتصال بقاعدة البيانات (PDO غير معرف).");
}

// دالة مساعدة لفحص الأعمدة الموجودة في الجدول لتجنب الأخطاء
function getTableColumns($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {}
    return [];
}

$subCols = getTableColumns($pdo, 'subscriptions');
$memberCols = getTableColumns($pdo, 'members');
$packageCols = getTableColumns($pdo, 'packages');
$salesCols = getTableColumns($pdo, 'sales');

// 1. جلب الاشتراكات بأدق طريقة ممكنة حسب هيكل قاعدة البيانات لديك (تشمل جميع الاشتراكات حتى المنتهية)
$subscriptions = [];
try {
    $subQueryStr = "SELECT s.*";
    
    if (in_array('name', $memberCols)) {
        $subQueryStr .= ", m.name as exact_member_name";
    } elseif (in_array('full_name', $memberCols)) {
        $subQueryStr .= ", m.full_name as exact_member_name";
    }
    
    if (in_array('phone', $memberCols)) {
        $subQueryStr .= ", m.phone as exact_member_phone";
    }
    
    if (in_array('name', $packageCols)) {
        $subQueryStr .= ", p.name as exact_package_name";
    }
    
    if (in_array('price', $packageCols)) {
        $subQueryStr .= ", p.price as package_table_price";
    }

    $subQueryStr .= " FROM subscriptions s";
    
    if (in_array('member_id', $subCols) && !empty($memberCols)) {
        $subQueryStr .= " LEFT JOIN members m ON s.member_id = m.id";
    }
    if (in_array('package_id', $subCols) && !empty($packageCols)) {
        $subQueryStr .= " LEFT JOIN packages p ON s.package_id = p.id";
    }
    
    $subQueryStr .= " ORDER BY s.id DESC";
    
    $stmt = $pdo->query($subQueryStr);
    if ($stmt) {
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY id DESC");
    if ($stmt) {
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 2. جلب المصروفات (Expenses)
$expenses = [];
try {
    $exp_query = $pdo->query("SELECT * FROM expenses ORDER BY id DESC");
    if ($exp_query) {
        $expenses = $exp_query->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

// 3. جلب مبيعات المتجر (POS) مع جلب اسم العضو المرتبط لزيادة الاحترافية
$sales = [];
try {
    $salesQueryStr = "SELECT sl.*";
    $productsCols = getTableColumns($pdo, 'products');
    
    if (!empty($productsCols) && in_array('name', $productsCols)) {
        $salesQueryStr .= ", p.name as product_real_name";
    }
    
    if (in_array('member_id', $salesCols) && !empty($memberCols)) {
        $salesQueryStr .= ", m.full_name as sale_member_name, m.phone as sale_member_phone";
    }
    
    $salesQueryStr .= " FROM sales sl";
    
    if (in_array('product_id', $salesCols) && !empty($productsCols)) {
        $salesQueryStr .= " LEFT JOIN products p ON sl.product_id = p.id";
    }
    
    if (in_array('member_id', $salesCols) && !empty($memberCols)) {
        $salesQueryStr .= " LEFT JOIN members m ON sl.member_id = m.id";
    }
    
    $salesQueryStr .= " ORDER BY sl.id DESC";
    
    $stmt = $pdo->query($salesQueryStr);
    if ($stmt) {
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    try {
        $stmt = $pdo->query("SELECT * FROM sales ORDER BY id DESC");
        if ($stmt) {
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $ex) {}
}

// جلب العملة المعتمدة
$currency = 'ج.م';
try {
    $settings_query = $pdo->query("SELECT currency FROM system_settings WHERE id=1");
    if ($settings_query) {
        $settings = $settings_query->fetch(PDO::FETCH_ASSOC);
        if ($settings && !empty($settings['currency'])) {
            $currency = $settings['currency'];
        }
    }
} catch (Exception $e) {}
?>

<?php $active_page = 'invoices'; ?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="app-main">
  <div class="app-content-header d-print-none">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0"><i class="bi bi-printer-fill me-2"></i>مركز طباعة الإيصالات والفواتير الشامل</h3>
        </div>
        <div class="col-sm-6 text-end">
          <a href="index.php" class="btn btn-secondary me-2">
            <i class="bi bi-house-door me-1"></i>لوحة التحكم
          </a>
          <button onclick="window.print()" class="btn btn-dark">
            <i class="bi bi-printer me-1"></i>طباعة الإيصال الحالي
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <!-- أزرار التبديل بين الأقسام -->
      <div class="row mb-4 d-print-none">
        <div class="col-md-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <label class="form-label fw-bold">اختر القسم المراد طباعة إيصالاته:</label>
              <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-outline-primary active" onclick="switchSection('subs', event)">
                  <i class="bi bi-people-fill me-1"></i> إيصالات الاشتراكات (Members)
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="switchSection('expenses', event)">
                  <i class="bi bi-wallet2 me-1"></i> إيصالات المصروفات (Finance)
                </button>
                <button type="button" class="btn btn-outline-success" onclick="switchSection('pos', event)">
                  <i class="bi bi-cart-check-fill me-1"></i> مبيعات المتجر (POS)
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= قسم 1: الاشتراكات (مع إضافة بحث سريع) ================= -->
      <div id="section_subs" class="receipt-section">
        <div class="card card-outline card-primary shadow-sm mb-4 d-print-none">
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">بحث سريع عن العضو (بالاسم أو رقم الهاتف):</label>
                <input type="text" id="subSearchInput" class="form-control" placeholder="اكتب للبحث السريع في قائمة الاشتراكات..." onkeyup="filterSubscriptions()">
              </div>
            </div>
            
            <label class="form-label fw-bold">اختر مشتركاً أو أكثر للطباعة في نفس الإيصال:</label>
            <div class="row g-2" id="subscriptionsContainer" style="max-height: 220px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
              <?php if (empty($subscriptions)): ?>
                <p class="text-muted text-center">لا توجد اشتراكات مسجلة.</p>
              <?php else: ?>
                <?php foreach ($subscriptions as $sub): 
                    $mName = $sub['exact_member_name'] ?? $sub['member_name'] ?? $sub['name'] ?? $sub['member'] ?? ('عضو #' . ($sub['member_id'] ?? $sub['id']));
                    $mPhone = $sub['exact_member_phone'] ?? '';
                    $pName = $sub['exact_package_name'] ?? $sub['package_name'] ?? $sub['package'] ?? 'اشتراك جيم';
                    $pPrice = $sub['price'] ?? $sub['package_table_price'] ?? $sub['total'] ?? $sub['amount'] ?? 500;
                    $sDate = $sub['start_date'] ?? $sub['date'] ?? '---';
                    $eDate = $sub['end_date'] ?? '---';
                ?>
                  <div class="col-md-6 sub-item-wrapper" data-search-text="<?php echo strtolower($mName . ' ' . $mPhone . ' ' . $pName); ?>">
                    <div class="form-check">
                      <input class="form-check-input sub-checkbox" type="checkbox" value="<?php echo $sub['id']; ?>"
                           data-member="<?php echo htmlspecialchars($mName); ?>"
                           data-package="<?php echo htmlspecialchars($pName); ?>"
                           data-price="<?php echo $pPrice; ?>"
                           data-start="<?php echo $sDate; ?>"
                           data-end="<?php echo $eDate; ?>"
                           id="sub_chk_<?php echo $sub['id']; ?>" onchange="updateReceiptsView()">
                      <label class="form-check-label" for="sub_chk_<?php echo $sub['id']; ?>">
                        <strong><?php echo htmlspecialchars($mName); ?></strong> <?php echo !empty($mPhone) ? '(' . htmlspecialchars($mPhone) . ')' : ''; ?> - <?php echo htmlspecialchars($pName); ?> (<strong><?php echo $pPrice; ?> <?php echo $currency; ?></strong>)
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- معاينة إيصال الاشتراكات -->
        <div class="card shadow-sm border-primary mb-4">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <h2>GYM MASTER - إيصال سداد اشتراكات</h2>
              <p class="text-muted">إيصال رسمي معتمد من الإدارة</p>
              <hr>
            </div>
            <div class="row mb-3">
              <div class="col-6"><strong>نوع الفاتورة:</strong> <span class="text-primary fs-5">اشتراكات أعضاء</span></div>
              <div class="col-6 text-end"><strong>تاريخ الطباعة:</strong> <?php echo date('Y-m-d H:i'); ?></div>
            </div>
            <table class="table table-bordered text-center align-middle">
              <thead class="table-dark">
                <tr>
                  <th>اسم المشترك</th>
                  <th>الباقة / الاشتراك</th>
                  <th>تاريخ البداية</th>
                  <th>تاريخ النهاية</th>
                  <th>المبلغ المدفوع</th>
                </tr>
              </thead>
              <tbody id="sub_receipt_items">
                <tr>
                  <td colspan="5" class="text-muted">الرجاء اختيار مشترك واحد على الأقل من القائمة أعلاه</td>
                </tr>
              </tbody>
            </table>
            <div class="row mt-4">
              <div class="col-6"><p><strong>الحالة:</strong> <span class="badge bg-success">تم الدفع بنجاح</span></p></div>
              <div class="col-6 text-end"><p class="mt-4"><strong>توقيع المسؤول:</strong> ........................................</p></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= قسم 2: المصروفات (Checkboxes) ================= -->
      <div id="section_expenses" class="receipt-section" style="display: none;">
        <div class="card card-outline card-danger shadow-sm mb-4 d-print-none">
          <div class="card-body">
            <label class="form-label fw-bold">اختر مصروفاً أو أكثر للطباعة معاً في إيصال الخزينة:</label>
            <div class="row g-2" style="max-height: 220px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
              <?php if (empty($expenses)): ?>
                <p class="text-muted text-center">لا توجد مصروفات مسجلة.</p>
              <?php else: ?>
                <?php foreach ($expenses as $exp): 
                    $title = $exp['title'] ?? $exp['expense_title'] ?? $exp['name'] ?? 'مصروف عام';
                    $amount = $exp['amount'] ?? $exp['price'] ?? 0;
                    $category = $exp['category'] ?? $exp['cat'] ?? 'عام';
                    $date = $exp['date'] ?? $exp['created_at'] ?? '---';
                    $notes = $exp['notes'] ?? $exp['description'] ?? '---';
                ?>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input exp-checkbox" type="checkbox" value="<?php echo $exp['id']; ?>"
                           data-title="<?php echo htmlspecialchars($title); ?>"
                           data-amount="<?php echo $amount; ?>"
                           data-category="<?php echo htmlspecialchars($category); ?>"
                           data-date="<?php echo $date; ?>"
                           data-notes="<?php echo htmlspecialchars($notes); ?>"
                           id="exp_chk_<?php echo $exp['id']; ?>" onchange="updateReceiptsView()">
                      <label class="form-check-label" for="exp_chk_<?php echo $exp['id']; ?>">
                        <strong><?php echo htmlspecialchars($title); ?></strong> (<strong><?php echo $amount; ?> <?php echo $currency; ?></strong>)
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- معاينة إيصال المصروفات -->
        <div class="card shadow-sm border-danger mb-4">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <h2>GYM MASTER - إيصال صرف من الخزينة</h2>
              <p class="text-muted">سجل المصروفات والخارجات المالية</p>
              <hr>
            </div>
            <div class="row mb-3">
              <div class="col-6"><strong>نوع الإيصال:</strong> <span class="text-danger fs-5">مصروفات نقدية</span></div>
              <div class="col-6 text-end"><strong>تاريخ الطباعة:</strong> <?php echo date('Y-m-d H:i'); ?></div>
            </div>
            <table class="table table-bordered text-center align-middle">
              <thead class="table-dark">
                <tr>
                  <th>عنوان المصروف</th>
                  <th>الفئة</th>
                  <th>تاريخ الصرف</th>
                  <th>ملاحظات</th>
                  <th>المبلغ المنصرف</th>
                </tr>
              </thead>
              <tbody id="exp_receipt_items">
                <tr>
                  <td colspan="5" class="text-muted">الرجاء اختيار بند مصروف واحد على الأقل</td>
                </tr>
              </tbody>
            </table>
            <div class="row mt-4">
              <div class="col-6"><p><strong>الاعتماد المالي:</strong> <span class="badge bg-danger">تم الصرف</span></p></div>
              <div class="col-6 text-end"><p class="mt-4"><strong>توقيع المحاسب / المدير:</strong> ........................................</p></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= قسم 3: مبيعات المتجر POS (مع إضافة بحث وإظهار اسم العضو) ================= -->
      <div id="section_pos" class="receipt-section" style="display: none;">
        <div class="card card-outline card-success shadow-sm mb-4 d-print-none">
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label fw-bold">بحث سريع في مبيعات المتجر (باسم المنتج أو اسم العضو):</label>
                <input type="text" id="posSearchInput" class="form-control" placeholder="اكتب للبحث السريع في المبيعات..." onkeyup="filterPosSales()">
              </div>
            </div>

            <label class="form-label fw-bold">اختر منتجاً أو أكثر لدمجهم في فاتورة واحدة:</label>
            <div class="row g-2" id="posContainer" style="max-height: 220px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
              <?php if (empty($sales)): ?>
                <p class="text-muted text-center">لا توجد مبيعات مسجلة في المتجر.</p>
              <?php else: ?>
                <?php foreach ($sales as $sale): 
                    $prodName = $sale['product_real_name'] ?? $sale['product_name'] ?? $sale['item_name'] ?? $sale['name'] ?? 'منتج / مكمل';
                    $qty = $sale['quantity'] ?? $sale['qty'] ?? 1;
                    $total = $sale['total_price'] ?? $sale['price'] ?? $sale['total'] ?? 0;
                    $saleDate = $sale['sale_date'] ?? $sale['date'] ?? $sale['created_at'] ?? '---';
                    $memberName = $sale['sale_member_name'] ?? 'عضو نقدي / عام';
                    $memberPhone = $sale['sale_member_phone'] ?? '';
                ?>
                  <div class="col-md-6 pos-item-wrapper" data-search-text="<?php echo strtolower($prodName . ' ' . $memberName . ' ' . $memberPhone); ?>">
                    <div class="form-check">
                      <input class="form-check-input pos-checkbox" type="checkbox" value="<?php echo $sale['id']; ?>"
                           data-product="<?php echo htmlspecialchars($prodName); ?>"
                           data-member="<?php echo htmlspecialchars($memberName); ?>"
                           data-qty="<?php echo $qty; ?>"
                           data-total="<?php echo $total; ?>"
                           data-date="<?php echo $saleDate; ?>"
                           id="pos_chk_<?php echo $sale['id']; ?>" onchange="updateReceiptsView()">
                      <label class="form-check-label" for="pos_chk_<?php echo $sale['id']; ?>">
                        <strong><?php echo htmlspecialchars($prodName); ?></strong> - العضو: <strong><?php echo htmlspecialchars($memberName); ?></strong> (الكمية: <?php echo $qty; ?>) - <strong><?php echo $total; ?> <?php echo $currency; ?></strong>
                      </label>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- معاينة فاتورة مبيعات المتجر -->
        <div class="card shadow-sm border-success mb-4">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <h2>GYM MASTER - فاتورة مبيعات المتجر (POS)</h2>
              <p class="text-muted">مبيعات المنتجات والمكملات الغذائية والمياه</p>
              <hr>
            </div>
            <div class="row mb-3">
              <div class="col-6"><strong>نوع الفاتورة:</strong> <span class="text-success fs-5">مبيعات متجر</span></div>
              <div class="col-6 text-end"><strong>تاريخ الطباعة:</strong> <?php echo date('Y-m-d H:i'); ?></div>
            </div>
            <table class="table table-bordered text-center align-middle">
              <thead class="table-dark">
                <tr>
                  <th>اسم العضو</th>
                  <th>المنتج / المكمل</th>
                  <th>الكمية</th>
                  <th>تاريخ البيع</th>
                  <th>الإجمالي</th>
                </tr>
              </thead>
              <tbody id="pos_receipt_items">
                <tr>
                  <td colspan="5" class="text-muted">الرجاء اختيار منتج واحد على الأقل من المتجر</td>
                </tr>
              </tbody>
              <tfoot id="pos_receipt_footer" style="display: none;">
                <tr class="fw-bold">
                  <td colspan="4" class="text-end">الإجمالي الكلي:</td>
                  <td id="pos_grand_total" class="text-success">0.00 <?php echo $currency; ?></td>
                </tr>
              </tfoot>
            </table>
            <div class="row mt-4">
              <div class="col-6"><p><strong>الحالة:</strong> <span class="badge bg-success">تم البيع نقداً</span></p></div>
              <div class="col-6 text-end"><p class="mt-4"><strong>توقيع البائع:</strong> ........................................</p></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<script>
function switchSection(section, event) {
    document.querySelectorAll('.receipt-section').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active', 'btn-primary', 'btn-danger', 'btn-success'));
    
    if (section === 'subs') {
        document.getElementById('section_subs').style.display = 'block';
        event.currentTarget.classList.add('active', 'btn-primary');
    } else if (section === 'expenses') {
        document.getElementById('section_expenses').style.display = 'block';
        event.currentTarget.classList.add('active', 'btn-danger');
    } else if (section === 'pos') {
        document.getElementById('section_pos').style.display = 'block';
        event.currentTarget.classList.add('active', 'btn-success');
    }
}

// دوال البحث السريع
function filterSubscriptions() {
    const query = document.getElementById('subSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.sub-item-wrapper');
    items.forEach(item => {
        const text = item.getAttribute('data-search-text');
        if (text.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterPosSales() {
    const query = document.getElementById('posSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.pos-item-wrapper');
    items.forEach(item => {
        const text = item.getAttribute('data-search-text');
        if (text.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function updateReceiptsView() {
    // 1. تحديث جدول الاشتراكات
    const subCheckboxes = document.querySelectorAll('.sub-checkbox:checked');
    const subTbody = document.getElementById('sub_receipt_items');
    subTbody.innerHTML = '';
    
    if (subCheckboxes.length === 0) {
        subTbody.innerHTML = '<tr><td colspan="5" class="text-muted">الرجاء اختيار مشترك واحد على الأقل من القائمة أعلاه</td></tr>';
    } else {
        let totalSub = 0;
        subCheckboxes.forEach(chk => {
            const member = chk.getAttribute('data-member');
            const pkg = chk.getAttribute('data-package');
            const price = parseFloat(chk.getAttribute('data-price')) || 0;
            const start = chk.getAttribute('data-start');
            const end = chk.getAttribute('data-end');
            totalSub += price;
            
            subTbody.innerHTML += `
                <tr>
                    <td><strong>${member}</strong></td>
                    <td>${pkg}</td>
                    <td>${start}</td>
                    <td>${end}</td>
                    <td><strong class="text-success">${price.toFixed(2)} <?php echo $currency; ?></strong></td>
                </tr>
            `;
        });
        subTbody.innerHTML += `
            <tr class="fw-bold table-active">
                <td colspan="4" class="text-end">إجمالي الاشتراكات:</td>
                <td class="text-success">${totalSub.toFixed(2)} <?php echo $currency; ?></td>
            </tr>
        `;
    }

    // 2. تحديث جدول المصروفات
    const expCheckboxes = document.querySelectorAll('.exp-checkbox:checked');
    const expTbody = document.getElementById('exp_receipt_items');
    expTbody.innerHTML = '';
    
    if (expCheckboxes.length === 0) {
        expTbody.innerHTML = '<tr><td colspan="5" class="text-muted">الرجاء اختيار بند مصروف واحد على الأقل</td></tr>';
    } else {
        let totalExp = 0;
        expCheckboxes.forEach(chk => {
            const title = chk.getAttribute('data-title');
            const cat = chk.getAttribute('data-category');
            const date = chk.getAttribute('data-date');
            const notes = chk.getAttribute('data-notes');
            const amount = parseFloat(chk.getAttribute('data-amount')) || 0;
            totalExp += amount;
            
            expTbody.innerHTML += `
                <tr>
                    <td><strong>${title}</strong></td>
                    <td>${cat}</td>
                    <td>${date}</td>
                    <td>${notes}</td>
                    <td><strong class="text-danger">${amount.toFixed(2)} <?php echo $currency; ?></strong></td>
                </tr>
            `;
        });
        expTbody.innerHTML += `
            <tr class="fw-bold table-active">
                <td colspan="4" class="text-end">إجمالي المصروفات المنصرفة:</td>
                <td class="text-danger">${totalExp.toFixed(2)} <?php echo $currency; ?></td>
            </tr>
        `;
    }

    // 3. تحديث جدول مبيعات المتجر POS (مع اسم العضو)
    const posCheckboxes = document.querySelectorAll('.pos-checkbox:checked');
    const posTbody = document.getElementById('pos_receipt_items');
    const posFooter = document.getElementById('pos_receipt_footer');
    posTbody.innerHTML = '';
    
    if (posCheckboxes.length === 0) {
        posTbody.innerHTML = '<tr><td colspan="5" class="text-muted">الرجاء اختيار منتج واحد على الأقل من المتجر</td></tr>';
        posFooter.style.display = 'none';
    } else {
        let grandTotal = 0;
        posCheckboxes.forEach(chk => {
            const product = chk.getAttribute('data-product');
            const member = chk.getAttribute('data-member');
            const qty = chk.getAttribute('data-qty');
            const date = chk.getAttribute('data-date');
            const total = parseFloat(chk.getAttribute('data-total')) || 0;
            grandTotal += total;
            
            posTbody.innerHTML += `
                <tr>
                    <td><strong>${member}</strong></td>
                    <td><strong>${product}</strong></td>
                    <td>${qty}</td>
                    <td>${date}</td>
                    <td><strong class="text-success">${total.toFixed(2)} <?php echo $currency; ?></strong></td>
                </tr>
            `;
        });
        document.getElementById('pos_grand_total').innerText = grandTotal.toFixed(2) + ' <?php echo $currency; ?>';
        posFooter.style.display = 'table-row-group';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
