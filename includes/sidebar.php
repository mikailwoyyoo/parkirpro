<?php
$role = $_SESSION['role'] ?? '';
$menus = [
    'admin' => [
        ['dashboard.php', 'Dashboard', 'bi-grid-1x2'],
        ['users.php', 'Pengguna', 'bi-people'],
        ['tarif.php', 'Tarif parkir', 'bi-cash-coin'],
        ['area.php', 'Area parkir', 'bi-p-square'],
        ['kendaraan.php', 'Data kendaraan', 'bi-car-front'],
        ['laporan.php', 'Laporan parkir', 'bi-file-earmark-bar-graph'],
        ['log_aktivitas.php', 'Aktivitas', 'bi-clock-history'],
    ],
    'petugas' => [
        ['dashboard.php', 'Dashboard', 'bi-grid-1x2'],
        ['kendaraan_masuk.php', 'Kendaraan masuk', 'bi-box-arrow-in-down-left'],
        ['kendaraan_keluar.php', 'Kendaraan keluar', 'bi-box-arrow-up-right'],
        ['transaksi.php', 'Riwayat transaksi', 'bi-receipt'],
    ],
    'owner' => [
        ['dashboard.php', 'Dashboard', 'bi-grid-1x2'],
        ['laporan.php', 'Laporan', 'bi-graph-up-arrow'],
    ],
];
$roleLabels = ['admin' => 'Administrator', 'petugas' => 'Petugas parkir', 'owner' => 'Pemilik usaha'];
?>
<aside class="sidebar" id="app-sidebar">
    <a class="brand" href="dashboard.php">
        <span class="brand-mark"><i class="bi bi-p-square-fill"></i></span>
        <span><strong>parkir<span>pro</span></strong><small>SMART PARKING SYSTEM</small></span>
    </a>
    <div class="sidebar-section-label">MENU UTAMA</div>
    <nav class="sidebar-nav" aria-label="Navigasi utama">
        <?php foreach ($menus[$role] ?? [] as [$file, $label, $icon]): ?>
            <a class="sidebar-link <?= $current_page === $file ? 'active' : '' ?>" href="<?= e($file) ?>">
                <i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span>
                <?php if ($current_page === $file): ?><span class="active-indicator"></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
        <div class="support-card">
            <span class="support-icon"><i class="bi bi-shield-check"></i></span>
            <strong>Sistem terkendali</strong>
            <span>Data parkir tersimpan dengan aman.</span>
        </div>
        <div class="sidebar-profile">
            <div class="avatar avatar-sidebar"><?= e(strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1))) ?></div>
            <div><strong><?= e($_SESSION['nama_lengkap'] ?? 'Pengguna') ?></strong><span><?= e($roleLabels[$role] ?? ucfirst($role)) ?></span></div>
        </div>
    </div>
</aside>
