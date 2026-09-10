<?php
defined('BASEPATH') or exit('No direct script access allowed');
class FrontPage extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->M_Menu = new M_Menu;
		$this->load->model('M_Frontpage');
		$this->load->model('M_Manajemen_approval');
		$this->data = array(
			'AppConfig' => $this->M_Menu->KonfigurasiAppGetData(),
			'data' => ''
		);
	}

	public function Index() {
		$this->load->view('frontpage/main2', $this->data);
	}
	public function ListResult() {
		$rows = $this->M_Frontpage->ListResult();
		$out = array();
		foreach ($rows as $r) {
			switch ($r['KegiatanStatus']) {
				case 'editable':
					$badge = '<span class="badge-soft-warning">'.svgico('edit', 13).' Draf</span>'; break;
				case 'Approval OnProgress':
					$badge = '<span class="badge-soft-info">'.svgico('clock', 13).' Dalam Proses</span>'; break;
				case 'Approval Selesai':
					$badge = '<span class="badge-soft-success">'.svgico('approval-check', 13).' Selesai</span>'; break;
				default:
					$badge = '<span class="badge-soft-danger">'.svgico('warning', 13).' '.$r['KegiatanStatus'].'</span>';
			}
			$out[] = array(
				'KegiatanID'  => $r['KegiatanID'],
				'nama'        => $r['text'],
				'kegiatan'    => trim($r['title2'].$r['text2']),
				'sptjb'       => $r['KegiatanNoSPTJB'] !== '' ? $r['KegiatanNoSPTJB'] : '-',
				'status'      => $badge,
				'status_raw'  => $r['KegiatanStatus'],
			);
		}
		header('Content-Type: application/json');
		echo json_encode(array('data' => $out));
	}
	public function ListStatus() {
		$KegiatanID = $this->input->get_post('KegiatanID');
		$kegiatan = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID)['Kegiatan'];
		if (empty($kegiatan)) { show_404(); return; }
		$this->data['data'] = $kegiatan[0];
		$this->data['history'] = $this->M_Manajemen_approval->GetFormInfoKegiatan($KegiatanID)['history'];
		$this->data['last_status'] = $this->M_Manajemen_approval->GetLastStatus($KegiatanID);
		$this->data['sla'] = $this->M_Manajemen_approval->KegiatanSlaEval($KegiatanID);
		$this->load->view('frontpage/ListStatus', $this->data);
	}
}
