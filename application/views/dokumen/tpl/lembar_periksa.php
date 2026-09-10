<?php
/**
 * CHECK LIST KELENGKAPAN DOKUMEN PENCAIRAN ANGGARAN & KARTU KENDALI (1 HALAMAN TERPADU)
 * Layout PERSIS 100% sesuai screenshot referensi fisik / Excel (media_1789006180519.png):
 * 1. Title: KARTU KENDALI PERJADIN/HONORARIUM TA 2026
 * 2. Header Info Table (Tgl/No ST, No SPTB/SPM, Judul Kegiatan, Nama Petugas Perjadin per baris grid)
 * 3. Subtitle: CHECK LIST KELENGKAPAN DOKUMEN PENCAIRAN ANGGARAN (Perjalanan Dinas / Honorarium / Kegiatan)
 * 4. Table Kelengkapan Berkas (13 items, gold header #E5A100)
 * 5. Peach Divider Row (#FCE4D6)
 * 6. Table Verifikasi Kesesuaian Isi (3 items, gold header #E5A100)
 * 7. Bottom Table: Keterangan vs ASIKKEKKU (Checklist) 7 Tahap (Lebar sejajar sampai kolom TIDAK)
 * CATATAN: Tanpa format tambahan, tanpa catatan verifikator, tanpa kotak ttd luar.
 */
defined('BASEPATH') or exit('No direct script access allowed');

$cl   = isset($tpl['checklist']) && is_array($tpl['checklist']) ? $tpl['checklist'] : array();
$val  = isset($d['items']) && is_array($d['items']) ? $d['items'] : array();
$alur = isset($d['alur_check']) && is_array($d['alur_check']) ? $d['alur_check'] : array(0, 0, 0, 0, 0, 0, 0);

// Fallback data header
$stNoTgl = !empty($d['no_surat_tugas_tgl']) ? $d['no_surat_tugas_tgl'] : (!empty($d['no_surat']) ? $d['no_surat'] : '');
$sptbSpm = !empty($d['no_sptb_spm']) ? $d['no_sptb_spm'] : '1';
$judul   = !empty($d['judul_kegiatan']) ? $d['judul_kegiatan'] : (!empty($d['judul']) ? $d['judul'] : (isset($kegiatan['KegiatanJudul']) ? $kegiatan['KegiatanJudul'] : ''));

// Daftar petugas perjadin (tiap nama menempati 1 baris grid fisik)
$petugasRaw = !empty($d['petugas_perjadin']) ? $d['petugas_perjadin'] : (!empty($d['penerima_nama']) ? $d['penerima_nama'] : '');
$petugasList = array();
if (!empty($petugasRaw)) {
    $lines = preg_split("/\r\n|\n|\r/", trim($petugasRaw));
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln !== '') $petugasList[] = $ln;
    }
}
if (empty($petugasList) && !empty($kegiatan['_pelaksana'])) {
    $petugasList = $kegiatan['_pelaksana'];
}
if (empty($petugasList)) {
    $petugasList = array('-');
}
$numPetugas = count($petugasList);

// 7 Langkah Alur ASIKKEKKU
$alurSteps = isset($tpl['alur_steps']) && is_array($tpl['alur_steps']) ? $tpl['alur_steps'] : array(
    'Petugas pelaksana kegiatan menyerahkan dokumen ke staf PPK',
    'Staf PPK menyerahkan dokumen yang telah di verifikasi ke Pembuat SPP/SPM',
    'Petugas SPP/SPM menyerahkan dokumen SPP/SPM serta data dukungnya ke Verifikator',
    'Verifikator menyerahkan berkas SPP/SPM dan data dukung yang telah di verifikasi untuk diperiksa dan di setujui PPK',
    'PPK memberikan berkas yang telah diperiksa dan Disetujui (TTE) ke PPSPM',
    'Petugas SPM membuat SPM dan menyerahkan SPM dan kelengkapan data dukung pencairan ke PPSPM untuk diperiksa dan Disetujui (TTE)',
    'PPSPM memeriksa dan Menyetujui (TTE) SPM serta mengembalikan berkas ke petugas SPM',
);
?>
<style>
.cl-wrap {
  font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
  font-size: 8.5pt;
  line-height: 1.25;
  color: #000;
  width: 100%;
  margin: 0 auto;
}
.cl-main-title {
  text-align: center;
  font-weight: bold;
  font-size: 11pt;
  margin: 0 0 6px 0;
  letter-spacing: .02em;
  text-transform: uppercase;
}
.cl-info-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 12px;
}
.cl-info-table td {
  border: 1px solid #000;
  padding: 2.5px 6px;
  font-size: 8.5pt;
  vertical-align: top;
}
.cl-info-lbl {
  width: 38%;
  font-weight: 500;
}
.cl-info-sep {
  width: 2%;
  text-align: center;
  padding: 2.5px 0 !important;
}
.cl-info-val {
  width: 60%;
}

