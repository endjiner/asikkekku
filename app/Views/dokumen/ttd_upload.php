<?php
$base = base_url();
$row  = $row ?? array();
$slot = $slot ?? "ppk";
?>
<div class="content-wrapper bg-ligry">
  <section class="content pt-3">
    <div class="container-fluid">
      <div class="row">

        <!-- ======= PANEL PREVIEW PDF ======= -->
        <div class="col-lg-7">
          <div class="card">
            <div class="card-header bg-teal">
              <h3 class="card-title mb-0 font-weight-bold">
                Preview Dokumen &mdash; <?= html_escape($row["label"] ?? "") ?>
              </h3>
            </div>
            <div class="card-body p-2">
              <div class="d-flex align-items-center mb-2" style="gap:8px">
                <button class="btn btn-sm btn-outline-secondary" id="pg-prev">&laquo; Prev</button>
                <span class="text-sm" id="pg-info">Halaman 1 / ?</span>
                <button class="btn btn-sm btn-outline-secondary" id="pg-next">Next &raquo;</button>
              </div>
              <!-- Container canvas PDF + overlay drag -->
              <div id="pdf-wrap" style="position:relative;display:inline-block;border:1px solid #ccc;background:#eee;width:100%">
                <canvas id="pdf-canvas" style="display:block;width:100%"></canvas>
                <!-- Overlay drag area TTD -->
                <div id="ttd-overlay" style="position:absolute;top:0;left:0;right:0;bottom:0;cursor:crosshair"></div>
                <!-- Kotak TTD yang bisa di-drag -->
                <div id="ttd-box" style="position:absolute;border:2px dashed #e74c3c;background:rgba(231,76,60,.1);display:none;pointer-events:none"></div>
              </div>
              <p class="text-muted text-sm mt-2 mb-0">
                &#9888; Klik &amp; seret di atas dokumen untuk menentukan area tanda tangan.
              </p>
            </div>
          </div>
        </div>

        <!-- ======= PANEL TTD ======= -->
        <div class="col-lg-5">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title font-weight-bold mb-0">
                Tanda Tangan &mdash; <?= strtoupper(html_escape($slot)) ?>
              </h3>
            </div>
            <div class="card-body">

              <?php if (!$boleh): ?>
                <div class="alert alert-warning"><?= html_escape($boleh_pesan) ?></div>
              <?php elseif (!empty($row["ttd"][$slot])): ?>
                <div class="alert alert-success py-2 text-sm">
                  Sudah ditandatangani oleh <strong><?= html_escape($row["ttd"][$slot]["SignerNama"]) ?></strong>
                  pada <?= date("d/m/Y H:i", strtotime($row["ttd"][$slot]["SignedAt"])) ?>.
                  Tandatangani lagi untuk mengganti.
                </div>
              <?php endif; ?>

              <?php if ($boleh): ?>
              <!-- Posisi TTD (dari overlay drag) -->
              <div class="alert alert-info py-2 text-sm mb-2" id="pos-info">
                Belum ada area TTD dipilih. Klik &amp; seret di atas dokumen.
              </div>
              <!-- Kanvas TTD -->
              <div class="ttd-tools mb-2 d-flex flex-wrap" style="gap:8px">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="ttd-clear">Bersihkan</button>
                <label class="btn btn-sm btn-outline-secondary mb-0">
                  Unggah foto ttd <input type="file" id="ttd-file" accept="image/*" hidden>
                </label>
              </div>
              <div style="border:1px dashed #9aa4b0;border-radius:8px;background:#fff;display:inline-block;width:100%">
                <canvas id="ttd-canvas" width="480" height="180" style="display:block;width:100%;touch-action:none;cursor:crosshair"></canvas>
              </div>
              <div class="mt-3 d-flex" style="gap:8px">
                <button type="button" class="btn btn-primary" id="btn-tempel">
                  Simpan &amp; Tempel TTD
                </button>
                <a href="javascript:history.back()" class="btn btn-default">Kembali</a>
                <span id="ttd-stat" class="align-self-center text-sm"></span>
              </div>
              <?php endif; ?>

            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
