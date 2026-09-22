<?php

namespace App\Controllers;

/**
 * Menjalankan migrasi database.
 *   php spark migrate           (dari CLI)  -- disarankan
 *   http://localhost/.../Migrate  (browser) -- hanya dari host lokal
 *
 * Di ENVIRONMENT production lewat browser: diblok, kecuali ?key= cocok
 * dengan tb_vrbl 'migrate_key'. Dari CLI selalu boleh.
 */
class Migrate extends BaseController
{
    public function index()
    {
        $isCli = is_cli();
        if (! $isCli) {
            $host  = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
            $local = (strpos($host, 'localhost') === 0 || strpos($host, '127.0.0.1') === 0);
            $row   = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'migrate_key'])->getRowArray();
            $key   = ! empty($row) ? trim((string) $row['VrblValue']) : '';
            $okKey = ($key !== '' && hash_equals($key, (string) $this->request->getGet('key')));
            if (! $local && ! $okKey) {
                show_404();

                return;
            }
        }

        $runner = service('migrations');
        try {
            $runner->latest();
            echo "Migrasi OK.\n";
        } catch (\Throwable $e) {
            echo 'Migrasi GAGAL: ' . $e->getMessage() . "\n";
        }
    }
}
