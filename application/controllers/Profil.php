<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profil — hanya fitur Ganti Password akun (dipakai oleh modal di semua halaman).
 * Modul detail pegawai lama dihapus (tabel tb_pegawai tidak ada pada database ini).
 */
class Profil extends App_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('M_Profil');
	}

	function PasswordModify()
	{
		$data['error']  = $this->M_Profil->PasswordModify();
		$data['status'] = $this->db->trans_status();
		echo json_encode($data);
	}
}
