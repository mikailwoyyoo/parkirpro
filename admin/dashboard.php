<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM tb_user WHERE status_aktif = 1')->fetchColumn();
$totalVehicles = (int) $pdo->query('SELECT COUNT(*) FROM tb_kendaraan')->fetchColumn();
$totalAreas = (int) $pdo->query('SELECT COUNT(*) FROM tb_area_parkir')->fetchColumn();
$activeParking = (int) $pdo->query("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'masuk'")->fetchColumn();
$todayTransactions = (int) $pdo->query('SELECT COUNT(*) FROM tb_transaksi WHERE DATE(waktu_masuk) = CURDATE()')->fetchColumn();
$areas = $pdo->query('SELECT a.*, (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = "masuk") AS kendaraan_aktif FROM tb_area_parkir a ORDER BY a.nama_area')->fetchAll();
$logs = $pdo->query('SELECT l.*, u.nama_lengkap FROM tb_log_aktivitas l LEFT JOIN tb_user u ON l.id_user = u.id_user ORDER BY l.waktu_aktivitas DESC LIMIT 6')->fetchAll();
$weekly = weeklyParkingStats($pdo);
$chart_rows = $weekly;
$chart_datasets = [
    ['label' => 'Kendaraan masuk', 'color' => '#4169ed', 'values' => array_column($weekly, 'masuk')],
    ['label' => 'Kendaraan keluar', 'color' => '#16a574', 'values' => array_column($weekly, 'keluar')],
];
$chart_id = 'admin-activity';
$chart_title = 'Aktivitas kendaraan';
$chart_subtitle = 'Perbandingan kendaraan masuk dan keluar setiap hari.';

$page_title = 'Dashboard';
$page_subtitle = 'Pantau kondisi dan aktivitas operasional area parkir.';
$page_class = 'dashboard-page dashboard-admin';
include '../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Pengguna aktif</span><span class="stat-icon blue"><i class="bi bi-people"></i></span></div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-note">Akun terdaftar di sistem</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Data kendaraan</span><span class="stat-icon green"><i class="bi bi-car-front"></i></span></div><div class="stat-value"><?= $totalVehicles ?></div><div class="stat-note">Kendaraan pernah terdaftar</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Kendaraan parkir</span><span class="stat-icon orange"><i class="bi bi-p-square"></i></span></div><div class="stat-value"><?= $activeParking ?></div><div class="stat-note">Sedang berada di area</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Transaksi hari ini</span><span class="stat-icon purple"><i class="bi bi-receipt"></i></span></div><div class="stat-value"><?= $todayTransactions ?></div><div class="stat-note"><?= $totalAreas ?> area parkir tersedia</div></div></div>
</div>
<div class="row g-3">
    <div class="col-12"><?php include '../includes/chart.php'; ?></div>
    <div class="col-xl-7">
        <section class="card h-100">
            <div class="card-body p-4">
                <div class="section-heading"><div><h2>Kapasitas area</h2><p>Jumlah kendaraan aktif dibandingkan kapasitas.</p></div><a href="area.php" class="btn btn-sm btn-outline-primary">Kelola area</a></div>
                <?php if (!$areas): ?><div class="table-empty">Belum ada area parkir. Tambahkan area untuk mulai beroperasi.</div><?php endif; ?>
                <?php foreach ($areas as $area): $percentage = (int) $area['kapasitas'] > 0 ? min(100, (int) round($area['kendaraan_aktif'] / $area['kapasitas'] * 100)) : 0; ?>
                    <div class="d-flex align-items-center justify-content-between mb-2 mt-4"><strong class="small"><?= e($area['nama_area']) ?></strong><span class="text-muted small"><?= (int) $area['kendaraan_aktif'] ?> / <?= (int) $area['kapasitas'] ?> kendaraan</span></div>
                    <div class="capacity-track"><span style="width: <?= $percentage ?>%"></span></div>
                    <div class="text-end text-muted mt-1" style="font-size:9px"><?= $percentage ?>% terisi</div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="card h-100">
            <div class="card-body p-4">
                <div class="section-heading"><div><h2>Akses cepat</h2><p>Menu operasional yang sering digunakan.</p></div></div>
                <div class="d-grid gap-2">
                    <a class="quick-action" href="users.php"><span class="quick-action-icon"><i class="bi bi-person-plus"></i></span><div><strong>Kelola pengguna</strong><span>Tambah atau atur akses akun</span></div><i class="bi bi-chevron-right ms-auto text-muted"></i></a>
                    <a class="quick-action" href="tarif.php"><span class="quick-action-icon"><i class="bi bi-cash-coin"></i></span><div><strong>Atur tarif parkir</strong><span>Perbarui harga per jenis kendaraan</span></div><i class="bi bi-chevron-right ms-auto text-muted"></i></a>
                    <a class="quick-action" href="kendaraan.php"><span class="quick-action-icon"><i class="bi bi-car-front"></i></span><div><strong>Lihat kendaraan</strong><span>Periksa data kendaraan terdaftar</span></div><i class="bi bi-chevron-right ms-auto text-muted"></i></a>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12">
        <section class="card">
            <div class="card-body p-0">
                <div class="section-heading px-4 pt-4"><div><h2>Aktivitas terbaru</h2><p>Catatan aktivitas pengguna sistem.</p></div><a href="log_aktivitas.php" class="btn btn-sm btn-outline-primary">Lihat semua</a></div>
                <div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th></tr></thead><tbody>
                    <?php foreach ($logs as $log): ?><tr><td><?= e(formatTanggalIndo($log['waktu_aktivitas'])) ?></td><td><?= e($log['nama_lengkap'] ?? 'Pengguna dihapus') ?></td><td><?= e($log['aktivitas']) ?></td></tr><?php endforeach; ?>
                    <?php if (!$logs): ?><tr><td colspan="3" class="table-empty">Belum ada aktivitas tercatat.</td></tr><?php endif; ?>
                </tbody></table></div>
            </div>
        </section>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
