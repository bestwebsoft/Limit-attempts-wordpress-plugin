<?php
/**
 * Display list of IP, which are in allowlist
 *
 * @package Limit Attempts
 * @since 1.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'Lmtttmpts_Allowlist' ) ) {
	class Lmtttmpts_Allowlist extends WP_List_Table {
		/**
		 * Adding collumns to table and their view
		 */
		public function get_columns() {
			$columns = array(
				'cb'            => '<input type="checkbox" />',
				'ip'            => __( 'Ip Address', 'limit-attempts' ),
				'add_time'      => __( 'Date Added', 'limit-attempts' ),
			);
			return $columns;
		}

		/**
		 * Seting sortable collumns
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'ip'        => array( 'ip', true ),
				'add_time'  => array( 'add_time', true ),
			);
			return $sortable_columns;
		}

		/**
		 * Adding action to 'ip' collumn
		 *
		 * @param array $item Row item.
		 */
		public function column_ip( $item ) {
			$actions = array(
				'delete'    => '<a href="' . wp_nonce_url( sprintf( '?page=limit-attempts-deny-and-allowlist.php&list=allowlist&lmtttmpts_remove_from_allowlist=%s', $item['ip'] ), 'lmtttmpts_remove_from_allowlist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Delete', 'limit-attempts' ) . '</a>',
			);
			return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		/**
		 * Adding bulk action
		 */
		public function get_bulk_actions() {
			$actions = array(
				'remove_from_allowlist_ips' => __( 'Delete', 'limit-attempts' ),
			);
			return $actions;
		}

		/**
		 * Customize displaying cb collumn
		 *
		 * @param array $item Row item.
		 */
		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="ip[]" value="%s" />',
				$item['ip']
			);
		}

		/**
		 * Preparing table items
		 */
		public function prepare_items() {
			global $wpdb;
			$part_ip = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '';
			/* query for total number of denylisted IPs */
			$count_query = 'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'lmtttmpts_allowlist`';
			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$count_query .= $wpdb->prepare(	' WHERE `ip` LIKE %s', '%' . $wpdb->esc_like( $part_ip ) . '%' );
			}
			/* get the total number of IPs */
			$totalitems = $wpdb->get_var( $count_query );
			/* get the value of number of IPs on one page */
			$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );

			/* set pagination arguments */
			$this->set_pagination_args(
				array(
					'total_items'   => $totalitems,
					'per_page'      => $perpage,
				)
			);

			$orderby = isset( $_REQUEST['orderby'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ), array_keys( $this->get_sortable_columns() ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'add_time';
			$order = ( isset( $_REQUEST['order'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ), array( 'asc', 'desc' ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';
			/* calculate offset for pagination */
			$paged = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < absint( $_REQUEST['paged'] ) ) ? absint( $_REQUEST['paged'] ) : 1;
			$offset = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = 'SELECT `ip`, `add_time` FROM `' . $wpdb->prefix . 'lmtttmpts_allowlist`';
			if ( isset( $_REQUEST['s'] ) ) {
				$query .= $wpdb->prepare(	' WHERE `ip` LIKE %s', '%' . $wpdb->esc_like( $part_ip ) . '%' );
			}
			/* add calculated values (order and pagination) to our query */
			$date_time_format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$query .= ' ORDER BY `' . $orderby . '` ' . $order . ' LIMIT ' . $offset . ',' . $perpage;
			$whitelisted_items = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $whitelisted_items as &$whitelisted_item ) {
				$whitelisted_item['add_time'] = is_null( $whitelisted_item['add_time'] ) ? '' : date( $date_time_format, strtotime( $whitelisted_item['add_time'] ) );
			}
			$columns                = $this->get_columns();
			$hidden                 = array();
			$sortable               = $this->get_sortable_columns();
			$this->_column_headers  = array( $columns, $hidden, $sortable );
			$this->items            = $whitelisted_items;
		}

		/**
		 * Seting sortable collumns
		 *
		 * @param array  $item        Row item.
		 * @param string $column_name Column name.
		 */
		public function column_default( $item, $column_name ) {
			/* setting default view for collumn items */
			switch ( $column_name ) {
				case 'ip':
				case 'add_time':
					return $item[ $column_name ];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true );
			}
		}

		/**
		 * Action message
		 */
		public function action_message() {
			global $wpdb, $lmtttmpts_options;
			$action_message = array(
				'error'             => false,
				'done'              => false,
				'error_country'     => false,
				'wrong_ip_format'   => '',
			);
			$done = '';

			if ( isset( $_REQUEST['lmtttmpts_remove_from_allowlist'] ) ) {
				check_admin_referer( 'lmtttmpts_remove_from_allowlist_' . sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_remove_from_allowlist'] ) ), 'lmtttmpts_nonce_name' );
				$ip_list = $_REQUEST['lmtttmpts_remove_from_allowlist'];
			} else {
				if (
					( isset( $_POST['action'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'remove_from_allowlist_ips' ) ||
					( isset( $_POST['action2'] ) && sanitize_text_field( wp_unslash( $_POST['action2'] ) ) === 'remove_from_allowlist_ips' )
				) {
					check_admin_referer( 'bulk-' . $this->_args['plural'] );
					$ip_list = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
				}
			}
			if ( isset( $ip_list ) ) {
				if ( empty( $ip_list ) ) {
					$action_message['done'] = __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'No address has been selected', 'limit-attempts' );
				} else {
					$ips = is_array( $ip_list ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $ip_list ) ) : sanitize_text_field( wp_unslash( $ip_list ) );

					$ips_placeholders = implode( ', ', array_fill( 0, count( (array) $ips ), '%s' ) );

					$wpdb->query(
						$wpdb->prepare(
							'DELETE FROM `' . $wpdb->prefix . 'lmtttmpts_allowlist` WHERE `ip` IN (' . $ips_placeholders . ');',
							(array) $ips
						)
					);
					if ( $wpdb->last_error ) {
						$action_message['error'] = $ips . '&nbsp;-&nbsp;' . __( 'Error while deleting from allowlist', 'limit-attempts' );
					} else {
						$done_ips = (array) $ip_list;
						$action_message['done'] = implode( ', ', $done_ips ) . '&nbsp;' . ( 1 == count( $done_ips ) ? __( 'has been deleted from allow list', 'limit-attempts' ) : __( 'have been deleted from allow list', 'limit-attempts' ) );

						if ( 1 == $lmtttmpts_options['block_by_htaccess'] ) {
							do_action( 'lmtttmpts_htaccess_hook_for_delete_from_whitelist', $done_ips );
						}
					}
				}
			}

			if ( isset( $_REQUEST['s'] ) ) {
				$search_request = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
				if ( ! empty( $search_request ) ) {
					if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])?(\.?(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[-0-9])?){0,3}?$/', $search_request ) ) {
						$action_message['done'] .= ( empty( $action_message['done'] ) ? '' : '<br/>' ) . __( 'Search results for', 'limit-attempts' ) . '&nbsp;' . $search_request;
					} else {
						$action_message['error'] .= ( empty( $action_message['error'] ) ? '' : '<br/>' ) . sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' );
					}
				}
			}

			if ( ! empty( $action_message['error'] ) ) { ?>
				<div class="error inline lmttmpts_message"><p><strong><?php echo esc_html( $action_message['error'] ); ?></strong></div>
				<?php
			}
			if ( ! empty( $action_message['done'] ) ) {
				?>
				<div class="updated inline lmttmpts_message"><p><?php echo esc_html( $action_message['done'] ); ?></p></div>
				<?php
			}
		}
	}
}
