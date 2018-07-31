<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget for listing top profiles
 */
class BP_Visitor_Most_Visited_Users_Widget extends WP_Widget {

	/**
	 * Widget constructor.
	 */
	public function __construct( $id_base = false, $name = '', array $widget_options = array() ) {
		$name                          = __( 'Most Visited Users', 'recent-visitors-for-buddypress-profile' );
		$widget_options['description'] = __( 'You can list most visited users on this site using the widget.', 'recent-visitors-for-buddypress-profile' );
		parent::__construct( $id_base, $name, $widget_options );
	}

	/**
     * Display the widget.
     *
	 * @param array $args display arguements.
	 * @param array $instance current widget instance settings.
	 */
	public function widget( $args, $instance ) {

		$params_args = array(
			'max'      => $instance['max'],
			'duration' => $instance['duration'],
		);

		$top_profiles = BP_Recent_Visitors::get_sitewide( $params_args );

		if ( empty( $top_profiles ) ) {
		    return ; // Do not show widget if no views available.
        }

		if ( 'slide' === $instance['view'] ) {
		    wp_enqueue_style( 'visitors-css' );
		    wp_enqueue_script( 'visitors-js' );
        }

		echo $args['before_widget'];
		echo $args['before_title'] . $instance['title'] . $args['after_title'];
		$item_list_class = $instance['view'] === 'list' ? 'item-list' : '';
		?>
        <div class="recent-visitors-widget">
	        <?php if ( ! empty( $top_profiles ) ) : ?>
                <ul class="<?php echo $item_list_class;?> most-visited-users-<?php echo $instance['view']?>">
			        <?php foreach ( $top_profiles as $top_profile ) : ?>
                        <li>
                            <div class="item-avatar">
						        <a
								   href="<?php echo bp_core_get_user_domain( $top_profile->user_id ) ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $top_profile->user_id ) ) ; ?></a>
                            </div>
                            <div class="item">
                                <div class="item-title"><a
                                            href="<?php echo bp_core_get_user_domain( $top_profile->user_id ) ?>"><?php echo  bp_core_get_user_displayname( $top_profile->user_id ) ; ?></a>
                                </div>
                                <?php if( ! empty( $instance['show_visits'] ) ):?>
                                <div class="item-meta">
                                    <span><?php _e( 'views:', 'recent-visitors-for-buddypress-profile' ) ?><span class="visit-count"><?php echo $top_profile->visits ; ?></span> </span>
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
		        <?php _e( 'No result found', 'recent-visitors-for-buddypress-profile' ); ?>
	        <?php endif; ?>
        </div>
		<?php
		echo $args['after_widget'];
	}

	/**
	 * Update the setings.
	 *
	 * @param array $new_instance new instance settings.
	 * @param array $old_instance old instance settings.
	 *
	 * @return mixed
	 */
	public function update( $new_instance, $old_instance ) {
		$instance['title']    = strip_tags( $new_instance['title'] );
		$instance['view']     = $new_instance['view'];
		$instance['max']      = $new_instance['max'];
		$instance['duration'] = $new_instance['duration'];
		$instance['show_visits'] = absint( $new_instance['show_visits'] );

		return $instance;
	}

	/**
	 * Display widget settings form.
	 *
	 * @param array $instance current instance.
	 */
	public function form( $instance ) {

		$default = array(
			'title'       => __( 'Popular Users', 'recent-visitors-for-buddypress-profile' ),
			'view'        => 'list',
			'max'         => 10,
			'duration'    => 7,
			'show_visits' => 1,
		);

		$args = wp_parse_args( $instance, $default );

		?>
        <p>
            <label for="<?php esc_attr_e( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title', 'recent-visitors-for-buddypress-profile' ) ?></label><br>
            <input type="text" id="<?php esc_attr_e( $this->get_field_id( 'title' ) ) ?>"
                   name="<?php esc_attr_e( $this->get_field_name( 'title' ) ) ?>"
                   value="<?php esc_attr_e( $args['title'] ) ?>">
        </p>

        <p>
            <label for="<?php esc_attr_e( $this->get_field_id( 'view' ) ); ?>"><?php _e( 'Select View', 'recent-visitors-for-buddypress-profile' ) ?></label><br>
            <select id="<?php esc_attr_e( $this->get_field_id( 'view' ) ); ?>" name="<?php esc_attr_e( $this->get_field_name( 'view' ) ); ?>">
	            <?php foreach ( $this->get_views() as $view => $label ) : ?>
                <option value="<?php esc_attr_e( $view ); ?>" <?php selected( $args['view'], $view, true )?>><?php _e( $label ); ?></option>
	            <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php esc_attr_e( $this->get_field_id( 'max' ) ); ?>"><?php _e( 'Max', 'recent-visitors-for-buddypress-profile' ) ?></label><br />
            <input type="number" id="<?php esc_attr_e( $this->get_field_id( 'max' ) ) ?>"
                   name="<?php esc_attr_e( $this->get_field_name( 'max' ) ) ?>"
                   value="<?php esc_attr_e( $args['max'] ) ?>">
        </p>

        <p>
            <label for="<?php esc_attr_e( $this->get_field_id( 'duration' ) ); ?>"><?php _e( 'Duration in days', 'recent-visitors-for-buddypress-profile' ) ?></label><br />
            <input type="text" id="<?php esc_attr_e( $this->get_field_id( 'duration' ) ) ?>"
                   name="<?php esc_attr_e( $this->get_field_name( 'duration' ) ) ?>"
                   value="<?php esc_attr_e( $args['duration'] ) ?>">
        </p>

        <p>
            <label for="<?php esc_attr_e( $this->get_field_id( 'show_visits' ) ); ?>"><?php _e( 'Show visit count', 'recent-visitors-for-buddypress-profile' ) ?></label>
            <input type="checkbox" id="<?php esc_attr_e( $this->get_field_id( 'show_visits' ) ) ?>"
                   name="<?php esc_attr_e( $this->get_field_name( 'show_visits' ) ) ?>"
                   value="1" <?php checked(1, $args['show_visits'], true );?>>
        </p>
		<?php
	}

	public function get_views() {
		$views = array(
			'list'  => __( 'List', 'recent-visitors-for-buddypress-profile' ),
			//'grid'  => __( 'Grid', 'recent-visitors-for-buddypress-profile' ),
			'slide' => __( 'Slide', 'recent-visitors-for-buddypress-profile' ),
		);

		return $views;
    }
}

function visitors_registers_widget() {
	register_widget( 'BP_Visitor_Most_Visited_Users_Widget' );
}

add_action( 'widgets_init', 'visitors_registers_widget' );
