<?php

class CM_Util {

  /**
   * @param int $number
   * @return int[]
   */
  public static function decbinarr($number) {
    $bin = decbin($number);
    $binarr = array();
    for ($i = 0; $i < strlen($bin); $i++) {
      if (substr($bin, -$i - 1, 1) == 1) {
        $binarr[] = pow(2, $i);
      }
    }
    return $binarr;
  }

  /**
   * Return human-readable information on one line about a variable
   *
   * @param mixed $expression
   * @return string
   */
  public static function var_line($expression) {
    $line = print_r($expression, true);
    $line = str_replace(PHP_EOL, ' ', $line);
    $line = trim($line);
    return $line;
  }

  /**
   * @param mixed $argument
   * @return string
   */
  public static function varDump($argument) {
    if (is_object($argument)) {
      if ($argument instanceof stdClass) {
        return 'object';
      }
      $value = get_class($argument);
      if ($argument instanceof CM_Model_Abstract) {
        $value .= '(' . implode(', ', (array) $argument->getId()) . ')';
      }
      return $value;
    }
    if (is_string($argument)) {
      if (strlen($argument) > 20) {
        $argument = substr($argument, 0, 20) . '...';
      }
      return '\'' . $argument . '\'';
    }
    if (is_bool($argument) || is_numeric($argument)) {
      return var_export($argument, true);
    }
    return gettype($argument);
  }

  /**
   * @param string $pattern OPTIONAL
   * @param string $path    OPTIONAL
   * @return array
   */
  public static function rglob($pattern = '*', $path = './') {
    $files = glob($path . $pattern, GLOB_NOSORT);
    sort($files); // glob's sort is not reliable (locale dependent?)
    $paths = glob($path . '*', GLOB_NOSORT | GLOB_MARK | GLOB_ONLYDIR);
    sort($paths);
    foreach ($paths as $path) {
      $files = array_merge($files, self::rglob($pattern, $path));
    }
    return $files;
  }

  /**
   * @param string           $pattern
   * @param CM_Site_Abstract $site
   * @return string[]
   */
  public static function rglobLibraries($pattern, CM_Site_Abstract $site) {
    $paths = array();
    foreach ($site->getNamespaces() as $namespace) {
      $libraryPath = CM_Util::getNamespacePath($namespace) . 'library/' . $namespace . '/';
      $paths = array_merge($paths, CM_Util::rglob($pattern, $libraryPath));
    }
    return $paths;
  }

  /**
   * @param array $array
   * @param mixed $value
   * @return array
   */
  public static function array_remove(array $array, $value) {
    return array_filter($array, function ($entry) use ($value) {
      return $value != $entry;
    });
  }

  /**
   * @param string       $className
   * @param boolean|null $ignoreInvalid
   * @throws CM_Exception_Invalid
   * @return string
   */
  public static function getNamespace($className, $ignoreInvalid = null) {
    if (null === $ignoreInvalid) {
      $ignoreInvalid = false;
    }
    $ignoreInvalid = (boolean) $ignoreInvalid;
    $className = (string) $className;
    $tail = strpbrk($className, '_\\');
    $namespace = substr($className, 0, -strlen($tail));
    if (!$namespace) {
      if ($ignoreInvalid) {
        return null;
      }
      throw new CM_Exception_Invalid('Could not detect namespace of `' . $className . '`.');
    }
    return $namespace;
  }

