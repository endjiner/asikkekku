<?php
/**
 * Navigasi ATAS horizontal (pengganti sidebar). Data dari $menu_list.
 * Item induk -> dropdown; item tanpa anak -> tautan langsung.
 */
if (!function_exists('pkp_topmenu')) {
	function pkp_topmenu($menu_list, $activeKode)
	{
		$html = '';
		foreach ($menu_list as $v) {
			if ($v['MenuParent'] != 0) continue;
			$feat = menu_feature($v['MenuKode']);
			if ($feat !== null && !role_can($feat)) continue;

			$children = array();
			foreach ($menu_list as $c) {
				if ($c['MenuParent'] != $v['MenuID']) continue;
				$cf = menu_feature($c['MenuKode']);
				if ($cf !== null && !role_can($cf)) continue;
				$children[] = $c;
			}

			$active = ($v['MenuKode'] == $activeKode);
			foreach ($children as $c) if ($c['MenuKode'] == $activeKode) $active = true;
			$ac = $active ? ' active' : '';

			if (!empty($v['parent']) && $children) {
				$html .= '<li class="tm-item tm-has' . $ac . '">';
				$html .= '<button type="button" class="tm-link tm-toggle">' . html_escape($v['MenuName']) . svgico('chevron-left', 13, 'tm-caret') . '</button>';
				$html .= '<div class="tm-drop"><div class="tm-drop-inner">';
				foreach ($children as $c) {
					$sub = ($c['MenuKode'] == $activeKode) ? ' active' : '';
					$html .= '<a class="tm-sub' . $sub . '" href="' . base_url($c['MenuLink']) . '">' . html_escape($c['MenuName']) . '</a>';
				}
				$html .= '</div></div></li>';
			} else {
				$href = !empty($v['MenuLink']) ? base_url($v['MenuLink']) : '#';
				$html .= '<li class="tm-item' . $ac . '"><a class="tm-link" href="' . $href . '">' . html_escape($v['MenuName']) . '</a></li>';
			}
		}
		return $html;
	}
}
?>
<ul class="tm-list"><?php echo pkp_topmenu($menu_list, isset($menu_detail['MenuKode']) ? $menu_detail['MenuKode'] : ''); ?></ul>
