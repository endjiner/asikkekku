<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" type="image/png" href="<?php echo base_url('assets/images/logo_baru.png');?>"/>
  <title><?php echo $AppConfig['app_title'] ?> | Masuk</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?=base_url()?>assets/template/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="<?=base_url()?>assets/css/custom-jtp.css?v=20260911-shell3">
</head>
<body class="hold-transition login-page">
<?php echo ui_sprite_inline(); /* sisipkan sprite ikon — tanpa ini svgico() kosong */ ?>

<div class="login-box">
  <div class="login-card">
    <div class="login-header">
      <img src="<?php echo base_url('assets/images/logo_baru.png') ?>" alt="Logo">
      <h2><?php echo $AppConfig['app_title'] ?></h2>
      <p>Silakan masuk ke akun Anda</p>
    </div>
    <div class="card-body">
      <?php if (!empty(session('error_info'))): ?>
        <div class="login-alert"><?php echo session('error_info'); session()->set('error_info', ''); ?></div>
      <?php endif ?>
      <form method="post" action="<?=base_url('MainPage/VerifyLogin')?>">
        <?php $__security = service('security'); ?>
        <input type="hidden" name="<?= $__security->getTokenName() ?>" value="<?= (string) $__security->getHash() ?>">
        <div class="form-group mb-3">
          <label for="login-username" class="form-label font-weight-semibold text-secondary text-sm">Nama Pengguna</label>
          <div class="input-group input-group-lg">
            <div class="input-group-prepend">
              <span class="input-group-text"><?php echo svgico('user', 18) ?></span>
            </div>
            <input type="text" id="login-username" name="Username" class="form-control form-control-lg" placeholder="Masukkan nama pengguna" required autocomplete="username">
          </div>
        </div>
        <div class="form-group mb-4">
          <label for="login-password" class="form-label font-weight-semibold text-secondary text-sm">Kata Sandi</label>
          <div class="input-group input-group-lg">
            <div class="input-group-prepend">
              <span class="input-group-text"><?php echo svgico('lock', 18) ?></span>
            </div>
            <input type="password" id="login-password" name="Password" class="form-control form-control-lg" placeholder="Masukkan kata sandi" required autocomplete="current-password">
          </div>
        </div>
        <div class="row">
          <div class="col-5">
            <a href="<?php echo base_url() ?>" class="btn btn-front-page">
              <?php echo svgico('arrow-left', 18) ?> Beranda
            </a>
          </div>
          <div class="col-7">
            <button type="submit" class="btn btn-login-teal">
              <?php echo svgico('login', 18) ?> Masuk
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?=base_url()?>assets/template/plugins/jquery/jquery.min.js"></script>
<script type="text/javascript">
</script>
</body>
</html>
