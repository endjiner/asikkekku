<?php
defined('BASEPATH') or exit('No direct script access allowed');
class MainPage extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->Auth = new auth;
		$this->M_Menu = new M_Menu;
		$this->load->model('auth/M_Login');
	}

	public function Index() {
		$this->Auth->cekLogin();
		redirect(base_url('Dashboard'));
	}

	public function Login() {
		$this->session->sess_destroy();
		$data['AppConfig'] = $this->M_Menu->KonfigurasiAppGetData();
		$this->load->view('auth/index', $data);
	}

	public function VerifyLogin() {
		$username = $this->input->post('Username', TRUE);
		$password = $this->input->post('Password', TRUE);

		$result = $this->M_Login->Login($username, $password);

		if (empty($result)) {
			$newdata['error_info'] = 'LOGIN ERROR,<br>Incorrect Username or Password !';
			$this->session->set_userdata($newdata);
			redirect(base_url('MainPage/Login'));
		} else {
			$UserID = $result[0]['UserID'];
			$UserGroupID = $result[0]['UserGroupID'];
			$UserName = $result[0]['UserName'];
			$UserFullName = (empty($result[0]['PegawaiNama'])) ? $result[0]['UserFullName'] : $result[0]['PegawaiNama'] ;
			$UserPosition = $result[0]['UserPosition'];

			$this->session->set_userdata('UserID', $UserID);
			$this->session->set_userdata('UserGroupID', $UserGroupID);
			$this->session->set_userdata('UserFullName', $UserFullName);
			$this->session->set_userdata('UserPosition', $UserPosition); 
			redirect(base_url('MainPage/Index'));
		}
	}

	public function Logout() {
		$this->session->sess_destroy();
		redirect(base_url('MainPage/Login'));
	}

	public function NotFound()
	{
		$this->Auth->cekLogin();
		$this->menu_kode = "1000";
		$this->Auth->cekMenu($this->menu_kode, 'r');

		$this->data = array(
			'UserID' => $this->session->userdata('UserID'),
			'UserGroupID' => $this->session->userdata('UserGroupID'),
			'UserName' => $this->session->userdata('UserName'), 
			'UserFullName' => $this->session->userdata('UserFullName'), 
			'UserPosition' => $this->session->userdata('UserPosition'), 
			'AppConfig' => $this->M_Menu->KonfigurasiAppGetData(),
			'error_info' => $this->session->userdata('error_info'),
			'menu_list' => $this->M_Menu->GetMenu(),
			'menu_detail' => $this->M_Menu->GetMenu()[$this->menu_kode],
			'data' => '',
			'body' => 'dashboard/NotFound'
		);
		$this->load->view('main', $this->data);
	}
}
