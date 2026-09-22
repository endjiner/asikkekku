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

    public function GetMenu($parent = 0, $UserGroupID = 0, $menu_arr = [])
    {
        $UserGroupID = $this->UserGroupID;
        $sql         = "SELECT *
                FROM tb_menu_access ma
                LEFT JOIN tb_menu m ON ma.MenuID = m.MenuID
                WHERE m.MenuParent = ?
                AND ma.UserGroupID = ?
                AND ma.Status_R = '1'
                ORDER BY m.MenuOrder";
        $query = $this->db->query($sql, [(int) $parent, $UserGroupID]);

        $rowcount = $query->getNumRows();
        if ($rowcount > 0) {
            foreach ($query->getResultArray() as $key => $value) {
                $menu_arr[$value['MenuKode']] = $value;
                $c_bfr                        = count($menu_arr);
                $menu_arr                     = $this->GetMenu($value['MenuID'], $UserGroupID, $menu_arr);
                $menu_arr[$value['MenuKode']]['parent'] = (count($menu_arr) == $c_bfr) ? false : true;
            }
        }

        return $menu_arr;
    }

    public function GetMenuAll($parent = 0, $menu_arr = [], $level = '')
    {
        $sql   = 'SELECT * FROM tb_menu WHERE MenuParent = ? ORDER BY MenuOrder';
        $query = $this->db->query($sql, [(int) $parent]);

        $rowcount = $query->getNumRows();
        if ($rowcount > 0) {
            foreach ($query->getResultArray() as $key => $value) {
                $menu_arr[$value['MenuKode']]          = $value;
                $menu_arr[$value['MenuKode']]['level'] = $level;
                $c_bfr                                 = count($menu_arr);
                $menu_arr                               = $this->GetMenuAll($value['MenuID'], $menu_arr, $level . ' -- ');
                $menu_arr[$value['MenuKode']]['parent'] = (count($menu_arr) == $c_bfr) ? false : true;
            }
        }

        return $menu_arr;
    }

    public function KonfigurasiAppGetData()
    {
        return model(M_Manajemen_app::class)->KonfigurasiAppGetData();
    }

    public function position_list()
    {
        $names = ['position'];

        return $this->db->table('tb_vrbl')->whereIn('VrblName', $names)->get()->getResultArray();
    }
}
