<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
            <?php echo illus('user-group', 40); ?> <span class="ml-2"><?= $menu_detail['MenuName'] ?></span>
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
                <span class="lt-ico"><?php echo illus('config', 22); ?></span>
                <span class="lt-txt">
                  <b>Grup Pengguna &amp; Hak Akses</b>
                  <small>Menentukan menu dan aksi yang boleh diakses tiap peran.</small>
                </span>
              </div>
              <button type="button" class="btn btn-primary GroupUserAdd" data-toggle="modal" data-target="#modal-xl-UserGroup">
                <?php echo svgico('add', 18) ?> Tambah Grup
              </button>
            </div>
            <div class="card-body">
              <table id="table-data" class="table table-sm text-sm table-bordered table-hover data-row-link wp-nw" width="100%">
                <thead>
                  <tr>
                    <th class="ta-c">No</th>
                    <th class="ta-c">Nama</th>
                    <th class="ta-c">Keterangan</th>
                    <th class="ta-c">Aksi</th>
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

<div class="modal show" id="modal-xl-UserGroup" aria-modal="true" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl">
    <form role="form" class="UserGroupModify" action="<?= base_url() ?>manajemen_app/UserGroupModify" method="post" autocomplete="off">
      <div class="modal-content text-sm">
        <div class="modal-header bg-teal text-white ta-c">
          <h5 class="modal-title">Formulir Tambah / Ubah Grup</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body row">
          <div class="col-6">
            <div class="form-group">
              <label for="user-group-id" class="form-left w-150px">Group ID</label>
              <span class="form-right">
                <input type="text" id="user-group-id" name="UserGroupID" class="form-control form-control-sm" placeholder="Group ID" readonly="">
              </span>
            </div>
            <div class="form-group">
              <label for="user-group-name" class="form-left w-150px">Nama Grup</label>
              <span class="form-right">
                <input type="text" id="user-group-name" name="UserGroupName" class="form-control form-control-sm t-cap" placeholder="Nama grup" required="">
              </span>
            </div>
            <div class="form-group">
              <label for="user-group-note" class="form-left w-150px">Keterangan Grup</label>
              <span class="form-right">
                <input type="text" id="user-group-note" name="UserGroupNote" class="form-control form-control-sm t-upc" placeholder="Keterangan grup" required="">
              </span>
            </div>
          </div>
          <div class="col-6" style="height: 400px; overflow: auto;">
            <table class="table table-sm table-hover">
              <thead>
                <tr>
                  <th class="ta-c">Menu</th>
                  <th class="ta-c w-10p">Read</th>
                  <th class="ta-c w-10p">Create</th>
                  <th class="ta-c w-10p">Update</th>
                  <th class="ta-c w-10p">Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($menu_all as $key => $value): ?>
                <tr>
                  <td>
                    <?= $value['level'].$value['MenuName'] ?>
                    <input type="hidden" name="menu_access_id[]" class="menu_access_id" value="0">
                    <input type="hidden" name="MenuID[]" class="MenuID" value="<?= $value['MenuID'] ?>">
                  </td>
                  <td>
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success ta-c">
                      <input type="checkbox" name="Status_R[]" class="custom-control-input Status_R" id="<?= $value['MenuID'] ?>_Status_R" value="1">
                      <label class="custom-control-label" for="<?= $value['MenuID'] ?>_Status_R"></label>
                    </div>
                  </td>
                  <td>
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success ta-c">
                      <input type="checkbox" name="Status_C[]" class="custom-control-input Status_C" id="<?= $value['MenuID'] ?>_Status_C" value="1">
                      <label class="custom-control-label" for="<?= $value['MenuID'] ?>_Status_C"></label>
                    </div>
                  </td>
                  <td>
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success ta-c">
                      <input type="checkbox" name="Status_U[]" class="custom-control-input Status_U" id="<?= $value['MenuID'] ?>_Status_U" value="1">
                      <label class="custom-control-label" for="<?= $value['MenuID'] ?>_Status_U"></label>
                    </div>
                  </td>
                  <td>
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success ta-c">
                      <input type="checkbox" name="Status_D[]" class="custom-control-input Status_D" id="<?= $value['MenuID'] ?>_Status_D" value="1">
                      <label class="custom-control-label" for="<?= $value['MenuID'] ?>_Status_D"></label>
                    </div>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
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