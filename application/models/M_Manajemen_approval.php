<?php
class M_Manajemen_approval extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->model('M_Dashboard');

		$this->DB_lib = new DB_lib;
		$this->UserID = $this->session->userdata('UserID');
		$this->UserName = $this->session->userdata('UserName');
		$this->UserGroupID = $this->session->userdata('UserGroupID');
		$this->UserFullName = $this->session->userdata('UserFullName');
		$this->UserPosition = $this->session->userdata('UserPosition');
		$this->date_time_now = date('Y-m-d H:i:s');
	}

	/* ================= JENIS PENGAJUAN (alur per jenis) ================= */

	/** JenisID sebuah kegiatan (fallback 1 = perjadin, satu-satunya alur lama). */
	private function _jenisOf($KegiatanID)
	{
		$r = $this->db->select('KegiatanJenisID')
			->get_where('tb_kegiatan', array('KegiatanID' => (int) $KegiatanID))->row_array();
		return (!empty($r) && (int) $r['KegiatanJenisID'] > 0) ? (int) $r['KegiatanJenisID'] : 1;
	}

	/** Daftar jenis pengajuan aktif untuk dropdown form pembuatan pengajuan. */
	public function JenisPengajuanAktif()
	{
		if (!$this->db->table_exists('tb_jenis_pengajuan')) {
			return array(array('JenisID' => 1, 'JenisNama' => 'Perjalanan Dinas (LS)'));
		}
		return $this->db->select('JenisID, JenisNama')
			->order_by('JenisUrutan', 'ASC')->order_by('JenisNama', 'ASC')
			->get_where('tb_jenis_pengajuan', array('JenisAktif' => 1))->result_array();
	}

	/** Validasi JenisID dari input (whitelist ke tb_jenis_pengajuan aktif). */
	public function JenisIsValid($JenisID)
	{
		$JenisID = (int) $JenisID;
		if ($JenisID <= 0) return FALSE;
		if (!$this->db->table_exists('tb_jenis_pengajuan')) return ($JenisID === 1);
		return $this->db->get_where('tb_jenis_pengajuan',
			array('JenisID' => $JenisID, 'JenisAktif' => 1))->num_rows() > 0;
	}

	/** Daftar Kode Output (dari tb_vrbl 'dok_output_list', JSON). */
	public function OutputList()
	{
		$r = $this->db->get_where('tb_vrbl', array('VrblName' => 'dok_output_list'))->row_array();
		$j = !empty($r['VrblValue']) ? json_decode($r['VrblValue'], true) : null;
		return is_array($j) ? array_values(array_filter(array_map('trim', $j))) : array();
	}

	/** Pilihan pegawai (dari tb_users) untuk pemilih pelaksana di form pengajuan. */
	public function PegawaiOptions()
	{
		$has = function ($c) { return $this->db->field_exists($c, 'tb_users'); };
		$cols = 'UserID, UserName AS NIP, UserFullName AS Nama';
		foreach (array('UserGol' => 'Gol', 'UserJabatan' => 'Jabatan', 'UserRekening' => 'Rekening',
			'UserBank' => 'Bank', 'UserNPWP' => 'NPWP') as $c => $as) {
			$cols .= $has($c) ? ", $c AS $as" : ", '' AS $as";
		}
		return $this->db->select($cols, FALSE)
			->where('UserAktif', 1)->order_by('UserFullName', 'ASC')
			->get('tb_users')->result_array();
	}

	/** Simpan ulang baris pelaksana sebuah kegiatan (hapus + isi ulang). */
	private function _syncPelaksana($KegiatanID, array $rows)
	{
		if (!$this->db->table_exists('tb_kegiatan_pelaksana')) return array();
		$KegiatanID = (int) $KegiatanID;
		$this->db->delete('tb_kegiatan_pelaksana', array('KegiatanID' => $KegiatanID));
		$nama = array();
		$urut = 0;
		foreach ($rows as $r) {
			$n = trim((string) (isset($r['nama']) ? $r['nama'] : ''));
			if ($n === '') continue;
			$nama[] = $n;
			$this->db->insert('tb_kegiatan_pelaksana', array(
				'KegiatanID' => $KegiatanID,
				'UserID'     => !empty($r['userid']) ? (int) $r['userid'] : null,
				'Nama'       => $n,
				'NIP'        => isset($r['nip']) ? trim((string) $r['nip']) : null,
				'Gol'        => isset($r['gol']) ? trim((string) $r['gol']) : null,
				'Jabatan'    => isset($r['jabatan']) ? trim((string) $r['jabatan']) : null,
				'Rekening'   => isset($r['rekening']) ? trim((string) $r['rekening']) : null,
				'Bank'       => isset($r['bank']) ? trim((string) $r['bank']) : null,
				'NPWP'       => isset($r['npwp']) ? trim((string) $r['npwp']) : null,
				'Urut'       => $urut++,
			));
		}
		return $nama;
	}

	/**
	 * SELECT dasar daftar kegiatan (tanpa hiasan HTML). Dipakai sebagai
	 * sub-query oleh M_DataTable::dtTableGetList() untuk server-side paging.
	 */
	public function KegiatanGetListSql()
	{
		// Sertakan info pengembalian terakhir (siapa yang harus merevisi &
		// jenisnya) supaya tombol Revisi/Hentikan Proses bisa ditampilkan.
		return "SELECT g.KegiatanID AS row_num, g.*,
					lh.FlowDestUser  AS RevisiUserID,
					lh.FlowRejectType AS RevisiJenis
				FROM tb_kegiatan g
				LEFT JOIN tb_approval_history lh
				  ON lh.HistoryID = (
						SELECT MAX(ah2.HistoryID)
						FROM tb_approval_history ah2
						WHERE ah2.KegiatanID = g.KegiatanID
				  )
				WHERE g.KegiatanDeletedAt IS NULL";
	}

	/**
	 * Tambahkan kolom hiasan (Action, KegiatanStatusBadges + badge SLA) ke
	 * kumpulan baris kegiatan. Dipanggil HANYA untuk baris di halaman aktif
	 * (server-side), jadi tetap ringan walau data ratusan ribu.
	 */
	public function KegiatanDecorateRows($rows)
	{
		if (empty($rows)) return array();

		$ids = array();
		foreach ($rows as $r) { $ids[] = $r['KegiatanID']; }
		$slaMap = $this->SlaEvalBatch($ids);

		$result = array();
		foreach ($rows as $value) {
			$action = '';
			$action .= ('<button type="button" class="btn btn-info btn-sm mr-1 KegiatanInfo" title="Info Detail" data-tooltip="true"
						data-toggle="modal" data-target="#modal-xl" data="'.$value['KegiatanID'].'" aria-label="Lihat info detail">
							'.svgico('info',15).'
						</button>');
			$isOwnerPJ = ($this->UserPosition == 'PJ-Kegiatan' && $value['KegiatanUserID'] == $this->UserID);
			if ($value['KegiatanStatus'] == 'editable' && ($isOwnerPJ || $this->UserPosition == 'SuperAdmin')) {
					$action .= ('<button type="button" class="btn btn-success btn-sm mr-1 KegiatanSend" title="Kirim Approval" data-tooltip="true"
							data="'.$value['KegiatanID'].'" aria-label="Kirim approval">
								'.svgico('send',15).'
							</button>');
					$action .= ('<button type="button" class="btn btn-primary btn-sm mr-1 KegiatanEdit" title="Edit" data-tooltip="true"
							data-toggle="modal" data-target="#modal-l-Kegiatan" data="'.$value['KegiatanID'].'" aria-label="Edit pengajuan">
								'.svgico('edit',15).'
							</button>');
					$action .= ('<button type="button" class="btn btn-danger btn-sm mr-1 KegiatanDelete" title="Hapus" data-tooltip="true"
							data="'.$value['KegiatanID'].'" aria-label="Hapus pengajuan">
								'.svgico('trash',15).'
							</button>');
			}

			// --- Pengajuan "Perlu Revisi" yang ditujukan ke user ini -----------
			$isRevisiPetugas = ($value['KegiatanStatus'] == 'Perlu Revisi'
				&& isset($value['RevisiUserID']) && (int) $value['RevisiUserID'] === (int) $this->UserID)
				|| ($value['KegiatanStatus'] == 'Perlu Revisi' && $this->UserPosition == 'SuperAdmin');
			if ($isRevisiPetugas) {
					$action .= ('<button type="button" class="btn btn-primary btn-sm mr-1 KegiatanEdit" title="Perbaiki Data" data-tooltip="true"
							data-toggle="modal" data-target="#modal-l-Kegiatan" data="'.$value['KegiatanID'].'" aria-label="Perbaiki data pengajuan">
								'.svgico('edit',15).'
							</button>');
				if (($this->UserPosition == 'PJ-Kegiatan' && $value['KegiatanUserID'] == $this->UserID)
					|| $this->UserPosition == 'SuperAdmin') {
						$action .= ('<button type="button" class="btn btn-danger btn-sm mr-1 KegiatanTerminate" title="Hentikan Proses (batalkan)" data-tooltip="true"
								data="'.$value['KegiatanID'].'" aria-label="Hentikan proses pengajuan">
									'.svgico('reject',15).'
								</button>');
				}
				$action .= ('<button type="button" class="btn btn-success btn-sm mr-1 KegiatanRevisiKirim" title="Kirim Ulang setelah revisi" data-tooltip="true"
							data="'.$value['KegiatanID'].'" aria-label="Kirim ulang pengajuan">
								'.svgico('send',15).'
							</button>');
			}

			if (!in_array($value['KegiatanStatus'], array('editable', 'Perlu Revisi'), true) && $this->UserPosition == 'SuperAdmin') {
				$action .= ('<button type="button" class="btn btn-danger btn-sm mr-1 KegiatanDelete" title="Hapus" data-tooltip="true"
							data="'.$value['KegiatanID'].'" aria-label="Hapus pengajuan">
								'.svgico('trash',15).'
							</button>');
			}
			$value['Action'] = $action;

			// Format Status to Soft Badge Pills
			if ($value['KegiatanStatus'] == 'editable') {
				$value['KegiatanStatusBadges'] = '<span class="badge-soft-warning">'.svgico('edit',13).' Draf</span>';
			} else if ($value['KegiatanStatus'] == 'Approval OnProgress') {
				$value['KegiatanStatusBadges'] = '<span class="badge-soft-info">'.svgico('clock',13).' Dalam Proses</span>';
			} else if ($value['KegiatanStatus'] == 'Approval Selesai') {
				$value['KegiatanStatusBadges'] = '<span class="badge-soft-success">'.svgico('approval-check',13).' Selesai</span>';
			} else if ($value['KegiatanStatus'] == 'Perlu Revisi') {
				$jenis = (isset($value['RevisiJenis']) && $value['RevisiJenis'] === 'terminate') ? ' &middot; diminta Hentikan Proses' : '';
				$value['KegiatanStatusBadges'] = '<span class="badge-soft-warning">'.svgico('edit',13).' Perlu Revisi'.$jenis.'</span>';
			} else if ($value['KegiatanStatus'] == 'Dibatalkan') {
				$value['KegiatanStatusBadges'] = '<span class="badge-soft-danger">'.svgico('reject',13).' Dibatalkan</span>';
			} else {
				$value['KegiatanStatusBadges'] = '<span class="badge-soft-danger">'.svgico('warning',13).' '.$value['KegiatanStatus'].'</span>';
			}

			// Peringatan Dini 4HK: sisipkan badge SLA untuk pengajuan yang berjalan.
			if ($value['KegiatanStatus'] == 'Approval OnProgress' && isset($slaMap[$value['KegiatanID']])) {
				$ewBadge = sla_badge($slaMap[$value['KegiatanID']]);
				if ($ewBadge !== '') $value['KegiatanStatusBadges'] .= ' ' . $ewBadge;
			}

			$result[] = $value;
		}
		return $result;
	}

	/** Daftar lengkap (tanpa paging) -- dipertahankan untuk pemakai lama. */
	public function KegiatanGetList()
	{
		$rows = $this->db->query($this->KegiatanGetListSql())->result_array();
		return $this->KegiatanDecorateRows($rows);
	}

	/**
	 * Evaluasi SLA (Peringatan Dini 4HK) untuk banyak kegiatan sekaligus.
	 * Tanpa argumen: semua kegiatan berstatus 'Approval OnProgress'.
	 * Mengembalikan map [KegiatanID => hasil sla_evaluasi()], plus kunci
	 * '_row' berisi baris mentah (dipakai dashboard peringatan dini).
	 */
	public function SlaEvalBatch($ids = null)
	{
		$filter = "k.KegiatanStatus = 'Approval OnProgress'";
		if (is_array($ids) && !empty($ids)) {
			$clean = array_map('intval', $ids);
			$filter = "k.KegiatanID IN (" . implode(',', $clean) . ")";
		}
		$filter .= " AND k.KegiatanDeletedAt IS NULL";

		$sql = "
			SELECT t2.KegiatanID, t2.KegiatanStatus, t2.KegiatanUserID, t2.KegiatanJudul,
			       t2.KegiatanNoSuratTugas, t2.KegiatanStatusTerakhir,
			       t2.FlowResult, t2.FlowDestUser, t2.mulai, t2.stage_since,
			       f2.FlowPosition AS PendingPosition, f2.FlowCode AS PendingFlowCode
			FROM (
				SELECT t1.*, IF(t1.FlowResult = 1, f.FlowOrder + 1, f.FlowOrder - 1) AS NextFlowOrder
				FROM (
					SELECT k.KegiatanID, k.KegiatanStatus, k.KegiatanUserID, k.KegiatanJudul,
					       k.KegiatanNoSuratTugas, k.KegiatanStatusTerakhir,
					       COALESCE(k.KegiatanJenisID, 1) AS KegiatanJenisID,
					       h.FlowResult, h.FlowDestUser, h.FlowDate AS stage_since, hm.mulai
					FROM tb_kegiatan k
					LEFT JOIN (
						SELECT * FROM tb_approval_history
						WHERE HistoryID IN (SELECT MAX(HistoryID) FROM tb_approval_history GROUP BY KegiatanID)
					) h ON k.KegiatanID = h.KegiatanID
					LEFT JOIN (
						SELECT KegiatanID, MIN(FlowDate) AS mulai FROM tb_approval_history GROUP BY KegiatanID
					) hm ON k.KegiatanID = hm.KegiatanID
					WHERE $filter
				) t1
				LEFT JOIN tb_approval_flow f
					ON t1.KegiatanStatusTerakhir = f.FlowCode AND f.JenisID = t1.KegiatanJenisID
			) t2
			LEFT JOIN tb_approval_flow f2
				ON t2.NextFlowOrder = f2.FlowOrder AND f2.JenisID = t2.KegiatanJenisID
		";

		$map = array();
		foreach ($this->db->query($sql)->result_array() as $r) {
			if (isset($map[$r['KegiatanID']])) continue; // tahap nonaktif bisa memunculkan baris ganda
			$eval = sla_evaluasi(array(
				'status'           => $r['KegiatanStatus'],
				'mulai'            => $r['mulai'],
				'stage_since'      => $r['stage_since'],
				'last_result'      => $r['FlowResult'],
				'pending_position' => $r['PendingPosition'],
			));
			$eval['_row'] = $r;
			$map[$r['KegiatanID']] = $eval;
		}
		return $map;
	}

	/** Evaluasi SLA satu kegiatan (dipakai modal Info & cek status publik). */
	public function KegiatanSlaEval($KegiatanID)
	{
		$KegiatanID = (int) $KegiatanID;
		$k = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID))->row_array();
		if (empty($k)) return sla_evaluasi(array());

		$hist = $this->db->order_by('HistoryID', 'ASC')
			->get_where('tb_approval_history', array('KegiatanID' => $KegiatanID))->result_array();
		if (empty($hist)) return sla_evaluasi(array('status' => $k['KegiatanStatus']));

		$jenis = ((int) $k['KegiatanJenisID']) > 0 ? (int) $k['KegiatanJenisID'] : 1;
		$flow = $this->db->get_where('tb_approval_flow', array('JenisID' => $jenis))->result_array();
		$first = $hist[0];
		$last  = $hist[count($hist) - 1];

		$byCode = array();
		$byOrder = array();
		foreach ($flow as $f) {
			$byCode[$f['FlowCode']] = $f;
			$byOrder[(int) $f['FlowOrder']][] = $f;
		}
		$pendingPos = '';
		if (isset($byCode[$last['FlowCode']])) {
			$ord = (int) $byCode[$last['FlowCode']]['FlowOrder'];
			$nextOrd = ((int) $last['FlowResult'] === 1) ? $ord + 1 : $ord - 1;
			if (!empty($byOrder[$nextOrd])) $pendingPos = $byOrder[$nextOrd][0]['FlowPosition'];
		}

		return sla_evaluasi(array(
			'status'           => $k['KegiatanStatus'],
			'mulai'            => $first['FlowDate'],
			'selesai_pada'     => $last['FlowDate'],
			'stage_since'      => $last['FlowDate'],
			'last_result'      => $last['FlowResult'],
			'pending_position' => $pendingPos,
		));
	}

	/**
	 * Kirim pengingat WhatsApp untuk pengajuan yang terhambat (Peringatan Dini 4HK).
	 *
	 * @param string   $mode           'cron' (batch, anti-spam 1x/tahap/hari) atau 'manual' (tombol dashboard)
	 * @param int|null $onlyKegiatanID batasi ke satu kegiatan (untuk mode manual)
	 * @return array   ['terkirim' => int, 'dilewati' => int, 'detail' => array]
	 */
	public function EarlyWarningKirim($mode = 'cron', $onlyKegiatanID = null)
	{
		$ids = $onlyKegiatanID ? array((int) $onlyKegiatanID) : null;
		$map = $this->SlaEvalBatch($ids);

		$allowed = ($mode === 'manual')
			? array('mepet', 'terlambat', 'dikembalikan')
			: array('mepet', 'terlambat');

		$hasLog = $this->db->table_exists('tb_ew_notifikasi');
		$today  = date('Y-m-d');
		$terkirim = 0;
		$gagal    = 0;
		$dilewati = 0;
		$detail = array();

		$this->load->library('wa');

		foreach ($map as $kid => $eval) {
			if (!in_array($eval['level'], $allowed, true)) { $dilewati++; continue; }

			$row = $eval['_row'];
			$flowCode = $row['PendingFlowCode'] ? $row['PendingFlowCode'] : ($row['KegiatanStatusTerakhir'] ?: '');

			if ($mode === 'cron' && $hasLog) {
				$dup = $this->db->query(
					"SELECT id FROM tb_ew_notifikasi WHERE KegiatanID = ? AND FlowCode = ? AND DATE(TanggalKirim) = ? LIMIT 1",
					array($kid, $flowCode, $today)
				)->num_rows();
				if ($dup > 0) { $dilewati++; continue; }
			}

			// Nomor tujuan: petugas tahap berjalan + Penanggung Jawab Kegiatan
			$phone_officer = '';
			$phone_pj = '';
			if (!empty($row['FlowDestUser'])) {
				$u = $this->db->get_where('tb_users', array('UserID' => $row['FlowDestUser']))->row_array();
				if (!empty($u['UserPhone'])) $phone_officer = $u['UserPhone'];
			}
			if (!empty($row['KegiatanUserID'])) {
				$u = $this->db->get_where('tb_users', array('UserID' => $row['KegiatanUserID']))->row_array();
				if (!empty($u['UserPhone'])) $phone_pj = $u['UserPhone'];
			}
			if ($phone_officer === '' && $phone_pj === '') { $dilewati++; continue; }

			$judul    = $row['KegiatanJudul'];
			$stage    = $row['PendingPosition'] ? $row['PendingPosition'] : 'petugas berikutnya';
			$deadline = $eval['deadline'] ? date('d/m/Y', strtotime($eval['deadline'])) : '-';

			$txt_officer = 'Pengingat: pengajuan "' . $judul . '" sudah berjalan ' . $eval['elapsed_hk'] . ' hari kerja'
				. ($eval['stage_elapsed_hk'] > 0 ? ' dan tertahan ' . $eval['stage_elapsed_hk'] . ' HK di tahap ' . $stage : '')
				. '. Target cair sebelum ' . $deadline . '. Mohon segera ditindaklanjuti.';
			$txt_pj = 'Update pengajuan "' . $judul . '": masih diproses (hari kerja ke-' . $eval['elapsed_hk'] . ', tahap ' . $stage . '). '
				. ($eval['level'] === 'terlambat'
					? 'Sudah melewati target 4 hari kerja, sedang dikawal petugas.'
					: 'Mendekati batas target 4 hari kerja.');

			$items = array();
			if ($phone_officer !== '') $items[] = array('phone' => $phone_officer, 'text' => $txt_officer);
			if ($phone_pj !== '')      $items[] = array('phone' => $phone_pj,      'text' => $txt_pj);

			$r = $this->wa->send_bulk($items, 'peringatan_dini', $kid);
			$terkirim += $r['sent'];
			$gagal    += $r['failed'];

			if ($hasLog) {
				$this->db->insert('tb_ew_notifikasi', array(
					'KegiatanID'   => $kid,
					'FlowCode'     => $flowCode,
					'Level'        => $eval['level'],
					'TanggalKirim' => date('Y-m-d H:i:s'),
				));
			}
			$detail[] = array('KegiatanID' => $kid, 'level' => $eval['level'], 'nomor' => count($items));
		}

		return array('terkirim' => $terkirim, 'gagal' => $gagal, 'dilewati' => $dilewati, 'detail' => $detail);
	}
	public function KegiatanGetData($KegiatanID = null)
	{
		$filter = '';
		$bind = array();
		if (!is_null($KegiatanID)) {
			$filter .= "AND KegiatanID = ? ";
			$bind[] = $KegiatanID;
		}

		$sql = "SELECT ROW_NUMBER() OVER (PARTITION BY KegiatanID ) row_num, k.* FROM tb_kegiatan k WHERE 1=1  ".$filter;
		$query 	= $this->db->query($sql, $bind);
		$result['Kegiatan'] = $query->result_array();

		$result['Pelaksana'] = array();
		if (!is_null($KegiatanID) && $this->db->table_exists('tb_kegiatan_pelaksana')) {
			$result['Pelaksana'] = $this->db->order_by('Urut', 'ASC')->order_by('id', 'ASC')
				->get_where('tb_kegiatan_pelaksana', array('KegiatanID' => (int) $KegiatanID))->result_array();
		}
		return $result;
	}
	public function KegiatanModify()
	{
		$this->db->trans_start();

		// echo json_encode($this->input->post()); exit();
		$KegiatanID = $this->input->post('KegiatanID');
		$KegiatanJudul = $this->input->post('KegiatanJudul');
		$KegiatanKeterangan = $this->input->post('KegiatanKeterangan');
		$KegiatanNoSuratTugas = $this->input->post('KegiatanNoSuratTugas');
		$KegiatanTanggal = $this->input->post('KegiatanTanggal');
		$KegiatanDestUser = $this->input->post('KegiatanDestUser');
		$KegiatanLampiran = $this->input->post('KegiatanLampiran');
		$KegiatanLampiranPrev = $this->input->post('KegiatanLampiranPrev');
		$KegiatanPemohonTipe = $this->input->post('KegiatanPemohonTipe');
		$KegiatanPemohonPhone = preg_replace('/\D/', '', (string) $this->input->post('KegiatanPemohonPhone'));

		// --- Pelaksana (repeater) + intake dokumen ---
		$pelIn = (array) $this->input->post('pel');           // pel[i][userid|nama|nip|gol|jabatan|rekening|bank|npwp]
		$pelRows = array();
		foreach ($pelIn as $row) {
			if (!is_array($row)) continue;
			if (trim((string) (isset($row['nama']) ? $row['nama'] : '')) === '') continue;
			$pelRows[] = $row;
		}
		$namaPelaksana = implode('; ', array_map(function ($r) { return trim((string) $r['nama']); }, $pelRows));
		// Fallback: form lama masih kirim KegiatanNamaPelaksana teks
		if ($namaPelaksana === '') $namaPelaksana = (string) $this->input->post('KegiatanNamaPelaksana');

		$kodeOutput = trim((string) $this->input->post('KegiatanKodeOutput'));
		if ($kodeOutput !== '' && !in_array($kodeOutput, $this->OutputList(), true)) {
			// terima apa adanya tapi tidak divalidasi ketat (daftar bisa berubah); simpan saja
		}

		$data = array(
			// 'KegiatanID' => $KegiatanID,
			'KegiatanNoSuratTugas' => $KegiatanNoSuratTugas,
			'KegiatanJudul' => $KegiatanJudul,
			'KegiatanTanggal' => $KegiatanTanggal,
			'KegiatanNamaPelaksana' => $namaPelaksana,
			'KegiatanKodeOutput' => ($kodeOutput === '') ? null : $kodeOutput,
			'KegiatanAsalTujuan' => trim((string) $this->input->post('KegiatanAsalTujuan')) ?: null,
			'KegiatanJmlHari' => ((int) $this->input->post('KegiatanJmlHari')) ?: null,
			'KegiatanPemohonTipe' => in_array($KegiatanPemohonTipe, array('internal','eksternal'), true) ? $KegiatanPemohonTipe : 'internal',
			'KegiatanPemohonPhone' => ($KegiatanPemohonPhone === '') ? null : $KegiatanPemohonPhone,
			// 'KegiatanStatus' => $KegiatanStatus,
			'KegiatanKeterangan' => $KegiatanKeterangan,
			'KegiatanLampiran' => $KegiatanLampiranPrev,
			'KegiatanDestUser' => $KegiatanDestUser,
			// Catatan: KegiatanUserID (pemilik/PJ) HANYA diisi saat data baru
			// dibuat -- jangan ditimpa saat diedit (mis. revisi oleh SPM/Staff
			// PPK), supaya kepemilikan tetap di PJ pembuat.
		);

		$filename = isset($_FILES['KegiatanLampiran']['name']) ? $_FILES['KegiatanLampiran']['name'] : '';
		if ($filename) {
			$cleanName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($filename));
			$data['KegiatanLampiran'] = $cleanName;
		}
		if ($KegiatanID == '') {
			// Jenis pengajuan hanya ditetapkan saat pembuatan (alur sudah berjalan
			// setelah itu). Whitelist ke tb_jenis_pengajuan aktif.
			$KegiatanJenisID = (int) $this->input->post('KegiatanJenisID');
			if (!$this->JenisIsValid($KegiatanJenisID)) {
				$this->db->trans_complete();
				return array('code' => 1, 'message' => 'Jenis pengajuan tidak valid.');
			}
			$data['KegiatanJenisID'] = $KegiatanJenisID;
			$data['KegiatanStatus'] = 'editable';
			$data['KegiatanUserID'] = $this->UserID;
			$this->db->insert('tb_kegiatan', $data);
			$KegiatanID = $this->db->insert_id();
		} else {
			$this->Auth->cekMenu("3100", 'u');
			$this->db->where('KegiatanID', $KegiatanID);
			$this->db->update('tb_kegiatan', $data);
		}

		// Sinkron baris pelaksana (kalau form mengirim repeater 'pel').
		if (!empty($pelRows)) {
			$this->_syncPelaksana($KegiatanID, $pelRows);
		}

		if ($this->db->error()['code'] != 0 ) {
			return $this->db->error();
		}
		$this->db->trans_complete();
	}
	/**
	 * Hapus pengajuan. Sekarang:
	 *  - HANYA SuperAdmin yang boleh (dulu semua user grup normal bisa -> risiko
	 *    kehilangan data + riwayat pengajuan orang lain).
	 *  - SOFT DELETE: set KegiatanDeletedAt, baris & riwayat TIDAK dibuang.
	 *    Semua query daftar sudah menyaring "KegiatanDeletedAt IS NULL".
	 */
	public function KegiatanDelete()
	{
		if ($this->UserPosition !== 'SuperAdmin') {
			return array('code' => 1, 'message' => 'Hanya SuperAdmin yang dapat menghapus pengajuan.');
		}

		$this->db->trans_start();
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$this->db->where('KegiatanID', $KegiatanID)
			->update('tb_kegiatan', array('KegiatanDeletedAt' => date('Y-m-d H:i:s')));
		$this->db->trans_complete();

		if ($this->db->error()['code'] != 0) return $this->db->error();
		return null;
	}
	public function KegiatanSendApproval()
	{
		$this->db->trans_start();

		$KegiatanID = $this->input->post("KegiatanID");

		$this->db->where('KegiatanID', $KegiatanID);
		$query = $this->db->get('tb_kegiatan', 1);
		$kegiatan = $query->result_array()[0];

		$jenis = ((int) $kegiatan['KegiatanJenisID']) > 0 ? (int) $kegiatan['KegiatanJenisID'] : 1;
		$this->db->where('FlowOrder', 1);
		$this->db->where('JenisID', $jenis);
		$query = $this->db->get('tb_approval_flow', 1);
		$flow = $query->result_array()[0];

		// $this->db->set('KegiatanDestUser', 0);
		$this->db->set('KegiatanStatus', 'Approval OnProgress');
		$this->db->set('KegiatanStatusTerakhir', $flow['FlowCode']);
		$this->db->set('KegiatanKeteranganTerakhir', 'Sudah Diajukan Approval oleh PJ-Kegiatan');
		$this->db->where('KegiatanID', $KegiatanID);
		$this->db->update('tb_kegiatan');

		$data = array(
			// 'HistoryID' => $HistoryID,
			'KegiatanID' => $KegiatanID,
			'FlowCode' => $flow['FlowCode'],
			'FlowKeterangan' => 'Sudah Diajukan Approval oleh PJ-Kegiatan',
			'FlowResult' => 1,
			'FlowDate' => date('Y-m-d H:i:s'),
			'FlowUserID' => $this->UserID,
			'FlowUserName' => $this->UserFullName,
			'FlowUserPosition' => $this->UserPosition,
			'FlowDestUser' => $kegiatan['KegiatanDestUser'],
		);
		$this->db->insert('tb_approval_history', $data);

		$text_src = 'Pengajuanmu sudah diajukan dan akan segera diproses ... ';
		$text_dst = 'Halo, ada pengajuan baru yang harus segera kamu tinjau ya ... ';
		$text_pemohon = 'Pengajuan pencairan dana untuk kegiatan "'.$kegiatan['KegiatanJudul'].'" telah DIAJUKAN dan sedang dalam proses persetujuan.';
		$this->M_Dashboard->send_text(
			$this->UserID, $kegiatan['KegiatanDestUser'], $text_src, $text_dst,
			isset($kegiatan['KegiatanPemohonPhone']) ? $kegiatan['KegiatanPemohonPhone'] : '', $text_pemohon,
			'persetujuan', $KegiatanID
		);

		$this->db->trans_complete();

		// Hook: auto-update Kartu Kendali TU saat pengajuan pertama dikirim.
		$this->load->model('M_Dokumen');
		$this->M_Dokumen->kartuKendaliAutoFill((int) $KegiatanID);
	}
	public function GetFormInfoKegiatan($KegiatanID = null)
	{
		$filter = '';
		$bind = array();
		if (!is_null($KegiatanID)) {
			$filter .= "AND h.KegiatanID = ? ";
			$bind[] = $KegiatanID;
		}

		$sql = "SELECT h.*, f.FlowNote, u.UserFullName FlowDestUserName
				FROM tb_approval_history h
				LEFT JOIN tb_kegiatan k ON k.KegiatanID = h.KegiatanID
				LEFT JOIN tb_approval_flow f
					ON h.FlowCode = f.FlowCode AND f.JenisID = COALESCE(k.KegiatanJenisID, 1)
				LEFT JOIN tb_users u ON h.FlowDestUser = u.UserID
				WHERE 1=1 ".$filter." ORDER BY HistoryID";
		$query 	= $this->db->query($sql, $bind);
		$result['history'] = $query->result_array();

		return $result;
	}
	public function GetLastStatus($KegiatanID = null)
	{
		$KegiatanID = (int) $KegiatanID;

		// Pengajuan berstatus "Perlu Revisi": petugas tujuan bertindak
		// SEBAGAI tahap tujuan itu sendiri (perbaiki data lalu kirim ulang
		// maju), bukan mundur satu tahap.
		$k = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID))->row_array();
		$jenis = (!empty($k) && (int) $k['KegiatanJenisID'] > 0) ? (int) $k['KegiatanJenisID'] : 1;
		if (!empty($k) && $k['KegiatanStatus'] === 'Perlu Revisi') {
			return $this->db->get_where('tb_approval_flow',
				array('FlowCode' => $k['KegiatanStatusTerakhir'], 'JenisID' => $jenis))->result_array();
		}

		$sql = "SELECT f.FlowOrder, h.FlowResult
				FROM tb_approval_flow f
				LEFT JOIN tb_kegiatan k ON k.KegiatanStatusTerakhir = f.FlowCode AND f.JenisID = COALESCE(k.KegiatanJenisID, 1)
				LEFT JOIN (SELECT FlowResult, KegiatanID FROM tb_approval_history WHERE KegiatanID = $KegiatanID ORDER BY HistoryID DESC LIMIT 1 ) h
				ON k.KegiatanID = h.KegiatanID
				WHERE k.KegiatanID = $KegiatanID";
		$result = $this->db->query($sql)->result_array();

		if (!empty($result)) {
			$result = $result[0];
			$filter = ($result['FlowResult'] == 1) ? 'FlowOrder > '.$result['FlowOrder'].' ORDER BY FlowOrder ASC' : 'FlowOrder < '.$result['FlowOrder'].' ORDER BY FlowOrder DESC' ;
			$sql = "SELECT * FROM tb_approval_flow
					WHERE JenisID = ".(int) $jenis." AND $filter LIMIT 1";
			$query 	= $this->db->query($sql);
			$result = $query->result_array();
			return $result;
		} else {
			return array();
		}
	}

	// -------------------------------------------------
	/**
	 * SELECT dasar antrian persetujuan (tanpa hiasan HTML). Filter per posisi
	 * user dibakukan di dalam SQL memakai escape/cast yang aman -- supaya
	 * hitungan total server-side juga ikut terfilter. Dipakai sebagai
	 * sub-query oleh M_DataTable::dtTableGetList().
	 */
	public function KegiatanApprovalGetListSql()
	{
		$where = '';
		if ($this->UserPosition != 'SuperAdmin') {
			$where = "WHERE f2.FlowPosition = " . $this->db->escape((string) $this->UserPosition)
				. " AND t2.FlowDestUser = " . (int) $this->UserID . " ";
		}
		return "SELECT ROW_NUMBER() OVER (ORDER BY KegiatanID) row_num, t2.*, f2.FlowPosition
				FROM (
					SELECT t1.*, IF(t1.FlowResult=1,f.FlowOrder+1,FlowOrder-1) FlowOrder
					FROM (
						SELECT k.*, h.FlowResult, h.FlowDestUser
						FROM tb_kegiatan k
						LEFT JOIN (
							SELECT * FROM tb_approval_history
							WHERE HistoryID IN (
								SELECT MAX(HistoryID) HistoryID
								FROM tb_approval_history
								GROUP BY KegiatanID
							)
						) h ON k.KegiatanID = h.KegiatanID
						WHERE k.KegiatanStatus = 'Approval OnProgress' AND k.KegiatanDeletedAt IS NULL
					) t1
					LEFT JOIN tb_approval_flow f
						ON t1.KegiatanStatusTerakhir = f.FlowCode AND f.JenisID = COALESCE(t1.KegiatanJenisID, 1)
				) t2
				LEFT JOIN tb_approval_flow f2
					ON t2.FlowOrder = f2.FlowOrder AND f2.JenisID = COALESCE(t2.KegiatanJenisID, 1)
				$where";
	}

	/** Tambahkan tombol Action + badge SLA (untuk baris di halaman aktif). */
	public function KegiatanApprovalDecorateRows($rows)
	{
		if (empty($rows)) return array();
		$ids = array();
		foreach ($rows as $r) { $ids[] = $r['KegiatanID']; }
		$slaMap = $this->SlaEvalBatch($ids);

		$result = array();
		foreach ($rows as $value) {
			$value['Action'] =
				'<button type="button" class="btn btn-success btn-xs mr-1 KegiatanInfo"
					data-toggle="modal" data-target="#modal-xl" data="'.$value['KegiatanID'].'">
					'.svgico('check-double',14).'
				</button>';

			// Peringatan Dini 4HK: badge SLA di kolom status antrian.
			if (isset($slaMap[$value['KegiatanID']])) {
				$ewBadge = sla_badge($slaMap[$value['KegiatanID']]);
				if ($ewBadge !== '') $value['KegiatanStatus'] .= ' ' . $ewBadge;
			}

			$result[] = $value;
		}
		return $result;
	}

	/** Daftar lengkap tanpa paging (dipakai dashboard untuk hitung antrian). */
	public function KegiatanApprovalGetList()
	{
		$rows = $this->db->query($this->KegiatanApprovalGetListSql())->result_array();
		return $this->KegiatanApprovalDecorateRows($rows);
	}
	public function ApprovalFormInfoKegiatanSubmit()
	{
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$FlowResult = (int) $this->input->post('FlowResult');

		return ($FlowResult === 1)
			? $this->_approvalTeruskan($KegiatanID)
			: $this->_approvalKembalikan($KegiatanID);
	}

	/**
	 * Tentukan OTOMATIS petugas tahap berikutnya (tidak perlu dipilih manual).
	 * Prioritas: (1) user yang pernah menangani posisi itu untuk kegiatan ini,
	 * (2) tujuan pilihan PJ saat membuat (khusus lompatan pertama PJK), (3) user
	 * aktif pertama pada posisi tersebut.
	 */
	private function _nextStageUser($FlowCode, $KegiatanID)
	{
		$jenis = $this->_jenisOf($KegiatanID);
		$cur = $this->db->get_where('tb_approval_flow',
			array('FlowCode' => $FlowCode, 'JenisID' => $jenis))->row_array();
		if (empty($cur)) return 0;
		$next = $this->db->where('FlowOrder >', (int) $cur['FlowOrder'])
			->where('JenisID', $jenis)
			->order_by('FlowOrder', 'ASC')->get('tb_approval_flow', 1)->row_array();
		if (empty($next)) return 0; // sudah tahap terakhir
		$pos = $next['FlowPosition'];

		$prev = $this->db->query(
			"SELECT h.FlowUserID FROM tb_approval_history h
			 WHERE h.KegiatanID = ? AND h.FlowUserPosition = ? AND h.FlowResult = 1
			 ORDER BY h.HistoryID DESC LIMIT 1",
			array((int) $KegiatanID, $pos))->row_array();
		if (!empty($prev['FlowUserID'])) return (int) $prev['FlowUserID'];

		// Lompatan pertama (tahap #1 -> #2): pakai tujuan pilihan PJ saat membuat.
		if ((int) $cur['FlowOrder'] === 1) {
			$keg = $this->db->get_where('tb_kegiatan', array('KegiatanID' => (int) $KegiatanID))->row_array();
			if (!empty($keg['KegiatanDestUser'])) return (int) $keg['KegiatanDestUser'];
		}

		$u = $this->db->order_by('UserID', 'ASC')
			->get_where('tb_users', array('UserPosition' => $pos, 'UserAktif' => 1), 1)->row_array();
		return !empty($u) ? (int) $u['UserID'] : 0;
	}

	/* ---- SETUJUI / KIRIM ULANG (maju ke tahap berikutnya, OTOMATIS) ----- */
	private function _approvalTeruskan($KegiatanID)
	{
		$KegiatanID = (int) $KegiatanID;
		$cek = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID), 1)->row_array();
		if (empty($cek)) {
			return array('code' => 1, 'message' => 'Pengajuan tidak ditemukan.');
		}
		if (!empty($cek['KegiatanDeletedAt'])) {
			return array('code' => 1, 'message' => 'Pengajuan sudah dihapus, tidak bisa diproses.');
		}
		// Non-SuperAdmin hanya boleh memproses jika memang petugas tujuan tahap ini.
		if ($this->UserPosition !== 'SuperAdmin') {
			$last = $this->db->query(
				"SELECT FlowDestUser FROM tb_approval_history WHERE KegiatanID = ? ORDER BY HistoryID DESC LIMIT 1",
				array($KegiatanID))->row_array();
			if (!$last || (int) $last['FlowDestUser'] !== (int) $this->UserID) {
				return array('code' => 1, 'message' => 'Pengajuan ini bukan pada antrian Anda.');
			}
		}

		$this->db->trans_start();

		$jenis              = ((int) $cek['KegiatanJenisID']) > 0 ? (int) $cek['KegiatanJenisID'] : 1;
		$FlowCode           = (string) $this->input->post('FlowCode');
		$FlowKeterangan     = (string) $this->input->post('FlowKeterangan');
		$KegiatanNoKwitansi = $this->input->post('KegiatanNoKwitansi');
		$KegiatanNoSPTJB    = $this->input->post('KegiatanNoSPTJB');

		// Gate tanda tangan PPK (opsional, tb_vrbl 'dok_gate_ppk'=1): PPK tidak
		// bisa meneruskan sebelum semua dokumen slot 'ppk' ditandatangani.
		if ($this->UserPosition === 'PPK') {
			$this->load->model('M_Dokumen');
			if ($this->M_Dokumen->dokGatePpkOn()) {
				$kurang = $this->M_Dokumen->ttdKurangUntukPosisi($KegiatanID, 'PPK');
				if (!empty($kurang)) {
					$this->db->trans_complete();
					return array('code' => 1, 'message' => 'Belum bisa disetujui: ' . count($kurang)
						. ' dokumen belum Anda tanda tangani (buka Info Pengajuan → Dokumen Pencairan).');
				}
			}
		}

		$FlowDestUser       = $this->_nextStageUser($FlowCode, $KegiatanID);

		$isSelesai = $this->db->query(
			"SELECT 1 FROM tb_approval_flow
			 WHERE JenisID = ? AND FlowOrder > (SELECT FlowOrder FROM tb_approval_flow WHERE FlowCode = ? AND JenisID = ?)",
			array($jenis, $FlowCode, $jenis))->result_array();

		$this->db->insert('tb_approval_history', array(
			'KegiatanID'       => $KegiatanID,
			'FlowCode'         => $FlowCode,
			'FlowKeterangan'   => $FlowKeterangan,
			'FlowResult'       => 1,
			'FlowDate'         => date('Y-m-d H:i:s'),
			'FlowUserID'       => $this->UserID,
			'FlowUserName'     => $this->UserFullName,
			'FlowUserPosition' => $this->UserPosition,
			'FlowDestUser'     => $FlowDestUser,
		));

		$this->db->set('KegiatanStatusTerakhir', $FlowCode);
		$this->db->set('KegiatanKeteranganTerakhir', $FlowKeterangan);
		$this->db->set('KegiatanStatus', empty($isSelesai) ? 'Approval Selesai' : 'Approval OnProgress');
		if ($KegiatanNoKwitansi) $this->db->set('KegiatanNoKwitansi', $KegiatanNoKwitansi);
		if ($KegiatanNoSPTJB)    $this->db->set('KegiatanNoSPTJB', $KegiatanNoSPTJB);
		$this->db->where('KegiatanID', $KegiatanID);
		$this->db->update('tb_kegiatan');

		$keg   = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID))->row_array();
		$judul = $keg['KegiatanJudul'];
		$pemohon_phone = isset($keg['KegiatanPemohonPhone']) ? $keg['KegiatanPemohonPhone'] : '';

		if (empty($isSelesai)) {
			$text_src = 'Pengajuan "'.$judul.'" telah SELESAI diproses seluruh tahap. Dana siap dicairkan.';
			$this->M_Dashboard->send_text($keg['KegiatanUserID'], '', $text_src, '', $pemohon_phone,
				'Kabar baik! Pengajuan pencairan dana untuk kegiatan "'.$judul.'" telah SELESAI diproses.',
				'persetujuan', $KegiatanID);
		} else {
			$posisi = $this->_flowPosisi($FlowCode, $jenis);
			$text_src = 'Pengajuan "'.$judul.'" telah diteruskan oleh '.$posisi.' ke tahap berikutnya.';
			$text_dst = 'Halo, ada pengajuan "'.$judul.'" yang perlu Anda tinjau.';
			$this->M_Dashboard->send_text($keg['KegiatanUserID'], $FlowDestUser, $text_src, $text_dst, $pemohon_phone,
				'Pengajuan "'.$judul.'" telah disetujui oleh '.$posisi.' dan lanjut ke tahap berikutnya.',
				'persetujuan', $KegiatanID);
		}

		if ($this->db->error()['code'] != 0) return $this->db->error();
		$this->db->trans_complete();

		// Hook: auto-update Kartu Kendali TU saat approval maju.
		$this->load->model('M_Dokumen');
		$this->M_Dokumen->kartuKendaliAutoFill($KegiatanID);

		return null;
	}

	/* ---- KEMBALIKAN (revisi / hentikan proses) ------------------------ */
	private function _approvalKembalikan($KegiatanID)
	{
		$this->db->trans_start();

		$FlowKeterangan = trim((string) $this->input->post('FlowKeterangan'));
		$jenis          = ($this->input->post('FlowRejectType') === 'terminate') ? 'terminate' : 'revisi';
		$destCode       = (string) $this->input->post('FlowDestCode');
		$destUser       = (int) $this->input->post('FlowDestUser');

		$keg = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID))->row_array();
		if (empty($keg)) { $this->db->trans_complete(); return array('code' => 1, 'message' => 'Data kegiatan tidak ditemukan.'); }
		$jenisId = ((int) $keg['KegiatanJenisID']) > 0 ? (int) $keg['KegiatanJenisID'] : 1;

		// Hentikan Proses: keputusan hanya di PJ -> tujuan otomatis ke PJ pembuat,
		// tidak perlu memilih petugas.
		if ($jenis === 'terminate') {
			$destCode = $this->_stageSatuCode($jenisId);
			$destUser = (int) $keg['KegiatanUserID'];
		}
		$targetValid = $this->db->get_where('tb_approval_flow', array(
			'JenisID' => $jenisId, 'FlowCode' => $destCode, 'FlowIsRevisiTarget' => 1,
		))->num_rows() > 0;
		if (!$targetValid) {
			$this->db->trans_complete();
			return array('code' => 1, 'message' => 'Tahap tujuan pengembalian tidak valid.');
		}
		if ($destUser <= 0) {
			$this->db->trans_complete();
			return array('code' => 1, 'message' => 'Petugas tujuan pengembalian wajib dipilih.');
		}

		$this->db->insert('tb_approval_history', array(
			'KegiatanID'       => $KegiatanID,
			'FlowCode'         => $destCode,               // tahap tujuan revisi
			'FlowKeterangan'   => $FlowKeterangan,
			'FlowResult'       => 0,
			'FlowRejectType'   => $jenis,
			'FlowDate'         => date('Y-m-d H:i:s'),
			'FlowUserID'       => $this->UserID,           // yang mengembalikan
			'FlowUserName'     => $this->UserFullName,
			'FlowUserPosition' => $this->UserPosition,
			'FlowDestUser'     => $destUser,               // petugas yang harus merevisi
		));

		$this->db->set('KegiatanStatusTerakhir', $destCode);
		$this->db->set('KegiatanKeteranganTerakhir', $FlowKeterangan);
		$this->db->set('KegiatanStatus', 'Perlu Revisi');
		$this->db->where('KegiatanID', $KegiatanID);
		$this->db->update('tb_kegiatan');

		// --- Notifikasi WA ke SEMUA peran yang sudah dilewati + petugas tujuan ---
		$judul   = $keg['KegiatanJudul'];
		$posisi  = $this->UserPosition;
		$tujuan  = $this->_flowPosisi($destCode, $jenisId);
		$kalimat = ($jenis === 'terminate')
			? 'Pengajuan "'.$judul.'" DIKEMBALIKAN oleh '.$posisi.' dengan permintaan HENTIKAN PROSES (dokumen dobel). Menunggu keputusan PJ-Kegiatan.'
			: 'Pengajuan "'.$judul.'" DIKEMBALIKAN oleh '.$posisi.' untuk REVISI di tahap '.$tujuan.'.';
		if ($FlowKeterangan !== '') $kalimat .= ' Catatan: '.$FlowKeterangan;

		$konteks = ($jenis === 'terminate') ? 'hentikan_proses' : 'revisi';
		$this->_kirimWaBanyak($this->_teleponPihakTerkait($KegiatanID, $destUser), $kalimat, $konteks, $KegiatanID);
		$dst = $this->db->get_where('tb_users', array('UserID' => $destUser))->row_array();
		if (!empty($dst['UserPhone'])) {
			$this->_kirimWaBanyak(array($dst['UserPhone']),
				($jenis === 'terminate')
					? 'Ada pengajuan "'.$judul.'" dengan permintaan HENTIKAN PROSES. Buka menu Daftar Pengajuan untuk memutuskan Hentikan Proses atau Revisi.'
					: 'Ada pengajuan "'.$judul.'" yang perlu Anda REVISI. Buka menu Daftar Pengajuan, perbaiki datanya, lalu Kirim Ulang.',
				$konteks, $KegiatanID);
		}

		if ($this->db->error()['code'] != 0) return $this->db->error();
		$this->db->trans_complete();

		// Hook: auto-update Kartu Kendali TU saat approval dikembalikan.
		$this->load->model('M_Dokumen');
		$this->M_Dokumen->kartuKendaliAutoFill($KegiatanID);

		return null;
	}

	/* ---- HENTIKAN PROSES / BATALKAN (hanya PJ) ---------------------- */
	public function KegiatanTerminate()
	{
		$this->db->trans_start();
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$catatan    = trim((string) $this->input->post('FlowKeterangan'));
		if ($catatan === '') $catatan = 'Proses dihentikan (dokumen dobel).';

		$keg = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID))->row_array();
		if (empty($keg)) { $this->db->trans_complete(); return array('code' => 1, 'message' => 'Data tidak ditemukan.'); }
		if ($this->UserPosition !== 'PJ-Kegiatan' && $this->UserPosition !== 'SuperAdmin') {
			$this->db->trans_complete(); return array('code' => 1, 'message' => 'Hanya PJ-Kegiatan yang dapat menghentikan proses.');
		}

		// Hapus berkas lampiran dari disk (hemat penyimpanan) lalu kosongkan.
		if (!empty($keg['KegiatanLampiran'])) {
			foreach (preg_split('/\s*[,;]\s*/', (string) $keg['KegiatanLampiran'], -1, PREG_SPLIT_NO_EMPTY) as $f) {
				$path = FCPATH . 'assets/lampiran/' . basename($f);
				if (is_file($path)) @unlink($path);
			}
		}

		$this->db->where('KegiatanID', $KegiatanID)->update('tb_kegiatan', array(
			'KegiatanStatus'             => 'Dibatalkan',
			'KegiatanLampiran'           => '',
			'KegiatanKeteranganTerakhir' => $catatan,
		));

		$jenisId = ((int) $keg['KegiatanJenisID']) > 0 ? (int) $keg['KegiatanJenisID'] : 1;
		$this->db->insert('tb_approval_history', array(
			'KegiatanID'       => $KegiatanID,
			'FlowCode'         => $keg['KegiatanStatusTerakhir'] ?: $this->_stageSatuCode($jenisId),
			'FlowKeterangan'   => $catatan,
			'FlowResult'       => 0,
			'FlowRejectType'   => 'terminate',
			'FlowDate'         => date('Y-m-d H:i:s'),
			'FlowUserID'       => $this->UserID,
			'FlowUserName'     => $this->UserFullName,
			'FlowUserPosition' => $this->UserPosition,
			'FlowDestUser'     => (int) $keg['KegiatanUserID'],
		));

		$this->_kirimWaBanyak(
			$this->_teleponPihakTerkait($KegiatanID, 0),
			'Pengajuan "'.$keg['KegiatanJudul'].'" telah DIHENTIKAN / dibatalkan oleh PJ-Kegiatan. Alasan: '.$catatan,
			'hentikan_proses', $KegiatanID
		);

		if ($this->db->error()['code'] != 0) return $this->db->error();
		$this->db->trans_complete();
		return null;
	}

	/* ---- Data pendukung form pengembalian --------------------------- */
	/** Tahap-tahap tujuan revisi (FlowIsRevisiTarget=1) + daftar petugasnya. */
	public function RevisiTargets($KegiatanID)
	{
		$KegiatanID = (int) $KegiatanID;
		$keg = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID))->row_array();
		$jenisId = (!empty($keg) && (int) $keg['KegiatanJenisID'] > 0) ? (int) $keg['KegiatanJenisID'] : 1;
		$out = array();
		$targets = $this->db->order_by('FlowOrder', 'ASC')
			->get_where('tb_approval_flow', array('JenisID' => $jenisId, 'FlowIsRevisiTarget' => 1))
			->result_array();
		foreach ($targets as $flow) {
			$code = $flow['FlowCode'];
			if ((int) $flow['FlowOrder'] === 1) {
				// Tahap pertama = PJ pembuat kegiatan
				$u = !empty($keg['KegiatanUserID'])
					? $this->db->get_where('tb_users', array('UserID' => $keg['KegiatanUserID']))->result_array()
					: array();
			} else {
				$u = $this->db->order_by('UserFullName', 'ASC')
					->get_where('tb_users', array('UserPosition' => $flow['FlowPosition'], 'UserAktif' => 1))
					->result_array();
			}
			$out[] = array(
				'code'     => $code,
				'position' => $flow['FlowPosition'],
				'label'    => ucwords(strtolower($flow['FlowNote'])),
				'users'    => array_map(function ($r) {
					return array('id' => $r['UserID'], 'name' => $r['UserFullName']);
				}, $u),
			);
		}
		return $out;
	}

	/** FlowCode tahap pertama (FlowOrder = 1) sebuah jenis; fallback 'PJK'. */
	private function _stageSatuCode($jenisID)
	{
		$r = $this->db->select('FlowCode')->get_where('tb_approval_flow',
			array('JenisID' => (int) $jenisID, 'FlowOrder' => 1))->row_array();
		return !empty($r['FlowCode']) ? $r['FlowCode'] : 'PJK';
	}

	/* ---- KIRIM ULANG setelah revisi (petugas tujuan -> maju) --------- */
	public function KegiatanRevisiKirimUlang()
	{
		// Sama seperti "teruskan", tetapi hanya boleh dari status Perlu Revisi
		// oleh petugas tujuan. Pengecekan hak dilakukan di controller.
		return $this->_approvalTeruskan((int) $this->input->post('KegiatanID'));
	}

	/* ---- Helper WA -------------------------------------------------- */
	/** Nomor telepon semua user yang pernah bertindak (disetujui) pada
	 *  kegiatan ini, kecuali $exceptUserID. */
	private function _teleponPihakTerkait($KegiatanID, $exceptUserID = 0)
	{
		$rows = $this->db->query(
			"SELECT DISTINCT u.UserPhone
			 FROM tb_approval_history h
			 JOIN tb_users u ON u.UserID = h.FlowUserID
			 WHERE h.KegiatanID = ? AND u.UserID <> ? AND u.UserPhone IS NOT NULL AND u.UserPhone <> ''",
			array((int) $KegiatanID, (int) $exceptUserID))->result_array();
		return array_column($rows, 'UserPhone');
	}
	private function _kirimWaBanyak($phones, $text, $context = 'persetujuan', $kegiatanID = null)
	{
		$phones = array_values(array_unique(array_filter((array) $phones)));
		if (empty($phones) || $text === '') return array('sent' => 0, 'failed' => 0, 'total' => 0);
		$this->load->library('wa');
		return $this->wa->send_bulk(array_map(function ($p) use ($text) {
			return array('phone' => $p, 'text' => $text);
		}, $phones), $context, $kegiatanID);
	}
	private function _flowPosisi($FlowCode, $jenisID = 1)
	{
		$r = $this->db->get_where('tb_approval_flow',
			array('FlowCode' => $FlowCode, 'JenisID' => (int) $jenisID))->row_array();
		return !empty($r) ? $r['FlowPosition'] : $FlowCode;
	}

	// -------------------------------------------------
	public function ReportGetList()
	{
		// Rentang bulan: "date_start" & "date_end" berformat "YYYY-MM".
		// Kalau kosong / tidak valid -> pakai bulan berjalan. Nilai dinormalkan
		// jadi tanggal PHP (bukan tempel string) -> aman dari injeksi.
		$parseMonth = function ($v) {
			$p = explode('-', (string) $v);
			$y = isset($p[0]) ? (int) $p[0] : 0;
			$m = isset($p[1]) ? (int) $p[1] : 0;
			if ($y < 2000 || $y > 2100 || $m < 1 || $m > 12) return null;
			return sprintf('%04d-%02d', $y, $m);
		};
		$start = $parseMonth($this->input->get('date_start'));
		$end   = $parseMonth($this->input->get('date_end'));
		if ($start === null) $start = date('Y-m');
		if ($end === null)   $end   = $start;
		if ($end < $start)   $end   = $start;

		$from = $start . '-01 00:00:00';
		$to   = date('Y-m-t 23:59:59', strtotime($end . '-01'));

		// Saringan status laporan: proses | selesai | kembali (boleh gabungan).
		$allowed = array('proses', 'selesai', 'kembali');
		$status  = (array) $this->input->get('status');
		$status  = array_values(array_intersect($allowed, $status));
		if (empty($status)) $status = $allowed;

		$bind = array($from, $to);
		$statusFilter = '';
		if (count($status) < count($allowed)) {
			$statusFilter = ' WHERE report_status IN (' . implode(',', array_fill(0, count($status), '?')) . ')';
			$bind = array_merge($bind, $status);
		}

		$sql = "SELECT * FROM (
					SELECT k.*, f.FlowPosition, h.FlowDate, h.FlowResult, h.FlowUserName,
						CASE
							WHEN k.KegiatanStatus = 'Approval Selesai' THEN 'selesai'
							WHEN h.FlowResult = 0 THEN 'kembali'
							ELSE 'proses'
						END AS report_status
					FROM tb_approval_history h
					LEFT JOIN tb_kegiatan k ON k.KegiatanID = h.KegiatanID
					LEFT JOIN tb_approval_flow f ON h.FlowCode = f.FlowCode AND f.JenisID = COALESCE(k.KegiatanJenisID, 1)
					WHERE k.KegiatanDeletedAt IS NULL AND h.HistoryID IN (
						SELECT MAX(HistoryID) HistoryID FROM tb_approval_history
						WHERE FlowDate BETWEEN ? AND ?
						GROUP BY KegiatanID
					)
				) rpt" . $statusFilter . "
				ORDER BY KegiatanTanggal ASC";
		$query 	= $this->db->query($sql, $bind);
		return $query->result_array();
	}

	// -------------------------------------------------
	public function getUserDestination($FlowOrder, $JenisID = 1)
	{
		$next = (int) $FlowOrder + 1;
		$sql = "SELECT f.*, u.UserID, u.UserFullName
				FROM tb_approval_flow f
				LEFT JOIN tb_users u ON f.FlowPosition = u.UserPosition
				WHERE f.FlowOrder = ? AND f.JenisID = ? AND u.UserAktif = 1";
		$query 	= $this->db->query($sql, array($next, (int) $JenisID));
		return $query->result_array();
	}

	/**
	 * DASBOR — sebaran berkas yang sedang berjalan di tiap tahap alur
	 * (untuk visual "Denyut Pencairan"). Read-only, di-scope per jenis.
	 * Balikan: array of ['order','code','position','label','count'].
	 */
	public function PipelineByStage($jenisID = 1)
	{
		$jenisID = (int) $jenisID ?: 1;

		$flow = $this->db->query(
			"SELECT FlowOrder, FlowCode, FlowPosition, FlowNote
			 FROM tb_approval_flow WHERE JenisID = ? AND FlowOrder > 0
			 ORDER BY FlowOrder", array($jenisID))->result_array();
		if (empty($flow)) return array();

		$orderOf = array();
		$out = array();
		foreach ($flow as $f) {
			$ord = (int) $f['FlowOrder'];
			$orderOf[$f['FlowCode']] = $ord;
			$out[$ord] = array(
				'order'    => $ord,
				'code'     => $f['FlowCode'],
				'position' => $f['FlowPosition'],
				'label'    => $f['FlowNote'] ?: $f['FlowPosition'],
				'count'    => 0,
			);
		}
		$maxOrder = max(array_keys($out));

		$rows = $this->db->query(
			"SELECT KegiatanStatus, KegiatanStatusTerakhir
			 FROM tb_kegiatan
			 WHERE KegiatanDeletedAt IS NULL AND COALESCE(KegiatanJenisID, 1) = ?
			   AND KegiatanStatus IN ('editable','Approval OnProgress','Perlu Revisi')",
			array($jenisID))->result_array();

		foreach ($rows as $r) {
			$st   = $r['KegiatanStatus'];
			$last = $r['KegiatanStatusTerakhir'];
			if ($st === 'editable' || $last === null || $last === '' || !isset($orderOf[$last])) {
				$stage = 1;
			} elseif ($st === 'Perlu Revisi') {
				$stage = $orderOf[$last];
			} else {
				$stage = min($orderOf[$last] + 1, $maxOrder);
			}
			if (isset($out[$stage])) $out[$stage]['count']++;
		}

		ksort($out);
		return array_values($out);
	}

	/**
	 * DASBOR — KPI bulan berjalan: jumlah pengajuan yang SELESAI bulan ini
	 * dan rata-rata hari kerja dari tanggal pengajuan sampai selesai.
	 */
	public function MonthlyKpi($jenisID = 1)
	{
		$jenisID = (int) $jenisID ?: 1;
		$this->load->helper('sla_helper');

		$lastCode = $this->db->query(
			"SELECT FlowCode FROM tb_approval_flow
			 WHERE JenisID = ? AND FlowOrder > 0 ORDER BY FlowOrder DESC LIMIT 1",
			array($jenisID))->row('FlowCode');
		$lastCode = $lastCode ?: 'SLS';

		$rows = $this->db->query(
			"SELECT k.KegiatanTanggal, h.FlowDate
			 FROM tb_kegiatan k
			 JOIN tb_approval_history h
			   ON h.KegiatanID = k.KegiatanID AND h.FlowCode = ? AND h.FlowResult = 1
			 WHERE k.KegiatanDeletedAt IS NULL AND COALESCE(k.KegiatanJenisID, 1) = ?
			   AND k.KegiatanStatus = 'Approval Selesai'
			   AND YEAR(h.FlowDate) = YEAR(CURDATE()) AND MONTH(h.FlowDate) = MONTH(CURDATE())",
			array($lastCode, $jenisID))->result_array();

		$sum = 0; $cnt = 0;
		foreach ($rows as $r) {
			if (!empty($r['KegiatanTanggal']) && !empty($r['FlowDate'])) {
				$sum += sla_hari_kerja($r['KegiatanTanggal'], $r['FlowDate']);
				$cnt++;
			}
		}
		return array(
			'selesai_bulan_ini' => count($rows),
			'avg_hk'            => $cnt ? round($sum / $cnt, 1) : null,
		);
	}

}
