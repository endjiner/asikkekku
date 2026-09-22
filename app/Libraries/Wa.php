<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Dulu application/libraries/Wa.php (CI3). $this->ci->load->database() +
 * $this->ci->db diganti db_connect() langsung; method query builder
 * di-rename ke gaya CI4 (table()->getWhere()/insert(), camelCase).
 */
class Wa
{
    private string $token_default = '';
    private string $endpoint      = 'https://solo.wablas.com/api/v2/send-message';

    private BaseConnection $db;

    /** Cache per-instance: token()/vrblOff() konstan sepanjang satu run
     *  send_bulk() (dipanggil per-penerima, atau berkali-kali dari
     *  EarlyWarningKirim()), sama seperti sla_config() di sla_helper.php. */
    private ?string $tokenCache = null;
    private array $vrblOffCache = [];

    public function __construct()
    {
        $this->db = db_connect();
    }

    private function token(): string
    {
        if ($this->tokenCache !== null) {
            return $this->tokenCache;
        }
        $row = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'wa_token'])->getRowArray();
        $t   = (! empty($row) && trim((string) $row['VrblValue']) !== '') ? trim($row['VrblValue']) : '';

        return $this->tokenCache = ($t !== '' && $t !== 'ISI_TOKEN_WABLAS_DI_SINI') ? $t : $this->token_default;
    }

    private function vrblOff(string $name): bool
    {
        if (array_key_exists($name, $this->vrblOffCache)) {
            return $this->vrblOffCache[$name];
        }
        $row = $this->db->table('tb_vrbl')->getWhere(['VrblName' => $name])->getRowArray();
        if (empty($row)) {
            return $this->vrblOffCache[$name] = false;
        }
        $v = strtolower(trim((string) $row['VrblValue']));

        return $this->vrblOffCache[$name] = in_array($v, ['0', 'off', 'false', 'no', 'tidak', 'nonaktif'], true);
    }

    private function enabled(string $context = 'umum'): bool
    {
        if ($this->vrblOff('wa_enabled')) {
            return false;
        }
        if ($context !== '' && $this->vrblOff('wa_enabled_' . $context)) {
            return false;
        }
        if (trim($this->token()) === '') {
            return false;
        }

        return true;
    }

    private function wrap(string $text): string
    {
        return "🛎 Notifikasi ASIKKEKKU\n\n" . $text .
               "\n\nRgds,\nASIKKEKKU - Balai Besar POM di Pangkal Pinang\n" .
               "⛔ Pesan otomatis ini dikirim oleh sistem, mohon untuk tidak membalas.";
    }

    private function normPhone(string $p): string
    {
        $p = preg_replace('/\D/', '', $p);
        if ($p === '') {
            return '';
        }
        if (substr($p, 0, 1) === '0') {
            $p = '62' . substr($p, 1);
        }
        if (substr($p, 0, 2) !== '62') {
            $p = '62' . $p;
        }

        return $p;
    }

    /** @return array{ok: bool, skipped?: bool, http?: int} */
    private function sendOne(string $phone, string $text, string $context, $kegiatanID): array
    {
        $phone = $this->normPhone($phone);
        $text  = trim($text);
        if ($phone === '' || $text === '') {
            return ['ok' => false, 'skipped' => true];
        }

        $message = $this->wrap($text);
        $payload = json_encode(['data' => [['phone' => $phone, 'message' => $message]]]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->endpoint,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $this->token(), 'Content-Type: application/json'],
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body     = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($curl);
        curl_close($curl);

        $ok = false;
        if ($curlErr === '' && $httpCode === 200) {
            $json = json_decode((string) $body, true);
            $ok   = is_array($json) && ! empty($json['status']);
        }
        $respLog = ($curlErr !== '') ? ('cURL: ' . $curlErr) : (string) $body;

        if ($this->db->tableExists('tb_wa_log')) {
            $this->db->table('tb_wa_log')->insert([
                'KonteksKirim' => $context,
                'KegiatanID'   => $kegiatanID ? (int) $kegiatanID : null,
                'Nomor'        => $phone,
                'Pesan'        => mb_substr($text, 0, 1000),
                'HttpCode'     => $httpCode,
                'Status'       => $ok ? 'ok' : 'gagal',
                'Respons'      => mb_substr($respLog, 0, 2000),
                'TanggalKirim' => date('Y-m-d H:i:s'),
            ]);
        }

        return ['ok' => $ok, 'skipped' => false, 'http' => $httpCode];
    }

    /**
     * @param array<int, array{phone?: string, text?: string}> $items
     *
     * @return array{sent: int, failed: int, total: int, disabled?: bool}
     */
    public function send_bulk(array $items, string $context = 'umum', $kegiatanID = null): array
    {
        if (! $this->enabled($context)) {
            if ($this->db->tableExists('tb_wa_log')) {
                $this->db->table('tb_wa_log')->insert([
                    'KonteksKirim' => $context,
                    'KegiatanID'   => $kegiatanID ? (int) $kegiatanID : null,
                    'Nomor'        => '-',
                    'Pesan'        => 'WA nonaktif (global/konteks/token) - ' . count($items) . ' pesan tidak dikirim',
                    'HttpCode'     => 0,
                    'Status'       => 'nonaktif',
                    'Respons'      => '',
                    'TanggalKirim' => date('Y-m-d H:i:s'),
                ]);
            }

            return ['sent' => 0, 'failed' => 0, 'total' => 0, 'disabled' => true];
        }

        $sent = 0;
        $failed = 0;
        foreach ($items as $it) {
            $r = $this->sendOne(
                isset($it['phone']) ? $it['phone'] : '',
                isset($it['text']) ? $it['text'] : '',
                $context,
                $kegiatanID
            );
            if (! empty($r['skipped'])) {
                continue;
            }
            if (! empty($r['ok'])) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => $sent + $failed];
    }

    public function send_text($phone_src, $phone_dst, $text_src, $text_dst, $phone_pemohon = '', $text_pemohon = '', string $context = 'umum', $kegiatanID = null): array
    {
        return $this->send_bulk([
            ['phone' => $phone_src, 'text' => $text_src],
            ['phone' => $phone_dst, 'text' => $text_dst],
            ['phone' => $phone_pemohon, 'text' => $text_pemohon],
        ], $context, $kegiatanID);
    }
}
