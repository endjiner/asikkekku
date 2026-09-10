<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="content-wrapper bg-ligry">
  <section class="content pt-3">
    <div class="container-fluid">
      <div class="card mx-auto" style="max-width:820px">
        <div class="card-header bg-teal">
          <h3 class="card-title mb-0 font-weight-bold">Cetak / Unduh Paket Dokumen</h3>
        </div>
        <div class="card-body">
          <p class="text-sm mb-2">
            Kegiatan #<?php echo (int) $KegiatanID ?>
            <?php if (!empty($kegiatan['KegiatanJudul'])): ?>&mdash; <?php echo html_escape($kegiatan['KegiatanJudul']) ?><?php endif ?>
          </p>

          <div class="d-flex flex-wrap align-items-center mb-2" style="gap:14px">
            <div>
              <label class="mr-2 mb-0"><input type="radio" name="pkt-ttd" value="1" checked> Dengan tanda tangan</label>
              <label class="mb-0"><input type="radio" name="pkt-ttd" value="0"> Tanpa tanda tangan (arsip fisik)</label>
            </div>
            <span class="text-sm">
              <a href="#" id="pkt-all">pilih semua</a> &middot;
              <a href="#" id="pkt-none">kosongkan</a>
            </span>
          </div>

          <table class="table table-sm table-bordered">
            <thead class="thead-light"><tr><th style="width:36px"></th><th>Dokumen</th><th style="width:30%">Rangkap</th></tr></thead>
            <tbody>
              <?php foreach ($dokumen as $d): ?>
                <?php if ($d['metode'] === 'upload') continue; ?>
                <?php foreach ($d['rangkap'] as $rk):
                  $val = $d['kode'] . ':' . $rk['key'];
                  $signed = isset($ttdmap[$d['kode']][$rk['key']]);
                ?>
                <tr>
                  <td class="text-center"><input type="checkbox" class="pkt-cb" value="<?php echo html_escape($val) ?>" <?php echo $rk['terisi'] ? 'checked' : '' ?>></td>
                  <td>
                    <?php echo html_escape($d['nama']) ?>
                    <?php echo $rk['terisi'] ? '<span class="text-success" title="sudah diisi">&#9679;</span>' : '<span class="text-muted" title="belum diisi">&#9675;</span>' ?>
                    <?php echo $signed ? '<span class="badge badge-success">ttd</span>' : '' ?>
                  </td>
                  <td class="text-sm"><?php echo $rk['label'] !== '' ? html_escape($rk['label']) : '&mdash;' ?></td>
                </tr>
                <?php endforeach ?>
              <?php endforeach ?>
            </tbody>
          </table>

          <button type="button" class="btn btn-outline-dark" id="pkt-pdf">Unduh PDF gabungan</button>
          <button type="button" class="btn btn-primary" id="pkt-go">Buka untuk Cetak (browser)</button>
          <span id="pkt-stat" class="text-sm ml-2 text-muted"></span>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
(function () {
  var base = '<?php echo base_url() ?>';
  var kid = '<?php echo (int) $KegiatanID ?>';
  function cbs() { return Array.prototype.slice.call(document.querySelectorAll('.pkt-cb')); }
  document.getElementById('pkt-all').addEventListener('click', function (e) { e.preventDefault(); cbs().forEach(function (c) { c.checked = true; }); });
  document.getElementById('pkt-none').addEventListener('click', function (e) { e.preventDefault(); cbs().forEach(function (c) { c.checked = false; }); });
  function selQs() {
    var sel = cbs().filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
    var stat = document.getElementById('pkt-stat');
    if (!sel.length) { stat.textContent = 'Pilih minimal satu dokumen.'; return null; }
    var ttd = document.querySelector('input[name=pkt-ttd]:checked').value;
    return { n: sel.length, qs: sel.map(function (v) { return 'd[]=' + encodeURIComponent(v); }).join('&') + '&ttd=' + ttd };
  }
  document.getElementById('pkt-go').addEventListener('click', function () {
    var s = selQs(); if (!s) return;
    window.open(base + 'dokumen/paketCetak/' + kid + '?' + s.qs, '_blank');
    document.getElementById('pkt-stat').textContent = s.n + ' dokumen dibuka di tab baru.';
  });
  document.getElementById('pkt-pdf').addEventListener('click', function () {
    var s = selQs(); if (!s) return;
    window.location = base + 'dokumen/paketUnduh/' + kid + '?' + s.qs;
    document.getElementById('pkt-stat').textContent = 'menyiapkan PDF ' + s.n + ' dokumen…';
  });
})();
</script>
