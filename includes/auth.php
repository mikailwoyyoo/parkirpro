<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function cekAkses(array $allowedRoles): void
{
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT nama_lengkap, role, status_aktif FROM tb_user WHERE id_user = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $account = $stmt->fetch();
    if (!$account || !(int) $account['status_aktif']) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ../login.php?access=inactive');
        exit;
    }

    $_SESSION['role'] = $account['role'];
    $_SESSION['nama_lengkap'] = $account['nama_lengkap'];
    if (!in_array($account['role'], $allowedRoles, true)) {
        header('Location: ../' . roleHome($account['role']));
        exit;
    }
}
