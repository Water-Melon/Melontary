<?php
/*
 * Copyright (C) Niklaus F.Schen.
 */
require_once('melontary.php');
class mConf
{
    private $confs;

    function __construct()
    {
        $this->confs = array();
        $melon = new melontary;
        $conf_path = $melon->getConfPath();
        if (($fh = fopen($conf_path, 'r')) == false) {
            throw new Exception('open "'.$conf_path.'" failed.');
        }
        $parser = xml_parser_create('UTF-8');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($parser, "self::startElement", "self::endElement");
        while ($data = fread($fh, 4096)) {
            if (!xml_parse($parser, $data, feof($fh))) {
                $msg = 'xml_parse error: '.xml_error_string(xml_get_error_code($parser));
                $msg .= ('line: '.xml_get_current_line_number($parser));
                fclose($fh);
                xml_parser_free($parser);
                throw new Exception($msg);
            }
        }
        fclose($fh);
        xml_parser_free($parser);
    }
    private function startElement($parser, $name, $attributes)
    {
        if ($name == 'melontary') return;
        $array = array();
        foreach ($attributes as $key => $value) {
            $array[$key] = $value;
        }
        $this->confs[$name][] = $array;
    }
    private function endElement($parser, $name)
    {
        return;
    }

    public function get()
    {
        return $this->confs;
    }
}
?>
