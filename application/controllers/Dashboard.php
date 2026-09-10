<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends App_Controller {

	private $menu_kode;

	function __construct()
	{
		parent::__construct();
		$this->menu_kode = "1000";
		$this->load->model('M_Dashboard');
		$this->load->model('M_Manajemen_approval');
		$this->bootData($this->menu_kode);
	}

	function index()
	{
		$this->data['data_all'] = $this->M_Dashboard->GetDataAll();
		$this->data['quick_actions'] = $this->M_Dashboard->GetQuickActions();
		$action_total = 0;
		$this->data['action_items'] = $this->M_Dashboard->GetActionItems(60, $action_total);
		$this->data['action_items_total'] = $action_total;
		$this->data['early_warnings'] = $this->M_Dashboard->GetEarlyWarnings();
		$this->load->model('M_Manajemen_approval');
		$this->data['pipeline'] = $this->M_Manajemen_approval->PipelineByStage(1);
		$this->data['kpi_bulan'] = $this->M_Manajemen_approval->MonthlyKpi(1);
		$this->data['body'] = 'dashboard/index';
		$this->data['footer'] = 'dashboard/indexFooter';
		$this->load->view('main', $this->data);
	}
}
