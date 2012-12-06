<?php

require_once ('config.php');

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

$counter = new RPC_RequestVariable($rpc, 3);
$A->incrementByReference($counter);
$rpc->bind('counter', $counter);

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
$client->A(1, 2)->getObject()->sum(3, 4)->GET() : ' . $client->A(1, 2)->getObject()->getObject()->sum(3, 4)->GET() . '
$client->A(1, 2)->getObject()->sum(3, 4) : ' . $client->A(1, 2)->getObject()->getObject()->sum(3, 4) . '
$client->A(1, 2)->a2 : ' . $client->A(1, 2)->a2 . '
$client->{"A::concat"}("ab", "cd")->GET() : ' . $client->{
"A::concat"
}('ab', 'cd')->GET() . '
$client->{"A::concat"}("ab", "cd") : ' . $client->{
"A::concat"
}('ab', 'cd') . '
';
