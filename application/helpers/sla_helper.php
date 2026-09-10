<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * sla_helper — Peringatan Dini "4 Hari Kerja" (4HK) menuju pencairan.
 *
 * Jam mulai dihitung dari pengajuan pertama kali dikirim (baris pertama
 * tb_approval_history), berakhir saat KegiatanStatus = 'Approval Selesai'.
 * Hari kerja = Senin–Jumat (akhir pekan dilewati, hari libur nasional
 * TIDAK diperhitungkan — sesuai keputusan pengguna).
 *
 * Ambang batas disimpan di tb_vrbl dan bisa diubah admin lewat menu
 * Konfigurasi Aplikasi:
 *   - sla_total_hk       : target total hari kerja sampai cair (default 4)
 *   - sla_warn_total_hk  : total berjalan >= nilai ini → "mepet" (default 3)
 *   - sla_warn_stage_hk  : satu tahap tertahan > nilai ini → "mepet" (default 1)
 */

if (!function_exists('sla_config')) {
	/** Ambil ambang SLA dari tb_vrbl (dengan default), di-cache per request. */
	function sla_config()
	{
		static $cfg = null;
		if ($cfg !== null) return $cfg;

		$cfg = array(
			'sla_total_hk'      => 4,
			'sla_warn_total_hk' => 3,
			'sla_warn_stage_hk' => 1,
		);
		try {
			$ci = &get_instance();
			if (isset($ci->db)) {
				$ci->db->where_in('VrblName', array_keys($cfg));
				foreach ($ci->db->get('tb_vrbl')->result_array() as $r) {
					$v = (int) $r['VrblValue'];
					if ($v > 0) $cfg[$r['VrblName']] = $v;
				}
			}
		} catch (Exception $e) {
			// biarkan default
		}
		return $cfg;
	}
}

if (!function_exists('sla_hari_kerja')) {
	/**
	 * Jumlah hari kerja (Sen–Jum) pada rentang ($dari, $sampai].
	 * Hari yang sama = 0. Menerima string tanggal/datetime atau DateTime.
	 */
	function sla_hari_kerja($dari, $sampai)
	{
		if (empty($dari) || empty($sampai)) return 0;
		try {
			$a = ($dari instanceof DateTime) ? clone $dari : new DateTime($dari);
			$b = ($sampai instanceof DateTime) ? clone $sampai : new DateTime($sampai);
		} catch (Exception $e) {
			return 0;
		}
		$a->setTime(0, 0, 0);
		$b->setTime(0, 0, 0);
		if ($b <= $a) return 0;

		$n = 0;
		$cur = clone $a;
		$cur->modify('+1 day');
		$guard = 0;
		while ($cur <= $b && $guard < 3650) {
			$w = (int) $cur->format('N'); // 1 (Sen) .. 7 (Min)
			if ($w <= 5) $n++;
			$cur->modify('+1 day');
			$guard++;
		}
		return $n;
	}
}

if (!function_exists('sla_deadline_tanggal')) {
	/** Tanggal kalender N hari kerja setelah $mulai (default N = sla_total_hk). */
	function sla_deadline_tanggal($mulai, $n = null)
	{
		if (empty($mulai)) return null;
		$cfg = sla_config();
		$n = ($n === null) ? $cfg['sla_total_hk'] : (int) $n;
		try {
			$d = ($mulai instanceof DateTime) ? clone $mulai : new DateTime($mulai);
		} catch (Exception $e) {
			return null;
		}
		$d->setTime(0, 0, 0);
		$sisa = max(0, $n);
		$guard = 0;
		while ($sisa > 0 && $guard < 3650) {
			$d->modify('+1 day');
			if ((int) $d->format('N') <= 5) $sisa--;
			$guard++;
		}
		return $d->format('Y-m-d');
	}
}

