<?php

class TencentKYC
{
	
	/**
	 * Uri
	 * @var string
	 */
	protected $url = 'https://miniprogram-kyc.tencentcloudapi.com/api/oauth2';

	/**
	 * WebBank APP ID
	 * @var string
	 */
	protected $webankID;
	/**
	 * APPID
	 * @var string
	 */
	protected $app_id;

	/**
	 * Secret
	 * @var string
	 */
	protected $secret;

	/**
	 * Access Token
	 * @var string
	 */
	protected $access_token;

	/**
	 * Ticket
	 * @var array
	 */
	protected $tickets = [
		'sign' => [],
		'notice' => []
	];
	/**
	 * openData
	 * @var array
	 */
	protected $openData = [];

	protected $defaultUrl = 'miniprogram-kyc.tencentcloudapi.com';

	/**
	 * Create a new tencent-kyc instance.
	 *
	 * @param string $webank
	 * @param string $app_id
	 * @param string $secret
	 * @return void
	 */
	public function __construct($webank,$app_id, $secret) {
		$this->webankID = $webank;
		$this->app_id = $app_id;
		$this->access_token = $secret;
	}

	protected function getUrl($str)
	{
		return $this->url .$str;
	}

	protected function error($msg)
	{
		return ['code' => 0,'msg' => $msg];
	}

	protected function success($data)
	{
		return ['code' => 1,'msg' => 'success','data' => $data];
	}

	/**
	 * Get Access Token
	 * @return array
	 */
	protected function getAccessToken()
	{
		$url = $this->getUrl('/access_token');
		$param = [
			'app_id' => $this->app_id,
			'secret' => $this->secret,
			'grant_type' => 'client_credential',
			'version' => '1.0.0'
		];

		$result = $this->sendRequest($url,$param);
		if(!is_array($result)) return $this->error($result);
		if($result['code'] != '0') return $this->error($result['msg']);
		$this->access_token = $result['access_token'];
		return $this->success([
			'transactionTime' => $result['transactionTime'],
			'access_token' => $result['access_token'],
			'expire_time' => $result['expire_time'],
			'expire_in' => $result['expire_in']
		]);
	}

	protected function getSignTicket()
	{
		$url = $this->getUrl('/api_ticket');
		$param = [
			'app_id' => $this->app_id,
			'access_token' => $this->access_token,
			'type' => 'SIGN',
			'version' => '1.0.0'
		];

		$result = $this->sendRequest($url,$param);
		if(!is_array($result)) return $this->error($result);
		if($result['code'] != '0') return $this->error($result['msg']);
		$this->tickets['sign'] = $result['tickets'];
		return $this->success([
			'transactionTime' => $result['transactionTime'],
			'ticket' => $result['tickets']['value'],
			'expire_time' => $result['tickets']['expire_time'],
			'expire_in' => $result['tickets']['expire_in']
		]);
	}

	protected function getNoticeTicket()
	{
		$url = $this->getUrl('/api_ticket');
		$param = [
			'app_id' => $this->app_id,
			'access_token' => $this->access_token,
			'type' => 'NONCE',
			'version' => '1.0.0'
		];

		$result = $this->sendRequest($url,$param);
		if(!is_array($result)) return $this->error($result);
		if($result['code'] != '0') return $this->error($result['msg']);
		$this->tickets['notice'] = $result['tickets'];
		return $this->success([
			'transactionTime' => $result['transactionTime'],
			'ticket' => $result['tickets']['value'],
			'expire_time' => $result['tickets']['expire_time'],
			'expire_in' => $result['tickets']['expire_in']
		]);
	}

	public function verifyLogin($data )
	{
		$param = array_merge([
			'code' => null,
			'orderNo' => null,
			'h5faceId' => null,
			'newSignature' => null,
			'liveRate' => null,
		],$data);

		$sign = [
			'app_id' => $this->app_id,
			'orderNo' => $param['orderNo'],
			'code' => $param['code'],
			'ticket' => $this->tickets['sign']['value']
		];
		$signature = $this->makeSign($sign);

		if($signature == $param['newSignature'])
		{
			return $param;
		}
		return ;
	}


	protected function postUser($order_no,$userId,$name,$idcard)
	{
		$url = 'https://miniprogram-kyc.tencentcloudapi.com/api/server/h5/geth5faceid?orderNo='.$order_no;
		$param = [
			'webankAppId' => $this->webankID,
			'orderNo' => $orderNo,
			'name' => $name,
			'idNo' => $idcard,
			'userId' => $userId,
			'version' => '1.0.0',
			'ticket' => $this->tickets['sign']['value']
		];

		$param['sign'] = $this->makeSign($param);

		$result = $this->sendRequest($url,$param);
		if(!is_array($result)) return $this->error($result);
		if($result['code'] != '0') return $this->error($result['msg']);
		$this->openData = $result['result'];
		return $this->success([
			'transactionTime' => $result['transactionTime'],
			'bizSeqNo' => $result['result']['bizSeqNo'],
			'orderNo' => $result['result']['orderNo'],
			'h5faceId' => $result['result']['h5faceId'],
			'optimalDomain' => $result['result']['optimalDomain'],
		]);
	}


	public function openKYC()
	{
		return [];
	}

	protected function openUrl()
	{
		$url = 'https://'.($this->openData['optimalDomain'] ? $this->openData['optimalDomain'] : $this->defaultUrl).'/api/web/login';
		$param = [
			'webankAppId' => $webankID,
			'orderNo' => $order,
			'userId' => $user_id,
			'version' => '1.0.0',
			'h5faceId' => $this->openData['h5faceId'],
			'nonce' => md5(time()),
			'ticket' => $this->tickets['notice']['value']
		];
		$param['sign'] = $this->makeSign($param);
		$param = [
			'webankAppId' => $param['webankAppId'],
			'version' => $param['version'],
			'nonce' => $param['nonce'],
			'orderNo' => $param['orderNo'],
			'h5faceId' => $param['h5faceId'],
			'url' => 'url',
			'resultType' => 1,
			'userId' => $param['userId'],
			'sign' => $param['sign'],
			'from' => 'browser',
			'redirectType' => 1,
		];
		return $url.'?'.http_build_query($param);
	}


	protected function makeSign($key,$data)
	{
		$str = '';
		foreach($data as $r)
		{
			$str .= $r;
		}
		$str .= $key;
		return sha1($str);
	}


	/**
	 * Send a request
	 * @param  string $url  
	 * @param  array  $data 
	 * @return array|string 
	 */
	protected function sendRequest($url,$data = [])
	{
		if(!$url) return ;
		if($data)
		{
			$data = http_build_query($data);
		}
		$opts = [
			'http' => [
				'method' => 'POST',
				'header' => "Content-type:application/x-www-form-urlencoded Content-Length:".strlen ($data),
				'content' => $data
			]
		];
		$context =  stream_context_create($opts);
		$result = file_get_contents($url,false,$context);
		try {
			return json_decode($result,true);
		} catch (\Exception $e) {
			return $result;
		}
	}
}