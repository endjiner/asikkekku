<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menjalankan migrasi database.
 *   php index.php migrate            (dari CLI)  -- disarankan
 *   http://localhost/.../migrate     (browser)   -- hanya dari host lokal
 *
 * Di ENVIRONMENT production lewat browser: diblok, kecuali ?key= cocok
 * dengan tb_vrbl 'migrate_key'. Dari CLI selalu boleh.
 */
class Migrate extends CI_Controller
{
	public function index()
	{
		$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
		if (!$isCli) {
			$host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '';
			$local = (strpos($host, 'localhost') === 0 || strpos($host, '127.0.0.1') === 0);
			$row = $this->db->get_where('tb_vrbl', array('VrblName' => 'migrate_key'))->row_array();
			$key = !empty($row) ? trim((string) $row['VrblValue']) : '';
			$okKey = ($key !== '' && hash_equals($key, (string) $this->input->get('key')));
			if (!$local && !$okKey) { show_404(); return; }
		}

		$this->load->library('migration');
		if ($this->migration->latest() === FALSE) {
			echo 'Migrasi GAGAL: ' . $this->migration->error_string() . "\n";
		} else {
			echo 'Migrasi OK. Versi sekarang: ' . $this->migration->current() . "\n";
		}
	}
}
