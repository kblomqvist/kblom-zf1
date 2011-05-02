Kblom is a library of PHP classes for Zend Framework 1

## Kblom_FunctionParamParser

Parses PHP source files of given function names and returns function
parameters. Also supports array key matching to fetch array key values.
CLI tool Kblom_Tool_Locale uses this class in translation resource file
generation by parsing translation message ids.

## Kblom_Tool

### Kblom_Tool_Locale

CLI tool for translation resource file generation.

Example use case:

	zf create locale en

Installation:

- edit your $HOME/.zf.ini dotfile
- append php.include_path by path/to/kblom-zf1/library
- add new basicloader for Kblom_Tool_Locale, e.g.,
  basicloader.classes.1 = Kblom_Tool_Locale

## Kblom_Validate

#### Kblom_Validate_Datetime

- Supports datetime matching against formats constructed by characters
  declared in http://www.php.net/manual/en/datetime.createfromformat.php.
- Also provides datetime parser as date parts can be fetched after
  validation.

	$validator = new Kblom_Validate_Datetime();
	$validator->isValid('2011-04-29 10:00:00'); // TRUE,  valid ISO 8601 format
	$validator->isValid('04-2011-29', 'm-Y-d'); // TRUE,  valid date in given format
	$validator->isValid('2011-04-29 25:00:00'); // FALSE, invalid format
	$validator->isValid('2011-02-29 10:00:00'); // FALSE, valid format, but invalid date
	
	$validator->isValid('10:45', 'H:i');
	print_r($v->getMatchedParts()); // array('hour' => '10', 'minute' => '45')
 

## Running tests

	cd tests
	phpunit --include-path /path/to/zf/library

