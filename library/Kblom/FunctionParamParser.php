<?php
/**
 * Parses specified function names and array keys from PHP source files.
 * During every hit function params are matched and saved according to user
 * given regexp pattern. The values of found array keys are saved as is.
 *
 * Common usage: translation resource file generation
 *
 * @category  Kblom
 * @package   Kblom_FunctionParamParser
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_FunctionParamParser
{
	const PATTERN_SINGLE_QUOTE_STRING = "'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'";
	const PATTERN_DOUBLE_QUOTE_STRING = "\"([^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"";

    /**
	 * Match these function names and parse its parameters with the given
	 * regexp patterns. Multiple patterns are imploded by regexp OR, |.
	 *
	 * Example:
	 *   array(
	 *     'translate' => array(
	 *        "'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'",
	 *        "\"([^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"",
	 *     )
	 *   );
	 *
	 * @var array
     */
	protected $_patterns = array();

    /** Paths where to find files. Defaults to path "basepath/." */
    protected $_basepath;
    protected $_relativePaths = array('.');

    /** Exclude these paths */
    protected $_excludePaths = array();

	/**
	 * Reads only files with these extensions
	 *
	 * @var array 
	 */
    protected $_extensions = array('php', 'phtml');

    protected $_parseNestedDirs = true;

    protected $_defaultPattern = '(.+)';

    protected $_matches;

    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
		if (isset($options['defaultPattern'])) {
			$this->setDefaultPattern($options['defaultPattern']);
		}
        if (isset($options['functionKeywords'])) {
            $this->setFuncKeyWords($options['functionKeywords']);
        }
        if (isset($options['basepath'])) {
            $this->setBasepath($options['basepath']);
        }
        if (isset($options['relativePaths'])) {
            $this->setRelativePaths($options['relativePaths']);
        }
        if (isset($options['excludePaths'])) {
            $this->setExcludePaths($options['excludePaths']);
        }
        if (isset($options['parseNestedDirs'])) {
            $this->setParseNestedDirs($options['parseNestedDirs']);
        }

        return $this;
	}

	public function setDefaultPattern($pattern)
	{
		if (empty($pattern)) {
			$pattern = null;
		}
		$this->_defaultPattern = $pattern;

		return $this;
	}

	public function getDefaultPattern()
	{
		return $this->_defaultPattern;
	}

    public function setFunctionKeywords(array $kwords)
    {
		$this->_patterns = array();

		foreach ($kwords as $fname => $pattern) {
			if (is_int($fname)) {
				$fname = $pattern;
				$pattern = null;
			}
			$this->setFunctionKeyword($fname, $pattern);
		}
		return $this;
	}

	public function setFunctionKeyword($fname, $pattern = null)
	{
		if ($pattern === null) {
			unset($this->_patterns[$fname]);
		} else {
			$this->_patterns[$fname] = array();
		}

		return $this->addFunctionKeyword($fname, $pattern);
	}

	public function addFunctionKeywords(array $kwords)
	{
		foreach ($kwords as $fname => $pattern) {
			if (is_int($fname)) {
				$fname = $pattern;
				$pattern = null;
			}
			$this->addFunctionKeyword($fname, $pattern);
		}
		return $this;
	}

	public function addFunctionKeyword($fname, $pattern = null)
	{
		if (!array_key_exists($fname, $this->_patterns)) {
			$this->_patterns[$fname] = array($this->getDefaultPattern());
		}

		if (!is_array($pattern)) {
			if (!empty($pattern)) {
				$pattern = array((string) $pattern);
			} else {
				$pattern = array();
			}
		}

		foreach ($pattern as $p) {
			if (!in_array($p, $this->_patterns[$fname])) {
				array_unshift($this->_patterns[$fname], $p);
			}
		}

		return $this;
	}

	public function getPatterns()
	{
		return $this->_patterns;
	}

    public function setBasepath($path)
    {
        $this->_basepath = (string) $path;
        return $this;
    }

    public function setRelativePaths(array $paths) {
        $this->_relativePaths = array();
        foreach ($paths as $path) {
            $this->_relativePaths[] = trim($path, '/');
        }
        return $this;
    }

    public function setExcludePaths(array $paths) {
        $this->_excludePaths = array();
        foreach ($paths as $path) {
            $this->_excludePaths[] = trim($path, '/');
        }
        return $this;
    }

    public function setParseRecursively($state)
    {
        $this->_parseRecursively = (boolean) $state;
        return $this;
    }

    public function parseAll($include_keys = true, $reparse = false)
    {
        if (!isset($this->_matches) || ($reparse === true)) {
            $this->_matches = array();

            foreach ($this->_relativePaths as $path) {
                $path = $this->_basepath . '/' . $path;
                $this->_matches = $this->_catMatches(
                    $this->_matches, $this->parseFolder($path, $this->_parseRecursively)
                );
            }
        }

        if (false === $include_keys) {
            $ret = array();
            foreach ($this->_matches as $func => $matches) {
                $ret = array_merge($ret, $matches);
            }
            return $ret;
        }

        return $this->_matches;
    }

    public function parseFile($file, $throw = true)
    {
        $pathParts = pathinfo($file);
        if (!in_array($pathParts['extension'], $this->_extensions)) {
            return array();
        }

        if (!is_file($file) || !is_readable($file)) {
            if (true === $throw) {
                throw new Zend_Exception($file . ' is not a file or is not readable.');
            }
            return array();
        }

		return $this->parseContent(file_get_contents($file));
	}

	public function parseContent($content)
	{
		if (empty($this->_patterns)) {
			throw new Exception('No function patterns set');
		}

		// Create pattern to match functions
		$pattern = implode('|', array_keys($this->_patterns));
		$pattern = "/($pattern)\s*\(\s*(.+)\s*\)/";

		// Match functions
		if (($c = preg_match_all($pattern, $content, $matches)) == 0) {
			return array(); // No matches
		}

		// Parse function parameter part with given patterns
		for ($i = 0; $i < $c; $i++) {
			$pattern = implode('|', $this->_patterns[$matches[1][$i]]);
			preg_match_all("/$pattern/", $matches[2][$i], $matches[2][$i]);
		}

		// Trim matches
		for ($i = 0; $i < $c; $i++) {

			// Save matches to temp array
			$t = $matches[2][$i];
			unset($t[0]);

			// Format space for trimmed matches from matches array
			$matches[2][$i] = array();

			// Go through function parameter matches, pmatch
			foreach ($t as $pmatch) {
				foreach ($pmatch as $pm) {
					if (!empty($pm)) {

					// Catch only the first match if not empty
					$matches[2][$i][] = $pm;
					}
				}
			}
		}

		return $this->postProcessMatches($matches, $content);
    }

	protected function postProcessMatches($matches, $content)
	{
		// Extend to resolve for example line numbers
		return $matches;
	}

    public function parseFolder($folder, $recursive = true)
    {
        if (substr($folder, -1) === '/') {
            $folder = substr_replace($folder, '', -1);
        }
        if ($this->isExcludedPath($folder)) {
            return array();
        }

        $files = scandir($folder);
        unset($files[0], $files[1]); // remove files . and ..

        $matches = array();
        foreach($files as $file) {
            if (is_dir($folder . '/' . $file) && (true === $recursive)) {
                // Recursion, traverses through subdirs
                $matches = array_merge($matches, $this->parseFolder($folder . '/' . $file));
            } else {
				$matches["$folder/$file"] =
					$this->parseFile($folder . '/' . $file);
            }
        }

        return $matches;
    }

    public function isExcludedPath($path)
    {
        $path = (string) $path;
        foreach ($this->_excludePaths as $p) {
            if ($this->_basepath . '/' . $p == $path) {
                return true;
            }
        }
        return false;
    }

    private function _catMatches($a1, $a2)
    {
        foreach ($a2 as $func => $matches) {
            if (!isset($a1[$func])) {
                $a1[$func] = array();
            }
            $a1[$func] = array_merge($a1[$func], $matches);
        }

        return $a1;
    }

	public function escapeRegexpPattern($pattern)
	{
		$search = array("\\", '$', '^', '[', ']', '(', ')');
		$replace = array("\\\\", '\$', "\^", '\[', '\]', '\(', '\)');
		return str_replace($search, $replace, $pattern);
	}
}

