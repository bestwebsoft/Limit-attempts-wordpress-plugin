<?php 
/**
 * Display list of IP, which are temporary blocked
 * @package Limit Attempts
 * @since 1.1.3
 */
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( ! class_exists( 'Lmtttmpts_Blocked_list' ) ) {
	class Lmtttmpts_Blocked_list extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'ip'			=> __( 'Ip address', 'limit-attempts' ),
				'block_till'	=> __( 'The lock expires', 'limit-attempts' )
			);
			return $columns;
		}

		function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'ip'			=> array( 'ip', true ),
				'block_till'	=> array( 'block_till', false )
			);
			return $sortable_columns;
		}

		function column_ip( $item ) {
			/* adding action to 'ip' collumn */
			$actions = array(
				'reset_block'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&action=%s&lmtttmpts_reset_block=%s', $_REQUEST['page'], $_REQUEST['action'], $item['ip'] ), 'lmtttmpts_reset_block_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Reset block', 'limit-attempts' ) . '</a>'
			);
			return sprintf('%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'reset_blocks'	=> __( 'Reset block', 'limit-attempts' )
			);
			return $actions;
		}

		function column_cb( $item ) {
			/* customize displaying cb collumn */
			return sprintf(
				'<input type="checkbox" name="ip[]" value="%s" />', $item['ip']
			);
		}

		function prepare_items() {
			/* preparing table items */
			global $wpdb;
			$prefix = $wpdb->prefix . 'lmtttmpts_';
			$part_ip = isset( $_REQUEST['s'] ) ? trim( htmlspecialchars( $_REQUEST['s'] ) ) : '';
			/* query for total number of IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "failed_attempts` WHERE `block` = true";
			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_REQUEST['s'] ) ) ) );
				if ( 0 != $search_ip || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$count_query .= " AND `ip_int` = " . $search_ip ." OR `ip` LIKE '%" . $part_ip . "%'";
				}
			}
			/* get the total number of IPs */
			$totalitems = $wpdb->get_var( $count_query );
			/* get the value of number of IPs on one page */
			$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );
			/* the total number of pages */
			$totalpages = ceil( $totalitems / $perpage );
			/* set pagination arguments */
			$this->set_pagination_args( array(
				"total_items" 	=> $totalitems,
				"total_pages" 	=> $totalpages,
				"per_page" 		=> $perpage
			) );

			/* the 'orderby' and 'order' values */
			$orderby = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ? $_REQUEST['orderby'] : 'block_till';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'asc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
			if ( 0 > $totalpages && $paged > $totalpages )
				$paged = $totalpages;
			$offset  = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `block_till` FROM `" . $prefix . "failed_attempts` WHERE `block` = true";
			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_REQUEST['s'] ) ) ) );
				if ( 0 != $search_ip || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$query .= " AND ( `ip_int`={$search_ip} OR `ip` LIKE '%{$part_ip}%')";
				}
			}
			/* add calculated values (order and pagination) to our query */
			$query .= " ORDER BY `" . $orderby. "` " . $order . " LIMIT " . $offset . "," . $perpage;
			/* get data from our failed_attempts table - list of blocked IPs */
			$blocked_items = $wpdb->get_results( $query, ARRAY_A );
			/* get site date and time format from DB option */
			$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			foreach ( $blocked_items as &$blocked_item ) {
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
				case 'ip':
				case 'block_till':
					return $item[ $column_name ];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true ) ;
			}
		}

		function pagination( $which ) {
			if ( empty( $this->_pagination_args ) ) {
				return;
			}

			$total_items = $this->_pagination_args['total_items'];
			$total_pages = $this->_pagination_args['total_pages'];
			$infinite_scroll = false;
			if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
				$infinite_scroll = $this->_pagination_args['infinite_scroll'];
			}

			if ( 'top' === $which && $total_pages > 1 && method_exists( $this->screen, 'render_screen_reader_content' ) ) {
				$this->screen->render_screen_reader_content( 'heading_pagination' );
			}

			$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

			$current = $this->get_pagenum();

			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

			$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

			$additional_inputs = '';
			$requests_indexes  = array( 'orderby', 'order', 's' );
			foreach ( $requests_indexes as $index ) {
				if ( isset( $_REQUEST[ $index ] ) ) {
					$request = esc_html( trim( $_REQUEST[ $index ] ) );
					if ( ! empty( $request ) ) {
						$current_url = add_query_arg( $index, $request, $current_url );
						$additional_inputs .= '<input type="hidden" name="' . $index . '" value="' . $request . '" />';
					}
				}
			}

			$page_links = array();

			$total_pages_before = '<span class="paging-input">';
			$total_pages_after  = '</span>';

			$disable_first = $disable_last = $disable_prev = $disable_next = false;

	 		if ( $current == 1 ) {
				$disable_first = true;
				$disable_prev = true;
	 		}
			if ( $current == 2 ) {
				$disable_first = true;
			}
	 		if ( $current == $total_pages ) {
				$disable_last = true;
				$disable_next = true;
	 		}
			if ( $current == $total_pages - 1 ) {
				$disable_last = true;
			}

			if ( $disable_first ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( remove_query_arg( 'paged', $current_url ) ),
					__( 'First page' ),
					'&laquo;'
				);
			}

			if ( $disable_prev ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
					__( 'Previous page' ),
					'&lsaquo;'
				);
			}

			if ( 'bottom' === $which ) {
				$html_current_page  = $current;
				$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input">';
			} else {
				$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' />",
					'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
					$current,
					strlen( $total_pages )
				);
			}
			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

			if ( $disable_next ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
					__( 'Next page' ),
					'&rsaquo;'
				);
			}

			if ( $disable_last ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
					__( 'Last page' ),
					'&raquo;'
				);
			}

			$pagination_links_class = 'pagination-links';
			if ( ! empty( $infinite_scroll ) ) {
				$pagination_links_class = ' hide-if-js';
			}
			$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

			if ( $total_pages ) {
				$page_class = $total_pages < 2 ? ' one-page' : '';
			} else {
				$page_class = ' no-pages';
			}
			$this->_pagination = "<div class='tablenav-pages{$page_class}'>{$output}{$additional_inputs}</div>";

			echo $this->_pagination;
		}
		
		function action_message() {
			global $wpdb, $lmtttmpts_options;
			$action_message = array(
				'error' 			=> false,
				'done'  			=> false,
				'error_country'		=> false,
				'wrong_ip_format'	=> ''
			);
			$error = $done = '';
			$prefix = "{$wpdb->prefix}lmtttmpts_";
			$message_list = array(
				'notice'						=> __( 'Notice:', 'limit-attempts' ),
				'empty_ip_list'					=> __( 'No address has been selected', 'limit-attempts' ),
				'block_reset_done'				=> __( 'Block has been reset for', 'limit-attempts' ),
				'block_reset_error'				=> __( 'Error while reseting block for', 'limit-attempts' ),
			);
			/* Realization action in table with blocked addresses */
			if ( isset( $_REQUEST['lmtttmpts_reset_block'] ) && check_admin_referer( 'lmtttmpts_reset_block_' . $_REQUEST['lmtttmpts_reset_block'], 'lmtttmpts_nonce_name' ) ) {
				/* single IP de-block */
				$result_reset_block = $wpdb->update(
					$wpdb->prefix . 'lmtttmpts_failed_attempts',
					array( 'block' => false ),
					array( 'ip_int' => sprintf( '%u', ip2long( $_REQUEST['lmtttmpts_reset_block'] ) ) ),
					array( '%s' ),
					array( '%s' )
				);

				if ( false !== $result_reset_block ) {
					/* if operation with DB was succesful */
					$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . esc_html( $_REQUEST['lmtttmpts_reset_block'] );

					if ( 1 == $lmtttmpts_options["block_by_htaccess"] ) {
						do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $_REQUEST['lmtttmpts_reset_block'] ); /* hook for deblocking by Htaccess */
					}
				} else {
					/* if error */
					$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . esc_html( $_REQUEST['lmtttmpts_reset_block'] );
				}
			} elseif ( ( ( isset( $_POST['action'] ) && $_POST['action'] == 'reset_blocks' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'reset_blocks' ) ) && check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				$done_reset_block = array();
				/* Realization bulk action in table with blocked addresses */
				if ( isset( $_POST['ip'] ) ) {
					/* array for loop */
					$ips = $_POST['ip'];
					foreach ( $ips as $ip ) {
						$result_reset_block = $wpdb->update(
							$wpdb->prefix . 'lmtttmpts_failed_attempts',
							array( 'block' => false ),
							array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
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

					if ( 1 == $lmtttmpts_options["block_by_htaccess"] && ! empty( $done_reset_block ) ) {
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
			}

			if ( isset( $_REQUEST['s'] ) ) {
				$search_request = esc_html( trim( $_REQUEST['s'] ) );
				if ( ! empty( $search_request ) ) {
					if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])?(\.?(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[-0-9])?){0,3}?$/', $search_request ) )
						$action_message['done'] .= ( empty( $action_message['done'] ) ? '' : '<br/>' ) . __( 'Search results for', 'limit-attempts' ) . '&nbsp;' . $search_request;
					else
						$action_message['error'] .= ( empty( $action_message['error'] ) ? '' : '<br/>' ) . __( 'Wrong format or it does not lie in range 0.0.0.0 - 255.255.255.255.', 'limit-attempts' );
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

if ( ! function_exists( 'lmtttmpts_display_blocked' ) ) {
	function lmtttmpts_display_blocked( $plugin_basename ) { ?>
		<div id="lmtttmpts_blocked">
			<?php $lmtttmpts_blocked_list = new Lmtttmpts_Blocked_list();
			$lmtttmpts_blocked_list->action_message();
			$lmtttmpts_blocked_list->prepare_items(); ?>
			<form method="get" action="admin.php">
				<?php $lmtttmpts_blocked_list->search_box( __( 'Search IP', 'limit-attempts' ), 'search_blocked_ip' );?>
				<input type="hidden" name="page" value="limit-attempts.php" />
				<input type="hidden" name="action" value="blocked" />
			</form>
			<form method="post" action="admin.php?page=limit-attempts.php&amp;action=blocked">
				<?php $lmtttmpts_blocked_list->display(); ?>
			</form>
		</div>
	<?php }
}

?>