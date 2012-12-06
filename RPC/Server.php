<?php

/**
 * @see https://github.com/barbushin/multirpc
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class RPC_Server {

	/**
	 * @var RPC_Protocol
	 */
	protected $protocol;

	/**
	 * @var RPC_Request
	 */
	public $request;
	public $response = array();

	public function __construct(RPC_Protocol $protocol) {
		$this->protocol = $protocol;
		$this->request = RPC_Request::initFromString($this->protocol->decryptResponseData());
	}

	public function runRpc() {
		try {
			$this->response = array_merge($this->response, $this->request->run());
		}
		catch(Exception $exception) {
			$this->response['__exception'] = serialize($exception);
		}
	}

	protected function getResponseAsString() {
		return json_encode($this->response);
	}

	public function sendResponse() {
		echo $this->protocol->getDataToSend($this->getResponseAsString(), true);
		exit();
	}
}
