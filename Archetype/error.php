<?php

require_once __DIR__ . '/misc.php';

function show_web_error_page(){
	require_once __DIR__ . '/tpl.php';

	$layout_template = isset($_GET['modal']) ? 'ModalLayout' : 'SimpleLayout';
	$layout = new template(__DIR__ . '/../Web/Views/' . $layout_template . '.phtml');
	$layout->pageClass = NULL;
	$layout->pageTitle = NULL;
	$layout->openGraphMetas = array();
	$layout->isFbAppMode = FALSE;
	$layout->rev = 0;

	$page = new template(__DIR__ . '/../Web/Views/Error/index.phtml');
	$page->message = NULL;
	$page->last_error_id = NULL;
	$page->showFeedbackLink = TRUE;

	$layout->PageContent = $page->render();
	$layout->render(TRUE);
	exit;
}



function exception_handler($exception) {
	$_result = $_resultExtended = array();

	$_result['error']       = TRUE;
	$_result['type']        = 'Unhandled Exception';
	$_result['exception']   = $exception->getMessage();

	$serverName = htmlspecialchars(trim(shell_exec("hostname")));

	$_resultExtended['hostname']    = $serverName;
	$_resultExtended['server']      = serverVar('SERVER_NAME');
	$_resultExtended['request_uri'] = serverVar('REQUEST_URI');
	$_resultExtended['client']      = serverVar('HTTP_USER_AGENT');
	$_resultExtended['stack_trace'] = explode("\n", $exception->getTraceAsString());


	$xh = '<div style="font: 15px/22px Arial, Sans-Serif; padding: 15px;">';

	// General Information about Error
	$xh .= '<fieldset style="border: 2px solid #f00;
		background-color: #fcc; padding: 0 20px;
	 	border-radius: 10px; -moz-border-radius: 10px;
		-webkit-border-radius: 10px;">

		<legend style="font-size: 22px; border: 2px solid #f00;
			background-color: #fcc; padding: 5px 10px;
			border-radius: 10px; -moz-border-radius: 10px;
			-webkit-border-radius: 10px;">
			' . date('Y-m-d H:i:s') . '
			Unhandled Exception
		</legend>
		<h3>' . $exception->getMessage() . '</h3>';

	// File and uri information
	if (serverVar('SERVER_NAME') AND serverVar('REQUEST_URI')) {
		$url = 'http://' . serverVar('SERVER_NAME') . serverVar('REQUEST_URI');
		$xh .= '<p>';
		$xh .= 'URL: <a href="' . $url . '"><b>' . htmlspecialchars($url) .
			' </b></a><br/>';
		$xh .= '</p>';
	}

	$xh .= '
	</fieldset>';

	// Server and environment information
	$xh .= '<div style="border: 2px solid #999; margin-top: 20px;
		background-color: #efefef; padding: 0 20px 20px 20px;
	 	border-radius: 10px; -moz-border-radius: 10px;
		-webkit-border-radius: 10px;">

		<h3>Server and Environment Information</h3>';
	$xh .= error_print_context(array(
			'Server'       => $serverName,
			'Server IP'    => serverVar('SERVER_ADDR'),
			'Client IP'    => serverVar('REMOTE_ADDR'),
			'Referer'      => serverVar('HTTP_REFERER'),
			'Redirect URL' => serverVar('REDIRECT_URL'),
			'Method'       => serverVar('REQUEST_METHOD'),
		), 0, TRUE);

	if (isset($_REQUEST) AND count($_REQUEST) > 0) {
		$xh .= '<br /><hr size="1" />
		<h3>Request Data</h3>';
		$xh .= error_print_context($_REQUEST, 0, TRUE);
	}

	$xh .= '<br /><hr size="1" />
		<h3>Stack Backtrace</h3>';
	$xh .= exception_render_stack_trace($exception->getTrace());

	$xh .= '
	</div>';

	$xh .= '</div>';


	//header('Content-type: application/json');
	header('HTTP/1.1 500 Internal Server Error');

	if (ARCHETYPE_DISPLAY_ERRORS) {
		if (preg_match('/(mozilla|webkit)/i', serverVar('HTTP_USER_AGENT'))) {
			die($xh);
		}
		else if (!isset($_SERVER)) {
			die(strip_tags($xh));
		}
		else {
			die(json_encode($_result));
		}
	}
	else {
		/*
		if (defined("ARCHETYPE_WATCHDOG_EMAIL_TO")) {
			mail(ARCHETYPE_WATCHDOG_EMAIL_TO,
				'Unhandled Exception',
				$xh,
				"From: Moonit Admin <admin@moonit.com>\r\n" .
					"Content-type: text/html; charset=utf-8"
			);
		}
		*/

		if (isset($GLOBALS['application_tag'])
			AND $GLOBALS['application_tag'] == 'Web') {

			show_web_error_page();

			/*
			header("Location: /Error/500?" .
				isset($_SERVER['QUERY_STRING']) ?
				$_SERVER['QUERY_STRING'] : NULL);
			exit;
			*/
		}

		die(json_encode(array(
			'error' => TRUE
		)));
	}

	exit;
}

