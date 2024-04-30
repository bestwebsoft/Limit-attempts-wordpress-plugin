<?php
/**
 * Display list of emails, which are in denylist
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

if ( ! class_exists( 'Lmtttmpts_Denylist_Email' ) ) {
	class Lmtttmpts_Denylist_Email extends WP_List_Table {
		public $is_geoip_exists;

		/**
		 * Construct
		 */
		public function __construct() {
			global $wpdb;
			parent::__construct();
			$this->is_geoip_exists = $wpdb->query( 'SHOW TABLES LIKE "' . $wpdb->base_prefix . 'bws_list_countries"' );
		}

		/**
		 * Adding collumns to table and their view
		 */
		public function get_columns() {
			$columns = array(
				'cb'           => '<input type="checkbox" />',
				'email'        => __( 'Email', 'limit-attempts' ),
				'add_time'     => __( 'Date Added', 'limit-attempts' ),
			);
			return $columns;
		}

		/**
		 * Seting sortable collumns
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'email'        => array( 'email', false ),
				'add_time'     => array( 'add_time', false ),
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
				'delete'    => '<a href="' . wp_nonce_url( sprintf( '?page=limit-attempts-deny-and-allowlist.php&list=denylist&tab-action=denylist_email&lmtttmpts_remove_from_denylist_email=%s', $item['email'] ), 'lmtttmpts_remove_from_denylist_email_' . $item['email'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Delete', 'limit-attempts' ) . '</a>',
			);
			return sprintf( '%1$s %2$s', $item['email'], $this->row_actions( $actions ) );
		}

		/**
		 * Adding bulk action
		 */
		public function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'remove_from_denylist_email_ips'    => __( 'Delete', 'limit-attempts' ),
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
			/* query for total number of denylisted Emails */
			$query = 'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'lmtttmpts_denylist_email`';
			/* if search */
			if ( isset( $_REQUEST['s'] ) ) {
				$part_email = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '';
				$query .= $wpdb->prepare(
					' WHERE `email` LIKE %s',
					'%' . $wpdb->esc_like( $part_email ) . '%'
				);
			}
			/* get the total number of Emails */
			$totalitems = $wpdb->get_var( $query );
			/* get the value of number of Emails on one page */
			$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );

			/* set pagination arguments */
			$this->set_pagination_args(
				array(
					'total_items'   => $totalitems,
					'per_page'      => $perpage,
				)
			);
			/* general query */

			$query =
				'SELECT
					`' . $wpdb->prefix . 'lmtttmpts_denylist_email`.`add_time`,
					`' . $wpdb->prefix . 'lmtttmpts_denylist_email`.`email`
				FROM `' . $wpdb->prefix . 'lmtttmpts_denylist_email`';
			if ( isset( $_REQUEST['s'] ) ) {
				$part_email = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '';
				$query .= $wpdb->prepare(
					' WHERE `email` LIKE %s',
					'%' . $wpdb->esc_like( $part_email ) . '%'
				);
			}

			/* the 'orderby' and 'order' values */
			$orderby = isset( $_REQUEST['orderby'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ), array_keys( $this->get_sortable_columns() ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'add_time';
			$order   = ( isset( $_REQUEST['order'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ), array( 'asc', 'desc' ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';
			/* calculate offset for pagination */
			$paged   = ( isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) && 0 < absint( $_REQUEST['paged'] ) ) ? absint( $_REQUEST['paged'] ) : 1;
			$offset  = ( $paged - 1 ) * $perpage;
			/* add calculated values (order and pagination) to our query */
			$query .= ' ORDER BY `' . $orderby . '` ' . $order . ' LIMIT ' . $offset . ',' . $perpage;
			/* get data from our denylist table - list of denylisted IPs */
			$blacklisted_items = $wpdb->get_results( $query, ARRAY_A );
			/* get site date and time format from DB option */
			$columns                = $this->get_columns();
			$hidden                 = array();
			$sortable               = $this->get_sortable_columns();
			$this->_column_headers  = array( $columns, $hidden, $sortable );
			$this->items            = $blacklisted_items;
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
				case 'add_time':
				case 'email':
					return $item[ $column_name ];
				case 'ip_range':
					return $item['ip_from'] . ' - ' . $item['ip_to'];
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
			$done = '';
			$message_list = array(
				'notice'                        => __( 'Notice:', 'limit-attempts' ),
				'empty_ip_list'                 => __( 'No address has been selected', 'limit-attempts' ),
				'denylisted_delete_done'        => __( 'has been deleted from deny list', 'limit-attempts' ),
				'denylisted_delete_done_many'   => __( 'have been deleted from deny list', 'limit-attempts' ),
				'denylisted_delete_error'       => __( 'Error while deleting from deny list', 'limit-attempts' ),
			);
			if ( isset( $_REQUEST['lmtttmpts_remove_from_denylist_email'] ) ) {
				check_admin_referer( 'lmtttmpts_remove_from_denylist_email_' . sanitize_text_field( wp_unslash( $_REQUEST['lmtttmpts_remove_from_denylist_email'] ) ), 'lmtttmpts_nonce_name' );
				$email = $_REQUEST['lmtttmpts_remove_from_denylist_email'];
			} else {
				if (
					( isset( $_POST['action'] ) && 'remove_from_denylist_email_ips' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) ||
					( isset( $_POST['action2'] ) && 'remove_from_denylist_email_ips' === sanitize_text_field( wp_unslash( $_POST['action2'] ) ) )
				) {
					check_admin_referer( 'bulk-' . $this->_args['plural'] );
					$email = isset( $_POST['email'] ) ? $_POST['email'] : '';
				}
			}
			if ( isset( $email ) ) {
				if ( empty( $email ) ) {
					$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
				} else {
					$eml = is_array( $email ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $email ) ) : sanitize_text_field( wp_unslash( $email ) );

					$eml_placeholders = implode( ', ', array_fill( 0, count( (array) $eml ), '%s' ) );

					$wpdb->query(
						$wpdb->prepare(
							'DELETE FROM `' . $wpdb->prefix . 'lmtttmpts_denylist_email` WHERE `email` IN (' . $eml_placeholders . ');',
							(array) $eml
						)
					);
					if ( $wpdb->last_error ) {
						$action_message['error'] = $eml . '&nbsp;-&nbsp;' . $message_list['denylisted_delete_error'];
					} else {
						$done_ips = (array) $email;
						$action_message['done'] = implode( ', ', $done_ips ) . '&nbsp;' . ( 1 == count( $done_ips ) ? $message_list['denylisted_delete_done'] : $message_list['denylisted_delete_done_many'] );
					}
				}
			}

			if ( isset( $_REQUEST['s'] ) ) {
				$search_request = esc_html( trim( $_REQUEST['s'] ) );
				if ( ! empty( $search_request ) ) {
					$action_message['done'] .= ( empty( $action_message['done'] ) ? '' : '<br/>' ) . __( 'Search results for', 'limit-attempts' ) . '&nbsp;' . $search_request;
				}
			}

			if ( ! empty( $action_message['error'] ) ) { ?>
				<div class="error inline lmtttmpts_message"><p><strong><?php echo esc_html( $action_message['error'] ); ?></strong></div>
				<?php
			}
			if ( ! empty( $action_message['done'] ) ) {
				?>
				<div class="updated inline lmtttmpts_message"><p><?php echo esc_html( $action_message['done'] ); ?></p></div>
				<?php
			}
		}
	}
}
