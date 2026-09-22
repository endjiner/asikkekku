<?php

namespace App\Models;

class M_Profil extends BaseModel
{
    private $UserID;

    public function __construct()
    {
        parent::__construct();
        $this->UserID = $this->session->get('UserID');
    }

    /**
     * Ganti password akun sendiri. Mengembalikan array error CI bila gagal,
     * atau null bila sukses (dipakai oleh show_json_error di sisi klien).
     */
    public function PasswordModify()
    {
        $this->db->transStart();

        $UserID         = $this->request->getPost('UserID') ?: $this->UserID;
        $PasswordOld    = (string) $this->request->getPost('PasswordOld');
        $PasswordNew    = (string) $this->request->getPost('PasswordNew');
        $PasswordVerify = (string) $this->request->getPost('PasswordVerify');

        if ($PasswordNew === '' || $PasswordNew !== $PasswordVerify) {
            return ['code' => 400, 'message' => 'Verifikasi password baru tidak cocok.'];
        }

        $row = $this->db->table('tb_users')->getWhere(['UserID' => $UserID])->getRowArray();
        if (empty($row)) {
            return ['code' => 404, 'message' => 'Akun tidak ditemukan.'];
        }

        $storedHash = (string) $row['UserPassword'];
        $validOld   = false;
        if ($storedHash !== '') {
            if (strpos($storedHash, '$2') === 0) {
                $validOld = password_verify($PasswordOld, $storedHash);
            } else {
                $validOld = (md5($PasswordOld) === $storedHash);
            }
        }
        if (! $validOld) {
            return ['code' => 401, 'message' => 'Password lama salah.'];
        }

        $this->db->table('tb_users')->where('UserID', $UserID)->update(['UserPassword' => md5($PasswordNew)]);

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();

        return null;
    }
}