<script>
(function () {
  var BASE      = '<?= $base ?>';
  var UPLOAD_ID = <?= (int) ($row["UploadID"] ?? 0) ?>;
  var SLOT      = '<?= html_escape($slot) ?>';
  var PDF_URL   = BASE + '<?= $row["FilePath"] ?? "" ?>';
  var BOLEH     = <?= $boleh ? 'true' : 'false' ?>;

  // ── PDF.js ────────────────────────────────────────────────────
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  var pdfDoc = null, curPage = 1, totalPages = 0;
  var canvas = document.getElementById('pdf-canvas');
  var ctx    = canvas.getContext('2d');

  function renderPage(num) {
    pdfDoc.getPage(num).then(function (page) {
      var vp    = page.getViewport({ scale: 1.5 });
      canvas.width  = vp.width;
      canvas.height = vp.height;
      page.render({ canvasContext: ctx, viewport: vp });
      document.getElementById('pg-info').textContent = 'Halaman ' + num + ' / ' + totalPages;
      curPage = num;
      resetBox();
    });
  }

  pdfjsLib.getDocument(PDF_URL).promise.then(function (pdf) {
    pdfDoc = pdf; totalPages = pdf.numPages;
    renderPage(1);
  }).catch(function (e) {
    document.getElementById('pg-info').textContent = 'Gagal memuat PDF: ' + e.message;
  });

  document.getElementById('pg-prev').addEventListener('click', function () {
    if (curPage > 1) renderPage(curPage - 1);
  });
  document.getElementById('pg-next').addEventListener('click', function () {
    if (curPage < totalPages) renderPage(curPage + 1);
  });

  // ── Drag overlay untuk pilih area TTD ─────────────────────────
  var overlay = document.getElementById('ttd-overlay');
  var box     = document.getElementById('ttd-box');
  var posInfo = document.getElementById('pos-info');
  var dragging = false, startX, startY, selPos = null;

  function relPos(e) {
    var r = overlay.getBoundingClientRect();
    var t = e.touches ? e.touches[0] : e;
    return { x: (t.clientX - r.left) / r.width, y: (t.clientY - r.top) / r.height };
  }

  function resetBox() { box.style.display = 'none'; selPos = null; if (posInfo) posInfo.textContent = 'Belum ada area TTD dipilih.'; }

  if (BOLEH) {
    overlay.addEventListener('mousedown', function (e) {
      var p = relPos(e); startX = p.x; startY = p.y; dragging = true; e.preventDefault();
    });
    overlay.addEventListener('mousemove', function (e) {
      if (!dragging) return;
      var p = relPos(e);
      var l = Math.min(startX, p.x), t = Math.min(startY, p.y);
      var w = Math.abs(p.x - startX), h = Math.abs(p.y - startY);
      box.style.left   = (l * 100) + '%';
      box.style.top    = (t * 100) + '%';
      box.style.width  = (w * 100) + '%';
      box.style.height = (h * 100) + '%';
      box.style.display = 'block';
    });
    window.addEventListener('mouseup', function (e) {
      if (!dragging) return; dragging = false;
      var p = relPos(e);
      var l = Math.min(startX, p.x), t = Math.min(startY, p.y);
      var w = Math.abs(p.x - startX), h = Math.abs(p.y - startY);
      if (w < 0.02 || h < 0.01) { resetBox(); return; }
      selPos = { x: l, y: t, w: w, h: h };
      if (posInfo) posInfo.textContent = 'Area TTD: X=' + (l*100).toFixed(1) + '% Y=' + (t*100).toFixed(1) + '% W=' + (w*100).toFixed(1) + '% H=' + (h*100).toFixed(1) + '%';
    });
  }

  // ── Kanvas TTD ─────────────────────────────────────────────────
  var cv   = document.getElementById('ttd-canvas');
  var cvCtx = cv ? cv.getContext('2d') : null;
  if (cvCtx) {
    cvCtx.lineWidth = 2.2; cvCtx.lineJoin = 'round'; cvCtx.lineCap = 'round'; cvCtx.strokeStyle = '#0b1b3a';
    var drawing = false, lastP = null, dirty = false;

    function cvPos(e) {
      var r = cv.getBoundingClientRect();
      var t = e.touches ? e.touches[0] : e;
      return { x: (t.clientX - r.left) * (cv.width / r.width), y: (t.clientY - r.top) * (cv.height / r.height) };
    }
    cv.addEventListener('mousedown', function (e) { drawing = true; lastP = cvPos(e); e.preventDefault(); });
    cv.addEventListener('mousemove', function (e) {
      if (!drawing) return;
      var p = cvPos(e);
      cvCtx.beginPath(); cvCtx.moveTo(lastP.x, lastP.y); cvCtx.lineTo(p.x, p.y); cvCtx.stroke();
      lastP = p; dirty = true; e.preventDefault();
    });
    window.addEventListener('mouseup', function () { drawing = false; });

    var clearBtn = document.getElementById('ttd-clear');
    if (clearBtn) clearBtn.addEventListener('click', function () {
      cvCtx.clearRect(0, 0, cv.width, cv.height); dirty = false;
    });

    var fileInp = document.getElementById('ttd-file');
    if (fileInp) fileInp.addEventListener('change', function (ev) {
      var f = ev.target.files[0]; if (!f) return;
      var img = new Image();
      img.onload = function () {
        cvCtx.clearRect(0, 0, cv.width, cv.height);
        var s = Math.min(cv.width / img.width, cv.height / img.height);
        var w = img.width * s, h = img.height * s;
        cvCtx.drawImage(img, (cv.width - w) / 2, (cv.height - h) / 2, w, h);
        var d = cvCtx.getImageData(0, 0, cv.width, cv.height), a = d.data;
        for (var i = 0; i < a.length; i += 4) {
          if (a[i] > 235 && a[i+1] > 235 && a[i+2] > 235) a[i+3] = 0;
        }
        cvCtx.putImageData(d, 0, 0); dirty = true;
      };
      img.src = URL.createObjectURL(f);
    });
  }

  // ── Simpan & Tempel ────────────────────────────────────────────
  var btnTempel = document.getElementById('btn-tempel');
  if (btnTempel) btnTempel.addEventListener('click', function () {
    var stat = document.getElementById('ttd-stat');
    var toast = function (t, m) { if (window.show_toast) show_toast(t, m); else alert(m); };
    if (!dirty) { toast('error', 'Kotak tanda tangan masih kosong.'); return; }
    if (!selPos) { toast('error', 'Pilih area TTD di atas dokumen terlebih dahulu.'); return; }
    stat.textContent = 'menyimpan…';
    var fd = new FormData();
    fd.append('UploadID', UPLOAD_ID);
    fd.append('slot', SLOT);
    fd.append('page', curPage);
    fd.append('x', selPos.x);
    fd.append('y', selPos.y);
    fd.append('w', selPos.w);
    fd.append('h', selPos.h);
    fd.append('image', cv.toDataURL('image/png'));
    if (window.CSRF) fd.append(window.CSRF.name, window.CSRF.hash);
    fetch(BASE + 'dokumen/ttdUploadSimpan', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        stat.textContent = '';
        if (res && res.ok) {
          toast('success', res.msg || 'Tanda tangan tersimpan.');
          setTimeout(function () { history.back(); }, 900);
        } else { toast('error', (res && res.msg) || 'Gagal.'); }
      }).catch(function () { stat.textContent = ''; toast('error', 'Gagal menghubungi server.'); });
  });
})();
</script>
