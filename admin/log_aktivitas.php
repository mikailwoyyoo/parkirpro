<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

$logs = $pdo->query('SELECT l.*, u.nama_lengkap, u.username FROM tb_log_aktivitas l LEFT JOIN tb_user u ON l.id_user = u.id_user ORDER BY l.waktu_aktivitas DESC LIMIT 300')->fetchAll();
$page_title = 'Aktivitas sistem';
$page_subtitle = 'Jejak perubahan dan aktivitas pengguna pada sistem.';
include '../includes/header.php';
?>
<section class="card">
    <div class="card-body p-0">
        <div class="section-heading px-4 pt-4"><div><h2>Log aktivitas</h2><p>Menampilkan 300 aktivitas terbaru.</p></div><span class="badge badge-soft-muted"><?= count($logs) ?> catatan</span></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th></tr></thead><tbody>
            <?php foreach ($logs as $log): ?><tr><td><?= e(formatTanggalIndo($log['waktu_aktivitas'])) ?></td><td><strong><?= e($log['nama_lengkap'] ?? 'Pengguna dihapus') ?></strong><?php if ($log['username']): ?><div class="text-muted" style="font-size:9px">@<?= e($log['username']) ?></div><?php endif; ?></td><td><?= e($log['aktivitas']) ?></td></tr><?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="3" class="table-empty">Belum ada aktivitas tercatat.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</section>
<?php include '../includes/footer.php'; ?>