.cl-section-title {
  text-align: center;
  font-weight: bold;
  font-size: 10pt;
  margin: 0 0 2px 0;
  text-transform: uppercase;
}
.cl-section-sub {
  font-weight: bold;
  font-size: 8.5pt;
  margin: 0 0 4px 0;
  text-align: left;
}

.cl-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 0;
}
.cl-table th, .cl-table td {
  border: 1px solid #000;
  padding: 2px 5px;
  font-size: 8.2pt;
  vertical-align: middle;
}
.cl-th-gold {
  background-color: #E5A100;
  color: #000;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  height: 20px;
}
.cl-c { text-align: center; }
.cl-spacer-row td {
  background-color: #FCE4D6;
  height: 14px;
  border: 1px solid #000;
  padding: 0;
}
.cl-check {
  font-weight: bold;
  font-size: 11pt;
  color: #000;
  display: inline-block;
  line-height: 1;
}

/* Tabel Alur ASIKKEKKU Bawah: lebar ~60% dari halaman, rata kiri, sisi kanan kosong sesuai referensi */
.cl-alur-wrap {
  width: 100%;
  margin-top: 14px;
}
.cl-alur-table {
  width: 60%;
  border-collapse: collapse;
}
.cl-alur-table th, .cl-alur-table td {
  border: 1px solid #000;
  padding: 3px 6px;
  font-size: 8pt;
  vertical-align: top;
}
.cl-alur-table th {
  font-weight: bold;
  text-align: center;
  background-color: #fff;
}
.cl-alur-table th.cl-alur-head-check,
.cl-alur-table td.cl-alur-cell-check {
  width: 110px;
  text-align: center;
  vertical-align: middle;
}

