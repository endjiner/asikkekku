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

    var table_data = $("#table-data").DataTable({
      "processing": true,
      "serverSide": true,
      "responsive": false,
      "autoWidth": true,
      "scrollX": dtNarrow,
      "scrollY": "calc(100vh - 360px)",
      "scrollCollapse": true,
      "order": [[0, 'desc']],
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
                     })
        $('.dataTables_filter').append($searchButton, $clearButton);
      },
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
      form.find('textarea').val('')
      form.find('select').val(null).trigger('change')
      $('.div_remove_KegiatanLampiranPrev').hide()
    }
    $("form.KegiatanModify").submit(function(e) {
      e.preventDefault(); 
      var form = $(this);
      var modal_id = form.closest('.modal').attr('id')
      var action_url = form.attr('action');

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
    $('body').on('change', '.pel-user', function() {
      var id = parseInt($(this).val(), 10);
      if (!id) return;
      var p = (window.PEGAWAI || []).filter(function(x){ return x.id === id; })[0];
      if (!p) return;
      var $r = $(this).closest('.pel-row');
      $r.find('.pel-nama').val(p.nama || '');
      $r.find('.pel-nip').val(p.nip || '');
      $r.find('.pel-gol').val(p.gol || '');
      $r.find('.pel-jabatan').val(p.jabatan || '');
      $r.find('.pel-rekening').val(p.rekening || '');
      $r.find('.pel-bank').val(p.bank || '');
      $r.find('.pel-npwp').val(p.npwp || '');
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
      $('select[name=KegiatanKodeOutput]').val('');
      $('input[name=KegiatanAsalTujuan]').val('');
      $('input[name=KegiatanJmlHari]').val('');
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
          $('select[name=KegiatanKodeOutput]').val(Kegiatan['KegiatanKodeOutput'] || '')
          $('input[name=KegiatanAsalTujuan]').val(Kegiatan['KegiatanAsalTujuan'] || '')
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
      KegiatanID = $(this).attr('data')
      url="<?php echo base_url();?>manajemen_approval/GetFormInfoKegiatan"
      url+="?KegiatanID="+KegiatanID
      $('#modal-xl .modal-body').empty().load(url, function (response, status) {
        if (status === 'error') show_toast('error', 'Gagal memuat detail pengajuan. Coba lagi.');
      });
      $('#modal-xl .modal-footer').hide();
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