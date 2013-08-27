<?php

class Cookie_File {

    //path 用于存储cookie文件的路径
    public $path;

    function __construct() {
        //初始化cookie文件
        $this->path = tempnam(sys_get_temp_dir(), 'rpc.cookie.');
        @touch($this->path);
    }

    function __destruct() {
        unlink($this->path);
    }
}
