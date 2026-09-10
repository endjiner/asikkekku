<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Helper dokumen form_inapp: terbilang, format rupiah, tanggal Indonesia.
 * Dipakai template pratinjau (application/views/dokumen/tpl/*).
 */

if (!function_exists('rupiah')) {
	function rupiah($n, $prefix = 'Rp ')
	{
		$n = (float) preg_replace('/[^0-9.\-]/', '', (string) $n);
		return $prefix . number_format($n, 0, ',', '.');
	}
}

if (!function_exists('terbilang')) {
	function terbilang($n)
	{
		$n = (int) round((float) preg_replace('/[^0-9.\-]/', '', (string) $n));
		if ($n === 0) return 'nol';
		$angka = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
			'sepuluh', 'sebelas');
		$bilang = function ($x) use (&$bilang, $angka) {
			$x = (int) $x;
			if ($x < 12) return $angka[$x];
			if ($x < 20) return $bilang($x - 10) . ' belas';
			if ($x < 100) return trim($bilang(intdiv($x, 10)) . ' puluh ' . $bilang($x % 10));
			if ($x < 200) return trim('seratus ' . $bilang($x - 100));
			if ($x < 1000) return trim($bilang(intdiv($x, 100)) . ' ratus ' . $bilang($x % 100));
			if ($x < 2000) return trim('seribu ' . $bilang($x - 1000));
			if ($x < 1000000) return trim($bilang(intdiv($x, 1000)) . ' ribu ' . $bilang($x % 1000));
			if ($x < 1000000000) return trim($bilang(intdiv($x, 1000000)) . ' juta ' . $bilang($x % 1000000));
			if ($x < 1000000000000) return trim($bilang(intdiv($x, 1000000000)) . ' miliar ' . $bilang($x % 1000000000));
			return trim($bilang(intdiv($x, 1000000000000)) . ' triliun ' . $bilang($x % 1000000000000));
		};
		return preg_replace('/\s+/', ' ', trim($bilang($n)));
	}
}

if (!function_exists('terbilang_rupiah')) {
	function terbilang_rupiah($n)
	{
		return ucfirst(trim(terbilang($n))) . ' Rupiah';
	}
}

if (!function_exists('tgl_ind')) {
	function tgl_ind($date, $with_day = false)
	{
		if (empty($date)) return '';
		$ts = is_numeric($date) ? (int) $date : strtotime($date);
		if (!$ts) return htmlspecialchars((string) $date);
		$bln = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
			'Agustus', 'September', 'Oktober', 'November', 'Desember');
		$hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
		$out = (int) date('j', $ts) . ' ' . $bln[(int) date('n', $ts)] . ' ' . date('Y', $ts);
		return $with_day ? $hari[(int) date('w', $ts)] . ', ' . $out : $out;
	}
}

if (!function_exists('dok_e')) {
	/** Echo aman untuk nilai field pratinjau. */
	function dok_e($v)
	{
		return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
	}
}
