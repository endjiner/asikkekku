<script type="text/javascript">
  $(document).ready(function() {
    function dtClamp(data, type) {
      if (type !== 'display' || data == null || data === '') return data;
      return '<span class="dt-c2">' + data + '</span>';
    }
    function dtRowNo(d, type, row, meta) {
      if (type !== "display") return d;
      var ord = meta.settings.aaSorting || [];
      var byNoCol = ord.length && ord[0][0] === meta.col;
      return byNoCol ? d : (meta.settings._iDisplayStart + meta.row + 1);
    }
    var dtNarrow = window.matchMedia('(max-width: 767.98px)').matches;

    var table_data = $("#table-data").DataTable({
      "processing": true,
      "serverSide": true,
      "responsive": false,
      "autoWidth": true,
      "scrollX": dtNarrow,
      "scrollY": "calc(100vh - 360px)",
      "scrollCollapse": true,
      "pageLength": 10,
      "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
      "language": {
        "processing": "Memuat data...",
        "search": "Cari:",
        "searchPlaceholder": "Nama / username / jabatan...",
        "lengthMenu": "Tampilkan _MENU_ baris",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ pengguna",
        "infoEmpty": "Tidak ada data pengguna",
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
        url: '<?= base_url() ?>manajemen_app/UserGetList',
        method: "POST",
      },
      "columns": [
        { data: 'No', searchable: false, render: dtRowNo },
        { data: 'UserName' },
        { data: 'UserFullName', render: dtClamp },
        { data: 'UserPosition' },
        { data: 'UserPhone' },
        { data: 'UserNote', render: dtClamp },
        { data: 'Status', orderable: false, searchable: false },
        { data: 'Action', orderable: false, searchable: false }
      ],
      "columnDefs": [
        { targets: [0], class: 'ta-c', width: '5%' },
        { targets: [4], class: 'ta-c wp-keep' },
        { targets: [6], class: 'ta-c', width: '5%' },
        { targets: [7], class: 'ta-c', width: '8%' },
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

    function formReset(form) {
      form.find(':checkbox').attr('value', '1').prop('checked', false);
      form.find('input[type="text"]').val('')
      form.find('input[type="password"]').val('')
      form.find('textarea').val('')
      form.find('select').val(null).trigger('change')
      form.find('.pw-new, .pw-verify').attr('type', 'password')
      form.find('.pw-match-hint').removeClass('text-danger text-success').text('')
      form.find('.toggle-pass').each(function () {
        $(this).find('.tp-on').removeClass('d-none'); $(this).find('.tp-off').addClass('d-none');
      })
    }

    // Verifikasi 2 langkah kata sandi baru: cek kecocokan secara langsung.
    function pwCheck(form) {
      var a = form.find('.pw-new').val(), b = form.find('.pw-verify').val();
      var $hint = form.find('.pw-match-hint');
      if (!a && !b) { $hint.removeClass('text-danger text-success').text(''); return true; }
      if (a.length < 4) { $hint.removeClass('text-success').addClass('text-danger').text('Kata sandi baru minimal 4 karakter.'); return false; }
      if (a !== b) { $hint.removeClass('text-success').addClass('text-danger').text('Ulangi kata sandi belum sama.'); return false; }
      $hint.removeClass('text-danger').addClass('text-success').text('Kata sandi baru cocok.');
      return true;
    }
    $('body').on('input', 'form.UserModify .pw-new, form.UserModify .pw-verify', function () {
      pwCheck($(this).closest('form.UserModify'));
    });

    $("form.UserModify").submit(function(e) {
      e.preventDefault();
      var form = $(this);
      if (!pwCheck(form)) {
        infoAksi('error', 'Verifikasi kata sandi baru gagal. Pastikan "Kata Sandi Baru" dan "Ulangi Kata Sandi Baru" sama.');
        return;
      }
      var modal_id = form.closest('.modal').attr('id')
      form.find(':checkbox:not(:checked)').attr('value', '0').prop('checked', true);
      var action_url = form.attr('action');
      $.ajax({
        type: "POST",
        url: action_url,
        data: form.serialize(), 
        dataType: 'json',
        success: function(data) {
          formReset(form)
          $('#'+modal_id).modal('hide')
          table_data.ajax.reload()
          show_json_error(data)
        },
        error: function () {
          show_toast('error', 'Gagal menyimpan data pengguna. Coba lagi.');
        }
      });
    });
    $('body').on('click', '.UserAdd', function() {
      formReset($('form.UserModify'))
    })
    $('body').on('click', '.UserEdit', function() {
      var UserID = $(this).attr('data');
      formReset($('form.UserModify'))
      $.ajax({
        type: "POST",
        url: '<?= base_url() ?>manajemen_app/UserGetData',
        data: {UserID:UserID}, 
        dataType: 'Json', 
        success: function(data) {
          user = data['user'][0]

          $('input[name=UserID]').val(user['UserID'])
          $('input[name=UserName]').val(user['UserName'])
          $('input[name=UserFullName]').val(user['UserFullName'])
          $('input[name=UserPhone]').val(user['UserPhone'])
          $('select[name=UserPosition]').val(user['UserPosition'])
          $('textarea[name=UserNote]').val(user['UserNote'])
          is_aktif = (user['UserAktif'] === '1') ? true : false;
          $('input[name=UserAktif]').prop('checked', is_aktif);
        },
        error: function () {
          show_toast('error', 'Gagal memuat data pengguna. Coba lagi.');
        }
      });
    });
    $('body').on('click', '.UserDelete', function() {
      var UserID = $(this).attr('data');
      confirmAksi({
        title: 'Hapus pengguna ini?',
        text: 'Akun pengguna akan dihapus permanen dari sistem.',
        confirmText: 'Ya, hapus', tone: 'danger'
      }).then(function(ok) {
        if (!ok) return;
        $.ajax({
          type: "POST", url: '<?= base_url() ?>manajemen_app/UserDelete',
          data: {UserID:UserID}, dataType: 'Json',
          success: function(data) { table_data.ajax.reload(); show_json_error(data); },
          error: function () { show_toast('error', 'Gagal menghapus pengguna. Coba lagi.'); }
        });
      });
    })

    // Tombol mata: tampilkan/sembunyikan isian kata sandi.
    $('body').on('click', '.toggle-pass', function() {
      var $inp = $(this).closest('.input-group').find('input');
      var reveal = $inp.attr('type') === 'password';
      $inp.attr('type', reveal ? 'text' : 'password');
      $(this).find('.tp-on').toggleClass('d-none', reveal);
      $(this).find('.tp-off').toggleClass('d-none', !reveal);
    })
  })
</script>