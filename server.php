<?php

namespace JSONRPC;

class Server_Exception extends \Exception {

    const STATUS_PARSE_ERROR = -32700;
    const STATUS_INVALID_REQUEST = -32600;
    const STATUS_METHOD_NOT_FOUNT = -32601;
    const STATUS_INVALID_PARAMS = -32602;
    const STATUS_INTERNAL_ERROR = -32603;

    static $errors = array(
        -32700=> 'Parse error',
        -32600=> 'Invalid Request',
        -32601=> 'Method not found',
        -32602=> 'Invalid params',
        -32603=> 'Internal error',
    );


    public function __construct($error_code) {

        if (!array_key_exists($error_code, self::$errors)) {

            $error_code = self::STATUS_INTERNAL_ERROR;
        }

        parent::__construct(self::$errors[$error_code], $error_code);
    }
}

class Server {

    //request的json数据
    public $request;

    //分析获取后的method
    public $method;

    //分析获取后的params
    public $params;

    //分析获取的id
    public $id;

    //初始化
    public function __construct($request = NULL) {

        if (!$request) {
            //不设定request，则默认从php输入流中获取
            $this->request= file_get_contents('php://input');
        }
        else {
            $this->request = $request;
        }

        if (isset($_COOKIE['session_id'])) {
            session_id($_COOKIE['session_id']);
        }
        else {
            setcookie('session_id', session_id());
        }

        session_start();

    }

    public function run() {

        //无论是否有错误，都发送头进行返回
        $this->send_header();

        try {

            //进行request分析
            $this->process();

            //获取已绑定_functions
            $_functions = $this->_functions;

            //遍历判定是否包含可用method
            if (array_key_exists($this->method, $_functions)) {
                $this->method = $_functions[$this->method];
            }
            else {
                throw new \JSONRPC\Server_Exception(\JSONRPC\Server_Exception::STATUS_METHOD_NOT_FOUNT);
            }

            $method = $this->method;
            $params = $this->params;

            if ((is_string($method) && !function_exists($method))
                    ||
                    (is_array($method) && !method_exists($method[0], $method[1]))
               ) throw new \JSONRPC\Server_Exception(\JSONRPC\Server_Exception::STATUS_METHOD_NOT_FOUNT);

            //调用，获取结果
            $this->result = call_user_func_array($method, $params);

            //发送结果
            $this->send_body();

        }
        catch(\JSONRPC\Server_Exception $e) {

            //抓到异常发送错误
            $this->send_error($e->getCode(), $e->getMessage());
        }
    }

    private $_functions = array();

    //进行绑定
    public function expose($origin, $dest = NULL) {

        if ($dest === NULL) {
            $dest = $origin;
        }

        $this->_functions[$origin] = $dest;

    }

    //进行request解析
    public function process() {

        //decode
        $decoded_array = json_decode($this->request, TRUE);

        if (json_last_error()) {

            //出现错误抛出语法异常错误
            throw new JSONRPC_Exception(JSONRPC_Exception::STATUS_PARSE_ERROR);
        }

        if (!isset($decoded_array['method'])) {
            //抛出不可用请求错误
            throw new JSONRPC_Exception(JSONRPC_Exception::STATUS_INVALID_REQUEST);
        }
        else {
            $this->method = $decoded_array['method'];
        }

        if (!isset($decoded_array['params'])) {
            //抛出参数错误
            throw new JSONRPC_Exception(JSONRPC_Exception::STATUS_INVALID_PARAMS);
        }
        else {
            $this->params = $decoded_array['params'];
        }

        if (!isset($decoded_array['id'])) {
            //抛出错误情趣
            throw new JSONRPC_Exception(JSONRPC_Exception::STATUS_INVALID_REQUEST);
        }
        else {
            $this->id = $decoded_array['id'];
        }
    }

    public function send_header() {
        //header表明json
        header('Content-Type: application/json');
    }

    public function send_body() {

        //正常结果返回
        $array = array(
                'jsonrpc'=> '2.0',
                'result'=> $this->result,
                'id'=> $this->id
                );

        echo json_encode($array);
    }

    public function send_error($code, $message) {

        //发送错误返回
        $array = array(
                'jsonrpc'=> '2.0',
                'error'=> array(
                    'code'=> $code,
                    'message'=> $message
                    ),
                'id'=> $this->id
                );
        echo json_encode($array);
    }

    public function __destruct() {
        session_write_close();
    }
}
