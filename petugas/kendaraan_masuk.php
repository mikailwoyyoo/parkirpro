<?php
require_once '../includes/auth.php';
cekAkses(['petugas', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $plate = strtoupper(preg_replace('/\s+/', '', trim((string) ($_POST['plat_nomor'] ?? ''))));
    $type = (string) ($_POST['jenis_kendaraan'] ?? '');
    $color = trim((string) ($_POST['warna'] ?? ''));
    $owner = trim((string) ($_POST['pemilik'] ?? ''));
    $areaId = filter_var($_POST['id_area'] ?? null, FILTER_VALIDATE_INT);

    try {
        if (!preg_match('/^[A-Z0-9-]{2,16}$/', $plate) || !in_array($type, ['motor', 'mobil', 'bus', 'truk', 'lainnya'], true) || $color === '' || $owner === '' || !$areaId) {
            throw new InvalidArgumentException('Periksa kembali plat, jenis kendaraan, pemilik, warna, dan area parkir.');
        }

        $pdo->beginTransaction();
        $areaStmt = $pdo->prepare('SELECT id_area, nama_area, kapasitas FROM tb_area_parkir WHERE id_area = ? FOR UPDATE');
        $areaStmt->execute([$areaId]);
        $area = $areaStmt->fetch();
        if (!$area) {
            throw new InvalidArgumentException('Area parkir yang dipilih tidak tersedia.');
        }
        $occupiedStmt = $pdo->prepare("SELECT id_parkir, id_slot FROM tb_transaksi WHERE id_area = ? AND status = 'masuk' ORDER BY id_parkir FOR UPDATE");
        $occupiedStmt->execute([$areaId]);
        $activeTransactions = $occupiedStmt->fetchAll();
        $occupied = count($activeTransactions);
        if ($occupied >= (int) $area['kapasitas']) {
            throw new InvalidArgumentException('Area parkir sudah penuh. Pilih area lain.');
        }

        $assignedSlots = [];
        $legacyCount = 0;
        foreach ($activeTransactions as $activeTransaction) {
            if ($activeTransaction['id_slot'] === null) {
                $legacyCount++;
            } else {
                $assignedSlots[(int) $activeTransaction['id_slot']] = true;
            }
        }
        $slotsStmt = $pdo->prepare('SELECT id_slot FROM tb_slot_parkir WHERE id_area = ? ORDER BY id_slot FOR UPDATE');
        $slotsStmt->execute([$areaId]);
        $availableSlots = array_values(array_filter(
            $slotsStmt->fetchAll(PDO::FETCH_COLUMN),
            static fn($slotId): bool => !isset($assignedSlots[(int) $slotId])
        ));
        $slotId = $availableSlots[$legacyCount] ?? null;
        if ($slotId === null) {
            throw new RuntimeException('Denah slot belum sesuai kapasitas area. Minta administrator menyimpan ulang pengaturan area.');
        }

        $vehicleStmt = $pdo->prepare('SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ? FOR UPDATE');
        $vehicleStmt->execute([$plate]);
        $vehicle = $vehicleStmt->fetch();
        if ($vehicle) {
            $vehicleId = (int) $vehicle['id_kendaraan'];
            $activeStmt = $pdo->prepare("SELECT id_parkir FROM tb_transaksi WHERE id_kendaraan = ? AND status = 'masuk' LIMIT 1 FOR UPDATE");
            $activeStmt->execute([$vehicleId]);
            if ($activeStmt->fetch()) {
                throw new InvalidArgumentException('Kendaraan dengan plat tersebut masih tercatat sedang parkir.');
            }
            $updateVehicle = $pdo->prepare('UPDATE tb_kendaraan SET jenis_kendaraan = ?, warna = ?, pemilik = ?, id_user = ? WHERE id_kendaraan = ?');
            $updateVehicle->execute([$type, $color, $owner, (int) $_SESSION['user_id'], $vehicleId]);
        } else {
            $insertVehicle = $pdo->prepare('INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)');
            $insertVehicle->execute([$plate, $type, $color, $owner, (int) $_SESSION['user_id']]);
            $vehicleId = (int) $pdo->lastInsertId();
        }

        $tariffStmt = $pdo->prepare('SELECT id_tarif, tarif_per_jam FROM tb_tarif WHERE jenis_kendaraan = ?');
        $tariffStmt->execute(in_array($type, ['bus', 'truk'], true) ? ['lainnya'] : [$type]);
        $tariff = $tariffStmt->fetch();
        if (!$tariff) {
            throw new InvalidArgumentException('Tarif untuk jenis kendaraan ini belum diatur.');
        }

        $barcodeToken = bin2hex(random_bytes(16));
        $insertTransaction = $pdo->prepare("INSERT INTO tb_transaksi (id_kendaraan, id_area, id_slot, id_tarif, id_user, barcode_token, tarif_per_jam, waktu_masuk, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'masuk')");
        $insertTransaction->execute([$vehicleId, $areaId, (int) $slotId, $tariff['id_tarif'], (int) $_SESSION['user_id'], $barcodeToken, $tariff['tarif_per_jam']]);
        $parkingId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE tb_area_parkir SET terisi = ? WHERE id_area = ?')->execute([$occupied + 1, $areaId]);
        catatLog($pdo, (int) $_SESSION['user_id'], "Kendaraan {$plate} masuk, transaksi #{$parkingId}");
        $pdo->commit();

        header('Location: cetak_struk.php?id=' . $parkingId);
        exit;
    } catch (InvalidArgumentException | RuntimeException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlashMessage('error', $exception->getMessage());
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception->getCode() === '23000') {
            setFlashMessage('error', 'Data kendaraan sedang diproses oleh petugas lain. Periksa plat nomor lalu coba kembali.');
        } else {
            throw $exception;
        }
    }
}

