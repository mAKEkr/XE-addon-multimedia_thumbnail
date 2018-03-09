<?php
  class MultimediaThumb{
    var $options;
    var $variables;
    var $document;

    function __construct ($options, $content = NULL, $document_srl = 0) {
      if($options->mt_thumbnailres_youtube     === NULL) $options->mt_thumbnailres_youtube     = 'hqdefault';
      if($options->mt_thumbnailres_soundcloud  === NULL) $options->mt_thumbnailres_soundcloud  = 't300x300';
      if($options->mt_thumbnailres_vimeo       === NULL) $options->mt_thumbnailres_vimeo       = 'thumbnail_medium';
      if($options->mt_thumbnailres_dailymotion === NULL) $options->mt_thumbnailres_dailymotion = 'thumbnail_240_url';

      $this->options = $options;

      // Compatibillity in ver 3.x
      // 하위호환성 확보를 위하여 Object가 아닌 Array로 제작.
      $this->variables = Array(
        'document_srl' => $document_srl
      );
      $this->document = Array(
        'content' => $content,
        'multimediaList' => Array(),
        'imageList' => Array(),
        'isMakeThumbnail' => false, // 애드온을 통하여 섬네일을 생성한 경우
        'isHaveThumbnail' => false // 첨부파일로 인하여 섬네일을 갖고있을 경우
      );

      $this->formatCheckRegExr = '/([a-z].+)\:([^/][a-zA-Z0-9\-\_\|]+)/i';

      if ($this->document['content'] !== NULL) {
        $this->proccessDocument();
      }
    }

    /*
      1. 게시물에 파일이 존재하는지 확인
      2. 게시물에 이미지가 존재하는지 확인
      3. 게시물에 멀티미디어가 존재하는지 확인
      4. 섬네일 생성
    */
    function proccessDocument () {
      if ($this->document['content'] !== NULL) {
        $this->document['imageList'] = $this->checkDocumentImages($this->document['content']);
        $this->checkDocumentThumbExsits();
        $this->checkDocumentEmbed();

        if ($this->document['isMakeThumbnail'] === false &&
            $this->document['isHaveThumbnail'] === false &&
            count($this->document['multimediaList']) > 0) {
          $this->document['content'] = $this->makeMultimediaThumb($this->document['multimediaList']['0']) . $this->document['content'];
        }
      }
    }

    function checkDocumentImages ($content) {
      $dom = new DOMDocument;
      $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

      $imageList = Array();
      foreach ($dom->getElementsByTagName('img') as $element) {
        array_push($imageList, Array(
          'node' => substr($dom->saveHTML($element), 0, -1) . ' />',
          'class' => $element->getAttribute('class'),
          'src' => $element->getAttribute('src'),
          'alt' => $element->getAttribute('alt'), // compatibility in 2.x
          'rel' => $element->getAttribute('rel') // compatibility in 1.x and 2.0, 2.1
        ));
      }


      return $imageList;
    }

    function checkDocumentThumbExsits () {
      $document_srl = $this->variables['document_srl'];
      $oContext = Context::getInstance();
      $oFileModel = getModel('file');

      $attachment_list = $oFileModel->getFiles($document_srl, array(), 'file_srl', true);

      if (count($attachment_list) > 0) { // 첨부파일이 존재할경우
        // 체크할 첨부파일의 확장자가 아래와 같을경우
        $allow_attachment_extension = Array('jpg', 'jpeg', 'gif', 'png');

        foreach ($attachment_list as $val) {
          if (in_array(strtolower(pathinfo($val->uploaded_filename, PATHINFO_EXTENSION)), $allow_attachment_extension) === false || file_exists($val->uploaded_filename) === false) {
            continue;
          } else {
            $this->document['isHaveThumbnail'] = true;
            break;
          }
        }
      } else { // 첨부파일이 존재하지 않을 경우
        // 이미지가 존재하는지 체크 인데 멀티미디어 섬네일이 존재하는지 체크
        foreach ($this->document['imageList'] as $element) {
          if ($element['class'] === 'xe-MultimediaThumb' ||
              strpos($element['alt'], ':') > 0 ||
              strpos($element['rel'], ':') > 0) {
            $this->document['isMakeThumbnail'] = true;
            break;
          }
        }
      }

      return ($this->document['isMakeThumbnail'] !== false && $this->document['isHaveThumbnail'] !== false) ? true : false;
    }

    function checkDocumentEmbed () {
      preg_match_all('/<(?=embed|iframe)[^>]+src=["\']?([^>"\']+)["\'].*?[^>]>/i', $this->document['content'], $matches);

      foreach ($matches['1'] as $embed) {
        array_push($this->document['multimediaList'], $this->getMultimediaFormat($embed));
      }

      return $this->document['multimediaList'];
    }

    function checkMultimediaFormat ($format) {
      list($service_name, $service_id) = explode(':', $format);

      /*
        해당부분 정규식 한개로 대응할 수 있도록 변경할것
      */
      switch($service_name){
        case 'youtube':
          preg_match('/[(A-Za-z0-9-_)+]/i', $format, $result);
        break;

        case 'vimeo':
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

    function getMultimediaFormat ($url) {
      //youtube
      preg_match('/youtube(?:|-nocookie).com\/(?:(?:v|embed)\/)?([a-zA-Z0-9-_]+)/i', $url, $youtube);
      //vimeo
      preg_match('/player.vimeo.com\/video\/?([0-9]+)/i', $url, $vimeo);
      //daum
      preg_match('/videofarm.daum.net\/controller\/video\/viewer\/Video.html\?vid=([0-9a-zA-Z]+)/i', $url, $daum);
      //naver
      preg_match('/serviceapi.nmv.naver.com\/flash\/(?:convertIframeTag.nhn|NFPlayer.swf)\?vid=([(A-Za-z0-9)]+)*.?&outKey=([(A-Za-z0-9)]+)*.?/i', $url, $naver);
      //pandora
      preg_match('/(?:flvr|channel).pandora.tv\/(?:flv2pan|php)\/(?:embed.fr1.ptv\?|flvmovie.dll\/).*?userid=([(a-zA-Z0-9)]+).*?\&(?:|amp;)prgid=([(0-9)]+)/i', $url, $pandora);
      //dailymotion
      preg_match('/dailymotion.com\/embed\/video\/([a-zA-Z0-9_]+)/i', $url, $dailymotion);

      if($youtube['0']     !== NULL) $return_value = 'youtube:'      . $youtube['1'];
      if($vimeo['0']       !== NULL) $return_value = 'vimeo:'        . $vimeo['1'];
      if($daum['0']        !== NULL) $return_value = 'daum:'         . $daum['1'];
      if($naver['0']       !== NULL) $return_value = 'naver:'        . $naver['1']      . '|' . $naver['2'];
      if($pandora['0']     !== NULL) $return_value = 'pandora:'      . $pandora['1']    . '|' . $pandora['2'];
      if($dailymotion['0'] !== NULL) $return_value = 'dailymotion:'  . $dailymotion['1'];

      unset($youtube);
      unset($vimeo);
      unset($soundcloud);
      unset($daum);
      unset($pandora);
      unset($dailymotion);


      return ($return_value === NULL) ? false : $return_value;
    }

    function makeMultimediaThumb ($format) {
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
          $json = json_decode(FileHandler::getRemoteResource('http://vimeo.com/api/v2/video/' . $service_id . '.json', NULL, NULL, 'GET', 'application/json'));
          $thumbnail_url = $json['0']->{$this->options->mt_thumbnailres_vimeo};
        break;

        case 'soundcloud':
          $json = json_decode(FileHandler::getRemoteResource('http://api.soundcloud.com/tracks/' . $service_id . '.json?client_id=2f426cafc5cec317628b1db5224fc7a1', NULL, NULL, 'GET', 'application/json'));
          $thumbnail_url = str_replace('large', $this->options->mt_thumbnailres_soundcloud, $json->artwork_url);
          $thumbnail_url = substr($thumbnail_url, 0, strpos($thumbnail_url, '?'));
        break;

        case 'daum':
          $xml = simplexml_load_string(FileHandler::getRemoteResource('http://tvpot.daum.net/clip/ClipInfoXml.do?vid=' . $service_id, NULL, NULL, 'GET', 'text/xml'), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
          $thumbnail_url = $xml->THUMB_URL;
          unset($xml);
        break;

        case 'naver':
          list($service_id, $service_key) = explode('|', $service_id);
          $xml = simplexml_load_string(FileHandler::getRemoteResource('http://serviceapi.nmv.naver.com/flash/videoInfo.nhn?vid=' . $service_id . '&outKey=' . $service_key, NULL, NULL, 'GET', 'text/xml'), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
          $thumbnail_url = $xml->CoverImage;
          unset($xml);
        break;

        case 'pandora':
          list($service_userid, $service_id) = explode('|', $service_id);
          $json = json_decode(FileHandler::getRemoteResource('http://flvr.pandora.tv/flv2pan/embed.dll/info?url=&prgid=' . $service_id . '&userid=' . $service_userid, NULL, NULL, 'GET', 'text/html'));
          $thumbnail_url = $json->image;
        break;

        case 'dailymotion':
          $json = json_decode(FileHandler::getRemoteResource('https://api.dailymotion.com/video/' . $service_id . '?fields=' . $this->options->mt_thumbnailres_dailymotion, NULL, NULL, 'GET', 'application/json'));
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

      $return_string = '';
      if (is_array($thumbnail_url)) {
        foreach($thumbnail_url as $val){
          $return_string .= '<img class="xe-MultimediaThumb" src="' . $val. '" alt="' . $format . '" />';
        }
      } else {
        $return_string = ($thumbnail_url !== false) ? '<img class="xe-MultimediaThumb" src="' . $thumbnail_url . '" alt="' . $format . '" />' . "\n" : false;
      }

      return $return_string;
      unset($return_string);
      unset($thumbnail_url);
      unset($format);
    }

    public function getResult() {
      return $this->document['content'];
    }

    function filterMultimediaThumb ($type = 'hide', $content = NULL) {
      if ($content !== NULL) {
        $images = $this->checkDocumentImages($content);
        $MultimediaThumbList = Array();

        foreach ($images as $element) {
          if ($element['class'] === 'xe-MultimediaThumb' ||
              strpos($element['alt'], ':') > 0 ||
              strpos($element['rel'], ':') > 0) {

            if ($element['class'] === 'xe-MultimediaThumb') {
              array_push($MultimediaThumbList, $element);
            } else if (strpos($element['rel'], ':') > 0) {
              if (preg_match($this->formatCheckRegExr, $element['rel'])) {
                array_push($MultimediaThumbList, $element);
              }
            } else if (strpos($element['alt'], ':') > 0) {
              if (preg_match($this->formatCheckRegExr, $element['alt'])) {
                array_push($MultimediaThumbList, $element);
              }
            }
          }
        }

        if ($type === 'hide')
        {
          foreach ($MultimediaThumbList as $element) {
            $content = str_replace($element['node'], '<!--' . $element['node'] . '-->', $content);
          }
        } else { // $type === 'remove'
          foreach ($MultimediaThumbList as $element) {
            $content = str_replace($element['node'], '', $content);
          }
        }

        unset($images);
        unset($MultimediaThumbList);
      }

      return $content;
      unset($content);
    }
  }