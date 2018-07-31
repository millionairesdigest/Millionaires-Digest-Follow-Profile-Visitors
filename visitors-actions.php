<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Record profile visit.
 */
function bp_visitors_record_visit() {

	// do not try t record if it is not a user profile or the user is not logged in or viewing self profile.
	if ( ! bp_is_user() || ! is_user_logged_in() || bp_is_my_profile() ) {
		return;
	}

	$visitor_id = get_current_user_id();

	$user_id = bp_displayed_user_id();

	// is visitor component disaled for the displayed user? We won't record then.
	if ( ! visitor_is_enabled_for_user( $user_id ) ) {
		return; // do not record as the component is disabled for the displayed user.
	}

	// check preference for loggedin user and displayed user.
	if ( ! visitors_can_record_visit( $user_id, $visitor_id ) ) {
		return;
	}

	// currently we do not record new visits in new entry, is it a good idea to record each visit in a new entry?
	if ( $earlier_visit = BP_Recent_Visitors::check_exists( $user_id, $visitor_id ) ) {

		$myvisitor = new BP_Recent_Visitors( $earlier_visit );
		// just update visit_time.
		$myvisitor->visit_time = gmdate( 'Y-m-d H:i:s' );
		$myvisitor->save();

	} else {
		// first time visit.
		$myvisitor             = new BP_Recent_Visitors();
		$myvisitor->visitor_id = $visitor_id;
		$myvisitor->user_id    = $user_id;
		$myvisitor->save();
	}

	/**
	 * Action to do something if you want on new visit.
	 *
	 * @since 1.2.5
	 */
	do_action( 'visitors_profile_visited', $user_id, $visitor_id );
}
add_action( 'bp_init', 'bp_visitors_record_visit', 20 );

/**
 * Show N visitors in the member header
 */
function visitors_show_my_recent_visitor() {

	// visitor must be enabled
	// also show_in_header too.
	if ( ! visitor_is_enabled_for_user( bp_displayed_user_id() ) || ! visitors_get_setting( 'show_in_header' ) ) {
		return;
	}

	/**
	 * @todo modify who can see the visitors
	 */
	// show only for logged in users and on their Home if they have set a preference of showing it.
	if ( ! bp_is_my_profile() || ! visitors_is_active_visitor_recording( bp_displayed_user_id() ) ) {
		return;
	}

	$recent_visitors = visitors_get_recent_visitors( bp_displayed_user_id(), visitors_get_setting('max_display_in_header', 5 ) );

	if ( empty( $recent_visitors ) ) {
		return;
	} // if no visits yest, do not show at all.

	$output = "<div class='recent-visitors'>
<h5>" . __( 'Recent Visitors', 'recent-visitors-for-buddypress-profile' ) . "</h5>";

	foreach ( $recent_visitors as $visitor ) {

		$output .= visitors_build_visitor_html( $visitor );
	}

	echo apply_filters( 'visitors_header_visitors_list', $output . '</div>', $recent_visitors );
}

// where to show it.
if ( visitors_get_setting('show_inside_header_meta' ) ) {
	add_action( 'bp_profile_header_meta', 'visitors_show_my_recent_visitor' );
} else {
	add_action( 'bp_after_member_header', 'visitors_show_my_recent_visitor' );
}
