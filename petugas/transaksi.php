<?php
require_once '../includes/auth.php';
cekAkses(['petugas', 'admin']);

$search = trim((string) ($_GET['search'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$sql = 'SELECT * FROM v_rekap_transaksi WHERE 1 = 1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (plat_nomor LIKE ? OR CAST(id_parkir AS CHAR) = ?)';
    $params[] = '%' . $search . '%';
    $params[] = $search;
}
if (in_array($status, ['masuk', 'keluar'], true)) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY waktu_masuk DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
$page_title = 'Riwayat transaksi';
$page_subtitle = 'Telusuri tiket masuk dan transaksi parkir yang telah selesai.';
include '../includes/header.php';
?>
<section class="card"><div class="card-body p-0">
    <div class="section-heading px-4 pt-4"><div><h2>Semua transaksi</h2><p>Menampilkan maksimal 200 transaksi terbaru.</p></div></div>
    <form method="get" class="filter-bar mx-4 mb-3 row g-2 align-items-end">
        <div class="col-md"><label class="form-label" for="search">Plat nomor atau ID</label><input class="form-control" id="search" name="search" value="<?= e($search) ?>" placeholder="Cari kendaraan"></div>
        <div class="col-md-3"><label class="form-label" for="status">Status transaksi</label><select class="form-select" id="status" name="status"><option value="">Semua status</option><option value="masuk" <?= $status === 'masuk' ? 'selected' : '' ?>>Sedang parkir</option><option value="keluar" <?= $status === 'keluar' ? 'selected' : '' ?>>Selesai</option></select></div>
        <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        <?php if ($search !== '' || $status !== ''): ?><div class="col-auto"><a class="btn btn-light" href="transaksi.php">Reset</a></div><?php endif; ?>
    </form>
    <div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Kendaraan</th><th>Area</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($transactions as $transaction): ?><tr>
            <td>#<?= (int) $transaction['id_parkir'] ?></td><td><span class="plate-number"><?= e($transaction['plat_nomor']) ?></span><div class="text-muted mt-1" style="font-size:9px"><?= e(ucfirst($transaction['jenis_kendaraan'])) ?></div></td>
            <td><?= e($transaction['nama_area']) ?></td><td><?= e(formatTanggalIndo($transaction['waktu_masuk'])) ?></td><td><?= e(formatTanggalIndo($transaction['waktu_keluar'])) ?></td><td><?= $transaction['durasi_jam'] ? (int) $transaction['durasi_jam'] . ' jam' : '-' ?></td>
            <td><?= $transaction['biaya_total'] !== null ? e(rupiah($transaction['biaya_total'])) : '-' ?></td><td><span class="badge <?= $transaction['status'] === 'masuk' ? 'badge-soft-warning' : 'badge-soft-success' ?>"><?= $transaction['status'] === 'masuk' ? 'Parkir' : 'Selesai' ?></span></td>
            <td><a class="btn btn-sm btn-light" href="cetak_struk.php?id=<?= (int) $transaction['id_parkir'] ?>" title="Lihat tiket / struk"><i class="bi bi-receipt"></i></a></td>
        </tr><?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="9" class="table-empty">Transaksi tidak ditemukan.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></section>
<?php include '../includes/footer.php'; ?>
