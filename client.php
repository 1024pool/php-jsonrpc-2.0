<?php

namespace JSONRPC;

class Client_Exception extends \Exception {}

class Client {

    public $url, $cookie_file;
    private $_id;

    public function __construct($url) {
        $this->url = $url;
        $this->_id = uniqid();
        $this->cookie_file = new \Cookie_File;
    }

    public function __call($method, $params = array()) {
        return call_user_func_array(array($this, 'call'), array(
            $method,
            $params
        ));
    }

    public function call($method, $params) {
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_COOKIEJAR => $this->cookie_file->path,
            CURLOPT_COOKIEFILE => $this->cookie_file->path,
            CURLOPT_URL =>  $this->url,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.1.13) Gecko/20100914 Firefox/3.5.13( .NET CLR 3.5.30729)',
            CURLOPT_POST => TRUE,
            CURLOPT_CONNECTTIMEOUT=> 5,
            CURLOPT_POSTFIELDS => json_encode(array(
                'jsonrpc'=> '2.0',
                'method'=> $method,
                'params'=> $params,
                'id'=> $this->_id
            ))
        ));

        $result = json_decode(curl_exec($ch), TRUE);

        curl_close($ch);

        if (!isset($result['jsonrpc']) || $result['jsonrpc'] != '2.0') {
            throw new \JSONRPC\Client_Exception('Wrong Response ! It`s Not JSONRPC2.0');
        }

        if ($result['id'] != $this->_id) {
            throw new \JSONRPC\Client_Exception('Wrong Response Id');
        }

        if (isset($result['error'])) {
            throw new \JSONRPC\Client_Exception($result['error']['message']);
        }

        return $result['result'];
    }
}
