<?php
/**
 * Sidebar menu. Ikon memakai ilustrasi flat lokal (illus()).
 * Item disaring per posisi user lewat role_can(menu_feature(kode)).
 */
function menu_icon_name($menuClass, $menuKode)
{
	$c = strtolower((string) $menuClass . ' ' . $menuKode);
	if (strpos($c, 'home') !== false || strpos($c, 'tachometer') !== false || strpos($c, 'dashboard') !== false || strpos($c, '1000') !== false) return 'dashboard';
	if (strpos($c, 'tools') !== false || strpos($c, 'cog') !== false || strpos($c, '2000') !== false) return 'config';
	if (strpos($c, 'users') !== false || strpos($c, '2100') !== false) return 'user-group';
	if (strpos($c, 'user') !== false || strpos($c, '2200') !== false) return 'users';
	if (strpos($c, '2300') !== false) return 'config';
	if (strpos($c, 'clipboard-list') !== false || strpos($c, '3100') !== false) return 'activity';
	if (strpos($c, 'clipboard-check') !== false || strpos($c, 'thumbs') !== false || strpos($c, '3200') !== false) return 'approval-inbox';
	if (strpos($c, 'scroll') !== false || strpos($c, 'chart') !== false || strpos($c, 'print') !== false || strpos($c, '3300') !== false) return 'report';
	if (strpos($c, '3000') !== false) return 'approval-check';
	return 'info';
}

function generate_menu($menu_list, $menu_parent = 0, $html = '', $level = 0)
{
	foreach ($menu_list as $value) {
		if ($value['MenuParent'] != $menu_parent) continue;

		$feature = menu_feature($value['MenuKode']);
		if ($feature !== null && !role_can($feature)) continue;

		$icon = menu_icon_name($value['MenuClass'], $value['MenuKode']);

		$html .= '<li class="nav-item">';
		$menu_href = $value['parent'] ? '#' : base_url().$value['MenuLink'];
		$menu_class = 'nav-link id_'.$value['MenuKode'].($value['parent'] ? ' nav-parent-toggle' : '');
		$html .= '<a href="'.$menu_href.'" class="'.$menu_class.'">';
		$html .= illus($icon, 20);
		$html .= '<p>'.$value['MenuName'].'</p>';
		if ($value['parent']) {
			// Caret submenu: pakai sprite SVG lokal (bukan icon-font).
			// Kelas .right dipertahankan supaya animasi putar bawaan
			// AdminLTE (menu-open -> rotate) tetap berjalan.
			$html .= svgico('chevron-left', 14, 'right nav-caret');
		}
		$html .= '</a>';
		if ($value['parent']) {
			$html .= '<ul class="nav nav-treeview">';
			$html .= generate_menu($menu_list, $value['MenuID'], '', $level + 1);
			$html .= '</ul>';
		}
		$html .= '</li>';
	}
	return $html;
}
?>

<nav class="mt-2">
  <?php /* data-widget="treeview" SENGAJA dihilangkan: buka/tutup submenu
           dikelola satu handler kustom di layouts/footer.php
           (.nav-parent-toggle). Kalau widget AdminLTE ikut aktif, kedua
           handler saling meniadakan saat menutup -> submenu "macet" terbuka. */ ?>
  <ul class="nav nav-pills nav-sidebar flex-column text-sm" role="menu">
    <?php echo generate_menu($menu_list); ?>
  </ul>
</nav>
