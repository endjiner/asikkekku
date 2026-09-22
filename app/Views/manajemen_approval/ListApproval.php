<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
            <?php echo illus('approval-inbox', 40); ?> <span class="ml-2"><?= $menu_detail['MenuName'] ?></span>
          </h1>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card list-card">
            <div class="card-header list-toolbar">
              <div class="lt-head">
                <span class="lt-ico"><?php echo illus('approval-inbox', 22); ?></span>
                <span class="lt-txt">
                  <b>Menunggu Persetujuan Anda</b>
                  <small>Klik baris untuk meninjau berkas lalu memberi keputusan.</small>
                </span>
              </div>
            </div>
            <div class="card-body">
              <table id="table-data" class="table table-sm text-sm table-bordered table-hover data-row-link wp-nw" width="100%">
                <thead>
                  <tr>
                    <th class="ta-ci">No</th>
                    <th class="ta-ci">No. Surat</th>
                    <th class="ta-ci">Nama Kegiatan</th>
                    <th class="ta-ci">Pelaksana / Penyedia</th>
                    <th class="ta-ci">Tanggal</th>
                    <th class="ta-ci">Status</th>
                    <th class="ta-ci">Aksi</th>
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
