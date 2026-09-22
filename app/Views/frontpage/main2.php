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
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/custom-jtp.css?v=20260922-ui1">
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
            <p class="hero-sub"><?php echo $AppConfig['app_description'] ?><br>Balai Besar POM di Pangkal Pinang</p>
            <span class="hero-rule"></span>
            <p class="hero-tag">Solusi digital untuk memantau, mencari, dan mengelola kartu kuitansi dengan mudah, cepat, dan transparan.</p>
          </div>
          <div class="col-lg-6 order-1 order-lg-2 hero-art">
            <?= view('frontpage/_hero_illustration', get_defined_vars()) ?>
          </div>
        </div>
      </div>
    </section>

    <div class="pub-container pub-body-inner">
      <form role="form" class="form-search search-card" autocomplete="off" onsubmit="return false;">
        <div class="sc-search">
          <div class="sc-type-wrap">
            <span class="sc-type-ico"><?php echo svgico('filter', 15) ?></span>
            <select id="search-type" name="type_search" class="sc-type-select" aria-label="Cari Berdasarkan">
              <option value="all" selected>Semua Kategori</option>
              <option value="nama_petugas">Nama Petugas / Penyedia</option>
              <option value="judul_kegiatan">Judul Kegiatan</option>
              <option value="no_sptb">No. SPTB / SPTJB</option>
              <option value="no_surat">No. Surat Tugas</option>
            </select>
          </div>
          <div class="sc-input-wrap">
            <span class="sc-search-ico"><?php echo svgico('search', 18) ?></span>
            <input type="search" id="search-text" name="search_text" placeholder="Cari berdasarkan nama, kegiatan, no. SPTB, atau no. surat…" aria-label="Kata kunci pencarian">
          </div>
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
                    <tr>
                      <th>Nama</th>
                      <th>Kegiatan</th>
                      <th>No. SPTJB</th>
                      <th>Tanggal</th>
                      <th>Status</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-5 status-col" id="statusCol">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="rc-title mb-0"><?php echo illus('approval-inbox', 28) ?> <span>Status</span></h5>
            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnCloseStatus" style="font-size: .78rem; padding: 2px 10px; border-radius: 6px;">
              ✕ Tutup
            </button>
          </div>
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
      <div>&copy; <?php echo date('Y') ?> <strong>Balai Besar POM di Pangkal Pinang</strong>. Seluruh hak cipta dilindungi.</div>
      <div>Dikembangkan oleh <a href="https://pangkalpinang.pom.go.id" target="_blank" rel="noopener">Balai Besar POM di Pangkal Pinang</a></div>
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
    var CLOSE_ICON = '✕';
    var EMPTY_STATUS = '<p class="text-muted text-center py-4 mb-0">Pilih salah satu hasil untuk melihat status pengajuannya.</p>';
    var activeKegiatanID = null;
    var statusCache = {};

    var mqlDesktop = window.matchMedia('(min-width: 992px)');
    var isDesktop = function () { return mqlDesktop.matches; };
    var currentIsDesktop = isDesktop();

    var table = $('#result-table').DataTable({
      dom: "<'row dt-controls'<'col-sm-6'l><'col-sm-6'f>>t<'row dt-foot'<'col-sm-5'i><'col-sm-7'p>>",
      language: {
        lengthMenu: 'Tampilkan _MENU_ baris', info: 'Menampilkan _START_–_END_ dari _TOTAL_',
        infoEmpty: 'Tidak ada data', infoFiltered: '(disaring dari _MAX_)',
        search: '', searchPlaceholder: 'Saring hasil…', paginate: { previous: '‹', next: '›' },
        zeroRecords: 'Tidak ada hasil yang cocok.', emptyTable: 'Ketik kata kunci pencarian di atas untuk melihat hasil.'
      },
      lengthMenu: [5, 10, 25, 50], pageLength: 10, order: [], autoWidth: false,
      ajax: { url: base + 'FrontPage/ListResult', type: 'GET', data: function (d) {
        d.search_text = $('#search-text').val();
        d.type_search = $('#search-type').val();
      }},
      columns: [
        { data: 'nama' },
        { data: 'kegiatan' },
        { data: 'sptjb', className: 'text-nowrap' },
        { data: 'tanggal', className: 'text-nowrap' },
        { data: 'status', className: 'text-center', orderable: false },
        { data: null, orderable: false, className: 'text-center text-nowrap',
          render: function (row) { return '<button type="button" class="btn-cek" data="' + row.KegiatanID + '">' + CEK_ICON + ' <span>Cek</span></button>'; } }
      ]
    });

    function updateSearchPlaceholder() {
      var type = $('#search-type').val();
      var ph = 'Cari berdasarkan nama, kegiatan, no. SPTB, atau no. surat…';
      if (type === 'nama_petugas') {
        ph = 'Masukkan nama petugas atau penyedia…';
      } else if (type === 'judul_kegiatan') {
        ph = 'Masukkan kata kunci judul kegiatan…';
      } else if (type === 'no_sptb') {
        ph = 'Masukkan nomor SPTB / SPTJB…';
      } else if (type === 'no_surat') {
        ph = 'Masukkan nomor surat tugas / SK…';
      }
      $('#search-text').attr('placeholder', ph);
    }

    $('#search-type').on('change', function () {
      updateSearchPlaceholder();
      if ($.trim($('#search-text').val()).length > 0) {
        resetAllStatus();
        table.ajax.reload();
      }
    });

    var searchTimer = null;
    $('#search-text').on('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        resetAllStatus();
        table.ajax.reload();
      }, 350);
    });

    $('form.form-search').on('submit', function (e) {
      e.preventDefault();
      clearTimeout(searchTimer);
      resetAllStatus();
      table.ajax.reload();
    });

    function fetchStatus(id, callback) {
      if (statusCache[id]) {
        callback(null, statusCache[id]);
        return;
      }
      $.ajax({
        url: base + 'FrontPage/ListStatus',
        type: 'POST',
        data: { KegiatanID: id },
        success: function (html) {
          statusCache[id] = html;
          callback(null, html);
        },
        error: function () {
          callback('error', '<div class="alert alert-danger mb-0">Gagal memuat status pengajuan. Silakan coba lagi.</div>');
        }
      });
    }

    function resetAllStatus() {
      activeKegiatanID = null;
      $('#result-table tbody tr').removeClass('row-active shown');
      $('#result-table .btn-cek').removeClass('btn-cek-active').html(CEK_ICON + ' <span>Cek</span>');
      table.rows().every(function () {
        if (this.child.isShown()) this.child.hide();
      });
      $('#statusPanel').html(EMPTY_STATUS);
      $('#btnCloseStatus').addClass('d-none');
    }

    function renderActiveStatus() {
      if (!activeKegiatanID) return;

      var foundTr = null;
      $('#result-table tbody tr').each(function () {
        var d = table.row(this).data();
        if (d && d.KegiatanID == activeKegiatanID) {
          foundTr = $(this);
        }
      });

      if (isDesktop()) {
        // Desktop: Tampilkan di card samping kanan, tutup baris anak di tabel
        table.rows().every(function () {
          if (this.child.isShown()) this.child.hide();
        });
        $('#result-table tbody tr').removeClass('shown');

        if (foundTr && foundTr.length) {
          foundTr.addClass('row-active');
          foundTr.find('.btn-cek').addClass('btn-cek-active').html(CLOSE_ICON + ' <span>Tutup</span>');
        }
        $('#btnCloseStatus').removeClass('d-none');
        $('#statusPanel').html('<p class="text-muted text-center py-4 mb-0">Memuat status…</p>');

        fetchStatus(activeKegiatanID, function (err, html) {
          if (activeKegiatanID && isDesktop()) {
            $('#statusPanel').html(html);
          }
        });
      } else {
        // Layar kecil / Mobile: Tampilkan inline di bawah baris data
        $('#statusPanel').html(EMPTY_STATUS);
        $('#btnCloseStatus').addClass('d-none');

        if (foundTr && foundTr.length) {
          var r = table.row(foundTr);
          table.rows().every(function () {
            if (this.child.isShown()) this.child.hide();
          });
          $('#result-table tbody tr').not(foundTr).removeClass('row-active shown');
          $('#result-table .btn-cek').not(foundTr.find('.btn-cek')).removeClass('btn-cek-active').html(CEK_ICON + ' <span>Cek</span>');

          foundTr.addClass('shown row-active');
          foundTr.find('.btn-cek').addClass('btn-cek-active').html(CLOSE_ICON + ' <span>Tutup</span>');

          var $box = $('<div class="inline-status open"><p class="text-muted text-center py-2 mb-0">Memuat status…</p></div>');
          r.child($box).show();

          fetchStatus(activeKegiatanID, function (err, html) {
            if (activeKegiatanID && !isDesktop()) {
              $box.html(html);
            }
          });
        }
      }
    }

    function toggleStatus(id, $tr) {
      if (!id || !$tr || !$tr.length) return;

      var isCurrentlyActive = (activeKegiatanID == id);

      if (isCurrentlyActive) {
        resetAllStatus();
        return;
      }

      resetAllStatus();
      activeKegiatanID = id;
      renderActiveStatus();
    }

    $('#btnCloseStatus').on('click', function () {
      resetAllStatus();
    });

    $('form.form-search').on('submit', function (e) {
      e.preventDefault();
      resetAllStatus();
      table.ajax.reload();
    });

    table.on('draw', function () {
      if (activeKegiatanID) {
        renderActiveStatus();
      }
    });

    $('#result-table tbody').on('click', 'tr', function (e) {
      if ($(e.target).closest('.btn-cek').length) return;
      var d = table.row(this).data();
      if (d) toggleStatus(d.KegiatanID, $(this));
    });

    $('#result-table tbody').on('click', '.btn-cek', function (e) {
      e.stopPropagation();
      var id = $(this).attr('data');
      var $tr = $(this).closest('tr');
      toggleStatus(id, $tr);
    });

    function handleLayoutChange() {
      var desktopNow = isDesktop();
      if (desktopNow !== currentIsDesktop) {
        currentIsDesktop = desktopNow;
        if (activeKegiatanID) {
          renderActiveStatus();
        } else {
          resetAllStatus();
        }
      }
    }

    if (mqlDesktop.addEventListener) {
      mqlDesktop.addEventListener('change', handleLayoutChange);
    } else {
      mqlDesktop.addListener(handleLayoutChange);
    }
    $(window).on('resize', handleLayoutChange);
  });
</script>
</body>
</html>
