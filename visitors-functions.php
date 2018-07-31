<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get a particular setting
 *
 * @param string $option_name name of the option.
 *
 * @return mixed
 */
function visitors_get_setting( $option_name, $default_val = '' ) {

	$default = array(
		'max_display'              => 20, // User->Visitors component page.
		'max_display_in_header'    => 5,
		'add_screen'               => true,
		'show_in_header'           => true,
		'show_inside_header_meta'  => 0,
		'show_in_directory'        => true,
		'notification_local'       => true,
		'notification_by_email'    => true,
		'notify_visitors_locally'  => 'no',
		'notify_visitors_by_email' => 'no',
		'default_avatar_size'      => 32,
		'allow_user_settings'      => true,
		// Do not record visits by these roles.
		'recording_roles_excluded' => array(),
		// Disable the plugin feature for these roles.
		'roles_excluded'           => array(),
		// Recording policy.
		// 'always'= For recording visit of the user_id, do not check whether they have it enabled or not.
		// 'mutual'= only record if enabled by the visitor.
		'recording_policy'         => 'mutual',
	);

	$settings = bp_get_option( 'bp_recent_visitors_settings', $default );

	$settings = apply_filters( 'bp_visitors_settings', $settings );

	if ( isset( $settings[ $option_name ] ) ) {
		return $settings[ $option_name ];
	}

	return $default_val;
}

/**
 * Check if the current visit should be recorded?
 *
 * @param int $displayed_user_id visited user id.
 * @param int $visitor_id visiting user id.
 *
 * @return bool
 */
function visitors_can_record_visit( $displayed_user_id, $visitor_id ) {
	if ( ! $displayed_user_id || ! $visitor_id ) {
		return false;
	}

	if ( ! visitor_is_enabled_for_user( $displayed_user_id ) ) {
		return false;
	}

	// displayed user has disaled the recording.
	if ( ! visitors_is_active_visitor_recording( $displayed_user_id ) ) {
		return false;
	}

	if ( ! visitor_is_visit_recordable( $visitor_id ) ) {
		return false;
	}

	return true;
}

/**
 * Can we record the visit by the given user?
 *
 * @param int $user_id does the give user's preference allow recording.
 *
 * @return bool
 */
function visitor_is_visit_recordable( $user_id ) {

	$is_enabled = true;
	
	$loggedin_user_id = bp_loggedin_user_id();
    $my_user = ( $loggedin_user_id ) ? $loggedin_user_id : bp_get_member_user_id();
    $member_type = bp_get_member_type( $my_user );
	
	//Don't record any users if they have one of the following member types
    $in = array( 'brand', 'famous-person', 'organization', 'government' );
	if ( ! in_array( $member_type, $in, false ) ) {
		return $user_id;
    }
	return false;

	if ( ! $user_id ) {
		$is_enabled = false;
	} else {

		$excluded_roles = visitors_get_setting( 'recording_roles_excluded' , array());
		foreach ( $excluded_roles as $role ) {
			// Is this role excluded from recording?
			if ( visitors_has_role( $user_id, $role ) ) {
				$is_enabled = false;
				break;
			}
		}
		// if the role is not excluded, check user preference.
		if ( $is_enabled ) {
			$policy = visitors_get_setting( 'recording_policy' );
			if ( 'mutual' === $policy && ! visitors_is_active_visitor_recording( $user_id ) ) {
				$is_enabled = false;
			}
		}
	}

	return apply_filters( 'visitor_is_visit_recordable', $is_enabled, $user_id );
}

/**
 * Is visitor component and features are enabled for the given user
 *
 * @param int $user_id Numeric user id to check.
 *
 * @return bool
 */
function visitor_is_enabled_for_user( $user_id ) {

	$is_enabled = true; // by default always enabled.

	if ( ! $user_id ) {
		$is_enabled = false; // user id must be given.
	} else {
		// user id is given.
		$excluded_roles = visitors_get_setting( 'roles_excluded', array() );

		foreach ( $excluded_roles as $role ) {
			// Is this role excluded from recording?
			if ( visitors_has_role( $user_id, $role ) ) {
				$is_enabled = false;
				break;
			}
		}

	}

	return apply_filters( 'visitor_is_enabled_for_user', $is_enabled, $user_id );
}

/**
 * Check whether the profile visit recording is active for user or not
 *
 * @param int $user_id numeric user id.
 *
 * @return mixed
 */
function visitors_is_active_visitor_recording( $user_id ) {
	return visitors_get_preferences( $user_id ); // get the user preference.
}

/**
 * Get visitors preference, Has user enabled the recording?
 *
 * @param int $user_id numeric user id.
 *
 * @return bool
 */
function visitors_get_preferences( $user_id ) {

	$prefs = bp_get_user_meta( $user_id, 'visitor_recording', true );

	if ( empty( $prefs ) ) {
		return true;
	} else {
		return bool_from_yn( $prefs );
	}

}

/**
 * Get total visits to a profile, it does not count the unique visitors, but the total visits
 *
 * @param int $user_id numric user id.
 *
 * @return int
 */
function visitors_get_profile_visit_count( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$visits_count = bp_get_user_meta( $user_id, 'profile_visits_count', true );

	return intval( $visits_count );
}

/**
 * Get how many unique visits a profile has received.
 *
 * @param int $user_id numeric user id.
 *
 * @return int
 */
function visitors_get_unique_visitors_count( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$unique_visitors_count = bp_get_user_meta( $user_id, 'unique_profile_visitors', true );

	return intval( $unique_visitors_count );
}

/**
 * Get the recent visitors for the given user
 *
 * @param int $user_id numeric user id.
 * @param int $count how many.
 * @param int $duration how many days.
 *
 * @return BP_Recent_Visitors[]
 */
function visitors_get_recent_visitors( $user_id = null, $count = 5, $duration = 0 ) {

	return BP_Recent_Visitors::get( array( 'user_id' => $user_id, 'per_page' => $count, 'duration' => $duration ) );
}

/**
 * Get the sitewide top visited profiles
 *
 * @param int $count How Many.
 * @param int $duration In How many days.
 *
 * @return array of objects(with two fields user_id, visit_count)
 */
function visitors_get_top_visited_users( $count = 10, $duration = 30 ) {

	return BP_Recent_Visitors::get_sitewide( array( 'per_page' => $count, 'max' => $count, 'duration' => $duration ) );
}

/**
 * Get the list of visit entries
 *
 * @param mixed $args array of args.
 *
 * @type int $user_id the user id
 * @type int $visitor_id optional, visitor id
 * @type int $duration optional no. of days
 * @type int $page which page?
 * @type int $per_page how many per page?
 * @type string $orderby optional default visit_time
 * @type string $sort_order optional, default DESC
 *
 * @return BP_Recent_Visitors[]
 */
function visitors_get_visitors( $args ) {
	return BP_Recent_Visitors::get( $args );
}

/**
 * Check if the scope is set to visitors in the bp_has_members()
 *
 * @return bool
 */
function visitors_is_visitor_scope() {

	if ( isset( $_POST['scope'] ) && $_POST['scope'] == 'visitors' ) {
		return true;
	}

	return false;
}

/**
 * Check if user has role
 *
 * @param int    $user_id numeric user id.
 * @param string $role role to check.
 *
 * @return bool
 */
function visitors_has_role( $user_id, $role ) {
	$user = get_user_by( 'id', $user_id );

	return in_array( $role, (array) $user->roles );
}
