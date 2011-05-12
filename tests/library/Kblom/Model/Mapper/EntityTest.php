<?php
require_once 'PHPUnit/Framework/TestCase.php';

require_once 'Kblom/Model/Entity.php';
require_once 'Kblom/Model/Mapper/Entity.php';

/** test fixtures */
require_once TESTS_PATH . '/library/Kblom/Model/_files/BlogEntry.php';
require_once TESTS_PATH . '/library/Kblom/Model/Mapper/_files/BlogEntry.php';

/**
 * Test case for entity mapper
 *
 * @category  Kblom
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Model_Mapper_EntityTest extends PHPUnit_Framework_TestCase
{
	protected $_mapper;

	protected $_adapter;
	protected $_dbTable;
	protected $_rowset;

	public function setUp()
	{
		$this->_adapter = $this->getMock('Zend_Db_Adapter_Mysqli',
			array(), array(), '', false);
		$this->_dbTable = $this->getMock('Zend_Db_Table_Abstract',
			array('find'), array(), '', false);
		$this->_rowset  = $this->getMock('Zend_Db_Table_Rowset_Abstract',
			array('current', 'count'), array(), '', false);

		$this->_dbTable->expects($this->any())
			           ->method('getAdapter')
					   ->will($this->returnValue($this->_adapter));

		$this->_mapper = new Model_Mapper_BlogEntry($this->_dbTable);
	}

	public function tearDown()
	{
		unset($this->_mapper, $this->_adapter, $this->_dbTable, $this->_rowset);
	}

	public function testType()
	{
		$this->assertInstanceOf('Kblom_Model_Mapper_Entity', $this->_mapper);
	}

	public function testCreate()
	{
		$mapper = new Model_Mapper_BlogEntry($this->_dbTable);
		$this->assertTrue($mapper->getDbTable() instanceof Zend_Db_Table_Abstract);
	}

	public function testFind()
	{
		$dbData = new stdClass;
		$dbData->id = 1;
		$dbData->title = 'bar';
		$dbData->content = 'baz';
		$dbData->author_id = 5;

		$this->_rowset->expects($this->once())
			->method('current')
			->will($this->returnValue($dbData));
		$this->_rowset->expects($this->once())
			->method('count')
			->will($this->returnValue(1));

		$this->_dbTable->expects($this->once())
			->method('find')
			->with($this->equalTo(1))
			->will($this->returnValue($this->_rowset));

		$result = $this->_mapper->find(1);

		$this->assertInstanceOf('Kblom_Model_Entity', $result);
		$this->assertEquals(array('id'=>1,'title'=>'bar','content'=>'baz','author'=>null), $result->toArray());
		$this->assertEquals(5, $result->getReferenceId('author'));
		$this->assertEquals('foo', $result->title); // title is forced to return 'foo' always

		$identity = $this->_mapper->find(1);
		$this->assertEquals($identity, $result);

		return $result;
	}

	/**
	 * @depends testFind
	 */
	public function testFindIdentity($result)
	{
		$this->markTestIncomplete('result is always null??');
		$identity = $this->_mapper->find(1);
		$this->assertEquals($identity, $result);
	}
}
