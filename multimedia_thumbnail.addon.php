<?php
  /**
   * @file Multimedia_thumbnail.addon.php
   * @author Jin Hu, Baek(m@ake.kr, @mAKEkr)
   * @brief generate thumbnail to  multimedia in document.
   * @version 3.0.0
   ***/

  if ($called_position === 'before_module_proc')
  {
    // 게시물 insert 시에
    if ($this->act === 'procBoardInsertDocument')
    {
      include_once(_XE_PATH_ . 'addons/multimedia_thumbnail/multimedia_thumbnail.class.php');

      $oContext = Context::getInstance();
      $vars = Context::getRequestVars();
      $MultimediaThumb = new MultimediaThumb($addon_info, $vars->content, $vars->document_srl);

      $oContext->set('content', $MultimediaThumb->getResult(), TRUE);

      unset($oContext);
      unset($vars);
      unset($content);
    }
  } else if ($called_position === 'after_module_proc') {
    if (($this->act === 'dispBoardContent' || $this->act === 'dispBoardWrite') &&
        (int)Context::get('document_srl') !== 0)
    {
      include_once(_XE_PATH_ . 'addons/multimedia_thumbnail/multimedia_thumbnail.class.php');

      $oDocument = Context::get('oDocument');
      $MultimediaThumb = new MultimediaThumb($addon_info);

      $oDocument->variables['content'] = ($this->act === 'dispBoardContent') ?
        $MultimediaThumb->filterMultimediaThumb('hide', $oDocument->variables['content']) :
        $MultimediaThumb->filterMultimediaThumb('remove', $oDocument->variables['content']);

      // unset($oDocument);
    }
  }
