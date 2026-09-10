<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
            <?php echo illus('users', 40); ?> <span class="ml-2"><?= $menu_detail['MenuName'] ?></span>
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
                <span class="lt-ico"><?php echo illus('user-group', 22); ?></span>
                <span class="lt-txt">
                  <b>Daftar Pengguna</b>
                  <small>Akun petugas alur persetujuan beserta peran dan statusnya.</small>
                </span>
              </div>
              <button type="button" class="btn btn-primary UserAdd" data-toggle="modal" data-target="#modal-l-User">
                <?php echo svgico('add', 18) ?> Tambah Pengguna
              </button>
            </div>
            <div class="card-body">
              <table id="table-data" class="table table-sm text-sm table-bordered table-hover data-row-link wp-nw" width="100%">
                <thead>
                  <tr>
                    <th class="ta-ci">No</th>
                    <th class="ta-ci">Nama Pengguna</th>
                    <th class="ta-ci">Nama Lengkap</th>
                    <th class="ta-ci">Posisi</th>
                    <th class="ta-ci">No. WhatsApp</th>
                    <th class="ta-ci">Keterangan</th>
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

<div class="modal show" id="modal-l-User" aria-modal="true" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-l">
    <form role="form" class="UserModify" action="<?= base_url() ?>manajemen_app/UserModify" method="post" autocomplete="off">
      <div class="modal-content text-sm">
        <div class="modal-header bg-teal text-white ta-c">
          <h5 class="modal-title">Formulir Tambah / Ubah Pengguna</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body row">
          <div class="col-12">
            <div class="form-group">
              <label for="user-id" class="form-left w-150px">ID</label>
              <span class="form-right"><input type="text" id="user-id" name="UserID" class="form-control form-control-sm" readonly=""></span>
            </div>
            <div class="form-group">
              <label for="user-name" class="form-left w-150px">Nama Pengguna</label>
              <span class="form-right"><input type="text" id="user-name" name="UserName" class="form-control form-control-sm" placeholder="username" required=""></span>
            </div>
            <div class="form-group">
              <label for="user-full-name" class="form-left w-150px">Nama Lengkap</label>
              <span class="form-right"><input type="text" id="user-full-name" name="UserFullName" class="form-control form-control-sm" placeholder="Nama lengkap" required=""></span>
            </div>
            <div class="form-group">
              <label for="user-position" class="form-left w-150px">Posisi</label>
              <span class="form-right">
                <select id="user-position" class="form-control form-control-sm UserPosition" name="UserPosition">
                  <?php foreach ($position as $value): ?>
                    <option value="<?php echo $value['VrblValue'] ?>"><?php echo $value['VrblValue'] ?></option>
                  <?php endforeach ?>
                </select>
              </span>
            </div>
            <div class="form-group">
              <label for="user-phone" class="form-left w-150px">No. WhatsApp</label>
              <span class="form-right">
                <input type="text" id="user-phone" name="UserPhone" class="form-control form-control-sm" placeholder="Contoh: 62812xxxxxxx" inputmode="numeric">
                <small class="text-muted">Diperlukan untuk petugas alur persetujuan (PJ-Kegiatan s.d. PPSPM).</small>
              </span>
            </div>
            <div class="form-group">
              <label for="user-password" class="form-left w-150px">Kata Sandi Baru</label>
              <span class="form-right">
                <div class="input-group input-group-sm">
                  <input type="password" id="user-password" name="UserPassword" class="form-control form-control-sm pw-new" placeholder="Kosongkan bila tidak diubah" autocomplete="new-password">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary toggle-pass" tabindex="-1" title="Lihat / sembunyikan" aria-label="Lihat atau sembunyikan kata sandi">
                      <span class="tp-on"><?php echo svgico('eye', 14) ?></span>
                      <span class="tp-off d-none"><?php echo svgico('lock', 14) ?></span>
                    </button>
                  </div>
                </div>
                <small class="text-muted">Isi kolom ini untuk mengganti kata sandi pengguna. Kosongkan bila tidak diubah.</small>
              </span>
            </div>
            <div class="form-group">
              <label for="user-password-verify" class="form-left w-150px">Ulangi Kata Sandi Baru</label>
              <span class="form-right">
                <div class="input-group input-group-sm">
                  <input type="password" id="user-password-verify" name="UserPasswordVerify" class="form-control form-control-sm pw-verify" placeholder="Ketik ulang kata sandi baru" autocomplete="new-password">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary toggle-pass" tabindex="-1" title="Lihat / sembunyikan" aria-label="Lihat atau sembunyikan kata sandi">
                      <span class="tp-on"><?php echo svgico('eye', 14) ?></span>
                      <span class="tp-off d-none"><?php echo svgico('lock', 14) ?></span>
                    </button>
                  </div>
                </div>
                <small class="text-muted pw-match-hint"></small>
              </span>
            </div>
            <div class="form-group">
              <label for="user-note" class="form-left w-150px">Keterangan</label>
              <span class="form-right"><textarea id="user-note" class="form-control form-control-sm UserNote" name="UserNote"></textarea></span>
            </div>
            <div class="form-group">
              <label for="user-active" class="form-left w-150px">Aktif?</label>
              <span class="form-right">
                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                  <input type="checkbox" class="custom-control-input" id="user-active" name="UserAktif" value="1">
                  <label class="custom-control-label" for="user-active"></label>
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
