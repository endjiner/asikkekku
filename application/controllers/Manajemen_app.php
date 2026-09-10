<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Manajemen_app extends App_Controller
{

	private $menu_kode;
	function __construct()
	{
		parent::__construct();
		$this->menu_kode = "2000";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		if (!role_can('manajemen_app')) {
			$this->Auth->alert_error_akses('Menu ini hanya untuk Administrator.');
			redirect('dashboard');
		}
		$this->load->model('manajemen_app/M_Manajemen_app');
		$this->bootData($this->menu_kode);
	}

	function index()
	{
		redirect(base_url());
	}

	function group_user_list()
	{
		$this->menu_kode = "2100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
		$this->data['menu_all'] = $this->M_Menu->GetMenuAll();
		// echo json_encode($this->data['menu_all']); exit();

		$this->data['body'] = 'manajemen_app/UserGroup';
		$this->data['footer'] = 'manajemen_app/UserGroupFooter';
		$this->load->view('main', $this->data);
	}
	function userGroupGetList()
	{
		$this->menu_kode = "2100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->load->model('M_DataTable');
		$sql = $this->M_Manajemen_app->userGroupGetListSql();
		$output = $this->M_DataTable->dtTableGetList($sql);
		$output['data'] = $this->M_Manajemen_app->userGroupDecorateRows($output['data']);
		echo json_encode($output);
	}
	function userGroupGetData()
	{
		$this->menu_kode = "2100";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$UserGroupID = $this->input->get_post('UserGroupID');
		$data = $this->M_Manajemen_app->userGroupGetData($UserGroupID);
		echo json_encode($data);
	}
	function userGroupModify()
	{
		$this->menu_kode = "2100";
		$this->Auth->cekMenu($this->menu_kode, 'c');
		$data['error'] = $this->M_Manajemen_app->userGroupModify();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
	function userGroupDelete()
	{
		$this->menu_kode = "2100";
		$this->Auth->cekMenu($this->menu_kode, 'd');
		$this->M_Manajemen_app->userGroupDelete();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}

	// -----------------------------------------------------
	function user_list()
	{
		$this->menu_kode = "2200";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
		$this->data['userGroupList'] = $this->M_Manajemen_app->userGroupGetData()['user_group'];
		$this->data['position'] = $this->M_Menu->position_list();
		$this->data['body'] = 'manajemen_app/User';
		$this->data['footer'] = 'manajemen_app/UserFooter';
		$this->load->view('main', $this->data);
	}
	function userGetList()
	{
		$this->menu_kode = "2200";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->load->model('M_DataTable');
		$sql = $this->M_Manajemen_app->userGetListSql();
		$output = $this->M_DataTable->dtTableGetList($sql);
		$output['data'] = $this->M_Manajemen_app->userDecorateRows($output['data']);
		echo json_encode($output);
	}
	function userGetData()
	{
		$this->menu_kode = "2200";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$UserID = $this->input->get_post('UserID');
		$data = $this->M_Manajemen_app->userGetData($UserID);
		echo json_encode($data);
	}
	function userModify()
	{
		$this->menu_kode = "2200";
		$this->Auth->cekMenu($this->menu_kode, 'c');
		$data['error'] = $this->M_Manajemen_app->userModify();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
	function userDelete()
	{
		$this->menu_kode = "2200";
		$this->Auth->cekMenu($this->menu_kode, 'd');
		$this->M_Manajemen_app->userDelete();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}

	// -----------------------------------------------------
	function konfigurasi_app()
	{
		$this->menu_kode = "2300";
		$this->Auth->cekMenu($this->menu_kode, 'r');
		$this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
		$this->data['data_list'] = $this->M_Manajemen_app->KonfigurasiAppGetData();
		$this->data['jenis_list'] = $this->M_Manajemen_app->JenisPengajuanList();
		$jenisSel = (int) $this->input->get('jenis');
		if ($jenisSel <= 0 && !empty($this->data['jenis_list'])) {
			$jenisSel = (int) $this->data['jenis_list'][0]['JenisID'];
		}
		$this->data['jenis_selected'] = $jenisSel;
		$this->data['flow'] = $this->M_Manajemen_app->ApprovalFlowGetData($jenisSel);
		// echo json_encode($this->data['data_list']); exit();
		$this->data['body'] = 'manajemen_app/KonfigurasiApp';
		$this->data['footer'] = 'manajemen_app/KonfigurasiAppFooter';
		$this->load->view('main', $this->data);
	}
	function KonfigurasiAppModify()
	{
		$this->menu_kode = "2300";
		$this->Auth->cekMenu($this->menu_kode, 'u');
		$this->M_Manajemen_app->KonfigurasiAppModify();

		$this->load->library('upload');
		$config['upload_path'] = './assets/images/';
		$config['allowed_types'] = 'gif|jpg|png';
		$config['max_size'] = '100';
		$config['max_width'] = '1024';
		$config['max_height'] = '768';


		$filename = isset($_FILES['logo_big']['name']) ? $_FILES['logo_big']['name'] : '';
		// echo json_encode($filename); exit();
		if ($filename) {
			$location = "assets/images/logo_baru.png";
			$uploadOk = 1;
			$imageFileType = pathinfo($location, PATHINFO_EXTENSION);
			// Check image format
			if ($imageFileType != "png") {
				$uploadOk = 0;
			}
			if ($uploadOk == 0) {
				// echo 0;
			} else {
				move_uploaded_file($_FILES['logo_big']['tmp_name'], $location);
			}
		}

		$filename = isset($_FILES['logo_small']['name']) ? $_FILES['logo_small']['name'] : '';
		// echo json_encode($filename); exit();
		if ($filename) {
			$location = "assets/images/logo_baru.png";
			$uploadOk = 1;
			$imageFileType = pathinfo($location, PATHINFO_EXTENSION);
			// Check image format
			if ($imageFileType != "png") {
				$uploadOk = 0;
			}
			if ($uploadOk == 0) {
				// echo 0;
			} else {
				move_uploaded_file($_FILES['logo_small']['tmp_name'], $location);
			}
		}

		$filename = isset($_FILES['cover_logo']['name']) ? $_FILES['cover_logo']['name'] : '';
		// echo json_encode($filename); exit();
		if ($filename) {
			$location = "assets/images/logo_baru.png";
			$uploadOk = 1;
			$imageFileType = pathinfo($location, PATHINFO_EXTENSION);
			// Check image format
			if ($imageFileType != "png") {
				$uploadOk = 0;
			}
			if ($uploadOk == 0) {
				// echo 0;
			} else {
				move_uploaded_file($_FILES['cover_logo']['tmp_name'], $location);
			}
		}

		// Dulu redirect+refresh (tanpa umpan balik). Sekarang AJAX -> JSON supaya
		// bisa munculkan pop-up "tersimpan" lalu reload dari sisi klien.
		if ($this->input->is_ajax_request()) {
			echo json_encode(array('ok' => true, 'msg' => 'Konfigurasi aplikasi tersimpan.'));
			return;
		}
		redirect('manajemen_app/konfigurasi_app', 'refresh');
	}
	function KonfigurasiAppFlowOrder()
	{
		$this->menu_kode = "2300";
		$this->Auth->cekMenu($this->menu_kode, 'c');
		$data['error'] = $this->M_Manajemen_app->KonfigurasiAppFlowOrder();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}

}
