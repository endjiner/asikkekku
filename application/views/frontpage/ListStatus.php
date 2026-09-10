<?php $sla = isset($sla) ? $sla : null; ?>
<?php if ($sla && $sla['level'] !== 'draf'):
	$tone = 'info';
	if ($sla['level'] === 'terlambat') $tone = 'danger';
	elseif ($sla['level'] === 'mepet' || $sla['level'] === 'dikembalikan' || $sla['level'] === 'selesai_telat') $tone = 'warning';
	elseif ($sla['level'] === 'selesai_ontime') $tone = 'success';
?>
<div class="ew-sla-banner ew-tone-<?php echo $tone ?>">
	<span class="ew-sla-ico"><?php echo svgico($sla['is_done'] ? 'check-double' : 'clock', 18) ?></span>
	<div>
		<strong><?php echo htmlspecialchars(sla_teks_publik($sla)) ?></strong>
		<?php if (!$sla['is_done'] && !empty($sla['deadline'])): ?>
			<div class="ew-sla-sub">Estimasi selesai sekitar <strong><?php echo date('d/m/Y', strtotime($sla['deadline'])) ?></strong> (target <?php echo (int) $sla['total_hk'] ?> hari kerja).</div>
		<?php endif ?>
	</div>
</div>
<?php endif ?>

<div class="timeline text-sm">
	<div>
		<span class="tl-dot bg-blue"><?php echo svgico('file-alt', 15) ?></span>
		<div class="timeline-item">
			<div class="timeline-body">
				<strong>No. SPTJB:</strong> <?php echo $data['KegiatanNoSPTJB'] ?: '-' ?><br>
				<strong>No. Surat:</strong> <?php echo $data['KegiatanNoSuratTugas'] ?><br>
				<strong>Kegiatan:</strong> <?php echo $data['KegiatanJudul'] ?><br>
				<strong>Pelaksana / Penyedia:</strong> <?php echo $data['KegiatanNamaPelaksana'] ?>
			</div>
		</div>
	</div>
	<?php $ls_prev = null; ?>

	<?php if (empty($history)): ?>
		<div>
			<span class="tl-dot bg-gray"><?php echo svgico('clock', 15) ?></span>
			<div class="timeline-item">
				<h3 class="timeline-header">PJ-Kegiatan</h3>
				<div class="timeline-body">Proses input / penyusunan berkas.</div>
			</div>
		</div>
	<?php endif ?>

	<?php foreach ($history as $value): ?>
		<?php $ls_delta = ($ls_prev && !empty($value['FlowDate'])) ? sla_hari_kerja($ls_prev, $value['FlowDate']) : null; ?>
		<?php if ($value['FlowCode'] == 'PJK'): ?>
			<div>
				<span class="tl-dot bg-blue"><?php echo svgico('send', 15) ?></span>
				<div class="timeline-item">
					<span class="time"><?php echo svgico('clock', 12) ?> <?php echo $value['FlowDate'] ?></span>
					<h3 class="timeline-header"><?php echo $value['FlowUserName'] ?> [<?php echo $value['FlowUserPosition'] ?>]</h3>
					<div class="timeline-body"><?php echo $value['FlowKeterangan'] ?></div>
				</div>
			</div>
		<?php else: ?>
			<div>
				<?php if ($value['FlowResult'] == 1): ?>
					<span class="tl-dot bg-green"><?php echo svgico('thumbs-up', 15) ?></span>
				<?php else: ?>
					<span class="tl-dot bg-maroon"><?php echo svgico('thumbs-down', 15) ?></span>
				<?php endif ?>
				<div class="timeline-item">
					<span class="time"><?php echo svgico('clock', 12) ?> <?php echo $value['FlowDate'] ?>
						<?php if ($ls_delta !== null): ?><span class="ew-delta">+<?php echo (int) $ls_delta ?> HK</span><?php endif ?>
					</span>
					<h3 class="timeline-header">
						<?php echo $value['FlowUserName'] ?>
						<small>[<?php echo $value['FlowUserPosition'].' : '.($value['FlowResult'] == 1 ? 'Disetujui' : 'Dikembalikan') ?>]</small>
					</h3>
					<div class="timeline-body"><?php echo $value['FlowKeterangan'] ?></div>
				</div>
			</div>
		<?php endif ?>
		<?php $ls_prev = $value['FlowDate']; ?>
	<?php endforeach ?>

	<?php if (!empty($history) && !empty($last_status)): ?>
		<div>
			<span class="tl-dot bg-gray"><?php echo svgico('hourglass', 15) ?></span>
			<div class="timeline-item">
				<h3 class="timeline-header">Menunggu: <?php echo $last_status[0]['FlowPosition'] ?></h3>
				<div class="timeline-body">Pengajuan sedang menunggu tindak lanjut dari petugas berikutnya.</div>
			</div>
		</div>
	<?php endif ?>

	<?php if (!empty($data['KegiatanStatus']) && $data['KegiatanStatus'] == 'Approval Selesai'): ?>
		<div>
			<span class="tl-dot bg-green"><?php echo svgico('check-double', 15) ?></span>
			<div class="timeline-item">
				<h3 class="timeline-header">Selesai</h3>
				<div class="timeline-body">Seluruh tahap approval telah selesai.</div>
			</div>
		</div>
	<?php endif ?>
</div>
