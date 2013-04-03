<?php


// Birth date validation error codes
define('DOB_INVALID_FORMAT', 1001);
define('DOB_MISSING_YEAR',   1002);
define('DOB_TOO_YOUNG',      1003);
define('DOB_TOO_OLD',        1004);

function is_valid_email($email) {
	$quotable       = '@,"\[\]\\x5c\\x00-\\x20\\x7f-\\xff';
	$local_quoted   = '"(?:[^"]|(?<=\\x5c)"){1,62}"';
	$local_unquoted =  '(?:(?:[^'.$quotable.'\.]|\\x5c(?=['.$quotable.']))'
		.'(?:[^'.$quotable.'\.]|(?<=\\x5c)['.$quotable.']|\\x5c(?=['.$quotable.'])|\.(?=[^\.])){1,62}'
		.'(?:[^'.$quotable.'\.]|(?<=\\x5c)['.$quotable.'])|[^'.$quotable.'\.]{1,2})';
	$local          = '('.$local_unquoted.'|'.$local_quoted.')';

	$_0_255         = '(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])';
	$domain_ip      = '\['.$_0_255.'(?:\.'.$_0_255.'){3}\]';
	$domain_name    = '(?!.{64})(?:[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.?|[a-zA-Z0-9]\.?)+\.(?:xn--[a-zA-Z0-9]+|[a-zA-Z]{2,6})';

	$exp = "/^(?:$local_unquoted|$local_quoted)@(?:$domain_name|$domain_ip)$/";

	return preg_match($exp,$email) ? TRUE : FALSE;
}

function is_valid_username($username){
	return preg_match("/^[a-zA-Z][a-zA-Z0-9_.-]{3,30}$/",
		$username) ? TRUE : FALSE;
}

// Takes date in MYSQL format. ex: 1972-01-17
function is_valid_dob($dob, $limit_low = 13, $limit_high = 100) {
	// Converting string to timestamp.
	if (strlen($dob) < 6 OR !($timestamp = strtotime($dob))) {
		return DOB_INVALID_FORMAT;
	}

	// Checking missing year.
	if (date('Y', $timestamp) == date('Y') && strpos($dob, date('Y')) === FALSE) {
		return DOB_MISSING_YEAR;
	}

	// Checking age limits
	if ($timestamp > strtotime("$limit_low years ago")) {
		return DOB_TOO_YOUNG;
	}
	//else if ($timestamp < strtotime("$limit_high years ago")) {
	else if ($timestamp < strtotime("01/01/1909")) {
		return DOB_TOO_OLD;
	}

	// This birth date is right
	return TRUE;
}
// Takes date in MYSQL format. ex: 1972-01-17
function is_valid_dob_no_too_old($dob, $limit_low = 13, $limit_high = 100) {
	// Converting string to timestamp.
	if (strlen($dob) < 6 OR !($timestamp = strtotime($dob))) {
		return DOB_INVALID_FORMAT;
	}

	// Checking missing year.
	if (date('Y', $timestamp) == date('Y') && strpos($dob, date('Y')) === FALSE) {
		return DOB_MISSING_YEAR;
	}

	// Checking age limits
	if ($timestamp > strtotime("$limit_low years ago")) {
		return DOB_TOO_YOUNG;
	}

	// This birth date is right
	return TRUE;
}

function is_valid_dob2($month, $day, $year) {
    return is_valid_dob("$month/$day/$year");
}

function process_dob($dob, $limit_low = 10, $limit_high = 100){
	// Validate birth date
	$validation_result = is_valid_dob($dob, $limit_low, $limit_high);
	if (!$validation_result) {
		return $validation_result;
	}

	// Convert to date
	$timestamp = strtotime($dob);

	// Return result
	return array(
		'timestamp' => $timestamp,
		'year'      => date('Y', $timestamp),
		'month'     => date('m', $timestamp),
		'day'       => date('d', $timestamp),
		'date'      => date('Y-m-d', $timestamp)
	);
}

function is_valid_gender($gender) {
   return ($gender == 'M' || $gender == 'F');    
}

