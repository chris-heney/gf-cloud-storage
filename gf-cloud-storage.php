<?php

/*
Plugin Name: Gravity Forms Cloud Storage
Plugin URI: https://expertoverflow.com
Description: A Gravity Forms feed addon to store gravity forms entries to cloud storage service providers like NextCloud.
Version: 1.0.1
Requires at least: 4.0
Requires PHP: 7.4
Author: Chris Heney
Author URI: https://chrisheney.com
Text Domain: gf_cloudstorage

------------------------------------------------------------------------
Copyright 2009-2022 Expert Overflow, LLC
*/

define( 'GF_CLOUD_STORAGE_VERSION', '2.0' );

add_action( 'gform_loaded', array( 'GF_Cloud_Storage_Bootstrap', 'load' ), 5 );

class GF_Cloud_Storage_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-roadie.php' );

		GFAddOn::register( 'GFCloudStorage' );
	}

}

function gf_cloud_storage() {
	return GFCloudStorage::get_instance();
}
