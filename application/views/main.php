<!DOCTYPE html>
<html lang="id">

<?php $this->load->view('layouts/header'); ?>

<?php if (isset($header)): ?>
  <?php $this->load->view($header); ?>
<?php endif ?>

<body class="hold-transition app-topnav page-<?= isset($menu_detail['MenuKode']) ? preg_replace('/[^0-9a-zA-Z]/', '', $menu_detail['MenuKode']) : 'x' ?>">
  <?php echo ui_sprite_inline(); ?>

  <header class="app-topbar">
    <a href="<?= base_url('dashboard') ?>" class="app-brand">
      <img src="<?= base_url($AppConfig['logo_small'] ?: 'assets/images/logo_baru.png') ?>" alt="Logo">
      <span class="app-brand-txt">
        <b><?php echo html_escape($AppConfig['app_title']) ?></b>
        <small>BBPOM di Pangkal Pinang</small>
      </span>
    </a>

    <button type="button" class="app-navtoggle" aria-label="Menu" aria-expanded="false">
      <?php echo svgico('menu', 20) ?>
    </button>

    <nav class="app-nav">
      <?php $this->load->view('layouts/topmenu'); ?>
    </nav>

    <div class="app-user">
      <button type="button" class="app-user-btn" data-toggle="modal" data-target="#modal-password" title="Ganti kata sandi">
        <?php echo illus('profile', 20); ?>
        <span class="app-user-name"><?= html_escape($UserFullName) ?><i><?= html_escape($UserPosition) ?></i></span>
      </button>
      <a class="app-logout" id="btnLogout" href="<?= base_url() ?>MainPage/logout" title="Keluar">
        <?php echo svgico('logout', 18) ?><span>Keluar</span>
      </a>
    </div>
  </header>

  <main class="app-main">
    <?php
    $link = dirname(__FILE__) . '/' . $body . '.php';
    if (realpath($link)) {
      $this->load->view($body);
    } else {
      $this->load->view('dashboard/UnderConstruction.php');
    }
    ?>

    <footer class="app-foot">
      <span>Hak Cipta &copy; <?php echo date('Y') ?> Balai Besar POM di Pangkal Pinang</span>
      <span class="d-none d-sm-inline">Dimuat dalam <b>{elapsed_time}</b> detik</span>
    </footer>
  </main>

  <div class="app-fabs">
    <?php if (!empty($AppConfig['link_panduan'])): ?>
      <a class="app-fab app-fab-help" href="<?= html_escape($AppConfig['link_panduan']) ?>" target="_blank" rel="noopener" title="Buku panduan" aria-label="Buku panduan"><?php echo svgico('guide', 18) ?></a>
    <?php endif ?>
    <button type="button" class="app-fab app-fab-top" aria-label="Kembali ke atas"><?php echo svgico('arrow-up', 18) ?></button>
  </div>
</body>

<?php $this->load->view('layouts/footer'); ?>

<?php if (isset($footer)): ?>
  <?php $this->load->view($footer); ?>
<?php endif ?>
<?php if (isset($footer_addt)): ?>
  <?php $this->load->view($footer_addt); ?>
<?php endif ?>

