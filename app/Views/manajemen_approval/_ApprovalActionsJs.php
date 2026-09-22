<?php /* Aksi persetujuan / kembalikan (revisi / hentikan proses) / kirim-ulang.
        Disisipkan di dalam $(document).ready() ListApprovalFooter & ListDataFooter.
        Membutuhkan variabel `table_data` (DataTable halaman ybs). */ ?>

// Buka modal formulir persetujuan / kirim ulang.
$('body').on('click', '.KegiatanInfoApproval, .KegiatanRevisiKirim', function () {
  var id = $(this).attr('data');
  var $body = $('#modal-xl .modal-body');
  $body.html(
    '<div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">' +
      '<div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>' +
      '<div class="font-weight-medium">Memuat formulir persetujuan &amp; rincian...</div>' +
    '</div>'
  );
  $('#modal-xl .modal-footer').hide();
  $body.load(
    '<?= base_url() ?>manajemen_approval/GetApprovalFormInfoKegiatan?KegiatanID=' + id,
    function (response, status) {
      if (status === 'error') {
        $body.html('<div class="alert alert-danger m-3">Gagal memuat formulir persetujuan. Silakan coba lagi.</div>');
        show_toast('error', 'Gagal memuat formulir approval. Coba lagi.');
      }
    }
  );
});

function submitApproval() {
  var form = $('form.ApprovalFormInfoKegiatanSubmit');
  if (!form.length) return;
  if (!form.find('.FlowKeterangan').val().trim()) {
    infoAksi('error', 'Kolom Keterangan wajib diisi.');
    return;
  }
  var modal_id = form.closest('.modal').attr('id');
  $.ajax({
    type: 'POST', url: form.attr('action'), data: form.serialize(), dataType: 'json',
    success: function (data) {
      $('#' + modal_id).modal('hide');
      if (typeof table_data !== 'undefined' && table_data) table_data.ajax.reload();
      show_json_error(data);
    },
    error: function () {
      show_toast('error', 'Gagal memproses persetujuan. Coba lagi.');
    }
  });
}

// Sinkron petugas dengan peran tujuan pada blok "Kembalikan".
$('body').on('change', '.js-return-code', function () {
  var code = $(this).val();
  var $dest = $('.js-return-dest');
  $dest.find('option').each(function () {
    var mismatch = $(this).data('code') !== code;
    $(this).prop('hidden', mismatch).prop('disabled', mismatch);
  });
  var $first = $dest.find('option:not([hidden])').first();
  if ($first.length) $dest.val($first.val());
});
$('body').on('change', '.js-return-jenis', function () {
  // "Hentikan Proses" -> tujuan tidak perlu diisi (otomatis ke PJ).
  var isTerminate = $(this).val() === 'terminate';
  $('.js-return-role, .js-return-user').toggleClass('d-none', isTerminate);
});

// SETUJUI / KIRIM ULANG -> otomatis ke tahap berikutnya (tanpa pilih tujuan).
$('body').on('click', '.btn-approve', function () {
  var form = $(this).closest('form');
  var kirimUlang = /Kirim Ulang/i.test($(this).text());
  confirmAksi({
    title: kirimUlang ? 'Kirim ulang pengajuan ini?' : 'Setujui pengajuan ini?',
    text: 'Pengajuan otomatis diteruskan ke tahap berikutnya dan notifikasi WhatsApp dikirim ke pihak terkait.',
    confirmText: kirimUlang ? 'Ya, kirim ulang' : 'Ya, setujui', tone: 'success', icon: 'question'
  }).then(function (ok) {
    if (!ok) return;
    form.find('.FlowResult').val('1');
    form.find('.FlowRejectType').val('revisi');
    form.find('.FlowDestCode').val('');
    form.find('.FlowDestUser').val('');
    submitApproval();
  });
});

// KEMBALIKAN (revisi / hentikan proses).
$('body').on('click', '.btn-return', function () {
  var form = $(this).closest('form');
  var $blk = form.find('.js-return-block');
  if ($blk.hasClass('d-none')) {
    $blk.removeClass('d-none');
    $(this).data('return-ready', true).text('Kembalikan');
    $blk.find('.js-return-jenis').trigger('change').trigger('focus');
    $blk.find('.js-return-code').trigger('change');
    return;
  }
  var jenis = $blk.find('.js-return-jenis').val();          // 'revisi' | 'terminate'
  var terminate = (jenis === 'terminate');
  var code = terminate ? 'PJK' : $blk.find('.js-return-code').val();
  var dest = terminate ? '' : $blk.find('.js-return-dest').val();
  if (!terminate && !dest) { infoAksi('error', 'Petugas tujuan revisi wajib dipilih.'); return; }
  confirmAksi({
    title: terminate ? 'Kembalikan dengan permintaan HENTIKAN PROSES?' : 'Kembalikan untuk REVISI?',
    text: terminate
      ? 'Pengajuan dikirim ke PJ-Kegiatan untuk diputuskan (hentikan proses / revisi). Semua peran yang sudah dilewati diberi notifikasi WhatsApp.'
      : 'Pengajuan dikembalikan ke petugas tujuan untuk diperbaiki. Semua peran terkait diberi notifikasi WhatsApp.',
    confirmText: 'Ya, kembalikan', tone: 'danger', icon: 'warning'
  }).then(function (ok) {
    if (!ok) return;
    form.find('.FlowResult').val('0');
    form.find('.FlowRejectType').val(jenis);
    form.find('.FlowDestCode').val(code);
    form.find('.FlowDestUser').val(dest || '');
    submitApproval();
  });
});

// HENTIKAN PROSES: dari dalam modal (tombol form) ATAU dari baris List Data.
$('body').on('click', '.btn-terminate-inline, .KegiatanTerminate', function () {
  var id = $(this).attr('data');
  var note = ($(this).closest('form').find('.FlowKeterangan').val() || '').trim();
  confirmAksi({
    title: 'Hentikan proses pengajuan ini?',
    text: 'Status menjadi "Dibatalkan", berkas lampiran DIHAPUS permanen, dan semua pihak diberi notifikasi. Tindakan ini tidak dapat dibatalkan.',
    confirmText: 'Ya, hentikan', tone: 'danger', icon: 'warning'
  }).then(function (ok) {
    if (!ok) return;
    $.ajax({
      type: 'POST', url: '<?= base_url() ?>manajemen_approval/KegiatanTerminate',
      data: { KegiatanID: id, FlowKeterangan: note }, dataType: 'json',
      success: function (data) {
        $('#modal-xl').modal('hide');
        if (typeof table_data !== 'undefined' && table_data) table_data.ajax.reload();
        show_json_error(data);
      },
      error: function () {
        show_toast('error', 'Gagal menghentikan proses pengajuan. Coba lagi.');
      }
    });
  });
});
