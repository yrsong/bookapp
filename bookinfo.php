<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
</html>
<?php
/**
 * 도서관 관련
 * @author 송영록, 황경호
 * @since 13.10.02
 * @최종수정일 13.10.08
 * -수정사항1 : 1.결과값 리턴시 태그 제거 /2.Location부분 정규식 변경. / 3.searchBook.php html entity함수 추가. / 4.책정보 배열로 리턴.
 * -수정사항2 : 1.isbn 10자리or13자리 입력제한 / 2.비치중|대출중 리턴값 true|false로 변경 / 3.각 구마다 전체 도서관 검색가능하도록 추가 
 *           4.searchBook의 unichar함수의 mb_convert_encoding => html_entity_decode로 변경.(서버단에서 돌아가지 않는 이유때문)
 *           5.config파일 추가. (도서관 상세코드)
 */

//header("Content-Type: application/json");

//include
include_once 'class/searchBook.php';
include_once 'config.php';

//정보
$isbn = isset($_GET['isbn']) && is_numeric($_GET['isbn']) ? $_GET['isbn'] : exit('{"msg":"empty isbn"}');
$libcode = isset($_GET['libcode']) && !empty($_GET['libcode']) ? $_GET['libcode'] : exit('{"msg":"empty libcode"}');
$isbn = (strlen($isbn)=="10" || strlen($isbn) =="13") ? $isbn : exit('{"msg":"isbn is not 10 or 13"}'); // isbn값 10자리 or 13자리 입력.

//초기값 (책제목, 대출여부, 소장위치)
$title = ''; 
$lib_array = array();

//오브젝트 생성
$obj = new searchBook();

