<?php
namespace W3\Db\Model;

use Exception;
use ArrayObject;
use SplSubject;
use SplObserver;

/**
 * Data object class
 *
 * simple array based data object model
 * provides varies ways to store/access data in a single class
 * provides iteration and array access
 */
class DataObject extends ArrayObject implements SplSubject
{
    protected
        $_modified = [],      // store all modified data
        $_observers = null;

    protected static
        $_schema = null;

    /**
     *
     * @access public
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        parent::__construct();
        $this->setFlags(self::ARRAY_AS_PROPS); // both array and property has same storage

        // initially we specify that nothing is modified
        if ($data) {
            $this->addData($data);
        }

        $this->reset();
    }

    /**
     * @param array $arr
     */
    public function setData(array $arr)
    {
        $this->exchangeArray($arr);
    }

    /**
     * appends/modifies data to current data
     * if data already exists, then it will be modified.
     *
     * @param array $arr
     */
    public function addData($arr)
    {
        foreach ($arr as $key => $val) {
            $this[$key] = $val;
        }
    }

    /**
     * Sets data for the currently defined keys only.
     *
     * Any other data provided in the $arr will be ignored
     * @param array $arr Any key/value that was not defined/set
     * @return array
     */
    public function setDataForDefinedKeys($arr)
    {
        foreach ($arr as $key => $val) {
            if (isset($this[$key])) {
                $this[$key] = $val;
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * Get modified key/value pairs.
     *
     * This will return modified keys with *modified* data, not the original
     * @see getModifiedKeys()
     * @return array
     */
    public function getModifiedData()
    {
        $arr = [];
        foreach ($this->getModifiedKeys() as $key) {
            $arr[$key] = $this[$key];
        }

        return $arr;
    }

    /**
     * Get modified key array.
     *
     * @see getModifiedData()
     * @return array
     */
    public function getModifiedKeys()
    {
        return array_keys($this->_modified);
    }

    /**
     * clear all modified values/resets
     */
    public function reset()
    {
        $this->_modified = [];
    }

    /**
     * Check if any value has been modified.
     *
     * If $key is given, then only checks if given $key is modified,
     * else it will return true if ANY data is modified.
     *
     * @return bool
     * @param string $key
     */
    public function isModified($key = null)
    {
        if ($key) {
            return (isset($this->_modified[$key]));
        } else {
            return (count($this->_modified) > 0);
        }
    }

    /**
     * Restore modified values.
     *
     * @access public
     * @return void
     */
    public function restore()
    {
        foreach ($this->_modified as $key => $value) {
            parent::offsetSet($key, $value); // use parent's setter so won't trigger
        }
    }

    /**
     * attach observer for model changes
     *
     * @access public
     * @param \SplObserver $observer
     * @return void
     */
    public function attach(SplObserver $observer)
    {
        if ($this->_observers === null) {
            $this->_observers = new \SplObjectStorage();
        }

        $this->_observers->attach($observer);
    }

    /**
     * Remove observer from listening to changes.
     *
     * @access public
     * @param \SplObserver $observer
     * @return void
     */
    public function detach(SplObserver $observer)
    {
        if (!$this->_observers) {
            return;
        }
        $this->_observers->detach($observer);
    }

    /**
     * notify observers.
     *
     * Do not call this method directly.
     * This method will be called automatically when changes happen
     * @access public
     * @return void
     */
    public function notify()
    {
        if ($this->_observers) {
            foreach ($this->_observers as $obj) {
                $obj->update($this);
            }
        }
    }

    /**
     * offsetSet function.
     *
     * @access public
     * @param mixed $key
     * @param mixed $value
     * @throws Exception
     */
    function offsetSet($key, $value)
    {
        // first we need to check against schema
        if (static::$_schema !== null && !array_key_exists($key, static::$_schema)) {
            throw new Exception("Key `{$key}` not found in the schema defined.");
        }

        $curval = isset($this[$key]) ? $this[$key] : null;
        if ($curval === $value) {
            return;
        } // same value, do nothing
        // update data
        $this->_modified[$key] = $curval;

        parent::offsetSet($key, $value);
        $this->notify();
    }

    /**
     * offsetUnset function.
     *
     * @access public
     * @param string $key
     * @return void
     */
    function offsetUnset($key)
    {
        if (!isset($this[$key])) {
            $this->_modified[$key] = null;
        } else {
            $this->_modified[$key] = $this[$key];
        }

        parent::offsetUnset($key);
        $this->notify();
    }

    /**
     * @note Need to override so that delayed construction call by PDO Statement fetching has correct behaviour.
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this[$name] = $value;
    }
}