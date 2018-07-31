<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Visitor model
 */
class BP_Recent_Visitors {
	/**
	 * Row id.
	 *
	 * @var int
	 */
	var $id;

	/**
	 * Visited user id.
	 *
	 * @var int
	 */
	var $user_id;

	/**
	 * Visitor user id.
	 *
	 * @var int
	 */
	var $visitor_id;

	/**
	 * Last visit time for the visitor.
	 *
	 * @var string
	 */
	var $visit_time;

	/**
	 * How many times the visitor has visited the user?
	 *
	 * @var int
	 */
	var $visit_count = 0;

	/**
	 * Total visitor count for the user.
	 *
	 * @var int
	 */
	var $visitor_count;

	/**
	 * Constructor.
	 *
	 * @param int|null $id null or the row id.
	 */
	public function __construct( $id = null ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Populate the visitor instance.
	 */
	public function populate() {
		global $wpdb;
		$bp = buddypress();

		if ( $visit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->visitors->table_name} WHERE id = %d", $this->id ) ) ) {
			$this->id            = $visit->id;
			$this->user_id       = $visit->user_id;
			$this->visitor_id    = $visit->visitor_id;
			$this->visit_count   = $visit->visit_count;
			$this->visit_time    = $visit->visit_time;
			$this->visitor_count = intval( bp_get_user_meta( $this->user_id, 'unique_profile_visitors', true ) );
		}

	}


	/**
	 * Save visit details to database.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;
		$bp = buddypress();

		$this->user_id    = apply_filters( 'visitors_user_id_before_save', $this->user_id, $this->id );
		$this->visitor_id = apply_filters( 'visitors_visitor_id_before_save', $this->visitor_id, $this->id );
		$this->visit_time = apply_filters( 'visitors_visit_time_before_save', $this->visit_time, $this->id );
		// increase visit_count by one.
		$this->visit_count = apply_filters( 'visitors_visitor_count_before_save', $this->visit_count + 1, $this->id );

		if ( ! $this->visit_time ) {
			$this->visit_time = gmdate( 'Y-m-d H:i:s' );
		}

		do_action( 'visitor_visit_before_save', $this );

		if ( $this->id ) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->visitors->table_name} SET
                            user_id = %d,
                            visitor_id = %s,
                            visit_count= %d,
                            visit_time = %s
                    WHERE
                            id = %d
                    ",
				$this->user_id,
				$this->visitor_id,
				$this->visit_count,
				$this->visit_time,
				$this->id
			);

		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$bp->visitors->table_name} (
                            user_id,
                            visitor_id,
                            visit_count,
                            visit_time
                    ) VALUES (
                        %d, %d, %d, %s
                    )",
				$this->user_id,
				$this->visitor_id,
				$this->visit_count,
				$this->visit_time
			);


			$unique_visitor_count = intval( bp_get_user_meta( $this->user_id, 'unique_profile_visitors', true ) );
			bp_update_user_meta( $this->user_id, 'unique_profile_visitors', $unique_visitor_count + 1 );

		}

		if ( false === $wpdb->query( $sql ) ) {
			return false;
		}

		// update visit count.
		$visits_count = intval( bp_get_user_meta( $this->user_id, 'profile_visits_count', true ) );
		bp_update_user_meta( $this->user_id, 'profile_visits_count', $visits_count + 1 );

		if ( ! $this->id ) {
			$this->id = $wpdb->insert_id;
		}

		do_action( 'visitors_visit_after_save', $this );

		return true;
	}


	/**
	 * Get all visitor Ids
	 *
	 * @param int $user_id numeric user id whose profile visits are fetched.
	 * @param int $count how many entries.
	 * @param int $duration since how many days.
	 *
	 * @return mixed array of ids
	 */
	public static function get_all_visitor_ids( $user_id = null, $count = 5, $duration = 0 ) {
		global $wpdb;
		$bp = buddypress();

		// if duration is given
		// if not given, assume displayed user id.
		if ( ! $user_id ) {
			$user_id = bp_displayed_user_id();
		}

		$where_conditions = array();

		// most recent first.
		$order_by = ' ORDER BY visit_time DESC LIMIT 0,' . $count;

		$where_conditions[] = $wpdb->prepare( 'user_id = %d', $user_id );

		if ( $duration ) {
			$where_conditions[] = $wpdb->prepare( 'visit_time >= DATE_ADD( NOW(),  INTERVAL -%d DAY )', $duration );
		}

		// allow to hook others and filter if needed.
		$where_conditions = apply_filters( 'rv_get_all_visitor_ids_where_clauses', $where_conditions, $user_id, $count, $duration );

		$where_sql = join( ' AND ', $where_conditions );
		$query     = "SELECT DISTINCT(visitor_id) FROM {$bp->visitors->table_name} WHERE {$where_sql} {$order_by} ";

		return $wpdb->get_col( $query );

	}


