<?php
require_once '../includes/auth.php';
cekAkses(['petugas', 'admin']);

$active = (int) $pdo->query("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'masuk'")->fetchColumn();
$todayIn = (int) $pdo->query('SELECT COUNT(*) FROM tb_transaksi WHERE DATE(waktu_masuk) = CURDATE()')->fetchColumn();
$todayOut = (int) $pdo->query("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()")->fetchColumn();
$todayRevenue = (float) $pdo->query("SELECT COALESCE(SUM(biaya_total), 0) FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()")->fetchColumn();
$recent = $pdo->query("SELECT * FROM v_rekap_transaksi WHERE status = 'masuk' ORDER BY waktu_masuk ASC LIMIT 7")->fetchAll();
$parkingLayout = parkingAreasLayout($pdo);
$weekly = weeklyParkingStats($pdo);
$chart_rows = $weekly;
$chart_datasets = [
    ['label' => 'Kendaraan masuk', 'color' => '#4169ed', 'values' => array_column($weekly, 'masuk')],
    ['label' => 'Kendaraan keluar', 'color' => '#16a574', 'values' => array_column($weekly, 'keluar')],
];
$chart_id = 'staff-activity';
$chart_title = 'Aktivitas parkir mingguan';
$chart_subtitle = 'Pantau jumlah kendaraan yang masuk dan keluar setiap hari.';
$page_title = 'Dashboard';
$page_subtitle = 'Ringkasan operasional dan kendaraan yang sedang berada di area.';
$page_class = 'dashboard-page dashboard-staff';
include '../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Sedang parkir</span><span class="stat-icon blue"><i class="bi bi-p-square"></i></span></div><div class="stat-value"><?= $active ?></div><div class="stat-note">Kendaraan aktif di area</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Masuk hari ini</span><span class="stat-icon green"><i class="bi bi-box-arrow-in-down-left"></i></span></div><div class="stat-value"><?= $todayIn ?></div><div class="stat-note">Tiket parkir diterbitkan</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Keluar hari ini</span><span class="stat-icon orange"><i class="bi bi-box-arrow-up-right"></i></span></div><div class="stat-value"><?= $todayOut ?></div><div class="stat-note">Transaksi selesai hari ini</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Pendapatan hari ini</span><span class="stat-icon purple"><i class="bi bi-wallet2"></i></span></div><div class="stat-value" style="font-size:20px"><?= e(rupiah($todayRevenue)) ?></div><div class="stat-note">Dari transaksi selesai</div></div></div>
</div>
<div class="row g-3 mb-4"><div class="col-12"><?php include '../includes/chart.php'; ?></div></div>
<?php include '../includes/parking-map.php'; ?>
<div class="row g-3 mb-4">
    <div class="col-md-6"><a class="quick-action" href="kendaraan_masuk.php"><span class="quick-action-icon"><i class="bi bi-plus-circle"></i></span><div><strong>Catat kendaraan masuk</strong><span>Buat tiket baru dan pilih area parkir</span></div><i class="bi bi-arrow-up-right ms-auto text-muted"></i></a></div>
    <div class="col-md-6"><a class="quick-action" href="kendaraan_keluar.php"><span class="quick-action-icon" style="background:#e9f8f2;color:#16a574"><i class="bi bi-cash-coin"></i></span><div><strong>Proses kendaraan keluar</strong><span>Hitung biaya dan selesaikan pembayaran</span></div><i class="bi bi-arrow-up-right ms-auto text-muted"></i></a></div>
</div>
<section class="card"><div class="card-body p-0"><div class="section-heading px-4 pt-4"><div><h2>Kendaraan sedang parkir</h2><p>Urutan kendaraan berdasarkan waktu masuk.</p></div><a href="transaksi.php" class="btn btn-sm btn-outline-primary">Semua transaksi</a></div>
    <div class="table-responsive"><table class="table"><thead><tr><th>Transaksi</th><th>Plat nomor</th><th>Jenis</th><th>Area</th><th>Waktu masuk</th><th>Petugas</th></tr></thead><tbody>
        <?php foreach ($recent as $row): ?><tr><td>#<?= (int) $row['id_parkir'] ?></td><td><span class="plate-number"><?= e($row['plat_nomor']) ?></span></td><td><?= e(ucfirst($row['jenis_kendaraan'])) ?></td><td><?= e($row['nama_area']) ?></td><td><?= e(formatTanggalIndo($row['waktu_masuk'])) ?></td><td><?= e($row['nama_petugas'] ?? 'Pengguna dihapus') ?></td></tr><?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="table-empty">Belum ada kendaraan di area parkir saat ini.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></section>
<?php include '../includes/footer.php'; ?>
