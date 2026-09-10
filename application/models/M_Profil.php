<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_Profil extends CI_Model {

	function __construct() {
		parent::__construct();
		$this->UserID = $this->session->userdata('UserID');
	}

	/**
	 * Ganti password akun sendiri. Mengembalikan array error CI bila gagal,
	 * atau null bila sukses (dipakai oleh show_json_error di sisi klien).
	 */
	public function PasswordModify()
	{
		$this->db->trans_start();

		$UserID        = $this->input->post('UserID') ?: $this->UserID;
		$PasswordOld   = (string) $this->input->post('PasswordOld');
		$PasswordNew   = (string) $this->input->post('PasswordNew');
		$PasswordVerify = (string) $this->input->post('PasswordVerify');

		if ($PasswordNew === '' || $PasswordNew !== $PasswordVerify) {
			return array('code' => 400, 'message' => 'Verifikasi password baru tidak cocok.');
		}

		$row = $this->db->get_where('tb_users', array('UserID' => $UserID))->row_array();
		if (empty($row)) {
			return array('code' => 404, 'message' => 'Akun tidak ditemukan.');
		}

		$storedHash = (string) $row['UserPassword'];
		$validOld = false;
		if ($storedHash !== '') {
			if (strpos($storedHash, '$2') === 0) {
				$validOld = password_verify($PasswordOld, $storedHash);
			} else {
				$validOld = (md5($PasswordOld) === $storedHash);
			}
		}
		if (!$validOld) {
			return array('code' => 401, 'message' => 'Password lama salah.');
		}

		$this->db->where('UserID', $UserID);
		$this->db->update('tb_users', array('UserPassword' => md5($PasswordNew)));

		if ($this->db->error()['code'] != 0) {
			return $this->db->error();
		}
		$this->db->trans_complete();
		return null;
	}
}
