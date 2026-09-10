<div class="content-wrapper bg-ligry">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1 class="m-0 h3 font-weight-bold text-dark d-flex align-items-center gap-2">
            <?php echo illus('config', 40); ?> <span class="ml-2"><?= $menu_detail['MenuName'] ?></span>
          </h1>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-8">
          <div class="card list-card">
            <div class="card-header list-toolbar">
              <div class="lt-head">
                <span class="lt-ico"><?php echo illus('config', 22); ?></span>
                <span class="lt-txt">
                  <b>Identitas, Tautan &amp; Target Waktu</b>
                  <small>Judul, deskripsi, logo, tautan panduan/anggaran, dan ambang SLA aplikasi.</small>
                </span>
              </div>
            </div>
            <form role="form" class="UserModify" enctype="multipart/form-data" action="<?= base_url() ?>manajemen_app/KonfigurasiAppModify" method="post" autocomplete="off">
              <input type="hidden" name="<?php echo $this->security->get_csrf_token_name() ?>" value="<?php echo $this->security->get_csrf_hash() ?>">
              <div class="card-body row">
                <div class="col-md-6">
                  <div class="form-group row">
                    <label for="app-title" class="col-sm-3 col-form-label"> Judul Aplikasi</label>
                    <span class="col-sm-9">
                      <input type="text" id="app-title" name="app_title" class="form-control form-control-sm" placeholder="judul singkat aplikasi" value="<?php echo $data_list['app_title'] ?>" required="">
                    </span>
                  </div> 
                  <div class="form-group row">
                    <label for="app-description" class="col-sm-3 col-form-label"> Deskripsi Aplikasi</label>
                    <span class="col-sm-9">
                      <input type="text" id="app-description" name="app_description" class="form-control form-control-sm" placeholder="deskripsi aplikasi" value="<?php echo $data_list['app_description'] ?>" required="">
                    </span>
                  </div>
                  <div class="form-group row">
                    <label for="BigLogoFile" class="col-sm-3 col-form-label"> Logo Besar</label>
                    <span class="col-sm-9">
                      <div class="input-group">
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" accept="image/png" id="BigLogoFile" name="logo_big">
                          <label class="custom-file-label" for="BigLogoFile">Pilih berkas</label>
                        </div> 
                      </div>
                      <img id="BigLogoFilePreview" src="<?php echo base_url($data_list['logo_big']) ?>" alt="your image" class="mt-2 mb-2 p-2" style="max-width: 300px; max-height: 100px; border: 1px solid grey;"/>
                    </span>
                  </div> 
                  <div class="form-group row">
                    <label for="SmallLogoFile" class="col-sm-3 col-form-label"> Logo Kecil</label>
                    <span class="col-sm-9">
                      <div class="input-group">
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" accept="image/png" id="SmallLogoFile" name="logo_small">
                          <label class="custom-file-label" for="SmallLogoFile">Pilih berkas</label>
                        </div> 
                      </div>
                      <img id="SmallLogoFilePreview" src="<?php echo base_url($data_list['logo_small']) ?>" alt="your image" class="mt-2 mb-2 p-2" style="max-width: 300px; max-height: 100px; border: 1px solid grey;"/>
                    </span>
                  </div> 
                </div>
                <div class="col-md-6">
                  <div class="form-group row">
                    <label for="cover-description" class="col-sm-3 col-form-label"> Deskripsi Sampul</label>
                    <span class="col-sm-9">
                      <input type="text" id="cover-description" name="cover_description" class="form-control form-control-sm" placeholder="deskripsi aplikasi" value="<?php echo $data_list['cover_description'] ?>" required="">
                    </span>
                  </div>
                  <div class="form-group row">
                    <label for="CoverLogoFile" class="col-sm-3 col-form-label"> Logo Sampul</label>
                    <span class="col-sm-9">
                      <div class="input-group">
                        <div class="custom-file">
                          <input type="file" class="custom-file-input" accept="image/png" id="CoverLogoFile" name="cover_logo">
                          <label class="custom-file-label" for="CoverLogoFile">Pilih berkas</label>
                        </div> 
                      </div>
                      <img id="CoverLogoFilePreview" src="<?php echo base_url($data_list['cover_logo']) ?>" alt="your image" class="mt-2 mb-2 p-2" style="max-width: 300px; max-height: 100px; border: 1px solid grey;"/>
                    </span>
                  </div>
                  <div class="form-group row">
                    <label for="link-panduan" class="col-sm-3 col-form-label"> Tautan Panduan</label>
                    <span class="col-sm-9">
                      <input type="text" id="link-panduan" name="link_panduan" class="form-control form-control-sm" value="<?php echo $data_list['link_panduan'] ?>" required="">
                    </span>
                  </div>
                  <div class="form-group row">
                    <label for="link-anggaran" class="col-sm-3 col-form-label"> Tautan Anggaran</label>
                    <span class="col-sm-9">
                      <input type="text" id="link-anggaran" name="link_anggaran" class="form-control form-control-sm" value="<?php echo $data_list['link_anggaran'] ?>" required="">
                    </span>
                  </div>
                </div>
                <div class="col-12">
                  <hr>
                  <h6 class="font-weight-bold text-teal d-flex align-items-center gap-2 mb-2">
                    <?php echo svgico('clock', 16) ?> Target Waktu &amp; Peringatan Dini (SLA)
                  </h6>
                  <p class="text-muted small mb-3">Target pencairan dihitung dalam <strong>hari kerja (Senin&ndash;Jumat)</strong> sejak pengajuan dikirim. Nilai di bawah dipakai untuk menandai pengajuan yang mulai terhambat.</p>
                </div>
                <div class="col-md-4">
                  <div class="form-group row">
                    <label for="sla-total-hk" class="col-sm-6 col-form-label">Target total (HK)</label>
                    <span class="col-sm-6">
                      <input type="number" id="sla-total-hk" min="1" max="60" name="sla_total_hk" class="form-control form-control-sm" value="<?php echo isset($data_list['sla_total_hk']) ? (int) $data_list['sla_total_hk'] : 4 ?>" required>
                      <small class="form-text text-muted">Batas waktu pencairan sejak pengajuan dikirim.</small>
                    </span>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group row">
                    <label for="sla-warn-total-hk" class="col-sm-6 col-form-label">Waspada total &ge; (HK)</label>
                    <span class="col-sm-6">
                      <input type="number" id="sla-warn-total-hk" min="1" max="60" name="sla_warn_total_hk" class="form-control form-control-sm" value="<?php echo isset($data_list['sla_warn_total_hk']) ? (int) $data_list['sla_warn_total_hk'] : 3 ?>" required>
                      <small class="form-text text-muted">Mulai tampil sebagai peringatan ketika total hari kerja mencapai nilai ini.</small>
                    </span>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group row">
                    <label for="sla-warn-stage-hk" class="col-sm-6 col-form-label">Tahap tertahan &gt; (HK)</label>
                    <span class="col-sm-6">
                      <input type="number" id="sla-warn-stage-hk" min="1" max="30" name="sla_warn_stage_hk" class="form-control form-control-sm" value="<?php echo isset($data_list['sla_warn_stage_hk']) ? (int) $data_list['sla_warn_stage_hk'] : 1 ?>" required>
                      <small class="form-text text-muted">Peringatan jika satu tahap melewati nilai ini tanpa tindak lanjut.</small>
                    </span>
                  </div>
                </div>
                <div class="col-12">
                  <center>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </center>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card list-card">
            <div class="card-header list-toolbar">
              <div class="lt-head">
                <span class="lt-ico"><?php echo illus('approval-check', 22); ?></span>
                <span class="lt-txt">
                  <b>Urutan Alur Persetujuan</b>
                  <small>Aktif/nonaktifkan tahap dan atur urutannya per jenis pengajuan.</small>
                </span>
              </div>
            </div>
            <div class="card-body pb-0">
              <div class="form-group mb-0">
                <label for="flow-jenis" class="text-sm mb-1">Jenis Pengajuan</label>
                <select id="flow-jenis" class="form-control form-control-sm"
                  onchange="window.location.href='<?= base_url() ?>manajemen_app/konfigurasi_app?jenis='+this.value">
                  <?php foreach (($jenis_list ?? array()) as $j): ?>
                    <option value="<?php echo (int) $j['JenisID'] ?>" <?php echo ((int) $j['JenisID'] === (int) ($jenis_selected ?? 0)) ? 'selected' : '' ?>>
                      <?php echo html_escape($j['JenisNama']) ?><?php echo empty($j['JenisAktif']) ? ' (nonaktif)' : '' ?>
                    </option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>
            <form role="form" class="FlowModify" enctype="multipart/form-data" action="<?= base_url() ?>manajemen_app/KonfigurasiAppFlowOrder" method="post" autocomplete="off">
              <input type="hidden" name="JenisID" value="<?php echo (int) ($jenis_selected ?? 1) ?>">
              <div class="card-body row">
                <div class="col-12">
                  <div class="table-responsive">
                  <table class="table table-sm table-flow">
                    <thead class="text-center">
                      <tr>
                        <th>Urutan / Aktif</th>
                        <th>Posisi</th>
                        <th>Keterangan</th>
                        <th>Tolak</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($flow as $key => $value): ?>
                        <tr>
                          <td class="text-center">
                            <input type="hidden" name="ID[]" value="<?php echo $value['ID'] ?>">
                            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                              <input type="checkbox" class="custom-control-input" name="active[]" id="customSwitch<?php echo $key ?>" <?php echo ($value['FlowOrder'] > 0) ? 'checked' : '' ; ?>>
                              <label class="custom-control-label" for="customSwitch<?php echo $key ?>"></label>
                            </div>
                          </td>
                          <td><?php echo $value['FlowPosition'] ?></td>
                          <td><?php echo $value['FlowNote'] ?></td>
                          <td class="text-center"><?php echo ($value['FlowReject'] == 1) ? svgico('check-double', 14) : '' ; ?></td>
                        </tr>
                      <?php endforeach ?> 
                    </tbody>
                  </table>
                  </div>
                </div>
                <div class="col-12">
                  <center>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </center>
                </div>
              </div>
            </form>
          </div>

          <?php
            $capRow = $this->db->get_where('tb_vrbl', array('VrblName' => 'dok_cap_image'))->row_array();
            $capPath = !empty($capRow['VrblValue']) ? $capRow['VrblValue'] : '';
            $gateRow = $this->db->get_where('tb_vrbl', array('VrblName' => 'dok_gate_ppk'))->row_array();
            $gateOn  = !empty($gateRow) && in_array(strtolower(trim($gateRow['VrblValue'])), array('1','on','true','ya','aktif'), true);
          ?>
          <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Cap Dinas (Dokumen Pencairan)</h3></div>
            <div class="card-body">
              <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="gate-ppk" <?php echo $gateOn ? 'checked' : '' ?>>
                <label class="custom-control-label" for="gate-ppk">
                  Kunci tombol <strong>"Setuju"</strong> PPK sampai semua dokumen slot PPK ditandatangani
                  <span id="gate-ppk-stat" class="text-sm ml-1 text-muted"></span>
                </label>
              </div>
              <p class="text-sm text-muted">
                PNG <strong>transparan</strong>, maks 600 KB / 1200&times;1200 px. Ditempel otomatis
                menindih tanda tangan PPK pada dokumen (Kwitansi, Nominatif, Riil, SPTJB) yang sudah di-ttd.
              </p>
              <div class="d-flex align-items-start" style="gap:16px">
                <div id="cap-prev-wrap" style="width:140px;height:140px;border:1px dashed #9aa4b0;border-radius:8px;background:#f4f6f8 url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\'><rect width=\'8\' height=\'8\' fill=\'%23e6e9ee\'/><rect x=\'8\' y=\'8\' width=\'8\' height=\'8\' fill=\'%23e6e9ee\'/></svg>');display:flex;align-items:center;justify-content:center;overflow:hidden">
                  <img id="cap-prev" src="<?php echo $capPath ? base_url($capPath) . '?v=' . time() : '' ?>" style="max-width:100%;max-height:100%;<?php echo $capPath ? '' : 'display:none' ?>" alt="cap">
                  <span id="cap-empty" class="text-muted text-sm"<?php echo $capPath ? ' style="display:none"' : '' ?>>belum ada</span>
                </div>
                <div>
                  <input type="file" id="cap-file" accept="image/png" class="form-control-file mb-2">
                  <div>
                    <button type="button" class="btn btn-sm btn-primary" id="cap-save">Simpan Cap</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="cap-del">Hapus</button>
                    <span id="cap-stat" class="text-sm ml-1"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <script>
          (function () {
            var toast = function (t, m) { if (window.show_toast) show_toast(t, m); };
            var f = document.getElementById('cap-file'), stat = document.getElementById('cap-stat');
            document.getElementById('cap-save').addEventListener('click', function () {
              if (!f.files[0]) { toast('error', 'Pilih berkas cap dulu.'); return; }
              var fd = new FormData(); fd.append('cap', f.files[0]);
              stat.textContent = 'mengunggah…'; stat.className = 'text-sm ml-1 text-muted';
              jQuery.ajax({ url: '<?php echo base_url() ?>dokumen/capUpload', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
                .done(function (r) {
                  stat.textContent = r.msg; stat.className = 'text-sm ml-1 ' + (r.ok ? 'text-success' : 'text-danger');
                  toast(r.ok ? 'success' : 'error', r.msg || (r.ok ? 'Cap dinas tersimpan.' : 'Gagal menyimpan cap.'));
                  if (r.ok && r.src) { var p = document.getElementById('cap-prev'); p.src = r.src; p.style.display = ''; document.getElementById('cap-empty').style.display = 'none'; }
                })
                .fail(function () { stat.textContent = 'Gagal.'; stat.className = 'text-sm ml-1 text-danger'; toast('error', 'Gagal mengunggah cap dinas.'); });
            });
            document.getElementById('cap-del').addEventListener('click', function () {
              confirmAksi({ title: 'Hapus cap dinas?', text: 'Cap tidak akan lagi ditempel pada dokumen.', confirmText: 'Ya, hapus', tone: 'danger' })
                .then(function (ok) {
                  if (!ok) return;
                  jQuery.post('<?php echo base_url() ?>dokumen/capHapus', {}, null, 'json')
                    .done(function (r) {
                      stat.textContent = (r && r.msg) || ''; stat.className = 'text-sm ml-1 text-muted';
                      toast('success', (r && r.msg) || 'Cap dinas dihapus.');
                      document.getElementById('cap-prev').style.display = 'none'; document.getElementById('cap-empty').style.display = '';
                    })
                    .fail(function () { toast('error', 'Gagal menghapus cap dinas.'); });
                });
            });
            var g = document.getElementById('gate-ppk'), gs = document.getElementById('gate-ppk-stat');
            if (g) g.addEventListener('change', function () {
              jQuery.post('<?php echo base_url() ?>dokumen/gatePpkSet', { on: g.checked ? 1 : 0 }, null, 'json')
                .done(function (r) {
                  var ok = r && r.ok;
                  gs.textContent = ok ? 'tersimpan' : 'gagal';
                  toast(ok ? 'success' : 'error', ok
                    ? ('Gate "Setuju" PPK ' + (g.checked ? 'DIAKTIFKAN' : 'dinonaktifkan') + '.')
                    : 'Gagal menyimpan pengaturan gate.');
                })
                .fail(function () { gs.textContent = 'gagal'; g.checked = !g.checked; toast('error', 'Gagal menyimpan pengaturan gate.'); });
            });
          })();
          </script>
        </div>
      </div>
    </div>
  </section>
</div>
