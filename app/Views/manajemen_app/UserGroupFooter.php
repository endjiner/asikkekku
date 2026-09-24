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
        "searchPlaceholder": "Nama grup / keterangan...",
        "lengthMenu": "Tampilkan _MENU_ baris",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ grup",
        "infoEmpty": "Tidak ada data grup",
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
        url: '<?= base_url() ?>manajemen_app/UserGroupGetList',
        method: "POST",
      },
      "columns": [
        { data: 'No', searchable: false, render: dtRowNo },
        { data: 'Nama', render: dtClamp },
        { data: 'Note', render: dtClamp },
        { data: 'Action', orderable: false, searchable: false }
      ],
      "columnDefs": [
        { targets: [0], class: 'ta-c', width: '6%' },
        { targets: [3], class: 'ta-c', width: '14%' },
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
      form.find('input[type="text"]:not(.MenuID)').val('')
      form.find('input.menu_access_id').val('0')
    }
    $("form.UserGroupModify").submit(function(e) {
      e.preventDefault(); 
      var form = $(this);
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
            show_toast('error', 'Gagal menyimpan grup pengguna. Coba lagi.');
          }
      });
    });
    $('body').on('click', '.GroupUserAdd', function() {
      formReset($('form.UserGroupModify'))
    })
    $('body').on('click', '.GroupUserEdit', function() {
      var UserGroupID = $(this).attr('data');
      formReset($('form.UserGroupModify'))
      $.ajax({
        type: "POST",
        url: '<?= base_url() ?>manajemen_app/UserGroupGetData',
        data: {UserGroupID:UserGroupID}, 
        dataType: 'Json', 
        success: function(data) {
          user_group = data['user_group'][0]
          menu_access = data['menu_access']

          $('input[name=UserGroupID]').val(user_group['UserGroupID'])
          $('input[name=UserGroupName]').val(user_group['UserGroupName'])
          $('input[name=UserGroupNote]').val(user_group['UserGroupNote'])

          $('input.MenuID').each(function(i, obj) {
            MenuID = $(this).val()
            tr = $(this).closest('tr')
            $.each(menu_access, function( index, value ) {
              if (MenuID === value['MenuID']) {
                tr.find('input.menu_access_id').val(value['MenuAccessID'])
                Status_R = (value['Status_R'] === '1') ? true : false;
                Status_C = (value['Status_C'] === '1') ? true : false;
                Status_U = (value['Status_U'] === '1') ? true : false;
                Status_D = (value['Status_D'] === '1') ? true : false;
                tr.find('input.Status_R').prop('checked', Status_R);
                tr.find('input.Status_C').prop('checked', Status_C);
                tr.find('input.Status_U').prop('checked', Status_U);
                tr.find('input.Status_D').prop('checked', Status_D);
              }
            });
          });
        },
        error: function () {
          show_toast('error', 'Gagal memuat grup pengguna. Coba lagi.');
        }
      });
    });
    $('body').on('click', '.GroupUserDelete', function() {
      var UserGroupID = $(this).attr('data');
      confirmAksi({
        title: 'Hapus grup pengguna ini?',
        text: 'Grup beserta hak akses menunya akan dihapus permanen.',
        confirmText: 'Ya, hapus', tone: 'danger'
      }).then(function(ok) {
        if (!ok) return;
        $.ajax({
          type: "POST", url: '<?= base_url() ?>manajemen_app/UserGroupDelete',
          data: {UserGroupID:UserGroupID}, dataType: 'Json',
          success: function(data) { table_data.ajax.reload(); show_json_error(data); },
          error: function () { show_toast('error', 'Gagal menghapus grup pengguna. Coba lagi.'); }
        });
      });
    });
  })
</script>