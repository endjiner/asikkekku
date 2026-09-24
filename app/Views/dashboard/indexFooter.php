<?php
$is_pj_user = (isset($UserPosition) && $UserPosition === 'PJ-Kegiatan');
$q_url = $is_pj_user ? base_url('manajemen_approval/list_data') : base_url('manajemen_approval/list_approval');
?>
<script type="text/javascript">
  /* Dashboard — dimuat SETELAH jQuery/SweetAlert/toastr (lihat layouts/footer.php). */
  $(function () {

    /* Peringatan Dini: tombol "Ingatkan" -> kirim pengingat WhatsApp ke
       petugas tahap yang sedang berjalan + Penanggung Jawab Kegiatan,
       tanpa memuat ulang halaman. */
    $('body').on('click', '.ew-nudge', function () {
      var btn = $(this);
      if (btn.prop('disabled')) return;
      var id = btn.data('id');
      confirmAksi({
        title: 'Kirim pengingat WhatsApp?',
        text: 'Pengingat dikirim ke petugas tahap yang sedang berjalan dan Penanggung Jawab Kegiatan.',
        confirmText: 'Ya, kirim', tone: 'success', icon: 'question'
      }).then(function (ok) {
        if (!ok) return;
        var original = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.ajax({
          type: 'POST', url: '<?= base_url() ?>manajemen_approval/EarlyWarningNudgeOne',
          data: { KegiatanID: id }, dataType: 'json'
        }).done(function (res) {
          if (res && res.status) {
            btn.html('<?php echo str_replace(array("\r", "\n"), "", addslashes(svgico("check-double", 14))) ?> Terkirim');
            show_toast('success', (res && res.message) ? res.message : 'Pengingat WhatsApp terkirim.');
          } else {
            btn.prop('disabled', false).html(original);
            show_toast('error', (res && res.message) ? res.message : 'Gagal mengirim pengingat.');
          }
        }).fail(function () {
          btn.prop('disabled', false).html(original);
          show_toast('error', 'Gagal mengirim pengingat, coba lagi.');
        });
      });
    });

    /* Inisialisasi DataTable untuk Tabel Status Pengajuan PJ dengan Pagination & Internal Scroll */
    if ($('#table-pj-status').length) {
      var pjTable = $('#table-pj-status').DataTable({
        dom: "<'db-dt-top-bar'<'db-dt-ctrl-row'<'db-more-slot-pj'>l>f><'db-table-scroll-container't><'db-dt-bottom-bar'ip>",
        pageLength: 5,
        lengthMenu: [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Semua"]],
        ordering: false, // Mempertahankan urutan prioritas dari server (Revisi -> Draf -> OnProgress -> Selesai)
        autoWidth: false,
        scrollY: '380px',
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
          { targets: 0, width: '5%', className: 'ta-ci' },
          { targets: 1, width: '22%' },
          { targets: 2, width: '47%' },
          { targets: 3, width: '18%', className: 'ta-ci' },
          { targets: 4, width: '8%', className: 'ta-ci' }
        ],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Cari pengajuan saya...",
          lengthMenu: "Tampilkan _MENU_ baris",
          info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pengajuan",
          infoEmpty: "Tidak ada data pengajuan",
          infoFiltered: "(disaring dari _MAX_ total)",
          paginate: {
            first: "Awal",
            last: "Akhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
          }
        }
      });

      $('.db-more-slot-pj').html('<a href="<?= $q_url ?>" class="db-more db-more-mobile">Lihat Semua Data <?php echo str_replace(array("\r", "\n"), "", addslashes(svgico("arrow-right", 12))) ?></a>');

      // Otomatis membuka di halaman terakhir (urutan pengajuan terakhir)
      var pageInfo = pjTable.page.info();
      if (pageInfo && pageInfo.pages > 1) {
        pjTable.page(pageInfo.pages - 1).draw('page');
      }

      /* Klik baris tabel DI MANAPUN untuk toggle dropdown detail alur 6 meja */
      $('#table-pj-status tbody').on('click', 'tr.pj-table-row', function (e) {
        if ($(e.target).closest('a').length) {
          return; // Jangan tutup accordion jika user mengklik link di dalam detail
        }

        var tr = $(this);
        var row = pjTable.row(tr);

        if (row.child.isShown()) {
          row.child.hide();
          tr.removeClass('row-expanded');
        } else {
          var detailHtml = tr.find('.pj-row-hidden-detail').html();
          if (detailHtml) {
            row.child(detailHtml, 'pj-child-row-wrapper').show();
            tr.addClass('row-expanded');
          }
        }
      });
    }

    /* Inisialisasi DataTable untuk Antrean Tindakan Petugas / Admin dengan Pagination & Internal Scroll */
    if ($('#table-queue-status').length) {
      $('#table-queue-status').DataTable({
        dom: "<'db-dt-top-bar'<'db-dt-ctrl-row'<'db-more-slot-queue'>l>f><'db-table-scroll-container't><'db-dt-bottom-bar'ip>",
        pageLength: 5,
        lengthMenu: [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Semua"]],
        ordering: false,
        autoWidth: false,
        scrollY: '380px',
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
          { targets: 0, width: '5%', className: 'ta-ci' },
          { targets: 1, width: '22%' },
          { targets: 2, width: '47%' },
          { targets: 3, width: '14%', className: 'ta-ci' },
          { targets: 4, width: '12%', className: 'ta-ci' }
        ],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Cari berkas antrean...",
          lengthMenu: "Tampilkan _MENU_ baris",
          info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ berkas",
          infoEmpty: "Tidak ada data berkas",
          infoFiltered: "(disaring dari _MAX_ total)",
          paginate: {
            first: "Awal",
            last: "Akhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
          }
        }
      });

      $('.db-more-slot-queue').html('<a href="<?= $q_url ?>" class="db-more db-more-mobile">Lihat Semua Data <?php echo str_replace(array("\r", "\n"), "", addslashes(svgico("arrow-right", 12))) ?></a>');
    }

    /* Inisialisasi DataTable untuk Tabel Perlu Perhatian (SLA) dengan Pagination & Internal Scroll */
    if ($('#table-late-sla').length) {
      $('#table-late-sla').DataTable({
        dom: "<'db-dt-top-bar'<'db-dt-ctrl-row'<'db-more-slot-sla'>l>f><'db-table-scroll-container't><'db-dt-bottom-bar'ip>",
        pageLength: 5,
        lengthMenu: [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Semua"]],
        ordering: false,
        autoWidth: false,
        scrollY: '380px',
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
          { targets: 0, width: '6%', className: 'ta-ci' },
          { targets: 1, width: '54%' },
          { targets: 2, width: '20%', className: 'ta-ci' },
          { targets: 3, width: '20%', className: 'ta-ci' }
        ],
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Cari pengajuan tertahan...",
          lengthMenu: "Tampilkan _MENU_ baris",
          info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ berkas",
          infoEmpty: "Tidak ada data berkas",
          infoFiltered: "(disaring dari _MAX_ total)",
          paginate: {
            first: "Awal",
            last: "Akhir",
            next: "Selanjutnya",
            previous: "Sebelumnya"
          }
        }
      });

      $('.db-more-slot-sla').html('<a href="<?= $q_url ?>" class="db-more db-more-mobile">Buka Semua Data <?php echo str_replace(array("\r", "\n"), "", addslashes(svgico("arrow-right", 12))) ?></a>');
    }

  });
</script>
