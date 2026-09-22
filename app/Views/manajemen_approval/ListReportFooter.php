<?php /* Aset ekspor (Excel/PDF/Print) KHUSUS halaman Laporan. Dimuat di sini,
         bukan di header/footer global, supaya halaman lain tetap ringan
         (~2,3 MB skrip tidak ikut). Urutan wajib: jszip & pdfmake sebelum
         buttons.html5. */ ?>
<link rel="stylesheet" href="<?= base_url() ?>assets/template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<script src="<?= base_url() ?>assets/template/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/jszip/jszip.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/pdfmake/pdfmake.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/pdfmake/vfs_fonts.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?= base_url() ?>assets/template/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script type="text/javascript">
  var table_data, title_page
  $(document).ready(function() {
    title_page = document.title

    function dtClamp(data, type) {
      if (type !== 'display' || data == null || data === '') return data;
      return '<span class="dt-c2">' + data + '</span>';
    }
    var dtNarrow = window.matchMedia('(max-width: 767.98px)').matches;

    // Jaga rentang tetap valid: "Dari" tidak boleh melewati "Sampai".
    function syncRange(changed) {
      var f = $('#report-from').val(), t = $('#report-to').val();
      if (f && t && f > t) {
        if (changed === 'from') $('#report-to').val(f);
        else $('#report-from').val(t);
      }
    }
    // Terapkan OTOMATIS begitu rentang / saringan status diubah (tanpa tombol).
    function applyFilter() {
      if (!table_data) return;
      table_data.ajax.reload();
      document.title = title_page + ' | ' + rangeLabel();
    }
    $('#report-from').on('change', function () { syncRange('from'); applyFilter(); });
    $('#report-to').on('change', function () { syncRange('to'); applyFilter(); });
    $(document).on('change', '.report-status', function () {
      // minimal satu status tetap tercentang
      if ($('.report-status:checked').length === 0) { $(this).prop('checked', true); return; }
      applyFilter();
    });

    function statusText(s) {
      if (s === 'selesai') return 'Selesai';
      if (s === 'kembali') return 'Dikembalikan';
      return 'Dalam Proses';
    }
    function statusBadge(s) {
      var cls = (s === 'selesai') ? 'badge-soft-success'
              : (s === 'kembali') ? 'badge-soft-danger' : 'badge-soft-info';
      return '<span class="' + cls + '">' + statusText(s) + '</span>';
    }

    /* ---- Format tanggal & label Bahasa Indonesia (moment.min.js tanpa locale) ---- */
    var BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var BULAN_S = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    function p2(n) { return (n < 10 ? '0' : '') + n; }
    function bulanTahun(ym) {
      if (!ym) return '-';
      var a = ym.split('-'); return (BULAN[parseInt(a[1], 10) - 1] || '?') + ' ' + a[0];
    }
    function tglIndo(iso) {
      if (!iso) return '-';
      var m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
      if (!m) return iso;
      var out = parseInt(m[3], 10) + ' ' + (BULAN_S[parseInt(m[2], 10) - 1] || '?') + ' ' + m[1];
      if (m[4]) out += ', ' + m[4] + '.' + m[5];
      return out;
    }
    function rangeLabel() {
      var f = $('#report-from').val(), t = $('#report-to').val();
      if (!f) return '';
      var a = bulanTahun(f), b = bulanTahun(t || f);
      return (a === b) ? a : (a + ' s.d. ' + b);
    }
    function statusFilterLabel() {
      var sel = $('.report-status:checked').map(function () {
        return statusText($(this).val());
      }).get();
      return (sel.length === 3 || sel.length === 0) ? 'Semua status' : sel.join(', ');
    }
    function cetakLabel() {
      var n = new Date();
      return p2(n.getDate()) + ' ' + BULAN[n.getMonth()] + ' ' + n.getFullYear()
        + ', pukul ' + p2(n.getHours()) + '.' + p2(n.getMinutes()) + ' WIB';
    }
    document.title = title_page + ' | ' + rangeLabel();

    var JUDUL_LAPORAN = 'LAPORAN PENGAJUAN KARTU KENDALI KUITANSI';
    var INSTANSI = 'BALAI BESAR POM DI PANGKAL PINANG';
    var cetakStamp = function () { return 'Dicetak: ' + cetakLabel(); };
    var exportTitle = function () { return JUDUL_LAPORAN + ' — Periode ' + rangeLabel(); };

    table_data = $("#table-data").DataTable({
      "dom": "<'row'<'col-12 col-md-7 mb-2 d-flex flex-wrap align-items-center'Bl><'col-12 col-md-5 mb-2'f>>rt<'row'<'col-sm-5'i><'col-sm-7'p>>",
      "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
      "pageLength": 25,
      "language": {
        processing: "Memuat…",
        search: "Cari:",
        searchPlaceholder: "judul / no. surat / pelaksana",
        lengthMenu: "Tampilkan _MENU_ baris",
        info: "Menampilkan _START_–_END_ dari _TOTAL_ baris",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(disaring dari _MAX_)",
        zeroRecords: "Tidak ada data yang cocok dengan pencarian",
        emptyTable: "Belum ada data untuk rentang bulan & saringan status ini",
        paginate: { first: "«", previous: "‹", next: "›", last: "»" }
      },
      "processing": true,
      "scrollX": dtNarrow,
      "scrollY": "calc(100vh - 400px)",
      "scrollCollapse": true,
      "autoWidth": true,
      "buttons": [
                  {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    title: exportTitle,
                    exportOptions: { stripHtml: true },
                    customize: function (xlsx) {
                      var sheet = xlsx.xl.worksheets['sheet1.xml'];
                      var $rows = $('row', sheet);
                      var lastRow = parseInt($rows.last().attr('r'), 10) || $rows.length;
                      // Baris header = baris pertama dengan >= 7 sel.
                      var headerRow = 1;
                      $rows.each(function () {
                        if ($('c', this).length >= 7) { headerRow = parseInt($(this).attr('r'), 10) || 1; return false; }
                      });
                      var firstData = headerRow + 1;
                      // AutoFilter (tombol sortir/saring bawaan Excel) di baris header.
                      $('sheetData', sheet).after('<autoFilter ref="A' + headerRow + ':G' + lastRow + '"/>');
                      // Lebar kolom ramah baca.
                      $('sheetData', sheet).before(
                        '<cols>' +
                        '<col min="1" max="1" width="6"/>' +
                        '<col min="2" max="2" width="22"/>' +
                        '<col min="3" max="3" width="52"/>' +
                        '<col min="4" max="4" width="26"/>' +
                        '<col min="5" max="5" width="14"/>' +
                        '<col min="6" max="6" width="16"/>' +
                        '<col min="7" max="7" width="38"/>' +
                        '</cols>'
                      );
                      // Bekukan baris header supaya tetap terlihat saat digulir.
                      $('sheetView', sheet).append(
                        '<pane ySplit="' + headerRow + '" topLeftCell="A' + firstData +
                        '" activePane="bottomLeft" state="frozen"/>'
                      );
                    }
                  },
                  {
                    extend: 'print', text: 'Cetak',
                    title: '',
                    exportOptions: { stripHtml: true },
                    customize: function (win) {
                      var $b = $(win.document.body);
                      $b.find('h1, .dt-print-title').remove();
                      $b.prepend(
                        '<div style="font-family:Arial,Helvetica,sans-serif;margin-bottom:12px">' +
                          '<div style="text-align:center;font-weight:700;font-size:15px">' + INSTANSI + '</div>' +
                          '<div style="text-align:center;font-weight:700;font-size:12px;color:#004282">' + JUDUL_LAPORAN + '</div>' +
                          '<hr style="border:0;border-top:2px solid #004282;margin:6px 0 10px">' +
                          '<div style="font-size:11px;line-height:1.6">' +
                            '<b>Periode:</b> ' + rangeLabel() + ' &nbsp;&middot;&nbsp; ' +
                            '<b>Status:</b> ' + statusFilterLabel() + ' &nbsp;&middot;&nbsp; ' +
                            '<b>Dicetak:</b> ' + cetakLabel() +
                          '</div>' +
                        '</div>'
                      );
                      $b.find('table').css({ 'font-size': '10px', 'border-collapse': 'collapse', 'width': '100%' });
                      $b.find('table th').css({ background: '#004282', color: '#fff', padding: '5px 6px', border: '1px solid #cbd5e1', 'text-align': 'center' });
                      $b.find('table td').css({ padding: '4px 6px', border: '1px solid #cbd5e1', 'vertical-align': 'top' });
                      $b.find('table tbody tr:nth-child(even) td').css({ background: '#f3f6fb' });
                    }
                  },
                  {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    filename: function () { return 'Laporan Pengajuan - ' + rangeLabel(); },
                    title: '',
                    exportOptions: { stripHtml: true },
                    customize: function (doc) {
                      // Ambil tabel hasil generate pdfmake (elemen ber-property .table).
                      var tbl = null;
                      (doc.content || []).forEach(function (c) { if (c && c.table) tbl = c; });
                      if (!tbl) return;

                      // --- Rapikan isi tabel: tanggal jadi format Indonesia ---
                      function cellText(c) { return (c && typeof c === 'object') ? (c.text || '') : (c || ''); }
                      function setCell(c, v) { if (c && typeof c === 'object') c.text = v; else return v; }
                      var HEAD = ['No.', 'No. Surat', 'Nama Kegiatan', 'Pelaksana / Penyedia', 'Tgl Pengajuan', 'Status', 'Pembaruan Terakhir'];
                      (tbl.table.body[0] || []).forEach(function (c, idx) { if (HEAD[idx] !== undefined) setCell(c, HEAD[idx]); });
                      tbl.table.body.forEach(function (row, i) {
                        if (i === 0) return; // baris header
                        // Kolom "No." = nomor urut berurutan mengikuti urutan cetak.
                        if (row[0] !== undefined) setCell(row[0], String(i));
                        row.forEach(function (c) {
                          var v = String(cellText(c));
                          if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
                            setCell(c, tglIndo(v));
                          } else if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/.test(v)) {
                            setCell(c, v.replace(/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?/, function (m) { return tglIndo(m); }));
                          }
                        });
                      });

                      tbl.table.headerRows = 1;
                      tbl.table.dontBreakRows = true;
                      tbl.table.widths = [16, 76, '*', 92, 58, 46, 116];
                      tbl.layout = {
                        fillColor: function (rowIndex) {
                          if (rowIndex === 0) return null;
                          return (rowIndex % 2 === 0) ? '#f3f6fb' : null;
                        },
                        hLineWidth: function () { return 0.5; },
                        vLineWidth: function () { return 0.5; },
                        hLineColor: function () { return '#cbd5e1'; },
                        vLineColor: function () { return '#cbd5e1'; },
                        paddingTop: function () { return 3; },
                        paddingBottom: function () { return 3; },
                        paddingLeft: function () { return 4; },
                        paddingRight: function () { return 4; }
                      };

                      var totalBaris = Math.max(0, tbl.table.body.length - 1);

                      // --- Susun ulang dokumen: kop + info + tabel ---
                      doc.content = [
                        { text: INSTANSI, alignment: 'center', bold: true, fontSize: 12 },
                        { text: JUDUL_LAPORAN, alignment: 'center', bold: true, fontSize: 10, color: '#004282', margin: [0, 2, 0, 0] },
                        { canvas: [{ type: 'line', x1: 0, y1: 5, x2: 762, y2: 5, lineWidth: 1.2, lineColor: '#004282' }], margin: [0, 3, 0, 8] },
                        {
                          columns: [
                            { width: '*', text: [{ text: 'Periode  : ', bold: true }, rangeLabel()] },
                            { width: 'auto', text: [{ text: 'Status  : ', bold: true }, statusFilterLabel()], alignment: 'right' }
                          ],
                          fontSize: 9, margin: [0, 0, 0, 2]
                        },
                        {
                          columns: [
                            { width: '*', text: [{ text: 'Dicetak  : ', bold: true }, cetakLabel()] },
                            { width: 'auto', text: [{ text: 'Jumlah data  : ', bold: true }, totalBaris + ' baris'], alignment: 'right' }
                          ],
                          fontSize: 9, margin: [0, 0, 0, 10]
                        },
                        tbl
                      ];

                      doc.pageMargins = [36, 34, 36, 42];
                      doc.defaultStyle.fontSize = 8;
                      doc.styles.tableHeader = {
                        fillColor: '#004282', color: '#ffffff', bold: true,
                        alignment: 'center', fontSize: 8, margin: [0, 2, 0, 2]
                      };
                      doc.footer = function (currentPage, pageCount) {
                        return {
                          margin: [36, 8, 36, 0],
                          columns: [
                            { text: 'Dihasilkan oleh Sistem ASIKKEKKU', italics: true, fontSize: 7, color: '#64748b' },
                            { text: 'Halaman ' + currentPage + ' dari ' + pageCount, alignment: 'right', fontSize: 7, color: '#64748b' }
                          ]
                        };
                      };
                    }
                  },
                  { extend: 'colvis', text: 'Kolom' },
      ],
      "responsive": false,
      "order": [[4, 'asc']],
      "ajax": {
        url: '<?= base_url() ?>manajemen_approval/ReportGetList',
        method: "GET",
        data: function (d) {
          d.date_start = $('#report-from').val();
          d.date_end   = $('#report-to').val();
          d.status = $('.report-status:checked').map(function () { return this.value; }).get();
        },
      },
      "drawCallback": function () {
        // Kolom "No." = nomor urut tampilan (1,2,3...) mengikuti urutan
        // & halaman aktif -- bukan indeks data.
        var api = this.api();
        var start = api.page.info().start;
        api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
          cell.innerHTML = start + i + 1;
        });
      },
      "columns": [
        { data: null, orderable: false, searchable: false,
          render: function (d, type, row, meta) { return meta.row + 1; } },
        { data: 'KegiatanNoSuratTugas' },
        { data: 'KegiatanJudul', render: dtClamp },
        { data: 'KegiatanNamaPelaksana', render: dtClamp },
        { data: 'KegiatanTanggal' },
        { data: 'report_status',
          render: function (d, type) {
            return (type === 'display') ? statusBadge(d) : statusText(d);
          } },
        { data: null, orderable: false,
          render: function (d, type, row) {
            var txt = (row['FlowDate'] || '-') + ' · ' + (row['FlowPosition'] || '-') + ' · ' + (row['FlowUserName'] || '-');
            return (type === 'display') ? '<span class="dt-c2">' + txt + '</span>' : txt;
          } },
      ],
      "columnDefs": [
        { targets: [0], class: 'ta-c', width: '4%' },
        { targets: [1], class: 'wp-keep' },
        { targets: [4], class: 'ta-c wp-keep' },
        { targets: [5], class: 'ta-c' },
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
  })
</script>