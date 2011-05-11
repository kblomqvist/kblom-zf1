<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Kblom/Model/Entity.php';

/** test fixture */
require_once TESTS_PATH . '/library/Kblom/Model/_files/BlogEntry.php';

/**
 * Test case for entity model
 *
 * @category  Kblom
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Model_EntityTest extends PHPUnit_Framework_TestCase
{
	protected $_fixture;

	public function setUp()
	{
		$this->_fixture = new Model_BlogEntry();
	}

	public function tearDown()
	{
		unset($this->_fixture);
	}

	public function testType()
	{
		$this->assertInstanceOf('Kblom_Model_Entity', $this->_fixture);
	}

	public function testCreate()
	{
		$model = new Model_BlogEntry();
		$expect = array(
			'id' => null,
			'title' => '',
			'content' => '',
			'author' => null
		);

		$this->assertEquals($expect, $model->toArray());
	}

	public function testSetProperties()
	{
		$data = array(
			'id' => 1,
			'title' => 'foo',
			'content' => 'bar',
		);
		$this->_fixture->setProperties($data);

		$this->assertEquals($data['id'], $this->_fixture->id);
		$this->assertEquals($data['title'], $this->_fixture->title);
		$this->assertEquals($data['content'], $this->_fixture->content);
	}

	public function testMagicGetterCanBeOverridenBySeparateGetMethod()
	{
		$this->assertEquals('foo', $this->_fixture->title);
	}

	public function testMagicSetterCanBeOverridenBySeparateSetMethod()
	{
		$author = new Kblom_Model_Entity();
		$this->_fixture->author = $author;

		$this->assertEquals($author, $this->_fixture->author);
	}

	public function testSettingReferenceId()
	{
		$this->_fixture->setReferenceId('author', 1);

		$this->assertEquals(1, $this->_fixture->getReferenceId('author'));
	}

	public function testAllowsStoreAuthorIdAsAReference()
	{
		$this->_fixture->setReferenceId('author', 5);
		$this->assertEquals(5, $this->_fixture->getReferenceId('author'));
	}

	public function testCannotSetReferenceWhichIsNotDeclearedProperty()
	{
		try {
			$this->_fixture->setReferenceId('foo', 1);
		} catch (Exception $expected) {
			return;
		}

		$this->fail('An expexted exception has not been raised.');
	}
}
