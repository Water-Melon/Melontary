<?php
/*
 * Copyright (C) Niklaus F.Schen.
 */

require_once("melontary.php");

class mLog
{
    private $path;
    private $level;
    private $logging_file;
    /*conf params*/
    private $conf_log_level_emerg = "emerg";

    function __construct($logging_file = __FILE__)
    {
        $melon = new melontary(0);
        $conf_path = $melon->getConfPath();
        if (($fh = fopen($conf_path, 'r')) == false) {
            throw new Exception('Open '.$conf_path.' failed.');
        }
        $parser = xml_parser_create("UTF-8");
        $this->path = NULL;
        $this->level = NULL;
        $this->logging_file = $logging_file;

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($parser, "self::startElement", "self::endElement");

        while ($data = fread($fh, 4096)) {
            if (!xml_parse($parser, $data, feof($fh))) {
                $msg = "xml_parse error: ".xml_error_string(xml_get_error_code($parser));
                $msg .= ("line: ".xml_get_current_line_number($parser));
                error_log($msg, 0);
                fclose($fh);
                xml_parser_free($parser);
                throw new Exception($msg);
            }
        }
        fclose($fh);
        xml_parser_free($parser);
    }

    function __destruct()
    {
        $this->path = NULL;
        $this->level = NULL;
        $this->logging_file = NULL;
    }

    private function startElement($parser, $name, $attributes)
    {
        if ($name != 'log') {
            return;
        }

        $this->path = $attributes['path'];
        $this->level = $attributes['level'];

        if ($this->level != 'debug' &&
            $this->level != 'error' &&
            $this->level != 'emerg')
        {
            die("Invalid log level in configuration file.");
        }
    }

    private function endElement($parser, $name)
    {
        return;
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
