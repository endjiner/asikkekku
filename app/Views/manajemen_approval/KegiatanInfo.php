<style type="text/css">.p-td-5 td { padding: 6px !important; }</style>

<?php
/* Peringatan Dini 4HK — banner ringkas di atas detail. */
$sla = isset($sla) ? $sla : null;
if ($sla && $sla['level'] !== 'draf'):
  $tone = 'info';
  if ($sla['level'] === 'terlambat' || $sla['level'] === 'selesai_telat') $tone = 'danger';
  elseif ($sla['level'] === 'mepet' || $sla['level'] === 'dikembalikan') $tone = 'warning';
  elseif ($sla['level'] === 'selesai_ontime') $tone = 'success';
?>
<div class="ew-sla-banner ew-tone-<?php echo $tone ?>">
  <div class="ew-sla-main">
    <span class="ew-sla-ico"><?php echo svgico($tone === 'danger' ? 'warning' : ($sla['is_done'] ? 'check-double' : 'clock'), 18) ?></span>
    <div>
      <strong><?php echo htmlspecialchars($sla['level_text']) ?></strong>
      <div class="ew-sla-sub">
        <?php if (!empty($sla['deadline'])): ?>Target cair: <strong><?php echo date('d/m/Y', strtotime($sla['deadline'])) ?></strong> &middot; <?php endif ?>
        Berjalan <strong><?php echo (int) $sla['elapsed_hk'] ?> HK</strong> / target <?php echo (int) $sla['total_hk'] ?> HK
        <?php if (!$sla['is_done'] && !empty($sla['stage_position'])): ?>
          &middot; Tahap <strong><?php echo htmlspecialchars($sla['stage_position']) ?></strong>
          <?php if ($sla['stage_elapsed_hk'] > 0): ?>tertahan <strong class="<?php echo $sla['stage_stuck'] ? 'text-danger' : '' ?>"><?php echo (int) $sla['stage_elapsed_hk'] ?> HK</strong><?php endif ?>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>
<?php endif ?>

