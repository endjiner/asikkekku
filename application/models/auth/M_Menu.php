<?php

class M_Menu extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->UserGroupID = $this->session->userdata('UserGroupID');
	}

	public function CheckMenu($UserGroupID, $MenuKode, $MenuAccess)
	{
		$sql = "SELECT ma.*
		        FROM tb_menu_access ma
		        LEFT JOIN tb_menu m ON ma.MenuID = m.MenuID
		        WHERE ma.UserGroupID = ?
		        AND m.MenuKode = ?";
		$resultMenu = $this->db->query($sql, array($UserGroupID, $MenuKode))->row_array();
		if (empty($resultMenu)) {
			return false;
		}
		$col = 'Status_' . strtoupper($MenuAccess);
		return isset($resultMenu[$col]) && $resultMenu[$col] == 1;
	}
	public function GetMenu($parent = 0, $UserGroupID = 0, $menu_arr = array())
	{
		$UserGroupID = $this->UserGroupID;
		$sql = "SELECT *
		        FROM tb_menu_access ma
		        LEFT JOIN tb_menu m ON ma.MenuID = m.MenuID
		        WHERE m.MenuParent = ?
		        AND ma.UserGroupID = ?
		        AND ma.Status_R = '1'
		        ORDER BY m.MenuOrder";
		$query 	= $this->db->query($sql, array((int) $parent, $UserGroupID));

		$rowcount = $query->num_rows();
		if ($rowcount > 0) {
			foreach ($query->result_array() as $key => $value) {
				$menu_arr[$value['MenuKode']] = $value;
				$c_bfr = count($menu_arr);
				$menu_arr = $this->GetMenu($value['MenuID'], $UserGroupID, $menu_arr);
				$menu_arr[$value['MenuKode']]['parent'] = (count($menu_arr) == $c_bfr) ? false : true ;
			};
		}
		return $menu_arr;
	}
	public function GetMenuAll($parent = 0, $menu_arr = array(), $level = '')
	{
		$sql = "SELECT * FROM tb_menu WHERE MenuParent = ? ORDER BY MenuOrder";
		$query 	= $this->db->query($sql, array((int) $parent));

		$rowcount = $query->num_rows();
		if ($rowcount > 0) {
			foreach ($query->result_array() as $key => $value) {
				$menu_arr[$value['MenuKode']] = $value;
				$menu_arr[$value['MenuKode']]['level'] = $level;
				$c_bfr = count($menu_arr);
				$menu_arr = $this->GetMenuAll($value['MenuID'], $menu_arr, $level.' -- ');
				$menu_arr[$value['MenuKode']]['parent'] = (count($menu_arr) == $c_bfr) ? false : true ;
			};
		}
		return $menu_arr;
	}
	public function KonfigurasiAppGetData()
	{
		$this->load->model('manajemen_app/M_Manajemen_app');
		return $this->M_Manajemen_app->KonfigurasiAppGetData();
	}
	public function position_list()
	{
		$names = array('position');
		$this->db->where_in('VrblName', $names);
		$query = $this->db->get('tb_vrbl')->result_array();
		return $query;
	} 
}
