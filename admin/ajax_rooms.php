<?php
require_once __DIR__ . '/../config/db.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$kelas = trim($_GET['kelas'] ?? '');

try {
  $pdo = db();

  if ($kelas !== '') {
    $st = $pdo->prepare("SELECT DISTINCT ruang FROM peserta WHERE kelas = ? AND ruang <> '' ORDER BY ruang");
    $st->execute([$kelas]);
  } else {
    $st = $pdo->query("SELECT DISTINCT ruang FROM peserta WHERE ruang <> '' ORDER BY ruang");
  }

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $rooms = [];
  foreach ($rows as $r) {
    if (isset($r['ruang'])) $rooms[] = $r['ruang'];
  }

  echo json_encode($rooms, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode([], JSON_UNESCAPED_UNICODE);
}