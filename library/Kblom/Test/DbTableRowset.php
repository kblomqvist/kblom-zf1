<?php
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Kblom_Test_DbTableRowset
 *
 * This class provides a mocker for Zend_Db_Table_Rowset.
 *
 * Example:
 * $dbTableRowset = new Kblom_Test_DbTableRowset();
 * 
 * $mock = $dbTableRowset->mock(array(
 * 		array(
 * 			'user_id' => 1,
 * 			'username' => 'foo',
 * 			'created' => '2011-05-14 13:23:10',
 * 			'updated' => '0000-00-00 00:00:00',
 * 		),
 * 		array(
 * 			'user_id' => 2,
 * 			'username' => 'bar'
 * 		),
 * 		array(
 * 			'user_id' => 3
 * 			'username' => 'baz'
 * 		)
 * );
 *
 * The first array is a prototype. Other arrays are merged to that so that
 * you can easily change only some fields and leave the other fields to get
 * values from the first array. In the example above, all rows have the same
 * created and updated values but different ids and usernames.
 *
 * @category   Kblom
 * @package    Kblom_Test
 * @copyright  Copyright (c) 2010-2011 Kim Blomqvist
 * @license    http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */

class Kblom_Test_DbTableRowset extends PHPUnit_Framework_TestCase
{
	/**
	 * Create a rowset mock from data base data
	 *
	 * @param array  $dbData           Database data in array format (rows)
	 * @param boolen $firstIsPrototype OPTIONAL if true the first array of dbData
	 *                                 is used as a prototype for the next rows
	 */
	public function mock(array $dbData, $firstIsPrototype = true)
	{
		$count   = count($dbData);
		$rows    = array();
		$valids  = array_fill(0, $count + 1, true);

		$valids[$count] = false;

		$mock = $this->getMock('Zend_Db_Table_Rowset_Abstract',
			array('current', 'count', 'valid'), array(), '', false);

		foreach ($dbData as $data) {
			if (!is_array($data)) {
				throw new Exception('foo');
			}
			if ($firstIsPrototype === true) {
				$data = array_merge($dbData[0], $data);
			}
			$rows[] = $this->_getStdClass($data);
		}

		$mock->expects($this->any())->method('count')
			->will($this->returnValue($count));

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

		return $mock;
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
