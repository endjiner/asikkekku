<?php
/**
 * Dasbor ASIKKEKKU — Layout Terstruktur Berdasarkan Skala Prioritas & Sadar-Peran
 * - PJ-Kegiatan: Pengusul berkas, fokus memantau posisi & status terakhir tiap pengajuan miliknya serta membuat pengajuan baru.
 * - Petugas Alur / Verifikator / PPK / PPSPM / Admin: Fokus antrean verifikasi tindakan, denyut alur beban 7 meja & monitoring SLA 4 HK.
 */
$_h = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
$_b = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$_t = time();
$tgl = $_h[(int) date('w', $_t)] . ', ' . date('j', $_t) . ' ' . $_b[(int) date('n', $_t)] . ' ' . date('Y', $_t);

$ew    = isset($early_warnings) ? $early_warnings : array('items' => array(), 'summary' => array(), 'can_nudge' => false);
$ewsum = $ew['summary'] + array('aman' => 0, 'mepet' => 0, 'terlambat' => 0, 'dikembalikan' => 0);
$items = isset($action_items) ? $action_items : array();
$nQueue = isset($action_items_total) ? (int) $action_items_total : count($items);
$nLate  = (int) $ewsum['terlambat'];

$is_pj    = ($UserPosition === 'PJ-Kegiatan');
$is_admin = ($UserPosition === 'SuperAdmin');
$queue_url = $is_pj ? base_url('manajemen_approval/list_data') : base_url('manajemen_approval/list_approval');

$pj = isset($pj_summary) ? $pj_summary : array('draft' => 0, 'revisi' => 0, 'perlu_tindakan' => 0, 'on_progress' => 0, 'complete' => 0, 'total' => 0);
$pj_items = isset($pj_submissions) ? $pj_submissions : array();
$pj_total = isset($pj_submissions_total) ? (int) $pj_submissions_total : count($pj_items);

$lateIds = array();
$hkMap   = array();
foreach ($ew['items'] as $w) {
	$id = (int) $w['KegiatanID'];
	$hkMap[$id] = (int) $w['stage_hk'];
	if ($w['level'] === 'terlambat') $lateIds[$id] = true;
}

// 1. Antrean Tindakan Anda: Selalu utamakan skala prioritas waktu yang paling lama menunggu (stage_hk tertinggi)
usort($items, function ($a, $b) use ($lateIds, $hkMap) {
	$ha = isset($hkMap[(int) $a['KegiatanID']]) ? $hkMap[(int) $a['KegiatanID']] : 0;
	$hb = isset($hkMap[(int) $b['KegiatanID']]) ? $hkMap[(int) $b['KegiatanID']] : 0;
	if ($ha !== $hb) return $hb - $ha; // Paling lama menunggu di urutan teratas
	$la = isset($lateIds[(int) $a['KegiatanID']]) ? 1 : 0;
	$lb = isset($lateIds[(int) $b['KegiatanID']]) ? 1 : 0;
	if ($la !== $lb) return $lb - $la;
	return (int) $b['KegiatanID'] - (int) $a['KegiatanID'];
});

// 2. Perlu Perhatian (SLA): Selalu utamakan skala prioritas waktu yang paling lama menunggu (stage_hk tertinggi)
$lateItems = array_values(array_filter($ew['items'], function ($w) { return $w['level'] === 'terlambat'; }));
if (empty($lateItems)) $lateItems = $ew['items'];
usort($lateItems, function ($a, $b) {
	$ha = isset($a['stage_hk']) ? (int) $a['stage_hk'] : 0;
	$hb = isset($b['stage_hk']) ? (int) $b['stage_hk'] : 0;
	if ($ha !== $hb) return $hb - $ha; // Paling lama menunggu di urutan teratas
	return (int) $b['KegiatanID'] - (int) $a['KegiatanID'];
});

// 3. Status Terakhir Pengajuan PJ: Utamakan revisi lalu waktu menunggu terlama
usort($pj_items, function ($a, $b) {
	$ra = ($a['badge_type'] === 'revisi') ? 1 : 0;
	$rb = ($b['badge_type'] === 'revisi') ? 1 : 0;
	if ($ra !== $rb) return $rb - $ra;
	$ha = isset($a['elapsed_hk']) ? (int) $a['elapsed_hk'] : 0;
	$hb = isset($b['elapsed_hk']) ? (int) $b['elapsed_hk'] : 0;
	if ($ha !== $hb) return $hb - $ha;
	return (int) $b['KegiatanID'] - (int) $a['KegiatanID'];
});

