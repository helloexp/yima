<?php

interface MQ {

    public function write($str);

    public function read();

    public function connect();

    public function close();
}

?>