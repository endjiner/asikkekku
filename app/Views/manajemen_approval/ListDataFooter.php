<script type="text/javascript">
  $(document).ready(function() {
    // Dari dashboard "Buat Pengajuan Baru" (?new=1) — buka formulir otomatis
    if (window.location.search.indexOf('new=1') > -1) {
      setTimeout(function () { $('.KegiatanAdd').trigger('click'); }, 250);
    }

    // Bungkus teks panjang -> di HP dipotong 2 baris (lihat .dt-c2 di CSS)
    // supaya tinggi tiap baris tabel seragam.
    function dtClamp(data, type) {
      if (type !== 'display' || data == null || data === '') return data;
      return '<span class="dt-c2">' + data + '</span>';
    }

    // Kolom "NO" mengikuti urutan data: terbaru ke terlama menurun dari total,
    // sedangkan urutan ascending dimulai dari nomor 1.
    function dtRowNo(d, type, row, meta) {
      if (type !== 'display') return d;
      var settings = meta.settings;
      var order = settings.aaSorting || [];
      var isDescending = order.length && order[0][0] === 0 && order[0][1] === 'desc';
      var total = (settings.json && settings.json.recordsFiltered) || settings._iRecordsDisplay || 0;
      var position = settings._iDisplayStart + meta.row + 1;
      return isDescending ? total - position + 1 : position;
    }

    // Di HP: aktifkan geser horizontal (scrollX) supaya kolom tidak
    // dipaksa gepeng. Di desktop: scrollX mati -> lebar kolom menyesuaikan
    // isi secara otomatis (table-layout auto) dan tabel muat dalam kartu.
    // scrollY tetap aktif di semua ukuran -> header tak tenggelam & sejajar.
    var dtNarrow = window.matchMedia('(max-width: 767.98px)').matches;

    var highlightOffset = <?= (int) ($highlightOffset ?? 0) ?>;

    var table_data = $("#table-data").DataTable({
      "processing": true,
      "serverSide": true,
      "responsive": false,
      "autoWidth": true,
      "scrollX": dtNarrow,
      "scrollY": "calc(100vh - 360px)",
      "scrollCollapse": true,
      "displayStart": highlightOffset,
      "order": [[0, 'desc']],
      "pageLength": 10,
      "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
      "language": {
        "processing": "Memuat data...",
        "search": "Cari:",
        "searchPlaceholder": "No. surat / judul / pelaksana...",
        "lengthMenu": "Tampilkan _MENU_ baris",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ pengajuan",
        "infoEmpty": "Tidak ada data pengajuan",
        "infoFiltered": "(disaring dari _MAX_ total)",
        "zeroRecords": "Tidak ada data yang cocok dengan pencarian",
        "paginate": {
          "first": "Awal",
          "last": "Akhir",
          "next": "Selanjutnya",
          "previous": "Sebelumnya"
        }
      },
      "ajax": {
        url: '<?= base_url() ?>manajemen_approval/KegiatanGetList',
        method: "POST",
      },
      "columns": [
        { data: 'row_num', searchable: false, render: dtRowNo },
        { data: 'KegiatanNoSuratTugas' },
        { data: 'KegiatanJudul', render: dtClamp },
        { data: 'KegiatanNamaPelaksana', render: dtClamp },
        { data: 'KegiatanTanggal' },
        { data: 'KegiatanStatusBadges', orderable: false, searchable: false },
        { data: 'Action', orderable: false, searchable: false }
      ],
      "columnDefs": [
        { targets: [0], class: 'ta-c', width: '5%' },
        { targets: [1, 4], class: 'wp-keep' },   // No. Surat & Tanggal: satu baris
        { targets: [5], class: 'ta-c', width: '10%' },
        { targets: [6], class: 'ta-c', width: '15%' },
      ],
      initComplete : function() {
        var input = $('.dataTables_filter input'),
          self = this.api(),
          $searchButton = $('<button class="btn btn-flat btn-success btn-sm display-n">')
                     .html('<?php echo str_replace(array(chr(13),chr(10)), "", svgico("search", 13)) ?>')
                     .click(function() {
                        self.ajax.reload();
                     }),
          $clearButton = $('<button class="btn btn-flat btn-success btn-sm ml-2">')
                     .html('<?php echo str_replace(array(chr(13),chr(10)), "", svgico("refresh", 13)) ?>')
                     .click(function() {
                        input.val('');
                        self.search('').draw();
                     });
        $('.dataTables_filter').append($searchButton, $clearButton);
      },
      drawCallback: function() {
        var urlParams = new URLSearchParams(window.location.search);
        var highlightId = urlParams.get('highlight') || urlParams.get('id');
        if (highlightId) {
          var $row = $('#row-kegiatan-' + highlightId + ', tr[data-id="' + highlightId + '"], tr:has(button[data="' + highlightId + '"])');
          if ($row.length) {
            $row.addClass('row-highlight-pulse');
            setTimeout(function() {
              var scrollEl = $('.dataTables_scrollBody')[0];
              if (scrollEl && $row[0]) {
                var rowTop = $row[0].offsetTop;
                scrollEl.scrollTop = Math.max(0, rowTop - 60);
              } else if ($row[0]) {
                $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
            }, 300);
          }
        }
      }
    })
    $('input[name="KegiatanTanggal"]').daterangepicker({ 
      singleDatePicker: true, 
      showDropdowns: true,
      locale: {
        format: 'YYYY-MM-DD'
      }
    })

    function formReset(form) {
      form.find(':checkbox').attr('value', '1').prop('checked', false);
      form.find('input[type="text"]').val('')
      form.find('input[type="password"]').val('')
      form.find('input[type="file"]').val(null)
      form.find('.custom-file-label').html('Pilih berkas PDF dari komputer...');
      form.find('textarea').val('')
      form.find('select').val(null).trigger('change')
      $('.div_remove_KegiatanLampiranPrev').hide()
      $('#tab-data-pokok-btn').tab('show')
    }

    $('body').on('change', '#KegiatanLampiran', function() {
      var fileName = $(this).val().split('\\').pop() || $(this).val().split('/').pop();
      $(this).next('.custom-file-label').html(fileName || 'Pilih berkas PDF dari komputer...');
    });
    $("form.KegiatanModify").submit(function(e) {
      e.preventDefault(); 
      var form = $(this);
      var modal_id = form.closest('.modal').attr('id')
      var action_url = form.attr('action');

      // Gabungkan Tempat Asal & Tempat Tujuan secara otomatis untuk kompatibilitas SPD / DB
      var asal = ($('#kegiatan-asal').val() || '').trim();
      var tujuan = ($('#kegiatan-tujuan').val() || '').trim();
      var combo = asal ? (asal + (tujuan ? ' ke ' + tujuan + ' PP' : '')) : tujuan;
      $('#kegiatan-asal-tujuan').val(combo);

      $.ajax({
        type: "POST",
        url: action_url,
        // data: form.serialize(), 
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(data) {
          formReset(form)
          $('#'+modal_id).modal('hide')
          table_data.ajax.reload()
          show_json_error(data)
        },
        error: function () {
          show_toast('error', 'Gagal menyimpan pengajuan. Coba lagi.');
        }
      });
    });
    // Quick template button Surat Tugas
    $('body').on('click', '.btn-st-tpl', function() {
      var prefix = $(this).attr('data-prefix');
      $('#kegiatan-no-surat').val(prefix).focus();
    });

    // Auto-fill No WhatsApp berdasarkan tipe pemohon
    $('body').on('change', '#kegiatan-pemohon-tipe', function() {
      var tipe = $(this).val();
      var userPhone = $('#kegiatan-pemohon-phone').attr('data-user-phone') || '';
      if (tipe === 'internal') {
        if (!$('#kegiatan-pemohon-phone').val() || $('#kegiatan-pemohon-phone').val() === '') {
          $('#kegiatan-pemohon-phone').val(userPhone);
        }
      }
    });

    // Muat ulang opsi "Petugas Tujuan" sesuai jenis pengajuan terpilih.
    // selectAfter (opsional): UserID yang di-set setelah opsi dibangun.
    function reloadPetugasTujuan(jenisID, selectAfter) {
      var $sel = $('select[name=KegiatanDestUser]');
      $.ajax({
        type: 'POST',
        url: '<?= base_url() ?>manajemen_approval/UserDestByJenis',
        data: { JenisID: jenisID },
        dataType: 'json',
        success: function(rows) {
          $sel.empty();
          (rows || []).forEach(function(r) {
            $sel.append($('<option>').val(r.UserID).text(r.UserFullName));
          });
          if (selectAfter != null) $sel.val(selectAfter);
        },
        error: function() {
          show_toast('error', 'Gagal memuat daftar petugas tujuan.');
        }
      });
    }
    $('body').on('change', 'select[name=KegiatanJenisID]', function() {
      reloadPetugasTujuan($(this).val(), null);
    });

    // ---- Repeater PELAKSANA ----
    var pelIdx = 0;
    function pelAddRow(d) {
      d = d || {};
      var tpl = document.getElementById('pel-tpl');
      if (!tpl) return;
      var html = tpl.innerHTML.replace(/__I__/g, 'r' + (pelIdx++));
      var $row = $(html);
      $row.find('.pel-user').val(d.UserID || d.userid || '');
      $row.find('.pel-nama').val(d.Nama || d.nama || '');
      $row.find('.pel-nip').val(d.NIP || d.nip || '');
      $row.find('.pel-gol').val(d.Gol || d.gol || '');
      $row.find('.pel-jabatan').val(d.Jabatan || d.jabatan || '');
      $row.find('.pel-rekening').val(d.Rekening || d.rekening || '');
      $row.find('.pel-bank').val(d.Bank || d.bank || '');
      $row.find('.pel-npwp').val(d.NPWP || d.npwp || '');
      $('#pel-list').append($row);
    }
    // Helper: hanya cocok jika ada salah satu kata dalam nama yang diawali query (misal 'pri' -> cocok 'Priya' atau 'Alvin Priantama')
    function matchPrefixKata(nama, query) {
      if (!query) return false;
      var q = query.toLowerCase().trim();
      if (!q) return false;
      var words = (nama || '').toLowerCase().split(/[\s,.\-_/()]+/);
      return words.some(function(w) {
        return w.indexOf(q) === 0;
      });
    }

    function escHtml(str) {
      if (!str) return '';
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fillPegawaiData($row, p) {
      if (p) {
        $row.find('.pel-nama').val(p.nama || '');
        $row.find('.pel-user').val(p.id || '');
        $row.find('.pel-nip').val(p.nip || '');
        $row.find('.pel-gol').val(p.gol || '');
        $row.find('.pel-jabatan').val(p.jabatan || '');
        if (!$row.find('.pel-rekening').val()) $row.find('.pel-rekening').val(p.rekening || '');
        if (!$row.find('.pel-bank').val()) $row.find('.pel-bank').val(p.bank || '');
        if (!$row.find('.pel-npwp').val()) $row.find('.pel-npwp').val(p.npwp || '');
        if (p.phone && !$('#kegiatan-pemohon-phone').val()) {
          $('#kegiatan-pemohon-phone').val(p.phone);
        }
        $row.find('.pel-status-hint').html('<span class="text-success font-weight-bold">✓ Pegawai Internal BPOM (NIP & Golongan terisi otomatis)</span>');
      } else {
        $row.find('.pel-user').val('');
        $row.find('.pel-status-hint').html('<span class="text-info font-weight-bold">ℹ Pelaksana / Penyedia Eksternal (NIP & Golongan opsional)</span>');
      }
    }

    $('body').on('input focus', '.pel-nama', function() {
      var val = ($(this).val() || '').trim();
      var $wrap = $(this).closest('.pel-nama-wrap');
      var $box = $wrap.find('.pel-suggest-box');
      var $r = $(this).closest('.pel-row');

      if (!val) {
        $box.addClass('d-none').empty();
        $r.find('.pel-user').val('');
        $r.find('.pel-status-hint').html('💡 Ketik awalan nama pegawai untuk auto-fill, atau ketik nama penyedia luar.');
        return;
      }

      // Filter hanya nama yang memiliki kata berawalan 'val' (misal: "pri" -> "Priya", "Alvin Priantama")
      var matches = (window.PEGAWAI || []).filter(function(p) {
        return matchPrefixKata(p.nama, val);
      });

      if (matches.length > 0) {
        var html = '';
        matches.slice(0, 8).forEach(function(p) {
          html += '<div class="pel-suggest-item" data-id="' + p.id + '">' +
                    '<div>' +
                      '<div class="pel-suggest-nama">' + escHtml(p.nama) + '</div>' +
                      '<div class="pel-suggest-nip">NIP: ' + escHtml(p.nip || '-') + ' | ' + escHtml(p.jabatan || '-') + '</div>' +
                    '</div>' +
                    '<span class="pel-suggest-badge">' + escHtml(p.gol || 'Internal') + '</span>' +
                  '</div>';
        });
        $box.html(html).removeClass('d-none');
      } else {
        $box.addClass('d-none').empty();
      }

      // Cari apakah teks input persis sama dengan nama pegawai
      var exact = (window.PEGAWAI || []).filter(function(x) {
        return (x.nama && x.nama.toLowerCase() === val.toLowerCase());
      })[0];
      fillPegawaiData($r, exact);
    });

    $('body').on('click', '.pel-suggest-item', function() {
      var id = $(this).attr('data-id');
      var $r = $(this).closest('.pel-row');
      var $box = $(this).closest('.pel-suggest-box');
      var p = (window.PEGAWAI || []).filter(function(x) { return x.id == id; })[0];
      if (p) {
        fillPegawaiData($r, p);
      }
      $box.addClass('d-none').empty();
    });

    $(document).on('click', function(e) {
      if (!$(e.target).closest('.pel-nama-wrap').length) {
        $('.pel-suggest-box').addClass('d-none');
      }
      if (!$(e.target).closest('.output-wrap').length) {
        $('.output-suggest-box').addClass('d-none');
      }
    });

    // ---- Autocomplete & Suggestion untuk Kode Output ----
    $('body').on('focus input', '#kegiatan-kode-output', function() {
      var val = ($(this).val() || '').trim().toLowerCase();
      var $wrap = $(this).closest('.output-wrap');
      var $box = $wrap.find('.output-suggest-box');
      var list = window.OFFICIAL_OUTPUTS || [];

      var matches = list.filter(function(o) {
        if (!val) return true; // tampilkan semua jika kotak baru diklik
        return o.code.toLowerCase().indexOf(val) !== -1;
      });

      if (matches.length > 0) {
        var html = '';
        matches.forEach(function(o) {
          html += '<div class="output-suggest-item" data-code="' + escHtml(o.code) + '" data-verif="' + escHtml(o.verif) + '">' +
                    '<span class="font-weight-bold text-dark text-sm">' + escHtml(o.code) + '</span>' +
                  '</div>';
        });
        $box.html(html).removeClass('d-none');
      } else {
        $box.addClass('d-none').empty();
      }
    });

    $('body').on('click', '.output-suggest-item', function() {
      var code = $(this).attr('data-code');
      var verif = $(this).attr('data-verif');
      var $inp = $('#kegiatan-kode-output');
      $inp.val(code);
      $(this).closest('.output-suggest-box').addClass('d-none').empty();

      // Auto-select Verifikator sesuai tabel resmi (Hana vs Ratna) bila ada di dropdown Petugas Tujuan
      if (verif) {
        var $dest = $('#kegiatan-dest-user');
        $dest.find('option').each(function() {
          var txt = $(this).text().toLowerCase();
          if (txt.indexOf(verif.toLowerCase()) !== -1) {
            $dest.val($(this).val());
            return false;
          }
        });
      }
    });

    $('body').on('click', '#pel-add', function() { pelAddRow({}); });
    $('body').on('click', '.pel-del', function() {
      var $l = $('#pel-list');
      $(this).closest('.pel-row').remove();
      if ($l.children().length === 0) pelAddRow({});
    });

    $('body').on('click', '.KegiatanAdd', function() {
      var form = $('form.KegiatanModify');
      formReset(form)
      $('#pel-list').empty(); pelIdx = 0; pelAddRow({});
      $('input[name=KegiatanKodeOutput]').val('');
      $('#kegiatan-asal').val('Pangkal Pinang');
      $('#kegiatan-tujuan').val('');
      $('input[name=KegiatanAsalTujuan]').val('');
      $('input[name=KegiatanJmlHari]').val('');
      var userPhone = $('#kegiatan-pemohon-phone').attr('data-user-phone') || '';
      $('#kegiatan-pemohon-phone').val(userPhone);
      $('select[name=KegiatanPemohonTipe]').val('internal');
      var $jenis = $('select[name=KegiatanJenisID]');
      $jenis.prop('selectedIndex', 0);
      reloadPetugasTujuan($jenis.val(), null);
    })
    $('body').on('click', '.KegiatanEdit', function() {
      // alert('ok')
      var KegiatanID = $(this).attr('data');
      formReset($('form.KegiatanModify'))
      $.ajax({
        type: "POST",
        url: '<?= base_url() ?>manajemen_approval/KegiatanGetData',
        data: {KegiatanID:KegiatanID},
        dataType: 'Json',
        success: function(data) {
          Kegiatan = data['Kegiatan'][0]
          $('input[name=KegiatanID]').val(Kegiatan['KegiatanID'])
          $('input[name=KegiatanNoSuratTugas]').val(Kegiatan['KegiatanNoSuratTugas'])
          $('input[name=KegiatanJudul]').val(Kegiatan['KegiatanJudul'])
          $('input[name=KegiatanTanggal]').val(Kegiatan['KegiatanTanggal'])
          $('textarea[name=KegiatanKeterangan]').val(Kegiatan['KegiatanKeterangan'])
          $('input[name=KegiatanKodeOutput]').val(Kegiatan['KegiatanKodeOutput'] || '')
          
          var rawAsalTujuan = Kegiatan['KegiatanAsalTujuan'] || '';
          var parsedAsal = 'Pangkal Pinang';
          var parsedTujuan = '';
          if (rawAsalTujuan) {
            if (rawAsalTujuan.indexOf(' ke ') !== -1) {
              var parts = rawAsalTujuan.split(' ke ');
              parsedAsal = parts[0].trim();
              parsedTujuan = (parts[1] || '').replace(/\s+PP$/i, '').trim();
            } else {
              parsedTujuan = rawAsalTujuan.replace(/\s+PP$/i, '').trim();
            }
          }
          $('#kegiatan-asal').val(parsedAsal);
          $('#kegiatan-tujuan').val(parsedTujuan);
          $('input[name=KegiatanAsalTujuan]').val(rawAsalTujuan);
          $('input[name=KegiatanJmlHari]').val(Kegiatan['KegiatanJmlHari'] || '')
          $('#pel-list').empty(); pelIdx = 0;
          var pel = data['Pelaksana'] || [];
          if (pel.length) { pel.forEach(function(p){ pelAddRow(p); }); } else { pelAddRow({}); }
          $('select[name=KegiatanJenisID]').val(Kegiatan['KegiatanJenisID'] || '1')
          $('select[name=KegiatanPemohonTipe]').val(Kegiatan['KegiatanPemohonTipe'] || 'internal')
          $('input[name=KegiatanPemohonPhone]').val(Kegiatan['KegiatanPemohonPhone'] || '')
          $('input[name=KegiatanLampiranPrev]').val(Kegiatan['KegiatanLampiran'])
          $('.div_remove_KegiatanLampiranPrev').show()
          // Bangun ulang opsi petugas untuk jenis kegiatan ini, lalu pilih yang tersimpan.
          reloadPetugasTujuan(Kegiatan['KegiatanJenisID'] || 1, Kegiatan['KegiatanDestUser'])
        },
        error: function () {
          show_toast('error', 'Gagal memuat data pengajuan. Coba lagi.');
        }
      });
    });
    $('body').on('click', '.KegiatanDelete', function() {
      var KegiatanID = $(this).attr('data');
      confirmAksi({
        title: 'Hapus data kegiatan?',
        text: 'Data kegiatan beserta riwayatnya akan dihapus permanen dan tidak bisa dikembalikan.',
        confirmText: 'Ya, hapus', tone: 'danger'
      }).then(function(ok) {
        if (!ok) return;
        $.ajax({
          type: "POST", url: '<?= base_url() ?>manajemen_approval/KegiatanDelete',
          data: {KegiatanID:KegiatanID}, dataType: 'Json',
          success: function(data) { table_data.ajax.reload(); show_json_error(data); },
          error: function () { show_toast('error', 'Gagal menghapus pengajuan. Coba lagi.'); }
        });
      });
    })
    $('body').on('click', '.KegiatanSend', function() {
      var KegiatanID = $(this).attr('data');
      confirmAksi({
        title: 'Kirim pengajuan untuk disetujui?',
        text: 'Setelah dikirim, data tidak dapat diubah lagi dan akan masuk ke antrian persetujuan.',
        confirmText: 'Ya, kirim', tone: 'primary', icon: 'question'
      }).then(function(ok) {
        if (!ok) return;
        $.ajax({
          type: "POST", url: '<?= base_url() ?>manajemen_approval/KegiatanSendApproval',
          data: {KegiatanID:KegiatanID}, dataType: 'Json',
          success: function(data) { table_data.ajax.reload(); show_json_error(data); },
          error: function () { show_toast('error', 'Gagal mengirim pengajuan. Coba lagi.'); }
        });
      });
    })
    $('body').on('click', '.btn_remove_KegiatanLampiranPrev', function() {
      $('input[name=KegiatanLampiranPrev]').val('')
    })
    $('body').on('click', '.KegiatanInfo', function() {
      var KegiatanID = $(this).attr('data');
      var url = "<?php echo base_url();?>manajemen_approval/GetFormInfoKegiatan?KegiatanID=" + KegiatanID;
      var $body = $('#modal-xl .modal-body');
      $body.html(
        '<div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">' +
          '<div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>' +
          '<div class="font-weight-medium">Memuat rincian &amp; riwayat pengajuan...</div>' +
        '</div>'
      );
      $('#modal-xl .modal-footer').hide();
      $body.load(url, function (response, status) {
        if (status === 'error') {
          $body.html('<div class="alert alert-danger m-3">Gagal memuat detail pengajuan. Silakan coba lagi.</div>');
          show_toast('error', 'Gagal memuat detail pengajuan. Coba lagi.');
        }
      });
    })

    // Setelah modal ditutup / layar diputar, samakan lebar kolom tabel.
    $(document).on('hidden.bs.modal', '#modal-xl, #modal-l-Kegiatan', function() {
      if (table_data) setTimeout(function(){ table_data.columns.adjust(); }, 200);
    })
    table_data.on('responsive-resize', function() { table_data.columns.adjust(); })

    // Aksi Revisi / Hentikan Proses / Kirim Ulang (tombol muncul di kolom Aksi
    // untuk pengajuan berstatus "Perlu Revisi" yang ditujukan ke user ini).
    <?= view('manajemen_approval/_ApprovalActionsJs', get_defined_vars()) ?>
  })
</script>