function http_send_request($url, $_parms = null, $method = 'GET', $alwaysReturnResponse = FALSE) {
	$query_string = '';
	if ($_parms) {
		foreach($_parms as $k => $v) {
			$v = urlencode($v);
			$query_string .= $k.'='.$v.'&';
		}
		$query_string = substr($query_string, 0, -1);  // remove trailing '&'
	}

	// Curl options array
	$curl_options = array(
		CURLOPT_RETURNTRANSFER => TRUE,             // return web page
		CURLOPT_FOLLOWLOCATION => TRUE,             // follow redirects
		CURLOPT_MAXREDIRS      => 10,               // stop after 10 redirects
		CURLOPT_ENCODING       => "",               // handle all encodings
		CURLOPT_USERAGENT      => "moonit-request", // our user agent
		CURLOPT_AUTOREFERER    => TRUE,             // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 15,               // timeout on connect
		CURLOPT_TIMEOUT        => 30,               // timeout on response
	);

	if (strtoupper($method) == 'POST') {
		$curl_options[CURLOPT_POST] = TRUE;

		if ($_parms) {
			$curl_options[CURLOPT_POSTFIELDS] = $query_string;
		}
	}
	else if (strtoupper($method) == 'PUT') {
		$curl_options[CURLOPT_CUSTOMREQUEST] = 'PUT';

		if ($_parms) {
			$curl_options[CURLOPT_POSTFIELDS] = $query_string;
		}
	}
	else {
		if ($_parms) {
			$url .= '?' . $query_string;
		}
	}

	$curl_options[CURLOPT_URL] = $url;

    // Getting content using curl.
    $ch = curl_init();
    curl_setopt_array($ch, $curl_options);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Controlling response http code.
    if (!$alwaysReturnResponse AND $http_code != 200) {
	    return FALSE;
    }

    // Controlling response content
	if ($response === FALSE)
        return FALSE;

    return $response;
}

function get_client_ip_address($convert_to_int = FALSE) {

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {        
		$ip = $_SERVER['HTTP_CLIENT_IP'];    
	} 
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];    
	} 
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	
	if ($convert_to_int) {
	    $ip = sprintf('%u', ip2long($ip));
	}

	return $ip;
}

function json_result($result) {
	header('Content-type: application/json');
	print json_encode($result);
    exit;
}

function daysSince($date)
{
	 $now = time(); 
     $your_date = strtotime($date);
     $datediff = $now - $your_date;
     $days = floor($datediff/(60*60*24));
	 if ($days == 0) return 'Today';
	 if ($days == 1) return '1 day ago';
	 if ($days < 30) return $days.' days ago';

	 $months = round($days/30);
	 if ($months == 1) return  '1 month ago';
	 return "$months months ago";

}

function nicetime($date, $short=false, $shorter=FALSE) {
		
    $_periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	if ($short) $_periods         = array("sec", "min", "hr", "day", "week", "month", "year", "decade");
    if ($shorter) $_periods         = array("s", "m", "h", "d", "w", "mo", "y", "dc");
    $_lengths         = array("60", "60", "24", "7", "4.35", "12", "10");
   
    $now             = time();
    $unix_date       = strtotime($date);
   
    // check validity of date
    if (empty($unix_date)) {   
		return NULL;
        //trigger_error("bad date: " . $date);
    }

    // is it future date or past date
    if ($now > $unix_date) {   
		$difference = $now - $unix_date;
		$tense      = "ago";       
		if ($short || $shorter) $tense      = "";
    } 
	else {
        $difference = $unix_date - $now;
        $tense      = "from now";
    }
   
    for($j = 0; $difference >= $_lengths[$j] && $j < count($_lengths)-1; $j++)
 	{
        $difference /= $_lengths[$j];
    }
   
    $difference = round($difference);
   
    if ($difference != 1) {
		if ($short) $_periods[$j] .= "s";
        elseif ($shorter) $_periods[$j] .= "";
        else $_periods[$j] .= "s";
    }

	if ($difference == 0 AND $_periods[$j] == 'seconds') {
		return 'now';
	}
   
	if ($short) return "$difference $_periods[$j] {$tense}";
    elseif ($shorter) return "$difference$_periods[$j]{$tense}";
    else return "$difference $_periods[$j] {$tense}";
}

