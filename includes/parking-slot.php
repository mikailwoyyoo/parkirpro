<?php
$occupied = $parkingSlot['kendaraan'] !== null;
$vehicleType = $parkingSlot['kendaraan']['jenis_kendaraan'] ?? '';
$vehicleImage = match ($vehicleType) {
    'motor', 'bus', 'truk' => "../assets/images/vehicle-{$vehicleType}-3d.svg",
    'mobil' => '../assets/images/car-top-view.svg',
    default => '../assets/images/car-top-view.svg',
};
?>
<div class="parking-slot <?= $occupied ? 'occupied' : 'vacant' ?> parking-slot--<?= e($parkingSlotPosition) ?>" title="<?= $occupied ? 'Terisi · ' . e($parkingSlot['kendaraan']['plat_nomor']) : 'Kosong' ?> · <?= e($parkingSlot['kode_slot']) ?>" aria-label="<?= e($parkingSlot['kode_slot']) ?>: <?= $occupied ? 'terisi' : 'kosong' ?>">
    <span class="parking-slot-number"><?= e($parkingSlot['kode_slot']) ?></span>
    <?php if ($occupied && $vehicleImage !== ''): ?>
        <img class="parking-car-3d parking-vehicle-3d--<?= e($vehicleType) ?>" src="<?= e($vehicleImage) ?>" alt="" aria-hidden="true">
    <?php else: ?>
        <i class="bi <?= $occupied ? 'bi-bicycle' : 'bi-p-square' ?>" aria-hidden="true"></i>
    <?php endif; ?>
    <span class="parking-slot-state"><?= $occupied ? e($parkingSlot['kendaraan']['plat_nomor']) : 'KOSONG' ?></span>
</div>
