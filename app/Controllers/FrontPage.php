<?php

namespace App\Controllers;

use App\Models\M_Frontpage;
use App\Models\M_Manajemen_approval;
use App\Models\M_Menu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FrontPage extends BaseController
{
    /** @var M_Menu */
    private $M_Menu;

    /** @var M_Frontpage */
    private $M_Frontpage;

    /** @var M_Manajemen_approval */
    private $M_Manajemen_approval;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->M_Menu               = model(M_Menu::class);
        $this->M_Frontpage           = model(M_Frontpage::class);
        $this->M_Manajemen_approval = model(M_Manajemen_approval::class);
        $this->data = [
            'AppConfig' => $this->M_Menu->KonfigurasiAppGetData(),
            'data'      => '',
        ];
    }

    public function Index()
    {
        return view('frontpage/main2', $this->data);
    }

    public function ListResult()
    {
        $rows = $this->M_Frontpage->ListResult();
        $out  = [];
        foreach ($rows as $r) {
            switch ($r['KegiatanStatus']) {
                case 'editable':
                    $badge = '<span class="badge-soft-warning">' . svgico('edit', 13) . ' Draf</span>';
                    break;
                case 'Approval OnProgress':
                    $badge = '<span class="badge-soft-info">' . svgico('clock', 13) . ' Dalam Proses</span>';
                    break;
                case 'Approval Selesai':
                    $badge = '<span class="badge-soft-success">' . svgico('approval-check', 13) . ' Selesai</span>';
                    break;
                default:
                    $badge = '<span class="badge-soft-danger">' . svgico('warning', 13) . ' ' . $r['KegiatanStatus'] . '</span>';
            }
            $out[] = [
                'KegiatanID' => $r['KegiatanID'],
                'nama'       => $r['text'],
                'kegiatan'   => trim($r['title2'] . $r['text2']),
                'sptjb'      => $r['KegiatanNoSPTJB'] !== '' ? $r['KegiatanNoSPTJB'] : '-',
                'status'     => $badge,
                'status_raw' => $r['KegiatanStatus'],
            ];
        }
        $this->response->setContentType('application/json')->setBody(json_encode(['data' => $out]));
    }

    public function ListStatus()
    {
        $KegiatanID = legacy_get_post('KegiatanID');
        $kegiatan   = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID)['Kegiatan'];
        if (empty($kegiatan)) {
            show_404();

            return;
        }
        $this->data['data']        = $kegiatan[0];
        $this->data['history']     = $this->M_Manajemen_approval->GetFormInfoKegiatan($KegiatanID)['history'];
        $this->data['last_status'] = $this->M_Manajemen_approval->GetLastStatus($KegiatanID);
        $this->data['sla']         = $this->M_Manajemen_approval->KegiatanSlaEval($KegiatanID);

        return view('frontpage/ListStatus', $this->data);
    }
}
