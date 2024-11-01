<?php
/*
 * Plugin Name: Store Check-Up and Fix-Up for WP-eCommerce
 * Contributors: jeff@pyebrook.com
 * Author: Pye Brook Company, Inc. / Jeffrey Schutzman
 * Plugin URI: http://www.pyebrook.com/snappy-wpec-site
 * Description: Show performance and configuration information from your WP-eCommerce / WordPress Site
 * Version: 4.0
 * Author URI: http://www.pyebrook.com
 * License: GPL v2
 */


/*
** Copyright 2010-2014, Pye Brook Company, Inc.
**
**
** This software is provided under the GNU General Public License, version
** 2 (GPLv2), that covers its  copying, distribution and modification. The
** GPLv2 license specifically states that it only covers only copying,
** distribution and modification activities. The GPLv2 further states that
** all other activities are outside of the scope of the GPLv2.
**
** All activities outside the scope of the GPLv2 are covered by the Pye Brook
** Company, Inc. License. Any right not explicitly granted by the GPLv2, and
** not explicitly granted by the Pye Brook Company, Inc. License are reserved
** by the Pye Brook Company, Inc.
**
** This software is copyrighted and the property of Pye Brook Company, Inc.
**
** Contact Pye Brook Company, Inc. at info@pyebrook.com for more information.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY
** WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
** A PARTICULAR PURPOSE.
**
*/
require __DIR__ . '/vendor/pyebrook/pbci-lib/pbci-lib.php';

include( plugin_dir_path( __FILE__ ) . 'database-check.php' );
include( plugin_dir_path( __FILE__ ) . 'cleanup-actions.php' );

class SnappySite {
	function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'admin_menu' ), 11 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'my_enqueue' ) );
		}
	}

	static function get_security() {
		$key = realpath( dirname( __FILE__ ) . '/snappy.js' );
		$nonce = wp_create_nonce( $key );
		return $nonce;
	}

	function my_enqueue( $hook ) {

		$stylesheet = snappy_plugin_root_dir() . 'style.css';
		$timestamp  = filemtime( $stylesheet );

		wp_register_style( 'snappy-style', plugins_url( 'style.css', __FILE__ ), array(), $timestamp );
		wp_enqueue_style( 'snappy-style' );

		if ( strpos( $hook, 'snappy' ) === false ) {
			return;
		}

		wp_enqueue_script( 'snappy', plugins_url( 'snappy.js', __FILE__ ), array(
				'jquery',
			), filemtime( __FILE__ ), false );

		$snappy              = array();
		$snappy['dir']       = WP_CONTENT_DIR . '/';
		$snappy['ajaxurl']   = plugins_url( 'ajax.php', __FILE__ );
		$snappy['adminajax'] = admin_url( 'admin-ajax.php' );
		$snappy['file']      = WP_CONTENT_DIR . '/timing.log';
		$snappy['security']  = self::get_security();
		$snappy['offset']    = 0;
		$snappy['max']       = 0;

		wp_localize_script( 'snappy', 'snappy', $snappy );
	}

	function admin_menu() {
		static $added_admin_menu = false;

		if ( ! $added_admin_menu ) {
			// Add a new submenu under Tools:
			add_management_page( __( 'Store Check-Up', 'snappy-site' ), __( 'Store Check-Up', 'snappy-site' ), 'manage_options', 'snappy_site', 'snappy_database_check' );

			if ( function_exists( 'pbci_about_help_support' ) ) {
				add_submenu_page( null, __( 'about' ), __( 'about' ), 'manage_options', 'pbci_about_help_support', 'pbci_about_help_support' );
			}
		}
	}

	function admin_logs() {
		?>
		<div class="wrap">
			<h2>Snappy WPEC Site</h2>
			<input type="button" id="refresh_now" value="Refresh Now"> &nbsp;&nbsp;&nbsp;&nbsp; <input type="checkbox"
			                                                                                           id="pause">Pause
			<div id="snappy_tabs">
				<ul>
					<li><a href="#timing">timing log</a></li>
					<li><a href="#debug">debug log</a></li>
					<?php if ( defined( 'SAVEQUERIES' ) ) { ?>
						<li><a href="#query">query log</a></li>
					<?php } ?>
					<li><a href="#other">other log</a></li>
				</ul>
				<div class="snappy_tab" id="timing" style="overflow-y:auto;height:50em;">

				</div>
				<div class="snappy_tab" id="debug" style="overflow-y:auto;height:50em;">

				</div>
				<?php if ( defined( 'SAVEQUERIES' ) ) { ?>
					<div class="snappy_tab" id="query" style="overflow-y:auto;height:50em;">

					</div>
				<?php } ?>
				<div class="snappy_tab" id="other" style="overflow-y:auto;height:50em;">

				</div>

			</div>

		</div>

	<?php
	}
}

