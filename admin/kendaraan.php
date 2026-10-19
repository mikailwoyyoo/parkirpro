<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

$search = trim((string) ($_GET['search'] ?? ''));
$sql = 'SELECT k.*, u.nama_lengkap, (SELECT t.status FROM tb_transaksi t WHERE t.id_kendaraan = k.id_kendaraan ORDER BY t.id_parkir DESC LIMIT 1) AS status_terakhir FROM tb_kendaraan k LEFT JOIN tb_user u ON k.id_user = u.id_user';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE k.plat_nomor LIKE ? OR k.pemilik LIKE ?';
    $params = ['%' . $search . '%', '%' . $search . '%'];
}
$sql .= ' ORDER BY k.dibuat_pada DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();
$page_title = 'Data kendaraan';
$page_subtitle = 'Daftar kendaraan yang pernah tercatat dalam sistem.';
include '../includes/header.php';
?>
<section class="card">
    <div class="card-body p-0">
        <div class="section-heading px-4 pt-4"><div><h2>Kendaraan terdaftar</h2><p>Menampilkan maksimal 200 data terbaru.</p></div><form method="get" class="d-flex gap-2"><input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Cari plat atau pemilik" aria-label="Cari plat atau pemilik"><button class="btn btn-primary"><i class="bi bi-search"></i></button></form></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Plat nomor</th><th>Kendaraan</th><th>Pemilik</th><th>Warna</th><th>Status terakhir</th><th>Dicatat oleh</th></tr></thead><tbody>
            <?php foreach ($vehicles as $vehicle): ?><tr><td><span class="plate-number"><?= e($vehicle['plat_nomor']) ?></span></td><td><?= e(ucfirst($vehicle['jenis_kendaraan'])) ?></td><td><?= e($vehicle['pemilik']) ?></td><td><?= e($vehicle['warna']) ?></td><td><?php if ($vehicle['status_terakhir']): ?><span class="badge <?= $vehicle['status_terakhir'] === 'masuk' ? 'badge-soft-warning' : 'badge-soft-muted' ?>"><?= $vehicle['status_terakhir'] === 'masuk' ? 'Sedang parkir' : 'Sudah keluar' ?></span><?php else: ?><span class="badge badge-soft-muted">Belum transaksi</span><?php endif; ?></td><td><?= e($vehicle['nama_lengkap'] ?? 'Pengguna dihapus') ?></td></tr><?php endforeach; ?>
            <?php if (!$vehicles): ?><tr><td colspan="6" class="table-empty"><?= $search !== '' ? 'Data kendaraan tidak ditemukan.' : 'Belum ada kendaraan yang tercatat.' ?></td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</section>
<?php include '../includes/footer.php'; ?>
