<?php
/**
 * Navigasi ATAS horizontal modern pill capsule.
 */
if (!function_exists('pkp_menu_icon')) {
	function pkp_menu_icon($kode, $name)
	{
		$nameLower = strtolower($name);
		if ($kode == '1000' || strpos($nameLower, 'dashboard') !== false) {
			return svgico('activity', 14, 'tm-ico');
		}
		if (strpos($nameLower, 'pengajuan') !== false || strpos($nameLower, 'kegiatan') !== false) {
			return svgico('file-alt', 14, 'tm-ico');
		}
		if (strpos($nameLower, 'approval') !== false || strpos($nameLower, 'persetujuan') !== false) {
			return svgico('approval-inbox', 14, 'tm-ico');
		}
		if (strpos($nameLower, 'laporan') !== false || strpos($nameLower, 'report') !== false) {
			return svgico('budget', 14, 'tm-ico');
		}
		if (strpos($nameLower, 'dokumen') !== false) {
			return svgico('book', 14, 'tm-ico');
		}
		if ($kode == '2000' || strpos($nameLower, 'aplikasi') !== false || strpos($nameLower, 'user') !== false) {
			return svgico('config', 14, 'tm-ico');
		}
		return svgico('activity', 14, 'tm-ico');
	}
}

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

			// Jika induk memiliki lebih dari 1 sub-menu -> render dropdown
			if (!empty($v['parent']) && count($children) > 1) {
				$active = ($v['MenuKode'] == $activeKode);
				foreach ($children as $c) if ($c['MenuKode'] == $activeKode) $active = true;
				$ac = $active ? ' active' : '';

				$html .= '<li class="tm-item tm-has' . $ac . '">';
				$html .= '<button type="button" class="tm-link tm-toggle">' . pkp_menu_icon($v['MenuKode'], $v['MenuName']) . '<span>' . html_escape($v['MenuName']) . '</span>' . svgico('chevron-left', 11, 'tm-caret') . '</button>';
				$html .= '<div class="tm-drop"><div class="tm-drop-inner">';
				foreach ($children as $c) {
					$sub = ($c['MenuKode'] == $activeKode) ? ' active' : '';
					$html .= '<a class="tm-sub' . $sub . '" href="' . base_url($c['MenuLink']) . '">' . pkp_menu_icon($c['MenuKode'], $c['MenuName']) . '<span>' . html_escape($c['MenuName']) . '</span></a>';
				}
				$html .= '</div></div></li>';
			} elseif (count($children) === 1) {
				// Jika pilihan anak hanya 1 -> langsung tampilkan sebagai tautan langsung tanpa dropdown
				$c = $children[0];
				$active = ($c['MenuKode'] == $activeKode || $v['MenuKode'] == $activeKode);
				$ac = $active ? ' active' : '';
				$href = !empty($c['MenuLink']) ? base_url($c['MenuLink']) : base_url($v['MenuLink']);
				$label = html_escape($c['MenuName']);
				$html .= '<li class="tm-item' . $ac . '"><a class="tm-link" href="' . $href . '">' . pkp_menu_icon($c['MenuKode'], $c['MenuName']) . '<span>' . $label . '</span></a></li>';
			} else {
				// Item biasa tanpa anak
				$active = ($v['MenuKode'] == $activeKode);
				$ac = $active ? ' active' : '';
				$href = !empty($v['MenuLink']) ? base_url($v['MenuLink']) : '#';
				$html .= '<li class="tm-item' . $ac . '"><a class="tm-link" href="' . $href . '">' . pkp_menu_icon($v['MenuKode'], $v['MenuName']) . '<span>' . html_escape($v['MenuName']) . '</span></a></li>';
			}
		}
		return $html;
	}
}
?>
<ul class="tm-list"><?php echo pkp_topmenu($menu_list, isset($menu_detail['MenuKode']) ? $menu_detail['MenuKode'] : ''); ?></ul>
