<?php
/**
 * Dasbor ASIKKEKKU — Layout Terstruktur Berdasarkan Skala Prioritas & Sadar-Peran
 * - PJ-Kegiatan: Pengusul berkas (tanpa target SLA verifikasi), fokus memantau posisi berkas & membuat pengajuan baru.
 * - Petugas Alur / Verifikator / PPK / PPSPM / Admin: Fokus antrean verifikasi tindakan & monitoring SLA 4 HK.
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

$pj = isset($pj_summary) ? $pj_summary : array('draft' => 0, 'on_progress' => 0, 'complete' => 0, 'total' => 0);

$lateIds = array();
$hkMap   = array();
foreach ($ew['items'] as $w) {
	$id = (int) $w['KegiatanID'];
	$hkMap[$id] = (int) $w['stage_hk'];
	if ($w['level'] === 'terlambat') $lateIds[$id] = true;
}
usort($items, function ($a, $b) use ($lateIds, $hkMap) {
	$la = isset($lateIds[(int) $a['KegiatanID']]) ? 1 : 0;
	$lb = isset($lateIds[(int) $b['KegiatanID']]) ? 1 : 0;
	if ($la !== $lb) return $lb - $la;
	$ha = isset($hkMap[(int) $a['KegiatanID']]) ? $hkMap[(int) $a['KegiatanID']] : 0;
	$hb = isset($hkMap[(int) $b['KegiatanID']]) ? $hkMap[(int) $b['KegiatanID']] : 0;
	return $hb - $ha;
});

$pipe = isset($pipeline) ? $pipeline : array();
$pipeTotal = 0;
foreach ($pipe as $p) $pipeTotal += (int) $p['count'];
$kpi = isset($kpi_bulan) ? $kpi_bulan : array('selesai_bulan_ini' => 0, 'avg_hk' => null);
$shortPos = function ($pos) {
	$m = array('PJ-Kegiatan' => 'Pengusulan (PJ)', 'PPK-Staff' => 'Staf PPK', 'SPM' => 'SPM', 'Verifikator' => 'Verifikator', 'PPK' => 'PPK', 'PPSPM' => 'PPSPM', 'SPP' => 'SPP (KPPN)');
	return isset($m[$pos]) ? $m[$pos] : $pos;
};

$myStageCount = null;
foreach ($pipe as $p) {
	if ($p['position'] === $UserPosition) { $myStageCount = (int) $p['count']; break; }
}

$lateItems = array_values(array_filter($ew['items'], function ($w) { return $w['level'] === 'terlambat'; }));
if (empty($lateItems)) $lateItems = array_slice($ew['items'], 0, 5);
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
              <span class="badge-soft-info"><?php echo svgico('user', 13) ?> Pengusul Kegiatan</span>
              <span>Pantau posisi dan status berkas pengajuan pencairan Anda pada setiap meja verifikasi.</span>
            <?php elseif ($nLate > 0): ?>
              <span class="badge-soft-danger"><?php echo svgico('warning', 13) ?> <?= $nLate ?> Pengajuan Lewat SLA (4 HK)</span>
              <span>Memerlukan perhatian dan percepatan tindak lanjut.</span>
            <?php elseif ($nQueue > 0): ?>
              <span class="badge-soft-info"><?php echo svgico('file', 13) ?> <?= $nQueue ?> Berkas Menunggu</span>
              <span>Terdapat berkas dalam antrean tindakan Anda.</span>
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
          <!-- KPI 1 KHUSUS PJ: Draf / Perlu Revisi -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card <?= $pj['draft'] > 0 ? 'db-kpi-warning' : 'db-kpi-primary' ?>" title="Draf kegiatan yang belum dikirim atau perlu perbaikan">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $pj['draft'] ?></h2>
                <div class="db-kpi-lbl">Draf / Perlu Revisi</div>
              </div>
              <div class="db-kpi-icon">
                <?php echo svgico('file', 22) ?>
              </div>
            </div>
            <div class="db-kpi-sub">
              <?= $pj['draft'] > 0 ? 'Perlu Anda lengkapi atau kirim ulang' : 'Tidak ada draf tertunda' ?>
            </div>
          </a>

          <!-- KPI 2 KHUSUS PJ: Sedang Diproses -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card db-kpi-primary" title="Pengajuan Anda yang sedang dalam alur verifikasi">
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
              Sedang diproses di meja verifikasi
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
              Penerbitan SPP / SP2D telah selesai
            </div>
          </a>

          <!-- KPI 4 KHUSUS PJ: Total Pengajuan Saya -->
          <a href="<?= base_url('manajemen_approval/list_data') ?>" class="db-kpi-card db-kpi-info" title="Total seluruh berkas yang Anda ajukan">
            <div class="db-kpi-top">
              <div>
                <h2 class="db-kpi-val"><?= (int) $pj['total'] ?></h2>
                <div class="db-kpi-lbl">Total Pengajuan Saya</div>
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
              Menunggu keputusan / tindakan Anda
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
              Tersebar di 7 tahapan alur verifikasi
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
              Rata-rata <b><?= $kpi['avg_hk'] !== null ? str_replace('.', ',', (string) $kpi['avg_hk']) : '-' ?></b> HK ke KPPN
            </div>
          </div>
        <?php endif ?>
      </div>

      <!-- 3. PIPELINE TRACKER: ALUR 7 TAHAPAN PENCAIRAN -->
      <section class="db-pipeline-card">
        <div class="db-sec-header">
          <div class="db-sec-title-wrap">
            <h3 class="db-sec-title"><?php echo svgico('guide', 18) ?> Denyut Alur Pencairan (7 Meja Verifikasi)</h3>
            <p class="db-sec-desc">
              <?= $is_pj ? 'Pantauan sebaran posisi berkas aktif saat ini dari pengusulan awal hingga penerbitan SPP ke KPPN' : 'Sebaran posisi berkas aktif saat ini pada setiap meja verifikasi penanggung jawab' ?>
            </p>
          </div>
          <?php if (! $is_pj && $myStageCount !== null && $myStageCount > 0): ?>
            <span class="badge-soft-info"><b><?= $myStageCount ?> berkas</b> berada di tahap Anda</span>
          <?php endif ?>
        </div>

        <div class="db-pipe-steps">
          <?php foreach ($pipe as $p):
            $c = (int) $p['count'];
            $mine = (! $is_pj && $p['position'] === $UserPosition);
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

      <!-- 4. WORKSPACE OPERASIONAL 2 KOLOM -->
      <div class="db-workspace">

        <!-- KOLOM UTAMA: DAFTAR BERKAS (FOKUS EKSEKUSI & PANTAUAN) -->
        <section class="db-panel-card">
          <div class="db-sec-header">
            <div class="db-sec-title-wrap">
              <h3 class="db-sec-title">
                <?php echo svgico('file', 18) ?>
                <?= $is_pj ? 'Daftar Berkas Pengajuan Anda' : 'Antrean Tindakan Anda' ?>
                <?php if ($nQueue > 0): ?>
                  <span class="db-count"><?= $nQueue ?></span>
                <?php endif ?>
              </h3>
              <p class="db-sec-desc">
                <?= $is_pj ? 'Berkas kegiatan yang Anda buat dan posisi proses saat ini' : 'Diprioritaskan berdasarkan status keterlambatan & batas SLA' ?>
              </p>
            </div>
            <?php if (!empty($items)): ?>
              <a href="<?= $queue_url ?>" class="db-more">Lihat Semua <?php echo svgico('arrow-right', 13) ?></a>
            <?php endif ?>
          </div>

          <?php if (empty($items)): ?>
            <div class="db-empty">
              <?php echo illus('done', 44, 'tint-green') ?>
              <h5 class="font-weight-bold mt-3 mb-1">Semua Beres!</h5>
              <p>
                <?php if ($is_pj): ?>
                  Tidak ada draf yang tertunda. Klik tombol <b>"Buat Pengajuan Baru"</b> di atas untuk mengajukan kegiatan baru.
                <?php else: ?>
                  Tidak ada berkas yang menunggu tindakan atau persetujuan Anda saat ini.
                <?php endif ?>
              </p>
              <?php if ($is_pj): ?>
                <div class="mt-3">
                  <a href="<?= base_url('manajemen_approval/list_data?new=1') ?>" class="btn btn-primary btn-sm font-weight-bold">
                    <?php echo svgico('add', 14) ?> Buat Pengajuan Baru
                  </a>
                </div>
              <?php endif ?>
            </div>
          <?php else: ?>
            <ul class="db-q-list">
              <?php foreach (array_slice($items, 0, 7) as $it):
                $id = (int) $it['KegiatanID'];
                $isLate = isset($lateIds[$id]);
                $hk = isset($hkMap[$id]) ? $hkMap[$id] : 0;
              ?>
                <li class="db-q-item <?= $isLate ? 'is-late' : '' ?>">
                  <div class="db-q-main">
                    <div class="db-q-title"><?= html_escape($it['judul']) ?></div>
                    <div class="db-q-meta">
                      <span><?= html_escape($it['no_surat'] ?: 'Tanpa nomor surat') ?></span>
                      <?php if (! $is_pj && $isLate): ?>
                        <span class="db-badge-late"><?php echo svgico('warning', 12) ?> Terlambat +<?= $hk ?> HK</span>
                      <?php elseif (! $is_pj && $hk > 0): ?>
                        <span class="db-badge-holding">Tertahan <?= $hk ?> HK</span>
                      <?php endif ?>
                      <?php if (!empty($it['ket'])): ?>
                        <span class="text-muted">&bull; <?= html_escape($it['ket']) ?></span>
                      <?php endif ?>
                    </div>
                  </div>
                  <a href="<?= $it['url'] ?>" class="db-btn-process">
                    <?= html_escape($it['cta'] ?: ($is_pj ? 'Buka Berkas' : 'Tinjau')) ?> <?php echo svgico('arrow-right', 14) ?>
                  </a>
                </li>
              <?php endforeach ?>
            </ul>
            <?php if (count($items) > 7): ?>
              <div class="text-center mt-3">
                <a href="<?= $queue_url ?>" class="btn btn-sm btn-default px-4 font-weight-bold">
                  Buka Semua <?= count($items) ?> Berkas <?php echo svgico('arrow-right', 13) ?>
                </a>
              </div>
            <?php endif ?>
          <?php endif ?>
        </section>

        <!-- KOLOM KANAN: MONITORING / PANDUAN DOKUMEN & AKSI CEPAT -->
        <div class="d-flex flex-column" style="gap: 20px;">

          <?php if ($is_pj): ?>
            <!-- CARD 1 KHUSUS PJ: PANDUAN PENGISIAN & KELENGKAPAN BERKAS -->
            <section class="db-panel-card">
              <div class="db-sec-header">
                <div class="db-sec-title-wrap">
                  <h3 class="db-sec-title text-primary">
                    <?php echo svgico('guide', 18) ?> Kelengkapan Berkas
                  </h3>
                  <p class="db-sec-desc">Panduan dokumen digital &amp; berkas eksternal</p>
                </div>
              </div>
              <div style="font-size: 0.82rem; color: #475569; line-height: 1.5;">
                <div class="p-3 mb-2 rounded" style="background:#f0f9ff; border:1px solid #bae6fd;">
                  <b class="text-primary d-block mb-1">📄 1. Dokumen Digital (Dalam Sistem)</b>
                  Formulir seperti <b>Kartu Kendali</b>, <b>Lembar Periksa</b>, <b>Kuitansi</b>, dan <b>SPTJB</b> dapat diisi langsung di dalam sistem ASIKKEKKU.
                </div>
                <div class="p-3 rounded" style="background:#f8fafc; border:1px solid #e2e8f0;">
                  <b class="text-dark d-block mb-1">📎 2. Berkas Eksternal (Luar Sistem)</b>
                  Berkas yang dibuat di luar sistem seperti <b>Surat Tugas (ST)</b>, <b>SPPD</b>, dan <b>LPJ Kegiatan</b> dapat diunggah melalui kontainer khusus berkas eksternal pada form pengajuan.
                </div>
              </div>
            </section>

          <?php else: ?>
            <!-- CARD 1 PETUGAS / ADMIN: PERLU PERHATIAN (SLA > 4 HK) -->
            <section class="db-panel-card">
              <div class="db-sec-header">
                <div class="db-sec-title-wrap">
                  <h3 class="db-sec-title text-danger">
                    <?php echo svgico('warning', 18) ?> Perlu Perhatian (SLA)
                    <?php if ($nLate > 0): ?>
                      <span class="db-count db-count-warn"><?= $nLate ?></span>
                    <?php endif ?>
                  </h3>
                  <p class="db-sec-desc">Pengajuan yang tertahan lebih dari 4 hari kerja</p>
                </div>
              </div>

              <?php if (empty($lateItems)): ?>
                <div class="db-empty db-empty-sm">
                  <p class="text-muted mb-0">Semua pengajuan masih berjalan dalam batas SLA 4 hari kerja.</p>
                </div>
              <?php else: ?>
                <ul class="db-warn-list">
                  <?php foreach (array_slice($lateItems, 0, 4) as $w): ?>
                    <li class="db-warn-item">
                      <div class="db-warn-main">
                        <div class="db-warn-title"><?= html_escape($w['judul']) ?></div>
                        <div class="db-warn-meta">
                          <?php if (!empty($w['stage'])): ?>
                            <span class="font-weight-bold text-dark">di <?= html_escape($w['stage']) ?></span> &bull;
                          <?php endif ?>
                          <span class="text-danger font-weight-bold">+<?= (int) $w['stage_hk'] ?> HK dari target</span>
                        </div>
                      </div>
                      <div class="db-warn-actions">
                        <?php if (!empty($ew['can_nudge'])): ?>
                          <button type="button" class="btn btn-xs btn-flat-green ew-nudge" data-id="<?= (int) $w['KegiatanID'] ?>" title="Kirim pengingat via WhatsApp">
                            <?php echo svgico('whatsapp', 13) ?> Ingatkan
                          </button>
                        <?php endif ?>
                        <a href="<?= $queue_url ?>" class="btn btn-xs btn-default" title="Lihat">
                          <?php echo svgico('arrow-right', 13) ?>
                        </a>
                      </div>
                    </li>
                  <?php endforeach ?>
                </ul>
                <?php if (count($lateItems) > 4): ?>
                  <div class="mt-2 text-right">
                    <a href="<?= $queue_url ?>" class="db-more">Lihat semua (<?= count($lateItems) ?>) <?php echo svgico('arrow-right', 13) ?></a>
                  </div>
                <?php endif ?>
              <?php endif ?>
            </section>
          <?php endif ?>

          <!-- CARD 2: AKSI CEPAT / SHORTCUTS -->
          <?php if (!empty($quick_actions)): ?>
            <section class="db-panel-card">
              <div class="db-sec-header">
                <div class="db-sec-title-wrap">
                  <h3 class="db-sec-title"><?php echo svgico('star', 17) ?> Aksi Cepat</h3>
                  <p class="db-sec-desc">Pintasan menu dan fungsi harian</p>
                </div>
              </div>
              <div class="db-quick-grid">
                <?php foreach ($quick_actions as $qa): ?>
                  <a href="<?= $qa['url'] ?>" class="db-quick-item">
                    <?php echo svgico($qa['icon'], 18) ?>
                    <span><?= html_escape($qa['label']) ?></span>
                  </a>
                <?php endforeach ?>
              </div>
            </section>
          <?php endif ?>

        </div>

      </div>

    </div>
  </div>
</div>
