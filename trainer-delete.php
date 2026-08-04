<?php
require_once 'config/db.php';

$id = $_GET['id'] ?? null;

if ($id !== null && is_numeric($id)) {

    $stmt = $pdo->prepare('SELECT photo FROM trainers WHERE id = :id');
    $stmt->execute([':id' => (int) $id]);
    $trainer = $stmt->fetch();

    $deleteStmt = $pdo->prepare('DELETE FROM trainers WHERE id = :id');
    $deleteStmt->execute([':id' => (int) $id]);

    if ($trainer && strpos($trainer['photo'], 'trainers/') === 0) {
        $filePath = __DIR__ . '/assets/img/' . $trainer['photo'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

header('Location: trainers.php?deleted=1');
exit;
