<?php

namespace App\Controllers;

use App\Libraries\Auth;
use App\Models\M_Login;
use App\Models\M_Menu;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class MainPage extends BaseController
{
    /** @var Auth */
    private $Auth;

    /** @var M_Menu */
    private $M_Menu;

    /** @var M_Login */
    private $M_Login;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->Auth    = new Auth();
        $this->M_Menu  = model(M_Menu::class);
        $this->M_Login = model(M_Login::class);
    }

    public function Index()
    {
        $this->Auth->cekLogin();
        legacy_redirect(base_url('Dashboard'));
    }

    public function Login()
    {
        $this->session->destroy();
        $data['AppConfig'] = $this->M_Menu->KonfigurasiAppGetData();

        return view('auth/index', $data);
    }

    public function VerifyLogin()
    {
        $username = $this->request->getPost('Username');
        $password = $this->request->getPost('Password');

        $result = $this->M_Login->Login($username, $password);

        if (empty($result)) {
            $this->session->set(['error_info' => 'LOGIN ERROR,<br>Incorrect Username or Password !']);
            legacy_redirect(base_url('MainPage/Login'));
        } else {
            $UserID       = $result[0]['UserID'];
            $UserGroupID  = $result[0]['UserGroupID'];
            $UserFullName = (empty($result[0]['PegawaiNama'])) ? $result[0]['UserFullName'] : $result[0]['PegawaiNama'];
            $UserPosition = $result[0]['UserPosition'];

            $this->session->set('UserID', $UserID);
            $this->session->set('UserGroupID', $UserGroupID);
            $this->session->set('UserFullName', $UserFullName);
            $this->session->set('UserPosition', $UserPosition);
            legacy_redirect(base_url('MainPage/Index'));
        }
    }

    public function Logout()
    {
        $this->session->destroy();
        legacy_redirect(base_url('MainPage/Login'));
    }

    public function NotFound()
    {
        $this->Auth->cekLogin();
        $menu_kode = '1000';
        $this->Auth->cekMenu($menu_kode, 'r');

        $menu_list = $this->M_Menu->GetMenu();
        $this->data = [
            'UserID'       => $this->session->get('UserID'),
            'UserGroupID'  => $this->session->get('UserGroupID'),
            'UserName'     => $this->session->get('UserName'),
            'UserFullName' => $this->session->get('UserFullName'),
            'UserPosition' => $this->session->get('UserPosition'),
            'AppConfig'    => $this->M_Menu->KonfigurasiAppGetData(),
            'error_info'   => $this->session->get('error_info'),
            'menu_list'    => $menu_list,
            'menu_detail'  => $menu_list[$menu_kode],
            'data'         => '',
            'body'         => 'dashboard/NotFound',
        ];

        return view('main', $this->data);
    }
}
