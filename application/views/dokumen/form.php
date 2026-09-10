<?php
/** @var array $tpl  @var string $kode  @var array $payload  @var array $kegiatan
 *  @var string $rangkap  @var array $rangkap_list  @var int $KegiatanID */
$isPerPenerima = ($tpl['rangkap'] === 'per_penerima');
?>
<link rel="stylesheet" href="<?= base_url() ?>assets/css/dokumen.css?v=20260910-wide">

<div class="content-wrapper bg-ligry dok-page-wide">
  <div class="content-header">
    <div class="container-fluid d-flex align-items-center flex-wrap gap-2">
      <h1 class="m-0 h4 font-weight-bold text-dark d-flex align-items-center gap-2">
        <?php echo illus('activity', 34); ?>
        <span class="ml-2"><?php echo dok_e($tpl['nama']) ?></span>
      </h1>
      <span class="badge-soft-info ml-2">Kegiatan #<?php echo (int) $KegiatanID ?></span>
      <a href="<?= base_url('dokumen/pilih') ?>" class="btn btn-sm btn-default ml-auto"><?php echo svgico('arrow-left', 15) ?> Kembali</a>
    </div>
  </div>

  <section class="content dok-content-wide">
    <div class="container-fluid">

      <?php if (!empty($tpl['catatan'])): ?>
        <div class="alert-soft-warning mb-3"><?php echo dok_e($tpl['catatan']) ?></div>
      <?php endif ?>

      <div class="dok-split">
        <!-- ============ PANEL ISIAN ============ -->
        <div class="dok-fields card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title font-weight-bold mb-0">Isian Field</h3>
            <?php if ($isPerPenerima): ?>
            <div class="form-inline">
              <label class="text-sm mr-2 mb-0">Rangkap:</label>
              <select id="sel-rangkap" class="form-control form-control-sm">
                <?php $rl = $rangkap_list ?: array(); if (empty($rl)) $rl = array($rangkap !== '-' ? $rangkap : ''); ?>
                <?php foreach ($rl as $r): ?>
                  <option value="<?php echo dok_e($r) ?>" <?php echo ($r === $rangkap ? 'selected' : '') ?>><?php echo dok_e($r ?: '(tanpa nama)') ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <?php endif ?>
          </div>
          <div class="card-body">
            <form id="dok-form" autocomplete="off" onsubmit="return false">

              <?php if (!empty($tpl['checklist'])): ?>
                <div class="dok-cl-box card card-outline card-warning mb-3">
                  <div class="card-header d-flex align-items-center justify-content-between p-2">
                    <span class="font-weight-bold text-sm text-dark"><i class="fas fa-tasks mr-1 text-warning"></i> Lembar Pemeriksaan Berkas</span>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-xs btn-outline-success font-weight-bold" id="btn-cl-all-ada" title="Setel semua item menjadi ADA & SESUAI">
                        ⚡ Centang Semua ADA &amp; SESUAI
                      </button>
                      <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-cl-reset">
                        Reset
                      </button>
                    </div>
                  </div>
                  <div class="card-body p-2">
                    <?php
                    $clVal = isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : array();
                    foreach ($tpl['checklist'] as $secKey => $sec):
                      $secTitle = $sec['judul'];
                      $secCols  = $sec['kolom'];
                      $secItems = $sec['item'];
                    ?>
                      <div class="font-weight-bold text-xs text-uppercase bg-light p-1 px-2 mb-2 rounded border text-dark">
                        <?= dok_e($secTitle) ?>
                      </div>
                      <div class="table-responsive mb-3" style="overflow-x:hidden;">
                        <table class="table table-sm table-bordered mb-0" style="font-size:.82rem; width:100%;">
                          <thead>
                            <tr class="bg-light">
                              <th style="width:28px;" class="text-center">#</th>
                              <th>Item Berkas</th>
                              <th style="width:130px;" class="text-center">Status</th>
                              <th style="width:36%;">Keterangan (opsional)</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($secItems as $idx => $label):
                              $rowVal = isset($clVal[$secKey][$idx]) ? $clVal[$secKey][$idx] : array();
                              $stVal  = isset($rowVal['status']) ? strtolower(trim($rowVal['status'])) : '';
                              $ketVal = isset($rowVal['ket']) ? $rowVal['ket'] : '';
                              $radName = 'cl_' . $secKey . '_' . $idx;
                              $isYa    = ($stVal === 'ya' || $stVal === 'ada' || $stVal === 'sesuai');
                              $isTidak = ($stVal === 'tidak');
                            ?>
                            <tr class="dok-cl-item" data-sec="<?= dok_e($secKey) ?>" data-idx="<?= (int)$idx ?>">
                              <td class="text-center text-muted font-weight-bold" style="vertical-align:middle;"><?= $idx + 1 ?></td>
                              <td class="font-weight-semibold" style="vertical-align:middle;"><?= dok_e($label) ?></td>
                              <td class="text-center" style="vertical-align:middle; white-space:nowrap;">
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                  <label class="btn btn-xs btn-outline-success <?= $isYa ? 'active' : '' ?>" style="padding:2px 8px; font-weight:600; width:50%;">
                                    <input type="radio" name="<?= $radName ?>" value="ya" <?= $isYa ? 'checked' : '' ?>> <?= dok_e($secCols[0]) ?>
                                  </label>
                                  <label class="btn btn-xs btn-outline-danger <?= $isTidak ? 'active' : '' ?>" style="padding:2px 8px; font-weight:600; width:50%;">
                                    <input type="radio" name="<?= $radName ?>" value="tidak" <?= $isTidak ? 'checked' : '' ?>> <?= dok_e($secCols[1]) ?>
                                  </label>
                                </div>
                              </td>
                              <td style="vertical-align:middle;">
                                <input type="text" class="form-control form-control-sm cl-ket" style="height:28px; font-size:.8rem;" placeholder="Keterangan / -" value="<?= dok_e($ketVal) ?>">
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($tpl['alur_steps'])): ?>
                <div class="dok-alur-box card card-outline card-info mb-3">
                  <div class="card-header d-flex align-items-center justify-content-between p-2">
                    <span class="font-weight-bold text-sm text-dark"><i class="fas fa-stream mr-1 text-info"></i> Alur Verifikasi ASIKKEKKU (7 Tahap)</span>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-xs btn-outline-info font-weight-bold" id="btn-alur-all">
                        ⚡ Centang Semua Alur
                      </button>
                      <button type="button" class="btn btn-xs btn-outline-secondary" id="btn-alur-reset">
                        Reset Alur
                      </button>
                    </div>
                  </div>
                  <div class="card-body p-2">
                    <div class="text-xs text-muted mb-2">Tahapan ini tercentang otomatis dari riwayat alur pengajuan, namun dapat disesuaikan manual:</div>
                    <table class="table table-sm table-bordered mb-0" style="font-size:.78rem;">
                      <thead>
                        <tr class="bg-light">
                          <th style="width:24px;" class="text-center">#</th>
                          <th>Tahapan Alur Keterangan</th>
                          <th style="width:70px;" class="text-center">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $alurVal = isset($payload['alur_check']) && is_array($payload['alur_check']) ? $payload['alur_check'] : array(0,0,0,0,0,0,0);
                        foreach ($tpl['alur_steps'] as $aIdx => $aLabel):
                          $aChecked = !empty($alurVal[$aIdx]);
                        ?>
                        <tr>
                          <td class="text-center text-muted"><?= $aIdx + 1 ?></td>
                          <td><?= dok_e($aLabel) ?></td>
                          <td class="text-center">
                            <div class="custom-control custom-checkbox">
                              <input type="checkbox" class="custom-control-input dok-alur-cb" id="alur-cb-<?= $aIdx ?>" data-idx="<?= $aIdx ?>" <?= $aChecked ? 'checked' : '' ?>>
                              <label class="custom-control-label" for="alur-cb-<?= $aIdx ?>">✓</label>
                            </div>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              <?php endif; ?>

              <?php foreach ($tpl['fields'] as $f):
                $val  = isset($payload[$f['key']]) ? $payload[$f['key']] : '';
                $auto = ($f['sumber'] !== 'manual');
                $ro   = $auto ? 'readonly' : '';
                // Separator antar seksi "diisi_oleh" (Verifikator / SPM / dll)
                $prevDiisi = isset($prevDiisiOleh) ? $prevDiisiOleh : null;
                $curDiisi  = isset($f['diisi_oleh']) ? $f['diisi_oleh'] : null;
                if ($curDiisi && $curDiisi !== $prevDiisi):
              ?>
                <div class="dok-seksi-header mt-3 mb-1">
                  <span class="badge badge-secondary text-sm"><?php echo dok_e($curDiisi) ?></span>
                </div>
              <?php endif; $prevDiisiOleh = $curDiisi; ?>

                <?php if ($f['tipe'] === 'rows'): ?>
                  <div class="form-group dok-rows" data-rows="<?php echo dok_e($f['key']) ?>">
                    <label class="font-weight-semibold"><?php echo dok_e($f['label']) ?></label>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-1 rows-tbl">
                        <thead><tr>
                          <?php foreach ($f['kolom'] as $c): ?><th class="text-sm"><?php echo dok_e($c['label']) ?></th><?php endforeach ?>
                          <th style="width:34px"></th>
                        </tr></thead>
                        <tbody>
                          <?php
                          $rows = (is_array($val) && $val) ? $val : array(array());
                          foreach ($rows as $rv): ?>
                          <tr class="row-item">
                            <?php foreach ($f['kolom'] as $c): ?>
                              <td><input type="<?php echo $c['tipe'] === 'number' ? 'number' : 'text' ?>"
                                   class="form-control form-control-sm"
                                   data-col="<?php echo dok_e($c['key']) ?>"
                                   data-fmt="<?php echo $c['tipe'] === 'uang' ? 'uang' : '' ?>"
                                   value="<?php echo dok_e(isset($rv[$c['key']]) ? $rv[$c['key']] : '') ?>"></td>
                            <?php endforeach ?>
                            <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger row-del">&times;</button></td>
                          </tr>
                          <?php endforeach ?>
                        </tbody>
                      </table>
                    </div>
                    <button type="button" class="btn btn-xs btn-outline-navy row-add">+ Tambah baris</button>
                  </div>
                <?php elseif ($f['tipe'] === 'textarea'): ?>
                  <div class="form-group">
                    <label class="font-weight-semibold"><?php echo dok_e($f['label']) ?> <?php if ($auto): ?><span class="dok-auto">otomatis</span><?php endif ?></label>
                    <textarea class="form-control form-control-sm dok-f" data-key="<?php echo dok_e($f['key']) ?>" rows="<?php echo in_array($f['key'], array('petugas_perjadin', 'judul_kegiatan')) ? 3 : 2 ?>" <?php echo $ro ?>><?php echo dok_e($val) ?></textarea>
                  </div>
                <?php elseif ($f['tipe'] === 'select' || $f['tipe'] === 'pilih'): ?>
                <div class="form-group">
                  <label class="font-weight-semibold"><?php echo dok_e($f['label']) ?></label>
                  <select class="form-control form-control-sm dok-f" data-key="<?php echo dok_e($f['key']) ?>" <?php echo $ro ?>>
                    <option value="">&mdash;</option>
                    <?php $opsi = isset($f['opsi']) ? $f['opsi'] : (isset($f['options']) ? $f['options'] : array()); foreach ($opsi as $o): ?>
                      <option value="<?php echo dok_e($o) ?>" <?php echo ((string)$val === (string)$o ? 'selected' : '') ?>><?php echo dok_e($o) ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
                <?php elseif ($f['tipe'] === 'checkbox'): ?>
                  <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input dok-f" id="cb-<?php echo dok_e($f['key']) ?>" data-key="<?php echo dok_e($f['key']) ?>" <?php echo (!empty($val) ? 'checked' : '') ?>>
                    <label class="form-check-label font-weight-semibold" for="cb-<?php echo dok_e($f['key']) ?>"><?php echo dok_e($f['label']) ?></label>
                  </div>
                <?php else:
                  $inputType = ($f['tipe'] === 'date') ? 'date' : (($f['tipe'] === 'number') ? 'number' : 'text');
                ?>
                  <div class="form-group">
                    <label class="font-weight-semibold"><?php echo dok_e($f['label']) ?> <?php if ($auto): ?><span class="dok-auto">otomatis</span><?php endif ?></label>
                    <input type="<?php echo $inputType ?>" class="form-control form-control-sm dok-f"
                      data-key="<?php echo dok_e($f['key']) ?>"
                      data-fmt="<?php echo $f['tipe'] === 'uang' ? 'uang' : '' ?>"
                      value="<?php echo dok_e($val) ?>" <?php echo $ro ?>>
                  </div>
                <?php endif ?>
              <?php endforeach ?>

              <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-flat-teal" id="btn-simpan"><?php echo svgico('save', 16) ?> Simpan field</button>
                <a class="btn btn-outline-navy" id="btn-cetak" target="_blank"><?php echo svgico('file-pdf', 16) ?> Cetak</a>
              </div>
            </form>
          </div>
        </div>

        <!-- ============ PANEL PRATINJAU ============ -->
        <div class="dok-preview card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title font-weight-bold mb-0">Pratinjau Format</h3>
            <span class="text-muted text-sm" id="pv-status">memuat&hellip;</span>
          </div>
          <div class="card-body">
            <div id="pv-sheet" class="doc-sheet-wrap"></div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
