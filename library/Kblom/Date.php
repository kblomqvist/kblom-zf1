<?php
/**
 * Extends PHP's DateTime class to support date and time setting from format.
 * This functionality is rather similar than Zend_Date's setTime() and
 * setDate() methods.
 *
 * @category  Kblom
 * @package   Kblom_Date
 * @uses      Kblom_Validate_Datetime
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Date extends DateTime
{
	/** @var Kblom_Validate_Datetime */
	protected $_datetimeValidator;

	/**
	 * Set date, time or datetime from format
	 *
	 * Example:
	 * $date->setFromFormat('H:i', '10:45'); // set time
	 * $date->setFromFormat('m', 4); // set only month
	 * $date->setFromFormat('Y-m-d H:i:s', '2011-04-24 10:00:01');
	 *
	 * @param string $format
	 * @param int|string $time
	 */
	public function setFromFormat($format, $time)
	{
		$validator = $this->_getDatetimeValidator();
		if (!$validator->isValid($time, $format)) {
			throw new Exception('Invalid time in given format');
		}

		$parts = array(
			'day' => $this->format('j'),
			'month' => $this->format('n'),
			'year' => $this->format('Y'),
			'hour' => $this->format('G'),
			'minute' => $this->format('i'),
			'second' => $this->format('s')
		);
		$parts = array_merge($parts, $validator->getMatchedParts());

		return $this->setDate($parts['year'], $parts['month'], $parts['day'])
			        ->setTime($parts['hour'], $parts['minute'], $parts['second']);
	}

	protected function _getDatetimeValidator()
	{
		if (!isset($this->_datetimeValidator)) {
			$this->_datetimeValidator = new Kblom_Validate_Datetime();
		}
		return $this->_datetimeValidator;
	}

	public function toString($format)
	{
		return $this->format($format);
	}

	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}
}