if (!function_exists('sla_evaluasi')) {
	/**
	 * Evaluasi SLA satu kegiatan.
	 *
	 * @param array $a  {
	 *   status            : KegiatanStatus ('Approval OnProgress' | 'Approval Selesai' | 'editable' | ...)
	 *   mulai             : datetime pengajuan pertama dikirim (MIN FlowDate) atau null
	 *   selesai_pada      : datetime saat status jadi 'Approval Selesai' (opsional; default = FlowDate terakhir)
	 *   stage_since       : datetime langkah terakhir (FlowDate terakhir) atau null
	 *   last_result       : FlowResult baris history terakhir (1 = disetujui, 0 = dikembalikan)
	 *   pending_position  : nama posisi tahap yang sedang ditunggu
	 * }
	 * @return array eval lengkap; kunci penting: level, elapsed_hk, sisa_hk,
	 *               stage_elapsed_hk, deadline, stage_stuck (bool)
	 */
	function sla_evaluasi($a)
	{
		$cfg = sla_config();
		$now = new DateTime();

		$status      = isset($a['status']) ? (string) $a['status'] : '';
		$mulai       = !empty($a['mulai']) ? $a['mulai'] : null;
		$stage_since = !empty($a['stage_since']) ? $a['stage_since'] : null;
		$last_result = isset($a['last_result']) ? (int) $a['last_result'] : 1;
		$pending_pos = isset($a['pending_position']) ? (string) $a['pending_position'] : '';

		$out = array(
			'level'            => 'draf',
			'level_text'       => 'Belum diajukan',
			'badge_class'      => 'badge-soft-info',
			'mulai'            => $mulai,
			'deadline'         => null,
			'elapsed_hk'       => 0,
			'sisa_hk'          => $cfg['sla_total_hk'],
			'stage_position'   => $pending_pos,
			'stage_since'      => $stage_since,
			'stage_elapsed_hk' => 0,
			'stage_stuck'      => false,
			'is_done'          => false,
			'is_returned'      => false,
			'total_hk'         => $cfg['sla_total_hk'],
			'warn_stage_hk'    => $cfg['sla_warn_stage_hk'],
		);

		if ($mulai === null || $status === 'editable' || $status === '') {
			return $out; // masih draf / di luar cakupan SLA
		}

		$out['deadline'] = sla_deadline_tanggal($mulai);

		// ---- Sudah selesai (cair) ------------------------------------
		if ($status === 'Approval Selesai') {
			$selesai = !empty($a['selesai_pada']) ? $a['selesai_pada'] : ($stage_since ?: $now);
			$hk = sla_hari_kerja($mulai, $selesai);
			$out['elapsed_hk']  = $hk;
			$out['sisa_hk']     = $cfg['sla_total_hk'] - $hk;
			$out['is_done']     = true;
			if ($hk <= $cfg['sla_total_hk']) {
				$out['level'] = 'selesai_ontime';
				$out['level_text'] = 'Cair tepat waktu (' . $hk . ' HK)';
				$out['badge_class'] = 'badge-soft-success';
			} else {
				$out['level'] = 'selesai_telat';
				$out['level_text'] = 'Cair terlambat (' . $hk . ' HK)';
				$out['badge_class'] = 'badge-soft-warning';
			}
			return $out;
		}

		// ---- Masih berjalan ----------------------------------------
		$elapsed = sla_hari_kerja($mulai, $now);
		$stageHk = $stage_since ? sla_hari_kerja($stage_since, $now) : 0;
		$out['elapsed_hk']       = $elapsed;
		$out['sisa_hk']          = $cfg['sla_total_hk'] - $elapsed;
		$out['stage_elapsed_hk'] = $stageHk;
		$out['stage_stuck']      = $stageHk > $cfg['sla_warn_stage_hk'];
		$out['is_returned']      = ($last_result === 0);

		if ($last_result === 0) {
			$out['level'] = 'dikembalikan';
			$out['level_text'] = 'Dikembalikan untuk diperbaiki';
			$out['badge_class'] = 'badge-soft-warning';
		} elseif ($elapsed > $cfg['sla_total_hk']) {
			$out['level'] = 'terlambat';
			$lewat = $elapsed - $cfg['sla_total_hk'];
			$out['level_text'] = 'Terlambat, lewat ' . $lewat . ' HK dari target';
			$out['badge_class'] = 'badge-soft-danger';
		} elseif ($elapsed >= $cfg['sla_warn_total_hk'] || $out['stage_stuck']) {
			$out['level'] = 'mepet';
			$out['level_text'] = $out['stage_stuck']
				? ('Tahap ' . ($pending_pos ?: 'berjalan') . ' tertahan ' . $stageHk . ' HK')
				: ('Segera, sisa ' . max(0, $out['sisa_hk']) . ' HK');
			$out['badge_class'] = 'badge-soft-warning';
		} else {
			$out['level'] = 'aman';
			$out['level_text'] = 'On-track (berjalan ' . $elapsed . ' HK)';
			$out['badge_class'] = 'badge-soft-info';
		}
		return $out;
	}
}

