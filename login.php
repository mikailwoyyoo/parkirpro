<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = isset($_GET['access']) && $_GET['access'] === 'inactive'
    ? 'Akun Anda tidak aktif. Hubungi administrator untuk bantuan.'
    : '';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . roleHome($_SESSION['role'] ?? ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan kata sandi wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT id_user, nama_lengkap, username, password, role FROM tb_user WHERE username = ? AND status_aktif = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            catatLog($pdo, (int) $user['id_user'], 'Login ke dalam sistem');
            header('Location: ' . roleHome($user['role']));
            exit;
        }

        $error = 'Username atau kata sandi tidak sesuai.';
    }
}

$parkingAvailability = publicParkingAvailability($pdo);
$publicVehicles = [];
foreach ($parkingAvailability as $area) {
    $publicVehicles = array_merge($publicVehicles, $area['kendaraan_aktif']);
}
usort($publicVehicles, static fn(array $first, array $second): int => strcmp($second['waktu_masuk'], $first['waktu_masuk']));
$publicVehicles = array_slice($publicVehicles, 0, 6);
$vehicleLabels = ['motor' => 'Motor', 'mobil' => 'Mobil', 'bus' => 'Bus', 'truk' => 'Truk', 'lainnya' => 'Kendaraan lain'];
$parkingAvailabilityJson = json_encode(
    $parkingAvailability,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101b36">
    <title>Masuk · ParkirPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <main class="login-layout">
        <section class="login-showcase">
            <a class="brand brand-light" href="login.php">
                <span class="brand-mark"><i class="bi bi-p-square-fill"></i></span>
                <span><strong>parkir<span>pro</span></strong><small>SMART PARKING SYSTEM</small></span>
            </a>
            <div class="showcase-copy">
                <span class="showcase-label"><i class="bi bi-stars"></i> PARKIR LEBIH CERDAS</span>
                <h1>Kelola ruang.<br><span>Permudah parkir.</span></h1>
                <p>Satu platform untuk mengelola kendaraan, transaksi, dan laporan parkir dengan lebih praktis.</p>
                <div class="showcase-visual">
                    <div class="visual-glow"></div>
                    <div class="parking-scene">
                        <div class="parking-scene-label"><span><i class="bi bi-p-square-fill"></i> KETERSEDIAAN AREA PARKIR</span><span class="scene-live"><i class="bi bi-circle-fill"></i> LIVE</span></div>
                        <div id="public-parking-map" class="public-parking-map" data-parking-availability data-endpoint="api/parking-availability.php" data-vehicle-image-base="assets/images/vehicle-" data-car-image="assets/images/car-top-view.svg" aria-live="polite" aria-busy="false">
                            <?php foreach ($parkingAvailability as $area): ?>
                                <section class="public-parking-area" aria-label="<?= e($area['nama_area']) ?>">
                                    <div class="public-area-heading">
                                        <strong><?= e($area['nama_area']) ?></strong>
                                        <span><b><?= (int) $area['terisi'] ?> terisi</b><i></i><b><?= (int) $area['tersedia'] ?> tersisa</b><i></i><?= (int) $area['kapasitas'] ?> total</span>
                                    </div>
                                    <div class="public-area-slots">
                                        <?php foreach ($area['slots'] as $slot): ?>
                                            <span class="public-parking-slot <?= $slot['tersedia'] ? 'available' : 'occupied' ?>" title="<?= e($slot['kode_slot']) ?> · <?= $slot['tersedia'] ? 'Kosong' : 'Terisi' ?>" aria-label="<?= e($slot['kode_slot']) ?>: <?= $slot['tersedia'] ? 'kosong' : 'terisi' ?>">
                                                <?php if ($slot['tersedia']): ?>
                                                    <i class="bi bi-p-square" aria-hidden="true"></i>
                                                <?php else: ?>
                                                    <?php $vehicleImage = match ($slot['jenis_kendaraan']) {
                                                        'motor', 'bus', 'truk' => "assets/images/vehicle-{$slot['jenis_kendaraan']}-3d.svg",
                                                        'mobil' => 'assets/images/car-top-view.svg',
                                                        default => 'assets/images/car-top-view.svg',
                                                    }; ?>
                                                    <img class="parking-public-vehicle--<?= e($slot['jenis_kendaraan'] ?? 'mobil') ?>" src="<?= e($vehicleImage) ?>" alt="" aria-hidden="true">
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (!$area['slots']): ?><span class="public-area-empty">Slot belum diatur</span><?php endif; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                            <?php if (!$parkingAvailability): ?><p class="public-map-empty">Belum ada area parkir.</p><?php endif; ?>
                        </div>
                        <section class="public-vehicle-list" aria-live="polite" aria-label="Kendaraan yang sedang parkir">
                            <div class="public-vehicle-list-heading">
                                <span><i class="bi bi-list-ul"></i> KENDARAAN SEDANG PARKIR</span>
                                <small data-public-vehicle-count><?= (int) array_sum(array_column($parkingAvailability, 'terisi')) ?> kendaraan</small>
                            </div>
                            <div class="public-vehicle-list-items" data-public-vehicle-list>
                                <?php foreach ($publicVehicles as $vehicle): ?>
                                    <div class="public-vehicle-item">
                                        <span class="public-vehicle-icon"><i class="bi <?= $vehicle['jenis_kendaraan'] === 'motor' ? 'bi-bicycle' : 'bi-car-front-fill' ?>"></i></span>
                                        <span class="public-vehicle-detail"><strong><?= e($vehicleLabels[$vehicle['jenis_kendaraan']] ?? 'Kendaraan') ?> · <?= e($vehicle['kode_slot']) ?></strong><small><?= e($vehicle['nama_area']) ?></small></span>
                                        <time><?= e(substr($vehicle['waktu_masuk'], 11, 5)) ?></time>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!$publicVehicles): ?><p class="public-vehicle-empty">Belum ada kendaraan yang parkir.</p><?php endif; ?>
                            </div>
                            <p class="public-vehicle-note" data-public-vehicle-note><?= count($publicVehicles) < array_sum(array_column($parkingAvailability, 'terisi')) ? 'Menampilkan kendaraan terbaru. Data tanpa nomor polisi.' : 'Informasi diperbarui otomatis. Data tanpa nomor polisi.' ?></p>
                        </section>
                        <div class="parking-scene-foot">
                            <span class="public-parking-totals"><span><i class="bi bi-car-front-fill"></i><strong data-parking-occupied><?= (int) array_sum(array_column($parkingAvailability, 'terisi')) ?></strong> terisi</span><span><i class="bi bi-p-square"></i><strong data-parking-total><?= (int) array_sum(array_column($parkingAvailability, 'tersedia')) ?></strong> tersisa</span></span>
                            <span class="public-parking-refresh"><i class="bi bi-arrow-repeat"></i> <span data-parking-updated>Memperbarui otomatis</span></span>
                        </div>
                    </div>
                </div>
                <aside class="login-promo">
                    <span class="login-promo-icon"><i class="bi bi-megaphone-fill"></i></span>
                    <div><span class="login-promo-label">INFORMASI PARKIR</span><strong>Parkir lebih praktis, tanpa menebak.</strong><p>Pantau slot yang tersedia dan kondisi area sebelum memilih tempat parkir.</p></div>
                    <a href="#public-parking-map">Lihat area <i class="bi bi-arrow-up-right"></i></a>
                </aside>
            </div>
            <div class="showcase-foot"><span>© <?= e(date('Y')) ?> ParkirPro</span><span>Platform manajemen parkir</span></div>
        </section>
        <section class="login-panel">
            <div class="login-form-wrap">
                <div class="login-mobile-brand">
                    <span class="brand-mark"><i class="bi bi-p-square-fill"></i></span>
                    <strong>parkir<span>pro</span></strong>
                </div>
                <span class="form-kicker">SELAMAT DATANG KEMBALI</span>
                <h2>Masuk ke akun</h2>
                <p class="login-lead">Masukkan detail akun untuk melanjutkan ke dashboard Anda.</p>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger login-error" role="alert"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" class="login-form">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <div class="input-with-icon"><i class="bi bi-person"></i><input id="username" type="text" name="username" class="form-control" placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required autofocus></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Kata sandi</label>
                        <div class="input-with-icon"><i class="bi bi-lock"></i><input id="password" type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" autocomplete="current-password" required><button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button></div>
                    </div>
                    <button type="submit" class="btn btn-primary login-submit">Masuk ke dashboard <i class="bi bi-arrow-right"></i></button>
                </form>
                <p class="login-security"><i class="bi bi-lock-fill"></i> Koneksi aman · Akses sesuai peran pengguna</p>
            </div>
        </section>
    </main>
    <script type="application/json" id="initial-parking-availability"><?= $parkingAvailabilityJson ?></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
