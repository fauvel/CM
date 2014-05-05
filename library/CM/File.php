<?php

class CM_File extends CM_Class_Abstract {

    /** @var string */
    private $_path;

    /** @var CM_File_Filesystem */
    protected $_filesystem;

    /**
     * @param string|CM_File          $file Path to file
     * @param CM_File_Filesystem|null $filesystem
     * @throws CM_Exception_Invalid
     */
    public function __construct($file, CM_File_Filesystem $filesystem = null) {
        if ($file instanceof CM_File) {
            $file = $file->getPath();
        }
        if (null === $filesystem) {
            $filesystem = self::getFilesystemDefault();
        }

        $this->_filesystem = $filesystem;
        $this->_path = (string) $file;
        if (!$this->getExists()) {
            throw new CM_Exception_Invalid('File path `' . $file . '` does not exist or is not a file.');
        }
    }

    /**
     * @return string File path
     */
    public function getPath() {
        return $this->_path;
    }

    /**
     * @return string File name
     */
    public function getFileName() {
        return pathinfo($this->getPath(), PATHINFO_BASENAME);
    }

    /**
     * @return string
     */
    public function getFileNameWithoutExtension() {
        return pathinfo($this->getPath(), PATHINFO_FILENAME);
    }

    /**
     * @return int
     */
    public function getSize() {
        return $this->_filesystem->getSize($this->getPath());
    }

    /**
     * @return string File mime type
     * @throws CM_Exception
     */
    public function getMimeType() {
        $info = new finfo(FILEINFO_MIME);
        $infoFile = $info->buffer($this->read());
        if (false === $infoFile) {
            throw new CM_Exception('Cannot detect FILEINFO_MIME of `' . $this->getPath() . '`');
        }
        $mime = explode(';', $infoFile);
        return $mime[0];
    }

    /**
     * @return string|null
     */
    public function getExtension() {
        $fileName = $this->getFileName();
        if (false === strpos($fileName, '.')) {
            return null;
        }

        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * @return string MD5-hash of file contents
     */
    public function getHash() {
        return $this->_filesystem->getChecksum($this->getPath());
    }

    /**
     * @return bool
     */
    public function getExists() {
        return $this->_filesystem->exists($this->getPath());
    }

    /**
     * @return string
     */
    public function read() {
        $cache = CM_Cache_Storage_Runtime::getInstance();
        if (false === ($content = $cache->get($this->_getCacheKeyContent()))) {
            $content = $this->_filesystem->read($this->getPath());
            $cache->set($this->_getCacheKeyContent(), $content, 1);
        }
        return $content;
    }

    /**
     * @return string
     */
    public function readFirstLine() {
        $content = $this->read();
        if (false !== ($firstLineEnd = strpos($content, "\n"))) {
            $content = substr($content, 0, $firstLineEnd + 1);
        }
        return $content;
    }

    /**
     * @param string $content
     */
    public function write($content) {
        $this->_filesystem->write($this->getPath(), $content);
        $cache = CM_Cache_Storage_Runtime::getInstance();
        $cache->set($this->_getCacheKeyContent(), $content, 1);
    }

    public function truncate() {
        $this->write('');
    }

    /**
     * @param string $path
     */
    public function copy($path) {
        $this->_filesystem->copy($this->getPath(), $path);
    }

    /**
     * @param string $path
     */
    public function move($path) {
        $path = (string) $path;
        $this->_filesystem->rename($this->getPath(), $path);
        $this->_path = $path;
    }

    public function delete() {
        $this->_filesystem->delete($this->getPath());
    }

    /**
     * @param int $permission
     */
    public function setPermissions($permission) {
        $permission = (int) $permission;
        @chmod($this->getPath(), $permission);
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->read();
    }

    /**
     * @param string $mode
     * @return resource
     * @throws CM_Exception
     */
    private function _openFileHandle($mode) {
        $resource = fopen($this->getPath(), $mode);
        if (false === $resource) {
            throw new CM_Exception('Could not open file in `' . $mode . '` mode. Path: `' . $this->getPath() . '`');
        }
        return $resource;
    }

    /**
     * @return string
     */
    private function _getCacheKeyContent() {
        return __CLASS__ . '_content_filesystem:' . get_class($this->_filesystem->getAdapter()) . '_path:' . $this->getPath();
    }

    /**
     * @param string                  $path
     * @param string|null             $content
     * @param CM_File_Filesystem|null $filesystem
     * @return static
     */
    public static function create($path, $content = null, CM_File_Filesystem $filesystem = null) {
        $content = (string) $content;
        if (null === $filesystem) {
            $filesystem = self::getFilesystemDefault();
        }
        $filesystem->write($path, $content);
        return new static($path, $filesystem);
    }

    /**
     * @param string|null $content
     * @param string|null $extension
     * @return static
     */
    public static function createTmp($extension = null, $content = null) {
        if (null !== $extension) {
            $extension = '.' . $extension;
        }
        $extension = (string) $extension;
        $filesystem = self::getFilesystemDefault();
        return static::create(CM_Bootloader::getInstance()->getDirTmp() . uniqid() . $extension, $content, $filesystem);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function exists($path) {
        return is_file($path);
    }

    /**
     * @param string $path
     * @throws CM_Exception_Invalid
     * @return int
     */
    public static function getModified($path) {
        $createStamp = filemtime($path);
        if (false === $createStamp) {
            throw new CM_Exception_Invalid('Can\'t get modified time of `' . $path . '`');
        }
        return $createStamp;
    }

    /**
     * taken from http://stackoverflow.com/a/2668953
     *
     * @param string $filename
     * @return string
     * @throws CM_Exception_Invalid
     */
    public static function sanitizeFilename($filename) {
        $filename = (string) $filename;

        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]", "}", "\\", "|", ";", ":", "\"", "'",
            "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;", "â€”", "â€“", ",", "<", ">", "/", "?", "\0");
        $clean = trim(str_replace($strip, '', $filename));
        $clean = preg_replace('/\s+/', "-", $clean);
        if (empty($clean)) {
            throw new CM_Exception_Invalid('Invalid filename.');
        }
        return $clean;
    }

    /**
     * @param string $path
     * @return CM_File
     */
    public static function factory($path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'php':
                return new CM_File_Php($path);
            case 'js':
                return new CM_File_Javascript($path);
            case 'csv':
                return new CM_File_Csv($path);
            default:
                return new CM_File($path);
        }
    }

    /**
     * @return CM_File_Filesystem
     */
    public static function getFilesystemDefault() {
        global $filesystem;
        if (null === $filesystem) {
            $adapter = new CM_File_Filesystem_Adapter_Local();
            $filesystem = new CM_File_Filesystem($adapter);
        }
        return $filesystem;
    }
}
