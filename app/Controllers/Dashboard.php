<?php

namespace App\Controllers;

use App\Models\M_Dashboard;
use App\Models\M_Manajemen_approval;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Dashboard extends AppController
{
    private $menu_kode;

    /** @var M_Dashboard */
    private $M_Dashboard;

    /** @var M_Manajemen_approval */
    private $M_Manajemen_approval;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->menu_kode           = '1000';
        $this->M_Dashboard         = model(M_Dashboard::class);
        $this->M_Manajemen_approval = model(M_Manajemen_approval::class);
        $this->bootData($this->menu_kode);
    }

    public function index()
    {
        $this->data['data_all']      = $this->M_Dashboard->GetDataAll();
        $this->data['quick_actions'] = $this->M_Dashboard->GetQuickActions();
        $action_total                = 0;
        $this->data['action_items']       = $this->M_Dashboard->GetActionItems(60, $action_total);
        $this->data['action_items_total'] = $action_total;
        $this->data['early_warnings']     = $this->M_Dashboard->GetEarlyWarnings();
        $this->data['pj_summary']         = $this->M_Dashboard->GetPjSummary();
        $this->data['pipeline']  = $this->M_Manajemen_approval->PipelineByStage(1);
        $this->data['kpi_bulan'] = $this->M_Manajemen_approval->MonthlyKpi(1);
        $this->data['body']      = 'dashboard/index';
        $this->data['footer']    = 'dashboard/indexFooter';

        return view('main', $this->data);
    }
}
