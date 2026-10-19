<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function formatRupiah($amount): string
{
    return rupiah($amount);
}

function formatTanggalIndo($value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    $months = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    return date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)]
        . ' ' . date('Y, H:i', $timestamp);
}

function formatHariTanggalIndo(string $value): string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    $days = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    return $days[(int) date('N', $timestamp)] . ', ' . date('d', $timestamp) . ' '
        . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function formatBulanTahunIndo(string $value): string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    return $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function tampilkanFlashMessage(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = $flash['type'] === 'success' ? 'success' : 'danger';
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show app-alert" role="alert">'
        . e($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>'
        . '</div>';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrfToken(), $submitted)) {
        http_response_code(419);
        exit('Sesi formulir telah berakhir. Silakan kembali dan coba lagi.');
    }
}

function catatLog(PDO $pdo, ?int $userId, string $activity): void
{
    $stmt = $pdo->prepare('INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES (?, ?)');
    $stmt->execute([$userId, $activity]);
}

function roleHome(string $role): string
{
    $homes = [
        'admin' => 'admin/dashboard.php',
        'petugas' => 'petugas/dashboard.php',
        'owner' => 'owner/dashboard.php',
    ];

    return $homes[$role] ?? 'login.php';
}

function durationHours(string $start, ?string $end = null): int
{
    $from = strtotime($start);
    $to = strtotime($end ?? 'now');

    return max(1, (int) ceil(max(0, $to - $from) / 3600));
}

function weeklyParkingStats(PDO $pdo): array
{
    $today = new DateTimeImmutable('today');
    $from = $today->modify('-6 days');
    $until = $today->modify('+1 day');
    $start = $from->format('Y-m-d');
    $end = $until->format('Y-m-d');

    $stats = [];
    for ($day = $from; $day < $until; $day = $day->modify('+1 day')) {
        $key = $day->format('Y-m-d');
        $stats[$key] = [
            'label' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][(int) $day->format('w')]
                . ' ' . $day->format('d'),
            'masuk' => 0,
            'keluar' => 0,
            'pendapatan' => 0.0,
        ];
    }

    $entries = $pdo->prepare('SELECT DATE(waktu_masuk) AS tanggal, COUNT(*) AS jumlah FROM tb_transaksi WHERE waktu_masuk >= ? AND waktu_masuk < ? GROUP BY DATE(waktu_masuk)');
    $entries->execute([$start, $end]);
    foreach ($entries->fetchAll() as $row) {
        $stats[$row['tanggal']]['masuk'] = (int) $row['jumlah'];
    }

    $exits = $pdo->prepare("SELECT DATE(waktu_keluar) AS tanggal, COUNT(*) AS jumlah, COALESCE(SUM(biaya_total), 0) AS pendapatan FROM tb_transaksi WHERE status = 'keluar' AND waktu_keluar >= ? AND waktu_keluar < ? GROUP BY DATE(waktu_keluar)");
    $exits->execute([$start, $end]);
    foreach ($exits->fetchAll() as $row) {
        $stats[$row['tanggal']]['keluar'] = (int) $row['jumlah'];
        $stats[$row['tanggal']]['pendapatan'] = (float) $row['pendapatan'];
    }

    return array_values($stats);
}

function syncParkingSlots(PDO $pdo, int $areaId, int $capacity): void
{
    $stmt = $pdo->prepare('SELECT id_slot, kode_slot FROM tb_slot_parkir WHERE id_area = ? ORDER BY id_slot FOR UPDATE');
    $stmt->execute([$areaId]);
    $slots = $stmt->fetchAll();

    $occupiedStmt = $pdo->prepare("SELECT id_slot FROM tb_transaksi WHERE id_area = ? AND status = 'masuk' AND id_slot IS NOT NULL");
    $occupiedStmt->execute([$areaId]);
    $occupied = array_fill_keys(array_map('intval', $occupiedStmt->fetchAll(PDO::FETCH_COLUMN)), true);
    if (count($occupied) > $capacity) {
        throw new InvalidArgumentException('Kapasitas tidak boleh lebih kecil dari jumlah kendaraan yang parkir.');
    }

    $removeCount = max(0, count($slots) - $capacity);
    if ($removeCount > 0) {
        $removable = array_values(array_filter($slots, static fn(array $slot): bool => !isset($occupied[(int) $slot['id_slot']])));
        $idsToRemove = array_column(array_slice(array_reverse($removable), 0, $removeCount), 'id_slot');
        if (count($idsToRemove) !== $removeCount) {
            throw new InvalidArgumentException('Slot yang sedang digunakan tidak dapat dihapus.');
        }
        $placeholders = implode(',', array_fill(0, count($idsToRemove), '?'));
        $delete = $pdo->prepare("DELETE FROM tb_slot_parkir WHERE id_slot IN ({$placeholders})");
        $delete->execute(array_map('intval', $idsToRemove));
    }

    $missingCount = $capacity - count($slots) + $removeCount;
    if ($missingCount <= 0) {
        return;
    }

    $maxNumber = 0;
    foreach ($slots as $slot) {
        if (preg_match('/-(\d+)$/', $slot['kode_slot'], $matches)) {
            $maxNumber = max($maxNumber, (int) $matches[1]);
        }
    }

    $insert = $pdo->prepare('INSERT INTO tb_slot_parkir (id_area, kode_slot) VALUES (?, ?)');
    for ($offset = 1; $offset <= $missingCount; $offset++) {
        $insert->execute([$areaId, sprintf('A%d-%03d', $areaId, $maxNumber + $offset)]);
    }
}

