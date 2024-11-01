<?php
$max 	  = isset( $_REQUEST['max'] ) 		? $_REQUEST['max'] 		: 2048;
$security = isset( $_REQUEST['security'] ) 	? $_REQUEST['security'] : '';
$key      = isset( $_REQUEST['key'] ) 	    ? $_REQUEST['key'] 		: '';
$dir      = isset( $_REQUEST['dir'] ) 	    ? $_REQUEST['dir'] 		: '';

$response['length']  = 0;
$response['buffer']  = '';
$response['current'] = 0;

$extra = realpath( dirname( __FILE__ ) . '/snappy.js' );
$what_security_key_should_be = hash( 'sha512', $extra . $dir );

if ( $what_security_key_should_be !== $security ) {
	$response['buffer'] = 'invalid security token';
	echo json_encode( $response );
	exit;
}

// timing.log
$files = array();

$key = 'timing';
$response['key'] = $key;
$file = $dir . $key . '.log';
$offset   = isset( $_REQUEST[$key.'_offset'] ) 	? $_REQUEST[$key.'_offset'] 	: 0;

if ( ! file_exists( $file ) ) {
	$response['buffer'] = 'target file empty';
	$response['current'] = 0;
} else {

	$file_length = filesize( $file );

	if ( $offset == 0 || $offset > $file_length ) {
		$offset = max( 0, $file_length - (2 * $max ) );
	}

	if ( $offset < $file_length ) {
		$response['buffer']  = file_get_contents( $file, null, null, $offset, $max );
		$count_read = strlen( $response['buffer'] );
		//$response['buffer'] = preg_replace( "/[\r\n]+/", '</br>', $response['buffer'] );
		$response['count'] = $count_read;
	} else {
		$response['buffer'] = '';
		$response['count'] = 0;
		$count_read = 0;
	}

	$response['current'] = $offset + $count_read;
}

$files[$key] = $response;



/////////////////////////////////////////////////////////////////////////////////////
// debug.log
$key = 'debug';
$response['key'] = $key;
$offset   = isset( $_REQUEST[$key.'_offset'] ) 	? $_REQUEST[$key.'_offset'] 	: 0;

$file = $dir . $key . '.log';

if ( ! file_exists( $file ) ) {
	$response['buffer'] = 'target file empty';
	$response['current'] = 0;
} else {
	$file_length = filesize( $file );

	if ( $offset == 0 || $offset > $file_length ) {
		$offset = max( 0, $file_length - ( 4 * $max ) );
	}

	if ( $offset < $file_length ) {
		$response['buffer']  = file_get_contents( $file, null, null, $offset, $max );
		$count_read = strlen( $response['buffer'] );
		//$response['buffer'] = preg_replace( "/[\r\n]+/", '</br>', $response['buffer'] );
		$response['count'] = $count_read;
	} else {
		$response['buffer'] = '';
		$count_read = 0;
		$response['count'] = 0;
	}

	$response['current'] = $offset + $count_read;
}

$files[$key] = $response;


/////////////////////////////////////////////////////////////////////////////////////
// other.log
$key = 'other';
$response['key'] = $key;
$offset   = isset( $_REQUEST[$key.'_offset'] ) 	? $_REQUEST[$key.'_offset'] 	: 0;

$otherfile = $dir . 'other.log';

if ( file_exists( $otherfile ) ) {
	$file = file_get_contents( $otherfile );
}

if ( ! file_exists( $otherfile ) || ! file_exists( $file ) ) {
	$response['buffer'] = '';
	$response['current'] = 0;
} else {
	$file_length = filesize( $file );

	if ( $offset == 0 || $offset > $file_length ) {
		$offset = max( 0, $file_length - ( 4 * $max ) );
	}

	if ( $offset < $file_length ) {
		$response['buffer']  = file_get_contents( $file, null, null, $offset, $max );
		$count_read = strlen( $response['buffer'] );
		$response['count'] = $count_read;
	} else {
		$response['buffer'] = '';
		$count_read = 0;
		$response['count'] = 0;
	}

	$response['current'] = $offset + $count_read;
}

$files[$key] = $response;


$return = json_encode( $files );
echo $return;
exit;

