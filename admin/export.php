<?php
require_once __DIR__ . '/../config/db.php';
require_login();

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$kelas = trim($_GET['kelas'] ?? '');
$ruang = trim($_GET['ruang'] ?? '');
$q     = trim($_GET['q'] ?? ''); // cari nama/nomor

$sql = "SELECT nomor_peserta, nama, kelas, ruang FROM peserta WHERE 1=1";
$params = [];

if ($kelas !== '') { $sql .= " AND kelas = ?"; $params[] = $kelas; }
if ($ruang !== '') { $sql .= " AND ruang = ?"; $params[] = $ruang; }
if ($q !== '') {
  $sql .= " AND (nomor_peserta LIKE ? OR nama LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
$sql .= " ORDER BY nomor_peserta";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* ====== Build XLSX ====== */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Peserta');

// Header
$headers = ['No', 'Nomor Peserta', 'Nama', 'Kelas', 'Ruang'];
$sheet->fromArray($headers, null, 'A1');

// Data
$r = 2;
$no = 1;
foreach ($rows as $row) {
  $sheet->setCellValue("A$r", $no++);

  // penting: nomor peserta diset STRING agar tidak berubah format
  $sheet->setCellValueExplicit(
    "B$r",
    (string)$row['nomor_peserta'],
    DataType::TYPE_STRING
  );

  $sheet->setCellValue("C$r", (string)$row['nama']);
  $sheet->setCellValue("D$r", (string)$row['kelas']);
  $sheet->setCellValue("E$r", (string)$row['ruang']);

  $r++;
}

$lastRow = max(1, $r - 1);   // kalau kosong tetap 1
$rangeAll = "A1:E{$lastRow}";
$rangeHeader = "A1:E1";

// Freeze header row
$sheet->freezePane('A2');

// AutoFilter
$sheet->setAutoFilter($rangeAll);

// Style header
$sheet->getStyle($rangeHeader)->getFont()->setBold(true);
$sheet->getStyle($rangeHeader)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle($rangeHeader)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle($rangeHeader)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFEFEF');

// Alignment kolom tertentu
$sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Wrap text untuk nama (biar kalau panjang tetap rapi)
$sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);

// Border semua sel
$sheet->getStyle($rangeAll)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Tinggi header sedikit
$sheet->getRowDimension(1)->setRowHeight(20);

// Lebar kolom (autoSize)
foreach (range('A','E') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* ====== Output ====== */
$filename = 'peserta_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;