<?php
/**
 * This should contain all functions related to upgrading/updating the plugin.
 */

add_action( 'admin_post_aioseop_upgrade', 'upgrade');
function upgrade() {
	// TODO: need a URL that will give the version number and the URL to download the zip from
	// will use that to populate the $temp_array
	$plugin_transient = get_site_transient( 'update_plugins' );
	$temp_array       = array(
		'slug'        => 'aioseop-pro',
		'new_version' => '1.1.1.1',
		'package'     => 'http://localhost/aioseop-pro.zip'
	);

	$temp_object		= (object) $temp_array;
	$plugin_transient->response[ 'aioseop-pro/all_in_one_seo_pack.php' ] = $temp_object;
	set_site_transient( 'update_plugins', $plugin_transient );

	$transient = get_transient( 'aioseop-pro_warning' );

	if ( false === $transient ) {
		set_transient( 'aioseop-pro_warning', 'in progress', 30 );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		$title         = 'Install';
		$plugin        = 'aioseop-pro/all_in_one_seo_pack.php';
		$nonce         = 'upgrade-plugin_' . $plugin;
		$url           = 'update.php?action=upgrade-plugin&plugin=' . urlencode( $plugin );
		$upgrader_skin = new Plugin_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'plugin' ) );
		$upgrader      = new Plugin_Upgrader( $upgrader_skin );
		$upgrader->upgrade( $plugin );
		delete_transient( 'aioseop-pro_warning' );
		wp_die(
			'', $title, array(
				'response' => 200,
			)
		);
	}
}