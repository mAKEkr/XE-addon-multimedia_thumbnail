<?php
	class MultimediaThumb{
		var $options;

		function __construct($options){
			if($options->mt_thumbnailres_youtube	   === NULL) $options->mt_thumbnailres_youtube     = 'hqdefault';
			if($options->mt_thumbnailres_soundcloud  === NULL) $options->mt_thumbnailres_soundcloud  = 't300x300';
			if($options->mt_thumbnailres_vimeo		   === NULL) $options->mt_thumbnailres_vimeo       = 'thumbnail_medium';
			if($options->mt_thumbnailres_dailymotion === NULL) $options->mt_thumbnailres_dailymotion = 'thumbnail_240_url';

			$this->options = $options;
      // Temporary builded regular expression. maybe fixed some reason.
      $this->validateRegExr = '/<img.?(?:class\=\"(xe\-MultimediaThumb)\")?.src=["\']?([^>"\']+)["\'].*?(?:rel|alt)=["\']?([^>"\']+)["\'].*?[^>]>/im';
		}

		function checkDocumentThumbExsits($document_srl, $content) {
			$oContext = Context::getInstance();
			$oFileModel = getModel('file');

			$attachment_list = $oFileModel->getFiles($document_srl, array(), 'file_srl', true);

			if(count($attachment_list) != '0'){
				$allow_attachment_extension = Array('jpg', 'jpeg', 'gif', 'png');

				foreach($attachment_list as $val){
					if( ! in_array(strtolower(pathinfo($val->uploaded_filename, PATHINFO_EXTENSION)), $allow_attachment_extension)) continue;
					if( ! file_exists($val->uploaded_filename)) continue;
					else
						$return_value = true;
					break;
				}
			} else {
				preg_match_all($this->validateRegExr, $content, $matches);

				foreach($matches as $key => $val){
					if ($val['0'] === null) continue;
					else
						$return_value = true;
				}
			}

			unset($matches);
			return ($return_value === NULL) ? false : $return_value;
		}

		function checkMultimediaThumbExists($content) {
			/*
				검사방법 - 섬네일 애드온으로 생성되었는지 확인.
        1. 구버전을 체크하기 위하여 img 태그를 전체 긁음
        2. class="xe-MultimediaThumb" / alt="(value)" / rel="(value)" 가 있는지 확인
        3. class에 xe-MultimediaThumb가 존재한다면 현재 최신코드, 2.2.x 이상대에서 제작된 섬네일
        4. rel에 multimedia format에 맞는 code가 들어가있는 경우 구버전대(1.x대, 2.0.x에서 제작된 섬네일)
        5. alt에 multimedia format에 맞는 code가 들어가있는 경우 신버전대(2.1.x대에서 제작된 섬네일)
			*/
			preg_match_all($this->validateRegExr, $content, $matches);

			$return_value = '';

			foreach($matches['2'] as $key => $val){
				$return_value .= $val;

				if ($val == null) continue;
				else {
					$return_value = $this->checkMultimediaFormat($val);
					if($return_value === true) break;
				}
			}

			unset($matches);
			return ($return_value === NULL) ? false : $return_value;
		}

		function getMultimediaThumb($content) {
			preg_match_all($this->validateRegExr, $content, $matches);

			$return_value = array();

			foreach($matches['2'] as $key => $val){
				if($val === NULL) continue;
				else {
					array_push($return_value, $val); // format
					array_push($return_value, $matches['1'][$key]); // url
					break;
				}
			}

			if($return_value['0'] == NULL) $return_value = false;

			return $return_value;
		}

		function checkMultimediaFormat($format) {
			list($service_name, $service_id) = explode(':', $format);

			switch($service_name){
				case 'youtube':
					preg_match('/[(A-Za-z0-9-_)+]/i', $format, $result);
				break;

				case 'vimeo':
					preg_match('/[(0-9)+]/i', $format, $result);
				break;

				case 'soundcloud':
					preg_match('/[(0-9)+]/i', $format, $result);
				break;

				case 'daum':
					preg_match('/[(A-Za-z0-9)+]/i', $format, $result);
				break;

				case 'naver':
					preg_match('/((?:[a-zA-Z0-9_-]+)\|(?:[a-zA-Z0-9_-]+))/i', $format, $result);
				break;

				case 'pandora':
					preg_match('/((?:[a-zA-Z0-9_-]+)\|(?:[0-9]+))/i', $format, $result);
				break;

				case 'dailymotion':
					preg_match('/[(A-Za-z0-9_)+]/i', $format, $result);
				break;

				default:
				break;
			}

			if($result['0'] == $format){
				$return_value = true;
			} else {
				$return_value = false;
			}

			unset($result);
			return $return_value;
		}

		function getMultimediaList($content) {
			preg_match_all('/<(?=embed|iframe)[^>]+src=["\']?([^>"\']+)["\'].*?[^>]>/i', $content, $matches);

			return $matches['1'];
		}

		function getMultimediaFormat($url) {
			//youtube
			preg_match('/youtube(?:|-nocookie).com\/(?:(?:v|embed)\/)?([a-zA-Z0-9-_]+)/i', $url, $youtube);
			//vimeo
			preg_match('/player.vimeo.com\/video\/?([0-9]+)/i', $url, $vimeo);
			//soundcloud
			preg_match('/api.soundcloud.com\/tracks\/?([0-9]+)/i', $url, $soundcloud);
			//daum
			preg_match('/videofarm.daum.net\/controller\/video\/viewer\/Video.html\?vid=([0-9a-zA-Z]+)/i', $url, $daum);
			//naver
			preg_match('/serviceapi.nmv.naver.com\/flash\/(?:convertIframeTag.nhn|NFPlayer.swf)\?vid=([(A-Za-z0-9)]+)*.?&outKey=([(A-Za-z0-9)]+)*.?/i', $url, $naver);
			//pandora
			preg_match('/(?:flvr|channel).pandora.tv\/(?:flv2pan|php)\/(?:embed.fr1.ptv\?|flvmovie.dll\/).*?userid=([(a-zA-Z0-9)]+).*?\&(?:|amp;)prgid=([(0-9)]+)/i', $url, $pandora);
			//dailymotion
			preg_match('/dailymotion.com\/embed\/video\/([a-zA-Z0-9_]+)/i', $url, $dailymotion);

			if($youtube['0']	   !== NULL) $return_value = 'youtube:'      . $youtube['1'];
			if($vimeo['0']       !== NULL) $return_value = 'vimeo:'        . $vimeo['1'];
			if($soundcloud['0']	 !== NULL) $return_value = 'soundcloud:'   . $soundcloud['1'];
			if($daum['0']		     !== NULL) $return_value = 'daum:'         . $daum['1'];
			if($naver['0']       !== NULL) $return_value = 'naver:'        . $naver['1']      . '|' . $naver['2'];
			if($pandora['0']	   !== NULL) $return_value = 'pandora:'      . $pandora['1']    . '|' . $pandora['2'];
			if($dailymotion['0'] !== NULL) $return_value = 'dailymotion:'  . $dailymotion['1'];

			unset($youtube);
			unset($vimeo);
			unset($soundcloud);
			unset($daum);
			unset($pandora);
			unset($dailymotion);

			return ($return_value === NULL) ? false : $return_value;
		}

		function makeMultimediaThumb($format) {
			list($service_name, $service_id) = explode(':', $format);
			switch($service_name){
				case 'youtube':
				 	if(($this->options->mt_thumbnailres_youtube == 'maxresdefault') && ($this->options->mt_thumbnailres_youtube_extend == 'enable')){
				 		$thumbnail_url = array(
				 			'http://i1.ytimg.com/vi/' . $service_id . '/' . $this->options->mt_thumbnailres_youtube . '.jpg',
				 			'http://i1.ytimg.com/vi/' . $service_id . '/hqdefault.jpg'
				 		);
				 	} else {
						$thumbnail_url = 'http://i1.ytimg.com/vi/' . $service_id . '/' . $this->options->mt_thumbnailres_youtube . '.jpg';
				 	}
				break;

				case 'vimeo':
					$json = json_decode(FileHandler::getRemoteResource('http://vimeo.com/api/v2/video/' . $service_id . '.json', null, null, 'GET', 'application/json'));
					$thumbnail_url = $json['0']->{$this->options->mt_thumbnailres_vimeo};
				break;

				case 'soundcloud':
					$json = json_decode(FileHandler::getRemoteResource('http://api.soundcloud.com/tracks/' . $service_id . '.json?client_id=2f426cafc5cec317628b1db5224fc7a1', null, null, 'GET', 'application/json'));
					$thumbnail_url = str_replace('large', $this->options->mt_thumbnailres_soundcloud, $json->artwork_url);
					$thumbnail_url = substr($thumbnail_url, 0, strpos($thumbnail_url, '?'));
				break;

				case 'daum':
					$xml = simplexml_load_string(FileHandler::getRemoteResource('http://tvpot.daum.net/clip/ClipInfoXml.do?vid=' . $service_id, null, null, 'GET', 'text/xml'), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
					$thumbnail_url = $xml->THUMB_URL;
					unset($xml);
				break;

				case 'naver':
					list($service_id, $service_key) = explode('|', $service_id);
					$xml = simplexml_load_string(FileHandler::getRemoteResource('http://serviceapi.nmv.naver.com/flash/videoInfo.nhn?vid=' . $service_id . '&outKey=' . $service_key, null, null, 'GET', 'text/xml'), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
					$thumbnail_url = $xml->CoverImage;
					unset($xml);
				break;

				case 'pandora':
					list($service_userid, $service_id) = explode('|', $service_id);
					$json = json_decode(FileHandler::getRemoteResource('http://flvr.pandora.tv/flv2pan/embed.dll/info?url=&prgid=' . $service_id . '&userid=' . $service_userid, null, null, 'GET', 'text/html'));
					$thumbnail_url = $json->image;
				break;

				case 'dailymotion':
					$json = json_decode(FileHandler::getRemoteResource('https://api.dailymotion.com/video/' . $service_id . '?fields=' . $this->options->mt_thumbnailres_dailymotion, null, null, 'GET', 'application/json'));
					$thumbnail_url = $json->{$this->options->mt_thumbnailres_dailymotion};
				break;

				default:
					$thumbnail_url = false;
				break;
			}

			$json = '';
			unset($json);
			unset($service_name);
			unset($service_id);
			unset($service_userid);

			if(is_array($thumbnail_url)) {
				$return_string = '';

				foreach($thumbnail_url as $val){
					$return_string .= '<img class="xe-MultimediaThumb" src="' . $val. '" alt="' . $format . '" />';
				}

				return ($this->checkXEVersion()) '<!-- ' . $return_string . ' -->' ? : $return_string . "\n";
				unset($return_string);
			} else {
				$thumbnail_url = ($thumbnail_url !== false) ? '<img src="' . $thumbnail_url . '" alt="' . $format . '" />' : false;
				if ($thumbnail_url !== false) {
					$thumbnail_url = $thumbnail_url . "\n";
				}

				return $thumbnail_url;
			}

			unset($thumbnail_url);
			unset($format);
		}

		function hideMultimediaThumb($content) {

		}

		function filterMultimediaThumb($type = 'hide', $content) {
			if ($type === 'hide')
      {
        $content = preg_replace($this->validateRegExr, '<!-- $0 -->', $content);
			} else { // $type === 'remove'
        $content = preg_replace($this->validateRegExr, '', $content);
      }

      return $content;
      unset($content);
		}

		/* Additional Scripts(common function or methods)
		*/

		function getAddonConfig() {
			return $this->options;
		}

		function checkXEVersion() {
			list($major, $minor, $patch) = explode('.', __XE_VERSION__);
			if(($major >= 1) && ($minor >= 8))
			{
				return true;
			} else {
				return false;
			}
		}
	}