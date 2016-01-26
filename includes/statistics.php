<?php 
/**
 * Display statistics
 * @package Limit Attempts
 * @since 1.1.3
 */

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( ! class_exists( 'Lmtttmpts_Statistics' ) ) {
	class Lmtttmpts_Statistics extends WP_List_Table { 
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'				=> '<input type="checkbox" />',
				'ip'				=> __( 'Ip address', 'limit-attempts' ),
				'failed_attempts'	=> __( 'Number of failed attempts', 'limit-attempts' ),
				'block_quantity'	=> __( 'Number of blocks', 'limit-attempts' ),
				'status'			=> __( 'Status', 'limit-attempts' )
			);
			return $columns;
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'clear_statistics_for_ips'	=> __( 'Delete statistics entry', 'limit-attempts' )
			);
			return $actions;
		}

		function column_cb( $item ) {
			/* customize displaying cb collumn */
			return sprintf(
				'<input type="checkbox" name="ip[]" value="%s" />', $item['ip']
			);
		}

		function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'ip'				=> array( 'ip', true ),
				'failed_attempts'	=> array( 'failed_attempts', false ),
				'block_quantity'	=> array( 'block_quantity', false )
			);
			return $sortable_columns;
		}

		function single_row( $item ) {
			/* add class to non 'not_blocked' rows (black-, whitelist or blocked) */
			$row_class = '';
			if ( isset( $item['row_class'] ) ) {
				/* if IP is black-, whitelisted or blocked */
				$row_class = ' class="' . $item['row_class'] . '"';
			}

			echo '<tr' . $row_class . '>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		function prepare_items() { /* preparing table items */
			global $wpdb, $lmtttmpts_options;
			$prefix = $wpdb->prefix . 'lmtttmpts_';
			$part_ip = isset( $_REQUEST['s'] ) ? trim( htmlspecialchars( $_REQUEST['s'] ) ) : '';
			/* query for total number of IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "all_failed_attempts`";
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_REQUEST['s'] ) ) );
				if ( 0 != $search_ip || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$count_query .= " WHERE `ip_int` = " . $search_ip . " OR `ip` LIKE '%" . $part_ip . "%'";
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

			/* the 'orderby' and 'order' values - If no sort, default to IP */
			$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) && 'ip' != $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'ip_int';
			$order = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array('asc', 'desc') ) ) ? $_REQUEST['order'] : 'asc';

			/* calculate offset for pagination */
			$paged = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
			if ( 0 > $totalpages && $paged > $totalpages )
				$paged = $totalpages;
			/* set pagination arguments */
			$offset = ( $paged - 1 ) * $perpage;
			/* general query */
			$query = "SELECT `ip`, `failed_attempts`, `block_quantity` FROM `" . $prefix . "all_failed_attempts`";
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_REQUEST['s'] ) ) );
				if ( 0 != $search_ip || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$query .= " WHERE `ip_int` = " . $search_ip. " OR `ip` LIKE '%" . $part_ip . "%'";
				}
			}
			/* add calculated values (order and pagination) to our query */
			$query .= " ORDER BY `" . $orderby . "` " . $order . " LIMIT " . $offset . "," . $perpage;
			/* get data from 'all_failed_attempts' table */
			$statistics = $wpdb->get_results( $query, ARRAY_A );
			if ( $statistics ) {
				/* loop - we calculate and add 'status' column and class data */
				foreach ( $statistics as &$statistic ) {
					if ( lmtttmpts_is_ip_in_table( $statistic['ip'], 'blacklist' ) ) {
						$statistic['status'] = '<a href="?page=' . $_REQUEST['page'] . '&action=blacklist&s=' . $statistic['ip'] . '">' . __( 'blacklisted', 'limit-attempts' ) . '</a>';
						$statistic['row_class'] = 'lmtttmpts_blacklist';
					} elseif ( lmtttmpts_is_ip_in_table( $statistic['ip'], 'whitelist' ) ) {
						$statistic['status'] = '<a href="?page=' . $_REQUEST['page'] . '&action=whitelist&s=' . $statistic['ip'] . '">' . __( 'whitelisted', 'limit-attempts' ) . '</a>';
						$statistic['row_class'] = 'lmtttmpts_whitelist';
					} elseif ( lmtttmpts_is_ip_blocked( $statistic['ip'] ) ) {
						$statistic['status'] = '<a href="?page=' . $_REQUEST['page'] . '&action=blocked&s=' . $statistic['ip'] . '">' . __( 'blocked', 'limit-attempts' ) . '</a>';
						$statistic['row_class'] = 'lmtttmpts_blocked';
					} else {
						$statistic['status'] = __( 'not blocked', 'limit-attempts' );
					}
				}
			}

			$columns 				= $this->get_columns();
			$hidden 				= array();
			$sortable 				= $this->get_sortable_columns();
			$this->_column_headers 	= array( $columns, $hidden, $sortable );
			$this->items 			= $statistics;
		}

		function column_default( $item, $column_name ) {
			/* setting default view for collumn items */
			switch( $column_name ) {
				case 'ip':
				case 'failed_attempts':	
				case 'block_quantity':
				case 'status':
					return $item[ $column_name ];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true );
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
			$lmtttmpts_message_list = array(
				'notice'						=> __( 'Notice:', 'limit-attempts' ),
				'empty_ip_list'					=> __( 'No address has been selected', 'limit-attempts' ),
				'clear_stats_complete_done'		=> __( 'Statistics has been cleared completely', 'limit-attempts' ),
				'stats_already_empty'			=> __( 'Statistics is already empty', 'limit-attempts' ),
				'clear_stats_complete_error'	=> __( 'Error while clearing statistics completely', 'limit-attempts' ),
				'clear_stats_for_ips_done'		=> __( 'Selected statistics entry (entries) has been deleted', 'limit-attempts' ),
				'clear_stats_for_ips_error'		=> __( 'Error while deleting statistics entry (entries)', 'limit-attempts' )
			);
			/* Clear Statistics */
			if ( isset( $_POST['lmtttmpts_clear_statistics_complete_confirm'] ) && check_admin_referer( "limit-attempts/limit-attempts.php" , 'lmtttmpts_nonce_name' ) ) {
				/* if clear completely */
				$result = lmtttmpts_clear_statistics_completely();
				if ( false === $result ) {
					/* if error */
					$action_message['error'] = $lmtttmpts_message_list['clear_stats_complete_error'];
				} elseif ( 0 === $result ) {
					/* if empty */
					$action_message['done'] = $lmtttmpts_message_list['notice'] . ' ' . $lmtttmpts_message_list['stats_already_empty'];
				} else {
					/* if success */
					$action_message['done'] = $lmtttmpts_message_list['clear_stats_complete_done'];
				}
			} elseif ( ( ( isset( $_POST['action'] ) && $_POST['action'] == 'clear_statistics_for_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'clear_statistics_for_ips' ) ) && check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				/* Clear some entries */
				if ( isset( $_POST['ip'] ) ) {
					/* if statistics entries exist */
					$ips = $_POST['ip'];
					$error = $done = 0;
					foreach ( $ips as $ip ) {
						if ( false === lmtttmpts_clear_statistics( $ip ) )
							$error++;
						else
							$done++;
					}
					if ( 0 < $error ) {
						$action_message['error'] = $lmtttmpts_message_list['clear_stats_for_ips_error'] . '. ' . __( 'Total', 'limit-attempts') . ': ' . $error . ' ' . _n( 'entry', 'entries', $error, 'limit-attempts' );
					}
					if ( 0 < $done ) {
						$action_message['done'] = $lmtttmpts_message_list['clear_stats_for_ips_done'] . '. ' . __( 'Total', 'limit-attempts') . ': ' . $done . ' ' . _n( 'entry', 'entries', $done, 'limit-attempts' );
					}
				} else {
					$action_message['done'] = $lmtttmpts_message_list['notice'] . ' ' . $lmtttmpts_message_list['empty_ip_list'];
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

if ( ! function_exists( 'lmtttmpts_display_statistics' ) ) {
	function lmtttmpts_display_statistics( $plugin_basename ) {
		if ( isset( $_POST['lmtttmpts_clear_statistics_complete'] ) && check_admin_referer( $plugin_basename, 'lmtttmpts_nonce_name' ) ) { ?>
			<div id="lmtttmpts_clear_statistics_confirm">
				<p><?php _e( 'Are you sure you want to delete all statistics entries?', 'limit-attempts' ) ?></p>
				<form method="post" action="admin.php?page=limit-attempts.php&amp;action=statistics" style="margin-bottom: 10px;">
					<button class="button" name="lmtttmpts_clear_statistics_complete_confirm"><?php _e( 'Yes, delete these entries', 'limit-attempts' ) ?></button>
					<button class="button" name="lmtttmpts_clear_statistics_complete_deny"><?php _e( 'No, go back to the Statistics page', 'limit-attempts' ) ?></button>
					<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
				</form>
			</div>
		<?php } else { ?>
			<div id="lmtttmpts_statistics">
				<?php $lmtttmpts_statistics_list = new Lmtttmpts_Statistics();
				$lmtttmpts_statistics_list->action_message();
				$lmtttmpts_statistics_list->prepare_items(); ?>
				<form method="get" action="admin.php">
					<?php $lmtttmpts_statistics_list->search_box( __( 'Search IP', 'limit-attempts' ), 'search_statistics_ip' ); ?>
					<input type="hidden" name="page" value="limit-attempts.php" />
					<input type="hidden" name="action" value="statistics" />
				</form>
				<form method="post" action="admin.php?page=limit-attempts.php&amp;action=statistics">
					<input type="hidden" name="lmtttmpts_clear_statistics_complete" />
					<input type="submit" class="button" value="<?php _e( 'Clear Statistics', 'limit-attempts' ) ?>" />
					<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
				</form>
				<form method="post" action="admin.php?page=limit-attempts.php&amp;action=statistics">
					<?php $lmtttmpts_statistics_list->display(); ?>
				</form>
			</div>
		<?php }
	}
}

?>
