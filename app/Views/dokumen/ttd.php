<div class="content-wrapper bg-ligry">
  <section class="content pt-3">
    <div class="container-fluid">
      <div class="card mx-auto" style="max-width:640px">
        <div class="card-header bg-teal">
          <h3 class="card-title mb-0 font-weight-bold">
            Tanda Tangan &mdash; <?php echo html_escape($tpl['nama']) ?>
          </h3>
        </div>
        <div class="card-body">
          <p class="text-sm mb-2">
            Slot: <strong><?php echo html_escape(strtoupper($slot)) ?></strong>
            <?php if ($rangkap !== '-'): ?> &middot; Rangkap: <strong><?php echo html_escape($rangkap) ?></strong><?php endif ?>
            &middot; Kegiatan #<?php echo (int) $KegiatanID ?>
          </p>

          <?php if (!empty($aktif[$slot])): ?>
            <div class="alert alert-success py-2 text-sm">
              Sudah ditandatangani oleh <strong><?php echo html_escape($aktif[$slot]['SignerNama']) ?></strong>
              pada <?php echo date('d/m/Y H:i', strtotime($aktif[$slot]['SignedAt'])) ?>.
              Menandatangani lagi akan mengganti yang lama.
            </div>
          <?php endif ?>

          <?php if (!$boleh): ?>
            <div class="alert alert-warning py-2 text-sm mb-0"><?php echo html_escape($boleh_pesan) ?></div>
          <?php else: ?>
            <div class="ttd-tools mb-2 d-flex flex-wrap" style="gap:8px">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="ttd-clear">Bersihkan</button>
              <label class="btn btn-sm btn-outline-secondary mb-0">
                Unggah foto baris ttd
                <input type="file" id="ttd-file" accept="image/*" hidden>
              </label>
              <span class="text-sm text-muted align-self-center">Coret di kotak, atau unggah foto tanda tangan (akan dipotong latar putihnya).</span>
            </div>
            <div class="ttd-canvas-wrap">
              <canvas id="ttd-canvas" width="560" height="220"></canvas>
            </div>
            <div class="mt-3 d-flex" style="gap:8px">
              <button type="button" class="btn btn-primary" id="ttd-save">Simpan tanda tangan</button>
              <a class="btn btn-default" href="javascript:history.back()">Kembali</a>
              <span id="ttd-stat" class="align-self-center text-sm"></span>
            </div>
          <?php endif ?>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  .ttd-canvas-wrap{ border:1px dashed #9aa4b0; border-radius:8px; background:#fff; display:inline-block; }
  #ttd-canvas{ display:block; touch-action:none; cursor:crosshair; width:100%; max-width:560px; height:220px; }
</style>

<script>
(function () {
  var cv = document.getElementById('ttd-canvas');
  if (!cv) return;
  var ctx = cv.getContext('2d');
  ctx.lineWidth = 2.2; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.strokeStyle = '#0b1b3a';
  var drawing = false, last = null, dirty = false;

  function pos(e) {
    var r = cv.getBoundingClientRect();
    var t = e.touches ? e.touches[0] : e;
    return { x: (t.clientX - r.left) * (cv.width / r.width), y: (t.clientY - r.top) * (cv.height / r.height) };
  }
  function start(e) { drawing = true; last = pos(e); e.preventDefault(); }
  function move(e) {
    if (!drawing) return;
    var p = pos(e);
    ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
    last = p; dirty = true; e.preventDefault();
  }
  function end() { drawing = false; }
  cv.addEventListener('mousedown', start); cv.addEventListener('mousemove', move);
  window.addEventListener('mouseup', end);
  cv.addEventListener('touchstart', start); cv.addEventListener('touchmove', move); cv.addEventListener('touchend', end);

  document.getElementById('ttd-clear').addEventListener('click', function () {
    ctx.clearRect(0, 0, cv.width, cv.height); dirty = false;
  });

  document.getElementById('ttd-file').addEventListener('change', function (ev) {
    var f = ev.target.files[0]; if (!f) return;
    var img = new Image();
    img.onload = function () {
      ctx.clearRect(0, 0, cv.width, cv.height);
      var s = Math.min(cv.width / img.width, cv.height / img.height);
      var w = img.width * s, h = img.height * s;
      ctx.drawImage(img, (cv.width - w) / 2, (cv.height - h) / 2, w, h);
      // buang latar hampir-putih -> transparan
      var d = ctx.getImageData(0, 0, cv.width, cv.height), a = d.data;
      for (var i = 0; i < a.length; i += 4) {
        if (a[i] > 235 && a[i + 1] > 235 && a[i + 2] > 235) a[i + 3] = 0;
      }
      ctx.putImageData(d, 0, 0); dirty = true;
    };
    img.src = URL.createObjectURL(f);
  });

  document.getElementById('ttd-save').addEventListener('click', function () {
    var stat = document.getElementById('ttd-stat');
    var toast = function (t, m) { if (window.show_toast) show_toast(t, m); else alert(m); };
    if (!dirty) { toast('error', 'Kotak tanda tangan masih kosong.'); return; }
    stat.textContent = 'menyimpan…'; stat.className = 'align-self-center text-sm text-muted';
    jQuery.post('<?php echo base_url() ?>dokumen/ttdSimpan', {
      kode: '<?php echo html_escape($kode) ?>',
      KegiatanID: '<?php echo (int) $KegiatanID ?>',
      rangkap: <?php echo json_encode($rangkap) ?>,
      slot: '<?php echo html_escape($slot) ?>',
      image: cv.toDataURL('image/png')
    }, null, 'json').done(function (r) {
      if (r && r.ok) {
        stat.textContent = ''; toast('success', (r && r.msg) || 'Tanda tangan tersimpan.');
        setTimeout(function () { history.back(); }, 900);
      } else {
        stat.textContent = ''; toast('error', (r && r.msg) || 'Gagal menyimpan tanda tangan.');
      }
    }).fail(function () {
      stat.textContent = ''; toast('error', 'Gagal menghubungi server.');
    });
  });
})();
</script>
