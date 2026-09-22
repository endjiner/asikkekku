<?php
/**
 * Fragmen: daftar dokumen pencairan sebuah kegiatan.
 * $KegiatanID, $kegiatan, $dokumen (dari M_Dokumen::dokumenUntukKegiatan).
 * Dimuat via .load() di popup "Informasi Pengajuan".
 */
$base = base_url();
?>
<div class="dok-panel">
  <?php if (!empty($dokumen)): ?>
    <div class="mb-2">
      <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?php echo $base ?>dokumen/paket/<?php echo $KegiatanID ?>">
        Cetak / Unduh Paket (pilih dokumen, dengan/tanpa ttd)
      </a>
    </div>
  <?php endif ?>
  <?php if (empty($dokumen)): ?>
    <p class="text-muted mb-0">Belum ada template dokumen untuk jenis pengajuan ini.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0 dok-panel-tbl">
        <thead class="thead-light">
          <tr>
            <th style="width:34%">Dokumen</th>
            <th style="width:12%">Metode</th>
            <th style="width:14%">Dibuat oleh</th>
            <th>Rangkap / Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dokumen as $d): ?>
          <tr>
            <td>
              <?php echo html_escape($d['nama']) ?>
              <?php if (!empty($d['butuh_cap'])): ?><span class="badge badge-warning ml-1" title="Perlu cap dinas">cap</span><?php endif ?>
            </td>
            <td>
              <?php
                $mb = array('form_inapp' => 'badge-info', 'checklist' => 'badge-secondary', 'upload' => 'badge-dark', 'kartu_kendali' => 'badge-warning');
                $cls = isset($mb[$d['metode']]) ? $mb[$d['metode']] : 'badge-light';
              ?>
              <span class="badge <?php echo $cls ?>"><?php echo html_escape($d['metode']) ?></span>
            </td>
            <td class="text-sm"><?php echo html_escape($d['dibuat_oleh']) ?></td>
            <td>
              <?php $bolehLihat = !isset($d['boleh_lihat']) || $d['boleh_lihat']; $bolehIsi = !isset($d['boleh_isi']) || $d['boleh_isi']; ?>
              <?php if (!$bolehLihat): ?>
                <span class="text-muted text-sm"><i class="fas fa-lock mr-1"></i>Menunggu tahap sebelumnya selesai</span>
              <?php elseif ($d['metode'] === 'upload'): ?>
                <span class="text-muted text-sm">unggah berkas (SAKTI / TTE) — belum diwadahi</span>
              <?php else: ?>
                <div class="d-flex flex-wrap" style="gap:6px">
                  <?php foreach ($d['rangkap'] as $rk):
                    $q = ($rk['key'] === '-') ? '' : ('?r=' . rawurlencode($rk['key']));
                    $dot = $rk['terisi'] ? '<span class="text-success" title="sudah diisi">&#9679;</span>' : '<span class="text-muted" title="belum diisi">&#9675;</span>';
                    $ttdRow = isset($ttdmap[$d['kode']][$rk['key']]) ? $ttdmap[$d['kode']][$rk['key']] : array();
                  ?>
                  <span class="dok-rk">
                    <?php echo $dot ?>
                    <?php if ($rk['label'] !== ''): ?><span class="text-sm"><?php echo html_escape($rk['label']) ?></span><?php endif ?>
                    <?php if ($bolehIsi): ?>
                    <a class="btn btn-xs btn-outline-primary" target="_blank"
                       href="<?php echo $base ?>dokumen/form/<?php echo $d['kode'] ?>/<?php echo $KegiatanID . $q ?>">Isi</a>
                    <?php endif ?>
                    <a class="btn btn-xs btn-outline-secondary" target="_blank"
                       href="<?php echo $base ?>dokumen/cetak/<?php echo $d['kode'] ?>/<?php echo $KegiatanID . $q ?>">Cetak</a>
                    <a class="btn btn-xs btn-outline-dark"
                       href="<?php echo $base ?>dokumen/unduh/<?php echo $d['kode'] ?>/<?php echo $KegiatanID . $q ?>">PDF</a>
                    <?php foreach ((array) $d['slot_ttd'] as $slot):
                      $signed = isset($ttdRow[$slot]);
                    ?>
                      <a class="btn btn-xs <?php echo $signed ? 'btn-success' : 'btn-outline-success' ?>" target="_blank"
                         title="<?php echo $signed ? 'sudah ditandatangani ('.html_escape($ttdRow[$slot]['SignerNama']).')' : 'tanda tangani' ?>"
                         href="<?php echo $base ?>dokumen/ttd/<?php echo $d['kode'] ?>/<?php echo $KegiatanID ?><?php echo $q === '' ? '?' : $q . '&' ?>slot=<?php echo $slot ?>">
                        <?php echo $signed ? '&#10003; ' : '' ?>ttd:<?php echo html_escape($slot) ?></a>
                    <?php endforeach ?>
                  </span>
                  <?php endforeach ?>
                  <?php if (empty($d['rangkap'])): ?><span class="text-muted text-sm">(belum ada penerima di kegiatan)</span><?php endif ?>
                </div>
                <div class="text-sm text-muted mt-1"><?php echo (int) $d['ada'] ?>/<?php echo (int) $d['total'] ?> terisi</div>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <p class="text-sm text-muted mt-2 mb-0">
      Tanda tangan &amp; cap dipasang saat tahap terkait (menyusul). &#9679; = sudah diisi, &#9675; = belum.
    </p>
  <?php endif ?>
