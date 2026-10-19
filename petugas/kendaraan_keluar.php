<?php
require_once '../includes/auth.php';
cekAkses(['petugas', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_keluar'])) {
    verifyCsrfToken();
    $parkingId = filter_var($_POST['id_parkir'] ?? null, FILTER_VALIDATE_INT);
    $submittedBarcode = trim((string) ($_POST['barcode_token'] ?? ''));
    try {
        if (!$parkingId) {
            throw new InvalidArgumentException('ID transaksi tidak valid.');
        }
        $pdo->beginTransaction();
        $areaLookup = $pdo->prepare('SELECT id_area FROM tb_transaksi WHERE id_parkir = ?');
        $areaLookup->execute([$parkingId]);
        $areaId = $areaLookup->fetchColumn();
        if (!$areaId) {
            throw new InvalidArgumentException('Transaksi tidak ditemukan.');
        }

        $areaLock = $pdo->prepare('SELECT id_area FROM tb_area_parkir WHERE id_area = ? FOR UPDATE');
        $areaLock->execute([(int) $areaId]);
        if (!$areaLock->fetch()) {
            throw new RuntimeException('Area transaksi tidak ditemukan. Hubungi administrator.');
        }

        $stmt = $pdo->prepare("SELECT t.id_parkir, t.id_area, t.waktu_masuk, t.tarif_per_jam, t.status, t.barcode_token, k.plat_nomor FROM tb_transaksi t JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan WHERE t.id_parkir = ? FOR UPDATE");
        $stmt->execute([$parkingId]);
        $transaction = $stmt->fetch();
        if (!$transaction || $transaction['status'] !== 'masuk') {
            throw new InvalidArgumentException('Transaksi tidak aktif atau sudah diproses sebelumnya.');
        }
        if ($submittedBarcode === '' || !hash_equals($transaction['barcode_token'], $submittedBarcode)) {
            throw new InvalidArgumentException('Barcode tiket tidak cocok. Scan tiket kendaraan yang benar.');
        }

        $durationStmt = $pdo->prepare('SELECT GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, ?, NOW()) / 3600))');
        $durationStmt->execute([$transaction['waktu_masuk']]);
        $hours = (int) $durationStmt->fetchColumn();
        $total = $hours * (float) $transaction['tarif_per_jam'];

        $update = $pdo->prepare("UPDATE tb_transaksi SET waktu_keluar = NOW(), durasi_jam = ?, biaya_total = ?, status = 'keluar' WHERE id_parkir = ? AND status = 'masuk'");
        $update->execute([$hours, $total, $parkingId]);
        if ($update->rowCount() !== 1) {
            throw new InvalidArgumentException('Transaksi sudah diproses petugas lain.');
        }
        $occupiedStmt = $pdo->prepare("SELECT id_parkir FROM tb_transaksi WHERE id_area = ? AND status = 'masuk' FOR UPDATE");
        $occupiedStmt->execute([(int) $transaction['id_area']]);
        $occupied = count($occupiedStmt->fetchAll());
        $pdo->prepare('UPDATE tb_area_parkir SET terisi = ? WHERE id_area = ?')->execute([$occupied, (int) $transaction['id_area']]);
        catatLog($pdo, (int) $_SESSION['user_id'], "Kendaraan {$transaction['plat_nomor']} keluar, transaksi #{$parkingId}");
        $pdo->commit();

        header('Location: cetak_struk.php?id=' . $parkingId);
        exit;
    } catch (InvalidArgumentException | RuntimeException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlashMessage('error', $exception->getMessage());
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$activeTransactions = [];
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT t.id_parkir, t.barcode_token, t.waktu_masuk, t.tarif_per_jam, k.plat_nomor, k.jenis_kendaraan, k.pemilik, a.nama_area FROM tb_transaksi t JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan JOIN tb_area_parkir a ON a.id_area = t.id_area WHERE t.status = 'masuk' AND (t.barcode_token = ? OR k.plat_nomor LIKE ? OR t.id_parkir = ?) ORDER BY t.waktu_masuk ASC LIMIT 30");
    $stmt->execute([$search, '%' . $search . '%', ctype_digit($search) ? (int) $search : 0]);
    $activeTransactions = $stmt->fetchAll();
}
$page_title = 'Kendaraan keluar';
$page_subtitle = 'Cari transaksi aktif, hitung biaya, dan selesaikan pembayaran.';
include '../includes/header.php';
?>
<?php tampilkanFlashMessage(); ?>
<section class="card mb-4"><div class="card-body p-4"><div class="section-heading"><div><h2>Cari kendaraan aktif</h2><p>Scan barcode tiket dengan kamera, atau cari menggunakan plat nomor.</p></div><span class="stat-icon orange"><i class="bi bi-search"></i></span></div>
    <form method="get" class="row g-2 align-items-center" data-barcode-search-form><div class="col"><input class="form-control" id="transaction-search" name="search" value="<?= e($search) ?>" placeholder="Scan barcode atau ketik plat nomor" data-barcode-search required></div><div class="col-auto"><button class="btn btn-primary h-100"><i class="bi bi-search me-1"></i>Cari</button></div><div class="col-auto"><button class="btn btn-outline-primary h-100" type="button" data-bs-toggle="modal" data-bs-target="#barcodeScanner"><i class="bi bi-camera me-1"></i>Scan kamera</button></div><?php if ($search !== ''): ?><div class="col-auto"><a href="kendaraan_keluar.php" class="btn btn-light h-100">Reset</a></div><?php endif; ?></form>
</div></section>
<div class="modal fade" id="barcodeScanner" tabindex="-1" aria-labelledby="barcodeScannerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content scanner-modal">
        <div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold" id="barcodeScannerTitle">Scan barcode tiket</h2><p class="card-subtitle mb-0">Izinkan akses kamera lalu arahkan ke barcode Code 128 pada tiket.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup scanner"></button></div>
        <div class="modal-body">
            <div class="scanner-frame"><video id="barcode-video" playsinline muted></video><div class="scanner-target"><span></span></div></div>
            <div class="scanner-status" data-scanner-status role="status">Menunggu akses kamera...</div>
            <div class="scanner-actions"><button type="button" class="btn btn-sm btn-light" data-camera-switch><i class="bi bi-arrow-repeat me-1"></i>Ganti kamera</button><button type="button" class="btn btn-sm btn-outline-danger" data-camera-stop><i class="bi bi-stop-circle me-1"></i>Hentikan kamera</button></div>
        </div>
    </div></div>
</div>
<?php if ($search !== ''): ?>
<section class="card"><div class="card-body p-0"><div class="section-heading px-4 pt-4"><div><h2>Hasil pencarian</h2><p><?= count($activeTransactions) ?> transaksi aktif ditemukan.</p></div></div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Transaksi</th><th>Kendaraan</th><th>Pemilik</th><th>Area</th><th>Waktu masuk</th><th>Tarif/jam</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($activeTransactions as $transaction): ?><tr><td>#<?= (int) $transaction['id_parkir'] ?></td><td><span class="plate-number"><?= e($transaction['plat_nomor']) ?></span><div class="text-muted mt-1" style="font-size:9px"><?= e(ucfirst($transaction['jenis_kendaraan'])) ?></div></td><td><?= e($transaction['pemilik']) ?></td><td><?= e($transaction['nama_area']) ?></td><td><?= e(formatTanggalIndo($transaction['waktu_masuk'])) ?></td><td><?= e(rupiah($transaction['tarif_per_jam'])) ?></td><td><form method="post" data-confirm="Proses kendaraan keluar dan hitung biaya parkir sekarang?"><?= csrfField() ?><input type="hidden" name="id_parkir" value="<?= (int) $transaction['id_parkir'] ?>"><input type="hidden" name="barcode_token" value="<?= e($transaction['barcode_token']) ?>"><button class="btn btn-sm btn-primary" name="proses_keluar" value="1"><i class="bi bi-check2-circle me-1"></i>Proses keluar</button></form></td></tr><?php endforeach; ?>
    <?php if (!$activeTransactions): ?><tr><td colspan="7" class="table-empty">Tidak ada kendaraan aktif dengan kata kunci tersebut.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></section>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/umd/zxing-browser.min.js"></script>
<?php include '../includes/footer.php'; ?>