$doing_ajax      = defined( 'DOING_AJAX' ) && DOING_AJAX;
$doing_heartbeat = isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'heartbeat';

if ( class_exists( 'SnappySite' ) ) {
	if ( is_admin() && ! $doing_ajax && ! $doing_heartbeat ) {
		$snappy_site = new SnappySite();
	} else {
		if ( is_admin() && ! $doing_ajax ) {
			$snappy_site = new SnappySite();
			wp_cache_set( 'snappy_admin_has_logged_in_recently', true, 'snappy', 300 );
		}
	}
}

function snappy_has_admin_loged_in_recently() {
	$has_admin_logged_in = wp_cache_get( 'snappy_admin_has_logged_in_recently', 'snappy' );

	return $has_admin_logged_in;
}

function snappy_is_active() {
	if ( function_exists( 'snappy_has_admin_loged_in_recently' ) ) {
		$result = snappy_has_admin_loged_in_recently();
	} else {
		$result = false;
	}

	return $result;
}


add_action( 'wp_ajax_snappy_cache_test', 'snappy_test_cache_action' );

for ( $i = 0; $i < 100; $i ++ ) {
	$action_name = 'wp_ajax_snappy_cache_test_' . $i;
	add_action( $action_name, 'snappy_test_cache_action' );
}

function snappy_test_cache_action() {

	if ( isset( $_REQUEST['set_this_value'] ) ) {
		wp_cache_set( 'snappy_cache_test_value', $_REQUEST['set_this_value'] );
		pbci_log( __FUNCTION__ . ' ' . microtime( true ) . ' set this value ' . $_REQUEST['set_this_value'] );
	}

	$test_result = $_REQUEST;

	if ( isset( $_REQUEST['test_index'] ) ) {
		$test_index                = intval( $_REQUEST['test_index'] );
		$test_result['test_index'] = $test_index;
		//pbci_log( 'test index is ' . $test_index );
	}

	$start            = microtime( true );
	$value_from_cache = wp_cache_get( 'snappy_cache_test_value' );
	$end              = microtime( true );

	$elapsed = number_format( ( $end - $start ) * 1000, 3 );

	$test_result['value_from_cache'] = ( $value_from_cache === false ) ? "FALSE" : $value_from_cache;

	pbci_log( __FUNCTION__ . ' ( in ' . $elapsed . ' ms ) got this value ' . $value_from_cache );

	//pbci_log( var_export( $test_result, true ) );

	wp_send_json_success( $test_result );

}


set_error_handler( 'snappy_fatal_handler', E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR );

register_shutdown_function( function () {
		$error = error_get_last();
		if ( ! empty( $error ) ) {
			if ( intval( $error['type'] ) & ( E_CORE_WARNING | E_WARNING ) ) {
				; // do nothing for warnings
			} else {
				snappy_fatal_handler( $error['type'], $error['message'], $error['file'], $error['line'] );
			}
		}
	}
);


function snappy_fatal_handler( $errno, $errstr, $errfile, $errline, $errcontext = null ) {
	$error_information = snappy_format_error( $errno, $errstr, $errfile, $errline );
	file_put_contents( WP_CONTENT_DIR . '/fatal-errors.log', $error_information, FILE_APPEND );
}

function snappy_format_error( $errno, $errstr, $errfile, $errline ) {
	$trace = snappy_get_backtrace();

	if ( function_exists( 'current_time' ) ) {
		$datestring = date( "Y-m-d-h-i-s", current_time( 'timestamp' ) );
	} else {
		$datestring = '';
	}

	$request_line = '';
	$request_line .= 'Fatal Error processing uri: ' . $_SERVER ['REQUEST_URI'];
	foreach ( $_REQUEST as $key => $value ) {
		if ( is_array( $value ) ) {
			$value = '(array)';
		}

		$request_line .= '   ' . $key . ' => "' . $value . '"';
	}

	$request_line .= "\n";

	$content = $datestring . "\n";
	$content .= $request_line;
	$content .= "Item\tDescription\n";
	$content .= "Error\t$errstr\n";
	$content .= "Errno\t$errno\n";
	$content .= "File\t$errfile\n";
	$content .= "Line\t$errline\n";
	$content .= "Trace\t$trace\n";

	$content .= "\n";
	$content .= '$_REQUEST' . "\n";
	$content .= var_export( $_REQUEST, true );

	$content .= "\n";

	return $content;
}


