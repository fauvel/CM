<?php

class CM_Usertext_Filter_Emoticon extends CM_Usertext_Filter_Abstract {

    const PATTERN_FALSE_SMILEY = '(\p{N}\s*+%|(\(|\p{N}|\p{L}\p{M}*+)[38BO])';

    /** @var int|null $_fixedHeight */
    private $_fixedHeight = null;

    /**
     * @param int|null $fixedHeight
     */
    function __construct($fixedHeight = null) {
        if (null !== $fixedHeight) {
            $this->_fixedHeight = (int) $fixedHeight;
        }
    }

    public function getCacheKey() {
        return parent::getCacheKey() + array('_fixedHeight' => $this->_fixedHeight);
    }

    public function transform($text, CM_Frontend_Render $render) {
        $text = (string) $text;
        $emoticons = $this->_getEmoticonData($render);
        $text = $this->_escapeFalseSmileys($text);
        $text = str_replace($emoticons['codes'], $emoticons['htmls'], $text);
        $text = $this->_unescapeFalseSmileys($text);
        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    protected function _escapeFalseSmileys($text) {
        return preg_replace('#' . self::PATTERN_FALSE_SMILEY . '\)#u', '$1' . html_entity_decode('&#xE000;', ENT_NOQUOTES, 'UTF-8'), $text);
    }

    /**
     * @param string $text
     * @return string
     */
    protected function _unescapeFalseSmileys($text) {
        return preg_replace('#' . self::PATTERN_FALSE_SMILEY . '\x{E000}#u', '$1)', $text);
    }

    /**
     * @param CM_Frontend_Render $render
     * @return array
     */
    private function _getEmoticonData(CM_Frontend_Render $render) {
        $cacheKey = CM_CacheConst::Usertext_Filter_EmoticonList . '_fixedHeight:' . (string) $this->_fixedHeight;
        $cache = CM_Cache_Local::getInstance();
        if (($emoticons = $cache->get($cacheKey)) === false) {
            $emoticons = array('codes' => array(), 'htmls' => array());
            $fixedHeight = '';
            if (null !== $this->_fixedHeight) {
                $fixedHeight = ' height="' . $this->_fixedHeight . '"';
            }
            /** @var CM_Emoticon $emoticon */
            foreach (new CM_Paging_Emoticon_All() as $emoticon) {
                foreach ($emoticon->getCodes() as $code) {
                    $emoticons['codes'][] = $code;
                    $emoticons['htmls'][] =
                        '<img src="' . $render->getUrlResource('layout', 'img/emoticon/' . $emoticon->getFileName()) . '" class="emoticon emoticon-' .
                        $emoticon->getName() . '" title="' . $emoticon->getDefaultCode() . '"' . $fixedHeight . ' />';
                }
            }
            $cache->set($cacheKey, $emoticons);
        }
        return $emoticons;
    }
}
