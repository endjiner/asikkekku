<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Manajemen_approval extends App_Controller
{

	private $menu_kode;
	function __construct()
	{
		parent::__construct();
		$this->menu_kode = "3000";
		$this->load->model('M_Manajemen_approval');
		$this->bootData($this->menu_kode);
	}

	function index()
	{
		redirect(base_url());
	}

	// -----------------------------------------------------
	function list_data()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
		$this->data['menu_all'] = $this->M_Menu->GetMenuAll();

		$this->data['JenisList'] = $this->M_Manajemen_approval->JenisPengajuanAktif();
		$defaultJenis = !empty($this->data['JenisList']) ? (int) $this->data['JenisList'][0]['JenisID'] : 1;
		$this->data['UserDest'] = $this->M_Manajemen_approval->getUserDestination(1, $defaultJenis);
		$this->data['OutputList'] = $this->M_Manajemen_approval->OutputList();
		$this->data['PegawaiOpts'] = $this->M_Manajemen_approval->PegawaiOptions();
		$this->data['body'] = 'manajemen_approval/ListData';
		$this->data['footer'] = 'manajemen_approval/ListDataFooter';
		$this->load->view('main', $this->data);
	}

	/** AJAX: daftar "Petugas Tujuan" untuk jenis tertentu (dependent dropdown). */
	function UserDestByJenis()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		if (!role_can('kegiatan_create')) {
			$this->Auth->alert_error_response('Posisi Anda tidak berhak menambah data kegiatan.');
			return;
		}
		$JenisID = (int) $this->input->post('JenisID');
		if (!$this->M_Manajemen_approval->JenisIsValid($JenisID)) {
			echo json_encode(array()); return;
		}
		echo json_encode($this->M_Manajemen_approval->getUserDestination(1, $JenisID));
	}
	function KegiatanGetList()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'r');

		// Server-side processing: DB yang paginasi/cari/urutkan, PHP hanya
		// menghias baris di halaman aktif. Skalabel walau data ratusan ribu.
		$this->load->model('M_DataTable');
		$sql = $this->M_Manajemen_approval->KegiatanGetListSql();
		$output = $this->M_DataTable->dtTableGetList($sql);
		$output['data'] = $this->M_Manajemen_approval->KegiatanDecorateRows($output['data']);
		echo json_encode($output);
	}
	function KegiatanGetData()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$KegiatanID = $this->input->get_post('KegiatanID');
		$data = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID);
		echo json_encode($data);
	}
	function KegiatanModify()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'c');
		if (!role_can('kegiatan_create')) {
			$this->Auth->alert_error_response('Posisi Anda tidak berhak menambah / mengubah data kegiatan.');
		}
		$data['error'] = $this->M_Manajemen_approval->KegiatanModify();
		$data['status'] = $this->db->trans_status();

		$f = isset($_FILES['KegiatanLampiran']) ? $_FILES['KegiatanLampiran'] : null;
		if ($f && $f['name'] !== '' && (int) $f['error'] === UPLOAD_ERR_OK) {
			$MAX = 5 * 1024 * 1024; // 5 MB
			$cleanName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($f['name']));
			$ext = strtolower(pathinfo($cleanName, PATHINFO_EXTENSION));
			$isPdfExt  = ($ext === 'pdf');
			$isPdfMime = false;
			if (is_uploaded_file($f['tmp_name'])) {
				$fh = @fopen($f['tmp_name'], 'rb');
				if ($fh) { $isPdfMime = (fread($fh, 5) === '%PDF-'); fclose($fh); }
			}
			if (!$isPdfExt || !$isPdfMime) {
				$data['error'] = array('code' => 1, 'message' => 'Lampiran harus berupa file PDF yang sah.');
			} elseif ($f['size'] > $MAX) {
				$data['error'] = array('code' => 1, 'message' => 'Ukuran lampiran melebihi 5 MB.');
			} else {
				move_uploaded_file($f['tmp_name'], FCPATH . 'assets/lampiran/' . $cleanName);
			}
		}
		echo json_encode($data);
	}
	function KegiatanDelete()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'd');
		if ($this->data['UserPosition'] !== 'SuperAdmin') {
			$this->Auth->alert_error_response('Hanya SuperAdmin yang dapat menghapus pengajuan.');
			return;
		}
		$data['error']  = $this->M_Manajemen_approval->KegiatanDelete();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
	function KegiatanSendApproval()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'u');
		$this->M_Manajemen_approval->KegiatanSendApproval();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
	function GetFormInfoKegiatan()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$KegiatanID = $this->input->get_post('KegiatanID');
		$data['data'] = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID)['Kegiatan'][0];
		$data['history'] = $this->M_Manajemen_approval->GetFormInfoKegiatan($KegiatanID)['history'];
		$data['last_status'] = $this->M_Manajemen_approval->GetLastStatus($KegiatanID);
		$data['sla'] = $this->M_Manajemen_approval->KegiatanSlaEval($KegiatanID);
		$this->load->view('manajemen_approval/KegiatanInfo', $data);
	}

	// -----------------------------------------------------
	function list_approval()
	{
		$this->menu_kode = "3200";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		if (!role_can('approval_inbox')) {
			$this->Auth->alert_error_akses('Menu Persetujuan tidak tersedia untuk posisi Anda.');
			redirect('dashboard');
		}
		$this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
		$this->data['menu_all'] = $this->M_Menu->GetMenuAll();

		$this->data['body'] = 'manajemen_approval/ListApproval';
		$this->data['footer'] = 'manajemen_approval/ListApprovalFooter';
		$this->load->view('main', $this->data);
	}
	function KegiatanApprovalGetList()
	{
		$this->menu_kode = "3200";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->load->model('M_DataTable');
		$sql = $this->M_Manajemen_approval->KegiatanApprovalGetListSql();
		$output = $this->M_DataTable->dtTableGetList($sql);
		$output['data'] = $this->M_Manajemen_approval->KegiatanApprovalDecorateRows($output['data']);
		echo json_encode($output);
	}
	function GetApprovalFormInfoKegiatan()
	{
		$this->menu_kode = "3200";
		$this->Auth->cekMenu($this->menu_kode, 'c');
		$KegiatanID = $this->input->get_post('KegiatanID');
		$data['data'] = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID)['Kegiatan'][0];
		$data['history'] = $this->M_Manajemen_approval->GetFormInfoKegiatan($KegiatanID)['history'];
		$data['last_status'] = $this->M_Manajemen_approval->GetLastStatus($KegiatanID);
		$data['sla'] = $this->M_Manajemen_approval->KegiatanSlaEval($KegiatanID);
		// echo json_encode($data['last_status']); exit();
		$jenisKeg = (int) (isset($data['data']['KegiatanJenisID']) ? $data['data']['KegiatanJenisID'] : 1) ?: 1;
		$data['UserDest'] = $this->M_Manajemen_approval->getUserDestination(
			isset($data['last_status'][0]['FlowOrder']) ? $data['last_status'][0]['FlowOrder'] : 0, $jenisKeg);
		// Data untuk form "Kembalikan" (revisi / hentikan proses) + status kegiatan.
		$data['RevisiTargets'] = $this->M_Manajemen_approval->RevisiTargets($KegiatanID);
		$data['kegiatan_status'] = $data['data']['KegiatanStatus'];
		$data['UserPosition'] = $this->session->userdata('UserPosition');
		$data['UserID'] = $this->session->userdata('UserID');
		$this->load->view('manajemen_approval/KegiatanApprovalForm', $data);
		$this->load->view('manajemen_approval/KegiatanInfo', $data);
	}
	function ApprovalFormInfoKegiatanSubmit()
	{
		$this->menu_kode = "3200";
		$this->Auth->cekMenu($this->menu_kode, 'c');
		if (!role_can('approval_inbox')) {
			$this->Auth->alert_error_response('Anda tidak berhak melakukan persetujuan.');
		}
		$data['error'] = $this->M_Manajemen_approval->ApprovalFormInfoKegiatanSubmit();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
	// Hentikan Proses (batalkan) -- hanya PJ-Kegiatan / SuperAdmin.
	function KegiatanTerminate()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'u');
		$data['error'] = $this->M_Manajemen_approval->KegiatanTerminate();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
	// Kirim ulang setelah revisi -- oleh petugas tujuan (PJ / Staff PPK / SPM).
	function KegiatanRevisiKirimUlang()
	{
		$this->menu_kode = "3100";
		$this->Auth->cekMenu($this->menu_kode, 'u');
		$data['error'] = $this->M_Manajemen_approval->KegiatanRevisiKirimUlang();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}

	// -----------------------------------------------------
	function list_report()
	{
		$this->menu_kode = "3300";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		if (!role_can('report')) {
			$this->Auth->alert_error_akses('Menu Laporan tidak tersedia untuk posisi Anda.');
			redirect('dashboard');
		}
		$this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
		$this->data['menu_all'] = $this->M_Menu->GetMenuAll();

		$this->data['body'] = 'manajemen_approval/ListReport';
		$this->data['footer'] = 'manajemen_approval/ListReportFooter';
		$this->load->view('main', $this->data);
	}
	function ReportGetList()
	{
		$this->menu_kode = "3200";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$data['data'] = $this->M_Manajemen_approval->ReportGetList();
		echo json_encode($data);
	}

	// -----------------------------------------------------
	// Peringatan Dini 4HK — pengingat WhatsApp manual (tombol di dashboard)
	function EarlyWarningNudgeOne()
	{
		if (!role_can('approval_inbox') && !role_is_admin()) {
			$this->Auth->alert_error_response('Anda tidak berhak mengirim pengingat.');
		}
		$KegiatanID = (int) $this->input->post('KegiatanID');
		if ($KegiatanID <= 0) {
			echo json_encode(array('status' => 0, 'message' => 'ID kegiatan tidak valid.'));
			return;
		}
		$res    = $this->M_Manajemen_approval->EarlyWarningKirim('manual', $KegiatanID);
		$gagal  = isset($res['gagal']) ? (int) $res['gagal'] : 0;
		$ok     = $res['terkirim'] > 0;
		if ($ok) {
			$message = 'Pengingat WhatsApp terkirim ke ' . $res['terkirim'] . ' nomor'
				. ($gagal > 0
					? ', ' . $gagal . ' gagal (cek koneksi internet, langganan Wablas, atau nomor tujuan).'
					: '.');
		} elseif ($gagal > 0) {
			$message = 'Gagal mengirim ke ' . $gagal . ' nomor. Periksa langganan Wablas, koneksi internet, atau nomor tujuan.';
		} else {
			$message = 'Tidak ada nomor tujuan, atau pengajuan ini tidak sedang terhambat.';
		}
		echo json_encode(array(
			'status'  => $ok ? 1 : 0,
			'message' => $message,
		) + $res);
	}
}
