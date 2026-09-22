<?php

namespace App\Controllers;

use App\Models\M_Manajemen_approval;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Cron extends BaseController
{
    /** @var M_Manajemen_approval */
    private $M_Manajemen_approval;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->M_Manajemen_approval = model(M_Manajemen_approval::class);
    }

    public function index()
    {
        show_404();
    }

    public function early_warning()
    {
        $key = (string) $this->request->getGet('key');

        $expected = '';
        $row      = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'ew_cron_key'])->getRowArray();
        if (! empty($row)) {
            $expected = (string) $row['VrblValue'];
        }

        $isSuperAdmin = ($this->session->get('UserPosition') === 'SuperAdmin');
        if (! $isSuperAdmin && ($expected === '' || ! hash_equals($expected, $key))) {
            return $this->jsonResponse(['status' => 0, 'message' => 'Kunci tidak valid.'], 403);
        }

        $res = $this->M_Manajemen_approval->EarlyWarningKirim('cron');

        return $this->jsonResponse(['status' => 1, 'waktu' => date('Y-m-d H:i:s')] + $res);
    }
}
