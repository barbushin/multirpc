# Usage example

## Server


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

## Client

	/***************************************************************
	RPC
	 **************************************************************/
	
	$rpc = new RPC_Request();
	
	$A = new RPC_RequestInitObject($rpc, 'A', array('A1', 'A2'));
	$rpc->bind('A', $A);
	$rpc->bind('A->a1', $A->a1);
	
	$A->setA1(9);
	$rpc->bind('A->a1 (2)', $A->a1);
	
	$A->a1 = 12;
	$rpc->bind('A->a1 (3)', $A->a1);
	
	$rpc->bind('A->sum', $A->sum(4, 7));
	$rpc->bind('A (2)', $A);
	$rpc->bind('A->getObject->sum', $A->getObject()->sum($A->a1, 3));
	
	$aSum = $A->getObject()->getObject()->sum(2, $A->a2);
	$rpc->bind('aSum', $aSum);
	
	$rpc->bind('A->getNull()->getNull()->sum', $A->getNull()->getNull()->sum(5, 6));
	$rpc->bind('a::concat', $A->__staticMethod('concat', array('o', 'k')));
	
	/***************************************************************
	CALL SERVER
	 **************************************************************/
	
	$serverUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . dirname($_SERVER['REQUEST_URI']) . '/server.php';
	$client = new RPC_Client(new RPC_Protocol(RPC_SECURE_KEY), $serverUrl);
	$response = $client->call($rpc);
	
	/***************************************************************
	PRINT RESPONSE
	 **************************************************************/
	
	echo '<pre>
	<h2>RPC multi-call result</h2>
	';
	print_r($response);
	
	/***************************************************************
	RPC ONE LINE CALL
	 **************************************************************/
	
	echo '
	<h2>RPC one line call</h2>
	$client->A(1, 2)->getObject()->sum(3, 4)->GET() : ' . $client->A(1, 2)->getObject()->getObject()->sum(3, 4)->GET().'
	$client->A(1, 2)->getObject()->sum(3, 4) : ' . $client->A(1, 2)->getObject()->getObject()->sum(3, 4).'
	$client->A(1, 2)->a2 : ' . $client->A(1, 2)->a2.'
	$client->{"A::concat"}("ab", "cd")->GET() : ' . $client->{"A::concat"}('ab', 'cd')->GET().'
	$client->{"A::concat"}("ab", "cd") : ' . $client->{"A::concat"}('ab', 'cd');


## Server response to Client


	Array
	(
	    [A] => Array
	        (
	            [a1] => 12
	            [a2] => A2
	        )
	
	    [A->a1] => A1
	    [A->a1 (2)] => 9
	    [A->a1 (3)] => 12
	    [A->sum] => 11
	    [A (2)] => Array
	        (
	            [a1] => 12
	            [a2] => A2
	        )
	
	    [A->getObject->sum] => 15
	    [aSum] => 2
	    [A->getNull()->getNull()->sum] => 
	    [a::concat] => ok
	    [some] => Array
	        (
	            [custom] => response var
	        )
	
	)

### RPC one line call

	$client->A(1, 2)->getObject()->sum(3, 4)->GET() : 7
	$client->A(1, 2)->getObject()->sum(3, 4) : 7
	$client->A(1, 2)->a2 : 2
	$client->{"A::concat"}("ab", "cd")->GET() : abcd
	$client->{"A::concat"}("ab", "cd") : abcd



===Recommended===
  * Great backup service with free 2Gb - <a href="http://goo.gl/UNlKw">Mozy.com</a>.
  * Increase productivity by automated time tracking - <a href="http://goo.gl/GNLuu">RescueTime</a>.
  * Google Chrome extension <a href="http://goo.gl/b10YF">PHP Console</a>.
  * Google Chrome extension <a href="http://goo.gl/kNix9">JavaScript Errors Notifier</a>.
 