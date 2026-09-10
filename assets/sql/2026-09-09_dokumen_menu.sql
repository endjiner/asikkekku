-- 2026-09-09  Menu sidebar "Dokumen Pencairan" (modul Dokumen disambungkan ke alur)
-- Aman dijalankan ulang.

INSERT INTO `tb_menu` (`MenuKode`,`MenuName`,`MenuLink`,`MenuClass`,`MenuParent`,`MenuOrder`)
SELECT '3400','Dokumen Pencairan','dokumen/pilih','fa-file-signature',3,'3'
WHERE NOT EXISTS (SELECT 1 FROM `tb_menu` WHERE `MenuKode`='3400');

-- Beri akslih (R/C/U) ke grup yang punya akses "Daftar Pengajuan" (3100).
INSERT INTO `tb_menu_access` (`MenuID`,`UserGroupID`,`Status_R`,`Status_C`,`Status_U`,`Status_D`)
SELECT (SELECT `MenuID` FROM `tb_menu` WHERE `MenuKode`='3400'), a.`UserGroupID`, 1,1,1,0
FROM `tb_menu_access` a
JOIN `tb_menu` m ON m.`MenuID`=a.`MenuID` AND m.`MenuKode`='3100' AND a.`Status_R`=1
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_menu_access` x
  WHERE x.`MenuID`=(SELECT `MenuID` FROM `tb_menu` WHERE `MenuKode`='3400')
    AND x.`UserGroupID`=a.`UserGroupID`
);