if (!function_exists('sla_badge')) {
	/**
	 * Badge HTML ringkas untuk sel Status di tabel / kartu dashboard.
	 * Mengembalikan '' bila kegiatan di luar cakupan SLA (draf).
	 */
	function sla_badge($eval, $with_stage = true)
	{
		if (empty($eval) || $eval['level'] === 'draf') return '';

		$icon = 'clock';
		if ($eval['level'] === 'terlambat' || $eval['level'] === 'selesai_telat') $icon = 'warning';
		if ($eval['level'] === 'selesai_ontime') $icon = 'check-double';

		$txt = '';
		switch ($eval['level']) {
			case 'aman':          $txt = 'On-track'; break;
			case 'mepet':         $txt = ($eval['stage_stuck'] && $with_stage)
										? ('Tahap tertahan ' . $eval['stage_elapsed_hk'] . ' HK')
										: ('Sisa ' . max(0, $eval['sisa_hk']) . ' HK'); break;
			case 'terlambat':     $txt = 'Lewat ' . max(1, $eval['elapsed_hk'] - $eval['total_hk']) . ' HK'; break;
			case 'dikembalikan':  $txt = 'Dikembalikan'; break;
			case 'selesai_ontime':$txt = 'Cair ' . $eval['elapsed_hk'] . ' HK'; break;
			case 'selesai_telat': $txt = 'Cair telat ' . $eval['elapsed_hk'] . ' HK'; break;
			default:              $txt = $eval['level_text'];
		}
		return '<span class="' . $eval['badge_class'] . ' ew-badge" title="' .
			htmlspecialchars($eval['level_text'], ENT_QUOTES) . '">' .
			svgico($icon, 12) . ' ' . htmlspecialchars($txt, ENT_QUOTES) . '</span>';
	}
}

if (!function_exists('sla_teks_publik')) {
	/** Kalimat status ramah publik (tanpa istilah alarm internal). */
	function sla_teks_publik($eval)
	{
		if (empty($eval) || $eval['level'] === 'draf') return 'Berkas sedang disusun oleh petugas.';
		switch ($eval['level']) {
			case 'selesai_ontime':
			case 'selesai_telat':
				return 'Selesai diproses dalam ' . $eval['elapsed_hk'] . ' hari kerja.';
			case 'dikembalikan':
				return 'Sedang diperbaiki oleh petugas, hari kerja ke-' . $eval['elapsed_hk'] . '.';
			case 'terlambat':
				return 'Masih diproses (melebihi estimasi), sedang dikawal petugas. Hari kerja ke-' . $eval['elapsed_hk'] . '.';
			default:
				return 'Sedang diproses, hari kerja ke-' . $eval['elapsed_hk'] . ' dari target ' . $eval['total_hk'] . '.';
		}
	}
}
