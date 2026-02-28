/* =========================================================
   PSAJ - FINAL INSTALLER SQL
   - users (admin)
   - peserta
   - app_settings (key/value)
   - school_profile (kop/logo/kepsek)
   ========================================================= */

SET NAMES utf8mb4;
SET time_zone = "+00:00";

/* -----------------------------
   TABLE: users
------------------------------ */
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* default admin (username: admin, password: admin123)
   Hash ini adalah bcrypt untuk "admin123" */
INSERT INTO users (username, password)
VALUES ('admin', '$2y$10$uIY3JbU1I8rJY5u5A9y0veq5xXoVvYb9s1J0nqJ1PqW6f6y1l5e3K')
ON DUPLICATE KEY UPDATE password = VALUES(password);

/* -----------------------------
   TABLE: peserta
------------------------------ */
CREATE TABLE IF NOT EXISTS peserta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor_peserta VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(200) NOT NULL,
  kelas VARCHAR(100) NOT NULL,
  ruang VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_kelas (kelas),
  INDEX idx_ruang (ruang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* -----------------------------
   TABLE: app_settings
   (PAKAI key/value sesuai script Anda)
------------------------------ */
CREATE TABLE IF NOT EXISTS app_settings (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* default settings */
INSERT INTO app_settings (`key`,`value`) VALUES
('kode_kabupaten', '30'),
('kode_sekolah', '0104'),
('tahun_pelajaran', '2025 - 2026')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

/* -----------------------------
   TABLE: school_profile
   (1 baris, id=1)
------------------------------ */
CREATE TABLE IF NOT EXISTS school_profile (
  id INT PRIMARY KEY,
  line1 VARCHAR(150) NOT NULL,
  line2 VARCHAR(150) NOT NULL,
  line3 VARCHAR(150) NOT NULL,
  line4 VARCHAR(200) NOT NULL,
  line5 VARCHAR(200) NOT NULL,
  line6 VARCHAR(150) NOT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  logo_width_mm INT DEFAULT 16,
  kepala_sekolah VARCHAR(150) NOT NULL,
  nip_kepala VARCHAR(50) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO school_profile (
  id, line1, line2, line3, line4, line5, line6,
  logo, logo_width_mm, kepala_sekolah, nip_kepala
) VALUES (
  1,
  'PEMERINTAH PROVINSI JAWA TIMUR',
  'DINAS PENDIDIKAN',
  'SMK NEGERI 2 LUMAJANG',
  'Jalan Gajah Mada No.- Telp./Fax. (0334) 881925',
  'Email: smkn_02lmj@yahoo.co.id | http://www.smkn2lumajang.sch.id',
  'LUMAJANG',
  NULL,
  16,
  'Muhammad Saiful Bahri, S.Ag, M.Pd.I',
  '197401052003121004'
)
ON DUPLICATE KEY UPDATE
  line1=VALUES(line1),
  line2=VALUES(line2),
  line3=VALUES(line3),
  line4=VALUES(line4),
  line5=VALUES(line5),
  line6=VALUES(line6),
  logo=VALUES(logo),
  logo_width_mm=VALUES(logo_width_mm),
  kepala_sekolah=VALUES(kepala_sekolah),
  nip_kepala=VALUES(nip_kepala);