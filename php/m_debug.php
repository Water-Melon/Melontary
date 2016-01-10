<?php
/*
 * Copyright (C) Niklaus F.Schen.
 */

require_once('melontary.php');
require_once('m_log.php');

class mDebug
{
    private $status;

    function __construct()
    {
        $this->status = NULL;
        $melon = new melontary(0);
        $conf_path = $melon->getConfPath();
        if (($fh = fopen($conf_path, 'r')) == false) {
            throw new Exception('Open '.$conf_path.' failed.');
        }
        $parser = xml_parser_create("UTF-8");

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($parser, "self::startElement", "self::endElement");

        while ($data = fread($fh, 4096)) {
            if (!xml_parse($parser, $data, feof($fh))) {
                $msg = "xml parse error: ".xml_error_string(xml_get_error_code($parser));
                $msg .= ("line: ".xml_get_current_line_number($parser));
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
        $this->status = NULL;
    }

    private function startElement($parser, $name, $attributes)
    {
        if ($name != 'debug') {
            return;
        }

        $this->status = $attributes['status'];
        if ($this->status != 'on' && $this->status != 'off') {
            $logger = new mLog(__FILE__);
            $logger->logging('emerg', "debug status should be 'on' or 'off'.");
        }
    }

    private function endElement($parser, $name)
    {
        return;
    }

    public function status()
    {
        return $this->status;
    }
}
?>
