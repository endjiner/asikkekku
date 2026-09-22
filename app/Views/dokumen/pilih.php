<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
        <?php echo illus('activity', 40); ?> <span class="ml-2">Dokumen Pencairan (Isian &amp; Pratinjau)</span>
      </h1>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="alert-soft-info mb-3">
        Modul uji: isi <strong>field</strong> dokumen &mdash; sistem menyimpan nilainya saja (bukan file),
        formulir dirender ulang saat dibuka/dicetak. Pilih kegiatan, lalu pilih dokumen yang mau diisi.
      </div>

      <div class="card list-card">
        <div class="card-header list-toolbar">
          <div class="lt-head">
            <span class="lt-ico"><?php echo illus('activity', 22); ?></span>
            <span class="lt-txt">
              <b>Pilih Kegiatan</b>
              <small>Cari kegiatan lalu tekan Pilih untuk mengisi dokumen pencairannya.</small>
            </span>
          </div>
          <div class="lt-search">
            <input type="search" id="cari-keg" class="form-control" placeholder="Cari judul / no. surat / pelaksana&hellip;">
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive" style="max-height:52vh;overflow:auto">
            <table class="table table-sm table-hover text-sm list-table" id="tbl-keg">
              <thead>
                <tr>
                  <th style="width:90px">ID</th>
                  <th>No. Surat</th>
                  <th>Nama Kegiatan</th>
                  <th>Pelaksana</th>
                  <th style="width:120px">Tanggal</th>
                  <th style="width:100px"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($kegiatan_list as $k): ?>
                <tr data-cari="<?php echo dok_e(strtolower($k['KegiatanNoSuratTugas'].' '.$k['KegiatanJudul'].' '.$k['KegiatanNamaPelaksana'])) ?>">
                  <td class="text-muted"><?php echo (int) $k['KegiatanID'] ?></td>
                  <td class="text-nowrap"><?php echo dok_e($k['KegiatanNoSuratTugas']) ?></td>
                  <td><?php echo dok_e($k['KegiatanJudul']) ?></td>
                  <td class="text-muted"><?php echo dok_e($k['KegiatanNamaPelaksana']) ?></td>
                  <td class="text-nowrap"><?php echo dok_e($k['KegiatanTanggal']) ?></td>
                  <td><button type="button" class="btn btn-sm btn-primary pilih-keg" data-id="<?php echo (int) $k['KegiatanID'] ?>">Pilih</button></td>
                </tr>
                <?php endforeach ?>
                <?php if (empty($kegiatan_list)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data kegiatan (atau DB tidak aktif).</td></tr>
                <?php endif ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card" id="card-dok" style="display:none">
        <div class="card-header"><h3 class="card-title font-weight-bold">Dokumen untuk Kegiatan <span id="lbl-keg"></span></h3></div>
        <div class="card-body">
          <div class="row">
            <?php foreach ($templates as $kode => $t): ?>
            <div class="col-md-6 col-lg-4 mb-3">
              <div class="border rounded p-3 h-100 d-flex flex-column">
                <div class="font-weight-bold"><?php echo dok_e($t['nama']) ?></div>
                <div class="text-muted text-sm mb-2">
                  <?php echo $t['rangkap'] === 'per_penerima' ? 'Per penerima (N rangkap)' : 'Satu per kegiatan' ?>
                  <?php if (!empty($t['catatan'])): ?><br><em class="text-warning"><?php echo dok_e($t['catatan']) ?></em><?php endif ?>
                </div>
                <a href="#" class="btn btn-sm btn-outline-navy mt-auto buka-dok" data-kode="<?php echo dok_e($kode) ?>">
                  <?php echo svgico('edit', 15) ?> Isi field
                </a>
              </div>
            </div>
            <?php endforeach ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
(function () {
  var base = '<?php echo base_url() ?>';
  var kegId = null;

  document.getElementById('cari-keg').addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('#tbl-keg tbody tr').forEach(function (tr) {
      var c = tr.getAttribute('data-cari') || '';
      tr.style.display = (!q || c.indexOf(q) !== -1) ? '' : 'none';
    });
  });

  document.querySelectorAll('.pilih-keg').forEach(function (b) {
    b.addEventListener('click', function () {
      kegId = this.getAttribute('data-id');
      document.getElementById('lbl-keg').textContent = '#' + kegId;
      document.getElementById('card-dok').style.display = '';
      document.getElementById('card-dok').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  document.querySelectorAll('.buka-dok').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      if (!kegId) return;
      window.location.href = base + 'dokumen/form/' + this.getAttribute('data-kode') + '/' + kegId;
    });
  });
})();
</script>
