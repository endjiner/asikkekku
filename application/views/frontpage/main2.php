<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <link rel="shortcut icon" type="image/png" href="<?php echo base_url('assets/images/logo_baru.png');?>"/>
  <title><?php echo $AppConfig['app_title'] ?> | Beranda</title>
  <meta content="Pencarian status kartu kendali kuitansi <?php echo $AppConfig['app_title'] ?>" name="description">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url() ?>assets/template/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/custom-jtp.css?v=20260911-shell3">
</head>

<body class="public-body">
  <?php echo ui_sprite_inline(); ?>

  <header class="public-header">
    <div class="pub-container d-flex align-items-center">
      <a href="<?php echo base_url() ?>" class="brand mr-auto">
        <img src="<?php echo base_url('assets/images/logo_baru.png') ?>" alt="Logo">
        <span><?php echo htmlspecialchars($AppConfig['app_title']) ?></span>
      </a>

      <nav class="public-nav" id="publicNav">
        <a href="<?php echo $AppConfig['link_panduan'] ?>" target="_blank" rel="noopener"><?php echo svgico('guide', 18) ?> Panduan</a>
        <a href="<?php echo $AppConfig['link_anggaran'] ?>" target="_blank" rel="noopener"><?php echo svgico('budget', 18) ?> Anggaran</a>
        <a href="<?php echo base_url('mainpage') ?>" class="cta"><?php echo svgico('login', 18) ?> Login</a>
      </nav>

      <button type="button" class="drawer-btn" id="drawerToggle" aria-label="Menu" aria-expanded="false">
        <span class="db-menu"><?php echo svgico('menu', 22) ?></span>
      </button>
    </div>
  </header>

  <main class="public-main">
    <section class="public-hero">
      <div class="pub-container">
        <div class="row align-items-center">
          <div class="col-lg-6 order-2 order-lg-1 hero-copy">
            <h1><?php echo htmlspecialchars($AppConfig['app_title']) ?></h1>
            <p class="hero-sub"><?php echo $AppConfig['app_description'] ?><br>Balai POM di Pangkalpinang</p>
            <span class="hero-rule"></span>
            <p class="hero-tag">Solusi digital untuk memantau, mencari, dan mengelola kartu kuitansi dengan mudah, cepat, dan transparan.</p>
          </div>
          <div class="col-lg-6 order-1 order-lg-2 hero-art">
            <?php $this->load->view('frontpage/_hero_illustration'); ?>
          </div>
        </div>
      </div>
    </section>

    <div class="pub-container pub-body-inner">
      <form role="form" class="form-search search-card" autocomplete="off" onsubmit="return false;">
        <input type="hidden" name="type_result" value="kuitansi">
        <div class="row">
          <div class="col-md-3 col-sm-6 sc-col">
            <label for="search-type-result">Jenis Data</label>
            <div class="sc-field"><?php echo svgico('fund', 16) ?>
              <select id="search-type-result" class="form-control" disabled><option>Kartu Kuitansi</option></select>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 sc-col">
            <label for="search-type">Cari Berdasarkan</label>
            <div class="sc-field"><?php echo svgico('user', 16) ?>
              <select id="search-type" name="type_search" class="form-control">
                <option value="no_sptb">No. SPTB</option>
                <option value="no_surat">No. Surat</option>
                <option value="judul_kegiatan" selected>Judul Kegiatan</option>
                <option value="nama_petugas">Nama Petugas / Penyedia</option>
              </select>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 sc-col">
            <label for="search-order-sort">Urutan</label>
            <div class="sc-field"><?php echo svgico('refresh', 16) ?>
              <select id="search-order-sort" name="order_sort" class="form-control">
                <option value="asc">A → Z</option>
                <option value="desc">Z → A</option>
              </select>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 sc-col">
            <label for="search-order-by">Urut Berdasarkan</label>
            <div class="sc-field"><?php echo svgico('calendar', 16) ?>
              <select id="search-order-by" name="order_by" class="form-control">
                <option value="tanggal">Tanggal</option>
                <option value="nama">Nama</option>
              </select>
            </div>
          </div>
        </div>
        <div class="sc-search">
          <span class="sc-search-ico"><?php echo svgico('search', 18) ?></span>
          <input type="search" id="search-text" name="search_text" placeholder="Masukkan kata kunci pencarian…" aria-label="Kata kunci pencarian">
          <button type="submit" class="btn-cari"><?php echo svgico('search', 18) ?> Cari</button>
        </div>
      </form>

      <div class="row results-wrap">
        <div class="col-lg-7 result-col">
          <h5 class="rc-title"><?php echo illus('activity', 28) ?> <span>Hasil Pencarian</span></h5>
          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table id="result-table" class="table table-hover w-100">
                  <thead>
                    <tr><th>Nama</th><th>Kegiatan</th><th>No. SPTJB</th><th>Status</th><th></th></tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-5 status-col" id="statusCol">
          <h5 class="rc-title"><?php echo illus('approval-inbox', 28) ?> <span>Status</span></h5>
          <div class="card">
            <div class="card-body">
              <div class="status-panel" id="statusPanel">
                <p class="text-muted text-center py-4 mb-0">Pilih salah satu hasil untuk melihat status pengajuannya.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="public-footer">
    <div class="pub-container d-flex flex-wrap justify-content-between" style="gap:6px;">
      <div>&copy; <?php echo date('Y') ?> <strong>Balai POM di Pangkalpinang</strong>. Seluruh hak cipta dilindungi.</div>
      <div>Dikembangkan oleh <a href="https://pangkalpinang.pom.go.id" target="_blank" rel="noopener">Balai POM di Pangkalpinang</a></div>
    </div>
  </footer>