</div>

<?php
$userPos = isset($userPosition) ? $userPosition : (session('UserPosition') ?: '');
$canUpload = in_array($userPos, array('PJ-Kegiatan', 'SPP', 'SPM', 'PPSPM', 'SuperAdmin'), true);
?>

<!-- ==================== UPLOAD DOKUMEN EKSTERNAL ==================== -->
<div class="dok-upload-panel mt-3" id="dok-upload-panel">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h3 class="card-title font-weight-bold mb-0">Upload Dokumen Eksternal</h3>
      <span class="text-muted text-sm">(LPD, SPPD, SPM, SPP, dll.)</span>
    </div>
    <div class="card-body">

      <?php if ($canUpload): ?>
      <!-- Form upload khusus peran yang berwenang (PJ-Kegiatan, SPP, SPM, PPSPM) -->
      <form id="frm-upload" class="form-inline mb-3" enctype="multipart/form-data">
        <input type="hidden" name="KegiatanID" value="<?= (int) $KegiatanID ?>">
        <select name="tipe" id="sel-tipe" class="form-control form-control-sm mr-2 mb-1">
          <option value="lpd">LPD</option>
          <option value="sppd">SPPD</option>
          <option value="spm">SPM</option>
          <option value="spp">SPP</option>
          <option value="lamp16">Lampiran 16 Segmen</option>
          <option value="lain">Lain-lain</option>
        </select>
        <label class="btn btn-sm btn-outline-secondary mb-1 mr-2">
          Pilih PDF <input type="file" id="inp-file" name="file" accept="application/pdf" hidden>
        </label>
        <span id="fname-lbl" class="text-sm text-muted mr-2 mb-1"></span>
        <button type="submit" class="btn btn-sm btn-flat-teal mb-1">Upload</button>
        <span id="up-stat" class="ml-2 text-sm align-self-center"></span>
      </form>
      <?php endif; ?>

      <!-- Daftar file -->
      <div id="upload-list">
        <p class="text-muted text-sm">Memuat&hellip;</p>
      </div>
    </div>
  </div>
</div>

