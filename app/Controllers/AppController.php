<?php

namespace App\Controllers;

use App\Libraries\Auth;
use App\Models\M_Menu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Dulu application/core/MY_Controller.php (App_Controller) -- boilerplate
 * yang diulang di tiap controller area login (cek login, M_Menu, susun
 * $this->data). Semua controller area login extend AppController.
 *
 *   class Dashboard extends AppController {
 *     public function initController($request, $response, $logger) {
 *       parent::initController($request, $response, $logger);
 *       $this->bootData('1000');
 *     }
 *   }
 */
abstract class AppController extends BaseController
{
    /** @var Auth */
    protected $Auth;

    /** @var M_Menu */
    protected $Menu;

    /** Alias kompatibilitas kode lama ($this->M_Menu). */
    protected $M_Menu;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->Menu   = model(M_Menu::class);
        $this->M_Menu = $this->Menu;
        $this->Auth   = new Auth();
        $this->Auth->cekLogin();
    }

    /**
     * Susun $this->data standar. Panggil sekali di constructor turunan.
     *
     * @param string|null $menu_kode kalau diisi: cek akses 'r' + sertakan menu_detail
     *
     * @return array<string, mixed>
     */
    protected function bootData(?string $menu_kode = null): array
    {
        $menu_list = $this->Menu->GetMenu();

        if ($menu_kode !== null) {
            $this->Auth->cekMenu($menu_kode, 'r');
        }

        $this->data = [
            'UserID'       => $this->session->get('UserID'),
            'UserGroupID'  => $this->session->get('UserGroupID'),
            'UserName'     => $this->session->get('UserName'),
            'UserFullName' => $this->session->get('UserFullName'),
            'UserPosition' => $this->session->get('UserPosition'),
            'AppConfig'    => $this->Menu->KonfigurasiAppGetData(),
            'error_info'   => $this->session->get('error_info'),
            'menu_list'    => $menu_list,
            'menu_detail'  => ($menu_kode !== null && isset($menu_list[$menu_kode])) ? $menu_list[$menu_kode] : null,
            'data'         => '',
        ];

        return $this->data;
    }

    /** Shortcut: user yang login SuperAdmin? */
    protected function isSuperAdmin(): bool
    {
        return $this->session->get('UserPosition') === 'SuperAdmin';
    }
}
