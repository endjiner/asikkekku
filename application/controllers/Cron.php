<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('M_Manajemen_approval');
	}

	public function index()
	{
		show_404();
	}

	public function early_warning()
	{
		$key = (string) $this->input->get('key');

		$expected = '';
		$row = $this->db->get_where('tb_vrbl', array('VrblName' => 'ew_cron_key'))->row_array();
		if (!empty($row)) $expected = (string) $row['VrblValue'];

		$isSuperAdmin = ($this->session->userdata('UserPosition') === 'SuperAdmin');
		if (!$isSuperAdmin && ($expected === '' || !hash_equals($expected, $key))) {
			$this->output
				->set_status_header(403)
				->set_content_type('application/json')
				->set_output(json_encode(array('status' => 0, 'message' => 'Kunci tidak valid.')));
			return;
		}

		$res = $this->M_Manajemen_approval->EarlyWarningKirim('cron');
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(array('status' => 1, 'waktu' => date('Y-m-d H:i:s')) + $res));
	}
}