function snappy_get_backtrace() {
	$trace = '';
	try {
		ob_start();
		$backtraces = debug_backtrace();
		echo $prefix = '';
		echo "\n";
		foreach ( $backtraces as $trace ) {
			if ( strlen( $prefix ) > 2 ) {
				if ( isset ( $trace['file'] ) ) {
					$info = pathinfo( $trace['file'] );
					if ( isset ( $info['basename'] ) ) {
						$basename = $info['basename'];
					} else {
						$basename = 'n/a';
					}
					if ( isset ( $trace['line'] ) ) {
						$traceline = $trace['line'];
					} else {
						$traceline = 'n/a';
					}
					if ( isset ( $trace->function ) ) {
						$tracefunc = $trace->function;
					} else {
						$tracefunc = 'n/a';
					}
				} else {
					$basename  = 'n/a';
					$traceline = 'n/a';
					$tracefunc = 'n/a';
				}

				echo $prefix . $basename . ' at line ' . $traceline . ' function ' . $tracefunc . ' ';
				if ( isset( $trace['args'] ) ) {
					if ( is_array( $trace['args'] ) ) {
						$argstr = '';
						foreach ( $trace['args'] as $key => $value ) {
							if ( $argstr != '' ) {
								$argstr .= ', ';
							}

							if ( is_string( $value ) ) {
								$argstr .= "{$key}={$value}";
							} elseif ( is_object( $value ) ) {
								if ( isset( $trace['class'] ) ) {
									$argstr .= 'object=' . get_class( $value );
								}
							} else {
								$argstr = '?';
							}
						}

						if ( strlen( $argstr ) > 50 ) {
							$argstr = substr( $argstr, 0, 47 ) . '...';
						}

						echo '(' . $argstr . ')';
						echo "\n";
					}
				}
			}

			$prefix .= '.';
		}

		$trace = ob_get_clean();

	} catch ( Exception $e ) {
		;
	}

	return $trace;

}

function snappy_plugin_root_dir() {
	return trailingslashit( dirname( __FILE__ ) );
}


function snappy_plugin_show_admin_links() {

	if ( is_admin() ) {
		?>
		<table>
			<tr>
				<td style="width:40%">
					<ul class="pbci-admin-links">
						<li>
							<a href="<?php echo admin_url( 'admin.php' ); ?>?page=pbci_about_help_support">About</a>
						</li>
					</ul>
				</td>
				<td>
					<i>Before using any of the options on this page to make changes to your data, make sure you have a current database backup.</i>
				</td>
			</tr>
		</table>
	<?php
	}
}

function smappy_plugin_activate() {
	pbci_admin_nag( 'Welcome to WP-eCommerce Check-Up and Fix-Up by Pye Brook Company, Inc. To see the status of your WP-eCommerce store and WordPress configuration use the <b>Store Checkup</b> menu item on your dashbaord <b>Tools</b> menu.' );
}

register_activation_hook( __FILE__, 'smappy_plugin_activate' );


function snappy_get_url() {

	if ( ! isset( $_REQUEST['security'] ) ) {
		exit( 0 );
	}

	$security = SnappySite::get_security();

	if ( $security != $_REQUEST['security'] ) {
		exit ( 0 );
	}

	if ( isset( $_REQUEST['url'] ) ) {
		$url = $_REQUEST['url'];
	} else {
		exit ( 0 );
	}

	$start = microtime( true );
	$get_response = wp_remote_get( $url, array( 'timeout' => 30000, 'user-agent' => 'pbci-crawler' ) );
	$end = microtime( true );
	$result = array();
	$result['url'] = $url;
	if ( is_wp_error($get_response ) ) {
		$result['code'] = 0;
		$result['message'] = $get_response->get_error_message();
	} else {
		$result['code'] = intval( $get_response['response']['code'] );
		$result['message'] = intval( $get_response['response']['message'] );
	}

	$result['elapsed'] = $end - $start;

	wp_send_json_success( $result );
	exit( 0 );
}



add_action( "wp_ajax_snappy_get_url", 'snappy_get_url');
add_action( "wp_ajax_no_priv_snappy_get_url", 'snappy_get_url' );


/**
 * Updates permalink slugs
 *
 * @since 3.8.9
 * @return type
 */
function snappy_get_wpec_permalinks() {
	global $wpdb;

	$short_codes = array(
		'[productspage]' => '',
		'[shoppingcart]' => '',
		'[transactionresults]' => '',
		'[userlog]' => '',
	);

	$pages = array();

	foreach ( $short_codes as $page_string => $page_url ) {
		$id = $wpdb->get_var( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` = 'page' AND `post_content` LIKE '%$page_string%' LIMIT 1" );

		if ( ! $id ) {
			$the_new_link = '(missing)';
		} else {
			$the_new_link = _get_page_link( $id );
		}

		$the_new_link = str_replace( array( 'http://', 'https://' ) , '', $the_new_link );

		$pages[$page_string] = 'http://' . $the_new_link;
		$pages[$page_string . ' secure' ] = 'https://' . $the_new_link;

	}

	return $pages;

}
