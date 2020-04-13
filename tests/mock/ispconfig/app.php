<?php

use PHPUnit\Framework\MockObject\MockObject;

class app
{
    public $db;
    public $tform;
    public $error;
    public $tpl;

    public function __construct()
    {
        $this->db = new db();
        $this->tform = new tform();
        $this->tpl = new tpl();
    }

    public function error($msg)
    {
        $this->error = $msg;
    }

    public function log($msg) {}
}
