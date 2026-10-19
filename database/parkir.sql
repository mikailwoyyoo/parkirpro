CREATE DATABASE IF NOT EXISTS parkir CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parkir;

SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS tb_user (
    id_user INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_lengkap VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'owner') NOT NULL DEFAULT 'petugas',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user),
    UNIQUE KEY uq_user_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_area_parkir (
    id_area INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_area VARCHAR(100) NOT NULL,
    kapasitas INT UNSIGNED NOT NULL DEFAULT 0,
    terisi INT UNSIGNED NOT NULL DEFAULT 0,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_area),
    UNIQUE KEY uq_area_nama (nama_area)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_tarif (
    id_tarif INT UNSIGNED NOT NULL AUTO_INCREMENT,
    jenis_kendaraan ENUM('motor', 'mobil', 'lainnya') NOT NULL,
    tarif_per_jam DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
    diperbarui_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_tarif),
    UNIQUE KEY uq_tarif_jenis (jenis_kendaraan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_slot_parkir (
    id_slot INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_area INT UNSIGNED NOT NULL,
    kode_slot VARCHAR(32) NOT NULL,
    PRIMARY KEY (id_slot),
    UNIQUE KEY uq_slot_area_kode (id_area, kode_slot),
    CONSTRAINT fk_slot_area FOREIGN KEY (id_area) REFERENCES tb_area_parkir (id_area) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_kendaraan (
    id_kendaraan INT UNSIGNED NOT NULL AUTO_INCREMENT,
    plat_nomor VARCHAR(16) NOT NULL,
    jenis_kendaraan ENUM('motor', 'mobil', 'bus', 'truk', 'lainnya') NOT NULL,
    warna VARCHAR(40) NOT NULL,
    pemilik VARCHAR(120) NOT NULL,
    id_user INT UNSIGNED NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_kendaraan),
    UNIQUE KEY uq_kendaraan_plat (plat_nomor),
    CONSTRAINT fk_kendaraan_user FOREIGN KEY (id_user) REFERENCES tb_user (id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_transaksi (
    id_parkir BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_kendaraan INT UNSIGNED NOT NULL,
    id_area INT UNSIGNED NOT NULL,
    id_slot INT UNSIGNED NULL,
    id_tarif INT UNSIGNED NULL,
    id_user INT UNSIGNED NULL,
    barcode_token CHAR(32) NOT NULL,
    tarif_per_jam DECIMAL(12,2) UNSIGNED NOT NULL,
    waktu_masuk DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    waktu_keluar DATETIME NULL,
    durasi_jam INT UNSIGNED NULL,
    biaya_total DECIMAL(14,2) UNSIGNED NULL,
    status ENUM('masuk', 'keluar') NOT NULL DEFAULT 'masuk',
    PRIMARY KEY (id_parkir),
    KEY idx_transaksi_status_masuk (status, waktu_masuk),
    KEY idx_transaksi_area_status (id_area, status),
    KEY idx_transaksi_slot_status (id_slot, status),
    KEY idx_transaksi_keluar (waktu_keluar),
    UNIQUE KEY uq_transaksi_barcode (barcode_token),
    CONSTRAINT fk_transaksi_kendaraan FOREIGN KEY (id_kendaraan) REFERENCES tb_kendaraan (id_kendaraan),
    CONSTRAINT fk_transaksi_area FOREIGN KEY (id_area) REFERENCES tb_area_parkir (id_area),
    CONSTRAINT fk_transaksi_slot FOREIGN KEY (id_slot) REFERENCES tb_slot_parkir (id_slot) ON DELETE SET NULL,
    CONSTRAINT fk_transaksi_tarif FOREIGN KEY (id_tarif) REFERENCES tb_tarif (id_tarif) ON DELETE SET NULL,
    CONSTRAINT fk_transaksi_user FOREIGN KEY (id_user) REFERENCES tb_user (id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tb_log_aktivitas (
    id_log BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_user INT UNSIGNED NULL,
    aktivitas VARCHAR(255) NOT NULL,
    waktu_aktivitas TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    KEY idx_log_waktu (waktu_aktivitas),
    CONSTRAINT fk_log_user FOREIGN KEY (id_user) REFERENCES tb_user (id_user) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW v_rekap_transaksi AS
SELECT
    t.id_parkir,
    k.plat_nomor,
    k.jenis_kendaraan,
    k.warna,
    k.pemilik,
    a.nama_area,
    t.waktu_masuk,
    t.waktu_keluar,
    t.durasi_jam,
    t.tarif_per_jam,
    t.biaya_total,
    t.status,
    u.nama_lengkap AS nama_petugas
FROM tb_transaksi t
JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
JOIN tb_area_parkir a ON a.id_area = t.id_area
LEFT JOIN tb_user u ON u.id_user = t.id_user;

INSERT IGNORE INTO tb_user (nama_lengkap, username, password, role)
VALUES
('Administrator', 'admin', '$2y$10$7nZUS9lggJrW4Jt0Kd/2FeTNuY2BguMrB8vXNa8McLBMvBoxeYA5K', 'admin'),
('Petugas Parkir', 'petugas', '$2y$10$EhuAviVeRSV.GTs2c3PcjORrPFUep6Ls.B1eVMYX92nJG/HKMYytS', 'petugas'),
('Owner', 'owner', '$2y$10$N.xceBeIu9T9cDp/eh/zneU.dKUjv/4fOc4N2ILWUCmjVw2vQOOsC', 'owner');

INSERT IGNORE INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES
('motor', 3000),
('mobil', 5000),
('lainnya', 7000);

INSERT IGNORE INTO tb_area_parkir (nama_area, kapasitas) VALUES
('Area A - Motor', 80),
('Area B - Mobil', 40),
('Area C - Umum', 20);

INSERT IGNORE INTO tb_slot_parkir (id_area, kode_slot)
SELECT a.id_area, CONCAT('A', a.id_area, '-', LPAD(n.nomor, 3, '0'))
FROM tb_area_parkir a
CROSS JOIN (
    SELECT satu.n + puluh.n * 10 + ratus.n * 100 + 1 AS nomor
    FROM
        (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) satu
    CROSS JOIN
        (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) puluh
    CROSS JOIN
        (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ratus
) n
WHERE n.nomor <= a.kapasitas;
