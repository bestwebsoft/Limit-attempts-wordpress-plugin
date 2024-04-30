<?php
/**
 * Display statistics
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

if ( ! class_exists( 'Lmtttmpts_Statistics' ) ) {
	class Lmtttmpts_Statistics extends WP_List_Table {
		/**
		 * Adding collumns to table and their view
		 */
		public function get_columns() {
			$columns = array(
				'cb'                => '<input type="checkbox" />',
				'ip'                => __( 'Ip Address', 'limit-attempts' ),
				'email'             => __( 'Email', 'limit-attempts' ),
				'failed_attempts'   => __( 'Failed Attempts', 'limit-attempts' ),
				'block_quantity'    => __( 'Blocks', 'limit-attempts' ),
				'status'            => __( 'Status', 'limit-attempts' ),
			);
			return $columns;
		}

		/**
		 * Seting sortable collumns
		 */
		public function get_bulk_actions() {
			$actions = array(
				'clear_statistics_for_ips'  => __( 'Delete statistics entry', 'limit-attempts' ),
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
				'<input type="checkbox" name="id[]" value="%s" />',
				$item['id']
			);
		}

		/**
		 * Adding bulk action
		 */
		public function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'ip'                => array( 'ip', true ),
				'failed_attempts'   => array( 'failed_attempts', false ),
				'block_quantity'    => array( 'block_quantity', false ),
			);
			return $sortable_columns;
		}

		/**
		 * Adding single row
		 *
		 * @param array $item Row item.
		 */
		public function single_row( $item ) {
			/* add class to non 'not_blocked' rows (deny-, allowlist or blocked) */
			$row_class = '';
			if ( isset( $item['row_class'] ) ) {
				/* if IP is deny-, allowlisted or blocked */
				$row_class = ' class="' . $item['row_class'] . '"';
			}

			echo '<tr' . esc_html( $row_class ) . '>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		/**
		 * Preparing table items
		 */
		public function prepare_items() {
			/* preparing table items */
			global $wpdb;

			$where = '';

			$part_ip = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '';

			if ( isset( $_REQUEST['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( ' ', '', sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) ) );
				if ( 0 != $search_ip || preg_match( '/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i', $part_ip ) ) {
					$where = $wpdb->prepare(
						' WHERE ip_int = %d OR ip LIKE %s ',
						$wpdb->esc_like( $search_ip ),
						'%' . $wpdb->esc_like( $part_ip ) . '%'
					);
				}
			}

			/* query for total number of IPs */
			$count_query = '
				SELECT 
					COUNT(*) 
				FROM 
					' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts
				' . $where . '
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

			/* the 'orderby' and 'order' values - If no sort, default to IP */
			$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ), array_keys( $this->get_sortable_columns() ) ) && 'ip' !== sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'ip_int';
			$order = ( isset( $_REQUEST['order'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ), array( 'asc', 'desc' ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';

			/* calculate offset for pagination */
			$paged = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < absint( $_REQUEST['paged'] ) ) ? absint( $_REQUEST['paged'] ) : 1;
			/* set pagination arguments */
			$offset = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = '
				SELECT
					id, 
					ip,
					email,
					block, 
					failed_attempts, 
					block_quantity 
				FROM 
					' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts
					' . $where . '
			';

			/* add calculated values (order and pagination) to our query */
			$query .= ' ORDER BY `' . $orderby . '` ' . $order . ' LIMIT ' . $offset . ',' . $perpage;
			/* get data from 'all_failed_attempts' table */
			$statistics = $wpdb->get_results( $query, ARRAY_A );
			if ( $statistics ) {
				/* loop - we calculate and add 'status' column and class data */
				foreach ( $statistics as $key => $statistic ) {

					$get_email_arr = $wpdb->get_col(
						$wpdb->prepare(
							'
							SELECT 
									email 
							FROM 
								' . $wpdb->prefix . 'lmtttmpts_email_list 
							WHERE 
									id_failed_attempts_statistics = %s
							',
							$wpdb->esc_like( $statistic['id'] )
						)
					);

					$statistics[ $key ]['email'] = ( $get_email_arr ) ? implode( '<br />', $get_email_arr ) : 'N/A';

					if ( lmtttmpts_is_ip_in_table( $statistic['ip'], 'denylist' ) ) {
						$statistics[ $key ]['status'] = '<a href="?page=limit-attempts-statistics.php&action=denylist&s=' . $statistic['ip'] . '">' . __( 'denylisted', 'limit-attempts' ) . '</a>';
						$statistics[ $key ]['row_class'] = 'lmtttmpts_denylist';
					} elseif ( lmtttmpts_is_ip_in_table( $statistic['ip'], 'allowlist' ) ) {
						$statistics[ $key ]['status'] = '<a href="?page=limit-attempts-statistics.php&action=allowlist&s=' . $statistic['ip'] . '">' . __( 'allowlisted', 'limit-attempts' ) . '</a>';
						$statistics[ $key ]['row_class'] = 'lmtttmpts_allowlist';
					} elseif ( lmtttmpts_is_blocked( $statistic['ip'], $get_email_arr ) ) {
						$statistics[ $key ]['status'] = '<a href="?page=limit-attempts-statistics.php&action=blocked&s=' . $statistic['ip'] . '">' . __( 'blocked', 'limit-attempts' ) . '</a>';
						$statistics[ $key ]['row_class'] = 'lmtttmpts_blocked';
					} else {
						$statistics[ $key ]['status'] = __( 'not blocked', 'limit-attempts' );
					}
				}
			}

			$columns                = $this->get_columns();
			$hidden                 = array();
			$sortable               = $this->get_sortable_columns();
			$this->_column_headers  = array( $columns, $hidden, $sortable );
			$this->items            = $statistics;
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
				case 'email':
				case 'failed_attempts':
				case 'block_quantity':
				case 'status':
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
			global $wpdb;
			$action_message = array(
				'error'             => false,
				'done'              => false,
				'error_country'     => false,
				'wrong_ip_format'   => '',
			);
			$error = '';
			$done  = '';
			$lmtttmpts_message_list = array(
				'notice'                        => __( 'Notice:', 'limit-attempts' ),
				'empty_ip_list'                 => __( 'No address has been selected', 'limit-attempts' ),
				'clear_stats_complete_done'     => __( 'Statistics has been cleared completely', 'limit-attempts' ),
				'stats_already_empty'           => __( 'Statistics is already empty', 'limit-attempts' ),
				'clear_stats_complete_error'    => __( 'Error while clearing statistics completely', 'limit-attempts' ),
				'clear_stats_for_ips_done'      => __( 'Selected statistics entry (entries) has been deleted', 'limit-attempts' ),
				'clear_stats_for_ips_error'     => __( 'Error while deleting statistics entry (entries)', 'limit-attempts' ),
			);
			/* Clear Statistics */
			if ( isset( $_POST['lmtttmpts_clear_statistics_complete_confirm'] ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
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
			} elseif ( ( ( isset( $_POST['action'] ) && 'clear_statistics_for_ips' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) || ( isset( $_POST['action2'] ) && 'clear_statistics_for_ips' === sanitize_text_field( wp_unslash( $_POST['action2'] ) ) ) ) && check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				/* Clear some entries */
				if ( isset( $_POST['id'] ) ) {
					/* if statistics entries exist */
					$ids   = array_map( 'absint', $_POST['id'] );
					$error = 0;
					$done  = 0;
					foreach ( $ids as $id ) {
						if ( false === lmtttmpts_clear_statistics( $id ) ) {
							$error++;
						} else {
							$done++;
						}
					}
					if ( 0 < $error ) {
						$action_message['error'] = $lmtttmpts_message_list['clear_stats_for_ips_error'] . '. ' . __( 'Total', 'limit-attempts' ) . ': ' . $error . ' ' . _n( 'entry', 'entries', $error, 'limit-attempts' );
					}
					if ( 0 < $done ) {
						$action_message['done'] = $lmtttmpts_message_list['clear_stats_for_ips_done'] . '. ' . __( 'Total', 'limit-attempts' ) . ': ' . $done . ' ' . _n( 'entry', 'entries', $done, 'limit-attempts' );
					}
				} else {
					$action_message['done'] = $lmtttmpts_message_list['notice'] . ' ' . $lmtttmpts_message_list['empty_ip_list'];
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

if ( ! function_exists( 'lmtttmpts_display_statistics' ) ) {
	/**
	 * Action message
	 *
	 * @param string $plugin_basename Plugin basemane.
	 */
	function lmtttmpts_display_statistics( $plugin_basename ) {
		global $lmtttmpts_options, $lmtttmpts_plugin_info, $wp_version;

		if ( isset( $_POST['lmtttmpts_clear_statistics_complete'] ) && check_admin_referer( $plugin_basename, 'lmtttmpts_nonce_name' ) ) {
			?>
			<div id="lmtttmpts_clear_statistics_confirm">
				<p><?php esc_html_e( 'Are you sure you want to delete all statistics entries?', 'limit-attempts' ); ?></p>
				<form method="post" action="" style="margin-bottom: 10px;">
					<button class="button button-primary" name="lmtttmpts_clear_statistics_complete_confirm"><?php esc_html_e( 'Yes, delete these entries', 'limit-attempts' ); ?></button>
					<button class="button button-secondary" name="lmtttmpts_clear_statistics_complete_deny"><?php esc_html_e( 'No, go back to the Statistics page', 'limit-attempts' ); ?></button>
					<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
				</form>
			</div>
			<?php
		} else {
			lmtttmpts_display_advertising( 'summaries' );
			?>
			<div id="lmtttmpts_statistics" class="lmtttmpts_list">
				<?php
				$lmtttmpts_statistics_list = new Lmtttmpts_Statistics();
				$lmtttmpts_statistics_list->action_message();
				$lmtttmpts_statistics_list->prepare_items();
				?>
				<form method="get" action="admin.php">
					<?php $lmtttmpts_statistics_list->search_box( __( 'Search IP', 'limit-attempts' ), 'search_statistics_ip' ); ?>
					<input type="hidden" name="page" value="limit-attempts-statistics.php" />
				</form>
				<form method="post" action="">
					<input type="hidden" name="lmtttmpts_clear_statistics_complete" />
					<input type="submit" class="button" value="<?php esc_html_e( 'Clear Statistics', 'limit-attempts' ); ?>" />
					<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
				</form>
				<form method="post" action="">
					<?php $lmtttmpts_statistics_list->display(); ?>
				</form>
			</div>
			<?php
		}
	}
}
