<?php

use PHPUnit\Framework\MockObject\MockObject;

class app
{
    public $db;
    public $tform;
    public $error;

    public function __construct()
    {
        $this->db = new db();
        $this->tform = new tform();
    }

    public function error($msg)
    {
        $this->error = $msg;
    }

    public function log($msg) {}
}
