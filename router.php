<?php
// Router sementara untuk `php -S` (demo lokal). Hapus jika tidak dipakai.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$abs = __DIR__ . urldecode($uri);
if ($uri !== '/' && file_exists($abs) && !is_dir($abs)) {
    return false; // biar server bawaan yang kirim file statis (css/js/img)
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php' . $uri;
require __DIR__ . '/index.php';