set_exception_handler('exception_handler');

// To test the exception_handler, temporarily uncomment this line:
// throw new Exception();

// As strict as possible
error_reporting(-1);

// Currently, this code assumes we're in a Web browser and we
// want to output the error as HTML. This doesn't translate well to
// working from the command line. So for now we'll skip all this if
// we're in dev mode on the command line.
//if (php_sapi_name() == 'cli' && CONFIG_DEV_MODE === true) {
//	return;
//}

# set up error_handler() as the new default error handling function
set_error_handler('error_handler');

$g_error_parameters		= array();
$g_error_handled		= false;
$g_error_proceed_url	= null;

/**
 * Customer error handler
 *
 * @param $p_type
 * @param $p_error
 * @param $p_file
 * @param $p_line
 * @param $p_context
 */
function error_handler( $p_type, $p_error, $p_file, $p_line, $p_context ) {
	global $g_error_parameters, $g_error_handled;

	# check if errors were disabled with @ somewhere in this call chain
	# also suppress php 5 strict warnings
	/*
	   if ( 0 == error_reporting()) {
		   return;
	   }
	   */

	$t_short_file = basename( $p_file );

	# build an appropriate error string
	switch ( $p_type ) {
		case E_WARNING:
			$t_error_type = 'SYSTEM WARNING';
			break;
		case E_NOTICE:
			$t_error_type = 'SYSTEM NOTICE';
			break;
		case E_USER_ERROR:
			$t_error_type = "APPLICATION ERROR";
			break;
		case E_USER_WARNING:
			$t_error_type = "APPLICATION WARNING";
			break;
		case E_USER_NOTICE:
			$t_error_type = 'APPLICATION NOTICE';
			break;
		default:
			#shouldn't happen, just display the error just in case
			$t_error_type = '';
	}

	if (is_numeric($p_error)) {
		$t_error_type .= " #$p_error";
	}
	$t_error_description = nl2br(error_string( $p_error ));

	$t_old_contents = ob_get_contents();
	# ob_end_clean() still seems to call the output handler which
	#  outputs the headers indicating compression. If we had
	#  PHP > 4.2.0 we could use ob_clean() instead but as it is
	#  we need to disable compression.
	// ob_clean();
	// compress_disable();

	if ( ob_get_length() ) {
		ob_end_clean();
	}

	$_result = $_resultExtended = array();

	$_result['error']       = TRUE;
	$_result['type']        = $t_error_type;
	$_result['description'] = $t_error_description;

	$serverName = htmlspecialchars(trim(shell_exec("hostname")));

	$_resultExtended['hostname']    = $serverName;
	$_resultExtended['file']        = htmlentities( $p_file ) . ' Line ' . $p_line;
	$_resultExtended['server']      = serverVar('SERVER_NAME');
	$_resultExtended['request_uri'] = serverVar('REQUEST_URI');
	$_resultExtended['client']      = serverVar('HTTP_USER_AGENT');

	$t_stack = debug_backtrace(); array_shift( $t_stack );
	$_resultExtended['stack_trace'] = $t_stack;



	$xh = '<div style="font: 15px/22px Arial, Sans-Serif; padding: 15px;">';

	// General Information about Error
	$xh .= '<fieldset style="border: 2px solid #f00;
		background-color: #fcc; padding: 0 20px;
	 	border-radius: 10px; -moz-border-radius: 10px;
		-webkit-border-radius: 10px;">

		<legend style="font-size: 22px; border: 2px solid #f00;
			background-color: #fcc; padding: 5px 10px;
			border-radius: 10px; -moz-border-radius: 10px;
			-webkit-border-radius: 10px;">
			' . date('Y-m-d H:i:s') . '
			' . $t_error_type . '
			</legend>
		<h3>' . $t_error_description . '</h3>';

	// File and uri information
	if (serverVar('SERVER_NAME') AND serverVar('REQUEST_URI')) {
		$url = 'http://' . serverVar('SERVER_NAME') . serverVar('REQUEST_URI');
		$xh .= '<p>';
		$xh .= 'URL: <a href="' . $url . '"><b>' . htmlspecialchars($url) .
			' </b></a><br/>';
		$xh .= 'File: <strong>'.htmlentities( $p_file ).' [Line '.$p_line.']';
		$xh .= '</p>';
	}

	$xh .= '
	</fieldset>';

	// Server and environment information
	$xh .= '<div style="border: 2px solid #999; margin-top: 20px;
		background-color: #efefef; padding: 0 20px 20px 20px;
	 	border-radius: 10px; -moz-border-radius: 10px;
		-webkit-border-radius: 10px;">

		<h3>Server and Environment Information</h3>';
	$xh .= error_print_context(array(
			'Server'       => $serverName,
			'Server IP'    => serverVar('SERVER_ADDR'),
			'Client IP'    => serverVar('REMOTE_ADDR'),
			'Referer'      => serverVar('HTTP_REFERER'),
			'Redirect URL' => serverVar('REDIRECT_URL'),
			'Method'       => serverVar('REQUEST_METHOD'),
		), 0, TRUE);

	if (isset($_REQUEST) AND count($_REQUEST) > 0) {
		$xh .= '<br /><hr size="1" />
		<h3>Request Data</h3>';
		$xh .= error_print_context($_REQUEST, 0, TRUE);
	}

	$xh .= '<br /><hr size="1" />
		<h3>Stack Backtrace</h3>';
	$xh .= error_render_stack_trace();

	$xh .= '
	</div>';

	$xh .= '</div>';


	//header('Content-type: application/json');
	header('HTTP/1.1 500 Internal Server Error');

	if (ARCHETYPE_DISPLAY_ERRORS) {
		if (preg_match('/(mozilla|webkit)/i', serverVar('HTTP_USER_AGENT'))) {
			die($xh);
		}
		else if (PHP_SAPI === 'cli') {
			$cmd_output = strip_tags($xh);
			$cmd_output = preg_replace('/\t/', '', $cmd_output);
			$cmd_output = preg_replace('/(\r|\n)+/', "\n", $cmd_output);
			die($cmd_output);
		}
		else {
			die(json_encode($_result));
		}
	}
	else {
		/*
		if (defined("ARCHETYPE_WATCHDOG_EMAIL_TO")) {
			mail(ARCHETYPE_WATCHDOG_EMAIL_TO,
				'Unhandled Exception',
				$xh,
				"From: Moonit Admin <admin@moonit.com>\r\n" .
					"Content-type: text/html; charset=utf-8"
			);
		}
		*/

		if (isset($GLOBALS['application_tag'])
			AND $GLOBALS['application_tag'] == 'Web') {
			header("Location: /Error/500?" .
				isset($_SERVER['QUERY_STRING']) ?
				$_SERVER['QUERY_STRING'] : NULL);
			exit;
		}

		die(json_encode(array(
			'error' => TRUE
		)));
	}

	exit;
}

