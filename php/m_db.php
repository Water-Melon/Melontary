<?php
/*
 * Copyright (C) Niklaus F.Schen.
 */
require_once('melontary.php');

class mDBConnection
{
    private $user;
    private $passwd;
    private $ip;
    private $port;
    private $bases;
    private $connection;

    function __construct($user, $passwd, $ip, $port)
    {
        $this->user = $user;
        $this->passwd = $passwd;
        $this->ip = $ip;
        $this->port = $port;
        $this->bases = array();
        $this->connection = NULL;
    }
    function __destruct()
    {
        $this->user = NULL;
        $this->passwd = NULL;
        $this->ip = NULL;
        $this->port = NULL;
        unset($this->bases);
        $this->close();
    }

    public function connect()
    {
        $this->connection = mysql_connect($this->ip.':'.$this->port, $this->user, $this->passwd);
        if (!$this->connection) {
            return false;
        }
        return true;
    }
    public function close()
    {
        if ($this->connection != NULL) {
            mysql_close($this->connection);
            $this->connection = NULL;
        }
    }
    public function &getDBHandle()
    {
        return $this->connection;
    }

    public function isActive()
    {
        return $this->connection==NULL? false: true;
    }
    public function error()
    {
        return mysql_error($this->connection);
    }
}

class mDB
{
    private $connections;

    function __construct()
    {
        $this->connections = array();

        $melon = new melontary(0);
        $conf_path = $melon->getConfPath();
        if (($fh = fopen($conf_path, 'r')) == false) {
            throw new Exception('Open '.$conf_path.' failed.');
        }
        $parser = xml_parser_create('UTF-8');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($parser, 'self::startElem', 'self::endElem');

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
        unset($this->connections);
    }

    private function startElem($parser, $name, $attributes)
    {
        if ($name === 'connection') {
            $connection = new mDBConnection($attributes['user'],
                                            $attributes['passwd'],
                                            $attributes['ip'],
                                            $attributes['port']);
            $this->connections[$attributes['utility']] = $connection;
            return;
        }
    }
    private function endElem($parser, $name)
    {
        return;
    }

    public function connectionExistent($utility)
    {
        if (isset($this->connections[$utility]))
            return true;
        return false;
    }

    public function error($utility)
    {
        if (!isset($this->connections[$utility]))
            return NULL;
        return $this->connections[$utility]->error();
    }

    public function connect($utility)
    {
        if (!$this->connectionExistent($utility))
            return false;
        return $this->connections[$utility]->connect();
    }

    public function close($utility)
    {
        if (!$this->connectionExistent($utility))
            return;
        $this->connections[$utility]->close();
    }

    public function useDatabase($utility, $dbname)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->connections[$utility]->getDBHandle();
        if (mysql_query('use '.$dbname, $handle) == false) {
            return false;
        }
        return true;
    }
    public function createDatabase($utility, $dbname)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->connections[$utility]->getDBHandle();
        if (mysql_query('create database '.$dbname, $handle) == false) {
            return false;
        }
        return true;
    }
    public function dropDatabase($utility, $dbname)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->connections[$utility]->getDBHandle();
        if (mysql_query('drop database '.$dbname, $handle) == false) {
            return false;
        }
        return true;
    }
    public function execute(/*$utility, ...*/)
    {
        if (($num = func_num_args()) == 0) return false;

        $utility = func_get_arg(0);
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->connections[$utility]->getDBHandle();

        $sql = '';
        for ($i = 1; $i < $num; $i++) {
            $value = func_get_arg($i);
            if (is_int($value) || is_float($value) || is_bool($value)) {
                $sql .= strval($value);
            } else if (is_string($value)) {
                $sql .= $value;
            } else {
                return false;
            }
            if ($i + 1 < $num) $sql .= ' ';
        }
        $sql .= ';';
        return mysql_query($sql, $handle);
    }
}
?>
