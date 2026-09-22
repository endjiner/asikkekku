<?php helper('dokumen'); ?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Cetak &mdash; <?php echo dok_e($tpl['nama']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/dokumen.css?v=20260908-a4">
  <style>
    /* Lembar A4 (210 x 297 mm). Margin cetak diatur via @page. */
    @page { size: A4 portrait; margin: 15mm 18mm 15mm 18mm; }
    html, body { background:#5f6b7a; margin:0; }
    body { padding:20px 0; font-family:"Times New Roman",Georgia,serif; }
    .bar { width:210mm; margin:0 auto 14px; display:flex; gap:8px; }
    .bar button { font-family:system-ui,sans-serif; font-size:13px; padding:8px 16px; border:0; border-radius:8px; cursor:pointer; }
    .bar .p { background:#004282; color:#fff; }
    .bar .b { background:#e0e4ea; color:#1a2431; }
    .a4 {
      width:210mm; min-height:297mm; box-sizing:border-box;
      padding:15mm 18mm; margin:0 auto; background:#fff;
      box-shadow:0 2px 22px rgba(0,0,0,.35);
    }
    .a4 .doc-sheet { width:100%; max-width:none; padding:0; box-shadow:none; }
    @media print {
      html, body { background:#fff; padding:0; }
      .bar { display:none; }
      .a4 { width:auto; min-height:0; padding:0; margin:0; box-shadow:none; }
    }
  </style>
</head>
<body>
  <div class="bar">
    <button class="p" onclick="window.print()">Cetak / Simpan PDF</button>
    <button class="b" onclick="window.close()">Tutup</button>
  </div>
  <div class="a4">
    <?= view('dokumen/_render', ['kode' => $kode, 'tpl' => $tpl, 'd' => $d, 'kegiatan' => $kegiatan]) ?>
  </div>
  <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
