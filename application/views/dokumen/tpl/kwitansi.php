<?php
/**
 * Kwitansi perjalanan dinas (LS). $d = payload.
 * Layout mengikuti form kantor Balai Besar POM di Pangkal Pinang (berkas 2026).
 * Area tanda tangan (.ds-sign) & cap dipasang belakangan oleh modul TTD.
 */
$rincian = isset($d['rincian']) && is_array($d['rincian']) ? $d['rincian'] : array();
$sum = 0;
foreach ($rincian as $r) {
	$sum += (float) preg_replace('/[^0-9.]/', '', isset($r['jumlah']) ? $r['jumlah'] : 0);
}
$nilai = ($d['jumlah'] !== '' && $d['jumlah'] !== null) ? (float) preg_replace('/[^0-9.]/', '', $d['jumlah']) : $sum;
$total = $sum ?: $nilai;
$tempat = $d['tempat'] ?: 'Pangkalpinang';
$tgl    = $d['tanggal'] ? tgl_ind($d['tanggal']) : '&hellip;&hellip;&hellip;';
?>
<div class="kw">

  <table class="kw-head">
    <tr>
      <td class="kw-kop">
        <div>BADAN POM RI</div>
        <div><strong>BALAI PENGAWAS OBAT DAN MAKANAN</strong></div>
        <div>DI PANGKALPINANG</div>
      </td>
      <td class="kw-nob">
        <table>
          <tr><td>Nomor Bukti</td><td>: <?php echo dok_e($d['nomor_bukti']) ?></td></tr>
          <tr><td>Kode MAK</td><td>: <?php echo dok_e($d['kode_mak'] ?: '—') ?></td></tr>
        </table>
      </td>
    </tr>
  </table>

  <h3 class="kw-title">KWITANSI</h3>

  <table class="kw-form">
    <tr><td class="kw-l">SUDAH TERIMA DARI</td><td class="kw-s">:</td><td><?php echo dok_e($d['terima_dari']) ?></td></tr>
    <tr><td class="kw-l">UANG SEBESAR</td><td class="kw-s">:</td><td><strong><?php echo dok_e(terbilang_rupiah($nilai)) ?></strong></td></tr>
    <tr><td class="kw-l">Guna pembayaran ongkos/biaya perjalanan</td><td class="kw-s">:</td><td><?php echo nl2br(dok_e($d['guna'])) ?></td></tr>
  </table>

  <p class="kw-sp">Surat Perintah dari Kepala Balai Pengawas Obat dan Makanan di Pangkalpinang</p>

  <table class="kw-form kw-form2">
    <tr>
      <td class="kw-l">Tanggal :</td>
      <td><?php echo $d['tgl_surat_tugas'] ? dok_e(tgl_ind($d['tgl_surat_tugas'])) : '' ?></td>
      <td class="kw-l">Nomor :</td>
      <td><?php echo dok_e($d['no_surat_tugas']) ?></td>
    </tr>
    <tr>
      <td class="kw-l">Untuk Perjalanan dinas dari :</td>
      <td colspan="3"><?php echo dok_e($d['untuk_perjalanan_dari']) ?></td>
    </tr>
  </table>

  <div class="kw-terbilang">TERBILANG&nbsp; <span><?php echo dok_e(rupiah($nilai)) ?></span></div>

  <div class="kw-lunas">TELAH DIBAYAR LUNAS</div>

  <table class="kw-ttd" style="width:100%;">
    <tr>
      <td style="width:33%;">TANGGAL :</td>
      <td style="width:33%;"></td>
      <td style="width:34%;" class="kw-r"><?php echo dok_e($tempat) ?>, <?php echo $tgl ?></td>
    </tr>
    <tr>
      <td style="width:33%;">Bendahara Pengeluaran</td>
      <td style="width:33%; text-align:center;">Pejabat Pembuat Komitmen</td>
      <td style="width:34%;" class="kw-r">Yang menerima,</td>
    </tr>
    <tr>
      <td><div class="ds-sign" data-slot="bendahara"></div></td>
      <td style="text-align:center;"><div class="ds-sign" data-slot="ppk" data-cap="1"></div></td>
      <td class="kw-r"><div class="ds-sign" data-slot="penerima"></div></td>
    </tr>
    <tr>
      <td class="ds-name"><?php echo dok_e(!empty($d['bendahara_nama']) ? $d['bendahara_nama'] : 'Desy Anindyasari, A.Md') ?></td>
      <td style="text-align:center;" class="ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></td>
      <td class="kw-r ds-name"><?php echo dok_e($d['penerima_nama'] ?: '—') ?></td>
    </tr>
    <tr>
      <td class="ds-nip">NIP. <?php echo dok_e(!empty($d['bendahara_nip']) ? $d['bendahara_nip'] : '198512022008122002') ?></td>
      <td style="text-align:center;" class="ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></td>
      <td class="kw-r ds-nip">NIP. <?php echo dok_e($d['penerima_nip']) ?></td>
    </tr>
  </table>

  <p class="kw-lampiran">Lampiran : PERATURAN MENTERI KEUANGAN NOMOR 45/PMK.05/2007 TENTANG PERJALANAN DINAS JABATAN DALAM NEGERI BAGI PEJABAT NEGARA, PEGAWAI NEGERI DAN PEGAWAI TIDAK TETAP</p>

  <div class="kw-subttl">RINCIAN BIAYA PERJALANAN DINAS</div>
  <table class="kw-tbl">
    <thead>
      <tr><th style="width:30px">NO</th><th>U R A I A N</th><th style="width:130px">JUMLAH</th><th style="width:150px">KETERANGAN</th></tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($rincian as $r): if (!array_filter($r)) continue;
        $uraian = trim((string) (isset($r['uraian']) ? $r['uraian'] : ''));
        $tarif  = isset($r['tarif']) && $r['tarif'] !== '' ? rupiah($r['tarif']) : '';
        $per    = trim((string) (isset($r['per']) ? $r['per'] : ''));
      ?>
      <tr>
        <td class="kw-c"><?php echo $i++ ?></td>
        <td>
          <?php echo dok_e($uraian) ?>
          <?php if ($tarif !== ''): ?><span class="kw-tarif"><?php echo dok_e($tarif) ?><?php echo $per !== '' ? ' /' . dok_e($per) : '' ?></span><?php endif ?>
        </td>
        <td class="kw-num"><?php echo isset($r['jumlah']) && $r['jumlah'] !== '' ? dok_e(rupiah($r['jumlah'])) : '' ?></td>
        <td><?php echo dok_e(isset($r['ket']) ? $r['ket'] : '') ?></td>
      </tr>
      <?php endforeach ?>
      <?php if (!array_filter($rincian, 'array_filter')): ?>
      <tr><td colspan="4" class="kw-c kw-muted">(belum ada rincian)</td></tr>
      <?php endif ?>
      <tr class="kw-total">
        <td colspan="2" class="kw-r">Jumlah&hellip;&hellip;&hellip;</td>
        <td class="kw-num"><?php echo dok_e(rupiah($total)) ?></td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <table class="kw-bayar">
    <tr>
      <td><?php echo dok_e($tempat) ?>,</td>
      <td class="kw-r"><?php echo dok_e($tempat) ?>,</td>
    </tr>
    <tr>
      <td>Telah dibayar sejumlah <strong><?php echo dok_e(rupiah($total)) ?></strong></td>
      <td class="kw-r">Telah menerima jumlah uang sebesar <strong><?php echo dok_e(rupiah($total)) ?></strong></td>
    </tr>
    <tr>
      <td><em><?php echo dok_e(terbilang_rupiah($total)) ?></em></td>
      <td class="kw-r"><em><?php echo dok_e(terbilang_rupiah($total)) ?></em></td>
    </tr>
  </table>

  <table class="kw-ttd kw-ttd2">
    <tr>
      <td><?php echo dok_e($tempat) ?>,</td>
      <td class="kw-r">Dengan catatan bahwa tarif satuan biaya tsb.<br>di atas saya tidak akan mengajukan klaim lagi</td>
    </tr>
    <tr>
      <td>Pejabat Pembuat Komitmen</td>
      <td class="kw-r">Yang menerima,</td>
    </tr>
    <tr>
      <td><div class="ds-sign" data-slot="ppk" data-cap="1"></div></td>
      <td class="kw-r"><div class="ds-sign" data-slot="penerima"></div></td>
    </tr>
    <tr>
      <td class="ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></td>
      <td class="kw-r ds-name"><?php echo dok_e($d['penerima_nama'] ?: '—') ?></td>
    </tr>
    <tr>
      <td class="ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></td>
      <td class="kw-r ds-nip">NIP. <?php echo dok_e($d['penerima_nip']) ?></td>
    </tr>
  </table>

  <div class="kw-rampung">Perhitungan SPPD rampung</div>
  <table class="kw-form kw-rampung-tbl">
    <tr>
      <td class="kw-l"><?php echo dok_e($tempat) ?><br>Pejabat yang berwenang / yang ditunjuk</td>
      <td>Ditetapkan sejumlah</td><td class="kw-r2">Rp.</td>
    </tr>
    <tr>
      <td></td>
      <td>Yang telah dibayarkan semua</td><td class="kw-r2">Rp.</td>
    </tr>
    <tr>
      <td></td>
      <td>Selisih kurang/lebih</td><td class="kw-r2">Rp.</td>
    </tr>
  </table>

</div>
