USE parkir;

CREATE TABLE IF NOT EXISTS tb_slot_parkir (
    id_slot INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_area INT UNSIGNED NOT NULL,
    kode_slot VARCHAR(32) NOT NULL,
    PRIMARY KEY (id_slot),
    UNIQUE KEY uq_slot_area_kode (id_area, kode_slot),
    CONSTRAINT fk_slot_area FOREIGN KEY (id_area) REFERENCES tb_area_parkir (id_area) ON DELETE CASCADE
) ENGINE=InnoDB;

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

ALTER TABLE tb_transaksi
    ADD COLUMN barcode_token CHAR(32) NULL AFTER id_user,
    ADD COLUMN id_slot INT UNSIGNED NULL AFTER id_area;

UPDATE tb_transaksi
SET barcode_token = MD5(CONCAT(UUID(), RAND(), id_parkir))
WHERE barcode_token IS NULL;

ALTER TABLE tb_transaksi
    MODIFY COLUMN barcode_token CHAR(32) NOT NULL,
    ADD UNIQUE KEY uq_transaksi_barcode (barcode_token),
    ADD KEY idx_transaksi_slot_status (id_slot, status),
    ADD CONSTRAINT fk_transaksi_slot FOREIGN KEY (id_slot) REFERENCES tb_slot_parkir (id_slot) ON DELETE SET NULL;
