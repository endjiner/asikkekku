<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base controller — menaruh boilerplate yang dulu diulang di tiap controller
 * (cek login, M_Menu, susun $this->data). Semua controller area login sebaiknya
 * extend App_Controller.
 *
 *   class Dashboard extends App_Controller {
 *     function __construct() {
 *       parent::__construct();
 *       $this->bootData('1000');   // kode menu utk cek akses + menu_detail
 *     }
 *   }
 */
class App_Controller extends CI_Controller
{
	/** @var auth */
	protected $Auth;
	/** @var M_Menu */
	protected $Menu;

	public function __construct()
	{
		parent::__construct();
		$this->Menu   = new M_Menu;
		$this->M_Menu = $this->Menu;            // alias kompatibilitas kode lama
		$this->Auth   = new auth;
		$this->Auth->cekLogin();
	}

	/**
	 * Susun $this->data standar. Panggil sekali di constructor turunan.
	 *
	 * @param string|null $menu_kode  kalau diisi: cek akses 'r' + sertakan menu_detail
	 */
	protected function bootData($menu_kode = null)
	{
		$menu_list = $this->Menu->GetMenu();

		if ($menu_kode !== null) {
			$this->Auth->cekMenu($menu_kode, 'r');
		}

		$this->data = array(
			'UserID'       => $this->session->userdata('UserID'),
			'UserGroupID'  => $this->session->userdata('UserGroupID'),
			'UserName'     => $this->session->userdata('UserName'),
			'UserFullName' => $this->session->userdata('UserFullName'),
			'UserPosition' => $this->session->userdata('UserPosition'),
			'AppConfig'    => $this->Menu->KonfigurasiAppGetData(),
			'error_info'   => $this->session->userdata('error_info'),
			'menu_list'    => $menu_list,
			'menu_detail'  => ($menu_kode !== null && isset($menu_list[$menu_kode])) ? $menu_list[$menu_kode] : null,
			'data'         => '',
		);

		return $this->data;
	}

	/** Shortcut: user yang login SuperAdmin? */
	protected function isSuperAdmin()
	{
		return $this->session->userdata('UserPosition') === 'SuperAdmin';
	}
}
