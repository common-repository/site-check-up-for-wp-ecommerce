<?php
/*
** Copyright 2010-2014, Pye Brook Company, Inc.
**
** Licensed under the Pye Brook Company, Inc. License, Version 1.0 (the "License");
** you may not use this file except in compliance with the License.
** You may obtain a copy of the License at
**
**     http://www.pyebrook.com/
**
** This software is not free may not be distributed, and should not be shared.  It is governed by the
** license included in its original distribution (license.pdf and/or license.txt) and by the
** license found at www.pyebrook.com.
*
** This software is copyrighted and the property of Pye Brook Company, Inc.
**
** See the License for the specific language governing permissions and
** limitations under the License.
**
** Contact Pye Brook Company, Inc. at info@pyebrook.com for more information.
*/


function snappy_update_permalink_slugs() {
	wpsc_update_permalink_slugs();
}

function snappy_do_ajax( $action, $args = null ) {

	$body = ! empty ( $args ) ? $args : array();

	$body['action'] = $action;
	$body['security'] = wp_create_nonce( $body['action'] );

	$url = admin_url( 'admin-ajax.php' );

	$response = wp_remote_post(
		$url,
		array(
			'method'      => 'POST',
			'timeout'     => 15,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $body,
			'cookies'     => array(),
		)
	);

	if ( is_wp_error( $response ) ) {
		$result = false;
		pbci_log ( $response->get_error_message() );
	} else {
		$result = true;
	}

	return $result;
}

function snappy_count_orphaned_terms() {
	global $wpdb;

	$sql = 'SELECT COUNT(*) '
	       . ' FROM ' . $wpdb->term_relationships . ' tr '
	       . ' INNER JOIN ' . $wpdb->term_taxonomy . '  tt ON( tr.term_taxonomy_id = tt.term_taxonomy_id ) '
	       . ' WHERE tt.taxonomy != "link_category" '
	       . ' AND tr.object_id NOT IN( SELECT ID FROM ' . $wpdb->posts . ' ) ';


	$count = $wpdb->get_var( $sql );

	if ( $count == NULL ) {
		$count = 0;
	}

	return $count;
}

function snappy_delete_orphaned_terms() {
	global $wpdb;

	$sql = 'DELETE tr '
	       . ' FROM ' . $wpdb->term_relationships . ' tr '
	       . ' INNER JOIN ' . $wpdb->term_taxonomy . '  tt ON( tr.term_taxonomy_id = tt.term_taxonomy_id ) '
	       . ' WHERE tt.taxonomy != "link_category" '
	       . ' AND tr.object_id NOT IN( SELECT ID FROM ' . $wpdb->posts . ' ) ';

	// pbci_log( __FUNCTION__ . ' ' . $sql );

	$count = $wpdb->get_var( $sql );

	if ( $count == NULL ) {
		$count = 0;
	}

	return $count;
}

function snappy_count_orphaned_post_meta() {
	global $wpdb;

	$sql = 'SELECT COUNT(*) FROM ' . $wpdb->postmeta . ' pm LEFT JOIN ' . $wpdb->posts . ' wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL';

	$count = $wpdb->get_var( $sql );

	if ( $count == NULL ) {
		$count = 0;
	}

	return $count;
}


function snappy_delete_orphaned_post_meta() {
	global $wpdb;

	$sql = 'DELETE pm FROM ' . $wpdb->postmeta . ' pm LEFT JOIN ' . $wpdb->posts . ' wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL';

	// pbci_log( __FUNCTION__ . ' ' . $sql );
	$count = $wpdb->get_var( $sql );

	if ( $count == NULL ) {
		$count = 0;
	}

	return $count;
}

function snappy_count_cache_files( $cache_file_directory = '' ) {
	$count = 0;

	if ( empty( $cache_file_directory ) ) {
		$cache_file_directory = WP_CONTENT_DIR . '/cache';
		$count = 0;
	}

	foreach( glob("{$cache_file_directory}/*") as $fn ) {
		if ( is_dir( $fn ) ) {
			$count += snappy_count_cache_files( $fn );
		} else {
			$count++;
		}
	}

	return $count;
}

