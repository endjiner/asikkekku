<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file). Front controller ada di ROOT
// repo (bukan public/) supaya cocok dengan hosting bersama yang document
// root-nya sudah terpasang ke akun (tidak bisa diarahkan ke sub-folder) --
// sama seperti deployment CI3 sebelumnya. Lihat .htaccess untuk proteksi
// akses langsung ke app/, writable/, vendor/, dan berkas sensitif lainnya.
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 * Diporting apa adanya dari index.php CI3: prioritas CI_ENV eksplisit ->
 * deteksi host lokal -> production (aman). Didefinisikan SEBELUM Boot
 * CI4 berjalan, supaya CodeIgniter\Boot::defineEnvironment() (yang hanya
 * menyetel ENVIRONMENT kalau belum ada) memakai nilai ini.
 */
if (isset($_SERVER['CI_ENV']) && in_array($_SERVER['CI_ENV'], ['development', 'testing', 'production'], true)) {
    define('ENVIRONMENT', $_SERVER['CI_ENV']);
} else {
    $__h = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
    $__local = ($__h === '' || strpos($__h, 'localhost') === 0 || strpos($__h, '127.0.0.1') === 0
        || strpos($__h, '192.168.') === 0 || strpos($__h, '::1') === 0 || PHP_SAPI === 'cli');
    define('ENVIRONMENT', $__local ? 'development' : 'production');
    unset($__h, $__local);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// Front controller ada di root repo, jadi app/Config/Paths.php ada SATU
// tingkat DI BAWAH (bukan di atas seperti skeleton public/index.php bawaan).
require FCPATH . 'app/Config/Paths.php';

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