<div class="ki-grid">
  <div class="ki-panel">
    <div class="card">
      <div class="card-header bg-gray"><h5 class="mb-0 font-weight-bold">Detail Kegiatan</h5></div>
      <div class="card-body">
        <table class="table table-hover p-td-5 no-row-link ki-detail">
          <tr><td class="ki-label">No. Surat</td><td><?php echo $data['KegiatanNoSuratTugas'] ?></td></tr>
          <tr><td>Nama Kegiatan</td><td><?php echo $data['KegiatanJudul'] ?></td></tr>
          <tr><td>Tanggal</td><td><?php echo $data['KegiatanTanggal'] ?></td></tr>
          <tr><td>Pelaksana / Penyedia</td><td><?php echo $data['KegiatanNamaPelaksana'] ?></td></tr>
          <tr>
            <td>Pemohon</td>
            <td>
              <?php $tipe = isset($data['KegiatanPemohonTipe']) ? $data['KegiatanPemohonTipe'] : 'internal'; ?>
              <span class="badge-soft-info"><?php echo ($tipe === 'eksternal') ? 'Eksternal' : 'Internal' ?></span>
              <?php if (!empty($data['KegiatanPemohonPhone'])): ?>
                &nbsp;<?php echo svgico('whatsapp',14,'text-green') ?> <?php echo mask_phone($data['KegiatanPemohonPhone']) ?>
              <?php else: ?>
                &nbsp;<small class="text-muted">(No. WhatsApp belum diisi)</small>
              <?php endif ?>
            </td>
          </tr>
          <tr><td>Status Persetujuan</td><td><?php echo $data['KegiatanStatus'] ?></td></tr>
          <tr>
            <td>Lampiran</td>
            <td>
              <?php if (!empty($data['KegiatanLampiran'])): ?>
                <a href="<?php echo base_url().'assets/lampiran/'.$data['KegiatanLampiran']; ?>" target="_blank" rel="noopener">
                  <?php echo svgico('file-pdf',15) ?> Buka lampiran
                </a>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif ?>
            </td>
          </tr>
          <tr><td>Keterangan</td><td><?php echo nl2br($data['KegiatanKeterangan']) ?></td></tr>
          <tr><td>No. Kwitansi</td><td><?php echo nl2br($data['KegiatanNoKwitansi']) ?></td></tr>
          <tr><td>No. SPTJB</td><td><?php echo $data['KegiatanNoSPTJB'] ?></td></tr>
          <tr>
            <td>Dokumen Pencairan</td>
            <td>
              <a href="<?php echo base_url('dokumen/unified/' . (int)$data['KegiatanID']) ?>" target="_blank" class="btn btn-xs btn-primary font-weight-bold">
                <i class="fas fa-file-alt mr-1"></i> Preview &amp; Cetak Seluruh Dokumen (Unified Reader)
              </a>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="ki-panel">
    <div class="card">
      <div class="card-header bg-gray"><h5 class="mb-0 font-weight-bold">Riwayat Persetujuan</h5></div>
      <div class="card-body">
        <div class="timeline text-sm">
          <?php if (empty($history)): ?>
            <div>
              <span class="tl-dot bg-gray"><?php echo svgico('clock',15) ?></span>
              <div class="timeline-item">
                <h3 class="timeline-header">PJ-Kegiatan</h3>
                <div class="timeline-body">Proses input / penyusunan berkas.</div>
              </div>
            </div>
          <?php endif ?>
          <?php
            $warn_stage_hk = ($sla && isset($sla['warn_stage_hk'])) ? (int) $sla['warn_stage_hk'] : 1;
            $prev_date = null;
          ?>
          <?php foreach ($history as $value): ?>
            <?php
              $delta = ($prev_date && !empty($value['FlowDate'])) ? sla_hari_kerja($prev_date, $value['FlowDate']) : null;
              $delta_late = ($delta !== null && $delta > $warn_stage_hk);
            ?>
            <?php if ($value['FlowCode'] == 'PJK'): ?>
              <div>
                <span class="tl-dot bg-blue"><?php echo svgico('send',15) ?></span>
                <div class="timeline-item">
                  <span class="time"><?php echo svgico('clock',12) ?> <?php echo $value['FlowDate'] ?></span>
                  <h3 class="timeline-header"><span class="tl-name"><?php echo $value['FlowUserName'] ?></span> <small class="tl-role">[<?php echo $value['FlowUserPosition'] ?>]</small></h3>
                  <div class="timeline-body"><?php echo $value['FlowKeterangan'] ?></div>
                </div>
              </div>
            <?php else: ?>
              <div>
                <?php if ($value['FlowResult'] == 1): ?>
                  <span class="tl-dot bg-green"><?php echo svgico('thumbs-up',15) ?></span>
                <?php else: ?>
                  <span class="tl-dot bg-maroon"><?php echo svgico('thumbs-down',15) ?></span>
                <?php endif ?>
                <div class="timeline-item">
                  <span class="time"><?php echo svgico('clock',12) ?> <?php echo $value['FlowDate'] ?>
                    <?php if ($delta !== null): ?>
                      <span class="ew-delta <?php echo $delta_late ? 'ew-delta-late' : '' ?>">+<?php echo (int) $delta ?> HK</span>
                    <?php endif ?>
                  </span>
                  <h3 class="timeline-header">
                    <span class="tl-name"><?php echo $value['FlowUserName'] ?></span>
                    <small class="tl-role">[<?php
                      $aksi = ($value['FlowResult'] == 1) ? 'Disetujui' : 'Dikembalikan';
                      if ($value['FlowResult'] != 1 && !empty($value['FlowRejectType'])) {
                        $aksi .= ($value['FlowRejectType'] === 'terminate') ? ' (minta Hentikan Proses)' : ' (Revisi)';
                      }
                      echo $value['FlowUserPosition'].' : '.$aksi;
                    ?>]</small>
                  </h3>
                  <div class="timeline-body"><?php echo $value['FlowKeterangan'] ?></div>
                </div>
              </div>
            <?php endif ?>
            <?php $prev_date = $value['FlowDate']; ?>
          <?php endforeach ?>
          <?php if (!empty($history) && !empty($last_status)): ?>
            <?php $wait_hk = ($sla && !$sla['is_done']) ? (int) $sla['stage_elapsed_hk'] : 0; ?>
            <div>
              <span class="tl-dot <?php echo ($sla && $sla['stage_stuck']) ? 'bg-maroon' : 'bg-gray' ?>"><?php echo svgico('hourglass',15) ?></span>
              <div class="timeline-item">
                <h3 class="timeline-header">Menunggu: <?php echo $last_status[0]['FlowPosition'] ?>
                  <?php if ($wait_hk > 0): ?>
                    <span class="ew-delta <?php echo ($sla && $sla['stage_stuck']) ? 'ew-delta-late' : '' ?>">tertahan <?php echo $wait_hk ?> HK</span>
                  <?php endif ?>
                </h3>
                <div class="timeline-body">Menunggu tindak lanjut petugas berikutnya<?php echo !empty($value['FlowDestUserName']) ? ' ('.$value['FlowDestUserName'].')' : '' ?>.</div>
              </div>
            </div>
          <?php endif ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $kegID = isset($data['KegiatanID']) ? (int) $data['KegiatanID'] : 0; ?>
<?php if ($kegID): ?>
<div class="card mt-3 shadow-none border-0" id="dok-panel-card" style="background: transparent;">
  <div class="card-header bg-transparent px-0 pt-0 pb-2 d-flex align-items-center justify-content-between border-0">
    <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-layer-group text-primary mr-2"></i>Berkas &amp; Dokumen Pencairan</h5>
    <span class="text-sm text-muted" id="dok-panel-stat"></span>
  </div>
  <div class="card-body p-0" id="dok-panel-body">
    <div class="d-flex align-items-center justify-content-center py-5 text-muted bg-white rounded-lg border">
      <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
      <span>Memuat pratinjau seluruh dokumen pencairan...</span>
    </div>
  </div>
</div>
<script>
  (function () {
    var url = '<?php echo base_url() ?>dokumen/unifiedPreview/<?php echo $kegID ?>';
    var $b = jQuery('#dok-panel-body'), $s = jQuery('#dok-panel-stat');
    jQuery.get(url).done(function (html) { $b.html(html); $s.text(''); })
      .fail(function () { $b.html('<div class="alert alert-danger text-sm m-2">Gagal memuat pratinjau dokumen pencairan.</div>'); $s.text('gagal'); });
  })();
</script>
<?php endif ?>
