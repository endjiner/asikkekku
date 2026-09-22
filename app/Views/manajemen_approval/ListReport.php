<?php $judul_laporan = 'Laporan Pengajuan Kartu Kendali Kuitansi'; ?>
<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
            <?php echo illus('report', 40); ?> <span class="ml-2"><?= $judul_laporan ?></span>
          </h1>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <div class="report-controls">
                <div class="report-range">
                  <span class="report-range-ico"><?php echo svgico('calendar',14) ?></span>
                  <label for="report-from">Dari</label>
                  <input type="month" id="report-from" value="<?php echo date('Y-m') ?>" max="<?php echo date('Y-m') ?>">
                  <label for="report-to">s/d</label>
                  <input type="month" id="report-to" value="<?php echo date('Y-m') ?>" max="<?php echo date('Y-m') ?>">
                </div>
                <div class="report-status-filter" aria-label="Saring status">
                  <label class="rsf-item"><input type="checkbox" class="report-status" value="proses" checked> Dalam Proses</label>
                  <label class="rsf-item"><input type="checkbox" class="report-status" value="selesai" checked> Selesai</label>
                  <label class="rsf-item"><input type="checkbox" class="report-status" value="kembali" checked> Dikembalikan</label>
                </div>
              </div>
            </div>
            <div class="card-body">
              <table id="table-data" class="table report-table text-sm table-bordered table-hover no-row-link wp-nw" width="100%">
                <thead>
                  <tr>
                    <th class="ta-ci">No.</th>
                    <th class="ta-ci">No. Surat</th>
                    <th class="ta-ci">Nama Kegiatan</th>
                    <th class="ta-ci">Pelaksana / Penyedia</th>
                    <th class="ta-ci">Tgl Pengajuan</th>
                    <th class="ta-ci">Status</th>
                    <th class="ta-ci">Pembaruan Terakhir</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
