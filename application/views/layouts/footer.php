<script src="<?= base_url() ?>assets/template/plugins/jquery/jquery.min.js"></script>
<script>
  /* --- CSRF: window.CSRF di-set di layouts/header.php. Prefilter ini
     menyisipkan token ke SETIAP request non-GET (AJAX jQuery) otomatis. --- */
  (function ($) {
    if (!$) return;
    if (!window.CSRF) {
      var mn = document.querySelector('meta[name=csrf-name]'), mh = document.querySelector('meta[name=csrf-hash]');
      window.CSRF = { name: mn ? mn.content : 'csrf_asikkekku', hash: mh ? mh.content : '' };
    }
    $.ajaxPrefilter(function (options) {
      var m = (options.type || options.method || 'GET').toUpperCase();
      if (m === 'GET' || m === 'HEAD' || options.crossDomain) return;
      var n = window.CSRF.name, h = window.CSRF.hash;
      if (options.data instanceof FormData) {
        if (!options.data.has(n)) options.data.append(n, h);
      } else if (typeof options.data === 'string') {
        options.data += (options.data ? '&' : '') + encodeURIComponent(n) + '=' + encodeURIComponent(h);
      } else if (options.data && typeof options.data === 'object') {
        if (options.data[n] == null) options.data[n] = h;
      } else {
        options.data = encodeURIComponent(n) + '=' + encodeURIComponent(h);
      }
    });
  })(window.jQuery);
</script>
<script src="<?= base_url() ?>assets/template/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="<?= base_url() ?>assets/template/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/moment/moment.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/daterangepicker/daterangepicker.js"></script>

<script src="<?=base_url()?>assets/template/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="<?=base_url()?>assets/template/plugins/toastr/toastr.min.js"></script>

<script src="<?= base_url() ?>assets/template/dist/js/adminlte.js"></script>

<script src="<?= base_url() ?>assets/template/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?= base_url() ?>assets/js/asik-anim.js?v=20260911-shell3"></script>
<?php /* DataTables Buttons + jszip + pdfmake (ekspor Excel/PDF/Print) hanya
         dipakai di halaman Laporan -> dimuat oleh manajemen_approval/ListReportFooter,
         bukan di sini, supaya ~2,3 MB skrip tidak ikut di setiap halaman. */ ?>
