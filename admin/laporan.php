<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

$start = (string) ($_GET['tgl_mulai'] ?? date('Y-m-01'));
$end = (string) ($_GET['tgl_selesai'] ?? date('Y-m-d'));
$type = (string) ($_GET['jenis'] ?? '');
$status = (string) ($_GET['status'] ?? '');
$validDate = static function (string $value): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$errors = [];
if (!$validDate($start) || !$validDate($end)) {
    $errors[] = 'Pilih tanggal laporan yang valid.';
}
if ($validDate($start) && $validDate($end) && $start > $end) {
    $errors[] = 'Tanggal mulai tidak boleh melewati tanggal selesai.';
}
if ($type !== '' && !in_array($type, ['motor', 'mobil', 'bus', 'truk', 'lainnya'], true)) {
    $errors[] = 'Jenis kendaraan tidak valid.';
}
if ($status !== '' && !in_array($status, ['masuk', 'keluar'], true)) {
    $errors[] = 'Status transaksi tidak valid.';
}

$transactions = [];
$summary = ['jumlah' => 0, 'selesai' => 0, 'pendapatan' => 0];
if (!$errors) {
    $sql = 'SELECT * FROM v_rekap_transaksi WHERE DATE(waktu_masuk) BETWEEN ? AND ?';
    $params = [$start, $end];
    if ($type !== '') {
        $sql .= ' AND jenis_kendaraan = ?';
        $params[] = $type;
    }
    if ($status !== '') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }

    $summaryStmt = $pdo->prepare('SELECT COUNT(*) AS jumlah, COALESCE(SUM(status = "keluar"), 0) AS selesai, COALESCE(SUM(CASE WHEN status = "keluar" THEN biaya_total ELSE 0 END), 0) AS pendapatan FROM (' . $sql . ') AS filtered_transactions');
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();
    $sql .= ' ORDER BY waktu_masuk DESC';

    if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'], true)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exportRows = $stmt->fetchAll();
        define('PARKING_REPORT_EXPORT', true);
        if ($_GET['export'] === 'pdf') {
            $page_title = 'Laporan Parkir';
            include '../includes/report-pdf.php';
            exit;
        }
        include '../includes/report-excel.php';
    }

    $stmt = $pdo->prepare($sql . ' LIMIT 500');
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
}

$page_title = 'Laporan parkir';
$page_subtitle = 'Tinjau dan ekspor transaksi parkir berdasarkan periode.';
include '../includes/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="get" class="filter-bar mb-4 row g-3 align-items-end">
    <div class="col-sm-6 col-lg-3"><label class="form-label" for="from">Tanggal mulai</label><input class="form-control" type="date" id="from" name="tgl_mulai" value="<?= e($start) ?>" required></div>
    <div class="col-sm-6 col-lg-3"><label class="form-label" for="to">Tanggal selesai</label><input class="form-control" type="date" id="to" name="tgl_selesai" value="<?= e($end) ?>" required></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label" for="type">Jenis kendaraan</label><select class="form-select" id="type" name="jenis"><option value="">Semua jenis</option><option value="motor" <?= $type === 'motor' ? 'selected' : '' ?>>Motor</option><option value="mobil" <?= $type === 'mobil' ? 'selected' : '' ?>>Mobil</option><option value="bus" <?= $type === 'bus' ? 'selected' : '' ?>>Bus</option><option value="truk" <?= $type === 'truk' ? 'selected' : '' ?>>Truk</option><option value="lainnya" <?= $type === 'lainnya' ? 'selected' : '' ?>>Lainnya</option></select></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Semua status</option><option value="masuk" <?= $status === 'masuk' ? 'selected' : '' ?>>Parkir</option><option value="keluar" <?= $status === 'keluar' ? 'selected' : '' ?>>Selesai</option></select></div>
    <div class="col-lg-2 d-grid"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan filter</button></div>
</form>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Total transaksi</span><span class="stat-icon blue"><i class="bi bi-receipt"></i></span></div><div class="stat-value"><?= (int) $summary['jumlah'] ?></div><div class="stat-note">Semua hasil filter periode</div></div></div>
    <div class="col-6 col-lg-4"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Transaksi selesai</span><span class="stat-icon orange"><i class="bi bi-check-circle"></i></span></div><div class="stat-value"><?= (int) $summary['selesai'] ?></div><div class="stat-note">Kendaraan yang telah keluar</div></div></div>
    <div class="col-12 col-lg-4"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Total pendapatan</span><span class="stat-icon green"><i class="bi bi-wallet2"></i></span></div><div class="stat-value" style="font-size:20px"><?= e(rupiah($summary['pendapatan'])) ?></div><div class="stat-note">Dari transaksi selesai</div></div></div>
</div>
<section class="card">
    <div class="card-body p-0">
        <div class="section-heading px-4 pt-4">
            <div><h2>Rincian transaksi</h2><p><?= e(formatTanggalIndo($start)) ?> — <?= e(formatTanggalIndo($end)) ?> · maksimal 500 baris pada tampilan, ekspor memuat semua hasil.</p></div>
            <div class="d-flex flex-wrap gap-2 no-print">
                <a class="btn btn-sm btn-outline-primary" href="?<?= e(http_build_query(['tgl_mulai' => $start, 'tgl_selesai' => $end, 'jenis' => $type, 'status' => $status, 'export' => 'excel'])) ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Unduh Excel</a>
                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="?<?= e(http_build_query(['tgl_mulai' => $start, 'tgl_selesai' => $end, 'jenis' => $type, 'status' => $status, 'export' => 'pdf'])) ?>"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak / PDF</a>
            </div>
        </div>
        <div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Kendaraan</th><th>Area</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Pendapatan</th><th>Status</th><th>Petugas</th></tr></thead><tbody>
            <?php foreach ($transactions as $transaction): ?><tr><td>#<?= (int) $transaction['id_parkir'] ?></td><td><span class="plate-number"><?= e($transaction['plat_nomor']) ?></span><div class="text-muted mt-1" style="font-size:9px"><?= e(ucfirst($transaction['jenis_kendaraan'])) ?></div></td><td><?= e($transaction['nama_area']) ?></td><td><?= e(formatTanggalIndo($transaction['waktu_masuk'])) ?></td><td><?= e(formatTanggalIndo($transaction['waktu_keluar'])) ?></td><td><?= $transaction['durasi_jam'] ? (int) $transaction['durasi_jam'] . ' jam' : '-' ?></td><td><?= $transaction['biaya_total'] !== null ? e(rupiah($transaction['biaya_total'])) : '-' ?></td><td><span class="badge <?= $transaction['status'] === 'masuk' ? 'badge-soft-warning' : 'badge-soft-success' ?>"><?= $transaction['status'] === 'masuk' ? 'Parkir' : 'Selesai' ?></span></td><td><?= e($transaction['nama_petugas'] ?? 'Pengguna dihapus') ?></td></tr><?php endforeach; ?>
            <?php if (!$transactions): ?><tr><td colspan="9" class="table-empty"><?= $errors ? 'Perbaiki filter tanggal untuk melihat laporan.' : 'Tidak ada transaksi pada periode terpilih.' ?></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</section>
<?php include '../includes/footer.php'; ?>
