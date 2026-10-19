<section class="parking-map-section mt-4">
    <div class="section-heading">
        <div>
            <h2>Denah area parkir</h2>
            <p>Lihat posisi slot kendaraan dan kapasitas area dari tampak atas.</p>
        </div>
        <span class="map-live-indicator"><i class="bi bi-circle-fill"></i> Kondisi terkini</span>
    </div>
    <div class="row g-3">
        <?php foreach ($parkingLayout as $area): ?>
            <?php
            $slotCount = count($area['slots']);
            $percentage = $slotCount > 0 ? (int) round($area['terisi'] / $slotCount * 100) : 0;
            $statusClass = $area['penuh'] ? 'full' : ($percentage >= 80 ? 'busy' : 'available');
            $sideCount = (int) ceil($slotCount / 4);
            $middleCount = max(0, $slotCount - ($sideCount * 2));
            $leftSlots = array_slice($area['slots'], 0, $sideCount);
            $middleSlots = array_slice($area['slots'], $sideCount, $middleCount);
            $middleLeftSlots = array_slice($middleSlots, 0, (int) ceil(count($middleSlots) / 2));
            $middleRightSlots = array_slice($middleSlots, count($middleLeftSlots));
            $rightSlots = array_slice($area['slots'], $sideCount + $middleCount);
            ?>
            <div class="col-12">
                <section class="card parking-map-card <?= e($statusClass) ?>">
                    <div class="card-body p-3 p-md-4">
                        <div class="parking-map-heading">
                            <div>
                                <span class="parking-map-icon"><i class="bi bi-p-square"></i></span>
                                <div>
                                    <h3><?= e($area['nama_area']) ?></h3>
                                    <p><?= $area['terisi'] ?> terisi <span>·</span> <?= $area['tersedia'] ?> kosong <span>·</span> kapasitas <?= $slotCount ?></p>
                                </div>
                            </div>
                            <span class="parking-status <?= e($statusClass) ?>">
                                <i class="bi <?= $area['penuh'] ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                                <?= $area['penuh'] ? 'Penuh' : 'Tersedia' ?>
                            </span>
                        </div>
                        <div class="parking-blueprint" aria-label="Denah <?= e($area['nama_area']) ?>">
                            <div class="parking-blueprint-entry"><i class="bi bi-box-arrow-in-down"></i><span>MASUK</span></div>
                            <div class="parking-lot-layout">
                                <div class="parking-side-bays parking-side-bays-left" aria-label="Deret parkir kiri">
                                    <?php foreach ($leftSlots as $slot): ?>
                                        <?php $parkingSlot = $slot; $parkingSlotPosition = 'side'; include __DIR__ . '/parking-slot.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                                <div class="parking-center-lane">
                                    <div class="parking-center-bank parking-center-bank-left" aria-label="Deret parkir tengah kiri">
                                        <?php foreach ($middleLeftSlots as $slot): ?>
                                            <?php $parkingSlot = $slot; $parkingSlotPosition = 'center-left'; include __DIR__ . '/parking-slot.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="parking-center-drive">
                                        <span><i class="bi bi-arrow-up"></i></span>
                                        <span>JALUR</span>
                                        <span><i class="bi bi-arrow-down"></i></span>
                                    </div>
                                    <div class="parking-center-bank parking-center-bank-right" aria-label="Deret parkir tengah kanan">
                                        <?php foreach ($middleRightSlots as $slot): ?>
                                            <?php $parkingSlot = $slot; $parkingSlotPosition = 'center-right'; include __DIR__ . '/parking-slot.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="parking-side-bays parking-side-bays-right" aria-label="Deret parkir kanan">
                                    <?php foreach ($rightSlots as $slot): ?>
                                        <?php $parkingSlot = $slot; $parkingSlotPosition = 'side'; include __DIR__ . '/parking-slot.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="parking-blueprint-exit"><i class="bi bi-box-arrow-up-right"></i><span>KELUAR</span></div>
                        </div>
                        <div class="parking-map-footer">
                            <div class="parking-map-legend"><span><i class="legend-dot vacant"></i>Kosong</span><span><i class="legend-dot occupied"></i>Terisi</span></div>
                            <span class="parking-occupancy"><?= $percentage ?>% terpakai</span>
                        </div>
                    </div>
                </section>
            </div>
        <?php endforeach; ?>
        <?php if (!$parkingLayout): ?>
            <div class="col-12"><div class="card card-body table-empty">Belum ada area parkir yang dapat ditampilkan.</div></div>
        <?php endif; ?>
    </div>
</section>
