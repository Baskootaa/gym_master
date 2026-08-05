<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// حماية الصفحة للأدمن والموظف فقط
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header("Location: schedules.php");
    exit();
}

require_once 'config/db.php';

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM schedule_sessions WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: schedules.php?deleted=1");
exit();