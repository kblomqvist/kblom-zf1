<?php

require_once 'Kblom/FunctionParamParser.php';
require_once 'Zend/Translate.php';

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

    /** Supported adapters with their file extensions */
    protected $_adapters = array(
        'array' => 'php',
    );

    protected $_profile;
    protected $_response;

    /**
     * Updates or creates a new translation resource file to match translation
     * message ids found from application source files.
     */
    public function create($locale, $module = 'all',
                           $adapter = 'array', $kwords = null)
    {
        if (!$this->moduleExists($module)) {
            return $this->_responseModuleDoesNotExist($module);
        }
        if (!array_key_exists($adapter, $this->_adapters)) {
            return $this->_responseUnknownAdapter($adapter);
        }
        if (null !== $kwords) {
            $this->addKeyWords($kwords);
        }

        /** Get translations */
        $translations = $this->getTranslations($locale, $module, $adapter);

        /** Save translations */
        $this->_saveTranslations($locale, $module, $adapter, $translations);

        /** Create response */
        $response = $this->_getResponse();

        if ($i = count($translations['new'])) {
            $response->appendContent("Added $i message id(s)", array('color' => 'green'));
            $response->appendContent(var_export($translations['new'], true));
        } else {
            $response->appendContent("No new message ids", array('color' => 'green'));
        }

        if ($i = count($translations['depricated'])) {
            $response->appendContent("Resource file includes $i depricated translation(s)", array('color' => 'yellow'));
            $response->appendContent(var_export($translations['depricated'], true));
        } else {
            $response->appendContent("No depricated translations", array('color' => 'green'));
        }

        return true;
    }

    public function sortTranslations(array $currentTranslations, array $matchedMessageIds)
    {
        $matches = array_fill_keys($matchedMessageIds, '');

        $new = array_diff_key($matches, $currentTranslations);
        $depricated = array_diff_key($currentTranslations, $matches);
        $active = array_diff_key(array_merge($new, $currentTranslations), $depricated);

        ksort($new, SORT_STRING);
        ksort($depricated, SORT_STRING);
        ksort($active, SORT_STRING);

        return array('new' => $new, 'depricated' => $depricated, 'active' => $active);
    }

    public function getTranslations($locale, $module, $adapter)
    {
        $currentTranslations = $this->_loadTranslations($locale, $module, $adapter);
        $matchedMessageIds = $this->getMatches($module);

        return $this->sortTranslations($currentTranslations, $matchedMessageIds);
    }

    public function getMatches($module)
    {
        $path = $this->getModulePath($module);

        $parser = new Kblom_FunctionParamParser(array(
            'basepath' => $path,
            'funcKeyWords' => $this->_func_kwords,
            'arrKeyWords' => $this->_arr_kwords,
            'defaultPattern' => self::DEFAULT_PATTERN
        ));
        if ($module == 'default' || $module == 'application') {
            $parser->setExcludePaths(array('modules'));
        }

        return $parser->getMatches(false);
    }

    /**
     * Returns path to module, which is 'application/modules/$module' or 
     * 'application' if $module == 'all' OR 'application'.
     */
    public function getModulePath($module)
    {
        $module = (string) $module;
        $profile = $this->_getProfile();
        $path = $profile->search('ApplicationDirectory')->getPath();

        if ($module === 'all' || $module === 'application') {
            return $path;
        }

        return $path . "/modules/$module";
    }

    /** Check if module exists */
    public function moduleExists($module)
    {
        $path = $this->getModulePath($module);
        return file_exists($path);
    }

    public function addKeyWords($list)
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

    /**
     * Returns path where to save translation resource file, which is
     * 'data/locales/$locale/modules', or 'data/locales/$locale' if
     * $module == 'all' OR 'application'.
     */
    protected function _getLocalesPath($locale, $module)
    {
        $profile = $this->_getProfile();
        $path  = $profile->search('LocalesDirectory')->create()->getPath();
        $path .= "/$locale";

        if ($module == 'all' || $module == 'application') {
            return $path;
        }

        return $path . "/modules";
    }

    /** Try to load current translations */
    protected function _loadTranslations($locale, $module, $adapter)
    {
        $profile = $this->_getProfile();
        $path    = $this->_getLocalesPath($locale, $module);
        $content = $path . '/' . $module . '.' . $this->_adapters[$adapter];

        try {
            $translate = new Zend_Translate(array(
                'adapter' => $adapter,
                'content' => $content,
                'locale'  => $locale,
                'disableNotices' => true
            ));
        } catch(Exception $e) {
            if (!file_exists($content)) {
                return array(); // The file will be created later
            }
            throw $e; // File exists, but consist from unkown format
        }

        // Translation file can be empty, thus this check
        if ($messageIds = $translate->getMessageIds()) {
            return array_combine($messageIds, $translate->getMessages());
        }

        return array();
    }

    protected function _saveTranslations($locale, $module, $adapter, array $translations)
    {
        if ($adapter == 'array') {
            $input = $this->_createArray($translations);
        }

        $path = $this->_getLocalesPath($locale, $module);
        $file = $path . '/' . $module . '.' . $this->_adapters[$adapter];

        if (!file_exists($path)) {
            mkdir($path);
        }

        return file_put_contents($file, $input);
    }

    protected function _createArray(array $translations)
    {
        $input = "<?php\nreturn array (\n";
        foreach ($translations['active'] as $key => $val) {
            $input .= "  '$key' => '$val',\n";
        }
        if (!empty($translations['depricated'])) {
            $input .= "\n  /** Depricated translations. Remove manually or save for reuse. */\n";
            foreach($translations['depricated'] as $key => $val) {
                $input .= "  '$key' => '$val',\n";
            }
        }
        $input .= ");\n";

        return $input;
    }

    protected function _responseUnknownAdapter($adapter)
    {
        $response = $this->_getResponse();
        $response->appendContent("$adapter: unknown adapter", array('color' => 'yellow'));

        return false;
    }

    protected function _responseModuleDoesNotExist($module)
    {
        $response = $this->_getResponse();
        $response->appendContent("$module: module does not exist", array('color' => 'yellow'));

        return false;
    }

    /** Proxy for profile loader */
    protected function _getProfile()
    {
        if (!isset($this->_profile)) {
            $this->_profile = $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        }
        return $this->_profile;
    }

    /** Proxy for getting response from registry */
    protected function _getResponse()
    {
        if (!isset($this->_response)) {
            $this->_response = $this->_registry->getResponse();
        }
        return $this->_response;
    }
}
