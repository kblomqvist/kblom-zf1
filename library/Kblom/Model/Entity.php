<?php
/**
 * Kblom_Model_Entity
 *
 * This class is adapted with a certain modifications from a GREAT book about
 * Zend Framework, Survive The Deep End, written by Pádraic Brady [1]. The idea
 * of Entity models and mappers was proposed by Benjamin Eberlei, but then
 * discontinued in favour of doctrine integration [2].
 *
 * "Entities are lightweight PHP Objects that don't need to extend any abstract
 *  base class or interface. An entity class must not be final or contain final
 *  methods. Additionally it must not implement clone nor wakeup or do so safely.
 *
 *  An entity contains persistable properties. A persistable property is an
 *  instance variable of the entity that is saved into and retrieved from the
 *  database ..." [3] For example your user model, Application_Model_User,
 *  is Entity.
 *
 * [1] http://survivethedeepend.com/
 * [2] http://framework.zend.com/wiki/pages/viewpage.action?pageId=9437243
 * [3] http://www.doctrine-project.org/docs/orm/2.0/en/tutorials/getting-started-xml-edition.html
 *
 * @category  Kblom
 * @package   Kblom_Model
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Model_Entity
{
	/**
	 * Entity data/properties
	 *
	 * Example:
	 * array(
	 *   'id'       => null,
	 *   'blogpost' => '',
	 *   'author'   => ''
	 * );
	 *
	 * @var array
	 * */
	protected $_data = array();

	/**
	 * Reference ids of refered entity models
	 *
	 * Provides lazy loading for these models
	 *
	 * Example:
	 * array(
	 *   'author' => 1 // Author model is found by this id
	 * );
	 *
	 * @var array
	 */
	protected $_references = array();

	public function __construct($data = null, $references = null)
	{
		if (!is_null($data)) {
			$this->setProperties($data);
		}
		if (!is_null($references)) {
			$this->setReferenceIds($references);
		}
	}

	public function setProperties($data)
	{
		if (!is_array($data)) { 
			throw new Exception('Invalid data type. Has to be array, ' 
				. gettype($data) . ' given.'); 
		}
		foreach ($data as $name => $value) {
			$this->{$name} = $value;
		}

		return $this;
	}

	public function toArray()
	{
		return $this->_data;
	}

	/**
	 * @param string $name Reference property name
	 * @param int|string $id Reference entity model's id
	 * @retrun Kblom_Model_Entity
	 */
	public function setReferenceId($name, $id)
	{
		if (!array_key_exists($name, $this->_data)) {
			throw new Exception(get_class($this)
				. ": invalid model reference, does not have property '$name'");

		}
		if (!is_numeric($id)) {
			$id = (string) $id;
		}
		$this->_references[(string) $name] = $id;

		return $this;
	}

	public function setReferenceIds(array $references)
	{
		foreach ($references as $name => $id) {
			$this->setReferenceId($name, $id);
		}
		return $this;
	}

	/**
	 * @param string $name Reference property name
	 * @return int|string Reference id or null if not exist
	 */
	public function getReferenceId($name)
	{
		if (array_key_exists($name, $this->_references)) {
			return $this->_references[$name];
		}
	}

	/**
	 * Sets property value
	 *
	 * You can override this magic setter by creating property specific
	 * setter method.
	 *
	 * @param string $name  Entity property name
	 * @param mixed  $value Entity property value
	 * @return null
	 * @throw Exception in case when invalid model property is set
	 */
	public function __set($name, $value)
	{
		if (!array_key_exists($name, $this->_data)) {
			throw new Exception(get_class($this)
				. ": '$name' is invalid model property");
		}
		
		$method = 'set' . $name;
		if (method_exists($this, $method)) {
			$this->$method($value);
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Returns property value
	 *
	 * You can override this magic getter by creating property specific
	 * getter method.
	 *
	 * @param string $name Entity property name
	 * @return mixed Entity property
	 */
	public function __get($name)
	{
		if (!array_key_exists($name, $this->_data)) {
			return null;
		}

		$method = 'get' . $name;
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		return $this->_data[$name];
	}

	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}

	public function __unset($name)
	{
		if (isset($this->_data[$name])) {
			unset($this->_data[$name]);
		}
	}
}
