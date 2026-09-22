<?php

namespace App\Models;

use App\Libraries\Auth;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Model;
use CodeIgniter\Session\Session;

/**
 * Base untuk semua model yang diporting dari CI3 (dulu extends CI_Model).
 *
 * CI_Model::__get() di CI3 meneruskan properti yang tak dikenal ($this->
 * input, $this->session, $this->Auth, dst.) ke superobject global lewat
 * get_instance(). CI4 tidak punya superobject seperti itu, jadi
 * disediakan eksplisit di sini supaya kode model lama ($this->input->
 * post(...), $this->session->userdata(...), $this->Auth->cekMenu(...))
 * tetap jalan dengan perubahan minimal di tiap model (hanya rename method,
 * bukan restrukturisasi akses).
 */
abstract class BaseModel extends Model
{
    protected ?IncomingRequest $request = null;

    protected ?Session $session = null;

    protected ?Auth $Auth = null;

    public function __construct()
    {
        parent::__construct();

        $req            = service('request');
        $this->request  = ($req instanceof IncomingRequest) ? $req : null;
        $this->session  = service('session');
        $this->Auth     = new Auth();
    }
}
