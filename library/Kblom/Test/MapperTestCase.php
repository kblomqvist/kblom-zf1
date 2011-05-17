<?php
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Kblom_Test_MapperTestCase
 *
 * This class does all boilerplate for data mapper testing. It provides a mocked
 * versions of db adapter, db table and db rowset out of box (_adapter, _dbTable and
 * _rowset, respectively).
 * 
 * Mocked rowset can be populated with test dbData by _populateRowset()_ method.
 * This method also makes the mocked rowset iteratable once.
 *
 *
 * Example:
 *
 * public function setUp() {
 * 		parent::setUp();
 * 		$this->_mapper = new Model_Mapper_BlogEntry($this->_dbTable);
 * }
 *
 * public function testFetchAll() {
 * 		$this->populateRowset($this->_rowset, array(
 * 			array( // Note [1]
 * 				'user_id' => 1,
 *	 			'username' => 'foo',
 * 				'created' => '2011-05-14 13:23:10',
 * 				'updated' => '0000-00-00 00:00:00',
 *	 		),
 * 			array(
 * 				'user_id' => 2,
 * 				'username' => 'bar'
 * 			),
 * 			array(
 * 				'user_id' => 3
 * 				'username' => 'baz'
 * 			)
 * 		));
 *
 *		$this->_dbTable->expects($this->once())
 *			->method('fetchAll')
 *			->with($this->equalTo(1))
 *			->will($this->returnValue($this->_rowset));
 *
 *		$result = $this->_mapper->fetcAll();
 *
 *		// ... assertions
 * }
 *
 * Note [1] The first given row (array) is a prototype. Other arrays are
 * merged to that so that you can only change the value of some fields and leave
 * the other fields as it was in the first place.
 *
 * @category   Kblom
 * @package    Kblom_Test
 * @copyright  Copyright (c) 2010-2011 Kim Blomqvist
 * @license    http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */

class Kblom_Test_MapperTestCase extends PHPUnit_Framework_TestCase
{
	protected $_adapter;
	protected $_dbTable;
	protected $_select;
	protected $_rowset;

	public function setUp()
	{
		$this->_adapter = $this->getMock('Zend_Db_Adapter_Mysqli',
			array(), array(), '', false);
		$this->_dbTable = $this->getMock('Zend_Db_Table_Abstract',
			array('find', 'insert', 'update', 'fetchAll', 'select'), array(), '', false);
		$this->_select = $this->getMock('Zend_Db_Table_Select',
			array(), array(), '', false);
		$this->_rowset = $this->getMock('Zend_Db_Table_Rowset_Abstract',
			array('current', 'count', 'valid', 'toArray'), array(), '', false);

		$this->_dbTable->expects($this->any())
			           ->method('getAdapter')
					   ->will($this->returnValue($this->_adapter));
		$this->_dbTable->expects($this->any())
			           ->method('select')
					   ->will($this->returnValue($this->_select));

	}

	public function tearDown()
	{
		unset($this->_adapter, $this->_dbTable, $this->_rowset);
	}

	/**
	 * Populate mocked rowset
	 *
	 * @param Mock_Zend_Db_Table_Rowset_Abstract_###
	 * @param array  $dbData           Database data in array format (rows)
	 * @param boolen $firstIsPrototype OPTIONAL if true the first array of dbData
	 *                                 is used as a prototype for the next rows
	 */
	public function populateRowset($mock, array $dbData, $firstIsPrototype = true)
	{
		$count   = count($dbData);
		$rows    = array();
		$valids  = array_fill(0, $count + 1, true);

		$valids[$count] = false;

		foreach ($dbData as $i => $data) {
			if (!is_array($data)) {
				throw new Exception('Given row is not in array form.');
			}
			if ($firstIsPrototype === true) {
				$dbData[$i] = array_merge($dbData[0], $data);
			}
			$rows[] = $this->_getStdClass($dbData[$i]);
		}

		$mock->expects($this->any())->method('count')
			->will($this->returnValue($count));

		$mock->expects($this->any())->method('toArray')
			->will($this->returnValue($dbData));

		$mock->expects($this->any())->method('valid')
			->will(call_user_func_array(
				array($this, 'onConsecutiveCalls'),
				$valids
			));

		$mock->expects($this->any())->method('current')
			->will(call_user_func_array(
				array($this, 'onConsecutiveCalls'),
				$rows
			));

		return $this;
	}

	protected function _getStdClass($data)
	{
		$class = new stdClass;
		foreach ($data as $key => $value) {
			$class->{$key} = $value;
		}
		return $class;
	}
}
