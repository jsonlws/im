<?php
/**
 * mysql数据库操作单例模式
 * Class MysqlPool
 */

class MysqlPool
{
    private static $instance;//单例对象
    private $pdo;

    //构造方法连接mysql，创建20mysql连接
    private function __construct($db='db')
    {
        //配置文件
        $mysqlConfig = parse_ini_file(__DIR__.'/../../config/config.ini',true)[$db];
        $dsn = $mysqlConfig['db_type'].':host='.$mysqlConfig['host'].';dbname='.$mysqlConfig['db_name'];
        try{
            $this->pdo =  new PDO($dsn,$mysqlConfig['user'],$mysqlConfig['pwd']);
        }catch (Throwable $e){
            throw new RuntimeException('mysql服务不可用');
        }
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }


    public static function getInstance($db='db')
    {
        if(is_null(self::$instance)){
            self::$instance = new self($db);
        }
        return self::$instance;
    }


    /**
     * 此方法主要用于修改和删除操作数据库使用
     * @param string $sql [预处理的sql语句]
     * @param array $data
     * @return bool
     */
    public function execute(string $sql,array $data):bool
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $val) {
            $stmt->bindValue($key + 1, $val);
        }
        $stmt->execute();
        $affect_row = $stmt->rowCount();
        $res = false;
        if ( $affect_row ) {
            $res = true;
        }
        return $res;
    }

    /**
     * 查找方法
     * @param string $sql
     * @param array $data
     * @return array
     */
    public function select(string $sql,array $data):array
    {
        $stmt = $this->pdo->prepare( $sql );
        foreach ($data as $key=>$val){
            $stmt->bindValue($key+1,$val);
        }
        $stmt->execute();
        $arr = [];
        while ( $row = $stmt->fetch() ) {
           $arr[] = $row;
        }
        return $arr;
    }

    /**
     * 插入数据库操作
     * @param string $sql
     * @param array $data
     * @return int
     */
    public function insert(string $sql,array $data):int
    {
        $stmt = $this->pdo->prepare( $sql );
        foreach ($data as $key=>$val){
            $stmt->bindValue($key+1,$val);
        }
        $stmt->execute();
        $insert_id = $this->pdo->lastInsertId();
        $res = 0;
        if ( $insert_id ){
            $res = $insert_id;
        }
        return $res;
    }

    /**
     * 开启事务
     */
    public function startTransaction(){
        return $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commitTransaction(){
        return $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollbackTransaction(){
        return $this->pdo->rollBack();
    }

    /**
     * 关闭连接
     */
    public function __destruct(){
        $this->pdo = null;
    }


}