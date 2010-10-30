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
 * @copyright Copyright (c) 2010 Kim Blomqvist
 * @lisence   http://github.com/kblomqvist/Kblom-zf/raw/master/LICENSE The MIT License
 */
class Kblom_FunctionParamParser
{
    /**
     * Match to these function names. The given pattern is used to
     * parse function parameters.
     * 
     * Example 1: only default pattern is used
     *      array('function_name', 'other_function')
     *
     * Example 2: mixed default and custom patterns
     *      array(
     *          'function_name',
     *          'other_function' => "^'(.+)',\s*'(.+)'"
     *      )
     */
    protected $_func_kwords = array();

    /** Match to these array keys */
    protected $_arr_kwords = array();

    /** Paths where to find files. Defaults to path "basepath/." */
    protected $_basepath;
    protected $_relativePaths = array('.');

    /** Exclude these paths */
    protected $_excludePaths = array();

    /** Reads only files with these extensions */
    protected $_extensions = array('php', 'phtml');

    /** Parse nested directories of given paths */
    protected $_parseRecursively = true;

    /** Get the string inside matched function parenthesis; func(THIS_PORTION) */
    protected $_defaultPattern = '(.+)';

    protected $_matches;

    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        if (isset($options['funcKeyWords'])) {
            $this->setFuncKeyWords($options['funcKeyWords']);
        }
        if (isset($optios['arrKeyWords'])) {
            $this->setArrKeyWords($options['arrKeyWords']);
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
        if (isset($options['parseRecursively'])) {
            $this->setParseRecursively($options['parseRecursively']);
        }

        return $this;
    }

    public function setFuncKeyWords(array $func_kwords)
    {
        $this->_func_kwords = $func_kwords;
        return $this;
    }

    public function setArrKeyWords(array $arr_kwords)
    {
        $this->_arr_kwords = $arr_kwords;
        return $this;
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

    public function setDefaultPattern($pattern)
    {
        $this->_defaultPattern = (string) $pattern;
        return $this;
    }

    public function setParseRecursively($state)
    {
        $this->_parseRecursively = (boolean) $state;
        return $this;
    }

    public function getMatches($include_keys = true, $reparse = false)
    {
        if (!isset($this->_matches) || (true === $reparse)) {
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

    public function parseFile($file, $throw = false)
    {
        $path_parts = pathinfo($file);
        if (!in_array($path_parts['extension'], $this->_extensions)) {
            return array();
        }

        if (!is_file($file) || !is_readable($file)) {
            if (true === $throw) {
                throw new Zend_Exception($file . ' is not a file or is not readable.');
            }
            return array();
        }

        // Read file
        $f_content = file_get_contents($file);

        $matches = array();
        foreach ($this->_func_kwords as $func => $patterns) {
            if (is_int($func)) {
                $func = $patterns;
                $patterns = array($this->_defaultPattern);
            }

            // Look up for functions and catch params part
            preg_match_all("/{$func}\(\s*(.+)\s*\)/", $f_content, $t_array);
            $func_params = $t_array[1];
            unset($t_array);

            // Match set patterns against function's params part
            $matches[$func] = array();
            $pattern = implode($patterns, '|');
            foreach ($func_params as $params) {
                $matches[$func] = array_merge(
                    $matches[$func], $this->_parseFuncParams("/{$pattern}/", $params)
                );
            }
        }

        // Look up for array keys and catch value
        $matches['__arr_keys'] = array();
        foreach ($this->_arr_kwords as $key) {
            preg_match_all("/'{$key}'\s*=>\s*'(.+)'/", $f_content, $t_array);
            $matches['__arr_keys'] = array_merge($matches['__arr_keys'], $t_array[1]);
            unset($t_array);
        }

        unset($f_content);

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
                $matches = $this->_catMatches(
                    $matches, $this->parseFolder($folder . '/' . $file)
                );
            } else {
                $matches = $this->_catMatches(
                    $matches, $this->parseFile($folder . '/' . $file)
                );
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

    private function _parseFuncParams($pattern, $params)
    {
        preg_match($pattern, $params, $matches);
        unset($matches[0]);

        $ret = array();
        foreach ($matches as $match) {
            if (!empty($match)) {
                $ret[] = $match;
            }
        }
        unset($matches);

        return $ret;
    }
}

