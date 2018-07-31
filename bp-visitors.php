<?php
/*
Plugin Name: Millionaire's Digest Profile Visitors
Description: Give users the ability to see the people who visit and view their profile.
Version: 1.0.0
Author: K&L (Founder of the Millionaire's Digest)
Author URI: https://millionairedigest.com/

*/

// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

// define constants.
define( 'BP_VISITORS_DB_VERSION', 22 );

/**
 * Main Helper class
 */
class BP_Visitor_Helper {


	/**
	 * Singleton instance
	 *
	 * @var BP_Visitor_Helper
	 */
	private static $instance;


	/**
	 * Main Helper class
	 */
	private function __construct() {

		add_action( 'bp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'bp_loaded', array( $this, 'load' ), 0 );
		// load textdomain.
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

	}


	/**
	 * Get the singleton object
	 *
	 * @return BP_Visitor_Helper
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load core
	 */
	public function load() {

		$path = plugin_dir_path( __FILE__ );

		$files = array(
			'visitors-class.php',
			'visitors-functions.php',
			'visitors-shortcode.php',
			'visitors-actions.php',
			'visitors-component.php',
			'visitors-screens.php',
			'visitors-hooks.php',
			'visitors-template.php',
			'visitors-notifications.php',
			'visitors-widget.php',
		);


		if ( is_admin() ) {
			$files[] = 'admin/admin.php';
		}

		foreach ( $files as $file ) {
			require_once $path . $file;
		}
	}

	/**
	 * Load css
	 */
	public function load_assets() {
		$url = plugin_dir_url( __FILE__ );

		wp_register_style( 'lightslider', $url . 'assets/css/lightslider.min.css' );
		wp_register_style( 'visitors-css', $url . 'assets/css/visitors.css', array( 'lightslider' ) );

		wp_register_script( 'lightslider', $url . 'assets/js/lightslider.min.js' );
		wp_register_script( 'visitors-js', $url . 'assets/js/visitors.js', array( 'lightslider' ) );
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'recent-visitors-for-buddypress-profile', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add settings on plugin screen
	 *
	 * @param array $actions links to be shown in the plugin list context.
	 *
	 * @return array
	 */
	function plugin_action_links( $actions ) {

		$actions['view-settings'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', admin_url( 'options-general.php?page=bp-recent-visitors' ), __( 'Settings', 'recent-visitors-for-buddypress-profile' ), __( 'Settings', 'recent-visitors-for-buddypress-profile' ) );

		return $actions;
	}
}

BP_Visitor_Helper::get_instance();


/*************** For Installation***************/
/**
 * Check & Create Tables if needed.
 */
function visitors_check_installed() {

	if ( get_site_option( 'bp-visitors-db-version' ) < BP_VISITORS_DB_VERSION ) {
		visitors_install();
	}
}
add_action( 'admin_menu', 'visitors_check_installed' );
add_action( 'network_admin_menu', 'visitors_check_installed' );

/**
 * Create table
 */
function visitors_install() {
	global $wpdb, $bp;

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}
	$sql[] = "CREATE TABLE IF NOT EXISTS {$bp->visitors->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            visitor_id bigint(20) NOT NULL,
            visit_count bigint(20) NOT NULL,
            visit_time datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id,visitor_id)
            ){$charset_collate};";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	dbDelta( $sql );
	do_action( 'visitors_install' );

	update_site_option( 'bp-visitors-db-version', BP_VISITORS_DB_VERSION );
}