function nicetimeSpan($difference, $short=false) {
		
    $_periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	if ($short) $_periods         = array("sec", "min", "hr", "day", "week", "month", "year", "decade");
    $_lengths         = array("60", "60", "24", "7", "4.35", "12", "10");
   
	$tense      = "";       
   
    for($j = 0; $difference >= $_lengths[$j] && $j < count($_lengths)-1; $j++)
 	{
        $difference /= $_lengths[$j];
    }
   
    $difference = round($difference);
   
    if ($difference != 1) {
		if ($short) $_periods[$j] .= "s";
		else $_periods[$j] .= "s";
    }

	if ($difference == 0 AND $_periods[$j] == 'seconds') {
		return 'now';
	}
   
	if ($short) return "$difference $_periods[$j] {$tense}";
    return "$difference $_periods[$j] {$tense}";
}

function nicetimeSpanWithTooltip($span, $short=false){

	if (NULL == $span) return "";
	$dt = number_format($span/60,0,'.',',');
	$s= '<span title="'.$dt.' mins">' .
		str_replace(' ', '&nbsp;', nicetimeSpan($span, $short)) . '</span>';
	return $s;
}


function nicetimeWithTooltip($date, $short=false){

	if (NULL == $date) return "";
	$dt = date("Y-m-d H:i:s", strtotime($date));
	$s= '<span title="'.$dt.'">' .
		str_replace(' ', '&nbsp;', nicetime($date, $short)) . '</span>';
	return $s;
}

function percent($numerator, $denominator, $decimal_places = 1) {
	if ($denominator == 0) return 0;
	return number_format(($numerator / $denominator) * 100,
		$decimal_places);
}

function calculate_age($dob) {  
    return floor((time() - strtotime($dob))/31556926);   
}

function array_sort_by_column($multi_array, $column, $reverse = false){
	if (!$multi_array || count($multi_array) == 0) return $multi_array;
	foreach ($multi_array as $row) $sort_array[] = strtolower($row[$column]);
	@array_multisort($sort_array, $multi_array);
	if ($reverse) $multi_array = @array_reverse($multi_array);
	return $multi_array;
}

function serverVar($key){
	if (isset($_SERVER[$key])) {
		return $_SERVER[$key];
	}

	return NULL;
}

//gets list of files in a folder
function getListOfFiles($path)
{
	$_fileList = array();
	if ($handle = opendir($path))
	{
		while (false !== ($file = readdir($handle)))
		{
			if ($file !== '.' && $file !== '..' && !is_dir($file)) $_fileList[] = $file;
		}
	}
	return $_fileList;
}

function geta($_array, $key, $default = NULL){
	if (isset($_array[$key]) OR isset($_array->$key)) {
		return $_array[$key];
	}
	else {
		return $default;
	}
}

function run_cmd_in_background($cmd){
	shell_exec($cmd . ' > /dev/null &');
	return TRUE;
}

function utf8_encode_recursive($_data){
	if (is_array($_data)) {
		foreach ($_data as &$val) {
			$val = utf8_encode_recursive($val);
		}
	}
	else {
		$_data = utf8_encode($_data);
	}

	return $_data;
}

function firstName($full_name){
	$name = explode(' ', trim($full_name));
	return ucfirst($name[0]);
}

function logger() {
    $arguments = func_get_args();
    $errorMessage = '';
    foreach($arguments as $argument) {
        $errorMessage .= print_r($argument,TRUE) . ' ';
    }
    if(!empty($backtrace)) {
        $errorMessage .= print_r(debug_backtrace(),TRUE);
    }
    if(!empty($stdout)) {
        echo $errorMessage;
    }
    else {
        error_log($errorMessage);
    }
}

function stringEndsWith($string, $test) {
	$strlen = strlen($string);
	$testlen = strlen($test);
	if ($testlen > $strlen) return false;
	return substr_compare($string, $test, -$testlen) === 0;
}