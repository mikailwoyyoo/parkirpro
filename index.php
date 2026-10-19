<?php
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . roleHome($_SESSION['role'] ?? ''));
} else {
    header('Location: login.php');
}
exit;
