<?php
/**
 * SURAT PERNYATAAN TANGGUNG JAWAB BELANJA (SPTJB). $d = payload.
 * Layout mengikuti berkas kantor BBPOM Pangkal Pinang (foto 2026).
 */
$baris = isset($d['baris']) && is_array($d['baris']) ? array_values(array_filter($d['baris'], 'array_filter')) : array();
$tot = 0; $totPpn = 0; $totPph = 0;
foreach ($baris as $r) {
	$tot    += (float) preg_replace('/[^0-9.]/', '', isset($r['jumlah']) ? $r['jumlah'] : 0);
	$totPpn += (float) preg_replace('/[^0-9.]/', '', isset($r['ppn']) ? $r['ppn'] : 0);
	$totPph += (float) preg_replace('/[^0-9.]/', '', isset($r['pph']) ? $r['pph'] : 0);
}
$n0 = function ($v) { return $v ? number_format((float) $v, 0, ',', '.') : '-'; };
?>
<div class="sp">
  <h3 class="sp-title">SURAT PERNYATAAN TANGGUNG JAWAB BELANJA</h3>
  <p class="sp-nomor">Nomor : <?php echo dok_e($d['nomor']) ?></p>

  <table class="sp-head">
    <tr><td class="sp-n">1</td><td class="sp-k">Kode Satker / Program</td><td>: <?php echo dok_e($d['kode_satker']) ?></td></tr>
    <tr><td class="sp-n">2</td><td class="sp-k">Nama Satuan Kerja</td><td>: <?php echo dok_e($d['nama_satker']) ?></td></tr>
    <tr><td class="sp-n">3</td><td class="sp-k">Tanggal dan No DIPA</td><td>: <?php echo dok_e($d['dipa_tgl_no']) ?></td></tr>
    <tr><td class="sp-n">4</td><td class="sp-k">Klasifikasi Anggaran</td><td>: <?php echo dok_e($d['klasifikasi_anggaran']) ?></td></tr>
  </table>

  <p class="sp-p">Yang bertanda tangan di bawah ini Kuasa Pengguna Anggaran Satuan Kerja Balai
     POM di Pangkalpinang menyatakan bahwa saya bertanggung jawab secara formal dan
     material dan kebenaran perhitungan pemungutan pajak atas segala pembayaran tagihan yang
     telah kami perintahkan dalam SPM ini dengan perincian sebagai berikut :</p>

  <table class="sp-tbl">
    <thead>
      <tr>
        <th rowspan="2" style="width:26px">NO<br><span class="sp-lbl">a</span></th>
        <th rowspan="2" style="width:80px">AKUN<br><span class="sp-lbl">b</span></th>
        <th rowspan="2">PENERIMA<br><span class="sp-lbl">c</span></th>
        <th rowspan="2">U R A I A N<br><span class="sp-lbl">d</span></th>
        <th rowspan="2" style="width:100px">JUMLAH<br><span class="sp-lbl">e</span></th>
        <th colspan="2">Pajak yang dipungut</th>
      </tr>
      <tr><th style="width:80px">P.P.N<br><span class="sp-lbl">f</span></th><th style="width:80px">P.Ph<br><span class="sp-lbl">g</span></th></tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($baris as $r): ?>
      <tr>
        <td class="sp-c"><?php echo $i++ ?></td>
        <td class="sp-c"><?php echo dok_e(isset($r['akun']) ? $r['akun'] : '') ?></td>
        <td><?php echo dok_e(isset($r['penerima']) ? $r['penerima'] : '') ?></td>
        <td><?php echo dok_e(isset($r['uraian']) ? $r['uraian'] : '') ?></td>
        <td class="sp-num"><?php echo $n0(isset($r['jumlah']) ? preg_replace('/[^0-9.]/', '', $r['jumlah']) : 0) ?></td>
        <td class="sp-num"><?php echo $n0(isset($r['ppn']) ? preg_replace('/[^0-9.]/', '', $r['ppn']) : 0) ?></td>
        <td class="sp-num"><?php echo $n0(isset($r['pph']) ? preg_replace('/[^0-9.]/', '', $r['pph']) : 0) ?></td>
      </tr>
      <?php endforeach ?>
      <?php if (!$baris): ?><tr><td colspan="7" class="sp-c sp-muted">(belum ada rincian)</td></tr><?php endif ?>
      <tr class="sp-total">
        <td colspan="4" class="sp-r"></td>
        <td class="sp-num"><?php echo $n0($tot) ?></td>
        <td class="sp-num"><?php echo $n0($totPpn) ?></td>
        <td class="sp-num"><?php echo $n0($totPph) ?></td>
      </tr>
    </tbody>
  </table>

  <p class="sp-p">Bukti-bukti pengeluaran anggaran dan asli setoran pajak ( S.S.P/B.P.N )
     tersebut di atas disimpan oleh Pengguna Anggaran / Kuasa Pengguna anggaran untuk
     kelengkapan administrasi dan pemeriksaan aparat pengawasan fungsional.</p>
  <p class="sp-p">Demikian Surat Pernyataan ini dibuat dengan sebenarnya.</p>

  <table class="sp-ttd">
    <tr><td></td><td class="sp-r">Pejabat Pembuat Komitmen</td></tr>
    <tr><td></td><td class="sp-r"><div class="ds-sign" data-slot="ppk" data-cap="1"></div></td></tr>
    <tr><td></td><td class="sp-r ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></td></tr>
    <tr><td></td><td class="sp-r ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></td></tr>
  </table>
</div>
