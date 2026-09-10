<?php

class M_Frontpage extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->DB_lib = new DB_lib;
		$this->date_time_now = date('Y-m-d H:i:s');
	}

	public function ListResult()
	{
		// echo json_encode($this->input->get()); exit();
		$order_by = $this->input->get('order_by');
		$order_sort = $this->input->get('order_sort');
		$search_text = trim((string) $this->input->get('search_text'));
		if ($search_text === '') {
			return array();
		}
		$type_result = $this->input->get('type_result');
		$type_search = $this->input->get('type_search');
		$text = '';
		$text2 = '';
		$title = '';
		$title2 = '';
		$filter = '';
		$sort = '';
		$bind = array();
		// Halaman publik (tanpa login) -> nilai pencarian WAJIB lewat binding (?),
		// jangan pernah ditempel langsung ke string SQL. Kolom & arah urut
		// dipilih dari daftar tetap (whitelist).
		$like = '%' . $search_text . '%';

		switch ($type_result) {
			case ($type_result == 'kuitansi' && $type_search == 'no_sptb'):
				$text = 'KegiatanNoSPTJB as text';
				$text2 = 'KegiatanJudul as text2';
				$title2 = '"Judul Kegiatan : " as title2';
				$filter .= "AND KegiatanNoSPTJB LIKE ? ";
				$bind[] = $like;
				break;
			case ($type_result == 'kuitansi' && $type_search == 'no_surat'):
				$text = 'KegiatanNoSuratTugas as text';
				$text2 = 'KegiatanJudul as text2';
				$title2 = '"Judul Kegiatan : " as title2';
				$filter .= "AND KegiatanNoSuratTugas LIKE ? ";
				$bind[] = $like;
				break;
			case ($type_result == 'kuitansi' && $type_search == 'judul_kegiatan'):
				$text = 'KegiatanJudul as text';
				$text2 = 'KegiatanNamaPelaksana as text2';
				$title2 = '"Nama Petugas : " as title2';
				$filter .= "AND KegiatanJudul LIKE ? ";
				$bind[] = $like;
				break;
			case ($type_result == 'kuitansi' && $type_search == 'nama_petugas'):
				$text = 'KegiatanNamaPelaksana as text';
				$text2 = 'KegiatanJudul as text2';
				$title2 = '"Judul Kegiatan : " as title2';
				$filter .= "AND KegiatanNamaPelaksana LIKE ? ";
				$bind[] = $like;
				break;
			default:
				$filter .= '';
				break;
		}

		$dir = (strtoupper((string) $order_sort) === 'DESC') ? 'DESC' : 'ASC';
		switch ($order_by) {
			case 'tanggal':
				$sort .= "ORDER BY KegiatanTanggal $dir";
				break;
			case 'nama':
				$sort .= "ORDER BY KegiatanNamaPelaksana $dir";
				break;
		}

		$sql = "SELECT ROW_NUMBER() OVER (PARTITION BY KegiatanID ) row_num, k.*, $text, $text2, $title2
				FROM tb_kegiatan k
				WHERE k.KegiatanDeletedAt IS NULL ".$filter.$sort;
		$query 	= $this->db->query($sql, $bind);
		$result = $query->result_array();

		// echo json_encode($result); exit();
		return $result;
	}
}
