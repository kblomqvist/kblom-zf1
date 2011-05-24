# kblom-zf1

Kblom is a library of PHP classes for Zend Framework 1

## Kblom_Date
Extends [PHP's DateTime class](http://www.php.net/manual/en/class.datetime.php) to support date and time setting from format. This functionality is rather similar than Zend_Date's setTime() and setDate() methods.

	$date = new Kblom_Date('2011-01-01');
	echo $date->format('Y-m-d H:i:s');                               // '2011-01-01 00:00:00'

	echo $date->setFromFormat('H:i', '10:45');                       // '2011-01-01 10:45:00'
	echo $date->setFromFormat('n', 4);                               // '2011-04-01 10:45:00'
	echo $date->setFromFormat('Y-m-d H:i:s', '2011-02-15 10:00:01'); // '2011-02-15 10:00:01'

## Kblom_FunctionParamParser

Parses PHP source files of given function names and returns function
parameters. Also supports array key matching to fetch array key values.
CLI tool Kblom_Tool_Locale uses this class in translation resource file
generation by parsing translation message ids.

## Kblom_Model

### Kblom_Model_Entity

`Kblom_Model_Entity` and `Kblom_Model_Mapper_Entity` are adapted with a certain
modifications from a great book about Zend Framework,
[Survive The Deep End](http://survivethedeepend.com/), written by __Pádraic Brady__.

Example of BlogEntry entity model, where getAuthor() implements lazy loading for Author
entity model ...

	class Model_BlogEntry extends Kblom_Model_Entity
	{
		protected $data = array(
			'id'      => null,
			'title'   => '',
			'content' => '',
			'author'  => null,
		);

		public function setAuthor(Model_Author $author)
		{
			$this->_data['author'] = $author;
			return $this;
		}

		public function getAuthor()
		{
			if (!isset($this->_data['author'])) {
				if ($id = $this->getReferenceId('author')) {
					$mapper = Kblom_Model_Mapper::factory('Author', '');
					$this->_data['author'] = $mapper->find($id);
				}
			}
			return $this->_data['author'];
		}
	}

#### Kblom_Model_Mapper_Entity

Example of BlogEntry mapper class ...

	class Model_Mapper_BlogEntry extends Kblom_Model_Mapper_Entity
	{
		...

		public function find($id)
		{
			if ($this->_hasIdentity($id)) {
				return $this->_getIdentity($id);
			}
			$rowset = $this->getDbTable()->find($id);

			if (count($rowset) != 1) {
				return;
			}
			$row = $rowset->current();

			$entry = new Model_BlogEntry(array(
				'id'      => $row->id,
				'title'   => $row->title,
				'content' => $row->content
			));
			$entry->setReferenceId('author', $row->author_id);

			$this->_setIdentity($id, $entry);
			return $entry;
		}
	}

#### Kblom_Model_Mapper

Factory for entity mapper objects. Loaded mappers are stored into Zend_Registry.

	$mapper = Kblom_Model_Mapper::factory('Author');         // returns Application_Model_Mapper_Author
	$mapper = Kblom_Model_Mapper::factory('Author', 'Foo_'); // returns Foo_Model_Mapper_Author
	$mapper = Kblom_Model_Mapper::factory('Author', '');     // returns Model_Mapper_Author

	Kblom_Model_Mapper::$namespace = 'Bar_';
	$mapper = Kblom_Model_Mapper::factory('Author');         // returns Bar_Model_Mapper_Author

## Kblom_Test

### Kblom_Test_MapperTestCase

This class does all boilerplate for data mapper testing. It provides a mocked
versions of db adapter, db table and db rowset out of box (_adapter, _dbTable and
_rowset, respectively).

Mocked rowset can be populated with test dbData by _populateRowset()_ method.
This method also makes the mocked rowset iteratable once.

/* Note [1] */ The first given row (array) is a prototype. Other arrays are
merged to that so that you can only change the value of some fields and leave
the other fields as it was in the first place.

Example test case for Entity data mapper:

	class Kblom_Model_Mapper_EntityTest extends Kblom_Test_MapperTestCase
	{
		protected $_mapper;

		public function setUp()
		{
			parent::setUp();
			$this->_mapper = new Model_Mapper_Entity($this->_dbTable);
		}

		public function testFind()
		{
			$this->populateRowset($this->_rowset, array(
				array(
					'id' => 1,
					'author_id' => 5
					'title' => 'bar',
					'content' => 'baz',
				)
			));

			$this->_dbTable->expects($this->once())
				->method('find')
				->with($this->equalTo(1))
				->will($this->returnValue($this->_rowset));

			$result = $this->_mapper->find(1);

			// ... assertions against $result
		}

 		public function testFetchAll() {
			$this->populateRowset($this->_rowset, array(
				array( /* Note [1] */
					'id' => 1,
					'author_id' => 5,
					'title' => 'foo',
					'content' => 'barbapapa',
					'created' => '2011-05-14 13:23:10',
					'updated' => '0000-00-00 00:00:00'
				),
				array(
					'id' => 2,
					'title' => 'bar'
				),
				array(
					'id' => 3
					'author_id' => 2
					'title' => 'baz'
				)
			));

 			$this->_dbTable->expects($this->once())
				->method('fetchAll')
				->with($this->equalTo(1))
				->will($this->returnValue($this->_rowset));
 			
			$result = $this->_mapper->fetcAll();

			// ... assertions against $result
		}
	}

## Kblom_Tool

### Kblom_Tool_Locale

CLI tool for translation resource file generation.

Example use case:

	zf create locale en

Installation:

1. Edit your `$HOME/.zf.ini` dotfile
2. Append `php.include_path` by `/path/to/kblom-zf1/library`
3. Add new basicloader for `Kblom_Tool_Locale`

The file __.zf.ini__ should look like ...

	include_path = "/path/to/zf/library:.:/usr/share/php:/usr/share/pear:/path/to/kblom-zf1/library"
	basicloader.classes.1 = Kblom_Tool_Locale

## Kblom_Validate

### Kblom_Validate_Datetime

Supports __datetime matching__ against formats constructed by characters
declared in [DateTime::createFromFormat](http://www.php.net/manual/en/datetime.createfromformat.php).
Also provides __datetime parser__ as date parts can be fetched after validation.

	$validator = new Kblom_Validate_Datetime();

	$validator->isValid('2011-04-29 10:00:00'); // TRUE,  valid ISO 8601 format
	$validator->isValid('04-2011-29', 'm-Y-d'); // TRUE,  valid date in given format
	$validator->isValid('2011-04-29 25:00:00'); // FALSE, invalid format
	$validator->isValid('2011-02-29 10:00:00'); // FALSE, valid format, but invalid date
	
	$validator->isValid('10:60', 'H:i'); // FALSE
	$validator->isValid('10:45', 'H:i'); // TRUE
	print_r($v->getMatchedParts());      // array('hour' => '10', 'minute' => '45')

## Running tests

	cd tests
	phpunit --include-path /path/to/zf/library

