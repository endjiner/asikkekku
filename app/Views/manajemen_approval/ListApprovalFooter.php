<script type="text/javascript">
  var table_data
  $(document).ready(function() {
    function dtClamp(data, type) {
      if (type !== 'display' || data == null || data === '') return data;
      return '<span class="dt-c2">' + data + '</span>';
    }
    // Kolom "NO": nomor record asli bila diurutkan lewat kolom NO / urutan
    // default; nomor urut tampilan (1,2,3...) bila diurutkan lewat kolom lain.
    function dtRowNo(d, type, row, meta) {
      if (type !== 'display') return d;
      var ord = meta.settings.aaSorting || [];
      var byNoCol = ord.length && ord[0][0] === meta.col;
      return byNoCol ? d : (meta.settings._iDisplayStart + meta.row + 1);
    }
    var dtNarrow = window.matchMedia('(max-width: 767.98px)').matches;

    table_data = $("#table-data").DataTable({
      "processing": true,
      "serverSide": true,
      "responsive": false,
      "autoWidth": true,
      "scrollX": dtNarrow,
      "scrollY": "calc(100vh - 360px)",
      "scrollCollapse": true,
      "order": [[0, 'desc']],
      "ajax": {
        url: '<?= base_url() ?>manajemen_approval/KegiatanApprovalGetList',
        method: "POST",
      },
      "columns": [
        { data: 'row_num', searchable: false, render: dtRowNo },
        { data: 'KegiatanNoSuratTugas' },
        { data: 'KegiatanJudul', render: dtClamp },
        { data: 'KegiatanNamaPelaksana', render: dtClamp },
        { data: 'KegiatanTanggal' },
        { data: 'KegiatanStatus', orderable: false, searchable: false },
        { data: 'Action', orderable: false, searchable: false }
      ],
      "columnDefs": [
        { targets: [0], class: 'ta-c', width: '8%' },
        { targets: [1, 4], class: 'wp-keep' },
        { targets: [5], class: 'ta-c', width: '12%' },
        { targets: [6], class: 'ta-c', width: '8%' },
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
    $('body').on('click', '.KegiatanInfo', function() {
      KegiatanID = $(this).attr('data')
      url="<?php echo base_url();?>manajemen_approval/GetApprovalFormInfoKegiatan"
      url+="?KegiatanID="+KegiatanID
      $('#modal-xl .modal-body').empty().load(url, function (response, status) {
        if (status === 'error') show_toast('error', 'Gagal memuat formulir approval. Coba lagi.');
      });
      $('#modal-xl .modal-footer').hide();
    })

    $(document).on('hidden.bs.modal', '#modal-xl', function() {
      if (table_data) setTimeout(function(){ table_data.columns.adjust(); }, 200);
    })
    table_data.on('responsive-resize', function() { table_data.columns.adjust(); })

    <?= view('manajemen_approval/_ApprovalActionsJs', get_defined_vars()) ?>
  })
</script>