	/**
	 *  Generic fetch
	 *
	 * @param array $args array of valid args.
	 *
	 * @return BP_Recent_Visitors[]
	 */
	public static function get( $args = null ) {

		global $wpdb;
		$bp = buddypress();

		$default = array(
			'user_id'    => false,
			'visitor_id' => false,
			'per_page'   => 10,
			'page'       => 1,
			'orderby'    => 'visit_time',
			'sort_order' => 'DESC',
			'duration'   => 0,
		);

		$args = wp_parse_args( $args, $default );
		extract( $args );

		$where_conditions = array();

		$where_sql = '';

		$user_id = $args['user_id'];
		$visitor_id = $args['visitor_id'];
		$duration = $args['duration'];

		$orderby = $args['orderby'];
		$sort_order = $args['sort_order'];

		if ( $user_id ) {
			$where_conditions[] = $wpdb->prepare( 'user_id = %d', $user_id );
		}

		if ( $visitor_id ) {
			$where_conditions[] = $wpdb->prepare( 'visitor_id = %d', $visitor_id );
		}

		if ( $duration ) {

			$where_conditions[] = $wpdb->prepare( 'visit_time >= DATE_ADD( NOW(),  INTERVAL -%d DAY )', $duration );
		}

		$sort_sql = '';

		if ( $orderby && $sort_order ) {
			$sort_sql = "ORDER BY {$orderby} {$sort_order}";
		}

		$limit_sql = '';

		if ( $page && $per_page ) {
			$limit_sql = $wpdb->prepare( " LIMIT %d, %d ", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );
		}

		// allow to hook others and filter if needed.
		$where_conditions = apply_filters( 'rv_get_where_clauses', $where_conditions, $args );

		if ( ! empty( $where_conditions ) ) {
			$where_sql = ' WHERE ' . join( ' AND ', $where_conditions );
		}

		$visitors = $wpdb->get_results( "SELECT * FROM {$bp->visitors->table_name}  {$where_sql} {$sort_sql} {$limit_sql}" );

		return $visitors;

	}

	/**
	 * Get an array of top visited profiles
	 *
	 * @param array $args sitewide query args.
	 *
	 * @return array|null|object array of object( user_id, visit_count)
	 */
	public static function get_sitewide( $args = array() ) {

		global $wpdb;
		$bp = buddypress();

		$where_conditions = array();
		$where_sql        = '';

		$defaults = array(
			'per_page' => 10,
			'page'     => 1,
			'max'      => 10,
			'duration' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$lower = ( $args['page'] - 1 ) * $args['per_page'];
		$upper = $args['page'] * $args['per_page'];

		// should we enforce max?
		if ( ( $upper - $lower ) > $args['max'] ) {
			$upper = $lower + $args['max'];
		}

		if ( $args['duration'] ) {
			$where_conditions[] = $wpdb->prepare( 'visit_time >= DATE_ADD( NOW(),  INTERVAL -%d DAY )', $args['duration'] );
		}

		// allow to hook others and filter if needed.
		$where_conditions = apply_filters( 'rv_get_sitewide_where_clauses', $where_conditions, $args );


		if ( $where_conditions ) {
			$where_sql = ' WHERE ' . join( ' AND ', $where_conditions );
		}

		$query = "SELECT user_id, count(user_id) as visits FROM {$bp->visitors->table_name} {$where_sql} GROUP BY user_id ORDER BY visits DESC LIMIT $lower, $upper ";

		$result = $wpdb->get_results( $query );

		return $result;
	}


	/**
	 * Check how many times the visitor has visited the user
	 *
	 * @param int $visitor_id numeric visitors id.
	 * @param int $user_id numeric user id.
	 *
	 * @return null|string
	 */
	public static function get_visit_count( $visitor_id, $user_id = 0 ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;
		$bp    = buddypress();
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT visit_count FROM {$bp->visitors->table_name} WHERE user_id = %d AND visitor_id = %d", $user_id, $visitor_id ) );

		return $count;
	}


	/**
	 * Check if there exists a recording of visit for $visitor_id -> to -> $user_id
	 *
	 * @param int $user_id numeric user id.
	 * @param int $visitor_id numeric visitor id.
	 *
	 * @return array
	 */
	public static function check_exists( $user_id = null, $visitor_id = null ) {

		global $wpdb;
		$bp = buddypress();

		if ( ! $user_id ) {
			$user_id = bp_displayed_user_id();
		}

		if ( ! $visitor_id ) {
			$visitor_id = get_current_user_id();
		}

		$query = $wpdb->prepare( "SELECT id FROM {$bp->visitors->table_name} WHERE user_id = %d AND visitor_id = %d", $user_id, $visitor_id );

		// return the id of the existing one.
		return $wpdb->get_col( $query );
	}
}
