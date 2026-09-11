<?php
/**
 * SURAT PERJALANAN DINAS (SPD). $d = payload.
 * Layout mengikuti berkas kantor BBPOM Pangkal Pinang (foto 2026).
 * Halaman 1. (Halaman 2 -- grid cap tiba/berangkat -- menyusul.)
 */
$pengikut = isset($d['pengikut']) && is_array($d['pengikut']) ? array_values(array_filter($d['pengikut'], 'array_filter')) : array();
while (count($pengikut) < 5) { $pengikut[] = array('nama' => '', 'tgl_lahir' => '', 'ket' => ''); }
?>
<div class="spd">
  <table class="spd-top">
    <tr>
      <td class="spd-kop">
        BADAN PENGAWAS OBAT DAN MAKANAN RI<br>
        <strong>BALAI PENGAWAS OBAT DAN MAKANAN</strong><br>
        DI PANGKALPINANG
      </td>
      <td class="spd-meta">
        <table>
          <tr><td>Lembar Ke</td><td>: <?php echo dok_e($d['lembar_ke']) ?></td></tr>
          <tr><td>Kode</td><td>: <?php echo dok_e($d['kode']) ?></td></tr>
          <tr><td>Nomor</td><td>: <?php echo dok_e($d['nomor']) ?></td></tr>
        </table>
      </td>
    </tr>
  </table>

  <h3 class="spd-title">SURAT PERJALANAN DINAS ( SPD )</h3>

  <table class="spd-tbl">
    <tr><td class="spd-n">1</td><td class="spd-k">Pejabat Pembuat Komitmen</td>
        <td>PEJABAT PEMBUAT KOMITMEN<br>BALAI POM DI PANGKALPINANG</td></tr>
    <tr><td class="spd-n">2</td><td class="spd-k">Nama / NIP Pegawai yang melaksanakan Perjalanan Dinas</td>
        <td><?php echo dok_e($d['pegawai_nama'] ?: '—') ?><br>NIP. <?php echo dok_e($d['pegawai_nip']) ?></td></tr>
    <tr><td class="spd-n">3</td><td class="spd-k">a. Pangkat dan Golongan<br>b. Jabatan / Instansi<br>c. Tingkat Biaya Perjalanan Dinas</td>
        <td>a. <?php echo dok_e($d['pangkat_gol']) ?><br>b. <?php echo dok_e($d['jabatan']) ?><br>c. &mdash;</td></tr>
    <tr><td class="spd-n">4</td><td class="spd-k">Maksud Perjalanan Dinas</td>
        <td><?php echo nl2br(dok_e($d['maksud'])) ?></td></tr>
    <tr><td class="spd-n">5</td><td class="spd-k">Alat angkutan yang dipergunakan</td>
        <td><?php echo dok_e($d['alat_angkut'] ?: 'Kendaraan Umum') ?></td></tr>
    <tr><td class="spd-n">6</td><td class="spd-k">a. Tempat berangkat<br>b. Tempat tujuan</td>
        <td>a. <?php echo dok_e($d['tempat_berangkat']) ?><br>b. <?php echo dok_e($d['tempat_tujuan']) ?></td></tr>
    <tr><td class="spd-n">7</td><td class="spd-k">a. Lamanya Perjalanan Dinas<br>b. Tanggal berangkat<br>c. Tanggal harus kembali / tiba di tempat baru *)</td>
        <td>a. <?php echo dok_e($d['lama_hari']) ?> hari<br>b. <?php echo $d['tgl_berangkat'] ? dok_e(tgl_ind($d['tgl_berangkat'])) : '' ?><br>c. <?php echo $d['tgl_kembali'] ? dok_e(tgl_ind($d['tgl_kembali'])) : '' ?></td></tr>
    <tr><td class="spd-n">8</td><td class="spd-k">Pengikut :</td>
        <td>
          <table class="spd-peng">
            <tr><th style="width:24px">No</th><th>Nama</th><th style="width:110px">Tanggal Lahir</th><th style="width:110px">Keterangan</th></tr>
            <?php foreach ($pengikut as $k => $p): ?>
            <tr><td class="spd-c"><?php echo $k + 1 ?></td><td><?php echo dok_e($p['nama']) ?></td><td><?php echo dok_e($p['tgl_lahir']) ?></td><td><?php echo dok_e($p['ket']) ?></td></tr>
            <?php endforeach ?>
          </table>
        </td></tr>
    <tr><td class="spd-n">9</td><td class="spd-k">Pembebanan Anggaran :<br>a. Instansi<br>b. Akun</td>
        <td>a. <?php echo dok_e($d['pembebanan_instansi']) ?><br>&nbsp;&nbsp;&nbsp;Nomor : <?php echo dok_e($d['dipa_no']) ?><br>b. <?php echo dok_e($d['kode_anggaran']) ?></td></tr>
    <tr><td class="spd-n">10</td><td class="spd-k">Keterangan Lain-lain</td>
        <td>Nomor Surat Tugas : <?php echo dok_e($d['no_surat_tugas']) ?><br>Tanggal Surat Tugas : <?php echo $d['tgl_surat_tugas'] ? dok_e(tgl_ind($d['tgl_surat_tugas'])) : '' ?></td></tr>
  </table>

  <p class="spd-foot">*) coret yang tidak perlu</p>

  <table class="spd-ttd">
    <tr><td></td><td class="spd-r">Dikeluarkan di : <?php echo dok_e($d['dikeluarkan_tempat']) ?></td></tr>
    <tr><td></td><td class="spd-r">Tanggal : <?php echo $d['dikeluarkan_tgl'] ? dok_e(tgl_ind($d['dikeluarkan_tgl'])) : '' ?></td></tr>
    <tr><td></td><td class="spd-r">Pejabat Pembuat Komitmen</td></tr>
    <tr><td></td><td class="spd-r"><div class="ds-sign" data-slot="ppk"></div></td></tr>
    <tr><td></td><td class="spd-r ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></td></tr>
    <tr><td></td><td class="spd-r ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></td></tr>
  </table>

  <!-- ===================== HALAMAN 2 : grid cap tiba/berangkat ===================== -->
  <div class="spd-pb"></div>
  <table class="spd-p2">
    <tr>
      <td class="spd-p2-l">
        <div>Berangkat dari</div>
        <div>(Tempat Kedudukan) : <strong><?php echo dok_e($d['tempat_berangkat']) ?></strong></div>
        <div>Ke : <strong><?php echo dok_e($d['tempat_tujuan']) ?></strong></div>
        <div>Pada Tanggal : <?php echo $d['tgl_berangkat'] ? dok_e(tgl_ind($d['tgl_berangkat'])) : '' ?></div>
      </td>
      <td class="spd-p2-r">
        <div>Pejabat Pembuat Komitmen</div>
        <div class="spd-p2-box"></div>
        <div class="ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></div>
        <div class="ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></div>
      </td>
    </tr>
    <?php for ($i = 1; $i <= 4; $i++): ?>
    <tr>
      <td class="spd-p2-l">
        <div>II. Tiba di : &hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;</div>
        <div>Pada Tanggal : &hellip;&hellip;&hellip;&hellip;</div>
        <div class="mt-1">Berangkat dari : &hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;</div>
        <div>Ke : &hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;</div>
        <div>Pada Tanggal : &hellip;&hellip;&hellip;&hellip;</div>
      </td>
      <td class="spd-p2-r">
        <div>Kepala&hellip;&hellip;&hellip;&hellip;</div>
        <div class="spd-p2-box"></div>
        <div>(&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;)</div>
      </td>
    </tr>
    <?php endfor ?>
    <tr>
      <td class="spd-p2-l">
        <div>Tiba kembali di : <strong><?php echo dok_e($d['tempat_berangkat']) ?></strong></div>
        <div>Pada Tanggal : <?php echo $d['tgl_kembali'] ? dok_e(tgl_ind($d['tgl_kembali'])) : '' ?></div>
        <div class="mt-1">Telah diperiksa dengan keterangan bahwa perjalanan tersebut atas
          perintahnya dan semata-mata untuk kepentingan jabatan dalam waktu yang sesingkat-singkatnya.</div>
      </td>
      <td class="spd-p2-r">
        <div>Pejabat Pembuat Komitmen</div>
        <div class="spd-p2-box"></div>
        <div class="ds-name"><?php echo dok_e($d['ppk_nama'] ?: '—') ?></div>
        <div class="ds-nip">NIP. <?php echo dok_e($d['ppk_nip']) ?></div>
      </td>
    </tr>
  </table>
  <div class="spd-p2-catatan"><strong>Catatan Lain-lain :</strong></div>
  <div class="spd-p2-perhatian">
    <strong>PERHATIAN :</strong>
    Pejabat Pembuat Komitmen yang menerbitkan SPD, pegawai yang melakukan perjalanan dinas,
    para pejabat yang mengesahkan tanggal berangkat/tiba, serta bendahara pengeluaran bertanggung
    jawab berdasarkan peraturan-peraturan Keuangan Negara apabila negara menderita rugi akibat
    kesalahan, kelalaian, dan kealpaannya.
  </div>
</div>
