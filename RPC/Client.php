<?php

/**
 * @see https://github.com/barbushin/multirpc
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class RPC_Client {

	/**
	 * @var RPC_Protocol
	 */
	protected $protocol;
	protected $serverUrl;
	protected $connectionTimeout;

	public function __construct(RPC_Protocol $protocol, $serverUrl, $connectionTimeout = 30) {
		$this->protocol = $protocol;
		$this->serverUrl = $serverUrl;
		$this->connectionTimeout = $connectionTimeout;
	}

	public function call(RPC_Request $request) {
		$requestData = $this->protocol->getDataToSend($request->convertToString());
		$responseData = $this->sendPostRequest($this->serverUrl, $requestData);
		if(!$responseData) {
			throw new RPC_RequestFailed('Request to "' . $this->serverUrl . '" failed with empty response');
		}
		return $this->protocol->getBindingData($this->protocol->decryptResponseData($responseData));
	}

	protected function sendPostRequest($URL, $data = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->connectionTimeout);
		curl_setopt($ch, CURLOPT_POST, (bool)$data);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, false);

		if($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}

		$response = curl_exec($ch);

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if($code == 204) {
			$code = 200;
		}
		if($code != 200 || $error) {
			throw new RPC_ConnectionFailed('Connection to "' . $URL . '" failed with code ' . $code . ', error: ' . $error . '" and response: "' . $response);
		}
		return $response;
	}

	/**
	 *
	 * $rpc->UsersCollection(12, 'John')->... equals to new User(12, 'John')
	 * $rpc->User(12, 'John')->... equals to new User(12, 'John')
	 *
	 * @param $class
	 * @param $args
	 * @return RPC_ClientCall
	 */
	public function __call($class, $args = array()) {
		if(preg_match('/^(\w+)\:\:(\w+)$/', $class, $matches)) {
			return new RPC_ClientCall($this, new RPC_RequestClassStaticMethod(new RPC_Request(), $matches[1], $matches[2], $args));
		}
		return new RPC_ClientCall($this, new RPC_RequestInitObject(new RPC_Request(), $class, $args));
	}

	/**
	 *
	 * $rpc->User->... equals to new User()
	 *
	 * @param $class
	 * @return RPC_ClientCall
	 */
	public function __get($class) {
		return $this->__call($class);
	}
}

class RPC_ClientCall {

	protected $client;
	/**
	 * @var RPC_RequestCall
	 */
	protected $targetCall;

	public function __construct(RPC_Client $client, RPC_RequestCall $targetCall) {
		$this->client = $client;
		$this->targetCall = $targetCall;
	}

	public function __get($property) {
		$this->targetCall = $this->targetCall->__get($property);
		return $this;
	}

	public function __call($method, $args) {
		$this->targetCall = $this->targetCall->__call($method, $args);
		return $this;
	}

	public function GET() {
		$rpc = $this->targetCall->request;
		$rpc->bind('result', $this->targetCall);
		$response = $this->client->call($rpc);
		return $response['result'];
	}

	public function __toString() {
		$result = $this->GET();
		if($result !== null && !is_scalar($result)) {
			throw new RPC_RequestFailed('Call ' . $this->targetCall->getName() . ' returned not scalar data and can\'t be converted __toString. Returned data: ' . print_r($result, true));
		}
		return (string)$result;
	}

	public function __set($property, $value) {
		$this->targetCall = $this->targetCall->__set($property, $value);
		return $this->GET();
	}
}
