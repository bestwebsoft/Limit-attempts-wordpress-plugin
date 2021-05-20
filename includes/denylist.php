<?php
/**
 * Display list of IP, which are in denylist
 * @package Limit Attempts
 * @since 1.1.3
 */
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( ! class_exists( 'Lmtttmpts_Denylist' ) ) {
	class Lmtttmpts_Denylist extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'ip'			=> __( 'Ip Address', 'limit-attempts' ),
				'add_time'		=> __( 'Date Added', 'limit-attempts' )
			);
			return $columns;
		}

		function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'ip' 		=> array( 'ip', true ),
				'add_time'	=> array( 'add_time', true )
			);
			return $sortable_columns;
		}

		function column_ip( $item ) {
			/* adding action to 'ip' collumn */
			$actions = array(
				'delete'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&lmtttmpts_remove_from_denylist=%s', $_REQUEST['page'], $item['ip'] ), 'lmtttmpts_remove_from_denylist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Delete', 'limit-attempts' ) . '</a>'
			);
			return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'remove_from_denylist_ips'	=> __( 'Delete', 'limit-attempts' )
			);
			return $actions;
		}

		function column_cb( $item ) {
			/* customize displaying cb collumn */
			return sprintf( '<input type="checkbox" name="ip[]" value="%s" />', $item['ip'] );
		}

		function prepare_items() {
			/* preparing table items */
			global $wpdb;
			$prefix = $wpdb->prefix . 'lmtttmpts_';
			$part_ip = isset( $_REQUEST['s'] ) ? trim( htmlspecialchars( $_REQUEST['s'] ) ) : '';
			/* query for total number of denylisted IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "denylist`";
			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
			    $count_query .= " WHERE `ip` LIKE '%" . $part_ip . "%'";
			}
			/* get the total number of IPs */
			$totalitems = $wpdb->get_var( $count_query );
			/* get the value of number of IPs on one page */
			$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );

			/* set pagination arguments */
			$this->set_pagination_args( array(
				"total_items" 	=> $totalitems,
				"per_page" 		=> $perpage
			) );
			/* the 'orderby' and 'order' values */
			$orderby = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ? $_REQUEST['orderby']  : 'add_time';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'desc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
			$offset  = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `add_time` FROM `" . $prefix . "denylist`";
			if ( isset( $_REQUEST['s'] ) ) {
			    $query .= " WHERE `ip` LIKE '%" . $part_ip . "%'";
			}

			/* add calculated values (order and pagination) to our query */
			$query .= " ORDER BY `" . $orderby. "` " . $order . " LIMIT " . $offset . "," . $perpage;
			$date_time_format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$blacklisted_items = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $blacklisted_items as &$blacklisted_item ) {
				$blacklisted_item['add_time'] = is_null( $blacklisted_item['add_time'] ) ? "" : date( $date_time_format, strtotime( $blacklisted_item['add_time'] ) );
			}
			$columns 				= $this->get_columns();
			$hidden 				= array();
			$sortable 				= $this->get_sortable_columns();
			$this->_column_headers	= array( $columns, $hidden, $sortable );
			$this->items 			= $blacklisted_items;
		}

		function column_default( $item, $column_name ) {
			/* setting default view for collumn items */
			switch( $column_name ) {
				case 'ip':
				case 'add_time':
					return $item[ $column_name ];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true ) ;
			}
		}

		function action_message() {
			global $wpdb, $lmtttmpts_options;
			$action_message = array(
				'error' 			=> false,
				'done'  			=> false,
				'error_country'		=> false,
				'wrong_ip_format'	=> ''
			);
			$done_ips = array();
			$prefix = "{$wpdb->prefix}lmtttmpts_";

			if ( isset( $_REQUEST['lmtttmpts_remove_from_denylist'] ) ) {
				check_admin_referer( 'lmtttmpts_remove_from_denylist_' . $_REQUEST['lmtttmpts_remove_from_denylist'], 'lmtttmpts_nonce_name' );
				$ip_list = $_REQUEST['lmtttmpts_remove_from_denylist'];
			} else {
				if(
					( isset( $_POST['action'] )  && $_POST['action']  == 'remove_from_denylist_ips' ) ||
					( isset( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_denylist_ips' )
				) {
					check_admin_referer( 'bulk-' . $this->_args['plural'] );
					$ip_list = isset( $_POST['ip'] ) ? $_POST['ip'] : '';
				}
			}
			if ( isset( $ip_list ) ) {
				if ( empty( $ip_list ) ) {
					$action_message['done'] = __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'No address has been selected', 'limit-attempts' );
				} else {
					$ips = is_array( $ip_list ) ? implode( "','", $ip_list ) : $ip_list;
					$wpdb->query( "DELETE FROM `{$prefix}denylist` WHERE `ip` IN ('{$ips}');" );
					if ( $wpdb->last_error ) {
						$action_message['error'] = $ips . '&nbsp;-&nbsp;' . __( 'Error while deleting from deny list', 'limit-attempts' );
					} else {
						$done_ips = (array)$ip_list;
						$action_message['done'] = implode( ', ', $done_ips ) . '&nbsp;' . ( 1 == count( $done_ips ) ? __( 'has been deleted from deny list', 'limit-attempts' ) : __( 'have been deleted from deny list', 'limit-attempts' ) );
						if ( 1 == $lmtttmpts_options["block_by_htaccess"] ) {
							do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $done_ips );
						}
					}
				}
			}

			if ( isset( $_REQUEST['s'] ) ) {
				$search_request = esc_html( trim( $_REQUEST['s'] ) );
				if ( ! empty( $search_request ) ) {
					if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])?(\.?(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[-0-9])?){0,3}?$/', $search_request ) )
						$action_message['done'] .= ( empty( $action_message['done'] ) ? '' : '<br/>' ) . __( 'Search results for', 'limit-attempts' ) . '&nbsp;' . $search_request;
					else
						$action_message['error'] .= ( empty( $action_message['error'] ) ? '' : '<br/>' ) . sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' );
				}
			}

			if ( ! empty( $action_message['error'] ) ) { ?>
				<div class="error inline lmttmpts_message"><p><strong><?php echo $action_message['error']; ?></strong></div>
			<?php }
			if ( ! empty( $action_message['done'] ) ) { ?>
				<div class="updated inline lmttmpts_message"><p><?php echo $action_message['done'] ?></p></div>
			<?php }
		}
	}
}