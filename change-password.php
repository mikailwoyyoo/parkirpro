<?php
require_once __DIR__ . '/includes/auth.php';
cekAkses(['admin', 'petugas', 'owner']);

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $current = (string) ($_POST['password_lama'] ?? '');
    $new = (string) ($_POST['password_baru'] ?? '');
    $confirm = (string) ($_POST['konfirmasi'] ?? '');

    $stmt = $pdo->prepare('SELECT password FROM tb_user WHERE id_user = ? AND status_aktif = 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $savedHash = $stmt->fetchColumn();
    if (!$savedHash || !password_verify($current, $savedHash)) {
        $error = 'Kata sandi saat ini tidak sesuai.';
    } elseif (strlen($new) < 8) {
        $error = 'Kata sandi baru harus memiliki minimal 8 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi kata sandi baru tidak cocok.';
    } elseif (password_verify($new, $savedHash)) {
        $error = 'Kata sandi baru harus berbeda dari kata sandi saat ini.';
    } else {
        $update = $pdo->prepare('UPDATE tb_user SET password = ? WHERE id_user = ?');
        $update->execute([password_hash($new, PASSWORD_DEFAULT), (int) $_SESSION['user_id']]);
        catatLog($pdo, (int) $_SESSION['user_id'], 'Mengubah kata sandi akun');
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $success = 'Kata sandi berhasil diperbarui.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti kata sandi · ParkirPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<main class="container py-5" style="max-width:650px">
    <a class="brand text-dark mb-4" href="<?= e(roleHome($_SESSION['role'])) ?>"><span class="brand-mark"><i class="bi bi-p-square-fill"></i></span><span><strong>parkir<span>pro</span></strong><small class="text-secondary">PENGATURAN AKUN</small></span></a>
    <section class="card"><div class="card-body p-4 p-md-5">
        <span class="stat-icon blue"><i class="bi bi-key"></i></span>
        <h1 class="h5 fw-bold mt-3">Ganti kata sandi</h1>
        <p class="text-muted mb-4" style="font-size:11px">Gunakan kata sandi kuat yang belum pernah digunakan sebelumnya.</p>
        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <form method="post"><?= csrfField() ?>
            <div class="mb-3"><label class="form-label" for="current-password">Kata sandi saat ini</label><input class="form-control" id="current-password" type="password" name="password_lama" autocomplete="current-password" required></div>
            <div class="mb-3"><label class="form-label" for="new-password">Kata sandi baru</label><input class="form-control" id="new-password" type="password" name="password_baru" minlength="8" autocomplete="new-password" required><div class="form-text">Minimal 8 karakter.</div></div>
            <div class="mb-4"><label class="form-label" for="confirm-password">Konfirmasi kata sandi baru</label><input class="form-control" id="confirm-password" type="password" name="konfirmasi" minlength="8" autocomplete="new-password" required></div>
            <div class="d-flex justify-content-between"><a href="<?= e(roleHome($_SESSION['role'])) ?>" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Kembali</a><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan kata sandi</button></div>
        </form>
    </div></section>
</main>
</body>
</html>