$pipe = isset($pipeline) ? $pipeline : array();
$pipeTotal = 0;
foreach ($pipe as $p) $pipeTotal += (int) $p['count'];
$kpi = isset($kpi_bulan) ? $kpi_bulan : array('selesai_bulan_ini' => 0, 'avg_hk' => null);
$shortPos = function ($pos) {
	$m = array('PJ-Kegiatan' => 'Pengusulan (PJ)', 'PPK-Staff' => 'Staf PPK', 'SPM' => 'SPM', 'Verifikator' => 'Verifikator', 'PPK' => 'PPK', 'PPSPM' => 'PPSPM');
	return isset($m[$pos]) ? $m[$pos] : $pos;
};

$myStageCount = null;
foreach ($pipe as $p) {
	if ($p['position'] === $UserPosition) { $myStageCount = (int) $p['count']; break; }
}

// Tahapan standar alur verifikasi sistem (6 Meja) hingga PPSPM
$standardStages = array(
	1 => array('label' => 'PJ Pengusul', 'pos' => 'Pengusulan (PJ)'),
	2 => array('label' => 'Staf PPK',    'pos' => 'Staf PPK'),
	3 => array('label' => 'SPM',         'pos' => 'SPM'),
	4 => array('label' => 'Verifikator', 'pos' => 'Verifikator'),
	5 => array('label' => 'PPK',         'pos' => 'PPK'),
	6 => array('label' => 'PPSPM',       'pos' => 'PPSPM'),
);
?>
<div class="content-wrapper">
  <div class="content">
    <div class="container-fluid db">

      <!-- 1. HEADER RINGKAS & STATUS UTAMA -->
      <header class="db-header-card">
        <div class="db-header-left">
          <div class="db-header-title-row">
            <h1 class="db-header-title">Halo, <?= html_escape($UserFullName) ?></h1>
            <span class="db-header-role"><?= html_escape($UserPosition) ?></span>
          </div>
          <p class="db-header-status">
            <?php if ($is_pj): ?>
              <span class="badge-soft-info"><?php echo svgico('user', 13) ?> Penanggung Jawab Kegiatan</span>
              <span>Pantau status dan posisi terkini berkas pengajuan kegiatan Anda menuju pencairan.</span>
            <?php elseif ($nLate > 0): ?>
              <span class="badge-soft-danger"><?php echo svgico('warning', 13) ?> <?= $nLate ?> Pengajuan Lewat SLA (4 HK)</span>
              <span>Memerlukan perhatian dan percepatan tindak lanjut.</span>
            <?php elseif ($nQueue > 0): ?>
              <span class="badge-soft-info"><?php echo svgico('file', 13) ?> <?= $nQueue ?> Berkas Menunggu di Meja Anda</span>
              <span>Terdapat berkas dalam antrean tindakan verifikasi Anda.</span>
            <?php else: ?>
              <span class="badge-soft-success"><?php echo svgico('check', 13) ?> Alur Lancar</span>
              <span>Semua berkas pengajuan berjalan tepat waktu.</span>
            <?php endif ?>
          </p>
        </div>
        <div class="db-header-right">
          <span class="db-pill-date"><?php echo svgico('clock', 14) ?> <?= $tgl ?></span>
          <?php if ($is_pj): ?>
            <a href="<?= base_url('manajemen_approval/list_data?new=1') ?>" class="btn btn-primary font-weight-bold shadow-sm" style="display:inline-flex; align-items:center; gap:6px;">
              <?php echo svgico('add', 16) ?> Buat Pengajuan Baru
            </a>
          <?php else: ?>
            <span class="db-pill-sla"><?php echo svgico('check-double', 13) ?> Target SLA: 4 HK / Tahap</span>
          <?php endif ?>
        </div>
      </header>

      <!-- 2. SKALA PRIORITAS: 4 KPI METRIC CARDS -->
      <div class="db-kpi-grid">
        <?php if ($is_pj): ?>
          <!-- KPI 1 KHUSUS PJ: Perlu Tindakan Saya (Draf & Revisi) -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card <?= $pj['perlu_tindakan'] > 0 ? 'db-kpi-warning' : 'db-kpi-primary' ?>" title="Draf kegiatan atau berkas yang perlu perbaikan revisi">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $pj['perlu_tindakan'] ?></h2>
                <div class="db-kpi-lbl">Perlu Tindakan Saya</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('edit', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              <?php if ($pj['revisi'] > 0 && $pj['draft'] > 0): ?>
                <b class="text-danger"><?= $pj['revisi'] ?> Perlu Revisi</b> &bull; <?= $pj['draft'] ?> Draf
              <?php elseif ($pj['revisi'] > 0): ?>
                <b class="text-danger"><?= $pj['revisi'] ?> Berkas perlu Anda perbaiki</b>
              <?php elseif ($pj['draft'] > 0): ?>
                <?= $pj['draft'] ?> Draf siap Anda lengkapi &amp; kirim
              <?php else: ?>
                Semua berkas telah terkirim
              <?php endif ?>
            </div>
          </a>

          <!-- KPI 2 KHUSUS PJ: Sedang Diproses di Meja Verifikasi -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card db-kpi-primary" title="Pengajuan Anda yang sedang berjalan di alur verifikasi">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $pj['on_progress'] ?></h2>
                <div class="db-kpi-lbl">Sedang Berjalan</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('guide', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              Sedang diproses di meja verifikasi petugas
            </div>
          </a>

          <!-- KPI 3 KHUSUS PJ: Selesai Dicairkan -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card db-kpi-success" title="Pengajuan Anda yang sudah selesai dicairkan">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $pj['complete'] ?></h2>
                <div class="db-kpi-lbl">Selesai Dicairkan</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('check-double', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              Pengesahan SPM oleh PPSPM telah selesai
            </div>
          </a>

          <!-- KPI 4 KHUSUS PJ: Total Pengajuan Saya -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card db-kpi-info" title="Total seluruh berkas yang Anda ajukan">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $pj['total'] ?></h2>
                <div class="db-kpi-lbl">Total Seluruh Pengajuan</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('stats', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              Semua riwayat pengajuan kegiatan Anda
            </div>
          </a>

        <?php else: ?>
          <!-- KPI 1 PETUGAS / ADMIN: Antrean Anda -->
          <a href="<?= $queue_url ?>" class="db-kpi-card db-kpi-primary" title="Klik untuk membuka antrean">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= $nQueue ?></h2>
                <div class="db-kpi-lbl">Antrean Tindakan Anda</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('file', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              Menunggu keputusan / tindakan verifikasi Anda
            </div>
          </a>

          <!-- KPI 2 PETUGAS / ADMIN: Lewat Target 4 HK -->
          <a href="<?= $queue_url ?>" class="db-kpi-card <?= $nLate > 0 ? 'db-kpi-danger' : 'db-kpi-warning' ?>" title="Pengajuan melewati batas SLA">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= $nLate ?></h2>
                <div class="db-kpi-lbl">Lewat Target 4 HK</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('warning', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              <?= $nLate > 0 ? 'Memerlukan atensi & dorongan tim' : 'Semua berkas aman sesuai target' ?>
            </div>
          </a>

          <!-- KPI 3 PETUGAS / ADMIN: Sedang Berjalan -->
          <div class="db-kpi-card db-kpi-info">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= $pipeTotal ?></h2>
                <div class="db-kpi-lbl">Sedang Berjalan</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('guide', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              Tersebar di 6 tahapan alur verifikasi
            </div>
          </div>

          <!-- KPI 4 PETUGAS / ADMIN: Selesai Bulan Ini -->
          <div class="db-kpi-card db-kpi-success">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $kpi['selesai_bulan_ini'] ?></h2>
                <div class="db-kpi-lbl">Selesai Bulan Ini</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('check-double', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              Rata-rata <b><?= $kpi['avg_hk'] !== null ? str_replace('.', ',', (string) $kpi['avg_hk']) : '-' ?></b> HK hingga PPSPM
            </div>
          </div>
        <?php endif ?>
      </div>

      <?php if (! $is_pj): ?>
        <!-- 3. PIPELINE TRACKER: ALUR 6 TAHAPAN (HANYA UNTUK PETUGAS & ADMIN) -->
        <section class="db-pipeline-card">
          <div class="db-sec-header">
            <div class="db-sec-title-wrap">
              <h3 class="db-sec-title"><?php echo svgico('guide', 18) ?> Denyut Alur Verifikasi (6 Meja)</h3>
              <p class="db-sec-desc">
                Sebaran posisi berkas aktif saat ini pada setiap meja verifikasi hingga pengesahan PPSPM
              </p>
            </div>
            <?php if ($myStageCount !== null && $myStageCount > 0): ?>
              <span class="badge-soft-info"><b><?= $myStageCount ?> berkas</b> berada di tahap Anda</span>
            <?php endif ?>
          </div>

          <div class="db-pipe-steps">
            <?php foreach ($pipe as $p):
              $c = (int) $p['count'];
              $mine = ($p['position'] === $UserPosition);
            ?>
              <div class="db-step-node <?= $c > 0 ? 'has-items' : '' ?> <?= $mine ? 'is-mine' : '' ?>">
                <?php if ($mine): ?>
                  <span class="db-step-tag">Meja Anda</span>
                <?php endif ?>
                <div class="db-step-num"><?= (int) $p['order'] ?></div>
                <div class="db-step-name" title="<?= html_escape($p['label']) ?>"><?= html_escape($shortPos($p['position'])) ?></div>
                <span class="db-step-badge"><?= $c ?></span>
              </div>
            <?php endforeach ?>
          </div>
        </section>
      <?php endif ?>

      <!-- 4. WORKSPACE OPERASIONAL 2 KOLOM -->
      <div class="db-workspace">

        <!-- ============================================================= -->
        <!-- KOLOM UTAMA (KIRI): DAFTAR BERKAS DENGAN STATUS TERKINI       -->
        <!-- ============================================================= -->
        <section class="db-panel-card">
          <div class="db-sec-header">
            <div class="db-sec-title-wrap">
              <h3 class="db-sec-title">
                <?php echo svgico($is_pj ? 'activity' : 'file', 18) ?>
                <?= $is_pj ? 'Status Terakhir Pengajuan Anda' : 'Antrean Tindakan Anda' ?>
                <?php if ($is_pj ? $pj_total > 0 : $nQueue > 0): ?>
                  <span class="db-count"><?= $is_pj ? $pj_total : $nQueue ?></span>
                <?php endif ?>
              </h3>
              <p class="db-sec-desc">
                <?= $is_pj ? 'Pantauan real-time posisi meja verifikasi dan tahapan berkas kegiatan Anda' : 'Diprioritaskan berdasarkan status keterlambatan & batas SLA' ?>
              </p>
            </div>
            <?php if ($is_pj ? !empty($pj_items) : !empty($items)): ?>
              <a href="<?= $queue_url ?>" class="db-more db-more-desktop">Lihat Semua Data <?php echo svgico('arrow-right', 13) ?></a>
            <?php endif ?>
          </div>

          <?php if ($is_pj): ?>
            <!-- ===================== TAMPILAN KHUSUS PJ ===================== -->
            <?php if (empty($pj_items)): ?>
              <div class="db-empty">
                <?php echo illus('done', 44, 'tint-green') ?>
                <h5 class="font-weight-bold mt-3 mb-1">Belum Ada Pengajuan</h5>
                <p>
                  Anda belum memiliki riwayat pengajuan kegiatan. Klik tombol <b>"Buat Pengajuan Baru"</b> di bawah untuk memulai usulan pencairan.
                </p>
                <div class="mt-3">
                  <a href="<?= base_url('manajemen_approval/list_data?new=1') ?>" class="btn btn-primary btn-sm font-weight-bold">
                    <?php echo svgico('add', 14) ?> Buat Pengajuan Baru
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="mb-2 text-muted" style="font-size: 0.78rem;">
                <span class="badge-soft-info py-1 px-2 font-weight-bold mr-1">&#9432; Petunjuk</span>
                Klik pada salah satu <b>baris tabel</b> untuk membuka detail titik alur (6 meja verifikasi) &amp; catatan pengajuan.
              </div>

              <div class="db-table-section mt-2">
                <table id="table-pj-status" class="table table-hover table-striped-soft text-sm w-100" style="margin-bottom:0;">
                  <thead>
                    <tr>
                      <th class="ta-ci" style="width: 6%;">No.</th>
                      <th style="width: 22%;">No. Surat &amp; Tanggal</th>
                      <th style="width: 44%;">Nama Kegiatan</th>
                      <th class="ta-ci" style="width: 20%;">Status / Posisi Meja</th>
                      <th class="ta-ci" style="width: 8%;"><span class="text-muted" title="Klik baris untuk detail">&#9662;</span></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pj_items as $idx => $it):
                      $st = $it['status'];
                      $stepOrder = (int) $it['current_stage_order'];
                      $bType = $it['badge_type'];
                    ?>
                      <tr class="pj-table-row" data-id="<?= $it['KegiatanID'] ?>" style="cursor: pointer;">
                        <td class="ta-ci font-weight-bold text-muted"><?= $idx + 1 ?></td>
                        <td>
                          <div class="font-weight-bold text-dark" style="white-space: nowrap;"><?= html_escape($it['no_surat'] ?: 'Tanpa nomor surat') ?></div>
                          <small class="text-muted"><?= !empty($it['updated_at']) ? date('d M Y', strtotime($it['updated_at'])) : '-' ?></small>
                        </td>
                        <td>
                          <div class="font-weight-bold text-dark" style="white-space: normal; line-height: 1.35;" title="<?= html_escape($it['judul']) ?>">
                            <?= html_escape($it['judul']) ?>
                          </div>
                          <?php if (!empty($it['pelaksana'])): ?>
                            <small class="text-muted d-block" style="white-space: normal; margin-top: 2px;">Pelaksana: <?= html_escape($it['pelaksana']) ?></small>
                          <?php endif ?>
                        </td>
                        <td>
                          <?php if ($bType === 'progress'): ?>
                            <span class="db-pj-desk-badge db-desk-progress">
                              <span class="db-pulse-dot"></span>
                              <span>Meja: <b><?= html_escape($it['current_desk']) ?></b></span>
                            </span>
                          <?php elseif ($bType === 'revisi'): ?>
                            <span class="db-pj-desk-badge db-desk-revisi">
                              <span class="db-pulse-dot-warn"></span>
                              <span><b>Perlu Revisi</b></span>
                            </span>
                          <?php elseif ($bType === 'draft'): ?>
                            <span class="db-pj-desk-badge db-desk-draft">
                              <?php echo svgico('edit', 12) ?>
                              <span><b>Draf</b></span>
                            </span>
                          <?php elseif ($bType === 'success'): ?>
                            <span class="db-pj-desk-badge db-desk-success">
                              <?php echo svgico('check-double', 12) ?>
                              <span><b>Selesai</b></span>
                            </span>
                          <?php else: ?>
                            <span class="db-pj-desk-badge db-desk-draft">
                              <span><?= html_escape($it['status_text']) ?></span>
                            </span>
                          <?php endif ?>
                        </td>
                        <td class="ta-ci" style="width: 44px; text-align: center; vertical-align: middle;">
                          <button type="button" class="btn btn-sm btn-light text-muted pj-expand-btn" style="width: 28px; height: 28px; padding: 0; border: none; border-radius: 6px; background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; pointer-events:none;" title="Klik untuk melihat titik alur 6 meja">
                            <span class="pj-chevron" style="line-height:1; font-size: 0.95rem;">&#9662;</span>
                          </button>

                          <!-- Konten Titik-Titik Meja (Dot-Dot Stepper) yang disembunyikan sampai baris diklik -->
                          <div class="pj-row-hidden-detail d-none">
                            <div class="p-3 rounded-lg my-2 text-left" style="background:#f8fafc !important; text-align:left;">
                              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                <span class="font-weight-bold text-dark" style="font-size:0.83rem;">
                                  <?php echo svgico('guide', 15) ?> Progres Alur Meja Verifikasi (6 Tahap)
                                </span>
                                <?php if ($bType === 'progress' && $it['elapsed_hk'] > 0): ?>
                                  <span class="badge-soft-warning font-weight-bold text-xs">
                                    Tertahan di meja saat ini: <?= (int) $it['elapsed_hk'] ?> HK
                                  </span>
                                <?php endif ?>
                              </div>

                              <!-- Mini Stepper Dot-Dot 6 Meja -->
                              <div class="db-pj-stepper mb-2">
                                <?php foreach ($standardStages as $ord => $stgInfo):
                                  $stepClass = '';
                                  if ($st === 'Approval Selesai') {
                                    $stepClass = 'is-done';
                                  } elseif ($st === 'Perlu Revisi' && $ord === 1) {
                                    $stepClass = 'is-revisi';
                                  } elseif ($ord < $stepOrder) {
                                    $stepClass = 'is-passed';
                                  } elseif ($ord === $stepOrder && $st === 'Approval OnProgress') {
                                    $stepClass = 'is-active';
                                  } elseif ($ord === 1 && $st === 'editable') {
                                    $stepClass = 'is-active';
                                  }
                                ?>
                                  <div class="db-pj-step <?= $stepClass ?>">
                                    <div class="db-pj-step-circle">
                                      <?php if ($stepClass === 'is-passed' || $stepClass === 'is-done'): ?>
                                        <?php echo svgico('check', 11) ?>
                                      <?php elseif ($stepClass === 'is-revisi'): ?>
                                        !
                                      <?php else: ?>
                                        <?= $ord ?>
                                      <?php endif ?>
                                    </div>
                                    <div class="db-pj-step-label"><?= html_escape($stgInfo['label']) ?></div>
                                  </div>
                                <?php endforeach ?>
                              </div>

                              <?php if (!empty($it['catatan_terakhir'])): ?>
                                <div class="db-pj-note <?= $bType === 'revisi' ? 'is-revisi' : '' ?> mt-2 mb-2 text-left">
                                  <?php echo svgico('chat', 13) ?>
                                  <b><?= $bType === 'revisi' ? 'Catatan Perbaikan:' : 'Catatan Petugas Terakhir:' ?></b>
                                  <?= html_escape($it['catatan_terakhir']) ?>
                                </div>
                              <?php endif ?>

                              <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top flex-wrap gap-2 text-muted" style="font-size:0.75rem;">
                                <span><b>ID Pengajuan:</b> #<?= $it['KegiatanID'] ?> &bull; Tanggal: <?= !empty($it['updated_at']) ? date('d F Y', strtotime($it['updated_at'])) : '-' ?></span>
                                <a href="<?= base_url('manajemen_approval/list_data?highlight=' . $it['KegiatanID']) ?>" class="btn btn-xs btn-primary font-weight-bold">
                                  Buka Halaman Data Lengkap <?php echo svgico('arrow-right', 12) ?>
                                </a>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach ?>
                  </tbody>
                </table>
              </div>
            <?php endif ?>

          <?php else: ?>
            <!-- ===================== TAMPILAN PETUGAS / ADMIN ===================== -->
            <?php if (empty($items)): ?>
              <div class="db-empty">
                <?php echo illus('done', 44, 'tint-green') ?>
                <h5 class="font-weight-bold mt-3 mb-1">Semua Beres!</h5>
                <p>
                  Tidak ada berkas yang menunggu tindakan atau persetujuan Anda saat ini.
                </p>
              </div>
            <?php else: ?>
              <div class="db-table-section mt-2">
                <table id="table-queue-status" class="table table-hover table-striped-soft text-sm w-100" style="margin-bottom:0;">
                  <thead>
                    <tr>
                      <th class="ta-ci" style="width: 5%;">No.</th>
                      <th style="width: 22%;">No. Surat</th>
                      <th style="width: 47%;">Nama Kegiatan</th>
                      <th class="ta-ci" style="width: 14%;">SLA / Durasi</th>
                      <th class="ta-ci" style="width: 12%;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $idx => $it):
                      $id = (int) $it['KegiatanID'];
                      $isLate = isset($lateIds[$id]);
                      $hk = isset($hkMap[$id]) ? $hkMap[$id] : 0;
                    ?>
                      <tr class="queue-table-row">
                        <td class="ta-ci font-weight-bold text-muted"><?= $idx + 1 ?></td>
                        <td>
                          <div class="font-weight-bold text-dark" style="white-space: nowrap;"><?= html_escape($it['no_surat'] ?: 'Tanpa nomor surat') ?></div>
                          <?php if (!empty($it['ket'])): ?>
                            <small class="text-muted d-block" style="white-space: normal; line-height: 1.3; margin-top: 1px;"><?= html_escape($it['ket']) ?></small>
                          <?php endif ?>
                        </td>
                        <td>
                          <div class="font-weight-bold text-dark" style="white-space: normal; line-height: 1.35;">
                            <?= html_escape($it['judul']) ?>
                          </div>
                          <?php if (!empty($it['pelaksana'])): ?>
                            <small class="text-muted d-block" style="white-space: normal; margin-top: 2px;">Pelaksana: <?= html_escape($it['pelaksana']) ?></small>
                          <?php endif ?>
                        </td>
                        <td class="ta-ci text-center" style="vertical-align: middle;">
                          <?php if ($isLate): ?>
                            <span class="db-badge-late d-inline-flex align-items-center" style="gap:4px; white-space: nowrap;">
                              <?php echo svgico('warning', 12) ?> +<?= $hk ?> HK
                            </span>
                          <?php elseif ($hk > 0): ?>
                            <span class="db-badge-holding d-inline-flex align-items-center" style="white-space: nowrap;">
                              <?= $hk ?> HK
                            </span>
                          <?php else: ?>
                            <span class="badge-soft-info d-inline-flex align-items-center" style="white-space: nowrap;">
                              Aman
                            </span>
                          <?php endif ?>
                        </td>
                        <td class="ta-ci text-center" style="vertical-align: middle;">
                          <a href="<?= $it['url'] ?>" class="btn btn-sm btn-primary font-weight-bold px-2" style="border-radius:8px; font-size:0.75rem; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                            <?= html_escape($it['cta'] ?: 'Tinjau') ?> <?php echo svgico('arrow-right', 11) ?>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach ?>
                  </tbody>
                </table>
              </div>
            <?php endif ?>
          <?php endif ?>
        </section>

        <!-- ============================================================= -->
        <!-- KOLOM KANAN: PERLU PERHATIAN (SLA) & PENGINGAT WHATSAPP        -->
        <!-- ============================================================= -->
        <section class="db-panel-card">
          <div class="db-sec-header">
            <div class="db-sec-title-wrap">
              <h3 class="db-sec-title text-danger">
                <?php echo svgico('warning', 18) ?> Perlu Perhatian (SLA)
                <?php if (count($lateItems) > 0): ?>
                  <span class="db-count db-count-warn"><?= count($lateItems) ?></span>
                <?php endif ?>
              </h3>
              <p class="db-sec-desc">
                <?= $is_pj ? 'Pengajuan Anda yang tertahan paling lama &amp; berpotensi melewati SLA' : 'Pengajuan yang tertahan paling lama &amp; melewati 4 HK' ?>
              </p>
            </div>
            <?php if (!empty($lateItems)): ?>
              <a href="<?= $queue_url ?>" class="db-more db-more-desktop">Buka Semua Data <?php echo svgico('arrow-right', 13) ?></a>
            <?php endif ?>
          </div>

          <?php if (empty($lateItems)): ?>
            <div class="db-empty db-empty-sm" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:380px; text-align:center;">
              <?php echo illus('check-double', 42, 'tint-green') ?>
              <h6 class="font-weight-bold text-dark mt-3 mb-1">Semua Pengajuan Berjalan Tepat Waktu</h6>
              <p class="text-muted text-xs mb-0" style="max-width: 320px;">
                <?= $is_pj ? 'Semua pengajuan kegiatan Anda saat ini masih diproses dalam batas standar SLA 4 hari kerja.' : 'Semua pengajuan masih berjalan dalam batas standar SLA 4 hari kerja.' ?>
              </p>
            </div>
          <?php else: ?>
            <div class="db-table-section mt-2">
              <table id="table-late-sla" class="table table-hover table-striped-soft text-sm w-100" style="margin-bottom:0;">
                <thead>
                  <tr>
                    <th class="ta-ci" style="width: 6%;">No.</th>
                    <th style="width: 52%;">Kegiatan &amp; Posisi Meja</th>
                    <th class="ta-ci" style="width: 22%;">Keterlambatan</th>
                    <th class="ta-ci" style="width: 20%;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lateItems as $idx => $w): ?>
                    <tr class="late-table-row">
                      <td class="ta-ci font-weight-bold text-muted"><?= $idx + 1 ?></td>
                      <td>
                        <div class="font-weight-bold text-dark" style="white-space: normal; line-height: 1.35;">
                          <?= html_escape($w['judul']) ?>
                        </div>
                        <?php if (!empty($w['no_surat'])): ?>
                          <small class="text-muted d-block" style="white-space: nowrap; margin-top: 1px;"><?= html_escape($w['no_surat']) ?></small>
                        <?php endif ?>
                        <?php if (!empty($w['stage'])): ?>
                          <small class="text-muted d-block mt-1">
                            Posisi Meja: <b class="text-dark"><?= html_escape($w['stage']) ?></b>
                          </small>
                        <?php endif ?>
                      </td>
                      <td class="ta-ci text-center" style="vertical-align: middle;">
                        <span class="db-badge-late d-inline-flex align-items-center" style="gap:4px; white-space: nowrap;">
                          <?php echo svgico('warning', 12) ?> +<?= (int) $w['stage_hk'] ?> HK
                        </span>
                      </td>
                      <td class="ta-ci text-center" style="vertical-align: middle;">
                        <div class="d-inline-flex align-items-center gap-1 justify-content-center">
                          <button type="button" class="btn btn-xs btn-flat-green ew-nudge" data-id="<?= (int) $w['KegiatanID'] ?>" title="Kirim pengingat via WhatsApp" style="font-size:0.75rem; padding: 4px 8px; border-radius: 6px; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                            <?php echo svgico('whatsapp', 13) ?> Ingatkan
                          </button>
                          <a href="<?= base_url('manajemen_approval/' . ($is_pj ? 'list_data' : 'list_approval') . '?highlight=' . $w['KegiatanID']) ?>" class="btn btn-xs btn-default" title="Buka detail" style="padding: 4px 8px; border-radius: 6px; display:inline-flex; align-items:center;">
                            <?php echo svgico('arrow-right', 13) ?>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          <?php endif ?>
        </section>

      </div>

      <!-- ============================================================= -->
      <!-- PANDUAN BAWAH / STANDAR LAYANAN (SEIMBANG & PROPORSIONAL)    -->
      <!-- ============================================================= -->
      <?php if ($is_pj): ?>
        <div class="db-guidelines-grid mt-4">
          <!-- CARD 1 KHUSUS PJ: PANDUAN 6 TAHAP ALUR VERIFIKASI -->
          <section class="db-panel-card">
            <div class="db-sec-header">
              <div class="db-sec-title-wrap">
                <h3 class="db-sec-title text-primary">
                  <?php echo svgico('guide', 18) ?> Alur 6 Meja Verifikasi
                </h3>
                <p class="db-sec-desc">Tahapan perjalanan berkas di dalam sistem hingga disahkan PPSPM</p>
              </div>
            </div>
            <div style="font-size: 0.8rem; color: #475569; line-height: 1.55;">
              <ol style="padding-left: 18px; margin-bottom: 0; display:flex; flex-direction:column; gap:6px;">
                <li><b>Pengusulan (PJ):</b> Input data &amp; dokumen pengajuan usulan.</li>
                <li><b>Staf PPK:</b> Verifikasi kelengkapan dokumen pengusulan.</li>
                <li><b>SPM:</b> Penerbitan Surat Perintah Membayar.</li>
                <li><b>Verifikator:</b> Pengecekan keabsahan &amp; kepatuhan anggaran.</li>
                <li><b>PPK:</b> Persetujuan Pejabat Pembuat Komitmen.</li>
                <li><b>PPSPM:</b> Pengujian &amp; pengesahan SPM (Tahap Akhir Sistem).</li>
              </ol>
            </div>
          </section>

          <!-- CARD 2 KHUSUS PJ: PANDUAN PENGISIAN & KELENGKAPAN BERKAS -->
          <section class="db-panel-card">
            <div class="db-sec-header">
              <div class="db-sec-title-wrap">
                <h3 class="db-sec-title text-dark">
                  <?php echo svgico('file-alt', 18) ?> Kelengkapan Berkas
                </h3>
                <p class="db-sec-desc">Panduan dokumen digital &amp; berkas eksternal</p>
              </div>
            </div>
            <div style="font-size: 0.82rem; color: #475569; line-height: 1.5;">
              <div class="p-3 mb-2 rounded" style="background:#e8f0fe; border:none;">
                <b class="text-primary d-block mb-1">📄 1. Dokumen Digital (Dalam Sistem)</b>
                Formulir seperti <b>Kartu Kendali</b>, <b>Lembar Periksa</b>, <b>Kuitansi</b>, dan <b>SPTJB</b> dapat diisi langsung di dalam sistem ASIKKEKKU.
              </div>
              <div class="p-3 rounded" style="background:#f1f5f9; border:none;">
                <b class="text-dark d-block mb-1">📎 2. Berkas Eksternal (Luar Sistem)</b>
                Berkas yang dibuat di luar sistem seperti <b>Surat Tugas (ST)</b>, <b>SPPD</b>, dan <b>LPJ Kegiatan</b> dapat diunggah melalui kontainer berkas eksternal pada form pengajuan.
              </div>
            </div>
          </section>
        </div>
      <?php else: ?>
        <!-- CARD INFORMASI MONITORING SLA UNTUK PETUGAS & ADMIN -->
        <section class="db-panel-card mt-4">
          <div class="db-sec-header">
            <div class="db-sec-title-wrap">
              <h3 class="db-sec-title text-dark">
                <?php echo svgico('clock', 18) ?> Standar Waktu Layanan (SLA) &amp; Panduan Verifikasi
              </h3>
              <p class="db-sec-desc">Ketentuan durasi penyelesaian di tiap meja verifikasi (Maksimal 4 Hari Kerja / Tahap)</p>
            </div>
          </div>
          <div class="row" style="font-size: 0.82rem; color: #475569; line-height: 1.55;">
            <div class="col-md-6 mb-2 mb-md-0">
              <div class="p-3 rounded h-100" style="background:#e8f0fe;">
                <b class="text-primary d-block mb-1">⏱️ Target Maksimal: 4 Hari Kerja / Tahap</b>
                Setiap petugas verifikasi diharapkan memproses berkas selambat-lambatnya 4 hari kerja sejak berkas tiba di meja Anda.
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded h-100" style="background:#f1f5f9;">
                <b class="text-dark d-block mb-1">📲 Pengingat Ramah (WhatsApp)</b>
                Gunakan tombol <b>Ingatkan</b> pada berkas yang terlambat untuk mengirimkan notifikasi pengingat langsung ke penanggung jawab terkait.
              </div>
            </div>
          </div>
        </section>
      <?php endif ?>

    </div>
  </div>
</div>