# ---------------
# Print out the variable context given
function error_print_context( $p_context, $i=0, $with_table = FALSE ) {
	if( !is_array( $p_context ) ) {
		return;
	}

	if ($with_table) {
		$xh = '<table cellpadding="5" cellspacing="0" style="min-width: 500px;">
			<tr bgcolor="#aaa">
				<th style="min-width: 200px; text-align: left;">Variable</th>
				<th style="min-width: 250px; text-align: left;">Value</th>
				<th style="min-width: 100px; text-align: left;">Type</th></tr>';
	}
	else {
		$xh = '';
	}

	# print normal variables
	foreach ( $p_context as $t_var => $t_val ) {
		if ( !is_array( $t_val ) && !is_object( $t_val ) ) {
			$t_val = htmlentities( (string)$t_val );
			$t_type = gettype( $t_val );

			# Mask Passwords
			if ( strpos( $t_var, 'password' ) !== false ) {
				$t_val = '**********';
			}

			$xh .=  '<tr ';
			if (($i++ % 2) == 1) {
				$xh .=  'style="background-color: #aaa"';
			} else {
				$xh .=  'style="background-color: #efefef"';
			}
			$xh .=  '>';

			$xh .= "<td>$t_var</td><td>$t_val</td><td>$t_type</td></tr>\n";
		}
	}

	# print arrays
	foreach ( $p_context as $t_var => $t_val ) {
		if ( is_array( $t_val ) && ( $t_var != 'GLOBALS' ) ) {
			$xh .= "<tr><td colspan=\"3\" align=\"left\"><br /><strong>$t_var</strong></td></tr>";

			$xh .= "<td colspan=\"3\">";
			if(count($t_val) == 0) {
				$xh .= "empty";
			} else {
				$xh .= error_print_context( $t_val);
			}
			$xh .= "</td></tr>";
		}
	}

	if ($with_table) {
		$xh .= '</table>';
	}

	return $xh;
}

