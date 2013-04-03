<?php

function arg($index) {
	static $_args, $path;

	if (!isset($_GET['path'])) {
		return NULL;
	}
	
	if (empty($_args) || $path != $_GET['path']) {
		$_args = explode('/', $_GET['path']);
		$path = $_GET['path'];
	}

	if (isset($_args[$index])) {
		return $_args[$index];
	}
	
	return NULL;
}


function arg2($index, $new_path) {
	static $_args, $path;

	if (empty($_args) || $path != $new_path) {
		$_args = explode('/', $new_path);
		$path = $new_path;
	}

	if (isset($_args[$index])) {
		return $_args[$index];
	}
	
	return NULL;
}


function is_form_post() {
    return (boolean) count($_POST);    
}

function req($var_name, $default = '<@>') {
	if (isset($_REQUEST[$var_name])) {
		return $_REQUEST[$var_name];
	}
	
	// If there was no default value, it means we expected the
	// variable to exist. Since it didn't, throw an exception.
	if ($default === '<@>') {
		throw new Exception("Request variable $var_name not found.");
	}

	return $default;
}

function http_redirect($url, $permanent = FALSE) {
	
 	// Ensure session variables are written before redirect.
	@session_write_close();  

	if ($permanent) {
		header("HTTP/1.1 301 Moved Permanently");
	}
	
	header('Location: '.$url);

	// Redirects may fail without this call to exit()
	exit();
}

function input_post($key, $default = NULL){
	if (isset($_POST[$key])){
		return $_POST[$key];
	}
	else {
		return $default;
	}
}

function input_get($key, $default = NULL){
	if (isset($_GET[$key])){
		return $_GET[$key];
	}
	else {
		return $default;
	}
}