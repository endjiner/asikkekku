<?php
/**
 * Dasbor ASIKKEKKU — sambutan sadar-peran + Antrean Anda + Denyut Pencairan
 * + Perlu Perhatian. Penanda terlambat & tahap milik user dibuat halus,
 * bukan banner. (2026-09-11)
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
	$m = array('PJ-Kegiatan' => 'PJ', 'PPK-Staff' => 'Staf PPK', 'SPM' => 'SPM', 'Verifikator' => 'Verifikator', 'PPK' => 'PPK', 'PPSPM' => 'PPSPM', 'SPP' => 'SPP');
	return isset($m[$pos]) ? $m[$pos] : $pos;
};
$myStageCount = null;
foreach ($pipe as $p) {
	if ($p['position'] === $UserPosition) { $myStageCount = (int) $p['count']; break; }
}

if ($is_admin) {
	$greetLine = ($nLate > 0)
		? '<b>' . $nLate . ' pengajuan</b> sudah lewat target 4 hari kerja dan perlu didorong.'
		: 'Semua pengajuan masih berjalan dalam target 4 hari kerja.';
} else {
	$bits = array();
	if ($nQueue > 0) $bits[] = '<b>' . $nQueue . ' berkas</b> ' . ($is_pj ? 'perlu Anda lengkapi atau kirim ulang' : 'menunggu keputusan Anda');
	if ($nLate > 0)  $bits[] = '<b>' . $nLate . '</b> sudah lewat target 4 HK';
	$greetLine = $bits ? ucfirst(implode(' &middot; ', $bits)) . '.' : 'Tidak ada berkas yang menunggu tindakan Anda saat ini.';
}
?>
<div class="content-wrapper">
  <div class="content">
    <div class="container-fluid db">

      <header class="db-greet">
        <div class="db-greet-txt">
          <h1>Halo, <?= html_escape($UserFullName) ?>.</h1>
          <p><?= $greetLine ?></p>
        </div>
        <span class="db-date"><?= $tgl ?></span>
      </header>

      <div class="db-flow">

        <section class="db-card db-queue">
          <div class="db-card-h">
            <span class="db-kicker"><?= $is_pj ? 'Berkas Anda' : 'Antrean Anda' ?></span>
            <?php if ($nQueue > 0): ?><span class="db-count"><?= $nQueue ?></span><?php endif ?>
          </div>
          <?php if (empty($items)): ?>
            <div class="db-empty">
              <?php echo illus('done', 40, 'tint-green') ?>
              <p><?= $is_pj ? 'Tidak ada draf atau berkas yang dikembalikan.' : 'Bagus &mdash; tidak ada berkas yang menunggu keputusan Anda.' ?></p>
            </div>
          <?php else: ?>
            <ul class="db-list">
              <?php foreach (array_slice($items, 0, 6) as $it):
                $id = (int) $it['KegiatanID'];
                $isLate = isset($lateIds[$id]);
                $hk = isset($hkMap[$id]) ? $hkMap[$id] : 0; ?>
                <li class="<?= $isLate ? 'is-late' : '' ?>">
                  <div class="db-li-main">
                    <div class="db-li-title"><?= html_escape($it['judul']) ?></div>
                    <div class="db-li-meta">
                      <?= html_escape($it['no_surat'] ?: 'Tanpa nomor surat') ?>
                      <?php if ($isLate): ?><span class="db-dot">&middot;</span> <span class="db-late">terlambat +<?= $hk ?> HK</span>
                      <?php elseif ($hk > 0): ?><span class="db-dot">&middot;</span> tertahan <b><?= $hk ?> HK</b><?php endif ?>
                      <?php if (!empty($it['ket'])): ?><span class="db-dot">&middot;</span> <span class="db-muted"><?= html_escape($it['ket']) ?></span><?php endif ?>
                    </div>
                  </div>
                  <a href="<?= $it['url'] ?>" class="db-go"><?= html_escape($it['cta']) ?> <?php echo svgico('arrow-right', 15) ?></a>
                </li>
              <?php endforeach ?>
            </ul>
            <a class="db-more" href="<?= $queue_url ?>">Lihat semua <?= $is_pj ? 'berkas' : 'antrean' ?> <?php echo svgico('arrow-right', 14) ?></a>
          <?php endif ?>
        </section>

        <div class="db-row2">

          <section class="db-card db-pulse">
            <div class="db-card-h"><span class="db-kicker">Denyut Pencairan</span></div>
            <?php if ($pipeTotal > 0): ?>
              <p class="db-pulse-lead">
                <?php if ($myStageCount !== null && $myStageCount > 0): ?>
                  <b><?= $myStageCount ?> berkas</b> di tahap Anda, dari <?= $pipeTotal ?> yang berjalan.
                <?php else: ?>
                  Posisi <b><?= $pipeTotal ?> berkas</b> yang sedang berjalan.
                <?php endif ?>
              </p>
              <div class="db-pipe" role="img" aria-label="Sebaran berkas per tahap alur">
                <?php foreach ($pipe as $p): $c = (int) $p['count']; $mine = ($p['position'] === $UserPosition); ?>
                  <div class="db-seg db-seg-<?= (int) $p['order'] ?><?= $c === 0 ? ' is-zero' : '' ?><?= $mine ? ' is-mine' : '' ?>" style="flex:<?= max(1, $c) ?> 1 0" title="<?= html_escape($p['label']) ?>: <?= $c ?> berkas">
                    <span class="db-seg-n"><?= $c ?></span>
                    <span class="db-seg-l"><?= html_escape($shortPos($p['position'])) ?></span>
                  </div>
                <?php endforeach ?>
              </div>
            <?php else: ?>
              <p class="db-muted mb-0">Belum ada berkas yang sedang berjalan.</p>
            <?php endif ?>
            <div class="db-kpi">
              <div>
                <span class="db-kpi-n"><?= (int) $kpi['selesai_bulan_ini'] ?></span>
                <span class="db-kpi-l">selesai bulan ini</span>
              </div>
              <div>
                <span class="db-kpi-n"><?= $kpi['avg_hk'] !== null ? str_replace('.', ',', (string) $kpi['avg_hk']) : '&ndash;' ?></span>
                <span class="db-kpi-l">rata&#8209;rata HK ke KPPN</span>
              </div>
            </div>
          </section>

          <section class="db-card db-risk">
            <div class="db-card-h">
              <span class="db-kicker db-kicker-warn"><?php echo svgico('warning', 14) ?> Perlu Perhatian</span>
              <?php if ($nLate > 0): ?><span class="db-count db-count-warn"><?= $nLate ?></span><?php endif ?>
            </div>
            <?php
            $late = array_values(array_filter($ew['items'], function ($w) { return $w['level'] === 'terlambat'; }));
            if (empty($late)) $late = array_slice($ew['items'], 0, 4);
            ?>
            <?php if (empty($late)): ?>
              <div class="db-empty db-empty-sm"><p>Semua pengajuan masih dalam target 4 hari kerja.</p></div>
            <?php else: ?>
              <ul class="db-list db-list-sm">
                <?php foreach (array_slice($late, 0, 4) as $w): ?>
                  <li>
                    <div class="db-li-main">
                      <div class="db-li-title"><?= html_escape($w['judul']) ?></div>
                      <div class="db-li-meta">
                        <?php if (!empty($w['stage'])): ?>di <?= html_escape($w['stage']) ?> <span class="db-dot">&middot;</span> <?php endif ?>
                        <span class="db-late">+<?= (int) $w['stage_hk'] ?> HK dari target</span>
                      </div>
                    </div>
                    <div class="db-risk-cta">
                      <?php if (!empty($ew['can_nudge'])): ?>
                        <button type="button" class="btn btn-sm btn-flat-green ew-nudge" data-id="<?= (int) $w['KegiatanID'] ?>"><?php echo svgico('whatsapp', 13) ?> Ingatkan</button>
                      <?php endif ?>
                      <a href="<?= $queue_url ?>" class="db-go db-go-plain" aria-label="Buka"><?php echo svgico('arrow-right', 15) ?></a>
                    </div>
                  </li>
                <?php endforeach ?>
              </ul>
              <?php if (count($late) > 4): ?><a class="db-more" href="<?= $queue_url ?>">Lihat semua (<?= count($late) ?>) <?php echo svgico('arrow-right', 14) ?></a><?php endif ?>
            <?php endif ?>
          </section>

        </div>
      </div>

      <?php if (!empty($quick_actions)): ?>
        <div class="db-quick">
          <span class="db-quick-l">Aksi cepat</span>
          <?php foreach ($quick_actions as $qa): ?>
            <a href="<?= $qa['url'] ?>" class="db-quick-btn"><?php echo svgico($qa['icon'], 16) ?> <?= html_escape($qa['label']) ?></a>
          <?php endforeach ?>
        </div>
      <?php endif ?>

    </div>
  </div>
</div>