function parkingAreasLayout(PDO $pdo): array
{
    $areas = $pdo->query('SELECT id_area, nama_area, kapasitas FROM tb_area_parkir ORDER BY nama_area')->fetchAll();
    if (!$areas) {
        return [];
    }

    $layout = [];
    foreach ($areas as $area) {
        $id = (int) $area['id_area'];
        $layout[$id] = [
            'id_area' => $id,
            'nama_area' => $area['nama_area'],
            'kapasitas' => (int) $area['kapasitas'],
            'slots' => [],
            'terisi' => 0,
            'tersedia' => 0,
        ];
    }

    $slots = $pdo->query('SELECT id_slot, id_area, kode_slot FROM tb_slot_parkir ORDER BY id_area, id_slot')->fetchAll();
    foreach ($slots as $slot) {
        $areaId = (int) $slot['id_area'];
        if (!isset($layout[$areaId])) {
            continue;
        }
        $layout[$areaId]['slots'][] = [
            'id_slot' => (int) $slot['id_slot'],
            'kode_slot' => $slot['kode_slot'],
            'kendaraan' => null,
        ];
    }

    $active = $pdo->query("SELECT t.id_parkir, t.id_area, t.id_slot, t.barcode_token, t.waktu_masuk, k.plat_nomor, k.jenis_kendaraan FROM tb_transaksi t JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan WHERE t.status = 'masuk' ORDER BY t.waktu_masuk, t.id_parkir")->fetchAll();
    $legacy = [];
    foreach ($active as $transaction) {
        $areaId = (int) $transaction['id_area'];
        if (!isset($layout[$areaId])) {
            continue;
        }
        $vehicle = [
            'id_parkir' => (int) $transaction['id_parkir'],
            'plat_nomor' => $transaction['plat_nomor'],
            'jenis_kendaraan' => $transaction['jenis_kendaraan'],
            'waktu_masuk' => $transaction['waktu_masuk'],
        ];
        if ($transaction['id_slot'] !== null) {
            foreach ($layout[$areaId]['slots'] as &$slot) {
                if ($slot['id_slot'] === (int) $transaction['id_slot']) {
                    $slot['kendaraan'] = $vehicle;
                    break;
                }
            }
            unset($slot);
        } else {
            $legacy[$areaId][] = $vehicle;
        }
    }

    foreach ($legacy as $areaId => $vehicles) {
        foreach ($vehicles as $vehicle) {
            foreach ($layout[$areaId]['slots'] as &$slot) {
                if ($slot['kendaraan'] === null) {
                    $slot['kendaraan'] = $vehicle;
                    break;
                }
            }
            unset($slot);
        }
    }

    foreach ($layout as &$area) {
        $area['terisi'] = count(array_filter($area['slots'], static fn(array $slot): bool => $slot['kendaraan'] !== null));
        $area['tersedia'] = max(0, count($area['slots']) - $area['terisi']);
        $area['penuh'] = $area['tersedia'] === 0;
    }
    unset($area);

    return array_values($layout);
}

function publicParkingAvailability(PDO $pdo): array
{
    return array_map(static function (array $area): array {
        $vehicles = [];
        foreach ($area['slots'] as $slot) {
            if ($slot['kendaraan'] !== null) {
                $vehicles[] = [
                    'jenis_kendaraan' => $slot['kendaraan']['jenis_kendaraan'],
                    'nama_area' => $area['nama_area'],
                    'kode_slot' => $slot['kode_slot'],
                    'waktu_masuk' => $slot['kendaraan']['waktu_masuk'],
                ];
            }
        }
        usort($vehicles, static fn(array $first, array $second): int => strcmp($second['waktu_masuk'], $first['waktu_masuk']));

        return [
            'id_area' => $area['id_area'],
            'nama_area' => $area['nama_area'],
            'kapasitas' => count($area['slots']),
            'terisi' => $area['terisi'],
            'tersedia' => $area['tersedia'],
            'kendaraan_aktif' => array_slice($vehicles, 0, 8),
            'slots' => array_map(static fn(array $slot): array => [
                'kode_slot' => $slot['kode_slot'],
                'tersedia' => $slot['kendaraan'] === null,
                'jenis_kendaraan' => $slot['kendaraan']['jenis_kendaraan'] ?? null,
            ], $area['slots']),
        ];
    }, parkingAreasLayout($pdo));
}
