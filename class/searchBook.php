<?php
include 'Snoopy.class.php';

/**
 * 
 * @method getKey($url, $pattern)
 * @method getBook($url, $pattern)
 */
 /**
 * 도서관 관련 클래스 함수
 * @since 13.10.02
 * @최종수정일 13.10.08
 * -수정사항1 : 1.파일이름 , 클래스 이름 searchBook -> SearchBook 으로 변경
 *				2.getBook_Array_Encoding 함수 추가  (EUC-KR로된 URL 일때 사용)
 */	


class SearchBook{

	/**
	 * fsockopen 프록시
	 * @author ysm
	 * @since 13.10.02
	 * @param string $url : URL
	 */
	function getPageCode($url){
		$host = "";    //메인 주소 
		$urlTail = "";    //디렉토리 + 파라미터 값 
		$urlToken = array();    //분리된 문자열을 저장할 임시값
	
		$urlToken = explode("/",$url);

		//$host = $urlToken[0]."//".$urlToken[2];
		$host = $urlToken[2];
		
		$urlToken = array_slice($urlToken, 3);    //host 부분을 잘라낸다 . 
		$urlTail = "/".implode("/",$urlToken);

		if(empty($url)) return '';
		//fsockopen
		$fp = fsockopen ($host, 80, $errno, $errstr, 30);
		$body = ''; //html 내용 초기값
		if (!$fp) {
			//echo "{$errstr} ({$errno})<br>\n";
		} else {
			fputs($fp, "GET ".$urlTail." HTTP/1.1\r\n"); //파라미터가 들어감
			fputs($fp, "Host: ".$host."\r\n"); //정식 호스트 주소가 들어감
			fputs($fp, "Connection: Close\r\n\r\n");
			
			$header = "";
			while (!feof($fp)) {
			$out = fgets ($fp,512);
			if (trim($out) == "") {
			break;
			}
				$header .= $out;
			}
		
			while (!feof($fp)) {
				$out = fgets ($fp,512);
				$body .= $out;
			}
		
			$idx = strpos(strtolower($header), "transfer-encoding: chunked");
		
			if ($idx > -1) { // chunk 데이터가 포함된 경우
				$temp = "";
				$offset = 0;
				do {
					$idx1 = strpos($body, "\r\n", $offset);
					$chunkLength = hexdec(substr($body, $offset, $idx1 - $offset));
		
					if ($chunkLength == 0) {
						break;
					} else {
						$temp .= substr($body, $idx1+2, $chunkLength);
						$offset = $idx1 + $chunkLength + 4;
					}
				} while(true);
				$body = $temp;
			}
			fclose ($fp);
		}
		return html_entity_decode($body);
	}
	
	/**
	 * URL
	 * @param String $url : key
	 * @param String $pattern : key
	 */
	function getKey($url, $pattern){
		/* curl 삭제*/
		$html = '';
		$html = $this->getPageCode($url); //url 그래로 변수를 넘긴다.
		$html = $this->getUTF8Code($html);

		if(preg_match_all($pattern, $html, $regs)){
			$keyvalue = $regs[1][0];
			return $keyvalue;
		}
	}
	
	/**
	 * 검색시 여러개의 책이 나오는 url의 여러개의 Key를 뽑아내기 위해.
	 * Enter description here ...
	 * @param unknown_type $url
	 * @param unknown_type $pattern
	 */
	function getKey_Array($url, $pattern){
		/* curl 삭제*/
		$html = '';
		$html = $this->getPageCode($url); //url 그래로 변수를 넘긴다.
		$html = $this->getUTF8Code($html);
		
		if(preg_match_all($pattern, $html, $regs)){
			$keyvalue = $regs[1];
			return $keyvalue;
		}
	}
	
	/**
	 * URL로 페이지를 읽고, 값을 추출해내기 위한 기능.
	 * @param unknown_type $url :  URL(ISBN + KEY)
	 * @param unknown_type $pattern : .
	 */
	function getBook($url, $pattern){
		$snoopy = new Snoopy;
		$snoopy->fetch($url);
		$txt = $snoopy->results;
		$txt = $this->getUTF8Code($txt);
		
		if(preg_match_all($pattern, $txt, $result)){		
			$info = $result[1][0];
			return $info;
		}
	}
	/**
	 * URL로 텍스트형식으로 만들고, 값을 추출해내기 위한 기능.
	 * @param unknown_type $url :  URL(ISBN + KEY)
	 * @param unknown_type $pattern : .
	 */
	function getBook_Text($url, $pattern){
		$snoopy = new Snoopy;
		$snoopy->fetchtext($url);
		$txt = $snoopy->results;
		$txt = $this->getUTF8Code($txt);
		
		if(preg_match_all($pattern, $txt, $result)){
			$info = $result[1][0];
			return $info;
		}
	}
	/**
	 * URL로 텍스트형식으로 만들고, 값을 추출해내기 위한 기능.(EUC-KR로된 URL 일때 사용)
	 * @param unknown_type $url :  URL(ISBN + KEY)
	 * @param unknown_type $pattern : .
	 */	
	function getBook_Encoding($url, $pattern){
		$snoopy = new Snoopy;
		$snoopy->fetch($url);
		$txt = $snoopy->results;
		$txt = iconv('EUC-KR', 'UTF-8', $txt);
		$txt = $this->getUTF8Code($txt);
		
		if(preg_match_all($pattern, $txt, $result)){
			$info = $result[1][0];
			return $info;
		}
	}
	/**
	 * URL로 텍스트형식으로 만들고, 배열 값을 리턴하기 위한 기능.
	 * @param unknown_type $url :  URL(ISBN + KEY)
	 * @param unknown_type $pattern : .
	 */	
	function getBook_Array($url, $pattern){
		$snoopy = new Snoopy;
		$snoopy->fetch($url);
		$txt = $snoopy->results;
		$txt = $this->getUTF8Code($txt);
		
		if(preg_match_all($pattern, $txt, $result)){
			$info = $result[1];
			return $info;
		}
	}
	
	/**
	 * URL로 텍스트형식으로 만들고, 배열 값을 리턴하기 위한 기능.(EUC-KR로된 URL 일때 사용)
	 * @param unknown_type $url :  URL(ISBN + KEY)
	 * @param unknown_type $pattern : .
	 */	
	function getBook_Array_Encoding($url, $pattern){
		$snoopy = new Snoopy;
		$snoopy->fetch($url);
		$txt = $snoopy->results;
		$txt = iconv('EUC-KR', 'UTF-8', $txt);
		$txt = $this->getUTF8Code($txt);
		
		if(preg_match_all($pattern, $txt, $result)){
			$info = $result[1];
			return $info;
		}
	}

	/**
	 * html entity set -> utf-8로 변환 
	 * @author syr
	 * @since 13.10.04
	 * @param string $html : 받아온 htmlcode
	 */
	function getUTF8Code($html){
		return preg_replace_callback('/&#[0-9]+;/', array($this, 'unichar'), $html); //&#으로 시작하는 문자열만 찾아서 치환한다.
	}

	/**
	 * preg_replace_callback의 callback 함수
	 * @author syr
	 * @since 13.10.04
	 * @param string $match : utf-8로 변경할 문자열의 array
	 */
	function unichar($match) {		
		return html_entity_decode($match[0], ENT_QUOTES, 'UTF-8');
	}
}
?>