<?php
/**
 * DAFTAR NOMINATIF BIAYA PERJALANAN DINAS. $d = payload, $kegiatan = data kegiatan.
 * Layout mengikuti berkas kantor BBPOM Pangkal Pinang (foto 2026).
 */
$baris = isset($d['baris']) && is_array($d['baris']) ? array_values(array_filter($d['baris'], 'array_filter')) : array();

// Prefill dari daftar pelaksana bila tabel masih kosong.
if (!$baris && !empty($kegiatan['_pelaksana_rows'])) {
	foreach ($kegiatan['_pelaksana_rows'] as $p) {
		$baris[] = array(
			'nama' => isset($p['Nama']) ? $p['Nama'] : '',
			'nip'  => isset($p['NIP']) ? $p['NIP'] : '',
			'gol'  => isset($p['Gol']) ? $p['Gol'] : '',
			'tujuan' => isset($d['asal_tujuan']) ? $d['asal_tujuan'] : '',
			'tgl_brgkt' => '', 'lama' => isset($d['jml_hari']) ? $d['jml_hari'] : '', 'jumlah' => '', 'ket' => '',
		);
	}
}
$total = 0;
foreach ($baris as $r) { $total += (float) preg_replace('/[^0-9.]/', '', isset($r['jumlah']) ? $r['jumlah'] : 0); }
?>
<div class="nm">
  <div class="nm-kop">
    <div>BADAN POM RI</div>
    <div><strong>BALAI PENGAWAS OBAT DAN MAKANAN</strong></div>
    <div>DI PANGKALPINANG</div>
    <div class="nm-kop-line"></div>
  </div>

  <h3 class="nm-title">DAFTAR NOMINATIF BIAYA PERJALANAN DINAS</h3>
  <p class="nm-sub">KEGIATAN <?php echo dok_e($d['kode_kegiatan'] ?: '—') ?><br>
     Tahun Anggaran <?php echo dok_e($d['tahun_anggaran']) ?></p>

  <table class="nm-tbl">
    <thead>
      <tr>
        <th rowspan="2" style="width:26px">No</th>
        <th colspan="3">Petugas yang melakukan Perjalanan</th>
        <th rowspan="2">Tujuan</th>
        <th rowspan="2" style="width:74px">Tgl Brgkt</th>
        <th colspan="2">Biaya Perjalanan</th>
        <th rowspan="2" style="width:70px">Ket</th>
      </tr>
      <tr>
        <th>Nama</th><th style="width:120px">NIP</th><th style="width:50px">Gol</th>
        <th style="width:50px">Lama</th><th style="width:110px">Jumlah</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($baris as $r): ?>
      <tr>
        <td class="nm-c"><?php echo $i++ ?></td>
        <td><?php echo dok_e(isset($r['nama']) ? $r['nama'] : '') ?></td>
        <td><?php echo dok_e(isset($r['nip']) ? $r['nip'] : '') ?></td>
        <td class="nm-c"><?php echo dok_e(isset($r['gol']) ? $r['gol'] : '') ?></td>
        <td><?php echo dok_e(isset($r['tujuan']) ? $r['tujuan'] : '') ?></td>
        <td class="nm-c"><?php echo dok_e(isset($r['tgl_brgkt']) ? $r['tgl_brgkt'] : '') ?></td>
        <td class="nm-c"><?php echo dok_e(isset($r['lama']) ? $r['lama'] : '') ?></td>
        <td class="nm-num"><?php echo (isset($r['jumlah']) && $r['jumlah'] !== '') ? dok_e(number_format((float) preg_replace('/[^0-9.]/', '', $r['jumlah']), 0, ',', '.')) : '' ?></td>
        <td><?php echo dok_e(isset($r['ket']) ? $r['ket'] : '') ?></td>
      </tr>
      <?php endforeach ?>
      <?php if (!$baris): ?><tr><td colspan="9" class="nm-c nm-muted">(belum ada data)</td></tr><?php endif ?>
      <tr class="nm-total">
        <td colspan="7" class="nm-r">JUMLAH</td>
        <td class="nm-num"><?php echo dok_e(number_format($total, 0, ',', '.')) ?></td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <table class="nm-ttd">
    <tr><td></td><td class="nm-r"><?php echo dok_e($d['tempat']) ?>,</td></tr>
    <tr><td></td><td class="nm-r">Pejabat Pembuat Komitmen</td></tr>
    <tr><td></td><td class="nm-r"><div class="ds-sign" data-slot="ppk" data-cap="1"></div></td></tr>
    <tr><td></td><td class="nm-r ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></td></tr>
    <tr><td></td><td class="nm-r ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></td></tr>
  </table>
</div>
