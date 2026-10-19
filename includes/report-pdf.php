<?php
if (!defined('PARKING_REPORT_EXPORT')) {
    http_response_code(404);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> · ParkirPro</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f4f9; color: #17233d; font: 12px Arial,sans-serif; }
        main { width: min(1320px,calc(100% - 36px)); margin: 28px auto; padding: 34px; border-top: 5px solid #4169ed; background: #fff; box-shadow: 0 8px 32px #17233d16; }
        .report-brand { display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid #e7ebf3; }
        .brand-name { color: #3158dc; font-size: 12px; font-weight: 800; letter-spacing: 1px; }
        .brand-name span { color: #17233d; }
        .report-meta { color: #7a879d; font-size: 9px; text-align: right; }
        h1 { margin: 22px 0 0; font-size: 23px; letter-spacing: -.4px; }
        .subtitle { margin: 6px 0 18px; color: #627089; }
        .summary { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 17px; }
        .summary div { padding: 13px 15px; border: 1px solid #e5eaf2; border-radius: 8px; background: linear-gradient(145deg,#fff,#f8faff); }
        .summary div:last-child { border-color: #d4efe2; background: linear-gradient(145deg,#fff,#f1faf5); }
        .summary span,.summary strong { display: block; }
        .summary span { margin-bottom: 7px; color: #66738b; font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .summary strong { color: #3158dc; font-size: 17px; }
        .summary div:last-child strong { color: #13865f; }
        .filters { display: flex; flex-wrap: wrap; gap: 6px 18px; padding: 10px 12px; margin: 0 0 16px; border-radius: 6px; background: #f4f6fb; color: #66738b; font-size: 10px; line-height: 1.7; }
        .filters strong { color: #33415c; }
        table { width: 100%; border-collapse: collapse; }
        th,td { padding: 8px 7px; border-bottom: 1px solid #e6eaf1; text-align: left; vertical-align: top; }
        th { border-top: 1px solid #dfe5ee; background: #eef2fa; color: #44536d; font-size: 8px; letter-spacing: .35px; text-transform: uppercase; }
        td { color: #39465e; font-size: 8.5px; }
        tbody tr:nth-child(even) { background: #fafbfd; }
        .status { display: inline-block; padding: 4px 7px; border-radius: 20px; background: #fff4e4; color: #a56313; font-size: 8px; white-space: nowrap; }
        .status.complete { background: #e8f7f0; color: #14865f; }
        .report-foot { display: flex; justify-content: space-between; padding-top: 12px; margin-top: 14px; border-top: 1px solid #e7ebf3; color: #8792a5; font-size: 8px; }
        .actions { display: flex; justify-content: center; gap: 8px; margin: 20px; }
        button { padding: 10px 14px; border: 0; border-radius: 6px; background: #4169ed; color: white; font-weight: 700; cursor: pointer; }
        a { padding: 10px 14px; border: 1px solid #dbe1ed; border-radius: 6px; color: #33415c; text-decoration: none; }
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { background: white; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            main { width: 100%; margin: 0; padding: 0; box-shadow: none; }
            .actions { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
            .report-foot { position: fixed; right: 0; bottom: 0; left: 0; }
        }
        @media (max-width: 700px) {
            main { width: calc(100% - 20px); padding: 16px; overflow-x: auto; }
            table { min-width: 800px; }
            .report-meta { max-width: 42%; line-height: 1.6; }
        }
    </style>
</head>
<body>
    <main>
        <div class="report-brand"><div class="brand-name">PARKIR<span>PRO</span> <span>· SMART PARKING</span></div><div class="report-meta">Laporan resmi operasional<br>Dicetak <?= e(date('d-m-Y H:i')) ?></div></div>
        <h1>Laporan transaksi parkir</h1>
        <p class="subtitle">Periode <?= e(date('d-m-Y', strtotime($start))) ?> — <?= e(date('d-m-Y', strtotime($end))) ?></p>
        <div class="summary">
            <div><span>Total transaksi</span><strong><?= (int) $summary['jumlah'] ?></strong></div>
            <div><span>Transaksi selesai</span><strong><?= (int) $summary['selesai'] ?></strong></div>
            <div><span>Total pendapatan</span><strong><?= e(rupiah($summary['pendapatan'])) ?></strong></div>
        </div>
        <div class="filters"><span><strong>Jenis:</strong> <?= e($type !== '' ? ucfirst($type) : 'Semua jenis') ?></span><span><strong>Status:</strong> <?= e($status !== '' ? ($status === 'masuk' ? 'Sedang parkir' : 'Selesai') : 'Semua status') ?></span><span><strong>Jumlah data:</strong> <?= count($exportRows) ?> transaksi</span><span><strong>Dicetak oleh:</strong> <?= e($_SESSION['nama_lengkap'] ?? 'Pengguna') ?></span></div>
        <table>
            <thead><tr><th>ID</th><th>Plat nomor</th><th>Jenis</th><th>Pemilik</th><th>Area</th><th>Waktu masuk</th><th>Waktu keluar</th><th>Durasi</th><th>Status</th><th>Pendapatan</th><th>Petugas</th></tr></thead>
            <tbody>
                <?php foreach ($exportRows as $row): ?>
                    <tr>
                        <td>#<?= (int) $row['id_parkir'] ?></td>
                        <td><?= e($row['plat_nomor']) ?></td>
                        <td><?= e(ucfirst($row['jenis_kendaraan'])) ?></td>
                        <td><?= e($row['pemilik']) ?></td>
                        <td><?= e($row['nama_area']) ?></td>
                        <td><?= e($row['waktu_masuk']) ?></td>
                        <td><?= e($row['waktu_keluar'] ?? '-') ?></td>
                        <td><?= $row['durasi_jam'] !== null ? (int) $row['durasi_jam'] . ' jam' : '-' ?></td>
                        <td><span class="status <?= $row['status'] === 'keluar' ? 'complete' : '' ?>"><?= $row['status'] === 'masuk' ? 'Parkir' : 'Selesai' ?></span></td>
                        <td><?= $row['biaya_total'] !== null ? e(rupiah($row['biaya_total'])) : '-' ?></td>
                        <td><?= e($row['nama_petugas'] ?? 'Pengguna dihapus') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$exportRows): ?><tr><td colspan="11">Tidak ada transaksi pada periode dan filter terpilih.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="report-foot"><span>ParkirPro · Laporan dibuat otomatis dari data transaksi.</span><span><?= count($exportRows) ?> baris data</span></div>
        <div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="javascript:history.back()">Kembali ke laporan</a></div>
    </main>
    <script>window.addEventListener('load', () => window.setTimeout(() => window.print(), 350));</script>
</body>
</html>
