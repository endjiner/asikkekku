<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Wa {

	private $token_default = "";
	private $endpoint = "https://solo.wablas.com/api/v2/send-message";

	function __construct()
	{
		$this->ci = &get_instance();
		$this->ci->load->database();
	}

	private function token()
	{
		$row = $this->ci->db->get_where('tb_vrbl', array('VrblName' => 'wa_token'))->row_array();
		$t = (!empty($row) && trim((string) $row['VrblValue']) !== '') ? trim($row['VrblValue']) : '';
		return ($t !== '' && $t !== 'ISI_TOKEN_WABLAS_DI_SINI') ? $t : $this->token_default;
	}

	private function vrblOff($name)
	{
		$row = $this->ci->db->get_where('tb_vrbl', array('VrblName' => $name))->row_array();
		if (empty($row)) return false;
		$v = strtolower(trim((string) $row['VrblValue']));
		return in_array($v, array('0', 'off', 'false', 'no', 'tidak', 'nonaktif'), true);
	}

	private function enabled($context = 'umum')
	{
		if ($this->vrblOff('wa_enabled')) return false;
		if ($context !== '' && $this->vrblOff('wa_enabled_' . $context)) return false;
		if (trim($this->token()) === '') return false;
		return true;
	}

	private function wrap($text)
	{
		return "🛎 Notifikasi ASIKKEKKU\n\n" . $text .
		       "\n\nRgds,\nASIKKEKKU - Balai POM di Pangkalpinang\n" .
		       "⛔ Pesan otomatis ini dikirim oleh sistem, mohon untuk tidak membalas.";
	}

	private function normPhone($p)
	{
		$p = preg_replace('/\D/', '', (string) $p);
		if ($p === '') return '';
		if (substr($p, 0, 1) === '0') $p = '62' . substr($p, 1);
		if (substr($p, 0, 2) !== '62') $p = '62' . $p;
		return $p;
	}

	private function sendOne($phone, $text, $context, $kegiatanID)
	{
		$phone = $this->normPhone($phone);
		$text  = trim((string) $text);
		if ($phone === '' || $text === '') {
			return array('ok' => false, 'skipped' => true);
		}

		$message = $this->wrap($text);
		$payload = json_encode(array('data' => array(array('phone' => $phone, 'message' => $message))));

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL            => $this->endpoint,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS     => $payload,
			CURLOPT_HTTPHEADER     => array('Authorization: ' . $this->token(), 'Content-Type: application/json'),
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT        => 15,
		));
		$body     = curl_exec($curl);
		$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curlErr  = curl_error($curl);
		curl_close($curl);

		$ok = false;
		if ($curlErr === '' && $httpCode === 200) {
			$json = json_decode((string) $body, true);
			$ok = is_array($json) && !empty($json['status']);
		}
		$respLog = ($curlErr !== '') ? ('cURL: ' . $curlErr) : (string) $body;

		if ($this->ci->db->table_exists('tb_wa_log')) {
			$this->ci->db->insert('tb_wa_log', array(
				'KonteksKirim' => $context,
				'KegiatanID'   => $kegiatanID ? (int) $kegiatanID : null,
				'Nomor'        => $phone,
				'Pesan'        => mb_substr($text, 0, 1000),
				'HttpCode'     => $httpCode,
				'Status'       => $ok ? 'ok' : 'gagal',
				'Respons'      => mb_substr($respLog, 0, 2000),
				'TanggalKirim' => date('Y-m-d H:i:s'),
			));
		}
		return array('ok' => $ok, 'skipped' => false, 'http' => $httpCode);
	}

	/**
	 * @param array  $items [['phone'=>..., 'text'=>...], ...]
	 * @return array ['sent'=>int, 'failed'=>int, 'total'=>int]
	 */
	function send_bulk($items, $context = 'umum', $kegiatanID = null)
	{
		if (!$this->enabled($context)) {
			if ($this->ci->db->table_exists('tb_wa_log')) {
				$this->ci->db->insert('tb_wa_log', array(
					'KonteksKirim' => $context,
					'KegiatanID'   => $kegiatanID ? (int) $kegiatanID : null,
					'Nomor'        => '-',
					'Pesan'        => 'WA nonaktif (global/konteks/token) - ' . count((array) $items) . ' pesan tidak dikirim',
					'HttpCode'     => 0,
					'Status'       => 'nonaktif',
					'Respons'      => '',
					'TanggalKirim' => date('Y-m-d H:i:s'),
				));
			}
			return array('sent' => 0, 'failed' => 0, 'total' => 0, 'disabled' => true);
		}
		$sent = 0; $failed = 0;
		foreach ((array) $items as $it) {
			$r = $this->sendOne(
				isset($it['phone']) ? $it['phone'] : '',
				isset($it['text']) ? $it['text'] : '',
				$context, $kegiatanID
			);
			if (!empty($r['skipped'])) continue;
			if (!empty($r['ok'])) $sent++; else $failed++;
		}
		return array('sent' => $sent, 'failed' => $failed, 'total' => $sent + $failed);
	}

	function send_text($phone_src, $phone_dst, $text_src, $text_dst, $phone_pemohon = '', $text_pemohon = '', $context = 'umum', $kegiatanID = null)
	{
		return $this->send_bulk(array(
			array('phone' => $phone_src,     'text' => $text_src),
			array('phone' => $phone_dst,     'text' => $text_dst),
			array('phone' => $phone_pemohon, 'text' => $text_pemohon),
		), $context, $kegiatanID);
	}
}
