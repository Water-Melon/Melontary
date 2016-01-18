<?php
/*
 * Copyright (C) Niklaus F.Schen.
 */

require_once("m_conf.php");

class mLog
{
    private $path;
    private $level;
    private $logging_file;
    /*conf params*/
    private $conf_log_level_emerg = "emerg";

    function __construct($logging_file, $conf)
    {
        $confs = $conf->get();
        $this->path = $confs['log'][0]['path'];
        $this->level = $confs['log'][0]['level'];
        $this->logging_file = $logging_file;
        if ($this->level != 'debug' &&
            $this->level != 'error' &&
            $this->level != 'emerg')
        {
            throw new Exception("Invalid log level in configuration file.");
        }
    }

    function __destruct()
    {
        $this->path = NULL;
        $this->level = NULL;
        $this->logging_file = NULL;
    }

    public function logging($level, $message)
    {
        date_default_timezone_set('UTC');
        switch ($level) {
            case 'debug':
                $msg = date('r')." [DEBUG] in file:[".$this->logging_file."]: ".$message."\n";
                echo $msg;
                break;
            case 'error':
                $msg = date('r')." [ERROR] in file:[".$this->logging_file."]: ".$message."\n";
                break;
            case 'emerg':
                $msg = date('r')." [EMERG] in file:[".$this->logging_file."]: ".$message."\n";
                break;
            default:
                return;
        }
        error_log($msg, 3, $this->path);
        if ($level == 'emerg') {
            die("Fatal error in runtime. Please check log file.\n");
        }
    }
}
?>
