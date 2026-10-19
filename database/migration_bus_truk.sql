USE parkir;

ALTER TABLE tb_kendaraan
    MODIFY jenis_kendaraan ENUM('motor', 'mobil', 'bus', 'truk', 'lainnya') NOT NULL;
