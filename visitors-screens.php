<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User settings screen.
 */
function bp_visitors_screen_general_settings() {

	global $current_user, $bp_settings_updated;

	$bp_settings_updated = false;

	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer( 'bp_settings_visitors' );

		// do not allow foul play by users.
		$visitor_setings = $_POST['enable_visitor_recording'];

		$bp_settings_updated = true;
		// it's either 1 or 0.
		update_user_meta( $current_user->ID, 'visitor_recording', $visitor_setings );
	}

	add_action( 'bp_template_title', 'bp_visitors_screen_general_settings_title' );
	add_action( 'bp_template_content', 'bp_visitors_screen_general_settings_content' );

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

/**
 * Settings title.
 */
function bp_visitors_screen_general_settings_title() {
	_e( 'Recent Visitors Settings', 'recent-visitors-for-buddypress-profile' );
}

/**
 * Settings content.
 */
function bp_visitors_screen_general_settings_content() {
	global $bp, $current_user, $bp_settings_updated; ?>

	<?php if ( $bp_settings_updated ) { ?>
        <div id="message" class="updated fade">
            <p><?php _e( 'Changes Saved.', 'recent-visitors-for-buddypress-profile' ) ?></p>
        </div>
	<?php } ?>

    <form action="<?php echo $bp->loggedin_user->domain . BP_SETTINGS_SLUG . '/visitors' ?>" method="post" class="standard-form" id="settings-form">
        <h4> <?php _e( 'Show My Profile Visits', 'recent-visitors-for-buddypress-profile' ) ?></h4>
        <label>
            <input type="radio" name="enable_visitor_recording" value="y" <?php if ( visitors_is_active_visitor_recording( $current_user->ID ) ): ?> checked="checked" <?php endif; ?> /><?php _e( "Yes", "recent-visitors-for-buddypress-profile" ); ?>
        </label>
        <label>
            <input type="radio" name="enable_visitor_recording" value="n" <?php if ( ! visitors_is_active_visitor_recording( $current_user->ID ) ): ?> checked="checked" <?php endif; ?> /><?php _e( "No", "recent-visitors-for-buddypress-profile" ); ?>
        </label>
        <p><?php _e( "Please note, If you enable the visitors recording, your visits to others profile will be visible too.", 'recent-visitors-for-buddypress-profile' ); ?>
        </p>
        <div class="submit">
            <input type="submit" name="submit" value="<?php _e( 'Save Changes', 'recent-visitors-for-buddypress-profile' ) ?>" id="submit" class="auto"/>
        </div>

		<?php wp_nonce_field( 'bp_settings_visitors' ) ?>
    </form>
	<?php
}

/**
 * Profile My visitor screen
 */
function visitors_screen_my_visitors() {

	// add the title/content.
	add_action( 'bp_template_title', 'bp_visitors_screen_my_visitors_title' );
	add_action( 'bp_template_content', 'bp_visitors_screen_my_visitors_content' );
	bp_core_load_template( 'members/single/plugins' );
}

/**
 * My visitor Content title.
 */
function bp_visitors_screen_my_visitors_title() {
	_e( 'Your Visitors', 'recent-visitors-for-buddypress-profile' );
}

/**
 * My visitor screen content.
 */
function bp_visitors_screen_my_visitors_content() {
	add_filter( 'bp_after_has_members_parse_args', 'bp_visitors_filter_on_visitor_component' );
	bp_get_template_part('members/members-loop');
	/*
	$html = '<div class="component-recent-visitors">';

	foreach ( $visitors as $visitor ) {

		$html .= visitors_build_visitor_html( $visitor );
	}
	$html .= "</div>";

	echo $html;*/
}

function bp_visitors_filter_on_visitor_component( $args ) {
	$max      = visitors_get_setting( 'max_display' );

	// $visitors = visitors_get_recent_visitors( bp_displayed_user_id(), $max, $duration );
	$page = 1;
	if ( isset( $_REQUEST['vpage'] ) ) {
		$page = absint( $_REQUEST['vpage'] );
	}
	$duration = 0;

	switch ( bp_current_action() ) {

		case '7days':
			$duration = 7;
			break;

		case '30days':
			$duration = 30;
			break;

	}
	$visitors = visitors_get_visitors( array(
		'user_id'  => bp_displayed_user_id(),
		'per_page' => $max,
		'page'     => $page,
		'duration' => $duration,
	) );

	$visitor_ids = wp_list_pluck( $visitors, 'visitor_id' );

	if ( empty( $visitor_ids ) ) {
		$visitor_ids = array( 0, 0 );
	}
    $args['per_page'] = $max;
	$args['max'] = $max;
    $args['include'] = $visitor_ids;

	return $args;
}
