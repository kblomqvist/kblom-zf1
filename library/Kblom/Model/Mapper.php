<?php
require_once 'Zend/Registry.php';

/**
 * Kblom_Model_Mapper
 *
 * Factory for entity data mapper objects. Be sure to use something similar
 * than this to get the largest benefit of the identity map pattern
 * implemented in Kblom_Model_Mapper_Entity mapper.
 *
 * @category  Kblom
 * @package   Kblom_Model
 * @copyright Copyright (c) 2010-2011 Kim Blomqvist
 * @license   http://github.com/kblomqvist/kblom-zf1/raw/master/LICENSE The MIT License
 */
class Kblom_Model_Mapper
{
	public static $namespace = 'Application_';

	/**
	 * Takes care of instantiation of Entity mapper objects. Every
	 * object is stored in registry when instantiated.
	 *
	 * @param string $mapper    Mapper name, e.g. User
	 * @param string $namespace OPTIONAL Mapper class namespace,
	 *                          e.g. Application (default)
	 * @return Kblom_Model_Mapper_Entity
	 */
	public static function factory($mapper, $namespace = null)
	{
		if ($namespace === null) {
			$namespace = self::$namespace;
		}

		$mapper = $namespace . 'Model_Mapper_' . (string) $mapper;

		if (!Zend_Registry::isRegistered($mapper)) {
			Zend_Registry::set($mapper, new $mapper());
		}

		$mapper = Zend_Registry::get($mapper);
		if (!$mapper instanceof Kblom_Model_Mapper_Entity) {
			throw new Exception('\'' . get_class($mapper) . '\' is invalid
			   Mapper object, should be instance of Kblom_Model_Mapper_Entity');
		}
		return $mapper;
	}
}
