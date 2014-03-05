<?php

abstract class CM_Response_View_Abstract extends CM_Response_Abstract {

  /**
   * @param string|null $key
   * @return array
   * @throws CM_Exception_Invalid
   */
  protected function _getViewInfo($key = null) {
    if (null === $key) {
      $key = 'view';
    }
    $query = $this->_request->getQuery();
    if (!isset($query[$key])) {
      throw new CM_Exception_Invalid('View `' . $key . '` not set.', null, null, CM_Exception::WARN);
    }
    $viewInfo = $query[$key];
    if (!is_array($viewInfo)) {
      throw new CM_Exception_Invalid('View `' . $key . '` is not an array', null, null, CM_Exception::WARN);
    }
    if (!isset($viewInfo['id'])) {
      throw new CM_Exception_Invalid('View id `' . $key . '` not set.', null, null, CM_Exception::WARN);
    }
    if (!isset($viewInfo['className']) || !class_exists($viewInfo['className']) ||
      !('CM_View_Abstract' == $viewInfo['className'] || is_subclass_of($viewInfo['className'], 'CM_View_Abstract'))
    ) {
      throw new CM_Exception_Invalid('View className `' . $key . '` is illegal: `' . $viewInfo['className'] .
        '`.', null, null, CM_Exception::WARN);
    }
    if (!isset($viewInfo['params'])) {
      throw new CM_Exception_Invalid('View params `' . $key . '` not set.', null, null, CM_Exception::WARN);
    }
    if (!isset($viewInfo['parentId'])) {
      $viewInfo['parentId'] = null;
    }
    return array('id'       => (string) $viewInfo['id'], 'className' => (string) $viewInfo['className'], 'params' => (array) $viewInfo['params'],
                 'parentId' => (string) $viewInfo['parentId']);
  }

  /**
   * @param array $params OPTIONAL
   * @return string Auto-id
   */
  public function reloadComponent($params = null) {
    $componentInfo = $this->_getViewInfo();
    $componentParams = $componentInfo['params'];
    if ($params) {
      $componentParams = array_merge($componentParams, $params);
    }
    $componentParams = CM_Params::factory($componentParams);

    $component = CM_Component_Abstract::factory($componentInfo['className'], $componentParams, $this->getViewer());
    $component->checkAccessible($this->getRender());
    $component->prepare();

    $html = $this->getRender()->render($component);

    $this->getRender()->getJs()->onloadHeaderJs('cm.window.appendHidden(' . json_encode($html) . ');');
    $this->getRender()->getJs()->onloadPrepareJs(
      'cm.views["' . $componentInfo['id'] . '"].replaceWith(cm.views["' . $component->getAutoId() . '"]);');
    $this->getRender()->getJs()->onloadReadyJs('cm.views["' . $component->getAutoId() . '"]._ready();');
    $componentInfo['id'] = $component->getAutoId();

    return $component->getAutoId();
  }

  /**
   * @param CM_Params $params
   * @return array
   */
  public function loadComponent(CM_Params $params) {
    $component = CM_Component_Abstract::factory($params->getString('className'), $params, $this->getViewer());
    $component->checkAccessible($this->getRender());
    $component->prepare();

    $html = $this->getRender()->render($component);
    $js = $this->getRender()->getJs()->getJs();

    $this->getRender()->getJs()->clear();
    return array('autoId' => $component->getAutoId(), 'html' => $html, 'js' => $js);
  }

  /**
   * @param CM_Params             $params
   * @param CM_Response_View_Ajax $response
   * @throws CM_Exception_Invalid
   * @return array
   */
  public function loadPage(CM_Params $params, CM_Response_View_Ajax $response) {
    $request = new CM_Request_Get($params->getString('path'), $this->getRequest()->getHeaders(), $this->getRequest()->getServer(), $this->getRequest()->getViewer());

    $count = 0;
    $paths = array($request->getPath());
    do {
      $url = $this->getRender()->getUrl(CM_Util::link($request->getPath(), $request->getQuery()));
      if ($count++ > 10) {
        throw new CM_Exception_Invalid('Page redirect loop detected (' . implode(' -> ', $paths) . ').');
      }
      $responsePage = new CM_Response_Page_Embed($request);
      $responsePage->process();
      $paths[] = $request->getPath();

      if ($redirectUrl = $responsePage->getRedirectUrl()) {
        $redirectExternal = (0 !== mb_stripos($redirectUrl, $this->getRender()->getUrl()));
        if ($redirectExternal) {
          return array('redirectExternal' => $redirectUrl);
        }
      }
    } while ($redirectUrl);

    foreach ($responsePage->getCookies() as $name => $cookieParameters) {
      $response->setCookie($name, $cookieParameters['value'], $cookieParameters['expire'], $cookieParameters['path']);
    }

    $page = $responsePage->getPage();

    $this->_setStringRepresentation(get_class($page));

    $html = $responsePage->getContent();
    $js = $responsePage->getRender()->getJs()->getJs();
    $responsePage->getRender()->getJs()->clear();

    $title = $responsePage->getTitle();
    $layoutClass = get_class($page->getLayout($this->getSite()));
    $menuList = array_merge($this->getSite()->getMenus(), $responsePage->getRender()->getMenuList());
    $menuEntryHashList = $this->_getMenuEntryHashList($menuList, $page);

    return array('autoId'            => $page->getAutoId(), 'html' => $html, 'js' => $js, 'title' => $title, 'url' => $url,
                 'layoutClass'       => $layoutClass,
                 'menuEntryHashList' => $menuEntryHashList);
  }

  public function popinComponent() {
    $componentInfo = $this->_getViewInfo();
    $this->getRender()->getJs()->onloadJs('cm.views["' . $componentInfo['id'] . '"].popIn();');
  }

  /**
   * Add a reload to the response.
   */
  public function reloadPage() {
    $this->getRender()->getJs()->onloadJs('window.location.reload(true)');
  }

  /**
   * @param CM_Page_Abstract|string $page
   * @param array|null              $params
   * @param boolean|null            $forceReload
   *
   */
  public function redirect($page, array $params = null, $forceReload = null) {
    $forceReload = (boolean) $forceReload;
    $url = $this->getRender()->getUrlPage($page, $params);
    $this->redirectUrl($url, $forceReload);
  }

  /**
   * @param string       $url
   * @param boolean|null $forceReload
   */
  public function redirectUrl($url, $forceReload = null) {
    $url = (string) $url;
    $forceReload = (boolean) $forceReload;
    $js = 'cm.router.route(' . json_encode($url) . ', ' . json_encode($forceReload) . ');';
    $this->getRender()->getJs()->onloadPrepareJs($js);
  }

  /**
   * @param CM_Menu[]        $menuList
   * @param CM_Page_Abstract $page
   * @return string[]
   */
  private function _getMenuEntryHashList(array $menuList, CM_Page_Abstract $page) {
    $menuEntryHashList = array();
    foreach ($menuList as $menu) {
      if (is_array($menuEntries = $menu->findEntries($page))) {
        foreach ($menuEntries as $menuEntry) {
          $menuEntryHashList[] = $menuEntry->getHash();
          foreach ($menuEntry->getParents() as $parentEntry) {
            $menuEntryHashList[] = $parentEntry->getHash();
          }
        }
      }
    }
    return $menuEntryHashList;
  }
}
