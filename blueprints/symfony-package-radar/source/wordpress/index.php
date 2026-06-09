<?php
if (isset($_GET['playground-redirection-handler'], $_GET['next'])) {
    header('Location: ' . $_GET['next'], true, 302);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$target = '/symfony-package-radar/public/index.php';
if ($path !== '/' && !str_starts_with($path, '/symfony-package-radar/')) {
    $target .= $path;
}

header('Location: ' . $target, true, 302);
