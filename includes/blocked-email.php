<?php
/**
 * Display list of Emails, which are temporary blocked
 *
 * @package Limit Attempts
 * @since 1.2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'Lmtttmpts_Blocked_List_Email' ) ) {
	class Lmtttmpts_Blocked_List_Email extends WP_List_Table {
		/**
		 * Adding collumns to table and their view
		 */
		public function get_columns() {
			$columns = array(
				'cb'            => '<input type="checkbox" />',
				'email'         => __( 'Email', 'limit-attempts' ),
				'block_till'    => __( 'Date Expires', 'limit-attempts' ),
			);
			return $columns;
		}

		/**
		 * Seting sortable collumns
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'email'         => array( 'email', true ),
				'block_till'    => array( 'block_till', false ),
			);
			return $sortable_columns;
		}

		/**
		 * Adding action to 'email' collumn
		 *
		 * @param array $item Row item.
		 */
		public function column_email( $item ) {
			$actions = array(
				'reset_block'   => '<a href="' . wp_nonce_url( sprintf( '?page=limit-attempts-blocked.php&lmtttmpts_reset_block=%s&tab-action=%s', sanitize_text_field( wp_unslash( $_REQUEST['tab-action'] ) ), $item['email'] ), 'lmtttmpts_reset_block_' . $item['email'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Reset Block', 'limit-attempts' ) . '</a>',
			);
			return sprintf( '%1$s %2$s', $item['email'], $this->row_actions( $actions ) );
		}

		/**
		 * Adding bulk action
		 */
		public function get_bulk_actions() {
			$actions = array(
				'reset_blocks'      => __( 'Reset Block', 'limit-attempts' ),
			);
			return $actions;
		}

		/**
		 * Customize displaying cb collumn
		 *
		 * @param array $item Row item.
		 */
		public function column_cb( $item ) {
			/* customize displaying cb collumn */
			return sprintf(
				'<input type="checkbox" name="email[]" value="%s" />',
				$item['email']
			);
		}

		/**
		 * Preparing table items
		 */
		public function prepare_items() {
			/* preparing table items */
			global $wpdb;
			$prefix = $wpdb->prefix . 'lmtttmpts_';

			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$search_email = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
				$and = $wpdb->prepare(
					' AND email LIKE %s',
					'%' . $wpdb->esc_like( $search_email ) . '%'
				);
			} else {
				$and = '';
			}

			/* query for count emails */
			$count_query = '
				SELECT 
					COUNT( email )
				FROM 
					' . $wpdb->prefix . 'lmtttmpts_failed_attempts 
				WHERE 
					block = TRUE AND 
					block_by = "email"
					' . $and . '
			';

			/* get the total number of Emails */
			$totalitems = $wpdb->get_var( $count_query );
			/* get the value of number of Emails on one page */
			$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );

			/* set pagination arguments */
			$this->set_pagination_args(
				array(
					'total_items'   => $totalitems,
					'per_page'      => $perpage,
				)
			);

			$query = '
				SELECT 
					email,
					block_till
				FROM 
					' . $wpdb->prefix . 'lmtttmpts_failed_attempts 
				WHERE 
					block = TRUE AND 
					block_by = "email"
					' . $and . '
			';

			/* the 'orderby' and 'order' values */
			$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ), array_keys( $this->get_sortable_columns() ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'block_till';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ), array( 'asc', 'desc' ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < absint( $_REQUEST['paged'] ) ) ? absint( $_REQUEST['paged'] ) : 1;
			$offset  = ( $paged - 1 ) * $perpage;
			/* add calculated values (order and pagination) to our query */
			$query .= ' ORDER BY ' . $orderby . ' ' . $order . ' LIMIT ' . $offset . ', ' . $perpage;
			/* get data from our failed_attempts table - list of blocked Emails */
			$blocked_items = $wpdb->get_results( $query, ARRAY_A );
			/* get site date and time format from DB option */
			$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			foreach ( $blocked_items as $key => $blocked_item ) {
				$blocked_items[ $key ]['email'] = ( $blocked_item['email'] ) ? $blocked_item['email'] : 'N/A';
				/* process block_till date */
				$blocked_items[ $key ]['block_till'] = date( $date_time_format, strtotime( $blocked_item['block_till'] ) );
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
				case 'email':
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

			$result_reset_block      = '';
			$result_reset_block_stat = '';

			$message_list = array(
				'notice'                        => __( 'Notice:', 'limit-attempts' ),
				'empty_email_list'              => __( 'No address has been selected', 'limit-attempts' ),
				'block_reset_done'              => __( 'Block has been reset for', 'limit-attempts' ),
				'block_reset_error'             => __( 'Error while reseting block for', 'limit-attempts' ),
			);
			/* Realization action in table with blocked addresses */

			if (
				isset( $_REQUEST['lmtttmpts_reset_block'] ) &&
				check_admin_referer( 'lmtttmpts_reset_block_' . sanitize_text_field( wp_unslash( $_REQUEST['tab-action'] ) ), 'lmtttmpts_nonce_name' )
			) {

				/* get ip and id of requested email */
				$email_info = $wpdb->get_row(
					$wpdb->prepare(
						' 
			    	SELECT 
							ip,
							id_failed_attempts_statistics 
						FROM 
							' . $wpdb->prefix . 'lmtttmpts_email_list 
						WHERE 
							email = %s
						',
						$wpdb->esc_like( sanitize_text_field( wp_unslash( $_REQUEST['tab-action'] ) ) )
					),
					ARRAY_A
				);

				/* single Email de-block */
				if ( $email_info ) {
					$result_reset_block = $wpdb->update(
						$wpdb->prefix . 'lmtttmpts_failed_attempts',
						array(
							'block' => false,
							'block_till' => null,
							'block_by'  => null,
						),
						array(
							'ip' => $email_info['ip'],
							'block_by' => 'email',
						),
						array( '%s' ),
						array( '%s' )
					);

					$result_reset_block_stat = $wpdb->update(
						$wpdb->prefix . 'lmtttmpts_all_failed_attempts',
						array( 'block' => false ),
						array( 'id' => $email_info['id_failed_attempts_statistics'] ),
						array( '%s' )
					);
				}

				if ( false !== $result_reset_block && false !== $result_reset_block_stat ) {
					/* if operation with DB was succesful */
					$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . sanitize_text_field( wp_unslash( $_REQUEST['tab-action'] ) );

					if ( $lmtttmpts_options['block_by_htaccess'] ) {
						do_action( 'lmtttmpts_htaccess_hook_for_reset_block', sanitize_text_field( wp_unslash( $_REQUEST['tab-action'] ) ) ); /* hook for deblocking by Htaccess */
					}
				} else {
					/* if error */
					$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . sanitize_text_field( wp_unslash( $_REQUEST['tab-action'] ) );
				}
			} elseif (
				(
					( isset( $_POST['action'] ) && 'reset_blocks' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) ||
					( isset( $_POST['action2'] ) && 'reset_blocks' === sanitize_text_field( wp_unslash( $_POST['action2'] ) ) )
				) && check_admin_referer( 'bulk-' . $this->_args['plural'] )
			) {
				$done_reset_block = array();
				/* Realization bulk action in table with blocked addresses */
				if ( isset( $_POST['email'] ) ) {
					/* array for loop */
					$emails = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['email'] ) );
					foreach ( $emails as $email ) {

						/* get ip and id of requested email */
						$email_info = $wpdb->get_row(
							$wpdb->prepare(
								' 
			    			SELECT 
									ip,
									id_failed_attempts_statistics 
								FROM 
									' . $wpdb->prefix . 'email_list 
								WHERE 
									email = %s
            		',
								$wpdb->esc_like( $email )
							),
							ARRAY_A
						);

						$result_reset_block = $wpdb->update(
							$wpdb->prefix . 'lmtttmpts_failed_attempts',
							array(
								'block' => false,
								'block_till' => null,
								'block_by' => null,
							),
							array(
								'ip' => $email_info['ip'],
								'block_by' => 'email',
							),
							array( '%s' ),
							array( '%s' )
						);

						$result_reset_block_stat = $wpdb->update(
							$wpdb->prefix . 'lmtttmpts_all_failed_attempts',
							array( 'block' => false ),
							array( 'id' => $email_info['id_failed_attempts_statistics'] ),
							array( '%s' )
						);

						if ( false !== $result_reset_block && false !== $result_reset_block_stat ) {
							/* if success */
							$done .= empty( $done ) ? $email : ', ' . $email;
							$done_reset_block[] = $email;
						} else {
							/* if error */
							$error .= empty( $error ) ? $email : ', ' . $email;
						}
					}

					if ( 1 == $lmtttmpts_options['block_by_htaccess'] && ! empty( $done_reset_block ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $done_reset_block ); /* hook for deblocking by Htaccess */
					}

					if ( ! empty( $done ) ) {
						/* if some Emails were de-blocked */
						$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . $done;
					}
					if ( ! empty( $error ) ) {
						/* if some Emails were not de-blocked because of error in DB */
						$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . $error;
					}
				} else {
					/* if empty Email list */
					$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
				}
			}

			if ( isset( $_REQUEST['s'] ) ) {
				$search_request = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
				if ( ! empty( $search_request ) ) {
					$action_message['done'] .= ( empty( $action_message['done'] ) ? '' : '<br/>' ) . __( 'Search results for', 'limit-attempts' ) . '&nbsp;' . $search_request;
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
