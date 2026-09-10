<?php $this->load->helper('dokumen'); ?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Paket Cetak &mdash; Kegiatan #<?php echo (int) $KegiatanID ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/dokumen.css?v=20260911-pkt">
  <style>
    @page { size: A4 portrait; margin: 15mm 18mm 15mm 18mm; }
    html, body { background:#5f6b7a; margin:0; }
    body { padding:20px 0; font-family:"Times New Roman",Georgia,serif; }
    .bar { width:210mm; margin:0 auto 14px; display:flex; gap:8px; }
    .bar button { font-family:system-ui,sans-serif; font-size:13px; padding:8px 16px; border:0; border-radius:8px; cursor:pointer; }
    .bar .p { background:#004282; color:#fff; } .bar .b { background:#e0e4ea; color:#1a2431; }
    .a4 {
      width:210mm; min-height:297mm; box-sizing:border-box; padding:15mm 18mm;
      margin:0 auto 20px; background:#fff; box-shadow:0 2px 22px rgba(0,0,0,.35);
      page-break-after:always;
    }
    .a4:last-child { page-break-after:auto; margin-bottom:0; }
    .a4 .doc-sheet { width:100%; max-width:none; padding:0; box-shadow:none; min-height:0; }
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
    <span style="align-self:center;font-family:system-ui;font-size:12px;color:#e6e9ee">
      <?php echo count($items) ?> dokumen &middot; <?php echo $tampilkan_ttd ? 'dengan tanda tangan' : 'TANPA tanda tangan' ?>
    </span>
  </div>

  <?php foreach ($items as $it): ?>
    <div class="a4">
      <?php $this->load->view('dokumen/_render', array(
        'kode' => $it['kode'], 'tpl' => $it['tpl'], 'd' => $it['d'],
        'kegiatan' => $kegiatan, 'ttd' => $it['ttd'], 'tampilkan_ttd' => $tampilkan_ttd,
      )); ?>
    </div>
  <?php endforeach ?>

  <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>
