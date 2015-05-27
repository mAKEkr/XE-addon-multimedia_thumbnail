<?php
	/**
	 * @file Multimedia_thumbnail.addon.php
	 * @author Jin Hu, Baek(m@ake.kr, @mAKEkr)
	 * @brief if doesn't thumbnail exists, making thumbnail to Multimedia in extra variables.
	 * @version 2.0.0
	 ***/

	if ($called_position == 'before_module_proc') {
		include_once(_XE_PATH_ . 'addons/multimedia_thumbnail/multimedia_thumbnail.class.php');

		if ($this->act == 'procBoardInsertDocument') {
			$oContext = Context::getInstance();
			$vars = Context::getRequestVars();
			$content = $vars->content;

			if ($addon_info->mt_enable_extravar == 'enable') {
				$oDocumentModel = getModel('document');
				$oModuleModel = getModel('module');

				$query_result = $oModuleModel->getModuleInfoByMid(Context::get('mid'));
				$extra_keys = $oDocumentModel->getExtraKeys($query_result->module_srl);

				foreach ($extra_keys as $idx => $val) {
					if ($val->eid == $addon_info->mt_extravar_name) {
						$target = $vars->{'extra_vars' . $val->idx};
					}
				}
			}
			$target = ($target === NULL) ? $vars->content : $target;

			$MultimediaThumb = new MultimediaThumb($addon_info);

			if ($MultimediaThumb->checkDocumentThumbExsits($vars->document_srl, $content)) {
				$multimedia_list = $MultimediaThumb->getMultimediaList($target);

				if ( $MultimediaThumb->checkMultimediaThumbExists($content) ) {
					if(count($multimedia_list) != 0){
						$multimedia_thumb = $MultimediaThumb->getMultimediaThumb($content);
						$new_multimedia_thumb = $MultimediaThumb->getMultimediaFormat($multimedia_list['0']);

						if($multimedia_thumb['0'] != $new_multimedia_thumb){
							$result_content = $MultimediaThumb->removeMultimediaThumb($content);
							$result_content = $MultimediaThumb->makeMultimediaThumb($new_multimedia_thumb) . $result_content;
						}
					} else if (count($multimedia_list) == 0) {
						$result_content = $MultimediaThumb->removeMultimediaThumb($content);
					} else {
					}
				} else {
				}
			} else {
				$multimedia_list = $MultimediaThumb->getMultimediaList($target);

				if(count($multimedia_list) != 0){
					$new_multimedia_thumb = $MultimediaThumb->getMultimediaFormat($multimedia_list['0']);

					if($multimedia_thumb['0'] != $new_multimedia_thumb){
						$result_content = $MultimediaThumb->makeMultimediaThumb($new_multimedia_thumb) . $content;
					}
				} else {
				}
			}

			$result_content = ($result_content === NULL) ? $vars->content : $result_content;

			$oContext->set('content', $result_content, TRUE);
		}
	} else if ($called_position == 'after_module_proc') {
		if($this->act == 'dispBoardContent'){
			$oContext  = Context::getInstance();
			$oDocument = Context::get('oDocument');

			$MultimediaThumb = new MultimediaThumb($addon_info);
			if( !$MultimediaThumb->checkXEVersion() ){
				$oDocument->variables['content'] = $MultimediaThumb->removeMultimediaThumb($oDocument->variables['content']);
			}
		}
	}