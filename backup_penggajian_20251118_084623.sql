-- ----------------------------------------
-- Struktur tabel: `dokumen_gaji`
-- ----------------------------------------

DROP TABLE IF EXISTS `dokumen_gaji`;
CREATE TABLE `dokumen_gaji` (
  `id_dokumen` int NOT NULL AUTO_INCREMENT,
  `id_penggajian` int NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `tanggal_generate` date NOT NULL,
  PRIMARY KEY (`id_dokumen`),
  KEY `id_penggajian` (`id_penggajian`),
  CONSTRAINT `dokumen_gaji_ibfk_1` FOREIGN KEY (`id_penggajian`) REFERENCES `penggajian` (`id_penggajian`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data untuk tabel: `dokumen_gaji`



-- ----------------------------------------
-- Struktur tabel: `karyawan`
-- ----------------------------------------

DROP TABLE IF EXISTS `karyawan`;
CREATE TABLE `karyawan` (
  `id_karyawan` int NOT NULL AUTO_INCREMENT,
  `nama_karyawan` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `posisi` varchar(50) NOT NULL,
  `gaji_pokok` int NOT NULL COMMENT 'gaji dasar per bulan',
  PRIMARY KEY (`id_karyawan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data untuk tabel: `karyawan`



-- ----------------------------------------
-- Struktur tabel: `laporan_service`
-- ----------------------------------------

DROP TABLE IF EXISTS `laporan_service`;
CREATE TABLE `laporan_service` (
  `id_laporan` int NOT NULL AUTO_INCREMENT,
  `id_karyawan` int NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah_service` int NOT NULL,
  `catatan` text NOT NULL,
  PRIMARY KEY (`id_laporan`),
  KEY `id_karyawan` (`id_karyawan`),
  CONSTRAINT `laporan_service_ibfk_1` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data untuk tabel: `laporan_service`



-- ----------------------------------------
-- Struktur tabel: `penggajian`
-- ----------------------------------------

DROP TABLE IF EXISTS `penggajian`;
CREATE TABLE `penggajian` (
  `id_penggajian` int NOT NULL AUTO_INCREMENT,
  `id_karyawan` int NOT NULL,
  `bulan` tinyint NOT NULL,
  `tahun` int NOT NULL,
  `total_service` int NOT NULL,
  `gaji_pokok` int NOT NULL,
  `bonus_per_service` int NOT NULL,
  `total_bonus` int NOT NULL,
  `total_gaji` int NOT NULL,
  `tanggal_proses` date NOT NULL,
  `status_pembayaran` enum('belum','lunas') NOT NULL DEFAULT 'belum',
  `tanggal_pembayaran` date DEFAULT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `keterangan_pembayaran` text,
  PRIMARY KEY (`id_penggajian`),
  KEY `fk_penggajian_karyawan` (`id_karyawan`),
  CONSTRAINT `fk_penggajian_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data untuk tabel: `penggajian`



-- ----------------------------------------
-- Struktur tabel: `user`
-- ----------------------------------------

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id_user` int NOT NULL AUTO_INCREMENT COMMENT 'ID User',
  `username` varchar(50) NOT NULL COMMENT 'Username Login',
  `password` varchar(255) NOT NULL COMMENT 'Password (tersimpan di hash)',
  `jabatan` enum('owner','karyawan','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'tipe user',
  `nama_lengkap` varchar(100) NOT NULL COMMENT 'opsional',
  `alamat` text,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data untuk tabel: `user`

INSERT INTO `user` VALUES('2','gosyen.tp','$2y$10$uF8N0lM/r690qirsYbjofeOlQWjrMEQWPO7eYbLs8sLxTPNDC7PKK','owner','Gosyen Mattew Tampubolon','Jalan Garuda Sakti KM 3','Air Molek','2006-06-17','user_2_1763448861.png');


