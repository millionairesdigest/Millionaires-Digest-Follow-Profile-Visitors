<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visitors List shortcode.
 *
 * @param array  $atts shortcode atts.
 * @param string $content content.
 *
 * @return string
 */
function visitors_shortcode( $atts = '', $content = null ) {

	$atts = shortcode_atts( array(
		'user'     => get_current_user_id(),
		'max'      => 5,
		'duration' => 0, // all time.
		'size'     => 32, // avatar size.
	), $atts );

	$user     = $atts['user'];
	$max      = $atts['max'];
	$duration = $atts['duration'];
	$size     = $atts['size'];

	// user can be given by id, username or email.
	if ( ! is_int( $user ) ) {
		$u = false;

		if ( is_email( $user ) ) {
			$u = get_user_by( 'email', $user );
		} else {
			$u = get_user_by( 'login', $user );
		}

		if ( $u ) {
			$user = $u->ID;
		}
	}
	if ( ! $user ) {
		return '';
	}

	$visitors = visitors_get_recent_visitors( $user, $max, $duration );

	if ( empty( $visitors ) ) {
		return '';
	}

	$html = '<div class="shortcode-recent-visitors">';

	foreach ( $visitors as $visitor ) {
		$html .= visitors_build_visitor_html( $visitor, array( 'height' => $size, 'width' => $size ) );
	}

	$html .= '</div>';

	return $html;
}
add_shortcode( 'recent-visitors', 'visitors_shortcode' );
add_shortcode( 'bp-visitors-recent-visitors', 'visitors_shortcode' );

/**
 * Sitewide top visited profile.
 *
 * @param array  $atts possible atts.
 * @param string $content content.
 *
 * @return null|string
 */
function visitors_top_profiles( $atts = array(), $content = null ) {

	$atts = shortcode_atts(
		array(
			'view'          => 'list', // others options grid, slide.
			'max'           => 5,
			'duration'      => 7,
            'show_visits'   => 1,
		), $atts );

	$top_profiles = BP_Recent_Visitors::get_sitewide( array(
		'max'      => $atts['max'],
		'duration' => $atts['duration'],
	) );

	if ( 'slide' === $atts['view'] ) {
		wp_enqueue_style( 'visitors-css' );
		wp_enqueue_script( 'visitors-js' );
	}

	ob_start();

	?>
    <div class="recent-visitors-shortcode">
		<?php if ( ! empty( $top_profiles ) ) : ?>
            <ul class="item-list most-visited-users-<?php echo $atts['view'] ?>">
				<?php foreach ( $top_profiles as $top_profile ) : ?>
                    <li>
                        <div class="item-avatar">
							<?php echo bp_core_fetch_avatar( array( 'item_id' => $top_profile->user_id ) ) ?>
                        </div>
                        <div class="item">
                            <div class="item-title"><a
                                        href="<?php echo bp_core_get_user_domain( $top_profile->user_id ) ?>"><?php echo  bp_core_get_user_displayname( $top_profile->user_id ); ?></a>
                            </div>
                            <?php if( ! empty( $atts['show_visits'] ) ): ?>
                            <div class="item-meta">
                                <span><?php _e( 'views:', 'recent-visitors-for-buddypress-profile' ) ?><span class="visit-count"><?php echo $top_profile->visits ; ?></span></span>
                            </div>
                        <?php endif;?>
                        </div>
                    </li>
				<?php endforeach; ?>
            </ul>
            <style type="text/css">
                ul.item-list span.visit-count {
                    padding-left: 5px;
                }
            </style>
		<?php else: ?>
			<?php _e( 'No result found.', 'recent-visitors-for-buddypress-profile' ); ?>
		<?php endif; ?>
    </div>
	<?php

	$content = ob_get_clean();

	return $content;
}
add_shortcode( 'bp-visitors-most-visited-users', 'visitors_top_profiles' );