  /**
   * @param string       $url
   * @param array|null   $params
   * @param boolean|null $methodPost
   * @param int|null     $timeout
   * @throws CM_Exception_Invalid
   * @return string
   */
  public static function getContents($url, array $params = null, $methodPost = null, $timeout = null) {
    $url = (string) $url;
    if (!empty($params)) {
      $params = http_build_query($params);
    }
    if (null === $timeout) {
      $timeout = 10;
    }
    $timeout = (int) $timeout;

    $curlConnection = curl_init();
    curl_setopt($curlConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlConnection, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curlConnection, CURLOPT_TIMEOUT, $timeout);
    if ($methodPost) {
      curl_setopt($curlConnection, CURLOPT_POST, 1);
      if (!empty($params)) {
        curl_setopt($curlConnection, CURLOPT_POSTFIELDS, $params);
      }
    } else {
      if (!empty($params)) {
        $url .= '?' . $params;
      }
    }
    curl_setopt($curlConnection, CURLOPT_URL, $url);

    $curlError = null;
    $contents = curl_exec($curlConnection);
    if ($contents === false) {
      $curlError = 'Curl error: `' . curl_error($curlConnection) . '` ';
    }

    $info = curl_getinfo($curlConnection);
    if ((int) $info['http_code'] !== 200) {
      $curlError .= 'HTTP Code: `' . $info['http_code'] . '`';
    }

    curl_close($curlConnection);
    if ($curlError) {
      $curlError = 'Fetching contents from `' . $url . '` failed: `' . $curlError;
      throw new CM_Exception_Invalid($curlError);
    }
    return $contents;
  }

  /**
   * @param string $xml
   * @throws CM_Exception_Invalid
   * @return SimpleXMLElement
   */
  public static function parseXml($xml) {
    $xml = (string) $xml;

    $xml = @simplexml_load_string($xml);
    if (false === $xml) {
      throw new CM_Exception_Invalid('Could not parse xml');
    }

    return $xml;
  }

  /**
   * @param string $path
   * @return string
   * @throws CM_Exception
   */
  public static function mkDir($path) {
    $path = (string) $path;
    if (!is_dir($path)) {
      if (false === @mkdir($path, 0777, true)) {
        if (!is_dir($path)) { // Might have been created in the meantime
          throw new CM_Exception('Cannot mkdir `' . $path . '`.');
        }
      }
    }
    return $path;
  }

  /**
   * @return string
   */
  public static function mkDirTmp() {
    $path = CM_Bootloader::getInstance()->getDirTmp() . uniqid() . DIRECTORY_SEPARATOR;
    return self::mkDir($path);
  }

  /**
   * @param string $path
   * @throws CM_Exception
   */
  public static function rmDir($path) {
    $path = (string) $path;
    self::rmDirContents($path);
    if (!@rmdir($path)) {
      throw new CM_Exception('Could not delete directory `' . $path . '`');
    }
  }

  /**
   * @param string $path
   * @throws CM_Exception
   */
  public static function rmDirContents($path) {
    $path = (string) $path . '/';
    if (!is_dir($path)) {
      return;
    }
    $systemFileList = scandir($path);
    $userFileList = array_diff($systemFileList, array('.', '..'));
    foreach ($userFileList as $filename) {
      $fullpath = $path . $filename;
      if (is_dir($fullpath)) {
        self::rmDir($fullpath . '/');
      } else {
        if (!@unlink($fullpath)) {
          throw new CM_Exception('Could not delete file `' . $fullpath . '`');
        }
      }
    }
  }

  /**
   * @param string $path
   * @param array  $params Query parameters
   * @return string
   */
  public static function link($path, array $params = null) {
    $link = $path;

    if (!empty($params)) {
      $params = CM_Params::encode($params);
      $query = http_build_query($params);
      if (strlen($query) > 0) {
        $link .= '?' . $query;
      }
    }

    return $link;
  }

  /**
   * @param string $string
   * @param int    $quote_style
   * @param string $charset
   * @return string
   */
  public static function htmlspecialchars($string, $quote_style = ENT_COMPAT, $charset = 'UTF-8') {
    return htmlspecialchars($string, $quote_style, $charset);
  }

  /**
   * @param string[] $paths
   * @throws CM_Exception_Invalid
   * @return array
   */
  public static function getClasses(array $paths) {
    $classes = array();
    foreach ($paths as $path) {
      $file = CM_File::factory($path);
      if (!$file instanceof CM_File_ClassInterface) {
        throw new CM_Exception_Invalid('Can only accept Class files. `' . $path . '` is not one.');
      }
      $meta = $file->getClassDeclaration();
      $classes[$meta['class']] = array('parent' => $meta['parent'], 'path' => $path);
    }

    $paths = array();
    while (count($classes)) {
      foreach ($classes as $class => $data) {
        if (!isset($classes[$data['parent']])) {
          $paths[$data['path']] = $class;
          unset($classes[$class]);
        }
      }
    }
    return $paths;
  }

