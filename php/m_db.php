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

    private function quoteField(&$field)
    {
        return "`".$field."`";
    }
    private function quoteValue(&$value)
    {
        return "'".$value."'";
    }

    private function where(array $conditions, $logic, $relation=array())
    {
        if (empty($conditions)) return '';

        $result = array();
        foreach ($conditions as $key => $value) {
            if (!is_array($relation) || !isset($relation[$key])) {
                $result[] = $this->quoteField($key).'='.$this->quoteValue($value);
            } else {
                $result[] = $this->quoteField($key).$relation[$key].$this->quoteValue($value);
            }
        }

        return 'where '.implode($logic, $result);
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
    public function &getDBHandle($utility)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        return $this->connections[$utility]->getDBHandle();
    }

    public function insert($utility, $table, array $data, $ignore=false)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->getDBHandle($utility);

        $fields = $values = array();
        foreach ($data as $key => $value) {
            $fields[] = $this->quoteField($key);
            $values[] = $this->quoteValue(addslashes($value));
        }
        $sql = "insert";
        if ($ignore) $sql .= " ignore";
        $sql .= " into ".$table."(".implode(',', $fields).") values(".implode(',', $values).")";
        if (mysql_query($sql, $handle) == false) {
            return false;
        }
        return true;
    }
    public function delete($utility, $table, array $conditions=array(), $logic='AND')
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->getDBHandle($utility);

        $sql = 'delete from '.$table.$this->where($conditions, $logic);

        if (mysql_query($sql, $handle) == false) {
            return false;
        }
        return true;
    }
    public function update($utility, $table, array $data, array $conditions=array(), $logic='AND', $ignore=false)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->getDBHandle($utility);

        /*set*/
        $set = "set ";
        $fields = array();
        foreach ($data as $key => $value) {
            $fields[] = $this->quoteField($key).'='.$this->quoteValue($value);
        }
        $set .= implode(',', $fields);

        $sql = 'update ';
        if ($ignore) $sql .= "ignore ";
        $sql .= $table.' '.$set.' ';
        $sql .= $this->where($conditions, $logic);

        if (mysql_query($sql, $handle) == false) {
            return false;
        }
        return true;
    }
    /*
     * infos:
     *       'table'         => tablename
     *       'fields'        => Fields of result
     *       'where'         => [array] where condition
     *       'relation'      => relationship of key-value in where
     *       'logic'         => where logic
     *       'orderField'    => order by field
     *       'orderSequence' => orderby Sequence
     *       'limitStep'     => step of limit
     *       'limitOffset'   => offset of limit
     */
    public function select($utility, array $infos)
    {
        if (!$this->connectionExistent($utility) || !$this->connections[$utility]->isActive())
            return false;
        $handle = &$this->getDBHandle($utility);

        $sql = "select ";
        if (!isset($infos['fields'])) {
            $sql .= '*';
        } else if (is_array($infos['fields'])) {
            $sql .= implode(',', $infos['fields']);
        } else {
            $sql .= $infos['fields'];
        }
        $sql .= ' from '.$infos['table'].' ';

        $sql .= $this->where($infos['where'], $infos['logic'], $infos['relation']);

        if (isset($infos['orderField'])) {
            $sql .= ' order by '.$infos['orderField'].' ';
            if (isset($infos['orderSequence'])) {
                $sql .= $infos['orderSequence'];
            }
        }

        if (isset($infos['limitStep'])) {
            $sql .= ' limit ';
            if (isset($infos['limitOffset'])) {
                $sql .= strval($infos['limitOffset']).',';
            }
            $sql .= strval($infos['limitStep']);
        }

        $result = mysql_query($sql, $handle);
        if ($result === false) return false;

        $rows = array();
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysql_free_result($result);

        return $rows;
    }
}
?>
