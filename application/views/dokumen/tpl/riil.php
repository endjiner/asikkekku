<?php
/**
 * DAFTAR PENGELUARAN RIIL. $d = payload.
 * Layout mengikuti berkas kantor BBPOM Pangkal Pinang (foto 2026).
 */
$rincian = isset($d['rincian']) && is_array($d['rincian']) ? array_values(array_filter($d['rincian'], 'array_filter')) : array();
$total = 0;
foreach ($rincian as $r) { $total += (float) preg_replace('/[^0-9.]/', '', isset($r['jumlah']) ? $r['jumlah'] : 0); }
?>
<div class="rl">
  <div class="rl-kop">
    <div>BADAN POM RI</div>
    <div><strong>BALAI BESAR PENGAWAS OBAT DAN MAKANAN</strong></div>
    <div>DI PANGKAL PINANG</div>
    <div class="rl-kop-line"></div>
  </div>

  <h3 class="rl-title">DAFTAR PENGELUARAN RIIL</h3>

  <p class="rl-p">Yang bertanda tangan dibawah ini :</p>
  <table class="rl-form">
    <tr><td class="rl-l">N a m a</td><td>: <?php echo dok_e($d['nama'] ?: '—') ?></td></tr>
    <tr><td class="rl-l">N I P</td><td>: <?php echo dok_e($d['nip']) ?></td></tr>
    <tr><td class="rl-l">Jabatan</td><td>: <?php echo dok_e($d['jabatan']) ?></td></tr>
  </table>

  <p class="rl-p">Berdasarkan Surat Perintah Perjalanan Dinas/Surat Tugas Tanggal
    <?php echo $d['spd_tgl'] ? dok_e(tgl_ind($d['spd_tgl'])) : '&hellip;' ?> Nomor :
    <?php echo dok_e($d['spd_no']) ?> dengan ini kami menyatakan dengan sesungguhnya bahwa :</p>

  <ol class="rl-ol">
    <li>Biaya transport pegawai dan /atau biaya penginapan di bawah ini yang tidak dapat
        diperoleh bukti-bukti pengeluarannya, meliputi :</li>
  </ol>

  <table class="rl-tbl">
    <thead><tr><th style="width:34px">NO</th><th>URAIAN</th><th style="width:130px">Jumlah</th><th style="width:150px">Keterangan</th></tr></thead>
    <tbody>
      <?php $i = 1; foreach ($rincian as $r): ?>
      <tr>
        <td class="rl-c"><?php echo $i++ ?></td>
        <td><?php echo dok_e(isset($r['uraian']) ? $r['uraian'] : '') ?></td>
        <td class="rl-num"><?php echo (isset($r['jumlah']) && $r['jumlah'] !== '') ? dok_e(rupiah($r['jumlah'])) : '' ?></td>
        <td><?php echo dok_e(isset($r['ket']) ? $r['ket'] : '') ?></td>
      </tr>
      <?php endforeach ?>
      <?php if (!$rincian): ?><tr><td colspan="4" class="rl-c rl-muted">(belum ada rincian)</td></tr><?php endif ?>
      <tr class="rl-total"><td colspan="2" class="rl-r">Jumlah</td><td class="rl-num"><?php echo dok_e(rupiah($total)) ?></td><td></td></tr>
    </tbody>
  </table>

  <ol class="rl-ol" start="2">
    <li>Jumlah uang tersebut pada angka 1 diatas benar-benar dikeluarkan untuk pelaksanaan
        perjalanan dinas dimaksud dan apabila dikemudian hari terdapat kelebihan atas pembayaran,
        kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.</li>
  </ol>

  <p class="rl-p">Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan
     sebagaimana mestinya.</p>

  <table class="rl-ttd">
    <tr>
      <td>Mengetahui/Menyetujui<br>Pejabat Pembuat Komitmen</td>
      <td class="rl-r"><?php echo dok_e($d['tempat']) ?>, <?php echo dok_e($d['bulan_tahun']) ?><br>Pejabat Negara /Pegawai Negeri yang<br>melakukan Perjalanan Dinas</td>
    </tr>
    <tr>
      <td><div class="ds-sign" data-slot="ppk" data-cap="1"></div></td>
      <td class="rl-r"><div class="ds-sign" data-slot="penerima"></div></td>
    </tr>
    <tr>
      <td class="ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></td>
      <td class="rl-r ds-name"><?php echo dok_e($d['nama'] ?: '—') ?></td>
    </tr>
    <tr>
      <td class="ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></td>
      <td class="rl-r ds-nip">NIP. <?php echo dok_e($d['nip']) ?></td>
    </tr>
  </table>
</div>
