<?php
$revisiMode = (isset($kegiatan_status) && $kegiatan_status === 'Perlu Revisi');
$revisiJenis = '';
if ($revisiMode && !empty($history)) {
	$lastH = end($history);
	$revisiJenis = isset($lastH['FlowRejectType']) ? $lastH['FlowRejectType'] : '';
}
$isPJ = (isset($UserPosition) && $UserPosition === 'PJ-Kegiatan') || (isset($UserPosition) && $UserPosition === 'SuperAdmin');
$targets = isset($RevisiTargets) ? $RevisiTargets : array();
?>
<?php if (!empty($last_status)): ?>
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header bg-gray">
				<h5 class="mb-0 font-weight-bold">
					<?php echo $revisiMode ? 'Formulir Kirim Ulang (setelah Revisi)' : 'Formulir Persetujuan' ?>
				</h5>
			</div>
			<div class="card-body">

				<?php if ($revisiMode): ?>
					<div class="alert-soft-info mb-3">
						Pengajuan ini <strong>dikembalikan untuk <?php echo $revisiJenis === 'terminate' ? 'diputuskan (Hentikan Proses / Revisi)' : 'direvisi' ?></strong>
						di tahap <strong><?php echo htmlspecialchars($last_status[0]['FlowPosition']) ?></strong>.
						Perbaiki datanya lewat tombol <em>Perbaiki Data</em> di halaman <em>Daftar Pengajuan</em>, lalu
						<strong>Kirim Ulang</strong> di bawah ini agar lanjut ke tahap berikutnya secara otomatis.
					</div>
				<?php endif ?>

				<form role="form" class="ApprovalFormInfoKegiatanSubmit form-horizontal" enctype="multipart/form-data"
					action="<?= base_url() ?>manajemen_approval/ApprovalFormInfoKegiatanSubmit" method="post" autocomplete="off">

					<input type="hidden" name="KegiatanID" value="<?php echo $data['KegiatanID'] ?>">
					<input type="hidden" name="FlowCode" value="<?php echo $last_status[0]['FlowCode'] ?>">
					<input type="hidden" name="FlowUserID" value="<?php echo !empty($history) ? end($history)['FlowUserID'] : '' ?>">
					<input type="hidden" name="FlowDate" value="<?php echo date('Y-m-d') ?>">
					<input type="hidden" name="FlowResult" class="FlowResult" value="1">
					<input type="hidden" name="FlowRejectType" class="FlowRejectType" value="revisi">
					<input type="hidden" name="FlowDestCode" class="FlowDestCode" value="">
					<input type="hidden" name="FlowDestUser" class="FlowDestUser" value="">

					<?php if ($last_status[0]['FlowCode'] == 'PPKS1'): ?>
					<div class="form-group row">
						<label class="col-sm-3 col-form-label" for="af-no-kwitansi">No Kwitansi</label>
						<div class="col-sm-9"><input type="text" id="af-no-kwitansi" class="form-control" name="KegiatanNoKwitansi"></div>
					</div>
					<div class="form-group row">
						<label class="col-sm-3 col-form-label" for="af-no-sptjb">No SPTJB</label>
						<div class="col-sm-9"><input type="text" id="af-no-sptjb" class="form-control" name="KegiatanNoSPTJB"></div>
					</div>
					<?php endif ?>

					<div class="form-group row">
						<label class="col-sm-3 col-form-label" for="af-keterangan">Keterangan</label>
						<div class="col-sm-9">
							<textarea id="af-keterangan" class="form-control form-control-sm FlowKeterangan" name="FlowKeterangan" rows="2" required
								placeholder="Catatan persetujuan / alasan pengembalian / revisi"></textarea>
						</div>
					</div>

					<!-- Blok pengembalian (Revisi / Hentikan Proses) -->
					<?php if ($revisiMode || $last_status[0]['FlowReject'] == 1): ?>
					<div class="js-return-block d-none border rounded p-2 mb-2 bg-light">
						<div class="form-group row mb-2">
							<label class="col-sm-3 col-form-label" for="af-return-jenis">Jenis Pengembalian</label>
							<div class="col-sm-9">
								<select id="af-return-jenis" name="ReturnJenisPilihan" class="form-control form-control-sm js-return-jenis">
									<option value="revisi">Revisi &mdash; data kurang / salah (pilih tujuan)</option>
									<option value="terminate">Hentikan Proses &mdash; dokumen dobel (otomatis ke PJ)</option>
								</select>
							</div>
						</div>
						<div class="form-group row mb-2 js-return-role">
							<label class="col-sm-3 col-form-label" for="af-return-code">Kembalikan ke</label>
							<div class="col-sm-9">
								<select id="af-return-code" name="ReturnCodePilihan" class="form-control form-control-sm js-return-code">
									<?php foreach ($targets as $t): ?>
										<option value="<?php echo $t['code'] ?>"><?php echo htmlspecialchars($t['label'].' ('.$t['position'].')') ?></option>
									<?php endforeach ?>
								</select>
							</div>
						</div>
						<div class="form-group row mb-0 js-return-user">
							<label class="col-sm-3 col-form-label" for="af-return-dest">Petugas</label>
							<div class="col-sm-9">
								<select id="af-return-dest" name="ReturnDestPilihan" class="form-control form-control-sm js-return-dest">
									<?php foreach ($targets as $t): foreach ($t['users'] as $u): ?>
										<option data-code="<?php echo $t['code'] ?>" value="<?php echo $u['id'] ?>"><?php echo htmlspecialchars($u['name']) ?></option>
									<?php endforeach; endforeach ?>
								</select>
							</div>
						</div>
					</div>
					<?php endif ?>

					<?php if (!$revisiMode && isset($UserPosition) && $UserPosition === 'PPK'): ?>
					<div id="ppk-ttd-gate" class="border rounded p-2 mb-2 bg-light d-none">
						<div class="d-flex align-items-center justify-content-between">
							<strong class="text-sm">Tanda tangan dokumen (PPK)</strong>
							<button type="button" class="btn btn-xs btn-outline-secondary" id="ppk-ttd-recheck">Cek ulang</button>
						</div>
						<div id="ppk-ttd-body" class="text-sm mt-1">memuat&hellip;</div>
					</div>
					<script>
					(function () {
						var kid = '<?php echo (int) $data['KegiatanID'] ?>';
						var box = document.getElementById('ppk-ttd-gate');
						if (!box) return;
						var body = document.getElementById('ppk-ttd-body');
						function approveBtn() { return document.querySelector('.approval-actions .btn-approve'); }
						function check() {
							jQuery.get('<?php echo base_url() ?>dokumen/ttdKurang/' + kid).done(function (r) {
								if (!r || !r.on) { box.classList.add('d-none'); if (approveBtn()) approveBtn().disabled = false; return; }
								box.classList.remove('d-none');
								if (r.lengkap) {
									body.innerHTML = '<span class="text-success">&#10003; Semua dokumen sudah Anda tanda tangani.</span>';
									if (approveBtn()) approveBtn().disabled = false;
									return;
								}
								if (approveBtn()) approveBtn().disabled = true;
								var h = '<div class="text-danger mb-1">' + r.kurang.length + ' dokumen belum ditandatangani:</div><ul class="pl-3 mb-1">';
								r.kurang.forEach(function (k) {
									h += '<li>' + k.nama + (k.label ? ' &mdash; ' + k.label : '') +
										' <a href="' + k.url_ttd + '" target="_blank">tanda tangani</a></li>';
								});
								h += '</ul><button type="button" class="btn btn-xs btn-primary" id="ppk-ttd-all">Buka semua &amp; tanda tangani</button>';
								body.innerHTML = h;
								var all = document.getElementById('ppk-ttd-all');
								if (all) all.addEventListener('click', function () {
									r.kurang.forEach(function (k, i) { setTimeout(function () { window.open(k.url_ttd, '_blank'); }, i * 250); });
								});
							});
						}
						document.getElementById('ppk-ttd-recheck').addEventListener('click', check);
						check();
					})();
					</script>
					<?php endif ?>

					<div class="d-flex flex-wrap justify-content-end gap-2 mt-3 approval-actions">
						<?php if ($revisiMode): ?>
							<?php if ($isPJ): ?>
								<button type="button" class="btn btn-outline-danger btn-terminate-inline"
									data="<?php echo $data['KegiatanID'] ?>" aria-label="Hentikan proses pengajuan"><?php echo svgico('reject',15) ?> Hentikan Proses</button>
							<?php endif ?>
							<button type="button" class="btn btn-warning btn-return">Kembalikan lagi</button>
							<button type="button" class="btn btn-success btn-approve"><?php echo svgico('send',15) ?> Kirim Ulang</button>
						<?php else: ?>
							<?php if ($last_status[0]['FlowReject'] == 1): ?>
								<button type="button" class="btn btn-danger btn-return"><?php echo svgico('reject',15) ?> Kembalikan</button>
							<?php endif ?>
							<button type="button" class="btn btn-success btn-approve"><?php echo svgico('approve',15) ?> Setujui</button>
						<?php endif ?>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php endif ?>