  /**
   * @param string $string
   * @return string
   */
  public static function camelize($string) {
    return preg_replace_callback('/[-_]([a-z])/',
      function ($matches) {
        return strtoupper($matches[1]);
      }, ucfirst(strtolower($string)));
  }

  /**
   * @param string      $string
   * @param string|null $separator
   * @return string
   */
  public static function uncamelize($string, $separator = null) {
    if (null === $separator) {
      $separator = '-';
    }
    return strtolower(preg_replace('/([A-Z])/', $separator . '\1', lcfirst($string)));
  }

  /**
   * @param string $string
   * @return string
   */
  public static function titleize($string) {
    return preg_replace_callback('/[-_ ]([a-z])/', function ($matches) {
      return ' ' . strtoupper($matches[1]);
    }, ucfirst(strtolower($string)));
  }

  /**
   * @param string    $namespace
   * @param bool|null $relative
   * @return string
   */
  public static function getNamespacePath($namespace, $relative = null) {
    $path = CM_Bootloader::getInstance()->getNamespacePath($namespace);
    if (!$relative) {
      $path = DIR_ROOT . $path;
    }
    return $path;
  }

  /**
   * @param string $pathRelative
   * @return CM_File[]
   */
  public static function getResourceFiles($pathRelative) {
    $pathRelative = (string) $pathRelative;
    $paths = array();
    foreach (CM_Bootloader::getInstance()->getNamespaces() as $namespace) {
      $paths[] = CM_Util::getNamespacePath($namespace) . 'resources/' . $pathRelative;
    }
    $paths[] = DIR_ROOT . 'resources/' . $pathRelative;

    $files = array();
    foreach (array_unique($paths) as $path) {
      if (CM_File::exists($path)) {
        $files[] = new CM_File($path);
      }
    }
    return $files;
  }

  /**
   * @param string      $command
   * @param array|null  $args
   * @param string|null $input
   * @param string|null $inputPath
   * @throws CM_Exception
   * @return string Output
   */
  public static function exec($command, array $args = null, $input = null, $inputPath = null) {
    if (null === $args) {
      $args = array();
    }
    foreach ($args as $arg) {
      if (!strlen($arg)) {
        throw new CM_Exception('Empty argument');
      }
      $command .= ' ' . escapeshellarg($arg);
    }
    if ($inputPath) {
      $command .= ' <' . escapeshellarg($inputPath);
    }
    return self::_exec($command, $input);
  }

  /**
   * @param string $command
   * @param string $stdin
   * @return string
   * @throws CM_Exception
   */
  private static function _exec($command, $stdin) {
    $descriptorSpec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
      throw new CM_Exception('Cannot open command file pointer to `' . $command . '`');
    }

