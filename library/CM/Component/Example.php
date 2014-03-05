<?php

class CM_Component_Example extends CM_Component_Abstract {

  public function prepare() {
    $foo = $this->_params->getString('foo', 'value1');
    $colorStyles = $this->_getColorStyles();
    $icons = $this->_getIcons();

    $this->setTplParam('now', time());
    $this->setTplParam('foo', $foo);
    $this->setTplParam('colorStyles', $colorStyles);
    $this->setTplParam('icons', $icons);

    $this->_setJsParam('uname', 'uname');
  }

  public function checkAccessible(CM_Render $render) {
    if (!CM_Bootloader::getInstance()->isDebug()) {
      throw new CM_Exception_NotAllowed();
    }
  }

  public static function ajax_test(CM_Params $params, CM_ComponentFrontendHandler $handler, CM_Response_View_Ajax $response) {
    $x = $params->getString('x');
    sleep(2);
    return 'x=' . $x;
  }

  public static function ajax_error(CM_Params $params, CM_ComponentFrontendHandler $handler, CM_Response_View_Ajax $response) {
    $status = $params->getInt('status', 200);
    $message = $params->has('text') ? $params->getString('text') : null;
    $messagePublic = $params->getBoolean('public', false) ? $message : null;
    if (in_array($status, array(500, 599), true)) {
      $response->addHeaderRaw('HTTP/1.1 ' . $status . ' Internal Server Error');
      $response->sendHeaders();
      exit($message);
    }
    $exception = $params->getString('exception');
    if (!in_array($exception, array('CM_Exception', 'CM_Exception_AuthRequired'), true)) {
      $exception = 'CM_Exception';
    }
    throw new $exception($message, $messagePublic);
  }

  public static function ajax_ping(CM_Params $params, CM_ComponentFrontendHandler $handler, CM_Response_View_Ajax $response) {
    $number = $params->getInt('number');
    self::stream($response->getViewer(true), 'ping', array("number" => $number, "message" => 'pong'));
  }

  public static function rpc_time() {
    return time();
  }

  /**
   * @return string[]
   */
  private function _getIcons() {
    $path = DIR_PUBLIC . '/static/css/library/icon.less';
    if (!CM_File::exists($path)) {
      return array();
    }

    $file = new CM_File($path);
    preg_match_all('#\.icon-(.+?):before { content:#', $file->read(), $icons);
    return $icons[1];
  }

  /**
   * @return array
   */
  private function _getColorStyles() {
    $site = $this->getParams()->getSite('site');
    $style = '';
    foreach (array_reverse($site->getNamespaces()) as $namespace) {
      $path = CM_Util::getNamespacePath($namespace) . 'layout/default/variables.less';
      if (CM_File::exists($path)) {
        $file = new CM_File($path);
        $style .= $file . PHP_EOL;
      }
    }
    preg_match_all('#@(color\w+)#', $style, $matches);
    $colors = array_unique($matches[1]);
    foreach ($colors as $variableName) {
      $style .= '.' . $variableName . ' { background-color: @' . $variableName . '; }' . PHP_EOL;
    }
    $lessCompiler = new lessc();
    $style = $lessCompiler->compile($style);
    preg_match_all('#.(color\w+)\s+\{([^}]+)\}#', $style, $matches);
    return array_combine($matches[1], $matches[2]);
  }
}
