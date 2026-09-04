<?php
$host = 'yamanote.proxy.rlwy.net';
$port = '50569';
$db   = 'railway';
$user = 'root';
$pass = 'CMWUvPdxANBfjroFtXlKHTrxWJwsvtMy';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // اسم ملف الـ SQL بتاعك (تأكد إنه في نفس المجلد أو اكتب مساره الصحيح)
   $sqlFile = 'database/gym_master.sql';
    
    if (!file_exists($sqlFile)) {
        die("ملف الـ SQL غير موجود في نفس المجلد!");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    
    echo "<h2 style='color: green;'>تم رفع وتصدير قاعدة البيانات بنجاح يا ميزو! 🚀</h2>";
} catch (\PDOException $e) {
    echo "<h2 style='color: red;'>خطأ: " . $e->getMessage() . "</h2>";
}
