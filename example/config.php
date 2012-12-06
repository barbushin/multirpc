<?php

define('RPC_SECURE_KEY', 'DLdsk1lWLKsdjLsl1sd');

function autoloadByDir($class) {
	$filePath = '../' . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
	require_once ($filePath);
}
spl_autoload_register('autoloadByDir');