@media print {
  @page {
    size: A4 portrait;
    margin: 8mm 12mm;
  }
  .doc-sheet, .a4 {
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    width: 100% !important;
    min-height: 0 !important;
  }
  .cl-wrap {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }
  .cl-th-gold {
    background-color: #E5A100 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .cl-spacer-row td {
    background-color: #FCE4D6 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>

<div class="cl-wrap">
  <!-- 1. Header Title -->
  <div class="cl-main-title">KARTU KENDALI PERJADIN/HONORARIUM TA 2026</div>

  <!-- 2. Header Info Table -->
  <table class="cl-info-table">
    <tr>
      <td class="cl-info-lbl">Tgl. Surat / No. Surat Tugas / SK</td>
      <td class="cl-info-val">: <?php echo dok_e($stNoTgl) ?></td>
    </tr>
    <tr>
      <td class="cl-info-lbl">NO. SPTB/ SPM</td>
      <td class="cl-info-val">: <?php echo dok_e($sptbSpm) ?></td>
    </tr>
    <tr>
      <td class="cl-info-lbl">Judul Kegiatan</td>
      <td class="cl-info-val">: <?php echo nl2br(dok_e($judul)) ?></td>
    </tr>
    <tr>
      <td class="cl-info-lbl" <?php echo ($numPetugas > 1 ? 'rowspan="'.$numPetugas.'"' : '') ?>>Nama Petugas Perjadin</td>
      <td class="cl-info-val">: <?php echo dok_e(isset($petugasList[0]) ? $petugasList[0] : '-') ?></td>
    </tr>
    <?php for ($pi = 1; $pi < $numPetugas; $pi++): ?>
    <tr>
      <td class="cl-info-val">&nbsp;&nbsp;<?php echo dok_e($petugasList[$pi]) ?></td>
    </tr>
    <?php endfor; ?>
  </table>

  <!-- 3. Section Title & Subtitle -->
  <div class="cl-section-title">CHECK LIST KELENGKAPAN DOKUMEN PENCAIRAN ANGGARAN</div>
  <div class="cl-section-sub">Perjalanan Dinas / Honorarium / Kegiatan</div>

  <!-- 4. Checklist Table: Kelengkapan Berkas & Verifikasi Kesesuaian Isi -->
  <table class="cl-table">
    <thead>
      <tr>
        <th class="cl-th-gold" style="width: 32px;">No.</th>
        <th class="cl-th-gold" style="text-align: center;">KELENGKAPAN BERKAS</th>
        <th class="cl-th-gold" style="width: 55px;">ADA</th>
        <th class="cl-th-gold" style="width: 55px;">TIDAK</th>
        <th class="cl-th-gold" style="width: 220px;">KETERANGAN</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $items1 = isset($cl['kelengkapan']['item']) ? $cl['kelengkapan']['item'] : array();
      foreach ($items1 as $i => $label):
        $row = isset($val['kelengkapan'][$i]) ? $val['kelengkapan'][$i] : array();
        $st  = isset($row['status']) ? strtolower(trim($row['status'])) : '';
        $ket = isset($row['ket']) ? $row['ket'] : '';
      ?>
      <tr>
        <td class="cl-c"><?php echo $i + 1 ?></td>
        <td><?php echo dok_e($label) ?></td>
        <td class="cl-c"><?php echo ($st === 'ya' || $st === 'ada') ? '<span class="cl-check">&#10003;</span>' : '' ?></td>
        <td class="cl-c"><?php echo ($st === 'tidak') ? '<span class="cl-check">&#10003;</span>' : '' ?></td>
        <td><?php echo dok_e($ket) ?></td>
      </tr>
      <?php endforeach; ?>

      <!-- 5. Baris Pemisah Orange/Peach sesuai Referensi Fisik -->
      <tr class="cl-spacer-row">
        <td colspan="5"></td>
      </tr>

      <!-- 6. Header Seksi II: Verifikasi Kesesuaian Isi -->
      <tr>
        <th class="cl-th-gold" style="width: 32px;">No.</th>
        <th class="cl-th-gold" style="text-align: center;">VERIFIKASI KESESUAIAN ISI</th>
        <th class="cl-th-gold" style="width: 55px;">SESUAI</th>
        <th class="cl-th-gold" style="width: 55px;">TIDAK</th>
        <th class="cl-th-gold" style="width: 220px;">KETERANGAN</th>
      </tr>
      <?php
      $items2 = isset($cl['verifikasi']['item']) ? $cl['verifikasi']['item'] : array();
      foreach ($items2 as $i => $label):
        $row = isset($val['verifikasi'][$i]) ? $val['verifikasi'][$i] : array();
        $st  = isset($row['status']) ? strtolower(trim($row['status'])) : '';
        $ket = isset($row['ket']) ? $row['ket'] : '';
      ?>
      <tr>
        <td class="cl-c"><?php echo $i + 1 ?></td>
        <td><?php echo dok_e($label) ?></td>
        <td class="cl-c"><?php echo ($st === 'ya' || $st === 'sesuai') ? '<span class="cl-check">&#10003;</span>' : '' ?></td>
        <td class="cl-c"><?php echo ($st === 'tidak') ? '<span class="cl-check">&#10003;</span>' : '' ?></td>
        <td><?php echo dok_e($ket) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- 7. Bottom Section: ASIKKEKKU Checklist Flow (7 Langkah) PERSIS SESUAI GAMBAR -->
  <div class="cl-alur-wrap">
    <table class="cl-alur-table">
      <thead>
        <tr>
          <th style="text-align: center;">Keterangan</th>
          <th class="cl-alur-head-check">ASIKKEKKU<br>(Checklist)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($alurSteps as $idx => $stepText):
          $isCheck = !empty($alur[$idx]);
        ?>
        <tr>
          <td><?php echo dok_e($stepText) ?></td>
          <td class="cl-alur-cell-check"><?php echo $isCheck ? '<span class="cl-check">&#10003;</span>' : '' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