# ---------------
# Print out the variable context given
function error_print_globals() {
	//array of globals we want to print out
	$error_globals = array(
		'POST'			=> $GLOBALS['_POST'],
		'GET'			=> $GLOBALS['_GET'],
		'COOKIE'		=> $GLOBALS['_COOKIE'],
		'REQUEST'		=> $GLOBALS['_REQUEST'],
		'SERVER'		=> $GLOBALS['_SERVER'],
		'SQL_OPTIONS'	=> isset($GLOBALS['SQL_OPTIONS']) ? $GLOBALS['SQL_OPTIONS'] : '',
		'FILES'			=> $GLOBALS['_FILES'],
	);

	return error_print_context( $error_globals );
}


function exception_render_stack_trace($trace) {
	return render_stack_trace($trace);
}

function error_render_stack_trace() {
	$t_stack = debug_backtrace();

	array_shift( $t_stack ); #remove the call to this function from the stack trace
	array_shift( $t_stack ); #remove the call to the error handler from the stack trace

	return render_stack_trace($t_stack);
}

function logger_render_stack_trace() {
	$t_stack = debug_backtrace();
	array_shift( $t_stack ); #remove the call to this function from the stack trace
	return render_stack_trace($t_stack);
}


# ---------------
# Print out a stack trace if PHP provides the facility or xdebug is present
function render_stack_trace($t_stack) {

	$xh = '';
	$xh .=  '<table cellpadding="5" cellspacing="0">';
	$xh .=  '<tr bgcolor="#aaa">
		<th style="min-width: 250px; text-align: left;">Filename</th>
		<th style="min-width: 100px; text-align: left;">Line</th>
		<th style="min-width: 200px; text-align: left;">Function</th></tr>';
	// <th>Args</th>

	$i = 0;
	$root_path_len = strlen(realpath(__DIR__ . '/../../'));
	foreach ( $t_stack as $t_frame ) {
		$xh .=  '<tr ';
		if (($i++ % 2) == 1)
			$xh .=  'style="background-color: #aaa"';
		else
			$xh .=  'style="background-color: #efefef"';

		$xh .=  '>';

		if (isset($t_frame['file'])) {
			$filename = substr($t_frame['file'], $root_path_len,
				strlen($t_frame['file']) - $root_path_len);
		}
		else {
			$filename = NULL;
		}

		$xh .=  '<td>' . htmlentities($filename) . '</td>
		<td>' . (isset( $t_frame['line'] ) ? $t_frame['line'] : '-') . '</td>
		<td>' . $t_frame['function'] . '</td>';
	}

	$xh .=  '</table>';


	return $xh;
}
# ---------------
# Build a string describing the parameters to a function
function error_build_parameter_string( $p_param ) {
	if ( is_array( $p_param ) ) {
		$t_results = array();

		foreach ( $p_param as $t_key => $t_value ) {
			$t_results[] =	'[' . error_build_parameter_string( $t_key ) . ']' .
				' => ' . error_build_parameter_string( $t_value );
		}

		return '{ ' . implode( $t_results, ', ' ) . ' }';
	} else if ( is_bool( $p_param ) ) {
		if ( $p_param ) {
			return 'true';
		} else {
			return 'false';
		}
	} else if ( is_float( $p_param ) || is_int( $p_param ) ) {
		return $p_param;
	} else if ( is_null( $p_param ) ) {
		return 'null';
	} else if ( is_object( $p_param ) ) {
		$t_results = array();

		$t_class_name = get_class( $p_param );
		$t_inst_vars = get_object_vars( $p_param );

		foreach ( $t_inst_vars as $t_name => $t_value ) {
			$t_results[] =	"[$t_name]" .
				' => ' . error_build_parameter_string( $t_value );
		}

		return 'Object <$t_class_name> ( ' . implode( $t_results, ', ' ) . ' )';
	} else if ( is_string( $p_param ) ) {
		return "'$p_param'";
	}
}

# ---------------
# Return an error string (in the current language) for the given error
function error_string( $p_error ) {
	global $g_error_parameters;

	# We pad the parameter array to make sure that we don't get errors if
	#  the caller didn't give enough parameters for the error string
	$t_padding = array_pad( array(), 10, '' );

	if (is_numeric($p_error)) {
		$t_error = $_ERRORS[$p_error];
	} else {
		$t_error = $p_error;
	}

	return call_user_func_array( 'sprintf', array_merge( array( $t_error ), $g_error_parameters, $t_padding ) );
}

# ---------------
# Set additional info parameters to be used when displaying the next error
# This function takes a variable number of parameters
#
# When writing internationalized error strings, note that you can change the
#  order of parameters in the string.  See the PHP manual page for the
#  sprintf() function for more details.
function error_parameters() {
	global $g_error_parameters;

	$g_error_parameters = func_get_args();
}

# ---------------
# Set a url to give to the user to proceed after viewing the error
function error_proceed_url( $p_url ) {
	global $g_error_proceed_url;

	$g_error_proceed_url = $p_url;
}
