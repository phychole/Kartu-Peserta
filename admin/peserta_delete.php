<?php
require_once __DIR__ . '/../config/db.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  $stmt = db()->prepare("DELETE FROM peserta WHERE id=?");
  $stmt->execute([$id]);
}
header('Location: ' . url('/admin/peserta.php'));
exit;