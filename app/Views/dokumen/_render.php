<?php
/**
 * Dispatcher pratinjau/cetak. $kode, $tpl, $d (payload tergabung), $kegiatan.
 * Opsional: $ttd (map slot => baris tb_dokumen_ttd, + '_cap_path'), $tampilkan_ttd (bool, default true).
 * Menghasilkan fragmen .doc-sheet (dipakai di panel pratinjau, halaman cetak, PDF).
 */
if (!function_exists('tgl_ind')) { helper('dokumen'); }

$d = isset($d) && is_array($d) ? $d : array();
if (!empty($tpl['fields'])) {
	foreach ($tpl['fields'] as $__f) {
		if (!array_key_exists($__f['key'], $d)) {
			$d[$__f['key']] = ($__f['tipe'] === 'rows') ? array() : '';
		}
	}
}

$ttd = isset($ttd) && is_array($ttd) ? $ttd : array();
$tampilkan_ttd = isset($tampilkan_ttd) ? (bool) $tampilkan_ttd : true;

$file = APPPATH . 'Views/dokumen/tpl/' . preg_replace('/[^a-z_]/', '', $kode) . '.php';

ob_start();
if (is_file($file)) { include $file; } else { echo '<p>Template tidak ditemukan.</p>'; }
$html = ob_get_clean();

// Tempel gambar tanda tangan + cap ke area .ds-sign[data-slot=...].
// Nama berkas ttd/cap sudah disanitasi (a-zA-Z0-9_-) -> aman langsung di URL.
if ($tampilkan_ttd && !empty($ttd)) {
	$base = base_url();
	$capPath = !empty($ttd['_cap_path']) ? $ttd['_cap_path'] : '';
	$html = preg_replace_callback(
		'#<div class="ds-sign"([^>]*)></div>#',
		function ($m) use ($ttd, $base, $capPath) {
			$attr = $m[1];
			if (!preg_match('/data-slot="([a-z0-9_]+)"/', $attr, $s)) return $m[0];
			$slot = $s[1];
			$signed = !empty($ttd[$slot]['ImagePath']);
			$inner = '';
			if ($signed) {
				$inner .= '<img class="ds-sign-img" src="' . $base . $ttd[$slot]['ImagePath'] . '" alt="ttd">';
			}
			// Cap dinas hanya menindih tanda tangan PPK yang SUDAH dibubuhkan.
			if ($signed && $capPath !== '' && preg_match('/data-cap="1"/', $attr)) {
				$inner .= '<img class="ds-cap-img" src="' . $base . $capPath . '" alt="cap">';
			}
			return $inner === '' ? $m[0] : '<div class="ds-sign"' . $attr . '>' . $inner . '</div>';
		},
		$html
	);
}
?>
<div class="doc-sheet<?php echo $tampilkan_ttd ? '' : ' doc-sheet-notte' ?>">
  <?php if (!$tampilkan_ttd): ?><div class="ds-watermark">SALINAN TANPA TANDA TANGAN</div><?php endif ?>
  <?php echo $html ?>
</div>