<style>
  .dok-panel-tbl td{ vertical-align:top; }
  .dok-panel .dok-rk{ display:inline-flex; align-items:center; gap:4px; border:1px solid #e3e6ea; border-radius:6px; padding:2px 6px; }
  .dok-panel .btn-xs{ padding:1px 7px; font-size:.72rem; line-height:1.4; }
  .up-item{ border:1px solid #e3e6ea; border-radius:8px; padding:8px 12px; margin-bottom:6px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .up-item .up-name{ flex:1; font-size:.85rem; font-weight:600; }
  .up-item .up-meta{ font-size:.75rem; color:#888; }
  .up-slot{ display:inline-flex; align-items:center; gap:4px; }
</style>

<script>
(function () {
  var base    = '<?= base_url() ?>';
  var KID     = <?= (int) $KegiatanID ?>;
  var userPos = '<?= htmlspecialchars($userPos, ENT_QUOTES) ?>';
  var canUp   = <?= $canUpload ? 'true' : 'false' ?>;

  function loadList() {
    fetch(base + 'dokumen/uploadEksternalList/' + KID, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (list) { renderList(list); })
      .catch(function () { document.getElementById('upload-list').innerHTML = '<p class="text-danger text-sm">Gagal memuat daftar.</p>'; });
  }

  function renderList(list) {
    var el = document.getElementById('upload-list');
    if (!list || !list.length) { el.innerHTML = '<p class="text-muted text-sm mb-0">Belum ada file yang diunggah.</p>'; return; }
    var html = '';
    list.forEach(function (r) {
      var ttdPpk   = r.ttd && r.ttd.ppk   ? '<span class="badge badge-success ml-1">PPK &#10003;</span>'   : '<span class="badge badge-light ml-1">PPK &mdash;</span>';
      var ttdPpspm = r.ttd && r.ttd.ppspm ? '<span class="badge badge-success ml-1">PPSPM &#10003;</span>' : '<span class="badge badge-light ml-1">PPSPM &mdash;</span>';

      var btnTtdPpk = '';
      if (userPos === 'PPK' || userPos === 'SuperAdmin') {
        btnTtdPpk = '<a class="btn btn-xs btn-outline-success ml-1" href="' + escHtml(r.url_ttd_ppk) + '" target="_blank">TTD PPK</a>';
      }
      var btnTtdPpspm = '';
      if (userPos === 'PPSPM' || userPos === 'SuperAdmin') {
        btnTtdPpspm = '<a class="btn btn-xs btn-outline-primary ml-1" href="' + escHtml(r.url_ttd_ppspm) + '" target="_blank">TTD PPSPM</a>';
      }
      var btnHapus = '';
      if (canUp || userPos === 'SuperAdmin') {
        btnHapus = '<button class="btn btn-xs btn-outline-danger ml-1" onclick="hapusUpload(' + r.UploadID + ')">Hapus</button>';
      }

      html += '<div class="up-item">'
        + '<span class="badge badge-info">' + escHtml(r.Tipe.toUpperCase()) + '</span>'
        + '<span class="up-name">' + escHtml(r.OriginalName || r.label) + '</span>'
        + '<span class="up-meta">' + (r.FileSize ? (Math.round(r.FileSize/1024) + ' KB') : '') + '</span>'
        + ttdPpk + ttdPpspm
        + btnTtdPpk + btnTtdPpspm
        + '<a class="btn btn-xs btn-outline-dark ml-1" href="' + escHtml(r.url_unduh) + '">&#8595; PDF</a>'
        + btnHapus
        + '</div>';
    });
    el.innerHTML = html;
  }

  function escHtml(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

  window.hapusUpload = function (id) {
    if (!confirm('Hapus file ini?')) return;
    var fd = new FormData(); fd.append('UploadID', id);
    if (window.CSRF) fd.append(window.CSRF.name, window.CSRF.hash);
    fetch(base + 'dokumen/uploadEksternalHapus', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.ok) loadList();
        else alert((res && res.msg) || 'Gagal.');
      });
  };

  // Tampilkan nama file dipilih
  document.getElementById('inp-file').addEventListener('change', function () {
    document.getElementById('fname-lbl').textContent = this.files[0] ? this.files[0].name : '';
  });

  // Submit upload
  document.getElementById('frm-upload').addEventListener('submit', function (e) {
    e.preventDefault();
    var stat = document.getElementById('up-stat');
    var fd = new FormData(this);
    if (window.CSRF) fd.append(window.CSRF.name, window.CSRF.hash);
    stat.textContent = 'Mengunggah…';
    fetch(base + 'dokumen/uploadEksternal', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        stat.textContent = '';
        if (res && res.ok) {
          document.getElementById('inp-file').value = '';
          document.getElementById('fname-lbl').textContent = '';
          if (window.show_toast) show_toast('success', 'File berhasil diunggah.');
          loadList();
        } else { if (window.show_toast) show_toast('error', (res && res.msg) || 'Gagal.'); else alert((res && res.msg) || 'Gagal.'); }
      }).catch(function () { stat.textContent = ''; if (window.show_toast) show_toast('error', 'Gagal menghubungi server.'); });
  });

  loadList();
})();
</script>
