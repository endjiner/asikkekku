<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/png" href="<?php echo base_url('assets/images/logo_baru.png');?>"/>
  <?php $__ci =& get_instance(); ?>
  <meta name="csrf-name" content="<?= $__ci->security->get_csrf_token_name() ?>">
  <meta name="csrf-hash" content="<?= $__ci->security->get_csrf_hash() ?>">
  <script>window.CSRF = { name: <?= json_encode($__ci->security->get_csrf_token_name()) ?>, hash: <?= json_encode($__ci->security->get_csrf_hash()) ?> };</script>
  <title><?php echo $AppConfig['app_title'] ?> | <?php echo isset($menu_detail['MenuName']) ? $menu_detail['MenuName'] : $AppConfig['app_title'] ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php /* FontAwesome & icheck-bootstrap DIHAPUS: ikon sudah pakai sprite SVG
           lokal (svgico()/illus()), icheck tidak dipakai. datatables-responsive
           tidak dipakai (semua tabel responsive:false). CSS DataTables Buttons
           dimuat khusus di halaman Laporan. */ ?>
  <link rel="stylesheet" href="<?= base_url() ?>assets/template/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/template/plugins/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/template/plugins/sweetalert2/sweetalert2.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/custom-jtp.css?v=20260911-shell3">
  <link rel="stylesheet" href="<?= base_url() ?>assets/template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/template/plugins/toastr/toastr.min.css">

</head>
