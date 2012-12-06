<?php

/**
 * @see https://github.com/barbushin/multirpc
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class RPC_Protocol {

	protected $key;

	public function __construct($key) {
		$this->key = $key;
	}

	public function decryptResponseData($responseData = null) {
		if($responseData !== null) {
			return $this->decrypt($responseData);
		}
		elseif(isset($_POST['__data'])) {
			return $this->decrypt($_POST['__data']);
		}
		else {
			throw new RPC_RequestFailed('Wrong request data');
		}
	}

	public function getBindingData($responseString) {
		$responseString = json_decode($responseString, true);
		if(!$responseString) {
			throw new RPC_RequestCallFailedOnServer('Wrong response format: ' . $responseString);
		}
		if(isset($responseString['__exception'])) {
			$exception = unserialize($responseString['__exception']);
			throw $exception;
		}
		return $responseString;
	}

	public function getDataToSend($data, $asString = false) {
		$encrypted = $this->encrypt($data);
		return $asString ? $encrypted : array('__data' => $encrypted);
	}

	protected function encrypt($data) {
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->key, gzcompress($data, 8), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}

	protected function decrypt($string) {
		$data = @base64_decode($string);
		if(!$data) {
			throw new Exception('Base64 decode failed of: ' . $data);
		}
		$data = @mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->key, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
		if(!$data) {
			throw new Exception('Decryption failed');
		}
		return gzuncompress($data);
	}
}

class RPC_ConnectionFailed extends RPC_RequestFailed {

}

class RPC_RequestFailed extends Exception {

}
