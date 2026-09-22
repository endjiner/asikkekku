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

  });
</script>
