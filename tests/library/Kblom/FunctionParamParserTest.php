<?php
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Kblom/FunctionParamParser.php';

/**
 * Test case for function param parser class
 *
 * @category  Kblom
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_FunctionParamParserTest extends PHPUnit_Framework_TestCase
{
	public $fpparser;
	public $basepath;

	public function setUp()
	{
		$this->basepath = './library/Kblom/_fixtures';

		$this->fpparser = new Kblom_FunctionParamParser(array(
			'basepath' => './library/Kblom/_fixtures',
		));
	}

	public function tearDown()
	{
		unset($this->fpparser);
	}

	public function testType()
	{
		$this->assertInstanceOf('Kblom_FunctionParamParser', $this->fpparser);
	}

	public function testHasDefaultPattert()
	{
		$this->assertEquals('(.+)', $this->fpparser->getDefaultPattern());
	}

	public function testEmptyDefaultPatternIsRevertedToNull()
	{
		$this->fpparser->setDefaultPattern('');
		$this->assertNull($this->fpparser->getDefaultPattern());

		$this->fpparser->setDefaultPattern(null);
		$this->assertNull($this->fpparser->getDefaultPattern());

		$this->fpparser->setDefaultPattern(false);
		$this->assertNull($this->fpparser->getDefaultPattern());

		$this->fpparser->setDefaultPattern(array());
		$this->assertNull($this->fpparser->getDefaultPattern());
	}

	public function testAddFunctionKeyword()
	{
		$this->fpparser->addFunctionKeyword('translate');
		$result = $this->fpparser->getPatterns();

		$this->assertEquals(array('translate' => array('(.+)')), $result);
	}

	public function testAddManyFunctionKeywords()
	{
		$this->fpparser->addFunctionKeywords(array(
			'translate',
			'setLabel',
			'setDescription'
		));
		$defaultPattern = $this->fpparser->getDefaultPattern();

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'translate' => array($defaultPattern),
			'setLabel' => array($defaultPattern),
			'setDescription' => array($defaultPattern)
		);

		$this->assertEquals($expect, $result);
	}

	public function testAddFunctionKeywordWithOnePattern()
	{
		$this->fpparser->addFunctionKeyword('translate', 'foo');

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'translate' => array('foo', '(.+)')
		);

		$this->assertSame($expect, $result);
	}

	public function testAddFunctionKeywordWithManyPatterns()
	{
		$this->fpparser->addFunctionKeyword('translate', array('foo', 'bar', 'baz'));

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'translate' => array('baz', 'bar', 'foo', '(.+)')
		);

		$this->assertSame($expect, $result);
	}

	public function testSetFunctionKeyword()
	{
		$this->fpparser->addFunctionKeyword('translate', 'foo');
		$this->fpparser->setFunctionKeyword('translate');

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'translate' => array('(.+)')
		);

		$this->assertSame($expect, $result);
	}

	public function testSetFunctionKeywordWithPatternShouldIgnoreDefaultPattern()
	{
		$this->fpparser->setFunctionKeyword('translate', array('foo'));

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'translate' => array('foo')
		);

		$this->assertSame($result, $expect);
	}

	public function testSetFunctionKeywords()
	{
		$this->fpparser->setFunctionKeywords(array('foo', 'bar'));

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'foo' => array('(.+)'),
			'bar' => array('(.+)')
		);

		$this->assertSame($expect, $result);
	}

	public function testSetFunctionKeywordsWithOnePattern()
	{
		$this->fpparser->setFunctionKeywords(array(
			'foo' => '1',
			'bar' => '2'
		));

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'foo' => array('1'),
			'bar' => array('2')
		);

		$this->assertSame($expect, $result);
	}

	public function testSetFunctionKeywordsWithManyPatterns()
	{
		$this->fpparser->setFunctionKeywords(array(
			'foo' => array('a', 'b'),
			'bar' => array('1', '2')
		));

		$result = $this->fpparser->getPatterns();
		$expect = array(
			'foo' => array('b', 'a'),
			'bar' => array('2', '1')
		);

		$this->assertSame($expect, $result);
	}

	public function ParseContentStringParams()
	{
		$content =
			"translate('foo');\n" .
			"translate('foo\'bar');";

		$sstring = Kblom_FunctionParamParser::PATTERN_SINGLE_QUOTE_STRING;
		$dstring = Kblom_FunctionParamParser::PATTERN_DOUBLE_QUOTE_STRING;

		$this->fpparser->setFunctionKeywords(array(
			'translate' => array(
				$sstring,
				$dstring
			),
		));

		$result = $this->fpparser->parseContent($content);

		//print_r($result);
	}

	public function ParseFile()
	{
		$sstring = Kblom_FunctionParamParser::PATTERN_SINGLE_QUOTE_STRING;
		$dstring = Kblom_FunctionParamParser::PATTERN_DOUBLE_QUOTE_STRING;

		$this->fpparser->setFunctionKeywords(array(
			'translate' => array(
				"^array\($sstring, $sstring,",
				"^array\($dstring, $dstring,",
				$sstring,
				$dstring
			),
			'myTranslate' => array(
				$sstring,
				$dstring
			)
		));


		$result = $this->fpparser->parseFile(
			$this->basepath . '/project/application/view/scripts/index/index.phtml');

		//print_r($result);
		$expect = array(
			'translate' => array(
				1 => array('Header'),
				7 => array('Car', 'Cars'),
				13 => array('It is %1\$s:%2\$:%3\$ o\'clock'),
				18 => array('Today is %1\$s in %2\$s. Actual time: %3\$s'),
				22 => array('moi'),
				28 => array('foo'),
			),
			'myTranslate' => array(
				23 => array('moi')
			)
		);

		//$this->assertSame($expect, $result);
	}

	public function testParseFolder()
	{
		$sstring = Kblom_FunctionParamParser::PATTERN_SINGLE_QUOTE_STRING;
		$dstring = Kblom_FunctionParamParser::PATTERN_DOUBLE_QUOTE_STRING;

		// Both type of strings
		$bstring = "$sstring|$dstring";

		$this->fpparser->setDefaultPattern($bstring);

		$arrayKeys = 'label|description';

		$this->fpparser->setFunctionKeywords(array(
			'translate' => array(
				"^array\($bstring, $bstring,",
				$bstring
			),
			'myTranslate',
			'array' => array(
				"(?<={$arrayKeys})['\"]\s*=>\s*$sstring",
			)
		));

		$result = $this->fpparser->parseFolder(
			$this->basepath . '/project/application');

		print_r($result);
	}
}
