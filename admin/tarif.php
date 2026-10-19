<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $id = filter_var($_POST['id_tarif'] ?? null, FILTER_VALIDATE_INT);
    $rate = filter_var($_POST['tarif_per_jam'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $rate === false || $rate < 0) {
        setFlashMessage('error', 'Tarif harus berupa angka nol atau lebih.');
    } else {
        $stmt = $pdo->prepare('UPDATE tb_tarif SET tarif_per_jam = ? WHERE id_tarif = ?');
        $stmt->execute([$rate, $id]);
        catatLog($pdo, (int) $_SESSION['user_id'], "Memperbarui tarif ID {$id}");
        setFlashMessage('success', 'Tarif parkir berhasil diperbarui.');
    }
    header('Location: tarif.php');
    exit;
}

$tariffs = $pdo->query('SELECT * FROM tb_tarif ORDER BY FIELD(jenis_kendaraan, "motor", "mobil", "lainnya")')->fetchAll();
$icons = ['motor' => 'bi-bicycle', 'mobil' => 'bi-car-front', 'lainnya' => 'bi-truck-front'];
$page_title = 'Tarif parkir';
$page_subtitle = 'Atur harga per jam untuk setiap jenis kendaraan.';
include '../includes/header.php';
?>
<?php tampilkanFlashMessage(); ?>
<section class="card">
    <div class="card-body p-0">
        <div class="section-heading px-4 pt-4"><div><h2>Tarif per jenis kendaraan</h2><p>Tarif yang berlaku untuk transaksi parkir baru.</p></div></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Jenis kendaraan</th><th>Tarif per jam</th><th>Pembaruan</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($tariffs as $tariff): ?><tr>
                <td><span class="d-inline-flex align-items-center gap-2"><span class="stat-icon blue" style="width:31px;height:31px;font-size:14px"><i class="bi <?= e($icons[$tariff['jenis_kendaraan']] ?? 'bi-car-front') ?>"></i></span><strong><?= e(ucfirst($tariff['jenis_kendaraan'])) ?></strong></span></td>
                <td><strong><?= e(rupiah($tariff['tarif_per_jam'])) ?></strong></td>
                <td><?= e(formatTanggalIndo($tariff['diperbarui_pada'])) ?></td>
                <td><form method="post" class="d-flex align-items-center gap-2"><?= csrfField() ?><input type="hidden" name="id_tarif" value="<?= (int) $tariff['id_tarif'] ?>"><input class="form-control" style="width:125px" type="number" name="tarif_per_jam" min="0" step="1000" value="<?= (int) $tariff['tarif_per_jam'] ?>" aria-label="Tarif per jam <?= e($tariff['jenis_kendaraan']) ?>" required><button class="btn btn-sm btn-primary">Simpan</button></form></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$tariffs): ?><tr><td colspan="4" class="table-empty">Data tarif belum tersedia. Pastikan database sudah diimpor.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</section>
<div class="alert alert-info mt-3 small"><i class="bi bi-info-circle me-2"></i>Tarif transaksi yang sudah berjalan tidak berubah saat tarif baru diperbarui.</div>
<?php include '../includes/footer.php'; ?>
