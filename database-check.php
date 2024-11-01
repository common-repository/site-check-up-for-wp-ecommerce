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

function snappy_database_check() {
	global $wpdb;

	snappy_process_db_check_request();

	$option_count          = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options );
	$autoload_option_count = $wpdb->get_var( 'SELECT count(*) FROM ' . $wpdb->options . ' WHERE autoload = "yes"' );
	$autoload_option_size  = $wpdb->get_var( 'SELECT sum( length(option_value) ) FROM ' . $wpdb->options . ' WHERE autoload = "yes"' );

	$memcache_detected = extension_loaded( 'memcache' ) && class_exists( 'Memcache' );
	$apc_detected      = extension_loaded( 'apc' );

	$extensions_loaded = get_loaded_extensions();
	sort( $extensions_loaded );

	$wp_cache = defined( 'WP_CACHE' ) && WP_CACHE;

	$cache_busting_int = rand();

	$test_query_start = microtime( true );
	$test_query = new WP_Query( array( 'post_type' => 'any', 'orderby ' => 'date', 'order' => 'ASC', 'posts_per_page' => 10, 'cache_results' => false, 'post__not_in' => array( $cache_busting_int), ) );
	$test_query_end = microtime( true );

	$test_query_time_without_caching = number_format( 1000.0 * ($test_query_end - $test_query_start) , 1 );

	$cache_busting_int = rand();

	// prime the query cache
	$test_query = new WP_Query( array( 'post_type' => 'any', 'orderby ' => 'date', 'order' => 'ASC', 'posts_per_page' => 10, 'cache_results' => true, 'post__not_in' => array( $cache_busting_int), ) );

	$test_query_start = microtime( true );
	$test_query = new WP_Query( array( 'post_type' => 'any', 'orderby ' => 'date', 'order' => 'ASC', 'posts_per_page' => 10, 'cache_results' => true, 'post__not_in' => array( $cache_busting_int), ) );
	$test_query_end = microtime( true );

	$test_query_time_with_caching = number_format( 1000.0 * ($test_query_end - $test_query_start) , 1 );

	$orphaned_terms = snappy_count_orphaned_terms();

	?>
	<form method="post">
		<div class="wrap snappy-database-check">
			<?php pbci_plugin_page_title_box( 'WP-eCommerce Check-Up and Fix-Up', 'snappy' ); ?>
			<?php snappy_plugin_show_admin_links(); ?>
			<hr>

			<table class="snappy-status widefat">

			<tr class="snappy-header-row">
				<th colspan="3">
					WP-e-Commerce
				</th>
			</tr>

				<tr>
					<td>WPeC Products</td>
					<td><?php echo number_format( snappy_wpec_product_count() );?></td>
				</tr>

				<tr>
					<td>WPeC Variations</td>
					<td><?php echo  number_format( snappy_wpec_variation_count() );?></td>
				</tr>

				<tr>
					<td>Orphaned WPeC Variations</td>
					<td><?php echo  number_format( snappy_wpec_orphaned_variation_count() );?></td>
					<td  class="button-holder">
						<input type='submit' name='delete_orphaned_variations' value='Delete Orphans' class='button-secondary' />
					</td>
				</tr>

				<tr class="snappy-header-row">


					<th colspan="2">
						WPeC Pages Permalinks
					</th>
					<th  class="button-holder">
						<input type='submit' name='reset_wpec_permalinks' value='Reset Permalinks' class='button-secondary' />
					</th>

				</tr>

						<?php
						$pages = snappy_get_wpec_permalinks();
						foreach ( $pages as $key => $value ) {
							?><tr><?php
							?><td><?php echo esc_html( $key );?></td><?php
							?><td><?php echo esc_html( $value );?></td><?php
							?><td><div class="url-check-result url-ready-to-check" data-url="<?php echo esc_html( $value );?>">Ready to Check</div></td><?php
							?></tr><?php
						}
						?>


			<tr class="snappy-header-row">
				<th colspan="3">
					WordPress Options
				</th>
			</tr>

			<tr>
				<td>Options</td>
				<td><?php echo  number_format( $option_count );?></td>
			</tr>

			<tr>
				<td>Options with Autoload</td>
				<td><?php echo  number_format( $autoload_option_count );?></td>
			</tr>

			<tr>
				<td>Options with Autoload Total Size</td>
				<td><?php echo  number_format( $autoload_option_size );?></td>
			</tr>

				<tr class="snappy-header-row">

					<th colspan="2">
						WordPress Transients
					</th>
					<th class="button-holder">
						<input type='submit' name='delete_all_transients' value='Delete All Transients' class='button-secondary' />
					</th>

				</tr>


				<?php
				$transient_option_count = '0';
				$transient_option_timeout_count = '0';
				$site_transient_option_count = '0';
				$site_transient_option_timeout_count = '0';

				$transient_info = snappy_count_transients_in_options();
				extract( $transient_info );
				?>

			<tr>
				<td>
					All Transients in options table
				</td>
				<td>
					<?php echo  number_format( intval( $transient_option_count ) + intval( $site_transient_option_count ) );?>
				</td>
				<td>

				</td>
			</tr>


				<tr>
					<td>
						Transients
					</td>
					<td>
						<?php echo  number_format( $transient_option_count );?>
					</td>
					<td>
					</td>
				</tr>


				<tr>
					<td>
						Transients with timeouts
					</td>
					<td>
						<?php echo  number_format( $transient_option_timeout_count );?>
					</td>
					<td>
					</td>
				</tr>
				<tr>
					<td>
						Site Transients
					</td>
					<td>
						<?php echo  number_format( $site_transient_option_count );?>
					</td>
					<td>
					</td>
				</tr>
				<tr>
					<td>
						Site Transients with timeouts
					</td>
					<td>
						<?php echo  number_format( $site_transient_option_timeout_count );?>
					</td>
					<td>
					</td>
				</tr>


				<tr>
				<td>Expired Transients in option table</td>
				<td>
					<?php echo  number_format( snappy_count_expired_transients_in_options() );?>
				</td>
				<td class="button-holder">
					<input type='submit' name='delete_expired_transients' value='Delete Expired Transients' class='button-secondary' />
				</td>

			</tr>

			<tr class="snappy-header-row">
				<th colspan="3">
					WordPress Tables and Relationships
				</th>
			</tr>

			<tr>
				<td>
					Database Rows
				</td>
				<td>
					<?php echo  number_format( snappy_get_database_row_count() );?>
				</td>
				<td>

				</td>

			</tr>

			<tr>
				<td>
					Orphaned Post Meta
				</td>
				<td>
					<?php echo  number_format( snappy_count_orphaned_post_meta() );?>
				</td>
				<td class="button-holder">
					<input type='submit' name='delete_orphaned_post_meta' value='Delete Orphaned Post Meta' class='button-secondary' />
				</td>

			</tr>

			<tr>
				<td>
					Orphaned Taxonomy Terms
				</td>
				<td>
					<?php echo  number_format( $orphaned_terms );?>
				</td>
				<td class="button-holder">
					<input type='submit' name='delete_orphaned_taxonomy_meta' value='Delete Orphaned Taxonomy Meta' class='button-secondary' />
				</td>
			</tr>


			<tr>
			</tr>

			<tr class="snappy-header-row">
				<th colspan="3">
					Cache
				</th>
			</tr>

			<tr>
				<td>
					Cache Files
				</td>
				<td>
					<?php echo  number_format( snappy_count_cache_files() );?> files<br>
					in <?php echo WP_CONTENT_DIR;?>/cache
				</td>
				<td class="button-holder">
					<input type='submit' name='delete_page_cache_files' value='Delete Cache Files' class='button-secondary' />
				</td>

			</tr>

			<tr>
				<td>Wordpress Cache</td>
				<td><?php echo $wp_cache ? 'Yes' : 'No';?></td>
				<td class="button-holder">
					<input type='submit' name='flush_wordpress_cache' value='Flush WordPress Cache' class='button-secondary' />
				</td>


			</tr>

			<tr>
				<td>Memcache Detected</td>
				<td><?php echo $memcache_detected ? 'Yes' : 'No';?></td>
			</tr>

			<?php
			if ( $memcache_detected ) {
				$stats = snappy_get_memcache_stats();
				?>
				<tr>
					<td>Memcache Contents</td>
					<td>
						<?php echo  number_format( $stats->count );?> items<br>
						<?php echo  number_format( $stats->size );?> B
					</td>

					<td class="button-holder">
						<input type='submit' name='flush_memcache' value='Flush Memcache' class='button-secondary' />
					</td>

				</tr>
				<?php
			}
			?>


			<tr>
				<td>APC Detected</td><td><?php echo $apc_detected ? 'Yes' : 'No';?></td>
			</tr>

			<tr>
				<td>
					milliseconds to run test query without query caching
				</td>
				<td>
					<?php echo  number_format( $test_query_time_without_caching );?>
				</td>
			</tr>

			<tr>
				<td>milliseconds to run test query with query caching</td>
				<td><?php echo  number_format( $test_query_time_with_caching );?></td>
			</tr>

			<tr>
				<td>Test Object Cache</td>
				<td><span id="object_cache_test_result">Not Tested</span></td>
				<td class="button-holder">
					<input type='submit' id='object_cache_test' name='object_cache_test' value='Test WordPress Object Cache' class='button-secondary' />
				</td>
			</tr>

			<tr class="snappy-header-row">
				<th colspan="3">
					Platform
				</th>
			</tr>

			<tr>
				<td>
					PHP Version
				</td>
				<td>
					<?php echo  phpversion ();?>
				</td>
				<td>

				</td>

			</tr>

			<tr>
				<td>
					WordPress Version
				</td>
				<td>
					<?php echo  get_bloginfo( 'version' );?>
					&nbsp;(<?php echo  ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'WP_DEBUG mode is ON' : ' WP_DEBUG mode is off' );?>)
				</td>
				<td>

				</td>
			</tr>

				<tr>
					<td>
						WP-eCommerce Version
					</td>
					<td>
						<?php
						if ( defined( 'WPSC_PRESENTABLE_VERSION' ) ) {
							$version = WPSC_PRESENTABLE_VERSION;
						} else {
							$version = 'WPSC_PRESENTABLE_VERSION is not defined?';;
						}

						?>
						<?php echo esc_html( $version );?>
					</td>
					<td>

					</td>
				</tr>
			</table>

		</div>
	</form>
	<?php
}

