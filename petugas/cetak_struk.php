<?php
require_once '../includes/auth.php';
cekAkses(['petugas', 'admin']);

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('ID transaksi tidak valid.');
}
$stmt = $pdo->prepare('SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, a.nama_area, s.kode_slot, u.nama_lengkap AS nama_petugas FROM tb_transaksi t JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan JOIN tb_area_parkir a ON a.id_area = t.id_area LEFT JOIN tb_slot_parkir s ON s.id_slot = t.id_slot LEFT JOIN tb_user u ON u.id_user = t.id_user WHERE t.id_parkir = ?');
$stmt->execute([$id]);
$data = $stmt->fetch();
if (!$data) {
    http_response_code(404);
    exit('Data transaksi tidak ditemukan.');
}
$isComplete = $data['status'] === 'keluar';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isComplete ? 'Struk' : 'Tiket' ?> #<?= (int) $data['id_parkir'] ?> · ParkirPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-md-5">
        <div class="receipt">
            <div class="text-center">
                <div class="receipt-logo"><i class="bi bi-p-square-fill"></i> PARKIRPRO</div>
                <div class="text-muted mt-1" style="font-size:9px">SMART PARKING SYSTEM</div>
                <div class="my-3 border-top"></div>
                <strong class="small"><?= $isComplete ? 'STRUK PEMBAYARAN' : 'TIKET PARKIR' ?></strong>
                <div class="text-muted mt-1" style="font-size:10px">Transaksi #<?= (int) $data['id_parkir'] ?></div>
            </div>
            <table class="w-100 receipt-table mt-3">
                <tr><td>Nomor polisi</td><td class="text-end fw-bold"><?= e($data['plat_nomor']) ?></td></tr>
                <tr><td>Jenis kendaraan</td><td class="text-end"><?= e(ucfirst($data['jenis_kendaraan'])) ?></td></tr>
                <tr><td>Pemilik</td><td class="text-end"><?= e($data['pemilik']) ?></td></tr>
                <tr><td>Warna</td><td class="text-end"><?= e($data['warna']) ?></td></tr>
                <tr><td>Area parkir</td><td class="text-end"><?= e($data['nama_area']) ?></td></tr>
                <?php if ($data['kode_slot']): ?><tr><td>Slot</td><td class="text-end fw-bold"><?= e($data['kode_slot']) ?></td></tr><?php endif; ?>
                <tr><td>Waktu masuk</td><td class="text-end"><?= e(formatTanggalIndo($data['waktu_masuk'])) ?></td></tr>
                <?php if ($isComplete): ?>
                    <tr><td>Waktu keluar</td><td class="text-end"><?= e(formatTanggalIndo($data['waktu_keluar'])) ?></td></tr>
                    <tr><td>Durasi</td><td class="text-end"><?= (int) $data['durasi_jam'] ?> jam</td></tr>
                    <tr><td>Tarif per jam</td><td class="text-end"><?= e(rupiah($data['tarif_per_jam'])) ?></td></tr>
                <?php else: ?>
                    <tr><td>Tarif per jam</td><td class="text-end"><?= e(rupiah($data['tarif_per_jam'])) ?></td></tr>
                    <tr><td>Status</td><td class="text-end">Sedang parkir</td></tr>
                <?php endif; ?>
            </table>
            <div class="ticket-barcode text-center mt-3">
                <svg id="ticket-barcode" data-barcode-value="<?= e($data['barcode_token']) ?>" role="img" aria-label="Barcode tiket parkir"></svg>
                <div class="barcode-fallback" data-barcode-fallback role="status"></div>
                <div class="barcode-caption">Barcode tiket · scan saat kendaraan keluar</div>
            </div>
            <?php if ($isComplete): ?><div class="d-flex justify-content-between receipt-total mt-3"><span>TOTAL BAYAR</span><span><?= e(rupiah($data['biaya_total'])) ?></span></div><?php endif; ?>
            <div class="text-center border-top mt-3 pt-3" style="font-size:10px">
                <div>Petugas: <?= e($data['nama_petugas'] ?? 'Pengguna dihapus') ?></div>
                <div class="mt-2 fw-semibold">Terima kasih telah menggunakan layanan kami.</div>
                <?php if (!$isComplete): ?><div class="text-muted mt-1">Simpan tiket ini untuk proses keluar.</div><?php endif; ?>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-2 no-print">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak <?= $isComplete ? 'struk' : 'tiket' ?></button>
            <a class="btn btn-light" href="dashboard.php"><i class="bi bi-arrow-left me-1"></i>Kembali ke dashboard</a>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        (() => {
            const barcode = document.querySelector('#ticket-barcode');
            const fallback = document.querySelector('[data-barcode-fallback]');
            try {
                if (!window.JsBarcode || !barcode) throw new Error('Pustaka barcode tidak tersedia.');
                window.JsBarcode(barcode, barcode.dataset.barcodeValue, {
                    format: 'CODE128',
                    lineColor: '#17233d',
                    width: 1.65,
                    height: 56,
                    margin: 5,
                    displayValue: false,
                });
            } catch (error) {
                if (fallback) fallback.textContent = 'Barcode gagal dibuat. Kode tiket: ' + (barcode?.dataset.barcodeValue ?? '');
                console.error('Barcode tiket gagal dibuat.', error);
            }
        })();
    </script>
</body>
</html>
