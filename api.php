<?php

require 'server.php';

function hello() {
    return 'hello';
}

//初始化jsonrpc对象
$server = new \JSONRPC\Server;

//进行调用方法并绑定，左侧调用方法，右侧为实际调用方法
//支持绑定到对象方法
$server->expose('trim');
error_log('foo');
$server->expose('hello', 'hello');

//执行
$server->run();
