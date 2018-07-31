<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional Visitor Component, enabled conditionally.
 */
class BP_Visitors_Component extends BP_Component {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::start(
			'visitors',
			__( 'Recent Visitors', 'recent-visitors-for-buddypress-profile' ),
			plugin_dir_path( __FILE__ ),
			array(
				'adminbar_myaccount_order' => 90,
			)
		);
		buddypress()->active_components[ $this->id ] = 1;

		add_action( 'bp_settings_setup_nav', array( $this, 'setup_settings_nav' ) );

	}


	/**
	 * Include files
	 *
	 * @param array $includes files to be included.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'screens',
		);

		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * @param array $args global args.
	 */
	public function setup_globals( $args = array() ) {

		// Define a slug, if necessary.
		if ( ! defined( 'BP_VISITORS_SLUG' ) ) {
			define( 'BP_VISITORS_SLUG', $this->id );
		}

		// Global tables for visitors component.
		$global_tables = array(
			'table_name' => bp_core_get_table_prefix() . 'bp_recent_visitors',

		);

		// All globals for visitors component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BP_VISITORS_SLUG,
			'root_slug'             => BP_VISITORS_SLUG,
			'has_directory'         => false,
			'notification_callback' => 'bp_visitors_format_notifications',
			'global_tables'         => $global_tables,
		);

		parent::setup_globals( $args );
	}

	/**
	 * Setup BuddyBar navigation
	 *
	 * @param array $main_nav main nav.
	 * @param array $sub_nav sub nav.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// $user_id = bp_is_user() ? bp_displayed_user_id() : bp_loggedin_user_id();

		//$is_enabled = bp_is_my_profile() && visitor_is_enabled_for_user( bp_displayed_user_id() );

		$show = ( is_super_admin()|| bp_is_my_profile() ) && visitor_is_enabled_for_user( bp_displayed_user_id() );//( $is_enabled || is_super_admin() );

		if ( ! $show ) {
			return ;
		}
		// Add 'Visitors' to the main navigation.
		$main_nav = array(
			'name'                    => sprintf( __( 'Visitors <span>%s</span>', 'recent-visitors-for-buddypress-profile' ), visitors_get_unique_visitors_count() ),
			'slug'                    => $this->slug,
			'position'                => 70,
			'screen_function'         => 'visitors_screen_my_visitors',
			'default_subnav_slug'     => 'my-visitors',
			'item_css_id'             => $this->id,
			'show_for_displayed_user' => $show,
		);

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}


		$visitors_link = trailingslashit( $user_domain . $this->slug );
		// Add the subnav items to the friends nav item.
		$sub_nav[] = array(
			'name'            => __( 'All time', 'recent-visitors-for-buddypress-profile' ),
			'slug'            => 'my-visitors',
			'parent_url'      => $visitors_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'visitors_screen_my_visitors',
			'position'        => 10,
			'item_css_id'     => 'visitors_screen_my_visitors',
			'user_has_access' => $show,
		);

		// Add the subnav items to the friends nav item.
		$sub_nav[] = array(
			'name'            => __( '7 Days', 'recent-visitors-for-buddypress-profile' ),
			'slug'            => '7days',
			'parent_url'      => $visitors_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'visitors_screen_my_visitors',
			'position'        => 20,
			'item_css_id'     => 'visitors_screen_my_visitors',
			'user_has_access' => $show,
		);

		$sub_nav[] = array(
			'name'            => __( '30 Days', 'recent-visitors-for-buddypress-profile' ),
			'slug'            => '30days',
			'parent_url'      => $visitors_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'visitors_screen_my_visitors',
			'position'        => 30,
			'item_css_id'     => 'visitors_screen_my_visitors',
			'user_has_access' => $show,
		);

		// if visitor settings calls for a new Nav to be added to BuddyPress.
		if ( visitors_get_setting( 'add_screen' ) ) {
			parent::setup_nav( $main_nav, $sub_nav );
		}
	}

	/**
	 * Setup settings nav.
	 */
	public function setup_settings_nav() {

		if ( ! visitors_get_setting( 'allow_user_settings' ) || ! visitor_is_enabled_for_user( bp_loggedin_user_id() ) ) {
			return;
		}

		$bp            = buddypress();
		$settings_link = bp_displayed_user_domain() . bp_get_settings_slug() . '/';

		bp_core_new_subnav_item(
			array(
				'name'            => __( 'Profile Visitors', 'recent-visitors-for-buddypress-profile' ),
				'slug'            => 'visitors',
				'parent_url'      => $settings_link,
				'parent_slug'     => $bp->settings->slug,
				'screen_function' => 'bp_visitors_screen_general_settings',
				'position'        => 40,
				'user_has_access' => is_super_admin() || bp_is_my_profile(),
			) );
	}

	/**
	 * Set up the WP Toolbar
	 *
	 * @param array $wp_admin_nav itms to be added to the admin nav.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		$bp = buddypress();

		if ( ! visitors_get_setting( 'allow_user_settings' ) || ! visitor_is_enabled_for_user( bp_loggedin_user_id() ) ) {
			return;
		}

		// Menus for logged in user.
		if ( is_user_logged_in() && bp_is_active( 'settings' ) ) {
			// Setup the logged in user variables.
			$user_domain   = bp_loggedin_user_domain();
			$visitors_link = trailingslashit( $user_domain . $this->slug );

			if ( visitors_get_setting( 'add_screen' ) ) {
				// Pending group invites.
				$count = visitors_get_unique_visitors_count( bp_loggedin_user_id() );
				$title = __( 'Visitors', 'recent-visitors-for-buddypress-profile' );

				if ( ! empty( $count ) ) {
					$title = sprintf( __( 'Visitors <span class="count">%s</span>', 'recent-visitors-for-buddypress-profile' ), $count );
				}

				// Add the "My Account" sub menus.
				$wp_admin_nav[] = array(
					'parent' => $bp->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => $title,
					'href'   => trailingslashit( $visitors_link ),
				);

				// Sunbanv items.
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-my-visitors',
					'title'  => __( 'All time', 'recent-visitors-for-buddypress-profile' ),
					'href'   => trailingslashit( $visitors_link . 'my-visitors' ),
				);
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-7days',
					'title'  => __( '7 Days', 'recent-visitors-for-buddypress-profile' ),
					'href'   => trailingslashit( $visitors_link . '7days' ),
				);
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-30days',
					'title'  => __( '30 Days', 'recent-visitors-for-buddypress-profile' ),
					'href'   => trailingslashit( $visitors_link . '30days' ),
				);
			}

			$settings_link = trailingslashit( $user_domain . bp_get_settings_slug() );

			// Add the "My Account" sub menus.
			$wp_admin_nav[] = array(
				'parent' => 'my-account-settings',
				'id'     => 'my-visitors',
				'title'  => __( 'Recent Visitors', 'recent-visitors-for-buddypress-profile' ),
				'href'   => trailingslashit( $settings_link . 'visitors' ),
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Sets up the title for pages and <title>
	 */
	public function setup_title() {
		$bp = buddypress();

		if ( bp_is_user() && bp_is_current_component( $this->id ) ) {
			$bp->bp_options_title = __( 'Recent Visitors', 'recent-visitors-for-buddypress-profile' );
		}

		parent::setup_title();
	}
}

/**
 * Setup component
 */
function bp_setup_visitors() {
	buddypress()->visitors = new BP_Visitors_Component();
}
add_action( 'bp_setup_components', 'bp_setup_visitors', 6 );
