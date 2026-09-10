<style>
  #modal-l-Kegiatan .pel-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:6px 10px; }
  #modal-l-Kegiatan .pel-grid label{ display:flex; flex-direction:column; font-size:.72rem; font-weight:600; margin:0; color:#555; }
  #modal-l-Kegiatan .pel-grid label input,
  #modal-l-Kegiatan .pel-grid label select{ font-weight:400; }
  #modal-l-Kegiatan .pel-row{ background:#fafbfc; }
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

<div class="modal show" id="modal-l-Kegiatan" aria-modal="true" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-l">
    <form role="form" class="KegiatanModify" enctype="multipart/form-data" action="<?= base_url() ?>manajemen_approval/KegiatanModify" method="post" autocomplete="off">
      <div class="modal-content text-sm">
        <div class="modal-header bg-teal text-white ta-c">
          <h5 class="modal-title">Formulir Tambah / Ubah Kegiatan</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body row">
          <div class="col-12">
            <div class="form-group">
              <label for="kegiatan-id" class="form-left w-150px">ID</label>
              <span class="form-right"><input type="text" id="kegiatan-id" name="KegiatanID" class="form-control form-control-sm" readonly=""></span>
            </div>
            <div class="form-group">
              <label for="kegiatan-no-surat" class="form-left w-150px">No. Surat</label>
              <span class="form-right"><input type="text" id="kegiatan-no-surat" name="KegiatanNoSuratTugas" class="form-control form-control-sm" required=""></span>
            </div>
            <div class="form-group">
              <label for="kegiatan-judul" class="form-left w-150px">Nama Kegiatan</label>
              <span class="form-right"><input type="text" id="kegiatan-judul" name="KegiatanJudul" class="form-control form-control-sm" required=""></span>
            </div>
            <div class="form-group">
              <label for="kegiatan-tanggal" class="form-left w-150px">Tanggal Pengajuan</label>
              <span class="form-right"><input type="text" id="kegiatan-tanggal" name="KegiatanTanggal" class="form-control form-control-sm" required=""></span>
            </div>
            <div class="form-group">
              <label class="form-left w-150px">Pelaksana</label>
              <span class="form-right">
                <div id="pel-list" class="pel-list"></div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="pel-add">+ Tambah Pelaksana</button>
                <small class="d-block text-muted mt-1">Pilih nama pegawai &rarr; NIP/gol/jabatan/rekening terisi otomatis, tetap bisa diubah (mis. no. rekening beda tiap kegiatan).</small>
              </span>
            </div>
            <script>
              window.PEGAWAI = <?php echo json_encode(array_map(function ($p) {
                return array(
                  'id' => (int) $p['UserID'], 'nama' => $p['Nama'], 'nip' => $p['NIP'],
                  'gol' => $p['Gol'], 'jabatan' => $p['Jabatan'],
                  'rekening' => $p['Rekening'], 'bank' => $p['Bank'], 'npwp' => $p['NPWP'],
                );
              }, ($PegawaiOpts ?? array())), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS); ?>;
            </script>
            <template id="pel-tpl">
              <div class="pel-row border rounded p-2 mb-2">
                <div class="pel-grid">
                  <label>Pegawai
                    <select class="form-control form-control-sm pel-user" name="pel[__I__][userid]">
                      <option value="">— pilih / manual —</option>
                      <?php foreach (($PegawaiOpts ?? array()) as $p): ?>
                        <option value="<?php echo (int) $p['UserID'] ?>"><?php echo html_escape($p['Nama'] . ' — ' . $p['NIP']) ?></option>
                      <?php endforeach ?>
                    </select>
                  </label>
                  <label>Nama <input class="form-control form-control-sm pel-nama" name="pel[__I__][nama]" required=""></label>
                  <label>NIP <input class="form-control form-control-sm pel-nip" name="pel[__I__][nip]"></label>
                  <label>Golongan <input class="form-control form-control-sm pel-gol" name="pel[__I__][gol]"></label>
                  <label>Jabatan <input class="form-control form-control-sm pel-jabatan" name="pel[__I__][jabatan]"></label>
                  <label>No. Rekening <input class="form-control form-control-sm pel-rekening" name="pel[__I__][rekening]"></label>
                  <label>Bank <input class="form-control form-control-sm pel-bank" name="pel[__I__][bank]"></label>
                  <label>NPWP <input class="form-control form-control-sm pel-npwp" name="pel[__I__][npwp]"></label>
                </div>
                <button type="button" class="btn btn-link btn-sm text-danger p-0 pel-del">hapus baris</button>
              </div>
            </template>
            <div class="form-group">
              <label for="kegiatan-kode-output" class="form-left w-150px">Kode Output</label>
              <span class="form-right">
                <select id="kegiatan-kode-output" name="KegiatanKodeOutput" class="form-control form-control-sm">
                  <option value="">— pilih —</option>
                  <?php foreach (($OutputList ?? array()) as $o): ?>
                    <option value="<?php echo html_escape($o) ?>"><?php echo html_escape($o) ?></option>
                  <?php endforeach ?>
                </select>
              </span>
            </div>
            <div class="form-group">
              <label for="kegiatan-asal-tujuan" class="form-left w-150px">Asal &rarr; Tujuan</label>
              <span class="form-right"><input type="text" id="kegiatan-asal-tujuan" name="KegiatanAsalTujuan" class="form-control form-control-sm" placeholder="Pangkal Pinang ke Kab. Bangka Tengah PP"></span>
            </div>
            <div class="form-group">
              <label for="kegiatan-jml-hari" class="form-left w-150px">Jumlah Hari</label>
              <span class="form-right"><input type="number" min="0" id="kegiatan-jml-hari" name="KegiatanJmlHari" class="form-control form-control-sm" style="max-width:110px"></span>
            </div>
            <div class="form-group">
              <label for="kegiatan-pemohon-tipe" class="form-left w-150px">Tipe Pemohon</label>
              <span class="form-right">
                <select id="kegiatan-pemohon-tipe" name="KegiatanPemohonTipe" class="form-control form-control-sm">
                  <option value="internal">Internal (Pegawai BPOM)</option>
                  <option value="eksternal">Eksternal (Penyedia / Pelaku Usaha)</option>
                </select>
              </span>
            </div>
            <div class="form-group">
              <label for="kegiatan-pemohon-phone" class="form-left w-150px">No. WhatsApp Pemohon</label>
              <span class="form-right">
                <input type="text" id="kegiatan-pemohon-phone" name="KegiatanPemohonPhone" class="form-control form-control-sm" placeholder="Contoh: 62812xxxxxxx" inputmode="numeric">
                <small class="text-muted">Pemohon akan menerima notifikasi WhatsApp saat status pengajuannya berubah.</small>
              </span>
            </div>
            <div class="form-group">
              <label for="kegiatan-keterangan" class="form-left w-150px">Keterangan</label>
              <span class="form-right"><textarea id="kegiatan-keterangan" class="form-control form-control-sm KegiatanNote" name="KegiatanKeterangan"></textarea></span>
            </div>
            <div class="form-group">
              <label for="kegiatan-jenis" class="form-left w-150px">Jenis Pengajuan</label>
              <span class="form-right">
                <select id="kegiatan-jenis" name="KegiatanJenisID" class="form-control form-control-sm KegiatanJenis" required="">
                  <?php foreach ($JenisList as $j): ?>
                    <option value="<?php echo (int) $j['JenisID'] ?>"><?php echo html_escape($j['JenisNama']) ?></option>
                  <?php endforeach ?>
                </select>
              </span>
            </div>
            <div class="form-group">
              <label for="kegiatan-dest-user" class="form-left w-150px">Petugas Tujuan</label>
              <span class="form-right">
                <select id="kegiatan-dest-user" class="form-control form-control-sm KegiatanDestUser" name="KegiatanDestUser">
                  <?php foreach ($UserDest as $value): ?>
                    <option value="<?php echo $value['UserID'] ?>"><?php echo $value['UserFullName'] ?></option>
                  <?php endforeach ?>
                </select>
              </span>
            </div>
            <div class="form-group">
              <label for="KegiatanLampiran" class="form-left w-150px">Lampiran (PDF)</label>
              <span class="form-right">
                <input type="file" accept="application/pdf" id="KegiatanLampiran" name="KegiatanLampiran">
                <div class="input-group input-group-sm mt-1 div_remove_KegiatanLampiranPrev">
                  <input type="text" class="form-control" id="KegiatanLampiranPrev" name="KegiatanLampiranPrev" readonly="">
                  <span class="input-group-append">
                    <button type="button" class="btn btn-danger btn-flat btn_remove_KegiatanLampiranPrev" title="Hapus lampiran sebelumnya" aria-label="Hapus lampiran sebelumnya"><?php echo svgico('trash',14) ?></button>
                  </span>
                </div>
              </span>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default modal-close" data-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>
