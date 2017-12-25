<?php

/**
 * zeroMQ class
 *
 * @author lyb
 */
class ZeroMQ implements MQ {

    private $str;

    private $config;

    private $client;

    private $log;

    /**
     * 创建mq
     *
     * @param array $config 配置参数
     */
    public function __construct($config) {
        $this->config = $config;
    }

    public function connect() {
        $context = new ZMQContext();
        $this->client = new ZMQSocket($context, ZMQ::SOCKET_REQ);
        $this->client->connect("tcp://" . $this->config['host']);
        // Configure socket to not wait at close time
        $this->client->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
    }

    public function close() {
    }

    /**
     * 写
     *
     * @param unknown_type $str
     */
    public function write($str) {
        // $this->str = $str;
        return $this->client->send($str);
    }

    /**
     * 读
     *
     * @return string
     */
    public function read() {
        $str = $this->client->recv();
        return $str;
        
        /*
         * $str =
         * "<root><result><id>0000</id><comment>撒旦法撒旦法</comment></result></root>";
         * return $str;
         */
        /*
         * $sequence = 0; $read = $write = array(); $expect_reply = true;
         * $retries_left = 1; $poll = new ZMQPoll(); $poll->add($this->client,
         * ZMQ::POLL_IN); while($expect_reply) { // Poll socket for a reply,
         * with timeout $events = $poll->poll($read, $write,
         * $this->config['timeout'] * 1000); // If we got a reply, process it
         * if($events > 0) { // We got a reply from the server, must match
         * sequence $reply = $this->client->recv(); if(intval($reply) ==
         * $sequence) { printf ("I: server replied OK (%s)%s", $reply, PHP_EOL);
         * $retries_left = 1; $expect_reply = false; } else { printf ("E:
         * malformed reply from server: %s%s", $reply, PHP_EOL); } } else
         * if(--$retries_left == 0) { echo "E: server seems to be offline,
         * abandoning", PHP_EOL; break; } else { echo "W: no response from
         * server, retrying…", PHP_EOL; // Old socket will be confused; close it
         * and open a new one $client = client_socket($context); // Send request
         * again, on new socket $client->send($my_data); } }
         */
    }
}

?>