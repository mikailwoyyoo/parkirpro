<?php
require_once '../includes/auth.php';
cekAkses(['owner']);

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$stmt = $pdo->prepare("SELECT COALESCE(SUM(biaya_total), 0) FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$monthRevenue = (float) $stmt->fetchColumn();
$todayRevenue = (float) $pdo->query("SELECT COALESCE(SUM(biaya_total), 0) FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()")->fetchColumn();
$arrivalsToday = (int) $pdo->query('SELECT COUNT(*) FROM tb_transaksi WHERE DATE(waktu_masuk) = CURDATE()')->fetchColumn();
$active = (int) $pdo->query("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'masuk'")->fetchColumn();
$latest = $pdo->query("SELECT * FROM v_rekap_transaksi WHERE status = 'keluar' ORDER BY waktu_keluar DESC LIMIT 6")->fetchAll();
$weekly = weeklyParkingStats($pdo);
$chart_rows = $weekly;
$chart_datasets = [
    ['label' => 'Pendapatan', 'color' => '#4169ed', 'values' => array_column($weekly, 'pendapatan')],
];
$chart_currency = true;
$chart_id = 'owner-revenue';
$chart_title = 'Pendapatan harian';
$chart_subtitle = 'Pendapatan transaksi selesai selama tujuh hari terakhir.';
$page_title = 'Dashboard owner';
$page_subtitle = 'Ringkasan pendapatan dan performa operasional parkir.';
$page_class = 'dashboard-page dashboard-owner';
include '../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Pendapatan bulan ini</span><span class="stat-icon green"><i class="bi bi-wallet2"></i></span></div><div class="stat-value" style="font-size:20px"><?= e(rupiah($monthRevenue)) ?></div><div class="stat-note"><?= e(formatBulanTahunIndo(date('Y-m-d'))) ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Pendapatan hari ini</span><span class="stat-icon blue"><i class="bi bi-cash-stack"></i></span></div><div class="stat-value" style="font-size:20px"><?= e(rupiah($todayRevenue)) ?></div><div class="stat-note">Transaksi yang telah selesai</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Kendaraan masuk</span><span class="stat-icon orange"><i class="bi bi-car-front"></i></span></div><div class="stat-value"><?= $arrivalsToday ?></div><div class="stat-note">Total hari ini</div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-top"><span class="stat-label">Sedang parkir</span><span class="stat-icon purple"><i class="bi bi-p-square"></i></span></div><div class="stat-value"><?= $active ?></div><div class="stat-note">Kendaraan aktif saat ini</div></div></div>
</div>
<div class="row g-3 mb-4"><div class="col-12"><?php include '../includes/chart.php'; ?></div></div>
<div class="row g-3">
    <div class="col-xl-8"><section class="card"><div class="card-body p-0"><div class="section-heading px-4 pt-4"><div><h2>Transaksi selesai terbaru</h2><p>Pendapatan terbaru berdasarkan transaksi yang telah dibayar.</p></div><a href="laporan.php" class="btn btn-sm btn-outline-primary">Buka laporan</a></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Plat nomor</th><th>Jenis</th><th>Keluar</th><th>Durasi</th><th>Pendapatan</th></tr></thead><tbody>
            <?php foreach ($latest as $row): ?><tr><td><span class="plate-number"><?= e($row['plat_nomor']) ?></span></td><td><?= e(ucfirst($row['jenis_kendaraan'])) ?></td><td><?= e(formatTanggalIndo($row['waktu_keluar'])) ?></td><td><?= (int) $row['durasi_jam'] ?> jam</td><td><strong><?= e(rupiah($row['biaya_total'])) ?></strong></td></tr><?php endforeach; ?>
            <?php if (!$latest): ?><tr><td colspan="5" class="table-empty">Belum ada transaksi selesai.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div></section></div>
    <div class="col-xl-4"><section class="card h-100"><div class="card-body p-4"><span class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></span><h2 class="h6 fw-bold mt-3">Pantau performa bisnis</h2><p class="text-muted" style="font-size:11px;line-height:1.7">Laporan menyediakan ringkasan transaksi berdasarkan periode, jenis kendaraan, status pembayaran, dan pendapatan.</p><a href="laporan.php" class="btn btn-primary mt-2">Lihat laporan lengkap <i class="bi bi-arrow-right ms-1"></i></a></div></section></div>
</div>
<?php include '../includes/footer.php'; ?>
