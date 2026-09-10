<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Auth {

	function __construct()	{
		$this->ci = &get_instance();
	}

	// cek session login
	function cekLogin() {
		$status = $this->ci->session->userdata('UserID');
		if (empty($status)) {
			redirect(base_url('MainPage/login'));
			exit();
		}
	}
	// cek akses menu
	function cekMenu($menu_kode, $status)
	{
		$this->ci->load->model('auth/M_Menu');
		$result = $this->ci->M_Menu->CheckMenu($this->ci->session->userdata('UserGroupID'), $menu_kode, $status);
		if ($result != 1) {
			if (!$this->ci->input->is_ajax_request()) {
				$this->alert_error_akses();
				redirect(base_url('dashboard'));
				exit();
			} else {
				$this->alert_error_response('anda tidak punya akses tersebut !');
			}
		}
	}
	function alert_error_akses($msg = null)
	{
		$newdata['error_info'] = (is_null($msg)) ? 'anda tidak punya akses tersebut !' : $msg;
		$this->ci->session->set_userdata($newdata);
	}
	function alert_error_response($msg)
	{
		$data['error'] = $msg;
		echo json_encode($data);
		exit();
	}
}
?>