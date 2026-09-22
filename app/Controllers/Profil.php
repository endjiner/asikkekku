<?php

namespace App\Controllers;

use App\Models\M_Profil;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Profil -- hanya fitur Ganti Password akun (dipakai oleh modal di semua halaman).
 * Modul detail pegawai lama dihapus (tabel tb_pegawai tidak ada pada database ini).
 */
class Profil extends AppController
{
    /** @var M_Profil */
    private $M_Profil;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->M_Profil = model(M_Profil::class);
    }

    public function PasswordModify()
    {
        return $this->jsonResponse([
            'error'  => $this->M_Profil->PasswordModify(),
            'status' => $this->db->transStatus(),
        ]);
    }
}
