<?php
/**
 * Kblom_Model_Mapper_Entity
 *
 * This class is adapted with a certain modifications from a GREAT book about
 * Zend Framework, Survive The Deep End, written by Pádraic Brady [1]. The idea
 * of Entity models and mappers was proposed by Benjamin Eberlei, but then
 * discontinued in favour of doctrine integration [2].
 *
 * "Entities are lightweight PHP Objects that don’t need to extend any abstract
 *  base class or interface. An entity class must not be final or contain final
 *  methods. Additionally it must not implement clone nor wakeup or do so safely.
 *
 *  An entity contains persistable properties. A persistable property is an
 *  instance variable of the entity that is saved into and retrieved from the
 *  database by Doctrine’s data mapping capabilities." [3] For example your user
 *  model, Application_Model_User, is Entity.
 *
 * [1] http://survivethedeepend.com/
 * [2] http://framework.zend.com/wiki/pages/viewpage.action?pageId=9437243
 * [3] http://www.doctrine-project.org/docs/orm/2.0/en/tutorials/getting-started-xml-edition.html
 *
 * @category   Kblom
 * @package    Kblom_Model
 * @subpackage Kblom_Model_Mapper
 * @copyright  Copyright (c) 2010-2011 Kim Blomqvist
 * @license    http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
abstract class Kblom_Model_Mapper_Entity
{
	/**
	 * Data gateway
	 *
	 * @var Zend_Db_Table
	 */
	protected $_dbTable = null;

	/**
	 * Identity container
	 *
	 * Reflects to Identity Map pattern.
	 *
	 * @var array
	 */
	protected $_identityMap = array();

	/**
	 * Constructor
	 *
	 * @param mixed $dbTable Data gateway extended from Zend_Db_Table_Abstract
	 */
	public function __construct($dbTable = null)
	{
		if ($dbTable !== null) {
			$this->setDbTable($dbTable);
		}
	}

	/**
	 * Set DbTable data gateway
	 * 
	 * @param Zend_Db_Table_Abstract $dBTable
	 */
	public function setDbTable($dbTable)
	{
		if (is_string($dbTable)) {
			$table = new $dbTable();
		}
		if (!$dbTable instanceof Zend_Db_Table_Abstract) {
			throw new Exception('Invalid DbTable data gateway provided, has to be instance of Zend_Db_Table_Abstract');
		}
		$this->_dbTable = $dbTable;

		return $this;
	}

	/**
	 * Get data gateway (DbTable) of this mapper
	 *
	 * This getter provides some magic if not overridden. Consider a
	 * conventional mapper class 'Application_Model_Mapper_Foo' ...
	 * If DbTable is not set, this method tries to invoke a new DbTable
	 * by resolving it from the mapper class name so that '_Mapper_' part is
	 * replaced by '_DbTable_'.
	 *
	 * @return Application_Model_DbTable_ClassName where ClassName is the name
	 *                                             of this class
	 */
	public function getDbTable()
	{
		if ($this->_dbTable === null) {
			$this->setDbTable(str_replace("_Mapper_", "_DbTable_",
				get_class($this)));

		}
		return $this->_dbTable;
	}

	/**
	 * Set identity (model)
	 *
	 * @param int|string $id Unique id of identity, a number or hash string
	 * @param Kblom_Model_Entity $identity Identity model
	 */
	protected function _setIdentity($id, Kblom_Model_Entity $identity)
	{
		if (!is_numeric($id)) {
			$id = (string) $id;
		}
		$this->_identityMap[$id] = $identity;

		return $this;
	}

	/**
	 * Get identity (model)
	 *
	 * @param int|string $id Unique id of identity, a number or hash string
	 * @return mixed Instance of entity model or null if not exists
	 */
	protected function _getIdentity($id)
	{
		if ($this->_hasIdentity($id)) {
			return $this->_identityMap[$id];
		}
		return null;
	}

	protected function _hasIdentity($id)
	{
		return array_key_exists($id, $this->_identityMap);
	}
}

