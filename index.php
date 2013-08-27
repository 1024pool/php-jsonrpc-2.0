<?php

require 'cookie_file.php';
require 'client.php';

$rpc = new \JSONRPC\Client('http://rpc/api.php');

try {
    echo $rpc->hello(). "\n";
    echo $rpc->trim("\t\n hello "). "\n";
    echo $rpc->create_exception();
}
catch(\JSONRPC\Client_Exception $e) {
    echo $e->getMessage();
}
