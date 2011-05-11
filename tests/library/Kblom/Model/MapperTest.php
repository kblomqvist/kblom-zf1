<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Kblom/Model/Mapper.php';

/** test fixture */
require_once TESTS_PATH . '/library/Kblom/Model/Mapper/_files/BlogEntry.php';

/**
 * Test case for entity model mapper factory
 *
 * @category  Kblom
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Model_MapperTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
	}

	public function testProduceBlogEntryMapper()
	{
		$result = Kblom_Model_Mapper::factory('BlogEntry', '');
		$this->assertInstanceOf('Kblom_Model_Mapper_Entity', $result);

		return $result;
	}

	/**
	 * @depends testProduceBlogEntryMapper
	 */
	public function testIdentity($result)
	{
		$identity = Kblom_Model_Mapper::factory('BlogEntry', '');
		$this->assertEquals($identity, $result);
	}

	/**
	 * @depends testProduceBlogEntryMapper
	 */
	public function testNamespaceCanBeChanged($result)
	{
		Kblom_Model_Mapper::$namespace = 'Foo_';
		$this->assertEquals('Foo_', Kblom_Model_Mapper::$namespace);

		Kblom_Model_Mapper::$namespace = '';
		$mapper = Kblom_Model_Mapper::factory('BlogEntry');
		$this->assertEquals($mapper, $result);
	}
}
