-- Menu sidebar: Persetujuan (prioritas) di atas Daftar Pengajuan; nama Bahasa Indonesia.
UPDATE tb_menu SET MenuOrder = 0 WHERE MenuKode = '3200';
UPDATE tb_menu SET MenuOrder = 1 WHERE MenuKode = '3100';
UPDATE tb_menu SET MenuOrder = 2 WHERE MenuKode = '3300';
UPDATE tb_menu SET MenuName = 'Dasbor'                WHERE MenuKode = '1000';
UPDATE tb_menu SET MenuName = 'Manajemen Aplikasi'    WHERE MenuKode = '2000';
UPDATE tb_menu SET MenuName = 'Grup Pengguna'         WHERE MenuKode = '2100';
UPDATE tb_menu SET MenuName = 'Pengguna'              WHERE MenuKode = '2200';
UPDATE tb_menu SET MenuName = 'Konfigurasi Aplikasi'  WHERE MenuKode = '2300';
UPDATE tb_menu SET MenuName = 'Manajemen Persetujuan' WHERE MenuKode = '3000';
UPDATE tb_menu SET MenuName = 'Persetujuan'           WHERE MenuKode = '3200';
UPDATE tb_menu SET MenuName = 'Daftar Pengajuan'      WHERE MenuKode = '3100';
UPDATE tb_menu SET MenuName = 'Laporan'               WHERE MenuKode = '3300';