<script type="text/javascript">
  // Bahasa Indonesia untuk SEMUA tabel DataTables (satu tempat).
  if (window.jQuery && $.fn.dataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
      language: {
        processing:     "Memuat…",
        lengthMenu:     "Tampilkan _MENU_ baris",
        zeroRecords:    "Tidak ada data yang cocok",
        emptyTable:     "Belum ada data",
        info:           "Menampilkan _START_–_END_ dari _TOTAL_ baris",
        infoEmpty:      "Menampilkan 0 baris",
        infoFiltered:   "(disaring dari _MAX_ baris)",
        search:         "Cari:",
        searchPlaceholder: "kata kunci…",
        paginate: { first: "«", previous: "‹", next: "›", last: "»" },
        aria: { sortAscending: ": aktifkan untuk urut naik", sortDescending: ": aktifkan untuk urut turun" }
      }
    });
    // Lengkapi atribut aksesibilitas kontrol bawaan DataTables (kotak cari &
    // pemilih jumlah baris) supaya tidak dilaporkan "tanpa id/name/label"
    // di panel Issues DevTools.
    $(document).on('init.dt', function (e, settings) {
      var $w = $(settings.nTableWrapper || (settings.oInstance && settings.oInstance.api().table().container()));
      if (!$w || !$w.length) return;
      var key = (settings.sTableId || 'dt') + '';
      $w.find('.dataTables_filter input').each(function () {
        if (!this.name) this.name = key + '_cari';
        if (!this.id) this.id = key + '_cari';
        this.setAttribute('aria-label', 'Cari data di tabel');
      });
      $w.find('.dataTables_length select').each(function () {
        if (!this.name) this.name = key + '_jml';
        if (!this.id) this.id = key + '_jml';
        this.setAttribute('aria-label', 'Jumlah baris per halaman');
      });
    });
  }
  $(document).ready(function() {
    show_access_restrict()
    $('body').tooltip({ selector: '[data-tooltip="true"]', placement: 'top', container: 'body' })

    // Sinkronkan lebar header tabel (DataTables) saat drawer dibuka/ditutup
    // di desktop. Tanpa ini, lebar kolom header ikut "menyusut" karena
    // DataTables menghitung lebar kolom berdasarkan lebar container saat
    // tabel pertama kali dibuat, dan tidak tahu kapan drawer selesai
    // bergeser (transisi CSS-nya 0.3s).
    function refreshAllDataTableColumns() {
      if (!$.fn.DataTable) return
      $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust()
    }
    $(document).on('click', '[data-widget="pushmenu"]', function() {
      setTimeout(refreshAllDataTableColumns, 320)
    })
    $(window).on('resize', function() {
      clearTimeout(window._dtAdjustTimer)
      window._dtAdjustTimer = setTimeout(refreshAllDataTableColumns, 150)
    })

    var menu = $("a.id_<?php echo $menu_detail['MenuKode'];?>")
    menu.addClass("active")
    menu.parent().addClass("active")
    menu.parents("li").addClass("menu-open")
    menu.parents("li").children("a").addClass("active")

    /* --- Sidebar "ingat kondisi terakhir" ---------------------------------
       Selain kondisi rail/terbuka (ditangani AdminLTE enableRemember +
       skrip inline di <body>), submenu yang sedang dibuka juga diingat,
       supaya saat pindah halaman lewat sidebar tampilannya persis sama. */
    var SB_KEY = 'asik.sidebar.openmenus'
    function sbSaveOpenMenus() {
      var ids = []
      $('.nav-sidebar > .nav-item.menu-open > a.nav-link').each(function () {
        var m = (this.className.match(/id_\d+/) || [])[0]
        if (m) ids.push(m)
      })
      try { localStorage.setItem(SB_KEY, JSON.stringify(ids)) } catch (e) {}
    }
    try {
      JSON.parse(localStorage.getItem(SB_KEY) || '[]').forEach(function (cls) {
        var $a = $('.nav-sidebar > .nav-item > a.nav-link.' + cls)
        if (!$a.length) return
        $a.closest('.nav-item').addClass('menu-open')
      })
    } catch (e) {}
    // Simpan setiap kali user membuka/menutup submenu (tunggu animasi selesai).
    $(document).on('click', '.nav-sidebar > .nav-item > .nav-link', function () {
      setTimeout(sbSaveOpenMenus, 400)
    })
    $(document).on('click', '.nav-sidebar > .nav-item > .nav-parent-toggle', function (e) {
      e.preventDefault()
      e.stopImmediatePropagation()
      var $item = $(this).closest('.nav-item')
      var $submenu = $item.children('.nav-treeview')
      var isCollapsedRail = $('body').hasClass('sidebar-collapse') && !$('body').hasClass('sidebar-open')
      var openClass = isCollapsedRail ? 'flyout-open' : 'menu-open'
      var isOpen = $item.hasClass(openClass)
      $item.toggleClass(openClass, !isOpen)
      $submenu.stop(true, true)[isOpen ? 'slideUp' : 'slideDown'](150)
      sbSaveOpenMenus()
    })
    // Simpan juga tepat sebelum meninggalkan halaman via klik menu.
    $(document).on('click', '.nav-sidebar a.nav-link[href]:not([href="#"])', sbSaveOpenMenus)

    // Baris tabel bisa diklik: klik sel mana pun (selain kolom nomor/aksi)
    // memicu tombol Info / detail pada baris tersebut.
    $('body').on('click', 'table.table-hover tbody td', function() {
      var $td = $(this)
      if ($td.hasClass('ta-c') || $td.hasClass('no-link') || $td.is(':last-child')) return
      if ($td.closest('table').hasClass('no-row-link')) return
      var $row = $td.closest('tr')
      var $btn = $row.find('.KegiatanInfo, .KegiatanCekStatus, .UserEdit, .GroupUserEdit').first()
      if ($btn.length) $btn.trigger('click')
    })
  })

  function show_access_restrict() {
    var error = "<?=$this->session->userdata('error_info')?>"
    if (error !== "") { show_toast('error', error) }
    <?php $this->session->set_userdata(array('error_info' => '')); ?>
  }
  function show_json_error(data) {
    if (data && data['error']) {
      var msg = (typeof data['error'] === 'object')
        ? ((data['error']['code'] ? data['error']['code'] + ': ' : '') + data['error']['message'])
        : data['error']
      show_toast('error', msg)
    } else {
      show_toast('success', 'Proses berhasil.')
    }
  }
  function show_toast(type, message) {
    if (type === 'success') { toastr.success(message) } else { toastr.error(message) }
  }

  /* SweetAlert2 pra-merender elemen input tersembunyi (swal2-input, -file,
     -range, -select, -radio, -checkbox, -textarea) tanpa id/name; DevTools
     "Issues" menandainya sebagai a11y warning walau tak pernah dipakai.
     Bersihkan saat dialog terbuka supaya panel Issues bersih. */
  function swalA11yFix(popup) {
    if (!popup) return;
    popup.querySelectorAll('input, select, textarea').forEach(function (el, i) {
      if (!el.id && !el.getAttribute('name')) el.setAttribute('name', 'swal2-field-' + i);
    });
    popup.querySelectorAll('label[for]').forEach(function (lb) {
      var t = lb.getAttribute('for');
      if (!t || !popup.querySelector('#' + (window.CSS && CSS.escape ? CSS.escape(t) : t))) lb.removeAttribute('for');
    });
  }

  /* ---- Konfirmasi kustom (SweetAlert2, bergaya brand) --------------
     Pakai:  confirmAksi({ title, text, confirmText, tone }).then(function(ok){ if(ok){...} })
     tone: 'primary' (default) | 'danger' | 'success'                */
  function confirmAksi(opts) {
    opts = opts || {};
    var tone = opts.tone || 'primary';
    var colors = {
      primary: '#004282',
      danger:  '#b23b3b',
      success: '#1F7A43'
    };
    return Swal.fire({
      title: opts.title || 'Konfirmasi',
      html: opts.text || 'Lanjutkan tindakan ini?',
      icon: opts.icon || (tone === 'danger' ? 'warning' : 'question'),
      showCancelButton: true,
      focusCancel: true,
      reverseButtons: true,
      confirmButtonText: opts.confirmText || 'Ya, lanjutkan',
      cancelButtonText: opts.cancelText || 'Batal',
      confirmButtonColor: colors[tone] || colors.primary,
      cancelButtonColor: '#64748b',
      buttonsStyling: true,
      customClass: { popup: 'swal-asik' },
      didOpen: swalA11yFix
    }).then(function (r) { return r.isConfirmed === true; });
  }
  function infoAksi(type, message) {
    Swal.fire({
      icon: type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info'),
      title: type === 'success' ? 'Berhasil' : (type === 'error' ? 'Gagal' : 'Info'),
      html: message || '',
      confirmButtonText: 'OK',
      confirmButtonColor: '#004282',
      customClass: { popup: 'swal-asik' },
      didOpen: swalA11yFix
    });
  }

  /* Konfirmasi Keluar (logout) */
  $(document).on('click', '#btnLogout, a[href$="MainPage/logout"]', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    var url = $(this).attr('href');
    confirmAksi({
      title: 'Keluar dari aplikasi?',
      text: 'Sesi Anda akan diakhiri dan Anda harus masuk kembali.',
      confirmText: 'Ya, keluar', tone: 'danger', icon: 'warning'
    }).then(function (ok) { if (ok) window.location.href = url; });
  });

  $('body').on('click', '.PegawaiFoto', function() {
    $('#modal-lg').modal('show')
    $('#modal-lg').find('.modal-body').empty().append('<div class="ta-c"><img id="FotoPegawaiFilePreview" src="" alt="" class="mt-2 mb-2 p-2" style="max-width: 400px; max-height: 300px; border: 1px solid grey;"/></div>')
    $('#modal-lg').find('.modal-body').find('#FotoPegawaiFilePreview').attr('src', $(this).attr('src'))
  })
</script>
