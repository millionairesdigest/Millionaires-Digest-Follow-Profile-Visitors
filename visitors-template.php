<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Used to build individual visitor entry
 *
 * @param BP_Recent_Visitors $visitor recetnt visitor object.
 *
 * @return string
 */
function visitors_build_visitor_html( $visitor, $args = null ) {

	$visitor_id = $visitor->visitor_id;

	$default = array(
		'item_id' => $visitor_id,
		'type'    => 'thumb',
		'height'  => visitors_get_setting( 'default_avatar_size' ),
		'width'   => visitors_get_setting( 'default_avatar_size' ),
	);

	$args = wp_parse_args( $args, $default );

	extract( $args );

	if ( $args['height'] > 50 ) {
		$args['type'] = 'full';
	}

	$avatar = bp_core_fetch_avatar( $args );

	// let other plugins/code generate the html.
	$html = apply_filters( 'visitors_pre_item_html', '', $visitor, $args );

	if ( ! empty( $html ) ) {
		return $html;
	}

	$html = '<a href="' . bp_core_get_user_domain( $visitor_id ) . '" title="' . bp_core_get_user_displayname( $visitor_id ) . '">' . $avatar . '</a>';

	return $html;
}

/**
 * Get visit count.
 *
 * @param int $user_id numeric user id.
 *
 * @return null|string
 */
function visitors_get_visit_count( $user_id ) {
	return BP_Recent_Visitors::get_visit_count( $user_id );
}
