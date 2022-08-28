<?php


class myserver_db {

    var $querynum = 0;
    var $link;
    var $histories;
    var $dbhost;
    var $dbuser;
    var $dbpw;
    var $dbcharset;
    var $pconnect;
    var $tablepre;
    var $time;
    var $sql;
    var $select;
    var $from;
    var $where;
    var $limit;
    var $order;
    var $leftjoin;
    var $goneaway = 5;

    function connect($dbhost, $dbuser, $dbpw, $dbname = '', $dbcharset = '', $pconnect = 0, $tablepre = '', $time = 0) {

        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpw = $dbpw;
        $this->dbname = $dbname;
        $this->dbcharset = $dbcharset;
        $this->pconnect = $pconnect;
        $this->tablepre = $tablepre;
        $this->time = $time;
        $this->reconnect();
    }

    private function reconnect() {
        if($this->link!=NULL)
            return;
        $this->link = mysqli_connect("p:" . $this->dbhost, $this->dbuser, $this->dbpw, $this->dbname);
        if (mysqli_connect_errno()) {
            $this->halt('Can not connect to MySQL server');
        }
        if (!mysqli_set_charset($this->link, "utf8")) {
            $this->halt('Error loading character set utf8');
        }
    }

    private function fetch_array($query, $result_type = MYSQLI_ASSOC) {
        return mysqli_fetch_array($query, $result_type);
    }

    function result_first($sql) {
        $query = $this->query($sql,1);
        return $this->result($query, 0);
    }

    function fetch_first($sql) {
        $query = $this->query($sql,1);
        return $this->fetch_array($query);
    }

    function fetch_all($sql, $id = '') {
        $arr = array();
        $query = $this->query($sql,1);
        while ($data = $this->fetch_array($query)) {
            $id ? $arr[$data[$id]] = $data : $arr[] = $data;
        }
  
        return $arr;
    }

    function query($sql, $type = '', $cachetime = FALSE) {
        if($type != 1){
            $sqls = strtoupper(trim($sql));
            if($sqls !== "START TRANSACTION" && $sqls !== "COMMIT"){
                $message = "MySql get sql";
                //file_put_contents('/home/web/logs/web/mysql_info_'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." >>> ".$message . " " . $sql. "\n", FILE_APPEND);
            }
        }
        if (!$query = mysqli_query($this->link, $sql)) {
            $this->halt('MySQL Query Error', $sql);
        }
        return $query;
    }


    function affected_rows() {
        return mysqli_affected_rows($this->link);
    }


    function error() {
        return (($this->link) ? mysqli_error($this->link) : mysqli_error());
    }

    function errno() {
        return mysqli_connect_errno();
    }

    function result($query, $row) {
        $query = mysqli_fetch_row($query);
        if ($query)
            return $query[$row];
    }

    function num_rows($query) {
        $query = mysqli_num_rows($query);
        return $query;
    }

    function num_fields($query) {
        return mysqli_num_fields($query);
    }

    function free_result($query) {
        return mysqli_free_result($query);
    }

    function insert_id() {
        return mysqli_insert_id($this->link);
    }

    function fetch_row($query) {
        $query = mysqli_fetch_row($query);
        return $query;
    }

    function fetch_fields($query) {
        return mysqli_fetch_fields($query);
    }

    function version() {
        return mysqli_get_server_info($this->link);
    }

    public function __destruct() {
        $this->close();
    }

    function close() {
        return mysqli_close($this->link);
    }

    function escape($value) {
        return mysqli_real_escape_string( $this->link,$value);
    }

    function createCommand($sql = '') {
        $this->sql = $sql;
        $this->ini();
        return $this;
    }

    function ini() {
        $this->select = $this->from = $this->leftjoin = $this->where = $this->order = '';
        $this->limit = ' limit 200';
    }

    function select($s) {
        $s = $this->fieldfilter($s);
        $this->select = 'select ' . $s;
        return $this;
    }

    /**
     * @param $fields
     * @return mixed
     */
    public function fieldfilter($fields) {
        $fields = ' '. strtolower($fields) .' ';
        $arr = [
            '/*',
            '#',
            ' delete ',
            ' from ',
            ' drop ',
            ' select ',
            ' alter ',
            ' create ',
            ' delimiter ',
            ' call ',
            '-- ',
        ];
        return str_replace($arr, ' ', $fields);
    }
    function from($f) {
        $f = $this->fieldfilter($f);
        $this->from = ' from ' . $f;
        return $this;
    }

    function getLastInsertID() {
        return $this->insert_id();
    }

    function insert($t, $v) {
        $str = '';
        foreach ($v as $k => $tv) {
            $str.=',`' . $k . '`=\'' . $this->escape($tv) . '\'';
        }
        $str = ltrim($str, ',');
        $this->sql = 'insert into ' . $t . ' set ' . $str;
        $this->query($this->sql,1);
    }

    function replace($t, $v) {
        $str = '';

        foreach ($v as $k => $tv) {
            if ($k == 0) {
                $str .= ' (' . implode(",", $tv) . ') values';
            } else {
				$str_replace = '';
				foreach ($tv as  $key => $vo) {
					$str_replace .= ',\'' .$this->escape($vo). '\'';
				}
				$str .= '(' .ltrim($str_replace, ',') . '),';
			}
		}
		$str = trim($str, ',');
		$this->sql = 'insert into ' . $t . $str;
		// echo $this->sql;die;
		return $this->query($this->sql,1);
	}