<div class="modal fade show" id="modal-lg" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title font-weight-bold">Detail</h5>
        <button type="button" class="btn btn-sm btn-navy modal-back" data-dismiss="modal">
          <?php echo svgico('arrow-left', 15) ?> Kembali
        </button>
      </div>
      <div class="modal-body"><p>&nbsp;</p></div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade show" id="modal-xl" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title font-weight-bold">Informasi Pengajuan</h5>
        <button type="button" class="btn btn-sm btn-navy modal-back" data-dismiss="modal">
          <?php echo svgico('arrow-left', 15) ?> Kembali
        </button>
      </div>
      <div class="modal-body"><p>&nbsp;</p></div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade show" id="modal-password" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
    <form role="form" class="PasswordModify" action="<?= base_url() ?>profil/PasswordModify" method="post" autocomplete="off">
      <div class="modal-content text-sm rounded-lg border-0 shadow">
        <div class="modal-header bg-teal text-white align-items-center">
          <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2">
            <?php echo svgico('key', 18) ?> Ganti Kata Sandi Akun
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="UserID" class="UserID" value="<?php echo $UserID ?>">
          <div class="form-group mb-3">
            <label class="font-weight-semibold text-secondary" for="pwd-old">Kata Sandi Lama</label>
            <input type="password" id="pwd-old" name="PasswordOld" class="form-control PasswordOld" required autocomplete="off" placeholder="Masukkan kata sandi lama">
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-semibold text-secondary" for="pwd-new">Kata Sandi Baru</label>
            <input type="password" id="pwd-new" name="PasswordNew" class="form-control PasswordNew" required autocomplete="off" placeholder="Masukkan kata sandi baru">
          </div>
          <div class="form-group mb-2">
            <label class="font-weight-semibold text-secondary" for="pwd-verify">Verifikasi Kata Sandi Baru</label>
            <input type="password" id="pwd-verify" name="PasswordVerify" class="form-control PasswordVerify" required autocomplete="off" placeholder="Ulangi kata sandi baru">
          </div>
        </div>
        <div class="modal-footer justify-content-between bg-light">
          <button type="button" class="btn btn-secondary modal-close" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-flat-brand">
            <?php echo svgico('save', 18) ?> Simpan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
</html>

<script type="text/javascript">
  /* --- Navigasi atas: toggle mobile, dropdown, FAB kembali-ke-atas --- */
  (function () {
    var bar = document.querySelector('.app-topbar');
    var tgl = document.querySelector('.app-navtoggle');
    if (bar && tgl) {
      tgl.addEventListener('click', function () {
        var open = bar.classList.toggle('nav-open');
        tgl.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
    document.querySelectorAll('.tm-toggle').forEach(function (b) {
      b.addEventListener('click', function (e) {
        e.preventDefault();
        var item = b.closest('.tm-item');
        var wasOpen = item.classList.contains('open');
        document.querySelectorAll('.tm-item.open').forEach(function (x) { if (x !== item) x.classList.remove('open'); });
        item.classList.toggle('open', !wasOpen);
      });
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.tm-item')) {
        document.querySelectorAll('.tm-item.open').forEach(function (x) { x.classList.remove('open'); });
      }
      if (bar && bar.classList.contains('nav-open') && !e.target.closest('.app-topbar')) {
        bar.classList.remove('nav-open');
        if (tgl) tgl.setAttribute('aria-expanded', 'false');
      }
    });
    var fabTop = document.querySelector('.app-fab-top');
    if (fabTop) {
      var onScroll = function () { fabTop.classList.toggle('show', window.scrollY > 320); };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
      fabTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    }
  })();

  $("form.PasswordModify").submit(function(e) {
    e.preventDefault();
    var form = $(this);
    if ($('.PasswordNew').val() !== $('.PasswordVerify').val()) {
      infoAksi('error', 'Verifikasi kata sandi baru tidak cocok. Silakan periksa kembali.');
      return false;
    }
    confirmAksi({
      title: 'Ganti kata sandi akun?',
      text: 'Setelah diganti, Anda perlu memakai kata sandi baru pada login berikutnya.',
      confirmText: 'Ya, ganti', tone: 'primary', icon: 'question'
    }).then(function(ok) {
      if (!ok) return;
      var modal_id = form.closest('.modal').attr('id');
      $.ajax({
        type: "POST", url: form.attr('action'), data: form.serialize(), dataType: 'json',
        success: function(data) {
          form.find('input[type="password"]').val('');
          $('#'+modal_id).modal('hide');
          show_json_error(data);
        },
        error: function () {
          show_toast('error', 'Gagal menyimpan perubahan kata sandi. Coba lagi.');
        }
      });
    });
  });
</script>
