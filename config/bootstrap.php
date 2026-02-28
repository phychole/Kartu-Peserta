<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/school.php';

// === BASE URL (AUTO) ===
// Otomatis mendeteksi folder project (mis: /kpsaj) maupun di root (mis: /)
$__project_root = realpath(__DIR__ . '/..');
$__doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;

$__base = '';
if ($__doc_root && $__project_root && strpos($__project_root, $__doc_root) === 0) {
    $__base = str_replace('\\', '/', substr($__project_root, strlen($__doc_root)));
}
$__base = '/' . trim($__base, '/');
if ($__base === '/') $__base = '';

define('BASE_URL', $__base);

/**
 * Helper membuat URL relatif terhadap folder project.
 * Contoh: url('/auth/login.php')
 */
function url(string $path): string {
    if ($path === '') return BASE_URL ?: '/';
    if ($path[0] !== '/') $path = '/' . $path;
    return (BASE_URL ?: '') . $path;
}

session_name('psaj_admin');
session_start();

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . url('/auth/login.php'));
        exit;
    }
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>