$areas = array_values(array_filter(parkingAreasLayout($pdo), static fn(array $area): bool => $area['tersedia'] > 0));
$page_title = 'Kendaraan masuk';
$page_subtitle = 'Catat kendaraan yang masuk dan terbitkan tiket parkir.';
include '../includes/header.php';
?>
<?php tampilkanFlashMessage(); ?>
<div class="row g-4">
    <div class="col-xl-7">
        <section class="card"><div class="card-body p-4">
            <div class="section-heading"><div><h2>Catat kendaraan masuk</h2><p>Isi detail kendaraan untuk membuat tiket parkir.</p></div><span class="stat-icon blue"><i class="bi bi-box-arrow-in-down-left"></i></span></div>
            <?php if (!$areas): ?><div class="alert alert-warning">Semua area penuh atau belum tersedia. Hubungi administrator sebelum mencatat kendaraan.</div><?php else: ?>
            <form method="post" class="row g-3"><?= csrfField() ?>
                <div class="col-12"><label class="form-label" for="plate">Nomor polisi</label><input class="form-control" id="plate" name="plat_nomor" maxlength="16" placeholder="Contoh: B 1234 ABC" data-uppercase required><div class="form-text">Kendaraan terdaftar sebelumnya akan dikenali dari plat nomor.</div></div>
                <div class="col-md-6"><label class="form-label" for="vehicle-type">Jenis kendaraan</label><select class="form-select" id="vehicle-type" name="jenis_kendaraan" required><option value="">Pilih jenis kendaraan</option><option value="motor">Motor</option><option value="mobil">Mobil</option><option value="bus">Bus</option><option value="truk">Truk</option><option value="lainnya">Lainnya</option></select></div>
                <div class="col-md-6"><label class="form-label" for="vehicle-color">Warna kendaraan</label><input class="form-control" id="vehicle-color" name="warna" maxlength="40" placeholder="Contoh: Hitam" required></div>
                <div class="col-md-6"><label class="form-label" for="owner">Nama pemilik</label><input class="form-control" id="owner" name="pemilik" maxlength="120" placeholder="Nama pemilik kendaraan" required></div>
                <div class="col-md-6"><label class="form-label" for="parking-area">Area parkir</label><select class="form-select" id="parking-area" name="id_area" required><option value="">Pilih area tersedia</option><?php foreach ($areas as $area): ?><option value="<?= (int) $area['id_area'] ?>"><?= e($area['nama_area']) ?> · sisa <?= (int) $area['tersedia'] ?> slot</option><?php endforeach; ?></select></div>
                <div class="col-12 pt-2"><button class="btn btn-primary w-100 py-2"><i class="bi bi-check2-circle me-1"></i>Catat masuk & cetak tiket</button></div>
            </form><?php endif; ?>
        </div></section>
    </div>
    <div class="col-xl-5">
        <section class="card h-100"><div class="card-body p-4"><div class="section-heading"><div><h2>Tarif saat ini</h2><p>Bus dan truk menggunakan tarif kategori Lainnya.</p></div><i class="bi bi-cash-coin text-primary fs-5"></i></div>
            <?php foreach ($pdo->query('SELECT jenis_kendaraan, tarif_per_jam FROM tb_tarif ORDER BY FIELD(jenis_kendaraan, "motor", "mobil", "lainnya")')->fetchAll() as $tariff): ?><div class="d-flex align-items-center justify-content-between py-3 border-bottom"><span class="small text-capitalize"><?= e($tariff['jenis_kendaraan']) ?></span><strong class="small"><?= e(rupiah($tariff['tarif_per_jam'])) ?><span class="text-muted fw-normal"> / jam</span></strong></div><?php endforeach; ?>
            <p class="text-muted mt-3 mb-0" style="font-size:10px"><i class="bi bi-info-circle me-1"></i>Pembulatan durasi dilakukan ke atas per jam dan minimal satu jam.</p>
        </div></section>
    </div>
</div>
<?php $parkingLayout = parkingAreasLayout($pdo); ?>
<?php include '../includes/parking-map.php'; ?>
<?php include '../includes/footer.php'; ?>