//강북문화정보센터
if($libcode == "0007"){
	$locode = 01;
	//config에 등록.
	if($locode == "01"){$lib = "MA";} //강북
	else if($locode == "02"){$lib = "MB";} //청소년
	else if($locode == "03"){$lib = "MC";} //솔샘
	else if($locode == "04"){$lib = "MD";} //송중
	else if($locode == "05"){$lib = "ME";} //수유
	else if($locode == "06"){$lib = "MG";} //미아
	else if($locode == "07"){$lib = "NA";} //삼양동
	else if($locode == "08"){$lib = "NB";} //미아동
	else if($locode == "09"){$lib = "NC";} //송중동
	else if($locode == "10"){$lib = "NE";} //삼각산동
	else if($locode == "11"){$lib = "NF";} //번1동
	else if($locode == "12"){$lib = "NG";} //번2동
	else if($locode == "13"){$lib = "NH";} //번3동
	else if($locode == "14"){$lib = "NI";} //수유1동
	else if($locode == "15"){$lib = "NL";} //우이동
	else if($locode == "16"){$lib = "NM";} //인수동 분소
	
	//키값 가져오기 위한 url (검색페이지 url)
	$url="http://www.gangbuklib.seoul.kr/gangbuk/01.search/list.asp?a_lib=$lib&a_v=f&m=0101&a_qf=I&a_q=$isbn&x=33&y=5&a_bf=T&a_dr=&a_dt=&a_ft=&a_mt=&a_ut=&a_lt=&a_pyFrom=&a_pyTo=&a_sort=A&a_vp=10";
	$pattern = "/a_key=(.*?)\"\>/is";
	$key = $obj->getKey($url, $pattern); //책의 고유 키값
	
	//책의 상세 페이지 url
	$infourl="http://www.gangbuklib.seoul.kr/gangbuk/01.search/list.asp?m=0101&a_lt=&a_v=f&a_bf=T&a_qf=I&a_mt=&a_vp=10&a_q=$isbn&a_dt=&a_pyTo=&a_sort=A&a_dr=&a_ft=&a_ut=&a_pyFrom=&a_lib=$lib&a_cp=1&a_key=$key";
	
	//책 제목 가져오기.
	$pattern_title = "/저자:\<\/dt\>\<dd\>(.*?)\//is";
	$title = $obj->getBook($infourl, $pattern_title); //책 제목
	
	$pattern_bookInfo = "/\<td.*?\>(.*?)\<\/td\>/is";	//<table> 태그의 있는 책정보를 모두 가지고 온다. 
	$td_array = $obj->getBook_Array($infourl, $pattern_bookInfo);	//책정보 태그 
	
	$tmp_book_array = array();
	$col_count = 6; //각 책당 자료갯수.
	$reserv_num = 2; //예약 정보 배열번호.
	$location_num = 5; //위치 정보 배열번호.
	$mark_num = 6; //청구 기호 배열번호.
	$rows = floor(count($td_array)/$col_count); //배열의 row 수

	for($i = 0 ; $i < $rows ; $i++) {
		$tmp_book_array["title"] = rawurlencode(trim($title));
		$td_array[$i * $col_count + $reserv_num] = ($td_array[$i * $col_count + $reserv_num] == "대출중" || $td_array[$i * $col_count + $reserv_num] == "대출대기중") ? "false" : "true";
		$tmp_book_array["reserve"] = rawurlencode(trim($td_array[$i * $col_count + $reserv_num]));
		$tmp_book_array["location"] = rawurlencode(trim($td_array[$i * $col_count + $location_num]))."  ".rawurlencode(trim($td_array[$i * $col_count + $mark_num]));    //위치정보 + 청구기호
		$tmp_book_array["libcode"] = rawurlencode(trim($lib));
		array_push($lib_array, $tmp_book_array);
	}
	
//금천구립정보도서관
}else if($libcode == "0018"){
	//config에 등록.
	$locode = 01;
	if($locode == "01"){$lib = "MA";} //금천
	else if($locode == "02"){$lib = "BR";} //가산
	else if($locode == "03"){$lib = "NR";} //금나래
	else if($locode == "04"){$lib = "GS";} //가산동
	else if($locode == "05"){$lib = "DA";} //독산 1동
	else if($locode == "06"){$lib = "DB";} //독산 2동
	else if($locode == "07"){$lib = "DC";} //독산 3동
	else if($locode == "08"){$lib = "DD";} //독산 4동
	else if($locode == "09"){$lib = "SA";} //시흥 1동
	else if($locode == "10"){$lib = "SC";} //시흥 3동
	else if($locode == "11"){$lib = "SD";} //시흥 4동
	else if($locode == "12"){$lib = "SE";} //시흥 5동
	else if($locode == "13"){$lib = "CA";} //참새

	//키값 가져오기 위한 url (검색페이지 url)
	$url="http://geumcheonlib.seoul.kr/doc_gc/search/search_01.htm?a_v=s&a_lib=$lib&a_qf=I&a_q=$isbn&x=51&y=4";
	$pattern = "/a_key=(.*?)\"\>/is";
	$key = $obj->getKey($url, $pattern); //책의 고유 키값
	
	//책의 상세 페이지 url
	$infourl="http://geumcheonlib.seoul.kr/doc_gc/search/search_01.htm?a_v=s&a_qf=I&a_q=$isbn&x=51&y=4&a_lib=$lib&a_cp=1&a_key=$key";
	
	//책 제목
	$pattern_title = "/class=\"A-LibMBookInfoBox\"\>\r\n<h3>(.*?)\<\/h3\>/is";
	$title = $obj->getBook($infourl, $pattern_title);
	
	$pattern_bookInfo = "/\<td.*?\>(.*?)\<\/td\>/is";	//<table> 태그의 있는 책정보를 모두 가지고 온다. 
	$td_array = $obj->getBook_Array($infourl, $pattern_bookInfo);	//책정보 태그 

	$tmp_book_array = array();
	$col_count = 6; //각 책당 자료갯수.
	$reserv_num = 2; //예약 정보 배열번호.
	$location_num = 5; //위치 정보 배열번호.
	$mark_num = 6; //청구 기호 배열번호.
	$rows = floor(count($td_array)/$col_count); //배열의 row 수
	
	//여러권의 책정보를 보여주기 위해 $lib_array에 결과 값을 넣는다.
	for($i = 0 ; $i < $rows ; $i++) {
		$tmp_book_array["title"] = rawurlencode(trim($title));
		$td_array[$i * $col_count + $reserv_num] = ($td_array[$i * $col_count + $reserv_num] == "대출" || $td_array[$i * $col_count + $reserv_num] == "대출대기") ? "false" : "true";
		$tmp_book_array["reserve"] = rawurlencode(trim($td_array[$i * $col_count + $reserv_num]));
		$tmp_book_array["location"] = rawurlencode(trim($td_array[$i * $col_count + $location_num]))."  ".rawurlencode(trim($td_array[$i * $col_count + $mark_num]));    //위치정보 + 청구기호
		$tmp_book_array["libcode"] = rawurlencode(trim($lib));
		array_push($lib_array, $tmp_book_array);
	}

//금천구립가산정보도서관
}else if($libcode == "0017"){	
	//각 도서의 키값 가져오기.
	$url="http://geumcheonlib.seoul.kr/doc_gc2/search/search_02.htm?a_v=f&a_lib=BR&a_qf=I&a_q=$isbn&a_bf=M&a_dr=&a_dt=&a_ft=&a_mt=&a_ut=&a_lt=&a_pyFrom=&a_pyTo=&a_sort=A&a_vp=10&x=22&y=19";
	$pattern = "/a_key=(.*?)\"\>/is";
	$key = $obj->getKey($url, $pattern);
	
	$pattern_key = "/\<h4 class=\"title\"\>\<a href=.*?a_key=(.*?)\"\>/is";
	$key_ar = $obj->getKey_Array($url, $pattern_key);

	$infourl="http://geumcheonlib.seoul.kr/doc_gc2/search/search_02.htm?a_v=f&a_qf=I&a_q=$isbn&a_bf=M&a_dr=&a_dt=&a_ft=&a_mt=&a_ut=&a_lt=&a_pyFrom=&a_pyTo=&a_sort=A&a_vp=10&x=22&y=19&a_lib=BR&a_cp=1&a_key=$key";	
	$pattern_title = "/class=\"A-LibMBookInfoBox\"\>\r\n<h3>(.*?)\<\/h3\>/is";
	$title = $obj->getBook($infourl, $pattern_title); //책제목
	
	$pattern_bookInfo = "/\<td.*?\>(.*?)\<\/td\>/is";	//<table> 태그의 있는 책정보를 모두 가지고 온다. 
	$td_array = $obj->getBook_Array($infourl, $pattern_bookInfo);	//책정보 태그 
	
	$tmp_book_array = array();
	$col_count = 6; //각 책당 자료갯수.
	$reserv_num = 2; //예약 정보 배열번호.
	$location_num = 5; //위치 정보 배열번호.
	
	//여러권의 책정보를 보여주기 위해 $lib_array에 결과 값을 넣는다.
	for($i = 0; $i < count($key_ar); $i++){
		$infourl = "http://geumcheonlib.seoul.kr/doc_gc2/search/search_02.htm?a_v=f&a_qf=I&a_q=$isbn&a_bf=M&a_dr=&a_dt=&a_ft=&a_mt=&a_ut=&a_lt=&a_pyFrom=&a_pyTo=&a_sort=A&a_vp=10&x=22&y=19&a_lib=BR&a_cp=1&a_key=$key_ar[$i]";
		$td_array = $obj->getBook_Array($infourl, $pattern_bookInfo);
		//rawurlencode처리 및 trim처리와 값을 배열에 저장.
		$tmp_book_array["title"] = trim($title);
		
		$td_array[$reserv_num] = ($td_array[$reserv_num] == "대출대기" || $td_array[$reserv_num] == "대출") ? "false" : "true";
		$tmp_book_array["reserve"] = trim($td_array[$reserv_num]);
		$tmp_book_array["location"] = trim($td_array[$location_num])."  ".trim($td_array[$location_num+1]); //위치정보 + 청구기호
	
		//array push 및 초기화.
		if (!empty($tmp_book_array["reserve"]) && !empty($tmp_book_array["location"])) { //정보 값이 셋팅이 되어있을경우	
			array_push($lib_array, $tmp_book_array);
			$tmp_book_array["reserve"] = '';
			$tmp_book_array["location"] = '';
			$tmp_book_array["title"] = '';
		}
	}
//관악문화도서관(미구현)
}else if($libcode == "0013"){
	$url = "http://www.gwanakcullib.seoul.kr/ecolas-dl/new_kwan//DLS_L3/index.php?mod=wdDls&act=viewSimpleSearchResultList&searchItem%5B%5D=allitem&searchWord%5B%5D=&submit.x=4&submit.y=26&search_type=_book&manageCode=MA&placeInfo=&stCode=$isbn&kdcClass=+&pubYearS=&pubYearE=&dataSort=RK+DESC&mediaCode=&listNum=10";
	$pattern = "/search_value1=(.*?)&/is";
	$key = $obj->getKey($url, $pattern);
	
	$infourl="http://www.gwanakcullib.seoul.kr/ecolas-dl/new_kwan//DLS_L3/index.php?mod=wdDls&act=viewSearchResultDetail&search_value1=$key&search_field1=RK";
	
	$pattern_title = "/\<h2\>(.*?)\//is";
	$pattern_reserv = "/예약\<\/th\>(.*?)\<\/table\>/is";	
	$pattern_location = "/GE[0-9]+(.*?)요약/is";
	
	$title = $obj->getBook_Encoding($infourl, $pattern_title);
	$reserv1 = $obj->getBook($infourl, $pattern_reserv);
	
	$pat = "/.+?(대출|비치)/is";
	
	if(preg_match($pat, $reserv1, $res)){
		$reserv = $res[1];
	}
	
//광진구립도서관(미구현)
}else if($libcode == "0014"){
	
//동대문구정보화도서관(미구현)
}else if($libcode == "0024"){
	
//이진아기념도서관(페이지 오류로 접근 불가 : 400 Bad Erorr)
}else if($libcode == "0041"){
	$url = "http://search.sdmljalib.or.kr/index.php?mod=wdDataSearch&act=searchResultList&manageCode=&searchItem=&searchWord=$isbn";
	$keyPattern = "/<a[^>]*href=[\'\"].*Key=([^>\'\"]*.+?)&[^>]*>/is";
	$key = $obj->getKey($url, $keyPattern); // 책 댑스의 고유 키값
	
	echo $key;
	
	//BOOK 상세페이지 url에서 정보 가져오는 부분.
	$infourl = "http://search.sdmljalib.or.kr/index.php?mod=wdDataSearch&act=searchResultDetail&recKey=$key&searchItem=allitem&searchWord[0]=$isbn";

	$titlePattern = "/BOOK\<\/p\>(.*?)\<\/h3\>/is";	
	$title = $obj->getBook($infourl, $titlePattern); //책 제목

	$reservePattern = "/.+?(대출중|비치).+?/i";
	$reserv = $obj->getBook($infourl, $reservePattern); //대출여부

	$locPattern = "/\<dd\>소장기관 : (.*?)\<\/dd\>/is";
	$location = $obj->getBook($url, $locPattern);
	$detaillocPattern = "/\<li\>청구기호 : \<span class=\"fb black\"\>(.*?)\<\/span\><\/li\>/";
	$location = $location."  ".$obj->getBook($url, $detaillocPattern); //도서관위치+소장위치.
}else{
	exit('{"msg":"error libcode"}');
}

if(empty($title)){
	exit('{"msg":"empty book"}');
/**
 * $lib_array(rawurlencode 및 trim은 각 도서관에서 데이터 삽입시 처리)
 * $lib_array 구성
 *	-$lib_array[$i]["title"] //책제목
 *	-$lib_array[$i]["reserv"] //대출여부
 *	-$lib_array[$i]["location"] //도서관명(소장위치)
 *  -$lib_array[$i]["libcode"] //상세도서관명(동별)
 */
}else{
	print_r($lib_array);
	
	exit('{"msg":"success", "title":"'.$lib_array["title"].'","reserve":"'.$lib_array["reserve"].'","location":"'.$lib_array["location"].'"}');
}
?>