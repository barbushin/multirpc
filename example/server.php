<?php

require_once ('config.php');

class A {

	public $a1;
	public $a2;

	public function __construct($a1 = null, $a2 = null) {
		$this->a1 = $a1;
		$this->a2 = $a2;
	}

	public function setA1($a1) {
		$this->a1 = $a1;
	}

	public function sum($a1, $a2) {
		return $a1 + $a2;
	}

	public function incrementByReference(&$number) {
		$number++;
	}

	public static function concat($a1, $a2) {
		return $a1 . $a2;
	}

	public function getObject() {
		return $this;
	}

	public static function getInstance() {
		return new A();
	}

	public function getNull() {
		return null;
	}
}

$server = new RPC_Server(new RPC_Protocol(RPC_SECURE_KEY));
$server->runRpc();
$server->response['some'] = array('custom' => 'response var');
$server->sendResponse();