function snappy_delete_cache_files( $cache_file_directory = '' ) {
	$count = 0;

	if ( empty( $cache_file_directory ) ) {
		$cache_file_directory = WP_CONTENT_DIR . '/cache';
		$count = 0;
	}

	foreach( glob("{$cache_file_directory}/*") as $fn ) {
		if ( is_dir( $fn ) ) {
			$count += snappy_delete_cache_files( $fn );
		} else {
			$count++;
			unlink( $fn );
			pbci_log( 'deleting cache file: ' . $fn );
		}
	}

	return $count;
}

function snappy_count_transients_in_options() {
	global $wpdb;
	$transient_option_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE option_name like "\_transient_%"' );
	$transient_option_timeout_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE option_name like "\_transient_timeout%"' );

	$site_transient_option_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE option_name like "\_site_transient_%"' );
	$site_transient_option_timeout_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE option_name like "\_site_transient_timeout%"' );

	$results = array(
		'transient_options' => $transient_option_count,
		'transient_option_timeout_count' => $transient_option_timeout_count,
		'site_transient_option_count' => $site_transient_option_count,
		'site_transient_option_timeout_count' => $site_transient_option_timeout_count,
	);

	return $results;
}

function snappy_delete_transients_in_options() {
	global $wpdb;
	$transient_options = $wpdb->get_col( 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name like "\_transient_%"' );
	foreach ( $transient_options as $index => $transient_option_name ) {
		$transient_name = str_replace('_transient_', '', $transient_option_name );
		delete_option( '_transient_timeout_' . $transient_name );
		delete_option( '_transient_' . $transient_name );
		pbci_log( __FUNCTION__ . ' ' . $transient_name );
	}


	$transient_options = $wpdb->get_col( 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name like "\_site_transient_%"' );
	foreach ( $transient_options as $index => $transient_option_name ) {
		$transient_name = str_replace('_site_transient_', '', $transient_option_name );
		delete_option( '_site_transient_timeout_' . $transient_name );
		delete_option( '_site_transient_' . $transient_name );
		pbci_log( __FUNCTION__ . ' ' . $transient_name );
	}

	return count( $transient_options );
}


function snappy_count_expired_transients_in_options() {
	global $wpdb;
	$transient_option_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE option_name like "\_transient_timeout%"  AND option_value < ' . time() );
	$site_transient_option_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE option_name like "\_site_transient_timeout%"  AND option_value < ' . time() );
	return $transient_option_count + $site_transient_option_count;
}

function snappy_delete_expired_transients_in_options() {
	global $wpdb;
	$transient_options = $wpdb->get_col( 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name like "\_transient_timeout\_%"  AND option_value < ' . time() );
	foreach ( $transient_options as $index => $transient_option_name ) {
		$transient_name = str_replace('_transient_timeout_', '', $transient_option_name );
		delete_option( '_transient_timeout_' . $transient_name );
		delete_option( '_transient_' . $transient_name );

		pbci_log( __FUNCTION__ . ' ' . $transient_name );
	}

	$site_transient_options = $wpdb->get_col( 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name like "\_site_transient_timeout\_%"  AND option_value < ' . time() );
	foreach ( $site_transient_options as $index => $transient_option_name ) {
		$transient_name = str_replace('_site_transient_timeout_', '', $transient_option_name );
		delete_option( '_site_transient_timeout_' . $transient_name );
		delete_option( '_site_transient_' . $transient_name );

		pbci_log( __FUNCTION__ . ' ' . $transient_name );
	}

	return count( $transient_options ) + count( $site_transient_options );
}

function snappy_get_memcache_stats() {
	$count = 0;
	$size = 0;
	if ( ! class_exists( 'Memcache' ) ) {
		echo 'MEMCACHE class not found<br>';
	} else {

		$memcache = new Memcache;
		$memcache->connect('127.0.0.1', 11211) or die ("Could not connect to memcache server");

		$allSlabs = $memcache->getExtendedStats('slabs');
		foreach ( $allSlabs as $server => $slabs ) {
			foreach ( $slabs as $slabId => $slabMeta ) {
				$cdump = $memcache->getExtendedStats('cachedump',(int)$slabId);
				if ( $cdump != false ) {
					foreach( $cdump as $keys => $arrVal ) {
						if ( is_array( $arrVal ) ) {
							foreach( $arrVal as $k => $v ) {
								$count ++;
								$size += snappy_estimate_size( $v );
							}
						}
					}
				} else {
					; //echo 'slabId '.$slabId.' was false?';
				}

			}
		}
	}

	$stats = new stdClass();
	$stats->count = $count;
	$stats->size = $size;

	return $stats;
}


function snappy_flush_memcache() {

	/* procedural API */
	$memcache_obj = memcache_connect( 'memcache_host', 11211 );

	memcache_flush( $memcache_obj );

	/* OO API */

	$memcache_obj = new Memcache;
	$memcache_obj->connect( 'memcache_host', 11211 );

	$memcache_obj->flush();
}


function snappy_estimate_size( $something ) {
	$tmp = serialize( $something );
	return strlen( $tmp );
}

function snappy_get_database_row_count() {
	global $wpdb;

	$sql = 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND table_name LIKE "' . esc_sql( $wpdb->prefix ) . '%"';
	$table_names = $wpdb->get_col( $sql );

	$total_row_count = 0;
	foreach ( $table_names as $table_name ) {
		$sql = 'SELECT count(*) FROM ' . $table_name;
		// pbci_log( $sql );
		$table_row_count = $wpdb->get_var( $sql );
		$total_row_count += intval( $table_row_count );
	}

	return $total_row_count;
}




function snappy_wpec_product_count() {
	global $wpdb;

	$sql = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type = "wpsc-product" AND post_parent = 0';
	$product_ids = $wpdb->get_col( $sql );
	$count = count( $product_ids );

	// pbci_log( $sql );
	return $count;
}


function snappy_wpec_variation_count() {
	global $wpdb;

	$sql = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type = "wpsc-product" AND post_parent <> 0 AND post_status = "inherit"';
	$product_ids = $wpdb->get_col( $sql );
	$count = count( $product_ids );

	// pbci_log( $sql );
	return $count;

}


function snappy_wpec_orphaned_variation_count() {
	global $wpdb;

	$parent_sql = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type = "wpsc-product" AND post_parent = 0 AND post_status <> "inherit"';
	$sql = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type = "wpsc-product" AND post_parent NOT IN ('. $parent_sql . ') AND post_status = "inherit"';
	$product_ids = $wpdb->get_col( $sql );
	$count = count( $product_ids );

	// pbci_log( $sql );
	return $count;
}

function snappy_delete_wpec_orphaned_variation() {
	global $wpdb;

	$parent_sql = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type = "wpsc-product" AND post_parent = 0 AND post_status <> "inherit"';
	$sql = 'SELECT ID from ' . $wpdb->posts . ' WHERE post_type = "wpsc-product" AND post_parent NOT IN ('. $parent_sql . ') AND post_status = "inherit"';
	$product_ids = $wpdb->get_col( $sql );
	$count = count( $product_ids );

	// pbci_log( 'query return ' . $count  . ' records' );
	// pbci_log( $sql );

	foreach ( $product_ids as $index => $product_id ) {
		pbci_log( 'Product ' . get_the_title( $product_id ) . ' (' . $product_id . ') is being deleted  ' . $index . ' of ' . $count );
		wp_delete_post( $product_id, true );

		set_time_limit( 0 );
	}


	// pbci_log( $sql );
	return $count;
}

function snappy_flush_wordpress_cache() {
	wp_cache_flush();
}

function snappy_process_db_check_request() {

	//pbci_log( __FUNCTION__ . '   ' . var_export( $_POST, true ) );
	$count = 0;

	if ( isset( $_POST['reset_wpec_permalinks'] ) ) {
		$count = snappy_update_permalink_slugs();
	}

	if ( isset( $_POST['delete_orphaned_variations'] ) ) {
		$count = snappy_delete_wpec_orphaned_variation();
	}

	if ( isset( $_POST['delete_orphaned_post_meta'] ) ) {
		$count = snappy_delete_orphaned_post_meta();
	}

	if ( isset( $_POST['delete_orphaned_taxonomy_meta'] ) ) {
		$count = snappy_delete_orphaned_terms();
	}

	if ( isset( $_POST['delete_page_cache_files'] ) ) {
		$count = snappy_delete_cache_files();
	}

	if ( isset( $_POST['flush_wordpress_cache'] ) ) {
		$count = snappy_flush_wordpress_cache();
	}

	if ( isset( $_POST['flush_memcache'] ) ) {
		$count = snappy_flush_memcache();
	}

	if ( isset( $_POST['delete_all_transients'] ) ) {
		$count = snappy_delete_transients_in_options();
	}

	if ( isset( $_POST['delete_expired_transients'] ) ) {
		$count = snappy_delete_expired_transients_in_options();
	}



	return $count;
}

