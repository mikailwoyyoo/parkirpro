<?php
require_once '../includes/auth.php';
cekAkses(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    try {
        if (isset($_POST['tambah'])) {
            $name = trim((string) ($_POST['nama_lengkap'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? '');
            if ($name === '' || $username === '' || strlen($password) < 8 || !in_array($role, ['admin', 'petugas', 'owner'], true)) {
                throw new InvalidArgumentException('Lengkapi data dengan benar. Kata sandi minimal 8 karakter.');
            }
            $stmt = $pdo->prepare('INSERT INTO tb_user (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            catatLog($pdo, (int) $_SESSION['user_id'], "Menambahkan pengguna: {$username}");
            setFlashMessage('success', 'Pengguna baru berhasil ditambahkan.');
        } elseif (isset($_POST['toggle_status'])) {
            $id = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id === (int) $_SESSION['user_id']) {
                throw new InvalidArgumentException('Akun Anda sendiri tidak dapat dinonaktifkan.');
            }
            $userStmt = $pdo->prepare('SELECT role, status_aktif FROM tb_user WHERE id_user = ?');
            $userStmt->execute([$id]);
            $targetUser = $userStmt->fetch();
            if (!$targetUser) {
                throw new InvalidArgumentException('Pengguna tidak ditemukan.');
            }
            if ($targetUser['role'] === 'admin' && (int) $targetUser['status_aktif'] === 1) {
                $activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM tb_user WHERE role = 'admin' AND status_aktif = 1")->fetchColumn();
                if ($activeAdmins <= 1) {
                    throw new InvalidArgumentException('Minimal satu akun admin aktif harus tersedia.');
                }
            }
            $nextStatus = (int) $targetUser['status_aktif'] === 1 ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE tb_user SET status_aktif = ? WHERE id_user = ?');
            $stmt->execute([$nextStatus, $id]);
            catatLog($pdo, (int) $_SESSION['user_id'], "Mengubah status pengguna ID {$id}");
            setFlashMessage('success', 'Status pengguna berhasil diperbarui.');
        }
    } catch (InvalidArgumentException $exception) {
        setFlashMessage('error', $exception->getMessage());
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            setFlashMessage('error', 'Username tersebut sudah digunakan.');
        } else {
            throw $exception;
        }
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query('SELECT id_user, nama_lengkap, username, role, status_aktif, dibuat_pada FROM tb_user ORDER BY dibuat_pada DESC')->fetchAll();
$page_title = 'Pengguna';
$page_subtitle = 'Atur akun dan hak akses pengguna sistem.';
include '../includes/header.php';
?>
<?php tampilkanFlashMessage(); ?>
<section class="card">
    <div class="card-body p-0">
        <div class="section-heading px-4 pt-4"><div><h2>Daftar pengguna</h2><p><?= count($users) ?> akun terdaftar di ParkirPro.</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUser"><i class="bi bi-plus-lg me-1"></i> Tambah pengguna</button></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Pengguna</th><th>Username</th><th>Peran</th><th>Status</th><th>Bergabung</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($users as $user): ?><tr>
                <td><div class="d-flex align-items-center gap-2"><span class="avatar" style="width:30px;height:30px;font-size:11px"><?= e(strtoupper(substr($user['nama_lengkap'], 0, 1))) ?></span><strong><?= e($user['nama_lengkap']) ?></strong></div></td>
                <td><?= e($user['username']) ?></td>
                <td><span class="badge badge-soft-muted"><?= e(ucfirst($user['role'])) ?></span></td>
                <td><span class="badge <?= $user['status_aktif'] ? 'badge-soft-success' : 'badge-soft-warning' ?>"><?= $user['status_aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td><?= e(formatTanggalIndo($user['dibuat_pada'])) ?></td>
                <td><?php if ((int) $user['id_user'] !== (int) $_SESSION['user_id']): ?><form method="post"><?= csrfField() ?><input type="hidden" name="id_user" value="<?= (int) $user['id_user'] ?>"><button class="btn btn-sm <?= $user['status_aktif'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" name="toggle_status" value="1" type="submit"><?= $user['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php else: ?><span class="text-muted">Akun Anda</span><?php endif; ?></td>
            </tr><?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="6" class="table-empty">Belum ada pengguna.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</section>
<div class="modal fade" id="addUser" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content"><?= csrfField() ?><div class="modal-header"><div><h2 class="modal-title fs-6 fw-bold">Tambah pengguna</h2><p class="card-subtitle mb-0">Buat akun akses baru.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body">
    <div class="mb-3"><label class="form-label" for="name">Nama lengkap</label><input class="form-control" id="name" name="nama_lengkap" maxlength="120" required></div>
    <div class="mb-3"><label class="form-label" for="new-username">Username</label><input class="form-control" id="new-username" name="username" maxlength="60" autocomplete="off" required></div>
    <div class="mb-3"><label class="form-label" for="new-password">Kata sandi</label><input class="form-control" id="new-password" type="password" name="password" minlength="8" required><div class="form-text">Gunakan minimal 8 karakter.</div></div>
    <div><label class="form-label" for="role">Peran akses</label><select class="form-select" id="role" name="role" required><option value="petugas">Petugas</option><option value="admin">Admin</option><option value="owner">Owner</option></select></div>
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" name="tambah" value="1">Simpan pengguna</button></div></form></div></div>
<?php include '../includes/footer.php'; ?>
