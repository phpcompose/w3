<?php
namespace W3\Db\Model;

use Doctrine\DBAL\Connection;
use Exception;

/**
 * Simple to active record model,
 *
 * model provides a single object to both retrive and manupulate
 * object from single database table.
 *
 * some rules and assumption of the database table design
 * * all fields/columns are lower cased, OR database server is case insancitive for fields/column names
 * * table name is capitilized OR database is case insensative for table name, OR subclass defines table name
 * * default primary key field name is 'pk', however, this can be changed by subclassing
 * * default table name is the name of the sub class name, unless changed by subclass.
 */
abstract class ActiveObject extends DataObject
{
    protected static
        /**
         * @var Connection Shared connection among all records
         */
        $_sharedConnection = null;

    protected
        /**
         * @var Connection local connection for the object
         */
        $_connection = null;

    protected
        $_pk = 'pk',
        $_table = null;

    /**
     * construct the object
     *
     * @param array $data
     * @param Connection $conn
     */
    public function __construct(array $data = null, Connection $conn = null)
    {
        parent::__construct($data);
        if ($conn) {
            $this->connection($conn);
        }

        $this->onInit();
    }

    /**
     * @param Connection|null $conn
     * @return Connection|null
     */
    public function connection(Connection $conn = null) : ?Connection
    {
        if ($conn) {
            $this->_connection = $conn;
        }

        return $this->_connection ?? self::$_sharedConnection;
    }

    /**
     * returns database table name represented by this model
     *
     * if table name is not defined by the sub class then
     * the name of the Class will be used as database table
     * @return string
     */
    public function getTable()
    {
        if ($this->_table) {
            return $this->_table;
        } else {
            /*
             * classname might have namespaces
             * we need to remove them
             */
            $clsname = get_called_class();
            $clsparts = explode('\\', $clsname);
            return end($clsparts);
        }
    }

    /**
     * getPkColumn function.
     *
     * @access public
     */
    public function getPkColumn()
    {
        return $this->_pk;
    }

    /**
     *
     * @access public
     * @return int
     */
    public function getPkValue()
    {
        return $this->{$this->getPkColumn()} ?? 0;
    }

    /**
     * @param $id
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function load($id) : bool
    {
        $data = $this->connection()
            ->executeQuery("SELECT * FROM `{$this->getTable()}` WHERE `{$this->getPkColumn()}` = ?", [$id])
            ->fetch();

        if(!$data) {
            return false;
        }

        $this->onLoad($data);
        $this->addData($data);
        $this->reset(); // clear modified
        return true;
    }

    /**
     * @param $pkval
     * @param Connection|null $connection
     * @return ActiveObject|null
     */
    public static function find($pkval, Connection $connection = null)
    {
        $obj = new static(null, $connection);
        if(!$obj->load($pkval)) {
            return null;
        }

        return $obj;
    }

    /**
     * @throws Exception
     */
    public function save()
    {
        $this->onSave();
        if (!$this->isModified()) {
            return false;
        }

        if (!$this->onPreSave()) {
            return false;
        }

        $pkvalue = $this->getPkValue();
        if ($pkvalue) { // update
            $result = $this->_update();
        } else { // insert
            $result = $this->_insert();
        }

        $this->onPostSave();
        $this->reset();
        return $result;
    }

    /**
     * removes the current data row from the database
     * @return int
     * @throws Exception
     */
    public function delete()
    {
        // event call and check
        $pre = $this->onPreDelete();
        if ($pre === null) {
            throw new Exception("onPreDelete must return boolean value");
        } else {
            if (!$pre) {
                return false;
            }
        }

        $conn = $this->connection();
        $pkfield = $this->getPkColumn();
        $pkvalue = $this->getPkValue();
        if (!$pkvalue) {
            return false;
        }

        $result = $conn->delete($this->getTable(), array($pkfield => $pkvalue));
        $this->onPostDelete($result);
        $this->reset();
        return $result;
    }

    /**
     * @throws Exception
     */
    protected function _insert()
    {
        // event call and check
        $pre = $this->onPreInsert();
        if ($pre === null) {
            throw new Exception("onPreInsert must return boolean value");
        } else {
            if (!$pre) {
                return \FALSE;
            }
        }

        $db = $this->connection();
        $table = $this->getTable();
        $pkfield = $this->getPkColumn();
        $data = $this->getModifiedData();

        $id = 0;
        $result = $db->insert($table, $data);
        if($result) {
            $id = $db->lastInsertId();
            $this->$pkfield = $id;
            // event call
            $this->onPostInsert($id);
        }

        return $id;
    }

    /**
     * @throws Exception
     */
    protected function _update()
    {
        // event call and check
        $pre = $this->onPreUpdate();
        if ($pre === null) {
            throw new Exception("onPreUpdate must return boolean value");
        } else {
            if (!$pre) {
                return \FALSE;
            }
        }

        $db = $this->connection();
        $table = $this->getTable();
        $pkfield = $this->getPkColumn();
        $pkvalue = $this->getPkValue();
        $data = $this->getModifiedData();

        // update data in the database
        $result = $db->update($table, $data, [$pkfield => $pkvalue]);
        $this->onPostUpdate($result);
        return $result;
    }

    protected function onLoad(array $data) {}

    protected function onInit() {}

    protected function onPreInsert()
    {
        return true;
    }

    protected function onPostInsert($id) {}

    protected function onPreUpdate()
    {
        return true;
    }

    protected function onPostUpdate($result) {}

    protected function onPreDelete()
    {
        return true;
    }

    protected function onPostDelete($result) {}

    protected function onSave() {}

    protected function onPreSave()
    {
        return true;
    }

    protected function onPostSave() {}
}