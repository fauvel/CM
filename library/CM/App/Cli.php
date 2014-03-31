<?php

class CM_App_Cli extends CM_Cli_Runnable_Abstract {

    public function setup() {
        $this->_getOutput()->writeln('Setting up filesystem…');
        $this->setupFilesystem();
        $this->_getOutput()->writeln('Setting up database…');
        $this->setupDatabase();
        $this->_getOutput()->writeln('Setting up translations…');
        $this->setupTranslations();
    }

    public function setupFilesystem() {
        CM_App::getInstance()->setupFilesystem();
    }

    public function setupDatabase() {
        CM_App::getInstance()->setupDatabase();
    }

    public function setupTranslations() {
        CM_App::getInstance()->setupTranslations();
    }

    public function fillCaches() {
        $this->_getOutput()->writeln('Warming up caches…');
        CM_App::getInstance()->fillCaches();
    }

    public function deploy() {
        $this->setup();
        $this->setDeployVersion();

        $dbCli = new CM_Db_Cli($this->_getInput(), $this->_getOutput());
        $dbCli->runUpdates();
    }

    public function generateConfigInternal() {
        // Create class types and action verbs config PHP
        $fileHeader = '<?php' . PHP_EOL;
        $fileHeader .= '// This is autogenerated action verbs config file. You should not adjust changes manually.' . PHP_EOL;
        $fileHeader .= '// You should adjust TYPE constants and regenerate file using `config generate` command' . PHP_EOL;
        $path = DIR_ROOT . 'resources/config/internal.php';
        $generator = new CM_Config_Generator();
        $classTypesConfig = $generator->generateConfigClassTypes();
        $actionVerbsConfig = $generator->generateConfigActionVerbs();
        foreach ($generator->getClassTypesRemoved() as $classRemoved) {
            $this->_getOutput()->writeln('Removed `' . $classRemoved . '`');
        }
        foreach ($generator->getClassTypesAdded() as $type => $classAdded) {
            $this->_getOutput()->writeln('Added `' . $classAdded . '` with type `' . $type . '`');
        }
        CM_File::create($path, $fileHeader . PHP_EOL . $classTypesConfig . PHP_EOL . PHP_EOL . $actionVerbsConfig . PHP_EOL);
        $this->_getOutput()->writeln('Created `' . $path . '`');

        // Create model class types and action verbs config JS
        $path = DIR_ROOT . 'resources/config/js/internal.js';
        $classTypes = $generator->getNamespaceTypes();
        $modelTypesConfig = 'cm.model.types = ' . CM_Params::encode(array_flip($classTypes['CM_Model_Abstract']), true) . ';';
        $actionTypesConfig = 'cm.action.types = ' . CM_Params::encode(array_flip($classTypes['CM_Action_Abstract']), true) . ';';
        CM_File::create($path, $modelTypesConfig . PHP_EOL . $actionTypesConfig . PHP_EOL);
        $this->_getOutput()->writeln('Created `' . $path . '`');
    }

    /**
     * @param int|null $deployVersion
     */
    public function setDeployVersion($deployVersion = null) {
        $deployVersion = (null !== $deployVersion) ? (int) $deployVersion : time();
        $sourceCode = '<?php' . PHP_EOL . '$config->deployVersion = ' . $deployVersion . ';';
        $targetPath = DIR_ROOT . 'resources' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'deploy.php';
        CM_File::create($targetPath, $sourceCode);
    }

    public static function getPackageName() {
        return 'app';
    }
}
