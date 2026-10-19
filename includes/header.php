<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$page_title = $page_title ?? 'Dashboard';
$page_subtitle = $page_subtitle ?? 'Ringkasan operasional parkir hari ini.';
$page_class = $page_class ?? '';
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title><?= e($page_title) ?> · ParkirPro</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="app-main">
        <header class="topbar">
            <button class="icon-button sidebar-toggle" type="button" aria-label="Buka navigasi" data-sidebar-toggle>
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-heading">
                <span class="eyebrow"><?= e(ucfirst($_SESSION['role'] ?? '')) ?> workspace</span>
                <h1><?= e($page_title) ?></h1>
            </div>
            <div class="topbar-user">
                <div class="avatar"><?= e(strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1))) ?></div>
                <div class="user-meta">
                    <strong><?= e($_SESSION['nama_lengkap'] ?? 'Pengguna') ?></strong>
                    <span><?= e(ucfirst($_SESSION['role'] ?? '')) ?></span>
                </div>
                <a class="icon-button" href="../change-password.php" aria-label="Ganti kata sandi" title="Ganti kata sandi">
                    <i class="bi bi-key"></i>
                </a>
                <a class="icon-button logout-button" href="../logout.php" aria-label="Keluar" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </header>
        <main class="page-content <?= e($page_class) ?>">
            <div class="page-intro">
                <div>
                    <p class="page-kicker"><?= e(formatHariTanggalIndo(date('Y-m-d'))) ?></p>
                    <p class="page-description"><?= e($page_subtitle) ?></p>
                </div>
            </div>
