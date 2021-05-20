<?php
/**
 * Display list of Emails, which are temporary blocked
 * @package Limit Attempts
 * @since 1.2.6
 */
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( ! class_exists( 'Lmtttmpts_Blocked_List_Email' ) ) {
	class Lmtttmpts_Blocked_List_Email extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'email'			=> __( 'Email', 'limit-attempts' ),
				'block_till'	=> __( 'Date Expires', 'limit-attempts' )
			);
			return $columns;
		}

		function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'email'			=> array( 'email', true ),
				'block_till'	=> array( 'block_till', false )
			);
			return $sortable_columns;
		}

		function column_email( $item ) {
			/* adding action to 'email' collumn */
			$actions = array(
				'reset_block'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&lmtttmpts_reset_block=%s&tab-action=%s', $_REQUEST['page'], $_REQUEST['tab-action'], $item['email'] ), 'lmtttmpts_reset_block_' . $item['email'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Reset Block', 'limit-attempts' ) . '</a>',
			);
			return sprintf( '%1$s %2$s', $item['email'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'reset_blocks'		=> __( 'Reset Block', 'limit-attempts' )
			);
			return $actions;
		}

		function column_cb( $item ) {
			/* customize displaying cb collumn */
			return sprintf(
				'<input type="checkbox" name="email[]" value="%s" />', $item['email']
			);
		}

		function prepare_items() {
			/* preparing table items */
			global $wpdb;
			$prefix = $wpdb->prefix . 'lmtttmpts_';

			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$search_email = trim( htmlspecialchars( $_REQUEST['s'] ) );
				$and = " AND email LIKE '%{$search_email}%' ";
			} else {
				$and = '';
            }

			// query for count emails
			$count_query = "
                SELECT 
                    COUNT( email )
                FROM 
                    {$prefix}failed_attempts 
                WHERE 
                    block = TRUE AND 
                    block_by = 'email'
                    {$and}
            ";

			/* get the total number of Emails */
			$totalitems = $wpdb->get_var( $count_query );
			/* get the value of number of Emails on one page */
			$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );

			/* set pagination arguments */
			$this->set_pagination_args( array(
				"total_items" 	=> $totalitems,
				"per_page" 		=> $perpage
			) );

			$query = "
                SELECT 
                    email,
                    block_till
                FROM 
                    {$prefix}failed_attempts 
                WHERE 
                    block = TRUE AND 
                    block_by = 'email' 
                    {$and}
            ";

			/* the 'orderby' and 'order' values */
			$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_REQUEST['orderby'] : 'block_till';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'asc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
			$offset  = ( $paged - 1 ) * $perpage;
			/* add calculated values (order and pagination) to our query */
			$query .= " ORDER BY {$orderby} {$order} LIMIT {$offset}, {$perpage} ";
			/* get data from our failed_attempts table - list of blocked Emails */
			$blocked_items = $wpdb->get_results( $query, ARRAY_A );
			/* get site date and time format from DB option */
			$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			foreach ( $blocked_items as &$blocked_item ) {
				$blocked_item['email'] = ( $blocked_item['email'] ) ? $blocked_item['email'] : 'N/A';
				/* process block_till date */
				$blocked_item['block_till'] = date( $date_time_format, strtotime( $blocked_item['block_till'] ) );
			}

			$columns 				= $this->get_columns();
			$hidden 				= array();
			$sortable 				= $this->get_sortable_columns();
			$this->_column_headers 	= array( $columns, $hidden, $sortable );
			$this->items 			= $blocked_items;
		}

		function column_default( $item, $column_name ) {
			/* setting default view for collumn items */
			switch( $column_name ) {
				case 'email':
				case 'block_till':
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
			$error = $done = $result_reset_block = $result_reset_block_stat = '';
			$prefix = "{$wpdb->prefix}lmtttmpts_";
			$message_list = array(
				'notice'						=> __( 'Notice:', 'limit-attempts' ),
				'empty_email_list'				=> __( 'No address has been selected', 'limit-attempts' ),
				'block_reset_done'				=> __( 'Block has been reset for', 'limit-attempts' ),
				'block_reset_error'				=> __( 'Error while reseting block for', 'limit-attempts' )
			);
			/* Realization action in table with blocked addresses */

			if (
                isset( $_REQUEST['lmtttmpts_reset_block'] ) &&
                check_admin_referer( 'lmtttmpts_reset_block_' . $_REQUEST['tab-action'], 'lmtttmpts_nonce_name' )
            ) {

				// get ip and id of requested email
				$email_info = $wpdb->get_row( $wpdb->prepare( " 
			    	SELECT 
			    	    ip,
			    	    id_failed_attempts_statistics 
                    FROM 
                        {$prefix}email_list 
                    WHERE 
                        email = %s
            	", $_REQUEST['tab-action'] ), ARRAY_A );

				/* single Email de-block */
				if ( $email_info ) {
					$result_reset_block = $wpdb->update(
						$wpdb->prefix . 'lmtttmpts_failed_attempts',
						array( 'block' => false, 'block_till' => null, 'block_by'  => null ),
						array( 'ip' => $email_info['ip'], 'block_by' => 'email' ),
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
					$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . esc_html( $_REQUEST['tab-action'] );

					if ( $lmtttmpts_options['block_by_htaccess'] ) {
						do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $_REQUEST['tab-action'] ); /* hook for deblocking by Htaccess */
					}
				} else {
					/* if error */
					$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . esc_html( $_REQUEST['tab-action'] );
				}
			} elseif (
                (
                    ( isset( $_POST['action'] ) && $_POST['action'] == 'reset_blocks' ) ||
                    ( isset( $_POST['action2'] ) && $_POST['action2'] == 'reset_blocks' )
                ) && check_admin_referer( 'bulk-' . $this->_args['plural'] )
            ) {
				$done_reset_block = array();
				/* Realization bulk action in table with blocked addresses */
				if ( isset( $_POST['email'] ) ) {
					/* array for loop */
					$emails = $_POST['email'];
					foreach ( $emails as $email ) {

						// get ip and id of requested email
						$email_info = $wpdb->get_row( $wpdb->prepare( " 
			    			SELECT 
			    			    ip,
			    			    id_failed_attempts_statistics 
                            FROM 
			    			    {$prefix}email_list 
                            WHERE 
                                email = %s
            			", $email ), ARRAY_A );

						$result_reset_block = $wpdb->update(
							$wpdb->prefix . 'lmtttmpts_failed_attempts',
							array( 'block' => false, 'block_till' => null, 'block_by' => null ),
							array( 'ip' => $email_info['ip'], 'block_by' => 'email' ),
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

					if ( 1 == $lmtttmpts_options["block_by_htaccess"] && ! empty( $done_reset_block ) ) {
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
				$search_request = esc_html( trim( $_REQUEST['s'] ) );
				if ( ! empty( $search_request ) ) {
					$action_message['done'] .= ( empty( $action_message['done'] ) ? '' : '<br/>' ) . __( 'Search results for', 'limit-attempts' ) . '&nbsp;' . $search_request;
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