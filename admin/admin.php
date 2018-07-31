<?php
// we use options buddy.
require_once dirname( __FILE__ ) . '/options-buddy/ob-loader.php';

/**
 * Admin settings builder class.
 */
class BP_Recent_Visitors_Admin {
	/**
	 * Page object.
	 *
	 * @var OptionsBuddy_Settings_Page
	 */
	private $page;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// create a options page
		// make sure to read the code below.
		$this->page = new OptionsBuddy_Settings_Page( 'bp_recent_visitors_settings' );
		// make it to use bp_get_option/bp_update_option.
		$this->page->set_bp_mode();


		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_footer', array( $this, 'admin_css' ) );
	}

	/**
	 * Initialize.
	 */
	public function admin_init() {

		// set the settings.
		$page = $this->page;

		$editable_roles = get_editable_roles();

		$roles = array();

		foreach ( $editable_roles as $key => $role ) {
			$roles[ $key ] = $role['name'];
		}
		// add_section
		// you can pass section_id, section_title, section_description, the section id must be unique for this page, section descriptiopn is optional.
		$page->add_section( 'basic_section', __( 'General', 'recent-visitors-for-buddypress-profile' ), __( 'Recent Visitors settings.', 'recent-visitors-for-buddypress-profile' ) );
		$page->add_section( 'default_section', __( 'Default', 'recent-visitors-for-buddypress-profile' ), __( 'Default settings for users.', 'recent-visitors-for-buddypress-profile' ) );


		$page->get_section( 'basic_section' )->add_fields( array(
			array(
				'name'    => 'roles_excluded',
				'label'   => __( 'Disable for roles? ', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Recent visitors component will be disabled/unavailable for the selected roles.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'multicheck',
				'default' => '',
				'options' => $roles,
			),
			array(
				'name'    => 'recording_roles_excluded',
				'label'   => __( 'Exclude roles from being recorded? ', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Visits by user belonging to selected roles will not be recorded.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'multicheck',
				'default' => '',
				'options' => $roles,

			),
			array(
				'name'    => 'recording_policy',
				'label'   => __( 'Recording Policy', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'How the visits should be recorded.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 'mutual',
				'options' => array(
					'mutual' => __( 'Mutual: Only record when Both the visiting and visitors settings are on.', 'recent-visitors-for-buddypress-profile' ),
					'always' => __( 'Always: Always record based on the setting of the profile being visited. Do not check for visitors settings.', 'recent-visitors-for-buddypress-profile' ),
				),

			),
			array(
				'name'    => 'show_in_directory',
				'label'   => __( 'Show recent visitors in members directory?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Logged in users will see recent visitors tab.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 1,
				'options' => array(
					1 => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					0 => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),

			array(
				'name'    => 'show_in_header',
				'label'   => __( 'Show in member header?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Users will see the recent visitors on their profile.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 1,
				'options' => array(
					1 => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					0 => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),

            array(
				'name'    => 'show_inside_header_meta',
				'label'   => __( 'Show inside header meta?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Please enable it if the listing is breaking your layout.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 0,
				'options' => array(
					1 => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					0 => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),

			array(
				'name'    => 'max_display_in_header',
				'label'   => __( 'How many visitors to display in header?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => '',
				'type'    => 'text',
				'default' => 5,
			),
			array(
				'name'    => 'default_avatar_size',
				'label'   => __( 'Visitor avatar size in header', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => '',
				'type'    => 'text',
				'default' => 32,
			),


			array(
				'name'    => 'add_screen',
				'label'   => __( 'Show a profile tab?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Users will be able to see their recent visitors for 7days, 30 days and all time.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 1,
				'options' => array(
					1 => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					0 => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),
			array(
				'name'    => 'max_display',
				'label'   => __( 'How many visitors to display in profile tab?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Pagination is not supported at the moment.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'text',
				'default' => 20,
			),

			array(
				'name'    => 'allow_user_settings',
				'label'   => __( 'Allow users to change their preference?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Users can enable/disable the recording if it is enabled.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 1,
				'options' => array(
					1 => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					0 => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),

			),

			array(
				'name'    => 'notification_local',
				'label'   => __( 'Enable local notification?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'The user will be able to override it from their notifications page.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 'no',
				'options' => array(
					'yes' => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					'no'  => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),
			array(
				'name'    => 'notification_by_email',
				'label'   => __( 'Email Notification?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'The user will be able to override it from their notifications page.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 'no',
				'options' => array(
					'yes' => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					'no'  => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),


		) );
		// add fields.
		$page->get_section( 'default_section' )->add_fields( array(
			array(
				'name'    => 'notify_visitors_locally',
				'label'   => __( 'Notify Locally on new visit?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'Will add a site notification for the visited user.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 'no',
				'options' => array(
					'yes' => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					'no'  => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),
			array(
				'name'    => 'notify_visitors_by_email',
				'label'   => __( 'Email Notification on new Visit?', 'recent-visitors-for-buddypress-profile' ),
				'desc'    => __( 'The user whose profile is being visited will get an email notification.', 'recent-visitors-for-buddypress-profile' ),
				'type'    => 'radio',
				'default' => 'no',
				'options' => array(
					'yes' => __( 'Yes.', 'recent-visitors-for-buddypress-profile' ),
					'no'  => __( 'No.', 'recent-visitors-for-buddypress-profile' ),
				),
			),

		) );


		$page->init();

	}

	/**
	 * Add admin Menu
	 */
	public function admin_menu() {
		add_options_page( __( 'Profile Visitors', 'recent-visitors-for-buddypress-profile' ), __( 'Profile Visitors', 'recent-visitors-for-buddypress-profile' ), 'manage_options', 'bp-recent-visitors', array(
			$this->page,
			'render',
		) );
	}


	/**
	 * Returns all the settings fields
	 */
	public function admin_css() {

		if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'bp-recent-visitors' ) {
			return;
		}

		?>

        <style type="text/css">
            .wrap .form-table {
                margin: 10px;
            }

        </style>

		<?php

	}


}

new BP_Recent_Visitors_Admin();
