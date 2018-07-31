<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notify user of new visit.
 *
 * @param int $user_id visited user id.
 * @param int $visitor_id visitor id.
 */
function visitors_notify_user( $user_id, $visitor_id ) {

	$args = compact( 'user_id', 'visitor_id' );

	if ( visitors_get_setting( 'notification_local' ) === 'yes' ) {
		visitors_add_notification( $args );
	}
	// if enabled by email.
	if ( visitors_get_setting( 'notification_by_email' ) === 'yes' ) {
		visitors_notify_by_email( $args );
	}
}

add_action( 'visitors_profile_visited', 'visitors_notify_user', 10, 2 );

/**
 * I strongly recommend against using it
 * I have added it on the request of one of our members and by default, It will stay disabled
 */
/**
 * Adds a Local notification
 *
 * @param array $args notification args.
 */
function visitors_add_notification( $args = null ) {

	$default = array(
		'user_id'    => bp_displayed_user_id(),
		'visitor_id' => get_current_user_id(),
	);

	$args = wp_parse_args( $args, $default );

	extract( $args );

	$notify_locally = bp_get_user_meta( $user_id, 'notify_visitors_locally', true );

	if ( ! $notify_locally ) {
		$notify_locally = visitors_get_setting( 'notify_visitors_locally' );
	} //get default sitewide option if not set by user


	if ( bp_is_active( 'notifications' ) && $notify_locally == 'yes' ) {
		bp_notifications_add_notification( array(
			'user_id'           => $user_id,
			'item_id'           => $visitor_id,
			'secondary_item_id' => $visitor_id,
			'component_name'    => buddypress()->visitors->id,
			'component_action'  => 'new_profile_visit',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
			'allow_duplicate'   => true,
		) );
	}
}

/**
 * Notify a User by email when his/her profile is being visited
 * Check for the preference and sends email if the user has set so
 *
 * @param array $args
 */
function visitors_notify_by_email( $args = null ) {

	$default = array(
		'user_id'    => bp_displayed_user_id(),
		'visitor_id' => get_current_user_id(),
	);

	$args = wp_parse_args( $args, $default );

	extract( $args );

	if ( ! $visitor_id || ! $user_id ) {
		return;
	}


	// get user preference for sending email.
	$send_by_email = bp_get_user_meta( $user_id, 'notify_visitors_by_email', true );

	if ( ! $send_by_email ) {
		$send_by_email = visitors_get_setting( 'notify_visitors_by_email' );
	}


	if ( 'yes' !== $send_by_email ) {
		return;
	}

	// if we are here, we should send an email notification.
	$user = new WP_User( $user_id );

	$to = $user->user_email;

	$subject = sprintf( __( '[%s] %s visited your profile', 'recent-visitors-for-buddypress-profile' ), get_bloginfo( 'name' ), bp_core_get_user_displayname( $user_id ) );

	$message = __( "Hi %s \n 
       %s visited your profile\n 
       Your Profile: %s\n
       Visit Their profile: %s\n
    ", 'recent-visitors-for-buddypress-profile' );

	$message = sprintf( $message, bp_core_get_user_displayname( $user_id ), bp_core_get_user_displayname( $visitor_id ), bp_core_get_user_domain( $user_id ), bp_core_get_user_domain( $visitor_id ) );

	wp_mail( $to, $subject, $message );

}

/**
 * Format local BuddyPress notification for the recent visitors.
 *
 * @param string $action action name.
 * @param int    $item_id item id.
 * @param int    $secondary_item_id secondary item id.
 * @param int    $total_items total number of items.
 * @param string $format what type of notification(text/object).
 *
 * @return array|string
 */
function bp_visitors_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	$count = $total_items;

	switch ( $action ) {
		case 'new_profile_visit':
			$visitor_id   = $item_id;
			$visitor_link = bp_loggedin_user_domain() . bp_get_notifications_slug();
			$visits_title = sprintf( __( '%d new visits', 'recent-visitors-for-buddypress-profile' ), $count );

			if ( (int) $total_items > 1 ) {

				$text = sprintf( __( 'You have %1$d new visits', 'recent-visitors-for-buddypress-profile' ), (int) $total_items );

			} else {

				$user_fullname = bp_core_get_user_displayname( $visitor_id );
				$text          = sprintf( __( '%1$s visited your profile', 'recent-visitors-for-buddypress-profile' ), $user_fullname );
				$visitor_link  = bp_core_get_user_domain( $visitor_id );

			}
			break;
	}

	if ( 'string' === $format ) {
		$return = '<a href="' . esc_url( $visitor_link ) . '" title="' . esc_attr( $visits_title ) . '">' . esc_html( $text ) . '</a>';
	} else {
		$return = array(
			'text' => $text,
			'link' => $visitor_link,
		);
	}

	return $return;
}

/**
 * User notificatin settings.
 */
function visitors_screen_notification_settings() {

	if ( ! visitors_get_setting( 'notification_local' ) && ! visitors_get_setting( 'notification_by_email' ) ) {
		return;
	}

	if ( ! $notify_locally = bp_get_user_meta( bp_displayed_user_id(), 'notify_visitors_locally', true ) ) {
		$notify_locally = visitors_get_setting( 'notify_visitors_locally' );
	}

	if ( ! $notify_by_email = bp_get_user_meta( bp_displayed_user_id(), 'notify_visitors_by_email', true ) ) {
		$notify_by_email = visitors_get_setting( 'notify_visitors_by_email' );
	}

	?>

    <table class="notification-settings" id="visitors-notification-settings">
        <thead>
        <tr>
            <th class="icon">&nbsp;</th>
            <th class="title"><?php _e( 'Recent Visitors', 'recent-visitors-for-buddypress-profile' ) ?></th>
            <th class="yes"><?php _e( 'Yes', 'recent-visitors-for-buddypress-profile' ) ?></th>
            <th class="no"><?php _e( 'No', 'recent-visitors-for-buddypress-profile' ) ?></th>
        </tr>
        </thead>

        <tbody>

		<?php if ( visitors_get_setting( 'notification_local' ) ): ?>
            <tr id="visitors-notify-locally">
                <td>&nbsp;</td>
                <td><?php _e( "Recieve Notification on new profile visits?", 'recent-visitors-for-buddypress-profile' ) ?></td>
                <td class="yes"><input type="radio" name="notifications[notify_visitors_locally]"
                                       value="yes" <?php checked( $notify_locally, 'yes', true ) ?>/></td>
                <td class="no"><input type="radio" name="notifications[notify_visitors_locally]"
                                      value="no" <?php checked( $notify_locally, 'no', true ) ?>/></td>
            </tr>
		<?php endif; ?>

		<?php if ( visitors_get_setting( 'notification_by_email' ) ): ?>
            <tr id="visitors-notify-by-email">
                <td>&nbsp;</td>
                <td><?php _e( "Recieve Notification on new profile visits by email?", 'recent-visitors-for-buddypress-profile' ) ?></td>
                <td class="yes"><input type="radio" name="notifications[notify_visitors_by_email]"
                                       value="yes" <?php checked( $notify_by_email, 'yes', true ) ?>/></td>
                <td class="no"><input type="radio" name="notifications[notify_visitors_by_email]"
                                      value="no" <?php checked( $notify_by_email, 'no', true ) ?>/></td>
            </tr>
		<?php endif; ?>

        </tbody>
    </table>

	<?php
}

add_action( 'bp_notification_settings', 'visitors_screen_notification_settings', 100 );
