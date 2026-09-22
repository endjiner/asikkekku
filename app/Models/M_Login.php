<?php

namespace App\Models;

class M_Login extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function PasswordMatches($password, $storedHash)
    {
        $password   = (string) $password;
        $storedHash = (string) $storedHash;

        if ($storedHash === '') {
            return false;
        }

        if (strpos($storedHash, '$2') === 0) {
            return password_verify($password, $storedHash);
        }

        // Hash lama md5() -- masih diterima, tapi login akan meng-upgrade-nya
        // ke bcrypt secara transparan (lihat Login()).
        return md5($password) === $storedHash;
    }

    /** True kalau hash masih format lama (perlu di-upgrade ke bcrypt). */
    public static function IsLegacyHash($storedHash)
    {
        return strpos((string) $storedHash, '$2') !== 0;
    }

    public function Login($username, $password)
    {
        $username = trim((string) $username);
        if ($username === '') {
            return [];
        }

        $prefix       = (defined('DB_SEPERADIK') && DB_SEPERADIK !== '') ? DB_SEPERADIK . '.' : '';
        $target_table = (defined('DB_SEPERADIK') && DB_SEPERADIK !== '') ? DB_SEPERADIK . '.tb_pegawai' : 'tb_pegawai';

        $b = $this->db->table('tb_users u');
        $b->select('u.*');
        $b->where('u.UserName', $username);
        $b->where('u.UserAktif', 1);

        if ($this->db->tableExists($target_table) || $this->db->tableExists('tb_pegawai')) {
            $b->select('p.PegawaiNama', false);
            $b->join($prefix . 'tb_pegawai p', 'p.PegawaiID = u.PegawaiID', 'left');
        } else {
            $b->select('u.UserFullName AS PegawaiNama', false);
        }

        $rows    = $b->get()->getResultArray();
        $matches = [];
        foreach ($rows as $row) {
            if (self::PasswordMatches($password, $row['UserPassword'])) {
                // Upgrade transparan md5 -> bcrypt saat login berhasil.
                if (self::IsLegacyHash($row['UserPassword'])) {
                    $new = password_hash($password, PASSWORD_DEFAULT);
                    $this->db->table('tb_users')->where('UserID', $row['UserID'])->update(['UserPassword' => $new]);
                }
                $row['PegawaiNama'] = empty($row['PegawaiNama']) ? $row['UserFullName'] : $row['PegawaiNama'];
                $matches[]          = $row;
            }
        }

        return $matches;
    }
}