<script src="<?= base_url() ?>assets/template/plugins/jquery/jquery.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(function () {
    var base = '<?php echo base_url() ?>';
    var isDesktop = function () { return window.matchMedia('(min-width: 992px)').matches; };

    /* ---- Menu dropdown kanan-atas (tanpa overlay) ---- */
    var $nav = $('#publicNav'), $tog = $('#drawerToggle');
    function closeNav() { $nav.removeClass('open'); $tog.removeClass('open').attr('aria-expanded', 'false'); }
    $tog.on('click', function (e) {
      e.stopPropagation();
      var open = !$nav.hasClass('open');
      $nav.toggleClass('open', open); $tog.toggleClass('open', open).attr('aria-expanded', open);
    });
    $(document).on('click', function (e) {
      if (!$(e.target).closest('#publicNav, #drawerToggle').length) closeNav();
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') closeNav(); });
    $nav.find('a').on('click', closeNav);

    /* ---- Tabel hasil ---- */
    var CEK_ICON = '<?php echo str_replace(array("\r","\n"), "", addslashes(svgico("eye", 15))) ?>';
    var table = $('#result-table').DataTable({
      dom: "<'row dt-controls'<'col-sm-6'l><'col-sm-6'f>>t<'row dt-foot'<'col-sm-5'i><'col-sm-7'p>>",
      language: {
        lengthMenu: 'Tampilkan _MENU_ baris', info: 'Menampilkan _START_–_END_ dari _TOTAL_',
        infoEmpty: 'Tidak ada data', infoFiltered: '(disaring dari _MAX_)',
        search: '', searchPlaceholder: 'Saring hasil…', paginate: { previous: '‹', next: '›' },
        zeroRecords: 'Tidak ada hasil yang cocok.', emptyTable: 'Lakukan pencarian di atas untuk melihat hasil.'
      },
      lengthMenu: [5, 10, 25, 50], pageLength: 10, order: [], autoWidth: false,
      ajax: { url: base + 'FrontPage/ListResult', type: 'GET', data: function (d) {
        $('form.form-search').serializeArray().forEach(function (x) { d[x.name] = x.value; });
      }},
      columns: [
        { data: 'nama' },
        { data: 'kegiatan' },
        { data: 'sptjb', className: 'text-nowrap' },
        { data: 'status', className: 'text-center', orderable: false },
        { data: null, orderable: false, className: 'text-center text-nowrap',
          render: function (row) { return '<button type="button" class="btn-cek" data="' + row.KegiatanID + '">' + CEK_ICON + ' Cek</button>'; } }
      ]
    });

    $('form.form-search').on('submit', function (e) {
      e.preventDefault();
      table.ajax.reload();
      $('#statusPanel').html('<p class="text-muted text-center py-4 mb-0">Pilih salah satu hasil untuk melihat status pengajuannya.</p>');
    });

    function loadStatus(id, $tr) {
      if (isDesktop()) {
        $('#result-table tbody tr').removeClass('row-active');
        if ($tr) $tr.addClass('row-active');
        $('#statusPanel').html('<p class="text-muted text-center py-3 mb-0">Memuat status…</p>')
          .load(base + 'FrontPage/ListStatus', { KegiatanID: id }, function (response, status) {
            if (status === 'error') $box.html('<div class="alert alert-danger">Gagal memuat status pengajuan. Silakan coba lagi.</div>');
          });
      } else {
        var r = table.row($tr);
        if (r.child.isShown()) { r.child.hide(); $tr.removeClass('shown'); return; }
        var $box = $('<div class="inline-status open">Memuat status…</div>');
        r.child($box).show(); $tr.addClass('shown');
        $box.load(base + 'FrontPage/ListStatus', { KegiatanID: id }, function (response, status) {
          if (status === 'error') $box.html('<div class="alert alert-danger">Gagal memuat status pengajuan. Silakan coba lagi.</div>');
        });
      }
    }
    $('#result-table tbody').on('click', 'tr', function () {
      var d = table.row(this).data();
      if (d) loadStatus(d.KegiatanID, $(this));
    });
    $('#result-table tbody').on('click', '.btn-cek', function (e) {
      e.stopPropagation();
      loadStatus($(this).attr('data'), $(this).closest('tr'));
    });
  });
</script>
</body>
</html>
