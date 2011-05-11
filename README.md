Kblom is a library of PHP classes for Zend Framework 1

## Kblom_FunctionParamParser

Parses PHP source files of given function names and returns function
parameters. Also supports array key matching to fetch array key values.
CLI tool Kblom_Tool_Locale uses this class in translation resource file
generation by parsing translation message ids.

## Kblom_Model

### Kblom_Model_Entity

Adapted with a certain modifications from a GREAT book about Zend Framework,
[Survive The Deep End](http://survivethedeepend.com/), written by Pádraic Brady.

Example blog entry, where the getAuthor() implements lazy loading for Model_Author
reference model.

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
				if (($id = $this->getReferenceId('author')) {
					$mapper = Kblom_Model_Mapper::factory('Author', '');
					$this->_data['author'] = $mapper->find($id);
				}
			}
			return $this->_data['author'];
		}
	}

#### Kblom_Model_Mapper_Entity

	class Model_Mapper_BlogEntry extends Kblom_Model_Mapper_Entity
	{
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

	// Returns Application_Model_Mapper_Author
	$mapper = Kblom_Model_Mapper::factory('Author');

	// Returns Foo_Model_Mapper_Author
	$mapper = Kblom_Model_Mapper::factory('Author', 'Foo_');

	// Returns Model_Mapper_Author
	$mapper = Kblom_Model_Mapper::factory('Author', '');


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

