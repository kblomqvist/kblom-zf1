<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Kblom/Validate/Datetime.php';

/**
 * Test case for datetime validator
 *
 * @category  Kblom
 * @package   Kblom_Validate
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Validate_DatetimeTest extends PHPUnit_Framework_TestCase
{
	protected $_validator;

	public function setUp()
	{
		$this->_validator = new Kblom_Validate_Datetime();
	}

	public function tearDown()
	{
		unset($this->_validator);
	}

	public function testType()
	{
		$this->assertInstanceOf('Kblom_Validate_Datetime', $this->_validator);
	}

	public function testCreate()
	{
		$validator = new Kblom_Validate_Datetime();

		$this->assertNull($validator->getMatchedParts());
		$this->assertNull($validator->getMatchedFormat());
	}

	public function testCreateWithOptions()
	{
		$formats = array(
			'd.M.Y',
			'd-M-Y'
		);
		$options = array(
			'formats' => $formats,
			'disableLoadDefaultFormats' => false
		);
		$validator = new Kblom_Validate_Datetime($options);

		$this->assertEquals($formats, $validator->getFormats());
	}

	public function testDisableLoadDefaultFormats()
	{
		$this->_validator->setDisableLoadDefaultFormats(true);
		$date = '2011-04-01';

		$result = $this->_validator->isValid($date);
		$this->assertFalse($result);

		$result = $this->_validator->isValid($date, 'Y-m-d');
		$this->assertTrue($result);
	}

	public function testValidDateISO8601()
	{
		$date = '2011-04-01 10:00:00';
		$result = $this->_validator->isValid($date);

		$expectFormat = 'Y-m-d H:i:s';
		$expectDateParts = array(
			'year'   => '2011',
			'month'  => '04',
			'day'    => '01',
			'hour'   => '10',
			'minute' => '00',
			'second' => '00'
		);

		$this->assertTrue($result);
		$this->assertEquals($expectFormat,
			$this->_validator->getMatchedFormat());
		$this->assertEquals($expectDateParts,
			$this->_validator->getMatchedParts());
	}

	public function testValidDateFormatISO8601ButInvalidDate()
	{
		$date = '2011-02-29'; // 28. Feb 2011 was the latest day in this month
		$result = $this->_validator->isValid($date);
		$errors = $this->_validator->getErrors();

		$this->assertFalse($result);
		$this->assertEquals('Y-m-d', $this->_validator->getMatchedFormat());
		$this->assertEquals(1, count($errors));
		$this->assertEquals('invalidDate', $errors[0]);
	}

	// Little endian date formats
	/** @group lendian */
	public function testValidDateDashedLittleEndian()
	{
		$date = '29/4/2011';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('j/n/Y', $this->_validator->getMatchedFormat());
	}

	/** @group lendian */
	public function testValidDateDottedLittleEndian()
	{
		$date = '29.4.2011';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('j.n.Y', $this->_validator->getMatchedFormat());
	}

	/** @group lendian */
	public function testValidDateDashedLittleEndianWithLeadingZeros()
	{
		$date = '29/04/2011';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('d/m/Y', $this->_validator->getMatchedFormat());
	}

	/** @group lendian */
	public function testValidDateDottedLittleEndianWithLeadingZeros()
	{
		$date = '29.04.2011';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('d.m.Y', $this->_validator->getMatchedFormat());
	}

	// Big endian date formats
	/** @group bendian */
	public function testValidDateDashedBigEndian()
	{
		$date = '2011/4/29';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('Y/n/j', $this->_validator->getMatchedFormat());
	}

	/** @group bendian */
	public function testValidDottedDashedBigEndians()
	{
		$date = '2011.4.29';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('Y.n.j', $this->_validator->getMatchedFormat());
	}

	/** @group bendian */
	public function testValidDateDashedBigEndianWithLeadingZeros()
	{
		$date = '2011/04/29';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('Y/m/d', $this->_validator->getMatchedFormat());
	}

	/** @group bendian */
	public function testValidDateDottedBigEndianWithLeadingZeros()
	{
		$date = '2011.04.29';
		$result = $this->_validator->isValid($date);

		$this->assertTrue($result);
		$this->assertEquals('Y.m.d', $this->_validator->getMatchedFormat());
	}

	public function testValidateByExplicitlyGivenFormat()
	{
		$date = '04-2011-29';
		$result = $this->_validator->isValid($date, 'm-Y-d');

		$this->assertTrue($result);
		$this->assertEquals('m-Y-d', $this->_validator->getMatchedFormat());
	}

	// Invalid date formats
	public function testInvalidDateFormat()
	{
		$date = '2011:04:29';
		$result = $this->_validator->isValid($date);
		$errors = $this->_validator->getErrors();

		$this->assertFalse($result);
		$this->assertNull($this->_validator->getMatchedFormat());
		$this->assertEquals(1, count($errors));
		$this->assertEquals('invalidFormat', $errors[0]);
	}

	public function test1111IsNotValidDate()
	{
		$date = '1111';
		$result = $this->_validator->isValid($date);
		$errors = $this->_validator->getErrors();

		$this->assertFalse($result);
		$this->assertEquals(1, count($errors));
		$this->assertEquals('invalidFormat', $errors[0]);
		$this->assertNull($this->_validator->getMatchedFormat());
	}

	public function testThreeDigitMonthIsInvalid()
	{
		$date = '29.004.2011';
		$result = $this->_validator->isValid($date);

		$this->assertFalse($result);
		$this->assertNull($this->_validator->getMatchedFormat());
	}

	public function testThreeDigitDayIsInvalid()
	{
		$date = '029.4.2011';
		$result = $this->_validator->isValid($date);

		$this->assertFalse($result);
		$this->assertNull($this->_validator->getMatchedFormat());
	}

	public function testDateCannotHaveMixedLittleAndBigEndians()
	{
		$date = '09.4.2011';
		$result = $this->_validator->isValid($date);

		$this->assertFalse($result);
		$this->assertNull($this->_validator->getMatchedFormat());
	}

	// Times
	/** @group time */
	public function testValidTimeHourMinute()
	{
		$time = '12:22';
		$result = $this->_validator->isValid($time, 'H:i');

		$expectFormat = 'H:i';
		$expectParts  = array(
			'hour' => '12',
			'minute' => '22'
		);

		$this->assertTrue($result);
		$this->assertEquals($expectFormat, $this->_validator->getMatchedFormat());
		$this->assertEquals($expectParts, $this->_validator->getMatchedParts());
	}

	/** @group time */
	public function testValidTimeHourMinuteSecond()
	{
		$time = '12:22:22';
		$result = $this->_validator->isValid($time, 'H:i:s');

		$expectFormat = 'H:i:s';
		$expectParts  = array(
			'hour' => '12',
			'minute' => '22',
			'second' => '22'
		);

		$this->assertTrue($result);
		$this->assertEquals($expectFormat, $this->_validator->getMatchedFormat());
		$this->assertEquals($expectParts, $this->_validator->getMatchedParts());
	}

	/** @group time */
	public function testInvalidHour()
	{
		$time = '25:25';
		$result = $this->_validator->isValid($time, 'H:i');

		$this->assertFalse($result);
		$this->assertNull($this->_validator->getMatchedFormat());
	}

	/** @group time */
	public function testInvalidMinute()
	{
		$time = '10:60';
		$result = $this->_validator->isValid($time, 'H:i');

		$this->assertFalse($result);
		$this->assertNull($this->_validator->getMatchedFormat());
	}
}
