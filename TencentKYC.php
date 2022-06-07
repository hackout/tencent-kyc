<?php

class TencentKYC {

	/**
	 * Uri
	 * @var string
	 */
	protected $url = 'https://faceid.tencentcloudapi.com';

	/**
	 * Rule ID
	 * @var string
	 */
	protected $rule_id;
	protected $appid;
	protected $secret;

	/**
	 * Create a new tencent-kyc instance.
	 *
	 * @param string $webank
	 * @param string $secret
	 * @return void
	 */
	public function __construct($rule_id, $appid, $secret) {
		$this->rule_id = $rule_id;
		$this->appid = $appid;
		$this->secret = $secret;
	}

	public function openKYC($name, $idcard) {
		$data = [
			'RuleId' => $this->rule_id,
			'IdCard' => $idcard,
			'Name' => $name,
			'RedirectUrl' => WP . '?weixin-phone-index-bindface',
		];
		$result = $this->sendRequest($this->url, $data);
		return isset($result['Response']) && isset($result['Response']['BizToken']) ? ['code' => 1, 'url' => $result['Response']['Url'], 'faceid' => $result['Response']['BizToken']] : ['code' => 0, 'msg' => '获取授权链接失败'];
	}

	protected function makeSign($data, $date) {
		$secretId = $this->appid;
		$secretKey = $this->secret;
		$host = "faceid.tencentcloudapi.com";
		$service = "faceid";
		$version = "2017-03-12";
		$action = "DetectAuth";
		$region = "";
		$timestamp = $date;
		$algorithm = "TC3-HMAC-SHA256";

		$httpRequestMethod = "POST";
		$canonicalUri = "/";
		$canonicalQueryString = "";
		$canonicalHeaders = "content-type:application/json; charset=utf-8\n" . "host:" . $host . "\n";
		$signedHeaders = "content-type;host";
		$payload = $data;
		$hashedRequestPayload = hash("SHA256", $payload);
		$canonicalRequest = $httpRequestMethod . "\n"
			. $canonicalUri . "\n"
			. $canonicalQueryString . "\n"
			. $canonicalHeaders . "\n"
			. $signedHeaders . "\n"
			. $hashedRequestPayload;

		$date = gmdate("Y-m-d", $timestamp);
		$credentialScope = $date . "/" . $service . "/tc3_request";
		$hashedCanonicalRequest = hash("SHA256", $canonicalRequest);
		$stringToSign = $algorithm . "\n"
			. $timestamp . "\n"
			. $credentialScope . "\n"
			. $hashedCanonicalRequest;

		$secretDate = hash_hmac("SHA256", $date, "TC3" . $secretKey, true);
		$secretService = hash_hmac("SHA256", $service, $secretDate, true);
		$secretSigning = hash_hmac("SHA256", "tc3_request", $secretService, true);
		$signature = hash_hmac("SHA256", $stringToSign, $secretSigning);

		$authorization = $algorithm
			. " Credential=" . $secretId . "/" . $credentialScope
			. ", SignedHeaders=content-type;host, Signature=" . $signature;
		return $authorization;
	}

	/**
	 * Send a request
	 * @param  string $url
	 * @param  array  $data
	 * @return array|string
	 */
	protected function sendRequest($url, $data = []) {
		if (!$url) {
			return;
		}
		$data = json_encode($data, 256);
		$time = time();
		$curl = curl_init();
		$headers['X-TC-Action'] = "DetectAuth";
		$headers['X-TC-Timestamp'] = $time;
		$headers['X-TC-Version'] = "2018-03-01";
		$headers['X-TC-Language'] = "zh-CN";
		$headers['Content-Type'] = "application/json; charset=utf-8";
		$headers['Authorization'] = $this->makeSign($data, $time);
		$header = [];
		foreach ($headers as $key => $rs) {
			$header[] = $key . ':' . $rs;
		}
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => $header,
		));

		$result = curl_exec($curl);

		curl_close($curl);

		try {
			return json_decode($result, true);
		} catch (\Exception $e) {
			return $result;
		}
	}
}