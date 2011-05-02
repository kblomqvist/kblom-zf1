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

