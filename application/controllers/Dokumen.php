<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dokumen extends App_Controller
{
	private $peran_alur = array('SuperAdmin', 'PJ-Kegiatan', 'PPK-Staff', 'Verifikator', 'SPP', 'PPK', 'SPM', 'PPSPM');

	function __construct()
	{
		parent::__construct();
		$this->load->model('M_Dokumen');
		$this->load->helper('dokumen');
		$this->bootData();
		$this->data['menu_detail'] = array('MenuName' => 'Dokumen Pencairan', 'MenuKode' => '3000');
		if (!in_array($this->session->userdata('UserPosition'), $this->peran_alur, true)) {
			show_error('Anda tidak berhak mengakses modul Dokumen Pencairan.', 403);
			exit;
		}
	}

	function index()
	{
		redirect(base_url('dokumen/pilih'));
	}

	function panel($KegiatanID = 0)
	{
		$KegiatanID = (int) $KegiatanID;
		$this->load->view('dokumen/panel', array(
			'KegiatanID'   => $KegiatanID,
			'kegiatan'     => $this->M_Dokumen->kegiatan($KegiatanID),
			'dokumen'      => $this->M_Dokumen->dokumenUntukKegiatan($KegiatanID),
			'ttdmap'       => $this->M_Dokumen->ttdAktifKegiatan($KegiatanID),
			'userPosition' => $this->session->userdata('UserPosition'),
		));
	}

	function pilih()
	{
		$this->data['kegiatan_list'] = $this->M_Dokumen->kegiatanTerbaru(60);
		$this->data['templates']     = $this->M_Dokumen->templates();
		$this->data['body']          = 'dokumen/pilih';
		$this->load->view('main', $this->data);
	}

	function form($kode = '', $KegiatanID = 0)
	{
		$tpl = $this->M_Dokumen->template($kode);
		if (!$tpl) { show_404(); return; }
		$KegiatanID = (int) $KegiatanID;
		$rangkap = $this->input->get('r');
		$rangkap = ($rangkap === null || $rangkap === '') ? '-' : $rangkap;

		$this->data['kode']         = $kode;
		$this->data['tpl']          = $tpl;
		$this->data['KegiatanID']   = $KegiatanID;
		$this->data['kegiatan']     = $this->M_Dokumen->kegiatan($KegiatanID);
		$this->data['rangkap']      = $rangkap;
		$this->data['rangkap_list'] = $this->M_Dokumen->rangkapList($KegiatanID);
		$this->data['payload']      = $this->M_Dokumen->load($kode, $KegiatanID, $rangkap);
		$this->data['body']         = 'dokumen/form';
		$this->load->view('main', $this->data);
	}

	function pratinjau()
	{
		$kode       = $this->input->post('kode');
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$rangkap    = $this->input->post('rangkap') ?: '-';
		$payloadIn  = json_decode($this->input->post('payload'), true) ?: array();

		$tpl = $this->M_Dokumen->template($kode);
		if (!$tpl) { show_404(); return; }

		$auto    = $this->M_Dokumen->autofill($kode, $KegiatanID, $rangkap);
		$payload = array_merge($auto, $payloadIn);

		$this->load->view('dokumen/_render', array(
			'kode' => $kode, 'tpl' => $tpl, 'd' => $payload,
			'kegiatan' => $this->M_Dokumen->kegiatan($KegiatanID),
			'ttd' => $this->_ttdMap($KegiatanID, $kode, $rangkap),
			'tampilkan_ttd' => true,
		));
	}

	private function _ttdMap($KegiatanID, $kode, $rangkap)
	{
		$map = $this->M_Dokumen->ttdAktif($KegiatanID, $kode, $rangkap);
		$cap = $this->M_Dokumen->capImagePath();
		if ($cap) $map['_cap_path'] = $cap;
		return $map;
	}

	function simpan()
	{
		$kode       = $this->input->post('kode');
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$rangkap    = $this->input->post('rangkap') ?: '-';
		$payload    = json_decode($this->input->post('payload'), true);
		if (!$this->M_Dokumen->template($kode) || !is_array($payload)) {
			echo json_encode(array('ok' => false, 'msg' => 'Data tidak valid.'));
			return;
		}
		$res = $this->M_Dokumen->save($kode, $KegiatanID, $rangkap, $payload, $this->data['UserID']);
		echo json_encode($res);
	}

	function cetak($kode = '', $KegiatanID = 0)
	{
		$tpl = $this->M_Dokumen->template($kode);
		if (!$tpl) { show_404(); return; }
		$rangkap = $this->input->get('r');
		$rangkap = ($rangkap === null || $rangkap === '') ? '-' : $rangkap;
		$withTtd = ($this->input->get('ttd') !== '0');
		$this->load->view('dokumen/cetak', array(
			'kode'     => $kode,
			'tpl'      => $tpl,
			'd'        => $this->M_Dokumen->load($kode, (int) $KegiatanID, $rangkap),
			'kegiatan' => $this->M_Dokumen->kegiatan((int) $KegiatanID),
			'ttd'      => $withTtd ? $this->_ttdMap((int) $KegiatanID, $kode, $rangkap) : array(),
			'tampilkan_ttd' => $withTtd,
			'AppConfig' => $this->data['AppConfig'],
		));
	}

	/* ---------------- UNDUH PDF (mPDF, server-side) ---------------- */

	private function _mpdf()
	{
		require_once FCPATH . 'vendor/autoload.php';
		$tmp = FCPATH . 'assets/uploads/tmp';
		if (!is_dir($tmp)) @mkdir($tmp, 0775, true);
		return new \Mpdf\Mpdf(array(
			'mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tmp,
			'margin_left' => 18, 'margin_right' => 18, 'margin_top' => 15, 'margin_bottom' => 15,
			'default_font' => 'dejavuserif',
		));
	}

	private function _docCss()
	{
		$f = FCPATH . 'assets/css/dokumen.css';
		return is_file($f) ? file_get_contents($f) : '';
	}

	/** Fragmen HTML _render sebuah dokumen (untuk mPDF). */
	private function _renderHtml($kode, $tpl, $d, $kegiatan, $ttd, $withTtd)
	{
		return $this->load->view('dokumen/_render', array(
			'kode' => $kode, 'tpl' => $tpl, 'd' => $d, 'kegiatan' => $kegiatan,
			'ttd' => $ttd, 'tampilkan_ttd' => $withTtd,
		), TRUE);
	}

	/** Unduh satu dokumen sebagai PDF A4. ?r=&ttd=0|1 */
	function unduh($kode = '', $KegiatanID = 0)
	{
		$tpl = $this->M_Dokumen->template($kode);
		if (!$tpl) { show_404(); return; }
		$KegiatanID = (int) $KegiatanID;
		$rangkap = $this->input->get('r'); $rangkap = ($rangkap === null || $rangkap === '') ? '-' : $rangkap;
		$withTtd = ($this->input->get('ttd') !== '0');

		$html = $this->_renderHtml($kode, $tpl, $this->M_Dokumen->load($kode, $KegiatanID, $rangkap),
			$this->M_Dokumen->kegiatan($KegiatanID),
			$withTtd ? $this->_ttdMap($KegiatanID, $kode, $rangkap) : array(), $withTtd);

		$this->M_Dokumen->printLog($KegiatanID, array($kode . ':' . $rangkap), $withTtd, $this->data['UserID']);

		$mpdf = $this->_mpdf();
		$mpdf->WriteHTML($this->_docCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML('<div class="a4">' . $html . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);
		$name = $kode . '_' . $KegiatanID . ($rangkap !== '-' ? '_' . preg_replace('/[^A-Za-z0-9]+/', '-', $rangkap) : '')
			. ($withTtd ? '' : '_tanpa-ttd') . '.pdf';
		$mpdf->Output($name, \Mpdf\Output\Destination::DOWNLOAD);
	}

	/** Unduh beberapa dokumen sebagai SATU PDF. ?d[]=kode:rangkap & ttd=0|1 */
	function paketUnduh($KegiatanID = 0)
	{
		$KegiatanID = (int) $KegiatanID;
		$withTtd = ($this->input->get('ttd') !== '0');
		$sel = (array) $this->input->get('d');
		$mpdf = $this->_mpdf();
		$mpdf->WriteHTML($this->_docCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
		$kegiatan = $this->M_Dokumen->kegiatan($KegiatanID);
		$n = 0; $isi = array();
		foreach ($sel as $s) {
			$p = explode(':', $s, 2);
			$kode = preg_replace('/[^a-z_]/', '', strtolower($p[0]));
			$rangkap = isset($p[1]) && $p[1] !== '' ? $p[1] : '-';
			$tpl = $this->M_Dokumen->template($kode);
			if (!$tpl) continue;
			if ($n > 0) $mpdf->AddPage();
			$html = $this->_renderHtml($kode, $tpl, $this->M_Dokumen->load($kode, $KegiatanID, $rangkap),
				$kegiatan, $withTtd ? $this->_ttdMap($KegiatanID, $kode, $rangkap) : array(), $withTtd);
			$mpdf->WriteHTML('<div class="a4">' . $html . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);
			$isi[] = $kode . ':' . $rangkap; $n++;
		}
		if (!$n) { show_error('Tidak ada dokumen dipilih.', 400); return; }
		$this->M_Dokumen->printLog($KegiatanID, $isi, $withTtd, $this->data['UserID']);
		$mpdf->Output('paket_' . $KegiatanID . ($withTtd ? '' : '_tanpa-ttd') . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
	}

	/* ---------------- PAKET CETAK / UNDUH (browser-print) ---------------- */

	/** Layar pilih dokumen untuk dicetak/diunduh sekaligus. */
	function paket($KegiatanID = 0)
	{
		$KegiatanID = (int) $KegiatanID;
		$this->data['KegiatanID'] = $KegiatanID;
		$this->data['kegiatan']   = $this->M_Dokumen->kegiatan($KegiatanID);
		$this->data['dokumen']    = $this->M_Dokumen->dokumenUntukKegiatan($KegiatanID);
		$this->data['ttdmap']     = $this->M_Dokumen->ttdAktifKegiatan($KegiatanID);
		$this->data['body']       = 'dokumen/paket';
		$this->load->view('main', $this->data);
	}

	/**
	 * Gabungan cetak: satu halaman berisi beberapa dokumen (page-break antar
	 * dokumen), auto window.print() -> user simpan sebagai satu PDF.
	 * ?d[]=kode:rangkap ... & ttd=0|1
	 */
	function paketCetak($KegiatanID = 0)
	{
		$KegiatanID = (int) $KegiatanID;
		$withTtd = ($this->input->get('ttd') !== '0');
		$sel = (array) $this->input->get('d');
		$items = array();
		foreach ($sel as $s) {
			$p = explode(':', $s, 2);
			$kode = preg_replace('/[^a-z_]/', '', strtolower($p[0]));
			$rangkap = isset($p[1]) && $p[1] !== '' ? $p[1] : '-';
			$tpl = $this->M_Dokumen->template($kode);
			if (!$tpl) continue;
			$items[] = array(
				'kode'    => $kode,
				'nama'    => $tpl['nama'],
				'tpl'     => $tpl,
				'rangkap' => $rangkap,
				'd'       => $this->M_Dokumen->load($kode, $KegiatanID, $rangkap),
				'ttd'     => $withTtd ? $this->_ttdMap($KegiatanID, $kode, $rangkap) : array(),
			);
		}
		if (!$items) { show_error('Tidak ada dokumen dipilih.', 400); return; }

		$this->M_Dokumen->printLog($KegiatanID, array_map(function ($x) {
			return $x['kode'] . ':' . $x['rangkap'];
		}, $items), $withTtd, $this->data['UserID']);

		$this->load->view('dokumen/paket_cetak', array(
			'KegiatanID'    => $KegiatanID,
			'kegiatan'      => $this->M_Dokumen->kegiatan($KegiatanID),
			'items'         => $items,
			'tampilkan_ttd' => $withTtd,
		));
	}

	/** JSON: dokumen yang masih perlu ditandatangani posisi user saat ini (untuk gate "Setuju"). */
	function ttdKurang($KegiatanID = 0)
	{
		$pos = $this->session->userdata('UserPosition');
		$on  = $this->M_Dokumen->dokGatePpkOn();
		$kurang = ($on && $pos !== 'SuperAdmin')
			? $this->M_Dokumen->ttdKurangUntukPosisi((int) $KegiatanID, $pos)
			: array();
		echo json_encode(array('on' => $on, 'posisi' => $pos, 'kurang' => $kurang, 'lengkap' => empty($kurang)));
	}

	/* ---------------- CAP DINAS (scan, dikelola admin) ---------------- */

	/** Unggah gambar cap dinas (PNG transparan). Hanya pengelola Konfigurasi Aplikasi. */
	function capUpload()
	{
		$this->Auth->cekMenu('2300', 'u');
		if (empty($_FILES['cap']['name']) || (int) $_FILES['cap']['error'] !== UPLOAD_ERR_OK) {
			echo json_encode(array('ok' => false, 'msg' => 'Tidak ada berkas.')); return;
		}
		$tmp = $_FILES['cap']['tmp_name'];
		if (!is_uploaded_file($tmp)) { echo json_encode(array('ok' => false, 'msg' => 'Berkas tidak sah.')); return; }
		if (filesize($tmp) > 600 * 1024) { echo json_encode(array('ok' => false, 'msg' => 'Maksimal 600 KB.')); return; }
		$head = @file_get_contents($tmp, false, null, 0, 8);
		if ($head !== "\x89PNG\r\n\x1a\n") { echo json_encode(array('ok' => false, 'msg' => 'Harus file PNG (disarankan transparan).')); return; }
		$info = @getimagesize($tmp);
		if (!$info || $info[0] > 1200 || $info[1] > 1200) {
			echo json_encode(array('ok' => false, 'msg' => 'Dimensi maksimal 1200x1200 px.')); return;
		}
		$dir = FCPATH . 'assets/uploads/';
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		$rel = 'assets/uploads/cap_dinas.png';
		if (!move_uploaded_file($tmp, FCPATH . $rel)) {
			echo json_encode(array('ok' => false, 'msg' => 'Gagal menyimpan berkas.')); return;
		}
		$this->M_Dokumen->capSet($rel, $this->data['UserID']);
		echo json_encode(array('ok' => true, 'msg' => 'Cap dinas tersimpan.', 'src' => base_url($rel) . '?v=' . time()));
	}

	/** Aktif/nonaktifkan gate "Setuju PPK". */
	function gatePpkSet()
	{
		$this->Auth->cekMenu('2300', 'u');
		$on = ((string) $this->input->post('on') === '1') ? '1' : '0';
		if ($this->db->get_where('tb_vrbl', array('VrblName' => 'dok_gate_ppk'))->num_rows() > 0) {
			$this->db->where('VrblName', 'dok_gate_ppk')->update('tb_vrbl', array('VrblValue' => $on));
		} else {
			$this->db->insert('tb_vrbl', array('VrblName' => 'dok_gate_ppk', 'VrblValue' => $on));
		}
		echo json_encode(array('ok' => true, 'on' => $on === '1'));
	}

	function capHapus()
	{
		$this->Auth->cekMenu('2300', 'u');
		$this->M_Dokumen->capSet('', $this->data['UserID']);
		@unlink(FCPATH . 'assets/uploads/cap_dinas.png');
		echo json_encode(array('ok' => true, 'msg' => 'Cap dinas dihapus.'));
	}

	/* ---------------- TANDA TANGAN (tangkap-langsung) ---------------- */

	/** Halaman kanvas tanda tangan untuk satu slot. */
	function ttd($kode = '', $KegiatanID = 0)
	{
		$tpl = $this->M_Dokumen->template($kode);
		if (!$tpl) { show_404(); return; }
		$KegiatanID = (int) $KegiatanID;
		$rangkap = $this->input->get('r'); $rangkap = ($rangkap === null || $rangkap === '') ? '-' : $rangkap;
		$slot    = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $this->input->get('slot')));
		if (!in_array($slot, (array) $tpl['slot_ttd'], true)) { show_error('Slot tanda tangan tidak dikenal.', 400); return; }

		$gate = $this->_ttdBoleh($KegiatanID, $slot);
		$this->data['kode'] = $kode;
		$this->data['tpl'] = $tpl;
		$this->data['KegiatanID'] = $KegiatanID;
		$this->data['rangkap'] = $rangkap;
		$this->data['slot'] = $slot;
		$this->data['kegiatan'] = $this->M_Dokumen->kegiatan($KegiatanID);
		$this->data['aktif'] = $this->M_Dokumen->ttdAktif($KegiatanID, $kode, $rangkap);
		$this->data['boleh'] = $gate['ok'];
		$this->data['boleh_pesan'] = $gate['msg'];
		$this->data['body'] = 'dokumen/ttd';
		$this->load->view('main', $this->data);
	}

	function ttdSimpan()
	{
		$kode = $this->input->post('kode');
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$rangkap = $this->input->post('rangkap') ?: '-';
		$slot = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $this->input->post('slot')));
		$tpl = $this->M_Dokumen->template($kode);
		if (!$tpl || !in_array($slot, (array) $tpl['slot_ttd'], true)) {
			echo json_encode(array('ok' => false, 'msg' => 'Slot / dokumen tidak valid.')); return;
		}
		$gate = $this->_ttdBoleh($KegiatanID, $slot);
		if (!$gate['ok']) { echo json_encode(array('ok' => false, 'msg' => $gate['msg'])); return; }

		$dataUrl = (string) $this->input->post('image');
		if (!preg_match('#^data:image/png;base64,#', $dataUrl)) {
			echo json_encode(array('ok' => false, 'msg' => 'Format gambar tidak valid (harus PNG).')); return;
		}
		$img = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
		if ($img === false) { echo json_encode(array('ok' => false, 'msg' => 'Gambar rusak.')); return; }

		$res = $this->M_Dokumen->ttdSimpan($KegiatanID, $kode, $rangkap, $slot, $img, array(
			'user_id' => $this->data['UserID'],
			'nama'    => isset($this->data['UserFullName']) ? $this->data['UserFullName'] : $this->session->userdata('UserFullName'),
			'role'    => isset($this->data['UserPosition']) ? $this->data['UserPosition'] : $this->session->userdata('UserPosition'),
			'ip'      => $this->input->ip_address(),
		));
		echo json_encode($res);
	}

	/**
	 * Boleh menandatangani slot ini?
	 *   penerima -> PJ pemilik pengajuan (mengumpulkan ttd pelaksana) / SuperAdmin.
	 *   ppk      -> user berposisi PPK yang MEMANG petugas tujuan tahap berjalan
	 *               (FlowDestUser baris riwayat terakhir) / SuperAdmin.
	 */
	private function _ttdBoleh($KegiatanID, $slot)
	{
		$pos = $this->session->userdata('UserPosition');
		$uid = (int) $this->session->userdata('UserID');
		if ($pos === 'SuperAdmin') return array('ok' => true, 'msg' => '');

		$this->db->select('KegiatanUserID, KegiatanStatus');
		$keg = $this->db->get_where('tb_kegiatan', array('KegiatanID' => (int) $KegiatanID))->row_array();
		if (empty($keg)) return array('ok' => false, 'msg' => 'Kegiatan tidak ditemukan.');

		if ($slot === 'penerima') {
			return ((int) $keg['KegiatanUserID'] === $uid && $pos === 'PJ-Kegiatan')
				? array('ok' => true, 'msg' => '')
				: array('ok' => false, 'msg' => 'Tanda tangan penerima dikumpulkan oleh PJ pembuat pengajuan.');
		}
		if ($slot === 'ppk') {
			if ($pos !== 'PPK') return array('ok' => false, 'msg' => 'Hanya PPK yang menandatangani slot ini.');
			$last = $this->db->select('FlowDestUser')
				->order_by('HistoryID', 'DESC')
				->get_where('tb_approval_history', array('KegiatanID' => (int) $KegiatanID), 1)->row_array();
			if (!empty($last) && (int) $last['FlowDestUser'] === $uid) return array('ok' => true, 'msg' => '');
			return array('ok' => false, 'msg' => 'Pengajuan ini belum / bukan pada antrian Anda sebagai PPK.');
		}
		return array('ok' => false, 'msg' => 'Slot tidak dikenal.');
	}

	/* ================================================================
	   UPLOAD DOKUMEN EKSTERNAL (LPD, SPPD, SPM, SPP, dll.)
	   ================================================================ */

	private function _loadDokUpload()
	{
		$this->load->model('M_DokUpload');
	}

	/** AJAX: daftar file terupload untuk sebuah kegiatan. */
	function uploadEksternalList($KegiatanID = 0)
	{
		$this->_loadDokUpload();
		$list = $this->M_DokUpload->listUpload((int) $KegiatanID);
		$base = base_url();
		foreach ($list as &$r) {
			$r['url_ttd_ppk']   = $base . 'dokumen/ttdUpload/' . $r['UploadID'] . '?slot=ppk';
			$r['url_ttd_ppspm'] = $base . 'dokumen/ttdUpload/' . $r['UploadID'] . '?slot=ppspm';
			$r['url_unduh']     = $base . 'dokumen/unduhBerTtd/' . $r['UploadID'];
		}
		unset($r);
		$this->output->set_content_type('application/json')->set_output(json_encode($list, JSON_UNESCAPED_UNICODE));
	}

	/** POST: upload PDF eksternal. */
	function uploadEksternal()
	{
		$this->_loadDokUpload();
		$pos       = $this->session->userdata('UserPosition');
		$userID    = $this->session->userdata('UserID');
		$KegiatanID = (int) $this->input->post('KegiatanID');
		$tipe      = strtolower(trim((string) $this->input->post('tipe')));

		// Validasi hak upload
		if (!$this->M_DokUpload->bolehUpload($tipe, $pos)) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Anda tidak berwenang upload tipe dokumen ini.')));
			return;
		}

		// Validasi file
		if (empty($_FILES['file']['tmp_name'])) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Tidak ada file yang diunggah.')));
			return;
		}
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime  = finfo_file($finfo, $_FILES['file']['tmp_name']);
		finfo_close($finfo);
		if ($mime !== 'application/pdf') {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Hanya file PDF yang diizinkan.')));
			return;
		}
		if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Ukuran file maksimal 10 MB.')));
			return;
		}

		$dir = FCPATH . 'assets/uploads/dok/' . $KegiatanID . '/';
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		$ext  = 'pdf';
		$safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
		$fname = $tipe . '_' . $safe . '_' . time() . '.' . $ext;
		if (!move_uploaded_file($_FILES['file']['tmp_name'], $dir . $fname)) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Gagal memindahkan file.')));
			return;
		}
		$rel = 'assets/uploads/dok/' . $KegiatanID . '/' . $fname;
		$res = $this->M_DokUpload->simpanUpload($KegiatanID, $tipe, $rel, $_FILES['file']['name'], $_FILES['file']['size'], $userID);
		$this->output->set_content_type('application/json')->set_output(json_encode($res, JSON_UNESCAPED_UNICODE));
	}

	/** POST: hapus upload (hanya uploader / SuperAdmin, sebelum ada TTD). */
	function uploadEksternalHapus()
	{
		$this->_loadDokUpload();
		$UploadID = (int) $this->input->post('UploadID');
		$userID   = (int) $this->session->userdata('UserID');
		$pos      = $this->session->userdata('UserPosition');
		$row      = $this->M_DokUpload->getUpload($UploadID);
		if (!$row) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'File tidak ditemukan.')));
			return;
		}
		if ($pos !== 'SuperAdmin' && (int) $row['UploadedBy'] !== $userID) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Anda tidak berwenang menghapus file ini.')));
			return;
		}
		$res = $this->M_DokUpload->hapusUpload($UploadID, $userID);
		$this->output->set_content_type('application/json')->set_output(json_encode($res, JSON_UNESCAPED_UNICODE));
	}

	/* ================================================================
	   TTD ON-DOCUMENT
	   ================================================================ */

	/** GET: halaman preview PDF + pilih area TTD + kanvas TTD. */
	function ttdUpload($UploadID = 0)
	{
		$this->_loadDokUpload();
		$pos  = $this->session->userdata('UserPosition');
		$row  = $this->M_DokUpload->getUpload((int) $UploadID);
		if (!$row) {
			$this->session->set_flashdata('error', 'Dokumen upload tidak ditemukan atau ID tidak valid.');
			redirect(base_url('dokumen/pilih'));
			return;
		}

		$slot = $this->input->get('slot') ?: 'ppk';
		$slot = preg_replace('/[^a-z0-9_]/', '', strtolower($slot));

		// Gate: ppk -> hanya PPK, ppspm -> hanya PPSPM atau SuperAdmin
		$boleh = false; $bolehPesan = '';
		if ($slot === 'ppk' && $pos === 'PPK') $boleh = true;
		elseif ($slot === 'ppspm' && in_array($pos, array('PPSPM', 'SuperAdmin'), true)) $boleh = true;
		else $bolehPesan = 'Anda tidak berwenang menandatangani slot ini.';

		$this->data['body'] = 'dokumen/ttd_upload';
		$this->data['row']       = $row;
		$this->data['slot']      = $slot;
		$this->data['boleh']     = $boleh;
		$this->data['boleh_pesan'] = $bolehPesan;
		$this->load->view('main', $this->data);
	}

	/** POST AJAX: simpan posisi + gambar TTD ke PDF yang di-upload. */
	function ttdUploadSimpan()
	{
		$this->_loadDokUpload();
		$pos      = $this->session->userdata('UserPosition');
		$userID   = $this->session->userdata('UserID');
		$UploadID = (int) $this->input->post('UploadID');
		$slot     = $this->input->post('slot');

		// Decode image base64
		$imgB64 = $this->input->post('image');
		if (strpos($imgB64, 'data:image/png;base64,') === 0) {
			$imgB64 = substr($imgB64, strlen('data:image/png;base64,'));
		}
		$img = base64_decode($imgB64);
		if ($img === false) {
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => false, 'msg' => 'Gambar tidak valid.')));
			return;
		}

		$pos_arr = array(
			'page' => (int) $this->input->post('page') ?: 1,
			'x'    => floatval($this->input->post('x')),
			'y'    => floatval($this->input->post('y')),
			'w'    => floatval($this->input->post('w')) ?: 0.2,
			'h'    => floatval($this->input->post('h')) ?: 0.06,
		);
		$signer = array(
			'user_id' => $userID,
			'nama'    => $this->session->userdata('UserFullName'),
			'role'    => $pos,
			'ip'      => $this->input->ip_address(),
		);

		$res = $this->M_DokUpload->simpanTtd($UploadID, $slot, $pos_arr, $img, $signer);
		$this->output->set_content_type('application/json')->set_output(json_encode($res, JSON_UNESCAPED_UNICODE));
	}

	/** GET: unduh PDF yang sudah di-embed TTD (FPDI overlay). */
	function unduhBerTtd($UploadID = 0)
	{
		$this->_loadDokUpload();
		$res = $this->M_DokUpload->buatPdfBerTtd((int) $UploadID);
		if (!$res['ok']) {
			show_error($res['msg'], 500);
			return;
		}
		$name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $res['name']);
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Length: ' . strlen($res['pdf']));
		echo $res['pdf'];
	}
}
