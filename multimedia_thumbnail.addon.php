<?php
  /**
   * @file Multimedia_thumbnail.addon.php
   * @author Jin Hu, Baek(m@ake.kr, @mAKEkr)
   * @brief generate thumbnail to  multimedia in document.
   * @version 2.2.0
   ***/

  if ($called_position === 'before_module_proc')
  {
    // 게시물 insert 시에
    if ($this->act === 'procBoardInsertDocument')
    {
      include_once(_XE_PATH_ . 'addons/multimedia_thumbnail/multimedia_thumbnail.class.php');

      $oContext = Context::getInstance();
      $vars = Context::getRequestVars();
      $content = $vars->content;
      $MultimediaThumb = new MultimediaThumb($addon_info);

      if ($MultimediaThumb->checkDocumentThumbExsits($vars->document_srl, $content))
      {
        $multimedia_list = $MultimediaThumb->getMultimediaList($content);

        if ($MultimediaThumb->checkMultimediaThumbExists($content))
        {
          if (count($multimedia_list) !== 0)
          {
            $multimedia_thumb = $MultimediaThumb->getMultimediaThumb($content);
            $new_multimedia_thumb = $MultimediaThumb->getMultimediaFormat($multimedia_list['0']);

            if ($multimedia_thumb['0'] != $new_multimedia_thumb)
            {
              $result_content = $MultimediaThumb->removeMultimediaThumb($content);
              $result_content = $MultimediaThumb->makeMultimediaThumb($new_multimedia_thumb) . $result_content;
            }
          } else {
            $result_content = $MultimediaThumb->removeMultimediaThumb($content);
          }
        }
      } else {
        $multimedia_list = $MultimediaThumb->getMultimediaList($content);

        if (count($multimedia_list) !== 0)
        {
          $new_multimedia_thumb = $MmultimediaThumb->getMultimediaFormat($multimedia_list['0']);

          if ($multimedia_thumb['0'] != $new_multimedia_thumb)
          {
            $result_content = $MultimediaThumb->makeMultimediaThumb($new_multimedia_thumb) . $content;
          }
        }
      }

      $oContext->set('content', ($result_content === NULL) ? $vars->content : $result_content, TRUE);

      unset($oContext);
      unset($vars);
      unset($content);
    }
  } else if ($called_position === 'after_module_proc') {
    if ($this->act === 'dispBoardContent' || $this->act === 'dispBoardWrite')
    {
      include_once(_XE_PATH_ . 'addons/ultimedia_thumbnail/multimedia_thumbnail.class.php');

      $oDocument = Context::get('oDocument');

      $MultimediaThumb = new MultimediaThumb($addon_info);
      $oDocument->variables['content'] = ($this->act === 'dispBoardContent') ?
        $MultimediaThumb->hideMultimediaThumb($oDocument->variables['content']) :
        $MultimediaThumb->removeMultimediaThumb($oDocument->variables['content']);

      unset($oDocument);
    }
  }
