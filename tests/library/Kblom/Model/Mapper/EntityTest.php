<?php
require_once 'Kblom/Test/MapperTestCase.php';

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
class Kblom_Model_Mapper_EntityTest extends Kblom_Test_MapperTestCase
{
	protected $_mapper;

	public function setUp()
	{
		parent::setUp();
		$this->_mapper = new Model_Mapper_BlogEntry($this->_dbTable);
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
		$this->populateRowset($this->_rowset, array(
			array(
				'id' => 1,
				'title' => 'bar',
				'content' => 'baz',
				'author_id' => 5
			)
		));

		$this->_dbTable->expects($this->once())
			->method('find')
			->with($this->equalTo(1))
			->will($this->returnValue($this->_rowset));

		$result = $this->_mapper->find(1);

		$this->assertInstanceOf('Kblom_Model_Entity', $result);
		$this->assertEquals(array('id'=>1,'title'=>'bar','content'=>'baz','author'=>null), $result->toArray());
		$this->assertEquals(5, $result->getReferenceId('author'));
		$this->assertEquals('foo', $result->title); // remember that title is forced to return 'foo' always

		$identity = $this->_mapper->find(1);
		$this->assertEquals($identity, $result);

		return $result;
	}
}
