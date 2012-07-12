<?php

class CM_App {
	/**
	 * @var CM_App
	 */
	private static $_instance;

	/**
	 * @return CM_App
	 */
	public static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * @return int
	 */
	public function getVersion() {
		return (int) CM_Option::getInstance()->get('app.version');
	}

	/**
	 * @param int $version
	 */
	public function setVersion($version) {
		$version = (int) $version;
		CM_Option::getInstance()->set('app.version', $version);
	}

	/**
	 * @return int
	 */
	public function getReleaseStamp() {
		return (int) CM_Option::getInstance()->get('app.releaseStamp');
	}

	/**
	 * @param int|null $releaseStamp
	 */
	public function setReleaseStamp($releaseStamp = null) {
		if (null === $releaseStamp) {
			$releaseStamp = time();
		}
		$releaseStamp = (int) $releaseStamp;
		CM_Option::getInstance()->set('app.releaseStamp', $releaseStamp);
	}

	/**
	 * @param              $directory
	 * @param Closure|null $callbackBefore fn($version)
	 * @param Closure|null $callbackAfter  fn($version)
	 * @return int Number of version bumps
	 */
	public function runUpdateScripts($directory, Closure $callbackBefore = null, Closure $callbackAfter = null) {
		CM_Cache::flush();
		CM_CacheLocal::flush();
		$version = $versionStart = $this->getVersion();
		while (true) {
			$updateScript = $directory . ($version + 1) . '.php';
			if (!file_exists($updateScript)) {
				break;
			}
			$version++;
			if ($callbackBefore) {
				$callbackBefore($version);
			}
			require $updateScript;
			$this->setVersion($version);
			if ($callbackAfter) {
				$callbackAfter($version);
			}
		}
		return ($version - $versionStart);
	}

	/**
	 * @return string
	 */
	public function generateClassTypesConfig() {
		$content = '<?php' . PHP_EOL . PHP_EOL;
		$content .= '// This is autogenerated class types config file. You should not adjust changes manually.' . PHP_EOL;
		$content .= '// You should adjust TYPE constants and regenerate file using www/cron/generate-config-class-types.php' . PHP_EOL;
		$typeNamespaces = array(
			'CM_Site_Abstract',
			'CM_Action_Abstract',
			'CM_Model_Abstract',
			'CM_Model_ActionLimit_Abstract',
			'CM_Model_Entity_Abstract',
			'CM_Model_StreamChannel_Abstract',
			'CM_Mail',
			'CM_Paging_Log_Abstract',
			'CM_Paging_ContentList_Abstract',
		);
		foreach ($typeNamespaces as $typeNamespace) {
			$content .= join(PHP_EOL, $this->_generateClassTypesConfig($typeNamespace));
		}
		return $content;
	}

	/**
	 * @param string $typeNamespace
	 * @throws CM_Exception_Invalid
	 * @return string[]
	 */
	private function _generateClassTypesConfig($typeNamespace) {
		$verifiedClasses = array();
		$highestTypeUsed = 0;
		foreach (CM_Util::getClassChildren($typeNamespace) as $className) {
			$reflectionClass = new ReflectionClass($className);
			if ($reflectionClass->hasConstant('TYPE')) {
				$type = $className::TYPE;
				if (array_key_exists($type, $verifiedClasses)) {
					throw new CM_Exception_Invalid('Duplicate `TYPE` constant for `' . $className . '` and `' . $verifiedClasses[$type] . '`. Both equal `' . $type . '` (within `' . $typeNamespace . '` type namespace).');
				}
				$verifiedClasses[$type] = $className;
				$highestTypeUsed = max($highestTypeUsed, $type);
			} elseif (!$reflectionClass->isAbstract()) {
				throw new CM_Exception_Invalid('`' . $className . '` does not have `TYPE` constant defined');
			}
		}

		ksort($verifiedClasses);
		$declarations = array();
		foreach ($verifiedClasses as $className) {
			$type = $className::TYPE;
			$declarations[$type] = '$config->' . $typeNamespace . '->types[' . $className . '::TYPE] = \'' . $className . '\'; // #' . $type;
		}

		$lines = array();
		$lines[] = '';
		$lines[] = 'if (!isset($config->' . $typeNamespace . ')) {';
		$lines[] = "\t" . '$config->' . $typeNamespace . ' = new StdClass();';
		$lines[] = '}';
		$lines[] = '$config->' . $typeNamespace . '->types = array();';
		$lines = array_merge($lines, $declarations);
		$lines[] = '// Highest type used: #' . $highestTypeUsed;
		$lines[] = '';
		return $lines;
	}
}
