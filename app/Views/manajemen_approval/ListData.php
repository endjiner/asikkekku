<style>
  #modal-l-Kegiatan .pel-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:6px 10px; }
  #modal-l-Kegiatan .pel-grid label{ display:flex; flex-direction:column; font-size:.72rem; font-weight:600; margin:0; color:#555; }
  #modal-l-Kegiatan .pel-grid label input,
  #modal-l-Kegiatan .pel-grid label select{ font-weight:400; }
  #modal-l-Kegiatan .pel-row{ background:#fafbfc; }
  .pel-suggest-box, .output-suggest-box {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1055;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 10px 20px -3px rgba(0,0,0,0.15), 0 4px 6px -2px rgba(0,0,0,0.05);
    max-height: 230px;
    overflow-y: auto;
    margin-top: 2px;
  }
  .pel-suggest-item, .output-suggest-item {
    padding: 7px 10px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    transition: background 0.15s ease;
  }
  .pel-suggest-item:last-child, .output-suggest-item:last-child { border-bottom: none; }
  .pel-suggest-item:hover, .output-suggest-item:hover { background: #eff6ff; }
  .pel-suggest-nama, .output-suggest-code { font-size: 0.83rem; font-weight: 600; color: #1e293b; }
  .pel-suggest-nip, .output-suggest-sub { font-size: 0.72rem; color: #64748b; }
  .pel-suggest-badge { font-size: 0.68rem; padding: 2px 6px; border-radius: 4px; background: #e2e8f0; color: #475569; white-space: nowrap; font-weight: 600; }
  .badge-ppk-1 { background: #dbeafe; color: #1e40af; }
  .badge-ppk-2 { background: #fef3c7; color: #92400e; }
  .badge-verif { background: #f1f5f9; color: #475569; }
</style>
<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
            <?php echo illus('activity', 40); ?> <span class="ml-2"><?= $menu_detail['MenuName'] ?></span>
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
                <span class="lt-ico"><?php echo illus('activity', 22); ?></span>
                <span class="lt-txt">
                  <b>Semua Pengajuan</b>
                  <small>Klik baris untuk membuka detail &amp; jejak status pencairan.</small>
                </span>
              </div>
              <?php if (role_can('kegiatan_create')): ?>
                <button type="button" class="btn btn-primary KegiatanAdd" data-toggle="modal" data-target="#modal-l-Kegiatan">
                  <?php echo svgico('add', 18) ?> Tambah Data
                </button>
              <?php endif ?>
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

<div class="modal fade" id="modal-l-Kegiatan" aria-modal="true" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form role="form" class="KegiatanModify" enctype="multipart/form-data" action="<?= base_url() ?>manajemen_approval/KegiatanModify" method="post" autocomplete="off">
      <div class="modal-content text-sm" style="border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px -10px rgba(15,23,42,0.25);">
        <div class="modal-header bg-teal text-white py-2 px-3 d-flex align-items-center justify-content-between">
          <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2 mb-0" style="font-size: 1rem;">
            <?php echo svgico('file', 17) ?> Formulir Pengajuan Kegiatan
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>

        <!-- TAB NAVIGATION -->
        <div class="bg-light border-bottom px-3 pt-2">
          <ul class="nav nav-pills nav-fill text-sm" id="pills-tab-kegiatan" role="tablist">
            <li class="nav-item">
              <a class="nav-link active font-weight-bold py-1 px-3" id="tab-data-pokok-btn" data-toggle="pill" href="#tab-data-pokok" role="tab">
                <?php echo svgico('activity', 14) ?> 1. Data Pokok &amp; Pelaksana
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link font-weight-bold py-1 px-3" id="tab-upload-btn" data-toggle="pill" href="#tab-upload" role="tab">
                <?php echo svgico('upload', 14) ?> 2. Berkas Eksternal (Luar Sistem)
              </a>
            </li>
          </ul>
        </div>

        <div class="modal-body p-3" style="max-height: calc(80vh - 120px); overflow-y: auto;">
          <input type="hidden" id="kegiatan-id" name="KegiatanID">

          <div class="tab-content" id="pills-tabContent-kegiatan">
            <!-- TAB 1: DATA POKOK & PELAKSANA -->
            <div class="tab-pane fade show active" id="tab-data-pokok" role="tabpanel">
              
              <!-- Panduan Ringkas Pemetaan Dokumen -->
              <div class="alert alert-light border p-2 mb-3 text-xs" style="background:#f8fafc; border-color:#e2e8f0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <strong class="text-dark"><i class="fas fa-magic text-primary mr-1"></i> Satu Kali Isi &rarr; Seluruh Dokumen Terisi Otomatis</strong>
                  <span class="badge badge-info">Otomatisasi ASIKKEKKU</span>
                </div>
                <div class="text-muted">
                  Data yang Anda isi di bawah akan langsung dicetak pada:
                  <span class="badge badge-light border">Lembar Surat Tugas</span>,
                  <span class="badge badge-light border">SPD</span>,
                  <span class="badge badge-light border">Kwitansi</span>,
                  <span class="badge badge-light border">Daftar Riil</span>,
                  <span class="badge badge-light border">Nominatif</span>, &amp;
                  <span class="badge badge-light border">Kartu Kendali</span>.
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label for="kegiatan-jenis" class="font-weight-bold mb-1">Jenis Pengajuan <span class="text-danger">*</span></label>
                    <select id="kegiatan-jenis" name="KegiatanJenisID" class="form-control form-control-sm KegiatanJenis" required="">
                      <?php foreach ($JenisList as $j): ?>
                        <option value="<?php echo (int) $j['JenisID'] ?>"><?php echo html_escape($j['JenisNama']) ?></option>
                      <?php endforeach ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label for="kegiatan-tanggal" class="font-weight-bold mb-1">Tanggal Pengajuan <span class="text-danger">*</span></label>
                    <input type="text" id="kegiatan-tanggal" name="KegiatanTanggal" class="form-control form-control-sm" required="">
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label for="kegiatan-no-surat" class="font-weight-bold mb-0">No. Surat Tugas <span class="text-danger">*</span></label>
                      <span class="text-xs text-muted">Klik template:</span>
                    </div>
                    <!-- Quick ST Template Badges -->
                    <div class="mb-1 d-flex flex-wrap gap-1">
                      <button type="button" class="btn btn-xs btn-outline-secondary btn-st-tpl" data-prefix="PW.01.10.8B.05.<?= date('y') ?>." title="Pengawasan">PW (Pengawasan)</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary btn-st-tpl" data-prefix="PL.03.01.20B.07.<?= date('y') ?>." title="Pelayanan Publik">PL (Pelayanan)</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary btn-st-tpl" data-prefix="KP.04.02.8B.08.<?= date('y') ?>." title="Kepegawaian">KP (Kepegawaian)</button>
                      <button type="button" class="btn btn-xs btn-outline-secondary btn-st-tpl" data-prefix="HK.02.02.8B.<?= date('y') ?>." title="Hukum">HK (Hukum)</button>
                    </div>
                    <input type="text" id="kegiatan-no-surat" name="KegiatanNoSuratTugas" class="form-control form-control-sm" placeholder="Contoh: PW.01.10.8B.05.26.xxx" required="">
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group mb-2 position-relative output-wrap">
                    <label for="kegiatan-kode-output" class="font-weight-bold mb-1">
                      Kode Output / MAK Anggaran
                      <i class="fas fa-question-circle text-muted" data-toggle="tooltip" title="Menentukan PPK penandatangan berkas & nomor MAK di Kwitansi/SPTJB"></i>
                    </label>
                    <input type="text" id="kegiatan-kode-output" name="KegiatanKodeOutput" class="form-control form-control-sm" placeholder="Klik untuk pilih rekomendasi atau ketik..." autocomplete="off">
                    <div class="output-suggest-box d-none"></div>
                    <small class="text-muted d-block text-xs mt-1">💡 Klik kolom untuk memilih daftar resmi DIPA BBPOM atau <b>ketik bebas</b>.</small>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-group mb-2">
                    <label for="kegiatan-judul" class="font-weight-bold mb-1">Nama / Uraian Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" id="kegiatan-judul" name="KegiatanJudul" class="form-control form-control-sm" placeholder="Contoh: Pemeriksaan Sarana Distribusi Produk Farmasi di Kab. Bangka Tengah..." required="">
                  </div>
                </div>

                <!-- Pemisahan Asal & Tujuan (Multi-Tujuan) -->
                <div class="col-md-5">
                  <div class="form-group mb-2">
                    <label for="kegiatan-asal" class="font-weight-bold mb-1">Tempat Berangkat (Asal)</label>
                    <input type="text" id="kegiatan-asal" class="form-control form-control-sm" value="Pangkal Pinang" placeholder="Kota Asal...">
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="form-group mb-2">
                    <label for="kegiatan-tujuan" class="font-weight-bold mb-1">
                      Tempat Tujuan <small class="text-muted">(Bisa &gt;1 kota/lokasi)</small>
                    </label>
                    <input type="text" id="kegiatan-tujuan" class="form-control form-control-sm" placeholder="Contoh: Kab. Bangka Tengah, Kab. Belitung">
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group mb-2">
                    <label for="kegiatan-jml-hari" class="font-weight-bold mb-1">Lama (Hari)</label>
                    <input type="number" min="0" id="kegiatan-jml-hari" name="KegiatanJmlHari" class="form-control form-control-sm" placeholder="Hari">
                  </div>
                </div>

                <!-- Hidden full string for backend / SPD backward compatibility -->
                <input type="hidden" id="kegiatan-asal-tujuan" name="KegiatanAsalTujuan">

                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label for="kegiatan-pemohon-tipe" class="font-weight-bold mb-1">Tipe Pemohon</label>
                    <select id="kegiatan-pemohon-tipe" name="KegiatanPemohonTipe" class="form-control form-control-sm">
                      <option value="internal">Internal (Pegawai BBPOM)</option>
                      <option value="eksternal">Eksternal (Penyedia / Pihak Luar)</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label for="kegiatan-pemohon-phone" class="font-weight-bold mb-1">No. WhatsApp Pemohon / PIC</label>
                    <input type="text" id="kegiatan-pemohon-phone" name="KegiatanPemohonPhone" class="form-control form-control-sm" placeholder="Contoh: 62812xxxxxxx" inputmode="numeric" data-user-phone="<?= htmlspecialchars($UserPhone ?? '', ENT_QUOTES) ?>">
                    <small class="text-muted text-xs">Untuk pengiriman notifikasi otomatis progres pencairan.</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label for="kegiatan-dest-user" class="font-weight-bold mb-1">Petugas Tujuan (Verifikator/Staf PPK)</label>
                    <select id="kegiatan-dest-user" class="form-control form-control-sm KegiatanDestUser" name="KegiatanDestUser">
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-2">
                    <label for="kegiatan-keterangan" class="font-weight-bold mb-1">Keterangan Tambahan</label>
                    <input type="text" id="kegiatan-keterangan" class="form-control form-control-sm KegiatanNote" name="KegiatanKeterangan" placeholder="Catatan tambahan bila ada...">
                  </div>
                </div>
              </div>

              <!-- Pelaksana Repeater -->
              <div class="card border bg-light mt-2 mb-0">
                <div class="card-header py-1 px-2 d-flex align-items-center justify-content-between">
                  <span class="font-weight-bold text-dark text-xs"><?php echo svgico('user', 13) ?> Daftar Pelaksana Kegiatan</span>
                  <button type="button" class="btn btn-primary btn-xs" id="pel-add">
                    <?php echo svgico('add', 12) ?> Tambah Pelaksana
                  </button>
                </div>
                <div class="card-body p-2">
                  <div id="pel-list" class="pel-list"></div>
                </div>
              </div>

              <datalist id="list-pegawai-rekomendasi">
                <?php foreach (($PegawaiOpts ?? array()) as $p): ?>
                  <option value="<?php echo html_escape($p['Nama']) ?>"><?php echo html_escape('NIP: ' . ($p['NIP'] ?: '-') . ' | ' . ($p['Jabatan'] ?: '')) ?></option>
                <?php endforeach ?>
              </datalist>

              <script>
                window.PEGAWAI = <?php echo json_encode(array_map(function ($p) {
                  return array(
                    'id' => (int) $p['UserID'], 'nama' => $p['Nama'], 'nip' => $p['NIP'],
                    'gol' => $p['Gol'], 'jabatan' => $p['Jabatan'],
                    'rekening' => $p['Rekening'], 'bank' => $p['Bank'], 'npwp' => $p['NPWP'],
                    'phone' => $p['Phone'] ?? '',
                  );
                }, ($PegawaiOpts ?? array())), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS); ?>;

                window.OFFICIAL_OUTPUTS = [
                  { code: '3165.AEA', ppk: 'PPK I', ppkName: 'Netty', verif: 'Hana', tone: 'ppk-1' },
                  { code: '3165.BAH', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.BDB', ppk: 'PPK I', ppkName: 'Netty', verif: 'Hana', tone: 'ppk-1' },
                  { code: '3165.BDC', ppk: 'PPK I', ppkName: 'Netty', verif: 'Hana', tone: 'ppk-1' },
                  { code: '3165.BIA', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.BKB', ppk: 'PPK II', ppkName: 'Priya', verif: 'Hana', tone: 'ppk-2' },
                  { code: '3165.BMB', ppk: 'PPK I', ppkName: 'Netty', verif: 'Hana', tone: 'ppk-1' },
                  { code: '3165.PDD', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.QCD', ppk: 'PPK I', ppkName: 'Netty', verif: 'Hana', tone: 'ppk-1' },
                  { code: '3165.QDC', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.QDG', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.QIA', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.QIC', ppk: 'PPK I', ppkName: 'Netty', verif: 'Ratna', tone: 'ppk-1' },
                  { code: '3165.RAB', ppk: 'PPK II', ppkName: 'Priya', verif: 'Hana', tone: 'ppk-2' },
                  { code: '6384.EBA.994.001', ppk: 'PPK I', ppkName: 'Netty', verif: 'Hana', tone: 'ppk-1' },
                  { code: '6384.EBA.994.002', ppk: 'PPK II', ppkName: 'Priya', verif: 'Hana', tone: 'ppk-2' },
                  { code: '6384.EBA.956', ppk: 'PPK II', ppkName: 'Priya', verif: 'Hana', tone: 'ppk-2' }
                ];
              </script>
              <template id="pel-tpl">
                <div class="pel-row border rounded p-2 mb-2 bg-white shadow-xs">
                  <input type="hidden" class="pel-user" name="pel[__I__][userid]">
                  <div class="pel-grid">
                    <label style="grid-column: span 2; position: relative;" class="pel-nama-wrap">
                      Nama Pelaksana / Penyedia <span class="text-danger">*</span>
                      <input class="form-control form-control-sm pel-nama" name="pel[__I__][nama]" placeholder="Ketik awalan nama pegawai atau nama pihak luar..." required="" autocomplete="off">
                      <div class="pel-suggest-box d-none"></div>
                    </label>
                    <label>NIP <small class="text-muted">(opsional)</small>
                      <input class="form-control form-control-sm pel-nip" name="pel[__I__][nip]" placeholder="-">
                    </label>
                    <label>Golongan
                      <input class="form-control form-control-sm pel-gol" name="pel[__I__][gol]" placeholder="-">
                    </label>
                    <label>Jabatan
                      <input class="form-control form-control-sm pel-jabatan" name="pel[__I__][jabatan]" placeholder="-">
                    </label>
                    <label>No. Rekening
                      <input class="form-control form-control-sm pel-rekening" name="pel[__I__][rekening]" placeholder="Nomor rekening">
                    </label>
                    <label>Bank
                      <input class="form-control form-control-sm pel-bank" name="pel[__I__][bank]" placeholder="Nama bank">
                    </label>
                    <label>NPWP
                      <input class="form-control form-control-sm pel-npwp" name="pel[__I__][npwp]" placeholder="NPWP">
                    </label>
                  </div>
                  <div class="d-flex align-items-center justify-content-between mt-1">
                    <small class="text-muted pel-status-hint">💡 Ketik nama pegawai untuk auto-fill, atau ketik nama penyedia/pihak luar.</small>
                    <button type="button" class="btn btn-link btn-xs text-danger p-0 pel-del font-weight-bold">Hapus baris</button>
                  </div>
                </div>
              </template>
            </div>

            <!-- TAB 2: UPLOAD BERKAS EKSTERNAL -->
            <div class="tab-pane fade" id="tab-upload" role="tabpanel">
              <div class="p-3 rounded border mb-3" style="background:#f0f9ff; border-color:#bae6fd !important;">
                <h6 class="font-weight-bold text-primary mb-1 d-flex align-items-center gap-2">
                  <?php echo svgico('upload', 16) ?> Kontainer Berkas Eksternal (Luar Sistem ASIKKEKKU)
                </h6>
                <p class="text-muted text-xs mb-0">
                  Tempat upload berkas resmi pendukung yang dibuat di luar sistem seperti <b>Surat Tugas (ST)</b>, <b>SPPD</b>, <b>LPJ Kegiatan</b>, <b>Kuitansi Pengeluaran Riil</b>, dll.
                </p>
              </div>

              <div class="form-group mb-3">
                <label for="KegiatanLampiran" class="font-weight-bold mb-1">Pilih Berkas Lampiran (Format PDF)</label>
                <div class="custom-file">
                  <input type="file" accept="application/pdf" class="custom-file-input" id="KegiatanLampiran" name="KegiatanLampiran">
                  <label class="custom-file-label" for="KegiatanLampiran">Pilih berkas PDF dari komputer...</label>
                </div>
                <div class="input-group input-group-sm mt-2 div_remove_KegiatanLampiranPrev">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-success text-white"><?php echo svgico('check', 12) ?> Berkas Tersimpan</span>
                  </div>
                  <input type="text" class="form-control" id="KegiatanLampiranPrev" name="KegiatanLampiranPrev" readonly="">
                  <span class="input-group-append">
                    <button type="button" class="btn btn-danger btn-flat btn_remove_KegiatanLampiranPrev" title="Hapus lampiran" aria-label="Hapus lampiran"><?php echo svgico('trash',14) ?> Hapus</button>
                  </span>
                </div>
              </div>

              <div class="p-3 rounded" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:0.8rem; color:#64748b;">
                <b class="text-dark d-block mb-1">💡 Informasi Dokumen Digital Sistem ASIKKEKKU:</b>
                Formulir digital (<b>Kartu Kendali</b>, <b>Lembar Periksa</b>, <b>SPTJB</b>, <b>Kuitansi Internal</b>) otomatis disiapkan oleh sistem dan dapat langsung dilengkapi/dicetak di panel <b>Dokumen</b> setelah kegiatan ini disimpan.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer justify-content-between py-2 px-3 bg-light border-top">
          <button type="button" class="btn btn-default btn-sm modal-close" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">
            <?php echo svgico('check', 14) ?> Simpan Pengajuan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
