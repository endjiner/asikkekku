<?php

class M_Manajemen_app extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->DB_lib = new DB_lib;
		$this->UserID = $this->session->userdata('UserID');
		$this->UserName = $this->session->userdata('UserName');
		$this->serGroupID = $this->session->userdata('serGroupID');
		$this->date_time_now = date('Y-m-d H:i:s');
	}

	/** SELECT dasar (alias kolom = key DataTables) untuk server-side paging. */
	public function userGroupGetListSql()
	{
		return "SELECT ROW_NUMBER() OVER (ORDER BY UserGroupID) `No`,
					UserGroupID,
					UserGroupName AS Nama,
					UserGroupNote AS Note
				FROM tb_users_group g";
	}

	/** Tambahkan kolom Action untuk baris di halaman aktif. */
	public function userGroupDecorateRows($rows)
	{
		$out = array();
		foreach ($rows as $value) {
			$value['Action'] =
				'<button type="button" class="btn btn-primary btn-xs mr-1 GroupUserEdit"
					data-toggle="modal" data-target="#modal-xl-UserGroup" data="'.$value['UserGroupID'].'">
					'.svgico('edit',14).'
				</button>'
				.'<button type="button" class="btn btn-danger btn-xs mr-1 GroupUserDelete"
					data="'.$value['UserGroupID'].'">
					'.svgico('trash',14).'
				</button>';
			$out[] = $value;
		}
		return $out;
	}

	/** Daftar lengkap tanpa paging -- dipertahankan untuk pemakai lama. */
	public function userGroupGetList()
	{
		$rows = $this->db->query($this->userGroupGetListSql())->result_array();
		return $this->userGroupDecorateRows($rows);
	}
	public function userGroupGetData($UserGroupID = null)
	{
		$filter = '';
		$bind = array();
		if (!is_null($UserGroupID)) {
			$filter .= "AND UserGroupID = ? ";
			$bind[] = $UserGroupID;
		}

		$sql = "SELECT ROW_NUMBER() OVER (PARTITION BY UserGroupName ) row_num, ug.*
		FROM tb_users_group ug WHERE 1=1  ".$filter;
		$query 	= $this->db->query($sql, $bind);
		$result['user_group'] = $query->result_array();

		$sql = "SELECT * FROM tb_menu_access WHERE 1=1 ".$filter;
		$query 	= $this->db->query($sql, $bind);
		$result['menu_access'] = $query->result_array();

		return $result;
	}
	public function userGroupModify()
	{
		$this->db->trans_start();

		// echo json_encode($this->input->post()); exit();
		$UserGroupID = $this->input->post("UserGroupID");
		$UserGroupName = $this->input->post("UserGroupName");
		$UserGroupNote = $this->input->post("UserGroupNote");
		$menu_access_id = $this->input->post("menu_access_id");
		$MenuID = $this->input->post("MenuID");
		$Status_R = $this->input->post("Status_R");
		$Status_C = $this->input->post("Status_C");
		$Status_U = $this->input->post("Status_U");
		$Status_D = $this->input->post("Status_D");

		$data = array(
			'UserGroupName' => ucwords($UserGroupName),
			'UserGroupNote' => strtoupper($UserGroupNote),
		);
		if ($UserGroupID == '') {
			$this->db->insert('tb_users_group', $data);
			$UserGroupID = $this->db->insert_id();
		} else {
			$this->Auth->cekMenu("2100", 'u');
			$this->db->where('UserGroupID', $UserGroupID);
			$this->db->update('tb_users_group', $data);
		}

		// echo $this->db->last_query(); 
		for ($i=0; $i < count($menu_access_id); $i++) { 
			$data = array(
				'UserGroupID' => $UserGroupID,
				'MenuID' => $MenuID[$i],
				'Status_C' => $Status_C[$i],
				'Status_R' => $Status_R[$i],
				'Status_U' => $Status_U[$i],
				'Status_D' => $Status_D[$i],
			);

			if ($menu_access_id[$i] == '0') {
				$this->db->insert('tb_menu_access', $data);
			} else {
				$this->db->where('MenuAccessID', $menu_access_id[$i]);
				$this->db->update('tb_menu_access', $data);
			}
		}
		// echo json_encode($data); exit();

		if ($this->db->error()['code'] != 0 ) {
			return $this->db->error();
		}
		$this->db->trans_complete();
	}
	public function userGroupDelete()
	{
		$this->db->trans_start();

		$UserGroupID = $this->input->post("UserGroupID");
		$query = $this->db->get_where('tb_users', array('UserGroupID' => $UserGroupID));
		if (count($query->result_array()) > 0) {
			$this->Auth->alert_error_response('gagal delete,<br>ada data user dengan group ini !');
		}

		$this->db->where('UserGroupID', $UserGroupID);
		$this->db->delete('tb_users_group');

		$this->db->where('UserGroupID', $UserGroupID);
		$this->db->delete('tb_menu_access');

		$this->db->trans_complete();
	}

	// -------------------------------------------------
	/** SELECT dasar (alias kolom = key DataTables) untuk server-side paging. */
	public function userGetListSql()
	{
		return "SELECT ROW_NUMBER() OVER (ORDER BY u.UserID) `No`,
					u.UserID, u.UserName, u.UserFullName, u.UserPosition,
					u.UserPhone, u.UserNote, u.UserAktif
				FROM tb_users u";
	}

	/** Tambahkan kolom Status (ikon aktif/nonaktif) + Action. */
	public function userDecorateRows($rows)
	{
		$out = array();
		foreach ($rows as $value) {
			$value['Status'] = ($value['UserAktif'] == 1)
				? svgico('approval-check', 18, 'text-green')
				: svgico('reject', 18);
			$value['Action'] =
				'<button type="button" class="btn btn-primary btn-xs mr-1 UserEdit" title="Ubah Data"
					data-toggle="modal" data-target="#modal-l-User" data="'.$value['UserID'].'">
					'.svgico('edit',14).'
				</button>';
			$out[] = $value;
		}
		return $out;
	}

	/** Daftar lengkap tanpa paging -- dipertahankan untuk pemakai lama. */
	public function userGetList()
	{
		$rows = $this->db->query($this->userGetListSql())->result_array();
		return $this->userDecorateRows($rows);
	}
	public function userGetData($UserID = null)
	{
		$filter = '';
		$bind = array();
		if (!is_null($UserID)) {
			$filter .= "AND UserID = ? ";
			$bind[] = $UserID;
		}

		$sql = "SELECT ROW_NUMBER() OVER (ORDER BY u.UserID) row_num,
				u.*, u.UserFullName AS PegawaiNama
				FROM tb_users u
				WHERE 1=1 ".$filter;
		$query 	= $this->db->query($sql, $bind);
		$result['user'] = $query->result_array();

		return $result;
	}
	public function userModify()
	{
		$this->db->trans_start();

		// echo json_encode($this->input->post()); exit();
		$UserID = $this->input->post('UserID');
		$UserName = $this->input->post('UserName');
		$UserFullName = $this->input->post('UserFullName');
		$UserPosition = $this->input->post('UserPosition');
		$UserNote = $this->input->post('UserNote');
		$UserPassword = $this->input->post('UserPassword');
		$UserPhone = $this->input->post('UserPhone');
		$UserAktif = $this->input->post('UserAktif');
		$PegawaiID = $this->input->post('PegawaiID');

		$data = array(
			'UserID' => $UserID,
			'UserName' => $UserName,
			'UserFullName' => $UserFullName,
			'UserPosition' => $UserPosition,
			'UserNote' => $UserNote,
			'UserPhone' => $UserPhone,
			'UserAktif' => $UserAktif, 
			'PegawaiID' => $PegawaiID, 
		);
		if ($UserPassword != '') {
			// Verifikasi 2 langkah: "Ulangi Kata Sandi Baru" wajib sama.
			$verify = (string) $this->input->post('UserPasswordVerify');
			if (strlen($UserPassword) < 4) {
				$this->db->trans_complete();
				return array('code' => 1, 'message' => 'Kata sandi baru minimal 4 karakter.');
			}
			if ($UserPassword !== $verify) {
				$this->db->trans_complete();
				return array('code' => 1, 'message' => 'Verifikasi kata sandi baru tidak cocok.');
			}
			$data['UserPassword'] = md5($UserPassword);
		}

		if ($UserID == '') {
			$this->db->insert('tb_users', $data);
		} else {
			$this->Auth->cekMenu("2200", 'u');
			$this->db->where('UserID', $UserID);
			$this->db->update('tb_users', $data);
		}
		// echo json_encode($data); exit();

		if ($this->db->error()['code'] != 0 ) {
			return $this->db->error();
		}
		$this->db->trans_complete();
	}
	public function userDelete()
	{
		$this->db->trans_start();

		$UserID = $this->input->post("UserID");
		$this->db->where('UserID', $UserID);
		$this->db->delete('tb_users');

		$this->db->trans_complete();
	}

	// -------------------------------------------------
	public function KonfigurasiAppGetData()
	{
		$vrbl = array(
			'app_title',
			'app_description',
			'logo_big',
			'logo_small',
			'cover_logo',
			'cover_description',
			'link_panduan',
			'link_anggaran',
			'sla_total_hk',
			'sla_warn_total_hk',
			'sla_warn_stage_hk',
		);
		$this->db->where_in('VrblName', $vrbl);
		$query = $this->db->get('tb_vrbl')->result_array();
		$result = array();
		foreach ($query as $key => $value) {
			$result[$value['VrblName']] = $value['VrblValue'];
		}
		return $result;
	}
	public function KonfigurasiAppModify()
	{
		$this->db->trans_start();

		$app_title = $this->input->post('app_title');
		$app_description = $this->input->post('app_description');
		$cover_description = $this->input->post('cover_description');
		$link_panduan = $this->input->post('link_panduan');
		$link_anggaran = $this->input->post('link_anggaran');

		$this->db->set('VrblValue', $app_title);
		$this->db->where('VrblName', 'app_title');
		$this->db->update('tb_vrbl');

		$this->db->set('VrblValue', $app_description);
		$this->db->where('VrblName', 'app_description');
		$this->db->update('tb_vrbl'); 
		
		$this->db->set('VrblValue', $cover_description);
		$this->db->where('VrblName', 'cover_description');
		$this->db->update('tb_vrbl');  
		
		$this->db->set('VrblValue', $link_panduan);
		$this->db->where('VrblName', 'link_panduan');
		$this->db->update('tb_vrbl');  
		
		$this->db->set('VrblValue', $link_anggaran);
		$this->db->where('VrblName', 'link_anggaran');
		$this->db->update('tb_vrbl');

		// Ambang batas Peringatan Dini (SLA 4HK). Baris mungkin belum ada
		// di tb_vrbl (tabel tanpa PK), jadi cek dulu lalu insert/update.
		$sla = array(
			'sla_total_hk'      => (int) $this->input->post('sla_total_hk'),
			'sla_warn_total_hk' => (int) $this->input->post('sla_warn_total_hk'),
			'sla_warn_stage_hk' => (int) $this->input->post('sla_warn_stage_hk'),
		);
		foreach ($sla as $name => $val) {
			if ($val <= 0) continue;
			$this->_vrbl_set($name, $val);
		}

		$this->db->trans_complete();
	}
	private function _vrbl_set($name, $value)
	{
		$exists = $this->db->get_where('tb_vrbl', array('VrblName' => $name))->num_rows() > 0;
		if ($exists) {
			$this->db->set('VrblValue', $value);
			$this->db->where('VrblName', $name);
			$this->db->update('tb_vrbl');
		} else {
			$this->db->insert('tb_vrbl', array('VrblName' => $name, 'VrblValue' => $value));
		}
	}
	public function ApprovalFlowGetData($jenisID = 1)
	{
		$this->db->where('JenisID', (int) $jenisID);
		$this->db->order_by('FlowOrder', 'ASC');
		$query = $this->db->get('tb_approval_flow')->result_array();
		return $query;
	}
	/** Semua jenis pengajuan (aktif + nonaktif) untuk selector editor alur. */
	public function JenisPengajuanList()
	{
		if (!$this->db->table_exists('tb_jenis_pengajuan')) {
			return array(array('JenisID' => 1, 'JenisNama' => 'Perjalanan Dinas (LS)', 'JenisAktif' => 1));
		}
		return $this->db->order_by('JenisUrutan', 'ASC')->order_by('JenisNama', 'ASC')
			->get('tb_jenis_pengajuan')->result_array();
	}
	public function KonfigurasiAppFlowOrder()
	{
		$this->db->trans_start();

		// echo json_encode($this->input->post()); exit();
		$jenisID = (int) $this->input->post('JenisID');
		if ($jenisID <= 0) $jenisID = 1;
		$id = $this->input->post('ID');
		$active = $this->input->post('active');

		$no = 0;
		for ($i=0; $i < count($id); $i++) {
			if ($active[$i] != '0') {
				$no += 1;
				$this->db->set('FlowOrder', $no);
			} else {
				$this->db->set('FlowOrder', 0);
			}
			// Batasi ke jenis yang sedang diedit -> ID asing hasil tamper tak bisa dinomori ulang.
			$this->db->where('ID', $id[$i]);
			$this->db->where('JenisID', $jenisID);
			$this->db->update('tb_approval_flow');
		}

		$this->db->trans_complete();
	}
}