(function () {
  var base = '<?php echo base_url() ?>';
  var KODE = <?php echo json_encode($kode) ?>;
  var KEG  = <?php echo (int) $KegiatanID ?>;
  var form = document.getElementById('dok-form');
  var pvSheet = document.getElementById('pv-sheet');
  var pvStatus = document.getElementById('pv-status');
  var selR = document.getElementById('sel-rangkap');
  var rangkap = selR ? selR.value : '-';

  function fmtUang(el) {
    if (el.getAttribute('data-fmt') !== 'uang') return;
    var raw = (el.value || '').replace(/[^\d]/g, '');
    el.value = raw ? Number(raw).toLocaleString('id-ID') : '';
  }
  function numVal(v) { return (String(v || '').replace(/[^\d]/g, '')) || ''; }

  function collect() {
    var out = {};
    form.querySelectorAll('.dok-f').forEach(function (el) {
      var k = el.getAttribute('data-key');
      if (el.type === 'checkbox') { out[k] = el.checked ? 1 : 0; return; }
      var v = el.value;
      if (el.getAttribute('data-fmt') === 'uang') v = numVal(v);
      out[k] = v;
    });
    form.querySelectorAll('.dok-rows').forEach(function (box) {
      var key = box.getAttribute('data-rows'), arr = [];
      box.querySelectorAll('.row-item').forEach(function (tr) {
        var o = {}, any = false;
        tr.querySelectorAll('[data-col]').forEach(function (inp) {
          var v = inp.value;
          if (inp.getAttribute('data-fmt') === 'uang') v = numVal(v);
          o[inp.getAttribute('data-col')] = v;
          if (v !== '' && v != null) any = true;
        });
        if (any) arr.push(o);
      });
      out[key] = arr;
    });
    // Kumpulkan isian checklist jika ada
    var clBox = form.querySelector('.dok-cl-box');
    if (clBox) {
      var items = { kelengkapan: {}, verifikasi: {} };
      clBox.querySelectorAll('.dok-cl-item').forEach(function (row) {
        var sec = row.getAttribute('data-sec');
        var idx = row.getAttribute('data-idx');
        var rad = row.querySelector('input[type="radio"]:checked');
        var st  = rad ? rad.value : '';
        var ketInp = row.querySelector('.cl-ket');
        var ket = ketInp ? ketInp.value : '';
        if (!items[sec]) items[sec] = {};
        items[sec][idx] = { status: st, ket: ket };
      });
      out['items'] = items;
    }
    // Kumpulkan isian checklist alur 7 langkah jika ada
    var alurBox = form.querySelector('.dok-alur-box');
    if (alurBox) {
      var alurArr = [];
      alurBox.querySelectorAll('.dok-alur-cb').forEach(function (cb) {
        alurArr.push(cb.checked ? 1 : 0);
      });
      out['alur_check'] = alurArr;
    }
    return out;
  }

  var t = null;
  function refresh() {
    clearTimeout(t);
    t = setTimeout(doRefresh, 350);
  }
  function doRefresh() {
    pvStatus.textContent = 'memuat…';
    var fd = new FormData();
    fd.append('kode', KODE); fd.append('KegiatanID', KEG);
    fd.append('rangkap', rangkap); fd.append('payload', JSON.stringify(collect()));
    if (window.CSRF) fd.append(window.CSRF.name, window.CSRF.hash);
    fetch(base + 'dokumen/pratinjau', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) { pvSheet.innerHTML = html; fitPreview(); pvStatus.textContent = 'diperbarui ' + new Date().toLocaleTimeString('id-ID'); })
      .catch(function () { pvStatus.textContent = 'gagal memuat pratinjau'; });
  }

  // Perkecil lembar A4 (210mm) supaya utuh di panel pratinjau yang lebih sempit.
  function fitPreview() {
    var sheet = pvSheet.querySelector('.doc-sheet');
    if (!sheet) return;
    sheet.style.transform = '';
    var avail = pvSheet.clientWidth || pvSheet.parentElement.clientWidth;
    var mm = document.createElement('div'); mm.style.width = '210mm'; mm.style.position = 'absolute'; mm.style.visibility = 'hidden';
    document.body.appendChild(mm); var a4px = mm.getBoundingClientRect().width; document.body.removeChild(mm);
    var scale = Math.min(1, (avail - 4) / a4px);
    if (scale < 1) {
      sheet.style.transform = 'scale(' + scale.toFixed(4) + ')';
      pvSheet.style.height = (sheet.getBoundingClientRect().height) + 'px';
    } else { pvSheet.style.height = ''; }
  }
  window.addEventListener('resize', fitPreview);

  form.addEventListener('input', function (e) {
    if (e.target.getAttribute && e.target.getAttribute('data-fmt') === 'uang') fmtUang(e.target);
    refresh();
  });
  // Opsional/Bisa batalkan pilihan: Klik tombol radio yang sudah aktif untuk membatalkan (uncheck keduanya)
  var clWasChecked = false;
  function checkPreState(e) {
    var lbl = e.target.closest('.dok-cl-item label');
    if (lbl) {
      var rad = lbl.querySelector('input[type="radio"]');
      clWasChecked = (rad && rad.checked) || lbl.classList.contains('active');
    }
  }
  form.addEventListener('pointerdown', checkPreState);
  form.addEventListener('mousedown', checkPreState);
  form.addEventListener('keydown', function (e) {
    if (e.key === ' ' || e.key === 'Enter') checkPreState(e);
  });
  form.addEventListener('click', function (e) {
    var lbl = e.target.closest('.dok-cl-item label');
    if (!lbl) return;
    var rad = lbl.querySelector('input[type="radio"]');
    if (!rad) return;

    if (clWasChecked) {
      // Jika tombol ini sebelumnya sudah terpilih, klik lagi untuk membatalkan pilihan (keduanya kosong)
      e.preventDefault();
      e.stopPropagation();
      rad.checked = false;
      lbl.classList.remove('active', 'focus');
      var grp = lbl.closest('.btn-group');
      if (grp) {
        grp.querySelectorAll('label').forEach(function (l) { l.classList.remove('active', 'focus'); });
        grp.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = false; });
      }
      clWasChecked = false;
      refresh();
    }
  }, true);

  form.addEventListener('change', function (e) {
    if (e.target && e.target.type === 'radio') {
      var lbl = e.target.closest('label');
      if (lbl) {
        var grp = lbl.closest('.btn-group');
        if (grp) grp.querySelectorAll('label').forEach(function (l) { l.classList.remove('active'); });
        lbl.classList.add('active');
      }
    }
    refresh();
  });

  // Tombol aksi cepat checklist (1-click centang semua ADA & SESUAI)
  var btnClAll = document.getElementById('btn-cl-all-ada');
  if (btnClAll) {
    btnClAll.addEventListener('click', function () {
      form.querySelectorAll('.dok-cl-item').forEach(function (row) {
        var radYa = row.querySelector('input[type="radio"][value="ya"]');
        if (radYa) {
          radYa.checked = true;
          var lbl = radYa.closest('label');
          if (lbl) {
            var grp = lbl.closest('.btn-group');
            if (grp) grp.querySelectorAll('label').forEach(function (l) { l.classList.remove('active'); });
            lbl.classList.add('active');
          }
        }
      });
      refresh();
    });
  }
  var btnClReset = document.getElementById('btn-cl-reset');
  if (btnClReset) {
    btnClReset.addEventListener('click', function () {
      form.querySelectorAll('.dok-cl-item').forEach(function (row) {
        row.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = false; });
        row.querySelectorAll('label').forEach(function (l) { l.classList.remove('active'); });
        var ket = row.querySelector('.cl-ket');
        if (ket) ket.value = '';
      });
      refresh();
    });
  }

  // Tombol aksi cepat alur 7 langkah
  var btnAlurAll = document.getElementById('btn-alur-all');
  if (btnAlurAll) {
    btnAlurAll.addEventListener('click', function () {
      form.querySelectorAll('.dok-alur-cb').forEach(function (cb) { cb.checked = true; });
      refresh();
    });
  }
  var btnAlurReset = document.getElementById('btn-alur-reset');
  if (btnAlurReset) {
    btnAlurReset.addEventListener('click', function () {
      form.querySelectorAll('.dok-alur-cb').forEach(function (cb) { cb.checked = false; });
      refresh();
    });
  }
  form.querySelectorAll('.dok-alur-cb').forEach(function (cb) {
    cb.addEventListener('change', refresh);
  });

  // Baris repeatable
  form.querySelectorAll('.dok-rows').forEach(function (box) {
    box.querySelector('.row-add').addEventListener('click', function () {
      var tb = box.querySelector('tbody');
      var tpl = tb.querySelector('.row-item');
      var nw = tpl.cloneNode(true);
      nw.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      tb.appendChild(nw);
      refresh();
    });
    box.addEventListener('click', function (e) {
      if (e.target.classList.contains('row-del')) {
        var rows = box.querySelectorAll('.row-item');
        if (rows.length > 1) e.target.closest('.row-item').remove();
        else e.target.closest('.row-item').querySelectorAll('input').forEach(function (i) { i.value = ''; });
        refresh();
      }
    });
  });

  if (selR) selR.addEventListener('change', function () {
    var url = new URL(window.location.href);
    url.searchParams.set('r', this.value);
    window.location.href = url.toString();
  });

  // Cetak link
  function cetakUrl() {
    return base + 'dokumen/cetak/' + KODE + '/' + KEG + (rangkap && rangkap !== '-' ? ('?r=' + encodeURIComponent(rangkap)) : '');
  }
  document.getElementById('btn-cetak').setAttribute('href', cetakUrl());

  // Simpan
  document.getElementById('btn-simpan').addEventListener('click', function () {
    var btn = this; btn.disabled = true;
    var fd = new FormData();
    fd.append('kode', KODE); fd.append('KegiatanID', KEG);
    fd.append('rangkap', rangkap); fd.append('payload', JSON.stringify(collect()));
    if (window.CSRF) fd.append(window.CSRF.name, window.CSRF.hash);
    fetch(base + 'dokumen/simpan', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        if (window.show_toast) show_toast(res.ok ? 'success' : 'error', res.msg);
        else alert(res.msg);
      })
      .catch(function () { btn.disabled = false; if (window.show_toast) show_toast('error', 'Gagal menyimpan.'); });
  });

  // format uang awal + render pertama
  form.querySelectorAll('[data-fmt="uang"]').forEach(fmtUang);
  doRefresh();
})();
</script>