    if ($stdin) {
      fwrite($pipes[0], $stdin);
    }
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $returnStatus = proc_close($process);
    if ($returnStatus != 0) {
      throw new CM_Exception('Command `' . $command . '` failed. STDERR: `' . trim($stderr) . '` STDOUT: `' . trim($stdout) . '`.');
    }
    return $stdout;
  }

  /**
   * @param string|null $namespace
   * @return string
   */
  public static function benchmark($namespace = null) {
    static $times;
    if (!$times) {
      $times = array();
    }
    $now = microtime(true) * 1000;
    $previousValue = null;
    if (array_key_exists($namespace, $times)) {
      $difference = $now - $times[$namespace];
    } else {
      $difference = null;
    }
    $times[$namespace] = $now;
    return sprintf('%.2f ms', $difference);
  }

  /**
   * @param null $namespace
   * @return string
   *
   * Measures time between two successive calls, sums up multiple measurements and tracks call count
   */
  public static function benchmarkMultiple($namespace = null) {
    static $timeTotals;
    if (!$timeTotals) {
      $timeTotals = array();
    }
    static $callCount;
    if (!$callCount) {
      $callCount = array();
    }
    static $times;
    if (!$times) {
      $times = array();
    }
    $now = microtime(true) * 1000;
    $total = 0;
    if (!array_key_exists($namespace, $callCount)) {
      $callCount[$namespace] = 0;
    }
    if (array_key_exists($namespace, $timeTotals)) {
      $total = $timeTotals[$namespace];
    }
    if (array_key_exists($namespace, $times)) {
      $difference = $now - $times[$namespace];
      $total += $difference;
      $timeTotals[$namespace] = $total;
      unset($times[$namespace]);
      $callCount[$namespace] += 1;
    } else {
      $times[$namespace] = $now;
    }
    $count = $callCount[$namespace];
    $output = sprintf('called %d times', $count);
    if ($count) {
      $output .= sprintf(', Average: %.2f ms, Total: %.2f ms', $total / $count, $total);
    }
    return $output;
  }

  /**
   * @param string       $className
   * @param boolean|null $includeAbstracts
   * @return string[]
   */
  public static function getClassChildren($className, $includeAbstracts = null) {
    $key = CM_CacheConst::ClassChildren . '_className:' . $className . '_abstracts:' . (int) $includeAbstracts;
    $cache = CM_Cache_Local::getInstance();
    if (false === ($classNames = $cache->get($key))) {
      $pathsFiltered = array();
      $paths = array();
      foreach (CM_Bootloader::getInstance()->getNamespaces() as $namespace) {
        $namespacePaths = CM_Util::rglob('*.php', CM_Util::getNamespacePath($namespace) . 'library/');
        $paths = array_merge($paths, $namespacePaths);
      }
      $regexp = '#\bclass\s+(?<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+#';
      foreach ($paths as $path) {
        $file = new CM_File($path);
        $fileContents = $file->read();
        if (preg_match($regexp, $fileContents, $matches)) {
          if (class_exists($matches['name'], true)) {
            $reflectionClass = new ReflectionClass($matches['name']);
            if (($reflectionClass->isSubclassOf($className) ||
                interface_exists($className) && $reflectionClass->implementsInterface($className)) &&
              (!$reflectionClass->isAbstract() || $includeAbstracts)
            ) {
              $pathsFiltered[] = $path;
            }
          }
        }
      }
      $classNames = self::getClasses($pathsFiltered);
      $cache->set($key, $classNames);
    }
    return $classNames;
  }

  /**
   * A tree with $level tiers. The children of the rootnode have the distinct value of the first column as key and contain all the rows
   * with this key as first value. The children of such a node have the distinct values of the second column as key and contain all the
   * rows which have the the key of their grandparent as first value and the key of their parent as second value. And so on.
   * The amount of leaf nodes corresponds to the amount of rows in the resultset.
   * Each leaf node contains an array consisting of the $rowcount - $level last entries of the row it represents. Or a scalar in the
   * case of $level = $rowcount -1.
   *
   * @param array[]              $items
   * @param int|null             $level          The number of columns that are used as indexes.
   * @param bool|null            $distinctLeaves Whether or not the leaves are unique given the specified indexes
   * @param string|string[]|null $keyNames
   * @throws CM_Exception_Invalid
   * @return array[]
   */
  public static function getArrayTree(array $items, $level = null, $distinctLeaves = null, $keyNames = null) {
    if (null === $level) {
      $level = 1;
    }
    if (null === $distinctLeaves) {
      $distinctLeaves = true;
    }
    $keyNames = (array) $keyNames;
    $result = array();
    foreach ($items as $item) {
      if (!is_array($item) || count($item) < ($level + 1)) {
        throw new CM_Exception_Invalid('Item is not an array or has less than `' . ($level + 1) . '` elements.');
      }
      $resultEntry = & $result;
      for ($i = 0; $i < $level; $i++) {
        if (isset($keyNames[$i])) {
          $keyName = $keyNames[$i];
          if (!array_key_exists($keyName, $item)) {
            throw new CM_Exception_Invalid('Item has no key `' . $keyName . '`.');
          }
          $value = $item[$keyName];
          unset($item[$keyName]);
        } else {
          $value = array_shift($item);
        }
        $resultEntry = & $resultEntry[$value];
      }
      if (count($item) <= 1) {
        $item = reset($item);
      }
      if ($distinctLeaves) {
        $resultEntry = $item;
      } else {
        $resultEntry[] = $item;
      }
    }
    return $result;
  }
}
