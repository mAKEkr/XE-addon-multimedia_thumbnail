<?php
	/**
	 * @file Multimedia_thumbnail.addon.php
	 * @author Jin Hu, Baek(m@ake.kr, @mAKEkr)
	 * @brief generate thumbnail to  multimedia in document.
	 * @version 2.1.0
	 ***/

	if ($called_position == 'before_module_proc')
	{
		if ($this->act == 'procBoardInsertDocument')
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
					if (count($multimedia_list) != 0)
					{
						$multimedia_thumb = $MultimediaThumb->getMultimediaThumb($content);
						$new_multimedia_thumb = $MultimediaThumb->getMultimediaFormat($multimedia_list['0']);

						if ($multimedia_thumb['0'] != $new_multimedia_thumb)
						{
							$result_content = $MultimediaThumb->removeMultimediaThumb($content);
							$result_content = $MultimediaThumb->makeMultimediaThumb($new_multimedia_thumb) . $result_content;
						}
					} else if (count($multimedia_list) == 0) {
						$result_content = $MultimediaThumb->removeMultimediaThumb($content);
					}
				}
			} else {
				$multimedia_list = $MultimediaThumb->getMultimediaList($content);

				if (count($multimedia_list) != 0)
				{
					$new_multimedia_thumb = $MultimediaThumb->getMultimediaFormat($multimedia_list['0']);

					if ($multimedia_thumb['0'] != $new_multimedia_thumb)
					{
						$result_content = $MultimediaThumb->makeMultimediaThumb($new_multimedia_thumb) . $content;
					}
				}
			}

			$result_content = ($result_content === NULL) ? $vars->content : $result_content;

			$oContext->set('content', $result_content, TRUE);
		}
	}
	else if ($called_position === 'after_module_proc')
	{
		if ($this->act === 'dispBoardContent' || $this->act === 'dispBoardWrite')
		{
			include_once(_XE_PATH_ . 'addons/multimedia_thumbnail/multimedia_thumbnail.class.php');

			$oContext  = Context::getInstance();
			$oDocument = Context::get('oDocument');

			$MultimediaThumb = new MultimediaThumb($addon_info);

			if ($this->act === 'dispBoardContent') // 게시물 열람시
			{
				$oDocument->variables['content'] = $MultimediaThumb->hideMultimediaThumb($oDocument->variables['content']);
			} else if ($this->act === 'dispBoardWrite') { // 게시물 수정시
				$oDocument->variables['content'] = $MultimediaThumb->removeMultimediaThumb($oDocument->variables['content']);
			}

			unset($oContext);
			unset($oDocument);
		}
	}