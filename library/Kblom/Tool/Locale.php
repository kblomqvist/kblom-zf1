<?php

require_once 'Kblom/FunctionParamParser.php';

/**
 * CLI tool for translation resource file generation
 *
 * @category  Kblom
 * @package   Kblom_Tool_Locale
 * @copyright Copyright (c) 2010 Kim Blomqvist
 * @license   http://github.com/kblomqvist/Kblom-zf/raw/master/LICENSE The MIT License
 */
class Kblom_Tool_Locale extends Zend_Tool_Project_Provider_Abstract
{
    /** Default patter for function parameter parsing */
    const DEFAULT_PATTERN = "^'(.+)'\s*,|^'(.+)'$";

    /**
     * Parser matches to these function names and parses
     * function parameters with the given regexp pattern.
     */
    protected $_func_kwords = array(
        'translate' => array(
            self::DEFAULT_PATTERN,
            "^array\(\s*'(.+)'\s*,\s*'(.+)'",
        ),
        'plural' => array(
            "^'(.+)',\s*'(.+)'",
        ),
        'setLabel',
        'setDescription'
    );

    /** Parser matches to these array key names */
    protected $_arr_kwords = array(
        'label',
        'legend',
        'description',
    );

    /** Supported adapters */
    protected $_adapters = array(
        'array'
    );

    public function create($locale, $module = 'all',
                           $adapter = 'array', $kwords = null)
    {
        $response = $this->_registry->getResponse();
        $profile  = $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);

        /** Check adapter support */
        if (!in_array($adapter, $this->_adapters)) {
            return $this->_responseUnknownAdapter($adapter, $response);
        }

        /** Resolve paths */
        $modulePath = $this->_getModulePath($module, $profile);
        if (!file_exists($modulePath)) {
            return $this->_responseModuleDoesNotExist($module, $response);
        }
        $localesPath = $this->_getLocalesPath($locale, $module, $profile);

        /** Add additional key words */
        if (null !== $kwords) {
            $this->_addKeyWords($kwords);
        }

        /** Setup function param parser */
        $parser = new Kblom_FunctionParamParser(array(
            'basepath' => $modulePath,
            'funcKeyWords' => $this->_func_kwords,
            'arrKeyWords' => $this->_arr_kwords,
            'defaultPattern' => self::DEFAULT_PATTERN
        ));
        if ($module == 'default' || $module == 'application') {
            $parser->setExcludePaths(array('modules'));
        }

        /**
         * @TODO refactor beginning here...
         * - Save methods for different adapters
         * - It may be better use Zend_Translate to read current translations
         */
        $translations = $this->_loadTranslations($localesPath . "/$module.php");   
        $matches = $parser->getMatches(false);

        $i = 0; $addedMessages = '';
        foreach ($matches as $id) {
            if (!isset($translations[$id])) {
                $i++;
                $translations[$id] = '';
                $addedMessages .= "$id\n";
            }
        }

        if ($i) {
            $response->appendContent("Added $i message id(s)", array('color' => 'green'));
            $response->appendContent($addedMessages);
        } else {
            $response->appendContent("No new message id(s)", array('color' => 'green'));
        }
        
        $i = 0; $removedMessages = '';
        foreach ($translations as $id => $msg) {
            if (!in_array($id, $matches)) {
                $i++;
                unset($translations[$id]);
                $removedMessages .= "$id\n";
            }
        }

        if ($i) {
            $response->appendContent("Removed $i message id(s)", array('color' => 'yellow'));
            $response->appendContent($removedMessages);
        } else {
            $response->appendContent("No removed message id(s)", array('color' => 'green'));
        }

        $this->_saveTranslations($localesPath, "$module.php", $translations);

        return true;
    }

    protected function _getModulePath($module, $profile)
    {
        $path = $profile->search('ApplicationDirectory')->getPath();

        if ($module == 'all' || $module == 'application') {
            return $path;
        }

        return $path . "/modules/$module";
    }

    protected function _getLocalesPath($locale, $module, $profile)
    {
        $path = $profile->search('LocalesDirectory')->create()->getPath();
        $path .= "/$locale";

        if ($module == 'all' || $module == 'application') {
            return $path;
        }

        return $path . "/modules";
    }

    protected function _addKeyWords($list)
    {
        $kwords = explode(':', (string) $list);
        foreach ($kwords as $k) {
            preg_match('/^\[(.+)\]$/', $k, $arr_key);
            if (isset($arr_key[1])) {
                $this->_arr_kwords[] = $arr_key[1];
            } else {
                $this->_func_kwords[] = $k;
            }
        }
    }

    protected function _loadTranslations($filename)
    {
        $data = array();
        if (file_exists($filename)) {
            ob_start();
            $data = include($filename);
            ob_end_clean();
        }
        return $data;
    }

    protected function _saveTranslations($path, $filename, array $translations)
    {
        if (!file_exists($path)) {
            mkdir($path);
        }

        return file_put_contents($path . "/$filename", "<?php\nreturn " . var_export($translations, true) . ';');
    }

    protected function _responseUnknownAdapter($adapter, $response)
    {
        $response->appendContent("$adapter: unknown adapter", array('color' => 'yellow'));

        return false;
    }

    protected function _responseModuleDoesNotExist($module, $response)
    {
        $response->appendContent("$module: module does not exist", array('color' => 'yellow'));

        return false;
    }
}
