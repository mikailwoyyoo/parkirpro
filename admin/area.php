<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    try {
        $name = trim((string) ($_POST['nama_area'] ?? ''));
        $capacity = filter_var($_POST['kapasitas'] ?? null, FILTER_VALIDATE_INT);
        if ($name === '' || $capacity === false || $capacity < 1) {
            throw new InvalidArgumentException('Nama area wajib diisi dan kapasitas minimal 1.');
        }

        if (isset($_POST['tambah_area'])) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO tb_area_parkir (nama_area, kapasitas) VALUES (?, ?)');
            $stmt->execute([$name, $capacity]);
            $id = (int) $pdo->lastInsertId();
            syncParkingSlots($pdo, $id, $capacity);
            catatLog($pdo, (int) $_SESSION['user_id'], "Menambahkan area parkir: {$name}");
            $pdo->commit();
            setFlashMessage('success', 'Area parkir berhasil ditambahkan.');
        } elseif (isset($_POST['update_area'])) {
            $id = filter_var($_POST['id_area'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException('Area parkir tidak valid.');
            }
            $pdo->beginTransaction();
            $lockArea = $pdo->prepare('SELECT id_area FROM tb_area_parkir WHERE id_area = ? FOR UPDATE');
            $lockArea->execute([$id]);
            if (!$lockArea->fetch()) {
                throw new InvalidArgumentException('Area parkir tidak ditemukan.');
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_area = ? AND status = 'masuk'");
            $stmt->execute([$id]);
            $occupied = (int) $stmt->fetchColumn();
            if ($capacity < $occupied) {
                throw new InvalidArgumentException("Kapasitas tidak boleh kurang dari {$occupied} kendaraan yang sedang parkir.");
            }
            $stmt = $pdo->prepare('UPDATE tb_area_parkir SET nama_area = ?, kapasitas = ? WHERE id_area = ?');
            $stmt->execute([$name, $capacity, $id]);
            syncParkingSlots($pdo, $id, $capacity);
            catatLog($pdo, (int) $_SESSION['user_id'], "Memperbarui area parkir: {$name}");
            $pdo->commit();
            setFlashMessage('success', 'Informasi area berhasil diperbarui.');
        }
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlashMessage('error', $exception->getMessage());
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            setFlashMessage('error', 'Nama area tersebut sudah digunakan.');
        } else {
            throw $exception;
        }
    }
    header('Location: area.php');
    exit;
}

$areas = $pdo->query("SELECT a.*, (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk') AS terisi FROM tb_area_parkir a ORDER BY a.nama_area")->fetchAll();
$parkingLayout = parkingAreasLayout($pdo);
$page_title = 'Area parkir';
$page_subtitle = 'Kelola kapasitas dan pantau ketersediaan setiap area.';
include '../includes/header.php';
?>
<?php tampilkanFlashMessage(); ?>
<div class="row g-3 mb-4">
    <?php foreach ($areas as $area): $percent = (int) $area['kapasitas'] ? min(100, round($area['terisi'] / $area['kapasitas'] * 100)) : 0; ?>
        <div class="col-md-6 col-xl-4"><section class="card h-100"><div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between"><span class="stat-icon blue"><i class="bi bi-p-square"></i></span><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit-<?= (int) $area['id_area'] ?>"><i class="bi bi-pencil me-1"></i>Edit</button></div>
            <h2 class="h6 fw-bold mt-3 mb-1"><?= e($area['nama_area']) ?></h2><p class="text-muted mb-3" style="font-size:10px">Kapasitas <?= (int) $area['kapasitas'] ?> kendaraan</p>
            <div class="d-flex justify-content-between mb-2" style="font-size:10px"><span><?= (int) $area['terisi'] ?> terisi</span><span><?= (int) $area['kapasitas'] - (int) $area['terisi'] ?> tersedia</span></div>
            <div class="capacity-track"><span style="width:<?= $percent ?>%"></span></div>
        </div></section></div>
        <div class="modal fade" id="edit-<?= (int) $area['id_area'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content"><?= csrfField() ?><div class="modal-header"><h2 class="modal-title fs-6 fw-bold">Edit area parkir</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
            <input type="hidden" name="id_area" value="<?= (int) $area['id_area'] ?>"><div class="mb-3"><label class="form-label">Nama area</label><input class="form-control" name="nama_area" maxlength="100" value="<?= e($area['nama_area']) ?>" required></div><div><label class="form-label">Kapasitas kendaraan</label><input class="form-control" type="number" name="kapasitas" min="<?= (int) $area['terisi'] ?>" value="<?= (int) $area['kapasitas'] ?>" required><div class="form-text">Minimal <?= (int) $area['terisi'] ?> sesuai kendaraan yang sedang parkir.</div></div>
        </div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" name="update_area" value="1">Simpan perubahan</button></div></form></div></div>
    <?php endforeach; ?>
    <?php if (!$areas): ?><div class="col-12"><div class="card card-body table-empty">Belum ada area parkir.</div></div><?php endif; ?>
</div>
<?php include '../includes/parking-map.php'; ?>
<div class="d-flex justify-content-end"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addArea"><i class="bi bi-plus-lg me-1"></i>Tambah area</button></div>
<div class="modal fade" id="addArea" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content"><?= csrfField() ?><div class="modal-header"><h2 class="modal-title fs-6 fw-bold">Tambah area parkir</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Nama area</label><input class="form-control" name="nama_area" maxlength="100" required></div><div><label class="form-label">Kapasitas kendaraan</label><input class="form-control" type="number" name="kapasitas" min="1" required></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" name="tambah_area" value="1">Simpan area</button></div></form></div></div>
<?php include '../includes/footer.php'; ?>
