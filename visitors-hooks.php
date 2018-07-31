<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter User Query to list visitors on directory
 *
 * @param BP_User_Query $query_obj user query object.
 *
 * @return mixed
 */
function visitors_users_filter( $query_obj ) {

	if ( ! is_user_logged_in() ) {
		return $query_obj; // no need to proceed.
	}

	$users = false;
	// are we on directory?
	if ( bp_is_members_component() && ! bp_is_user() ) {
		// we are on the Members directory
		// check if the query is for visitors.
		if ( isset( $_POST['scope'] ) && $_POST['scope'] == 'visitors' ) {

			$users = visitors_get_recent_visitors( get_current_user_id(), 100 );

			if ( ! empty( $users ) ) {

				$users = wp_list_pluck( $users, 'visitor_id' );
			}
			$uid_where = $query_obj->uid_clauses['where'];


			if ( empty( $users ) ) {
				// if no users found, let us fake it.
				$users = array( 0 => 0 );
			}

			$list = '(' . join( ',', $users ) . ')';

			if ( $uid_where ) {
				$uid_where .= " AND u.{$query_obj->uid_name} IN {$list}";
			} else {
				$uid_where = "WHERE u.{$query_obj->uid_name} IN {$list}"; // we are treading a hard line here.
			}

			$query_obj->uid_clauses['where'] = $uid_where;

		}
	}

	return $query_obj;
}
add_action( 'bp_pre_user_query', 'visitors_users_filter' );

/**
 * Add recent visitors nav to directory.
 */
function visitors_directory_add_nav() {

	if ( ! is_user_logged_in() || ! visitor_is_enabled_for_user( bp_loggedin_user_id() ) || ! visitors_get_setting( 'show_in_directory' ) ) {
		return;
	}
	?>
    <li id="members-visitors">
        <a href="<?php echo bp_loggedin_user_domain() . buddypress()->visitors->slug . '/my-visitors/' ?>"><?php printf( __( 'Recent Visitors <span>%s</span>', 'recent-visitors-for-buddypress-profile' ), visitors_get_unique_visitors_count( bp_loggedin_user_id() ) ); ?></a>
    </li>
	<?php
}

add_action( 'bp_members_directory_member_types', 'visitors_directory_add_nav' );

/**
 * Show visit count in directory?
 */
function visitors_show_visit_count_on_dir() {

	if ( ! visitors_is_visitor_scope() ) {
		return;
	}

	$count = visitors_get_visit_count( bp_get_member_user_id() );
	printf('<span class="rv-views-count">%s<span class="rv-views-count-number">%d</span></span>',  __( 'Visit:', 'recent-visitors-for-buddypress-profile' ) , $count );
}
add_action( 'bp_directory_members_item', 'visitors_show_visit_count_on_dir' );

// BuddyPress profile visibility compatible.
// Filter Recent visitor queries.
if ( function_exists( 'bp_profile_visibility_manager' ) ) {
	add_filter( 'rv_get_where_clauses', 'rv_bppv_filter_where_clause' );
	add_filter( 'rv_get_sitewide_where_clauses', 'rv_bppv_filter_sitewide_where_clause' );
	add_filter( 'rv_get_all_visitor_ids_where_clauses', 'rv_bppv_filter_where_clause' );
}

// Compatibility with BuddyPress Profile visibility Manager.
/**
 * Hide members whose profile are hidden on directory.
 *
 * @param array $where_conditions conditions.
 *
 * @return string
 */
function rv_bppv_filter_where_clause( $where_conditions ) {

	$in_clause = bp_profile_visibility_manager()->get_dir_excluded_users_sql();
	$clause    = "visitor_id NOT IN ($in_clause)";
	array_unshift( $where_conditions, $clause );

	return $where_conditions;
}

/**
 * Filter sitewide list.
 *
 * @param array $where_conditions conditions.
 *
 * @return array
 */
function rv_bppv_filter_sitewide_where_clause( $where_conditions ) {
	$in_clause = bp_profile_visibility_manager()->get_dir_excluded_users_sql();
	$clause    = "user_id NOT IN ($in_clause)";
	array_unshift( $where_conditions, $clause );

	return $where_conditions;
}

/**
 * Compatibility with Simple privacy plugin
 */

/**
 *  Filter the recent visitors query.
 *
 * @param array $where_conditions where clauses.
 *
 * @return array
 */
function rv_bpsprivacy_filter_where_clause( $where_conditions ) {

	$clause = rv_bpsprivacy_get_clause();

	if ( ! empty( $clause ) ) {
	    array_unshift( $where_conditions, $clause );
	}

	return $where_conditions;
}
/**
 *  Filter the recent visitors query.
 *
 * @param array $where_conditions where clauses.
 *
 * @return array
 */
function rv_bpsprivacy_filter_sitewide_where_clause( $where_conditions ) {

	$clause = rv_bpsprivacy_get_clause( 'user_id' );

	if ( ! empty( $clause ) ) {
		array_unshift( $where_conditions, $clause );
	}

	return $where_conditions;
}
// Filter Recent visitor queries.
if ( function_exists( 'sbpp04_get_hidden_members' ) ) {
	add_filter( 'rv_get_where_clauses', 'rv_bpsprivacy_filter_where_clause' );
	add_filter( 'rv_get_sitewide_where_clauses', 'rv_bpsprivacy_filter_sitewide_where_clause' );
	add_filter( 'rv_get_all_visitor_ids_where_clauses', 'rv_bpsprivacy_filter_where_clause' );
}
/**
 * Get the clause to hide BP Privacy based hidden members.
 *
 * @return string
 */
function rv_bpsprivacy_get_clause( $field_name = 'visitor_id' ) {

	$users = sbpp04_get_hidden_members();
	$users = $users->get_results();


	if ( empty( $users ) ) {
		return '';
	}

	$user_ids = wp_list_pluck( $users, 'ID' );

	$list = join( ',', $user_ids );

	// Clause.
	return "{$field_name} NOT IN ($list)";

}
