<?php
/*
 * Copyright (C) Niklaus F.Schen.
 */
require_once('m_conf.php');

class mDebug
{
    private $status;

    function __construct($conf)
    {
        $this->status = $conf->get()['debug'][0]['status'];
    }

    function __destruct()
    {
        $this->status = NULL;
    }

    public function status()
    {
        return $this->status;
    }
}
?>
