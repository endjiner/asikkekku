<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hardening 2026-09-04:
 *  - tb_kegiatan.KegiatanDeletedAt  (soft delete pengajuan)
 *  - index performa pada tb_kegiatan (status, status terakhir, user, dest)
 *  - index tb_approval_history.FlowDestUser (query antrian persetujuan)
 */
class Migration_Hardening_soft_delete_indexes extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('KegiatanDeletedAt', 'tb_kegiatan')) {
            $this->forge->addColumn('tb_kegiatan', array(
                'KegiatanDeletedAt' => array('type' => 'DATETIME', 'null' => TRUE, 'default' => NULL),
            ));
        }
        $idx = array(
            'tb_kegiatan' => array(
                'idx_keg_deleted'         => 'KegiatanDeletedAt',
                'idx_keg_status'          => 'KegiatanStatus',
                'idx_keg_status_terakhir' => 'KegiatanStatusTerakhir',
                'idx_keg_user'            => 'KegiatanUserID',
                'idx_keg_dest'            => 'KegiatanDestUser',
            ),
            'tb_approval_history' => array(
                'idx_ah_destuser' => 'FlowDestUser',
            ),
        );
        foreach ($idx as $table => $defs) {
            foreach ($defs as $name => $col) {
                $exists = $this->db->query(
                    "SELECT 1 FROM information_schema.statistics
                     WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1",
                    array($table, $name))->getNumRows() > 0;
                if (!$exists) {
                    $this->db->query("ALTER TABLE `$table` ADD INDEX `$name` (`$col`)");
                }
            }
        }
    }

    public function down()
    {
        foreach (array('idx_keg_deleted', 'idx_keg_status', 'idx_keg_status_terakhir', 'idx_keg_user', 'idx_keg_dest') as $n) {
            @$this->db->query("ALTER TABLE `tb_kegiatan` DROP INDEX `$n`");
        }
        @$this->db->query("ALTER TABLE `tb_approval_history` DROP INDEX `idx_ah_destuser`");
        if ($this->db->fieldExists('KegiatanDeletedAt', 'tb_kegiatan')) {
            $this->forge->dropColumn('tb_kegiatan', 'KegiatanDeletedAt');
        }
    }
}
