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

    /** Supported adapters with their file extensions */
    protected $_adapters = array(
        'array' => 'php',
    );

    protected $_profile;
    protected $_response;

    public function create($locale, $module = 'all',
                           $adapter = 'array', $kwords = null)
    {
        $response = $this->_getResponse();

        /** Check adapter support */
        if (!array_key_exists($adapter, $this->_adapters)) {
            return $this->_responseUnknownAdapter($adapter);
        }

        /** Resolve paths */
        $modulePath = $this->_getModulePath($module);
        if (!file_exists($modulePath)) {
            return $this->_responseModuleDoesNotExist($module);
        }
        $localesPath = $this->_getLocalesPath($locale, $module);

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
         * @TODO beging refactoring here...
         * - Save methods for different adapters. It may be good idea to use Zend_Log_Writer.
         */
        $translations = $this->_loadTranslations($locale, $module, $adapter);
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

    /**
     * Returns path to module, which is 'application/modules/$module' or 
     * 'application' if $module == 'all' OR 'application'.
     */
    protected function _getModulePath($module)
    {
        $profile = $this->_getProfile();
        $path = $profile->search('ApplicationDirectory')->getPath();

        if ($module == 'all' || $module == 'application') {
            return $path;
        }

        return $path . "/modules/$module";
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

    protected function _saveTranslations($path, $filename, array $translations)
    {
        if (!file_exists($path)) {
            mkdir($path);
        }

        return file_put_contents($path . "/$filename", "<?php\nreturn " . var_export($translations, true) . ';');
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