    function update($table, $field, $w, $a = array()) {
        $str = '';
        foreach ($field as $k => $tv) {
            $str.=',`' . $k . '`=\'' . $this->escape($tv) . '\'';
        }
        $str = ltrim($str, ',');

        $where = '';
        if ($a) {
            $v = array_values($a);
            $a = array_keys($a);

            foreach ($v as &$t) {
                $t = '\'' . $this->escape($t) . '\'';
            }
            $where = ' where ' . str_replace($a, $v, $w);
        } elseif ($w)
            $where = ' where ' . $w;
        $this->sql = 'update ' . $table . ' set ' . $str . $where;
        return $this->query($this->sql,1);
    }

    function delete($table, $w, $a = array()) {
        $where = '';
        if (!trim($w))
            return false;
        if ($a) {
            $v = array_values($a);
            $a = array_keys($a);

            foreach ($v as &$t) {
                $t = '\'' . $this->escape($t) . '\'';
            }
            $where = ' where ' . str_replace($a, $v, $w);
        } elseif ($w)
            $where = ' where ' . $w;
        $this->sql = 'delete from ' . $table . $where;
        return $this->query($this->sql,1);
    }

    /**
     *
     * @param type $a 类似：array(':a',':b',':c')要替换的key
     * @param type $v 类似: array(2,3,4);要替换的值
     * @return \ucserver_db
     */
    function bindValues($a) {
        $v = array_values($a);
        $a = array_keys($a);
        if ($a && $v) {
            foreach ($v as &$t) {
                $t = '\'' . $this->escape($t) . '\'';
            }
            $this->sql = str_replace($a, $v, $this->sql);
        }
        return $this;
    }

    /**
     * where(array('in','field',array(1,2,3,4)))
     * where('field=:i',array(':i'=>2))
     * @param type $w
     * @param type $a
     * @return \ucserver_db
     */
    function where($w, $a = array(), $isand = false) {
        if (is_array($w)) {
            if ($w[0] == 'in') {
                foreach ($w[2] as &$t) {
                    $t = '\'' . $this->escape($t) . '\'';
                }
                $tstr = implode(',', $w[2]);
				$tstr = $tstr ? $tstr : '-1000';
                if ($isand == true) {
                    //$this->where=$this->where?' where '.$w[1].' in ('.$tstr.')':$this->where.' and '.$w[1].' in ('.$tstr.')';
                    $this->where = $this->where ? $this->where . ' and ' . $w[1] . ' in (' . $tstr . ')' : ' where ' . $w[1] . ' in (' . $tstr . ')';
                } else
                    $this->where = ' where ' . $w[1] . ' in (' . $tstr . ')';
            }
        }
        elseif ($a) {
            $v = array_values($a);
            $a = array_keys($a);

            foreach ($v as &$t) {
                $t = '\'' . $this->escape($t) . '\'';
            }
            if ($isand === true) {
                //$this->where=$this->where?' where '.str_replace($a,$v,$w):$this->where.' and '.str_replace($a,$v,$w);
                $this->where = $this->where ? $this->where . ' and ' . str_replace($a, $v, $w) : ' where ' . str_replace($a, $v, $w);
            } else
                $this->where = ' where ' . str_replace($a, $v, $w);
        } elseif ($w) {
            if ($isand === true) {
                $this->where = $this->where ? ' where ' . $w : $this->where . ' and ' . $w;
            } else
                $this->where = ' where ' . $w;
        }

        return $this;
    }

    function leftJoin($add1, $add2) {
        $this->leftjoin = ' left join ' . $add1 . ' on ' . $add2;
        $this->leftjoin = $this->fieldfilter($this->leftjoin);
        return $this;
    }

    function limit($limit, $offset = 0) {
        $this->limit = ' limit ' . (int) $offset . ',' . (int) $limit;
        return $this;
    }

    function order($o) {
        if($o){
            $o = $this->fieldfilter($o);
        $this->order = ' order by ' . $o;
        }
        return $this;
    }

    function groupsql() {
        if (!$this->sql) {
            $this->sql = $this->select . $this->from . $this->leftjoin . $this->where . $this->order . $this->limit;
        }
    }

    function queryAll() {
        $this->groupsql();
        return $this->fetch_all($this->sql);
    }

    function execute() {
        return $this->query($this->sql,1);
    }

    function queryRow() {
        $this->groupsql();
        return $this->fetch_first($this->sql);
    }

    function halt($message = '', $sql = '') {
        if($this->errno() <= 2006 && $this->errno() >= 2000){
            $this->link = NULL;
            $this->reconnect();
        }
        if(DEBUG_ENABLE){
        echo $message."\n".$sql."\n".$this->error();
        }else{
          // file_put_contents('/home/web/logs/web/mysql_error'.date('Y-m-d').'.txt',$message . " " . $sql. " error:" . $this->error()." erron:". $this->errno() . " " . $this->print_stack_trace()." ", FILE_APPEND);
        }
        return NULL;
    }
    function print_stack_trace()
    {
        $array =debug_backtrace();
      //print_r($array);//信息很齐全
       unset($array[0]);
       foreach($array as $row)
        {
           $html .=$row['file'].':'.$row['line'].'行,调用方法:'.$row['function']."\n";
        }
        return $html;
    }
    public function begin_transaction($mr = MYSQLI_TRANS_START_READ_WRITE) {
        mysqli_begin_transaction($this->link, $mr);
    }

    public function commit() {
        mysqli_commit($this->link);
    }

    public function rollback() {
        mysqli_rollback($this->link);
    }
    public function select_db($dbname) {
        mysqli_select_db($this->link, $dbname);
    }
    function group($o) {
        if($o){
            $o = $this->fieldfilter($o);
        $this->order = ' group by ' . $o;
        }
        return $this;
    }
}

?>
