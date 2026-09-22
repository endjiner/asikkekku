<?php

namespace App\Models;

class M_Menu extends BaseModel
{
    private $UserGroupID;

    public function __construct()
    {
        parent::__construct();
        $this->UserGroupID = $this->session->get('UserGroupID');
    }

    public function CheckMenu($UserGroupID, $MenuKode, $MenuAccess)
    {
        $sql        = 'SELECT ma.*
                FROM tb_menu_access ma
                LEFT JOIN tb_menu m ON ma.MenuID = m.MenuID
                WHERE ma.UserGroupID = ?
                AND m.MenuKode = ?';
        $resultMenu = $this->db->query($sql, [$UserGroupID, $MenuKode])->getRowArray();
        if (empty($resultMenu)) {
            return false;
        }
        $col = 'Status_' . strtoupper($MenuAccess);

        return isset($resultMenu[$col]) && $resultMenu[$col] == 1;
    }

    /**
     * Baris anak-menu langsung dari $parent.
     *   $filtered = true  -> GetMenu(): dibatasi tb_menu_access punya user
     *               (join tb_menu_access + tb_menu, UserGroupID + Status_R='1').
     *   $filtered = false -> GetMenuAll(): semua baris tb_menu apa adanya
     *               (dipakai admin lihat/susun struktur menu penuh).
     */
    private function _menuChildren(int $parent, bool $filtered): array
    {
        if ($filtered) {
            $sql  = "SELECT *
                    FROM tb_menu_access ma
                    LEFT JOIN tb_menu m ON ma.MenuID = m.MenuID
                    WHERE m.MenuParent = ?
                    AND ma.UserGroupID = ?
                    AND ma.Status_R = '1'
                    ORDER BY m.MenuOrder";
            $bind = [$parent, $this->UserGroupID];
        } else {
            $sql  = 'SELECT * FROM tb_menu WHERE MenuParent = ? ORDER BY MenuOrder';
            $bind = [$parent];
        }

        return $this->db->query($sql, $bind)->getResultArray();
    }

    public function GetMenu($parent = 0, $menu_arr = [])
    {
        foreach ($this->_menuChildren((int) $parent, true) as $value) {
            $menu_arr[$value['MenuKode']] = $value;
            $c_bfr                        = count($menu_arr);
            $menu_arr                     = $this->GetMenu($value['MenuID'], $menu_arr);
            $menu_arr[$value['MenuKode']]['parent'] = (count($menu_arr) == $c_bfr) ? false : true;
        }

        return $menu_arr;
    }

    public function GetMenuAll($parent = 0, $menu_arr = [], $level = '')
    {
        foreach ($this->_menuChildren((int) $parent, false) as $value) {
            $menu_arr[$value['MenuKode']]          = $value;
            $menu_arr[$value['MenuKode']]['level'] = $level;
            $c_bfr                                 = count($menu_arr);
            $menu_arr                               = $this->GetMenuAll($value['MenuID'], $menu_arr, $level . ' -- ');
            $menu_arr[$value['MenuKode']]['parent'] = (count($menu_arr) == $c_bfr) ? false : true;
        }

        return $menu_arr;
    }

    public function KonfigurasiAppGetData()
    {
        return model(M_Manajemen_app::class)->KonfigurasiAppGetData();
    }

    public function position_list()
    {
        return $this->db->table('tb_vrbl')->whereIn('VrblName', ['position'])->get()->getResultArray();
    }
}
