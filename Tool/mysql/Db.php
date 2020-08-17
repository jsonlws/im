<?php
declare(strict_types=1);
class Db{

    public $conn;
    private $table_name;
    private $prefix;
    private $sql = [
         'where'   => null,
         'orderBy' => null,
         'limit'   => null,
      ];
    private static $instance;//单例对象

    public function __construct()
    {
        //配置文件
        $mysqlConfig = parse_ini_file(__DIR__.'/../../config/config.ini',true)['db'];
        //表前缀
        $this->prefix = $mysqlConfig['prefix'];
        $dsn = $mysqlConfig['db_type'].':host='.$mysqlConfig['host'].';dbname='.$mysqlConfig['db_name'];
        try{
            $this->conn =  new PDO($dsn,$mysqlConfig['user'],$mysqlConfig['pwd']);
        }catch (Throwable $e){
            throw new RuntimeException('mysql服务不可用');
        }
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }


    public static function getInstance()
    {
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public  function table(string $table_name)
    {
        $this->table_name = $this->prefix.$table_name;
        return $this;
    }


    /**
     * 获取多个结果
     * @param string $fields
     * @return array|bool|string
     */
    public function getAll(string $fields='*'){
        $querySql = sprintf("SELECT %s FROM %s", $fields, $this->table_name);
        if(!empty($this->sql['where'])) {
            $querySql .= ' WHERE ' . $this->sql['where'];
        }
        if(!empty($this->sql['orderBy'])) {
            $querySql .= ' ORDER BY ' . $this->sql['orderBy'];
        }
       if(!empty($this->sql['limit'])) {
            $querySql .= ' LIMIT ' . $this->sql['limit'];
        }
        return $this->query($querySql);
    }

    /**
     * 查询单个
     * @param string $fields
     * @return mixed|null
     */
    public function getOne($fields = '*') {
        $result = $this->getAll($fields);
        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * 添加操作
     * @param $data
     * @return array|bool|string
     */
    public function insert(array $data) {
        foreach ($data as $key => &$value) {
            $value = addslashes($value);
        }
        $keys = "`".implode('`,`', array_keys($data))."`";
        $values = "'".implode("','", array_values($data))."'";
        $querySql = sprintf("INSERT INTO %s ( %s ) VALUES ( %s )", $this->table_name, $keys, $values);
        return $this->query($querySql);
     }

    /**
     * 删除操作
     * @return array|bool|string
     */
    public function delete() {
        $querySql = sprintf("DELETE FROM %s WHERE ( %s )", $this->table_name, $this->sql['where']);
        return $this->query($querySql);
     }

    /**
     * 修改操作
     * @param $data
     * @return array|bool|string
     */
    public function update(array $data) {
        $updateFields = [];
        foreach ($data as $key => $value) {
               $up_value = addslashes($value);
                $updateFields[] = "`$key`='$up_value'";
        }
        $updateFields = implode(',', $updateFields);
        $querySql = sprintf("UPDATE %s SET %s", $this->table_name, $updateFields);

        if(!empty($this->sql['where'])) {
             $querySql .= ' WHERE ' . $this->sql['where'];
        }
        return $this->query($querySql);
      }



    public function query($querySql) {
        $querystr = strtolower(trim(substr($querySql,0,6)));
        $stmt = $this->conn->prepare($querySql);
        $ret = $stmt->execute();
        if(!$ret) print_r($stmt->errorInfo());
        if($querystr == 'select') {
            $retData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $retData;
        }elseif($ret && $querystr == 'insert') {
            return $this->conn->lastInsertId();
        }else{
            return $ret;
        }
     }


    public function limit($limit, $limitCount = null) {
        if(!$limitCount) {
               $this->sql['limit'] = $limit;
        }else{
                $this->sql['limit'] = $limit .','. $limitCount;
        }
        return $this;
    }

    public function orderBy($orderBy) {
        $this->sql['orderBy'] = $orderBy;
        return $this;
    }

    public function close() {
           return $this->conn = null;
    }

    /**
     * @param $where
     * @return $this|null
     */
     public function where($where) {
        if(!is_array($where)) {
              return null;
        }
        $crondsArr = [];
        foreach ($where as $key => $value) {
            $fieldValue = $value;
            if(is_array($fieldValue)) {
                $crondsArr[] = "$key ".$fieldValue[0]. ' ' . addslashes($fieldValue[1]);
            }else{
                $fieldValue = addslashes($fieldValue);
                $crondsArr[] = "$key='$fieldValue'";
            }
        }
        $this->sql['where'] = implode(' AND ', $crondsArr);
        return $this;
     }




    /**
     * @param $where
     * @return $this|null
     */
    public function orwhere($where) {
        if(!is_array($where)) {
            return null;
        }
        $crondsArr = [];
        foreach ($where as $key => $value) {
            $fieldValue = $value;
            if(is_array($fieldValue)) {
                $crondsArr[] = "$key ".$fieldValue[0]. ' ' . addslashes($fieldValue[1]);
            }else{
                $fieldValue = addslashes($fieldValue);
                $crondsArr[] = "$key='$fieldValue'";
            }
        }
        $this->sql['where'] = implode(' OR ', $crondsArr);
        return $this;
    }

}