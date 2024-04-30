<?php
/**
 * Display list of IP, which are temporary blocked
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

if ( ! class_exists( 'Lmtttmpts_Blocked_List' ) ) {
	class Lmtttmpts_Blocked_List extends WP_List_Table {
		/**
		 * Adding collumns to table and their view
		 */
		public function get_columns() {
			$columns = array(
				'cb'            => '<input type="checkbox" />',
				'ip'            => __( 'IP Address', 'limit-attempts' ),
				'block_till'    => __( 'Date Expires', 'limit-attempts' ),
			);
			return $columns;
		}

		/**
		 * Seting sortable collumns
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'ip'            => array( 'ip', true ),
				'block_till'    => array( 'block_till', false ),
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
				'reset_block'   => '<a href="' . wp_nonce_url( sprintf( '?page=limit-attempts-blocked.php&lmtttmpts_reset_block=%s', $item['ip'] ), 'lmtttmpts_reset_block_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Reset Block', 'limit-attempts' ) . '</a>',
				'add_to_allowlist'  => '<a href="' . wp_nonce_url( sprintf( '?page=limit-attempts-blocked.php&lmtttmpts_add_to_allowlist=%s', $item['ip'] ), 'lmtttmpts_add_to_allowlist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Add to Allow list', 'limit-attempts' ) . '</a>',
			);
			return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		/**
		 * Adding bulk action
		 */
		public function get_bulk_actions() {
			$actions = array(
				'reset_blocks'      => __( 'Reset Block', 'limit-attempts' ),
				'add_to_allowlist'  => __( 'Add to Allow List', 'limit-attempts' ),
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

			$and = '';

			$prefix = $wpdb->prefix . 'lmtttmpts_';
			$part_ip = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '';

			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( ' ', '', trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) ) ) );
				if ( 0 != $search_ip || preg_match( '/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i', $part_ip ) ) {
					$and = $wpdb->prepare(
						' AND ( ip_int = %d OR ip LIKE %s )',
						$wpdb->esc_like( $search_ip ),
						'%' . $wpdb->esc_like( $part_ip ) . '%'
					);
				}
			}

			/* query for total number of IPs */
			$count_query =
				'SELECT 
					COUNT( ip )
				FROM 
					' . $wpdb->prefix . 'lmtttmpts_failed_attempts 
				WHERE 
					block = TRUE AND 
					block_by = "ip"
					' . $and . '
				';

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

			/* the 'orderby' and 'order' values */
			$orderby = isset( $_REQUEST['orderby'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ), array_keys( $this->get_sortable_columns() ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'block_till';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ), array( 'asc', 'desc' ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < absint( $_REQUEST['paged'] ) ) ? absint( $_REQUEST['paged'] ) : 1;
			$offset  = ( $paged - 1 ) * $perpage;

			/* general query */
			$query =
				'SELECT 
					ip, 
					block_till 
				FROM 
					' . $wpdb->prefix . 'lmtttmpts_failed_attempts 
				WHERE 
					block = TRUE AND 
					block_by = "ip" 
					' . $and . '
        ';

			/* add calculated values (order and pagination) to our query */
			$query .= ' ORDER BY `' . $orderby . '` ' . $order . ' LIMIT ' . $offset . ',' . $perpage;
			/* get data from our failed_attempts table - list of blocked IPs */
			$blocked_items = $wpdb->get_results( $query, ARRAY_A );
			/* get site date and time format from DB option */
			$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			foreach ( $blocked_items as &$blocked_item ) {
				/* process block_till date */
				$blocked_item['block_till'] = date( $date_time_format, strtotime( $blocked_item['block_till'] ) );
			}

			$columns                = $this->get_columns();
			$hidden                 = array();
			$sortable               = $this->get_sortable_columns();
			$this->_column_headers  = array( $columns, $hidden, $sortable );
			$this->items            = $blocked_items;
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
				case 'block_till':
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
			$error = '';
			$done  = '';
			$message_list = array(
				'notice'                        => __( 'Notice:', 'limit-attempts' ),
				'empty_ip_list'                 => __( 'No address has been selected', 'limit-attempts' ),
				'block_reset_done'              => __( 'Block has been reset for', 'limit-attempts' ),
				'block_reset_error'             => __( 'Error while reseting block for', 'limit-attempts' ),
				'single_add_to_allowlist_done'  => __( 'IP address was added to allow list', 'limit-attempts' ),
				'add_to_allowlist_done'         => __( 'IP addresses were added to allow list', 'limit-attempts' ),
			);
			/* Realization action in table with blocked addresses */
			if (
				isset( $_GET['lmtttmpts_add_to_allowlist'] ) &&
				check_admin_referer( 'lmtttmpts_add_to_allowlist_' . sanitize_text_field( wp_unslash( $_GET['lmtttmpts_add_to_allowlist'] ) ), 'lmtttmpts_nonce_name' )
			) {
				if ( filter_var( sanitize_text_field( wp_unslash( $_GET['lmtttmpts_add_to_allowlist'] ) ), FILTER_VALIDATE_IP ) ) {
					$ip = sanitize_text_field( wp_unslash( $_GET['lmtttmpts_add_to_allowlist'] ) );
					$ip_int = sprintf( '%u', ip2long( $ip ) );

					/* single IP de-block */
					$result_reset_block = $wpdb->update(
						$wpdb->prefix . 'lmtttmpts_failed_attempts',
						array(
							'block' => false,
							'block_till' => null,
							'block_by' => null,
						),
						array( 'ip_int' => sprintf( '%u', $ip_int ) )
					);
					/* single IP add to allow list */
					if ( false !== $result_reset_block ) {
						$wpdb->insert(
							$wpdb->prefix . 'lmtttmpts_allowlist',
							array(
								'ip' => $ip,
								'add_time' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
							)
						);

						$action_message['done'] = $message_list['single_add_to_allowlist_done'] . ':&nbsp;' . esc_html( $ip );

						if ( $lmtttmpts_options['block_by_htaccess'] ) {
							do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $ip );
						}
					}
				}
			} elseif (
				isset( $_REQUEST['lmtttmpts_reset_block'] ) &&
				check_admin_referer( 'lmtttmpts_reset_block_' . sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_reset_block'] ) ), 'lmtttmpts_nonce_name' )
			) {
				/* single IP de-block */
				$result_reset_block = $wpdb->update(
					$wpdb->prefix . 'lmtttmpts_failed_attempts',
					array(
						'block' => false,
						'block_till' => null,
						'block_by' => null,
					),
					array(
						'ip_int' => sprintf( '%u', ip2long( sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_reset_block'] ) ) ) ),
						'block_by' => 'ip',
					),
					array( '%s' ),
					array( '%s' )
				);

				if ( false !== $result_reset_block ) {
					/* if operation with DB was succesful */
					$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_reset_block'] ) );

					if ( 1 == $lmtttmpts_options['block_by_htaccess'] ) {
						do_action( 'lmtttmpts_htaccess_hook_for_reset_block', trim( sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_reset_block'] ) ) ) ); /* hook for deblocking by Htaccess */
					}
				} else {
					/* if error */
					$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_reset_block'] ) );
				}
			} elseif ( ( ( isset( $_POST['action'] ) && 'reset_blocks' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) || ( isset( $_POST['action2'] ) && 'reset_blocks' === sanitize_text_field( wp_unslash( $_POST['action2'] ) ) ) ) && check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				$done_reset_block = array();
				/* Realization bulk action in table with blocked addresses */
				if ( isset( $_POST['ip'] ) ) {
					/* array for loop */
					$ips = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['ip'] ) );
					foreach ( $ips as $ip ) {
						$result_reset_block = $wpdb->update(
							$wpdb->prefix . 'lmtttmpts_lmtttmpts_failed_attempts',
							array(
								'block' => false,
								'block_till' => null,
								'block_by' => null,
							),
							array(
								'ip_int' => sprintf( '%u', ip2long( $ip ) ),
								'block_by' => 'ip',
							),
							array( '%s' ),
							array( '%s' )
						);
						if ( false !== $result_reset_block ) {
							/* if success */
							$done .= empty( $done ) ? $ip : ', ' . $ip;
							$done_reset_block[] = $ip;
						} else {
							/* if error */
							$error .= empty( $error ) ? $ip : ', ' . $ip;
						}
					}

					if ( 1 == $lmtttmpts_options['block_by_htaccess'] && ! empty( $done_reset_block ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $done_reset_block ); /* hook for deblocking by Htaccess */
					}

					if ( ! empty( $done ) ) {
						/* if some IPs were de-blocked */
						$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . $done;
					}
					if ( ! empty( $error ) ) {
						/* if some IPs were not de-blocked because of error in DB */
						$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . $error;
					}
				} else {
					/* if empty IP list */
					$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
				}
			} elseif ( ( ( isset( $_POST['action'] ) && 'add_to_allowlist' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) || ( isset( $_POST['action2'] ) && 'add_to_allowlist' === sanitize_text_field( wp_unslash( $_POST['action2'] ) ) ) ) && check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				$done_add_to_whitelist = array();
				/* Realization bulk action in table with blocked addresses */
				if ( isset( $_POST['ip'] ) ) {
					/* array for loop */
					$ips = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['ip'] ) );
					foreach ( $ips as $ip ) {
						$ip_int = sprintf( '%u', ip2long( $ip ) );
						$result_reset_block = $wpdb->update(
							$wpdb->prefix . 'lmtttmpts_failed_attempts',
							array(
								'block' => false,
								'block_till' => null,
								'block_by' => null,
							),
							array(
								'ip_int' => $ip_int,
								'block_by' => 'ip',
							),
							array( '%s' ),
							array( '%s' )
						);
						/* if success */
						if ( false !== $result_reset_block ) {
							$wpdb->insert(
								$wpdb->prefix . 'lmtttmpts_allowlist',
								array(
									'ip' => $ip,
									'add_time' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
								)
							);

							$action_message['done'] = $message_list['single_add_to_allowlist_done'] . ':&nbsp;' . esc_html( $ip );

							if ( $lmtttmpts_options['block_by_htaccess'] ) {
								do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $ip );
							}
						}
					}
					if ( isset( $lmtttmpts_options['block_by_htaccess'] ) && ! empty( $done_add_to_whitelist ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $done_add_to_whitelist );
					}
					$action_message['done'] = $message_list['add_to_allowlist_done'] . '&nbsp;' . $done;
				} else {
					/* if empty IP list */
					$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
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
