<?php 
/**
 * Display list of IP, which are in blacklist 
 * @package Limit Attempts
 * @since 1.1.3
 */
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( ! class_exists( 'Lmtttmpts_Blacklist' ) ) {
	class Lmtttmpts_Blacklist extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'ip'			=> __( 'Ip address', 'limit-attempts' ),
				'add_time'		=> __( 'Date added', 'limit-attempts' )
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
				'remove_from_blacklist'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&action=%s&lmtttmpts_remove_from_blacklist=%s', $_REQUEST['page'], $_REQUEST['action'], $item['ip'] ), 'lmtttmpts_remove_from_blacklist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Remove from blacklist', 'limit-attempts' ) . '</a>'
			);
			return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'remove_from_blacklist_ips'	=> __( 'Remove from blacklist', 'limit-attempts' )
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
			/* query for total number of blacklisted IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "blacklist`";
			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_REQUEST['s'] ) ) ) );
				if ( 0 != $search_ip  || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$count_query .= " WHERE ( `ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip . ") OR `ip` LIKE '%" . $part_ip . "%'";
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
			$orderby = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ? $_REQUEST['orderby']  : 'add_time';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'desc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
			if ( 0 > $totalpages && $paged > $totalpages )
				$paged = $totalpages;
			$offset  = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `add_time` FROM `" . $prefix . "blacklist`";
			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_REQUEST['s'] ) ) ) );
				if ( 0 != $search_ip ||  preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$query .= " WHERE ( `ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip . ") OR `ip` LIKE '%" . $part_ip . "%'";
				}
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
			$error_ips = $done_ips = array();
			$prefix = "{$wpdb->prefix}lmtttmpts_";
			$message_list = array(
				'notice'						=> __( 'Notice:', 'limit-attempts' ),
				'error'							=> __( 'ERROR:', 'limit-attempts' ),
				'empty_ip_list'					=> __( 'No address has been selected', 'limit-attempts' ),
				'empty_ip_textarea'				=> __( 'You must type IP address', 'limit-attempts' ),
				'wrong_ip_format_error'			=> __( 'Wrong format or it does not lie in diapason 0.0.0.0 - 255.255.255.255.', 'limit-attempts' ),
				'blacklist_add_done'			=> __( 'has been added to blacklist', 'limit-attempts' ),
				'blacklist_add_error'			=> __( 'can&rsquo;t be added to blacklist.', 'limit-attempts' ),
				'ip_already_in_blacklist'		=> __( 'This IP address has already been added to blacklist', 'limit-attempts' ),
				'ip_also_in_whitelist'			=> __( 'This IP address is in whitelist too, please check this to avoid errors', 'limit-attempts' ),
				'blacklisted_delete_done'		=> __( 'has been deleted from blacklist', 'limit-attempts' ),
				'blacklisted_delete_done_many'	=> __( 'have been deleted from blacklist', 'limit-attempts' ),
				'blacklisted_delete_error'		=> __( 'Error while deleting from blacklist', 'limit-attempts' ),
			);
			if ( isset( $_POST['lmtttmpts_add_to_blacklist'] ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
				/* IP to add to blacklist */
				$add_to_blacklist_ip = esc_html( trim( $_POST['lmtttmpts_add_to_blacklist'] ) );
				if ( '' == $add_to_blacklist_ip ) {
					$action_message['error'] = $message_list['error'] . '&nbsp;' . $message_list['empty_ip_textarea'];
				} else {
					if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $add_to_blacklist_ip ) ) {
						if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'blacklist' ) ) {
							$action_message['done'] .= $message_list['notice'] . '&nbsp;' . $message_list['ip_already_in_blacklist'] . ' - ' . $add_to_blacklist_ip;
						} else {
							if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'whitelist' ) )
								$action_message['done'] .= $message_list['notice'] . '&nbsp;' . $message_list['ip_also_in_whitelist'] . ' - ' . $add_to_blacklist_ip;
							if ( false !== lmtttmpts_add_ip_to_blacklist( $add_to_blacklist_ip ) ) {
								lmtttmpts_remove_from_blocked_list( $add_to_blacklist_ip );
								if ( ! empty( $action_message['done'] ) )
									$action_message['done'] .= '<br />';
								$action_message['done'] .= $add_to_blacklist_ip . '&nbsp;' . $message_list['blacklist_add_done'];
								if ( 1 == $lmtttmpts_options["block_by_htaccess"] )
									do_action( 'lmtttmpts_htaccess_hook_for_block', $add_to_blacklist_ip );
							} else {
								if ( ! empty( $action_message['error'] ) )
									$action_message['error'] .= '<br />';
								$action_message['error'] .= $add_to_blacklist_ip . '&nbsp;' . $message_list['blacklist_add_error'];
							}
						}
					} else {
						/* wrong IP format */
						$action_message['error'] .= $message_list['wrong_ip_format_error'] . '<br />' . $add_to_blacklist_ip . '&nbsp;' . $message_list['blacklist_add_error'];
					}
				}
			} else {
				if ( isset( $_REQUEST['lmtttmpts_remove_from_blacklist'] ) ) {
					check_admin_referer( 'lmtttmpts_remove_from_blacklist_' . $_REQUEST['lmtttmpts_remove_from_blacklist'], 'lmtttmpts_nonce_name' );
					$ip_list = $_REQUEST['lmtttmpts_remove_from_blacklist'];
				} else {
					if( 
						( isset( $_POST['action'] )  && $_POST['action']  == 'remove_from_blacklist_ips' ) || 
						( isset( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_blacklist_ips' )
					) {
						check_admin_referer( 'bulk-' . $this->_args['plural'] );
						$ip_list = isset( $_POST['ip'] ) ? $_POST['ip'] : '';
					}
				}
				if ( isset( $ip_list ) ) {
					if ( empty( $ip_list ) ) {
						$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
					} else {
						$ips = is_array( $ip_list ) ? implode( "','", $ip_list ) : $ip_list;
						$wpdb->query( "DELETE FROM `{$prefix}blacklist` WHERE `ip` IN ('{$ips}');" );
						if ( $wpdb->last_error ) {
							$action_message['error'] = $ips . '&nbsp;-&nbsp;' . $message_list['blacklisted_delete_error'];
						} else {
							$done_ips = (array)$ip_list;
							$action_message['done'] = implode( ', ', $done_ips ) . '&nbsp;' . ( 1 == count( $done_ips ) ? $message_list['blacklisted_delete_done'] : $message_list['blacklisted_delete_done_many'] );
							if ( 1 == $lmtttmpts_options["block_by_htaccess"] )
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


if ( ! function_exists( 'lmtttmpts_display_blacklist' ) ) {
	function lmtttmpts_display_blacklist( $plugin_basename ) { ?>
		<div id="lmtttmpts_blacklist">
			<form method="post" action="admin.php?page=limit-attempts.php&amp;action=blacklist" class="lmtttmpts_edit_list_form">
				<table>
					<tr valign="top">
						<td><input type="text" maxlength="31" name="lmtttmpts_add_to_blacklist" /></td>
						<td><input type="submit" class="button-secondary" value="<?php _e( 'Add IP to blacklist', 'limit-attempts' ) ?>" /></td>
					</tr>
				</table>
				<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
			</form>
			<div>
				<span class="bws_info" style="display: inline-block;margin: 10px 0;"><?php _e( "Allowed formats:", 'limit-attempts' ); ?><code>192.168.0.1</code></span>
			</div>
			<?php lmtttmpts_display_advertising( 'blacklist' );
			$lmtttmpts_blacklist_table = new Lmtttmpts_Blacklist();
			$lmtttmpts_blacklist_table->action_message();
			$lmtttmpts_blacklist_table->prepare_items(); ?>
			<form method="get" action="admin.php">
				<?php $lmtttmpts_blacklist_table->search_box( __( 'Search IP', 'limit-attempts' ), 'search_blacklisted_ip' ); ?>
				<input type="hidden" name="page" value="limit-attempts.php" />
				<input type="hidden" name="action" value="blacklist" />
			</form>
			<form method="post" action="admin.php?page=limit-attempts.php&amp;action=blacklist">
				<?php $lmtttmpts_blacklist_table->display(); ?>
			</form>
		</div>
	<?php }
} ?>