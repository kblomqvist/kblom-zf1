<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Kblom/Date.php';

/**
 * Test case for date object
 *
 * @category  Kblom
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_DateTest extends PHPUnit_Framework_TestCase
{
	protected $_fixture;

	public function setUp()
	{
		$this->_fixture = new Kblom_Date('2011-04-23');
	}

	public function tearDown()
	{
		unset($this->_fixture);
	}

	public function testType()
	{
		$this->assertInstanceOf('Kblom_Date', $this->_fixture);
	}

	public function testCreate()
	{
		$this->assertEquals('2011-04-23', $this->_fixture->format('Y-m-d'));
		$this->assertEquals('00:00:00', $this->_fixture->format('H:i:s'));
	}

	public function testSetDateFromFormat()
	{
		$format = 'Y-m-d';
		$date = '2011-04-25';
		$this->_fixture->setFromFormat($format, $date);

		$this->assertEquals($date, $this->_fixture->format($format));
	}

	public function testSetTimeFromFormat()
	{
		$format = 'H:i:s';
		$date = '10:00:01';
		$this->_fixture->setFromFormat($format, $date);

		$this->assertEquals($date, $this->_fixture->format($format));
	}

	public function testSetDateAndTimeFromFormat()
	{
		$format = 'Y-m-d H:i:s';
		$date = '2011-02-01 10:01:02';
		$this->_fixture->setFromFormat($format, $date);

		$this->assertEquals($date, $this->_fixture->format($format));
	}
}
