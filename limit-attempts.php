<?php
/**
Plugin Name: Limit Attempts by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/limit-attempts/
Description: Protect WordPress website against brute force attacks. Limit rate of login attempts.
Author: BestWebSoft
Version: 1.3.1
Text Domain: limit-attempts
Domain Path: /languages
Author URI: https://bestwebsoft.com/
License: GPLv3 or later
 */

/**
  © Copyright 2021  BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! is_admin() ) {
	require_once dirname( __FILE__ ) . '/includes/front-end-functions.php';
}

if ( ! function_exists( 'lmtttmpts_add_admin_menu' ) ) {
	/**
	 * Function for adding menu and submenu
	 */
	function lmtttmpts_add_admin_menu() {
		global $wp_version, $submenu, $lmtttmpts_plugin_info;

		$hook = add_menu_page(
			__( 'Limit Attempts Settings', 'limit-attempts' ),
			'Limit Attempts',
			'manage_options',
			'limit-attempts.php',
			'lmtttmpts_settings_page',
			'none'
		);

		add_submenu_page(
			'limit-attempts.php',
			__( 'Limit Attempts Settings', 'limit-attempts' ),
			__( 'Settings', 'limit-attempts' ),
			'manage_options',
			'limit-attempts.php',
			'lmtttmpts_settings_page'
		);

		add_submenu_page(
			'limit-attempts.php',
			__( 'Limit Attempts Blocked', 'limit-attempts' ),
			__( 'Blocked', 'limit-attempts' ),
			'manage_options',
			'limit-attempts-blocked.php',
			'lmtttmpts_settings_page'
		);

		add_submenu_page(
			'limit-attempts.php',
			__( 'Limit Attempts Deny & Allow List', 'limit-attempts' ),
			__( 'Deny & Allow List', 'limit-attempts' ),
			'manage_options',
			'limit-attempts-deny-and-allowlist.php',
			'lmtttmpts_settings_page'
		);

		add_submenu_page(
			'limit-attempts.php',
			__( 'Limit Attempts Logs', 'limit-attempts' ),
			__( 'Logs', 'limit-attempts' ),
			'manage_options',
			'limit-attempts-log.php',
			'lmtttmpts_settings_page'
		);

		add_submenu_page(
			'limit-attempts.php',
			__( 'Limit Attempts Statistics', 'limit-attempts' ),
			__( 'Statistics', 'limit-attempts' ),
			'manage_options',
			'limit-attempts-statistics.php',
			'lmtttmpts_settings_page'
		);

		add_submenu_page(
			'limit-attempts-create-item.php',
			__( 'Add New', 'limit-attempts' ),
			__( 'Add New', 'limit-attempts' ),
			'manage_options',
			'lmtttmpts-create-new-item.php',
			'lmtttmpts_create_new_item'
		);

		add_submenu_page(
			'limit-attempts.php',
			'BWS Panel',
			'BWS Panel',
			'manage_options',
			'lmtttmpts-bws-panel',
			'bws_add_menu_render'
		);

		if ( isset( $submenu['limit-attempts.php'] ) ) {
			$submenu['limit-attempts.php'][] = array(
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'limit-attempts' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/limit-attempts/?k=fdac994c203b41e499a2818c409ff2bc&pn=140&v=' . $lmtttmpts_plugin_info['Version'] . '&wp_v=' . $wp_version,
			);
		}

		add_action( "load-$hook", 'lmtttmpts_screen_options' );
	}
}

if ( ! function_exists( 'lmtttmpts_plugins_loaded' ) ) {
	/**
	 * Internationalization
	 */
	function lmtttmpts_plugins_loaded() {
		load_plugin_textdomain( 'limit-attempts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'lmtttmpts_plugin_init' ) ) {
	/**
	 * Function initialisation plugin for init
	 */
	function lmtttmpts_plugin_init() {
		global $lmtttmpts_plugin_info, $lmtttmpts_page;
		$plugin_basename = plugin_basename( __FILE__ );

		require_once dirname( __FILE__ ) . '/bws_menu/bws_include.php';
		bws_include_init( $plugin_basename );

		if ( empty( $lmtttmpts_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$lmtttmpts_plugin_info = get_plugin_data( __FILE__ );
		}

		/* check WordPress version */
		bws_wp_min_version_check( $plugin_basename, $lmtttmpts_plugin_info, '4.5' );

		$lmtttmpts_page = array(
			'limit-attempts.php',
			'limit-attempts-blocked.php',
			'limit-attempts-deny-and-allowlist.php',
			'limit-attempts-log.php',
			'limit-attempts-statistics.php',
			'lmtttmpts-create-new-item.php',
		);

		/* Call register settings function */
		if ( ( isset( $_GET['page'] ) && in_array( $_GET['page'], $lmtttmpts_page ) ) || ! is_admin() ) {
			register_lmtttmpts_settings();
		}
	}
}

if ( ! function_exists( 'lmtttmpts_plugin_admin_init' ) ) {
	/**
	 * Function initialisation plugin for admin_init
	 */
	function lmtttmpts_plugin_admin_init() {
		global $bws_plugin_info, $lmtttmpts_plugin_info, $pagenow, $lmtttmpts_options;

		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id'      => '140',
				'version' => $lmtttmpts_plugin_info['Version'],
			);
		}

		if ( 'plugins.php' == $pagenow ) {
			/* Install the option defaults */
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				register_lmtttmpts_settings();
				bws_plugin_banner_go_pro( $lmtttmpts_options, $lmtttmpts_plugin_info, 'limit-attempts', 'limit-attempts', '33bc89079511cdfe28aeba317abfaf37', '140', 'limit-attempts' );
			}
		}

	}
}

if ( ! function_exists( 'lmtttmpts_admin_head' ) ) {
	/**
	 * Function to add stylesheets - icon for menu
	 */
	function lmtttmpts_admin_head() { ?>
		<style type="text/css">
			.menu-top.toplevel_page_limit-attempts .wp-menu-image {
				font-family: 'bwsicons' !important;
			}
			.menu-top.toplevel_page_limit-attempts .wp-menu-image:before {
				content: "\e91a";
				font-family: 'bwsicons' !important;
			}
		</style>
		<?php
	}
}

if ( ! function_exists( 'lmtttmpts_enqueue_scripts' ) ) {
	/**
	 * Function to add stylesheets
	 */
	function lmtttmpts_enqueue_scripts() {
		global $lmtttmpts_page, $lmtttmpts_plugin_info;

		if ( ! $lmtttmpts_plugin_info ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$lmtttmpts_plugin_info = get_plugin_data( __FILE__ );
		}

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $lmtttmpts_page ) ) {
			wp_enqueue_style( 'lmtttmpts_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $lmtttmpts_plugin_info['Version'] );
			/* script */
			$script_vars = array(
				'lmtttmpts_ajax_nonce' => wp_create_nonce( 'lmtttmpts_ajax_nonce_value' ),
			);
			wp_enqueue_script( 'lmtttmpts_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), $lmtttmpts_plugin_info['Version'], true );
			wp_localize_script( 'lmtttmpts_script', 'lmtttmptsScriptVars', $script_vars );

			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
		}
	}
}

if ( ! function_exists( 'lmtttmpts_get_default_messages' ) ) {
	/**
	 * Get $default_messages array with info on defaults messages
	 *
	 * @return array $default_messages with info on default messages
	 */
	function lmtttmpts_get_default_messages() {
		$default_messages = array(
			/* Error Messages */
			'failed_message'           => sprintf( __( '%s attempts left before block.', 'limit-attempts' ), '%ATTEMPTS%' ),
			'blocked_message'          => sprintf( __( 'Too many failed attempts. You have been blocked until %s.', 'limit-attempts' ), '%DATE%' ),
			'denylisted_message'       => __( "You've been added to deny list. Please contact website administrator.", 'limit-attempts' ),
			/* Email Notifications */
			'email_subject'            => sprintf( __( '%1$s has been blocked on %2$s', 'limit-attempts' ), '%IP%', '%SITE_NAME%' ),
			'email_subject_denylisted' => sprintf( __( '%1$s has been added to the deny list on %2$s', 'limit-attempts' ), '%IP%', '%SITE_NAME%' ),
			'email_blocked'            => sprintf( __( 'IP %1$s has been blocked automatically on %2$s due to the excess of login attempts on your website %3$s.', 'limit-attempts' ), '%IP%', '%WHEN%', '<a href="%SITE_URL%">%SITE_NAME%</a>' ) . '<br/><br/>' . sprintf( __( 'Using the plugin %s', 'limit-attempts' ), '<a href="%PLUGIN_LINK%">Limit Attempts by BestWebSoft</a>' ),
			'email_denylisted'         => sprintf( __( 'IP %1$s has been added automatically to the deny list on %2$s due to the excess of locks quantity on your website %3$s.', 'limit-attempts' ), '%IP%', '%WHEN%', '<a href="%SITE_URL%">%SITE_NAME%</a>' ) . '<br/><br/>' . sprintf( __( 'Using the plugin %s', 'limit-attempts' ), '<a href="%PLUGIN_LINK%">Limit Attempts by BestWebSoft</a>' ),
		);
		return $default_messages;
	}
}

if ( ! function_exists( 'lmtttmpts_plugin_activate' ) ) {
	/**
	 * Activation plugin function
	 *
	 * @param bool $networkwide Flag for network.
	 */
	function lmtttmpts_plugin_activate( $networkwide ) {
		global $wpdb;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			/* check if it is a network activation - if so, run the activation function for each blog id */
			if ( $networkwide ) {
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					lmtttmpts_create_table();
				}
				switch_to_blog( $old_blog );
				return;
			}
		}
		lmtttmpts_create_table();
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'lmtttmpts_plugin_uninstall' );
			restore_current_blog();
		} else {
			register_uninstall_hook( __FILE__, 'lmtttmpts_plugin_uninstall' );
		}
	}
}

if ( ! function_exists( 'lmtttmpts_new_blog' ) ) {
	/**
	 * Activation function for new blog in network
	 *
	 * @param number $blog_id Blog ID.
	 * @param number $user_id User ID.
	 * @param string $domain  Domain.
	 * @param string $path    Path.
	 * @param number $site_id Site ID.
	 * @param string $meta    Meta.
	 */
	function lmtttmpts_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		global $wpdb;
		if ( is_plugin_active_for_network( 'limit-attempts/limit-attempts.php' ) ) {
			$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			lmtttmpts_create_table();
			switch_to_blog( $old_blog );
		}
	}
}

if ( ! function_exists( 'lmtttmpts_create_table' ) ) {
	/**
	 * Initial tables create
	 */
	function lmtttmpts_create_table() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		/* Query for create table with current number of failed attempts and block quantity, block status and time when addres will be deblocked */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $prefix . "failed_attempts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip` CHAR(31) NOT NULL,
            `ip_int` BIGINT,
            `email` VARCHAR( 255 ),
            `failed_attempts` INT(3) NOT NULL DEFAULT '0',
            `block` BOOL DEFAULT FALSE,
            `block_quantity` INT(3) NOT NULL DEFAULT '0',
            `block_start` DATETIME,
            `block_till` DATETIME,
            `block_by` VARCHAR( 255 ),
            `last_failed_attempt` TIMESTAMP,
            PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/* Query for create table with all number of failed attempts and block quantity, block status and time when addres will be deblocked */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $prefix . "all_failed_attempts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip` CHAR(31) NOT NULL,
            `ip_int` BIGINT,
            `email` VARCHAR( 255 ),
            `failed_attempts` INT(4) NOT NULL DEFAULT '0',
            `block` BOOL DEFAULT FALSE,
            `block_quantity` INT(3) NOT NULL DEFAULT '0',
            `last_failed_attempt` TIMESTAMP,
            PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/* Query for create table with allowlisted addresses */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'allowlist` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip` CHAR(31) NOT NULL UNIQUE,
            `add_time` DATETIME,
            PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;';
		dbDelta( $sql );
		/* Query for create table with denylisted addresse */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'denylist` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip` CHAR(31) NOT NULL UNIQUE,
            `add_time` DATETIME,
            PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;';
		dbDelta( $sql );

		/* Query to create table with denylisted email addresses */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'denylist_email` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR( 255 ),
            `add_time` DATETIME,
            PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;';
		dbDelta( $sql );

		/* Query to create table with emails for CF */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'email_list` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_failed_attempts` INT,
            `id_failed_attempts_statistics` INT,
            `ip` CHAR(31) NOT NULL,
            `email` VARCHAR( 255 ),
            PRIMARY KEY (`id`)
            ) DEFAULT CHARSET=utf8;';
		dbDelta( $sql );
	}
}

if ( ! function_exists( 'register_lmtttmpts_settings' ) ) {
	/**
	 * Register settings function
	 */
	function register_lmtttmpts_settings() {
		global $lmtttmpts_options, $lmtttmpts_plugin_info, $wpdb;

		$prefix     = $wpdb->prefix . 'lmtttmpts_';
		$db_version = '1.7';

		/* Install the option defaults */
		if ( ! get_option( 'lmtttmpts_options' ) ) {
			$options_default = lmtttmpts_get_options_default();
			add_option( 'lmtttmpts_options', $options_default );
			/* Schedule event to clear statistics daily */
			$time = time() - fmod( time(), 86400 ) + 86400;
			wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_statistics_clear' );
		}
		/* Get options from the database */
		$lmtttmpts_options = get_option( 'lmtttmpts_options' );

		if ( ! isset( $lmtttmpts_options['plugin_db_version'] ) || $lmtttmpts_options['plugin_db_version'] != $db_version ) {

			/**
			 * @deprecated since 1.2.9
			 * @todo remove after 20.09.2021
			 */

			$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` ADD `block_start` DATETIME AFTER `block_quantity`;' );
			/* end deprecated */

			lmtttmpts_create_table();

			/* crop table 'all_failed_attempts' */
			$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts` LIKE "invalid_captcha_from_login_form";' );
			/* drop columns */
			if ( ! empty( $column_exists ) ) {
				$wpdb->query(
					'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts`
					DROP `invalid_captcha_from_login_form`,
					DROP `invalid_captcha_from_registration_form`,
					DROP `invalid_captcha_from_reset_password_form`,
					DROP `invalid_captcha_from_comments_form`,
					DROP `invalid_captcha_from_contact_form`,
					DROP `invalid_captcha_from_subscriber`,
					DROP `invalid_captcha_from_bp_registration_form`,
					DROP `invalid_captcha_from_bp_comments_form`,
					DROP `invalid_captcha_from_bp_create_group_form`,
					DROP `invalid_captcha_from_contact_form_7`;'
				);
			}

			$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` LIKE "block_by"' );
			if ( 0 == $column_exists ) {
				$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` ADD `block_by` TEXT AFTER `block_till`;' );
			}

			$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` LIKE "email"' );
			if ( 0 == $column_exists ) {
				$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` ADD `email` TEXT AFTER `ip_int`;' );
			}

			$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` LIKE "last_failed_attempt"' );
			if ( 0 == $column_exists ) {
				$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` ADD `last_failed_attempt` TIMESTAMP AFTER `block_by`;' );
			}

			$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts` LIKE "email"' );
			if ( 0 == $column_exists ) {
				$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts` ADD `email` TEXT AFTER `ip_int`;' );
			}

			$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts` LIKE "block"' );
			if ( 0 == $column_exists ) {
				$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts` ADD `block` BOOL DEFAULT FALSE AFTER `failed_attempts`;' );
			}

			/* update database to version 1.3 */
			$tables = array( 'denylist', 'allowlist', 'failed_attempts', 'all_failed_attempts' );
			foreach ( $tables as $table_name ) {
				$table = $prefix . $table_name;
				if ( 0 == $wpdb->query( 'SHOW COLUMNS FROM ' . $table . ' LIKE "id";' ) ) {
					if ( in_array( $table_name, array( 'allowlist', 'denylist' ) ) ) {
						$wpdb->query(
							'ALTER TABLE ' . $table . ' DROP PRIMARY KEY,
							ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
							ADD `add_time` DATETIME;'
						);
						$indexes = $wpdb->get_results( 'SHOW KEYS FROM `' . $table . '` WHERE Key_name Like "%ip%"' );
						if ( empty( $indexes ) ) {
							/* add necessary indexes */
							$wpdb->query( 'ALTER IGNORE TABLE `' . $table . '` ADD UNIQUE (`ip`);' );
						} else {
							/* remove excess indexes */
							$drop = array();
							foreach ( $indexes as $index ) {
								if ( preg_match( '|ip_|', $index->Key_name ) && ! in_array( ' DROP INDEX ' . $index->Key_name, $drop ) ) {
									$drop[] = ' DROP INDEX `' . $index->Key_name . '`';
								}
							}
							if ( ! empty( $drop ) ) {
								$wpdb->query( 'ALTER TABLE `' . $table . '`' . implode( ',', $drop ) );
							}
						}
					} else {
						$wpdb->query( 'ALTER TABLE ' . $table . ' DROP PRIMARY KEY, ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;' );
					}
				}
				/* update database to version 1.4 */
				if ( in_array( $table_name, array( 'denylist', 'allowlist' ) ) ) {
					if ( 0 != $wpdb->query( 'SHOW COLUMNS FROM ' . $table . ' LIKE "ip\_%";' ) ) {
						$wpdb->query(
							'ALTER TABLE ' . $table . '
							DROP `ip_from`,
							DROP `ip_to`,
							DROP `ip_from_int`,
							DROP `ip_to_int`;'
						);
					}
				}
			}
			/* update DB version */
			$lmtttmpts_options['plugin_db_version'] = $db_version;
			$update_option                          = true;
		}

		/* Update options when update plugin */
		if ( ! isset( $lmtttmpts_options['plugin_option_version'] ) || $lmtttmpts_options['plugin_option_version'] != $lmtttmpts_plugin_info['Version'] ) {

			/* delete default messages from wp_options - since v 1.0.6 */
			$lmtttmpts_messages_defaults = lmtttmpts_get_default_messages();
			foreach ( $lmtttmpts_messages_defaults as $key => $value ) {
				if ( isset( $lmtttmpts_options[ $key . '_default' ] ) ) {
					unset( $lmtttmpts_options[ $key . '_default' ] );
				}
			}
			/* rename hooks from 'log' to 'statistics' - since v 1.0.6 */
			if ( isset( $lmtttmpts_options['days_to_clear_log'] ) ) {
				$lmtttmpts_options['days_to_clear_statistics'] = $lmtttmpts_options['days_to_clear_log'];
				/* delete old 'log' cron hook */
				if ( wp_next_scheduled( 'lmtttmpts_daily_log_clear' ) ) {
					wp_clear_scheduled_hook( 'lmtttmpts_daily_log_clear' );
					if ( 0 != $lmtttmpts_options['days_to_clear_statistics'] ) {
						$time = time() - fmod( time(), 86400 ) + 86400;
						wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_statistics_clear' );
					}
				}
				unset( $lmtttmpts_options['days_to_clear_log'] );
			}

			/* check if old version of htaccess is used */
			if ( ! empty( $lmtttmpts_options['block_by_htaccess'] ) ) {
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$all_plugins = get_plugins();
				if (
					is_plugin_active( 'htaccess/htaccess.php' ) ||
					( array_key_exists( 'htaccess/htaccess.php', $all_plugins ) && ! array_key_exists( 'htaccess-pro/htaccess-pro.php', $all_plugins ) )
				) {
					global $htccss_plugin_info;
					if ( ! $htccss_plugin_info ) {
						$htccss_plugin_info = get_plugin_data( plugin_dir_path( dirname( __FILE__ ) ) . 'htaccess/htaccess.php' );
					}
					if ( $htccss_plugin_info['Version'] < '1.6.2' ) {
						do_action( 'lmtttmpts_htaccess_hook_for_delete_all' );
						$lmtttmpts_options['htaccess_notice'] = sprintf( __( 'Limit Attempts interaction with Htaccess was turned off since you are using an outdated Htaccess plugin version. If you want to keep using this interaction, please update Htaccess plugin at least to v%s.', 'limit-attempts' ), '1.6.2' );
					}
				}
			}
			/* show pro features */
			$lmtttmpts_options['hide_premium_options']  = array();
			$lmtttmpts_options['blocked_message']       = preg_replace( '|have been blocked till|', 'have been blocked for', $lmtttmpts_options['blocked_message'] );
			$options_default                            = lmtttmpts_get_options_default();
			$lmtttmpts_options                          = array_merge( $options_default, $lmtttmpts_options );
			$lmtttmpts_options['plugin_option_version'] = $lmtttmpts_plugin_info['Version'];
			$update_option                              = true;
		}

		if ( isset( $update_option ) ) {
			update_option( 'lmtttmpts_options', $lmtttmpts_options );
		}
	}
}

if ( ! function_exists( 'lmtttmpts_get_options_default' ) ) {
	/**
	 * Fetch plugin default options
	 *
	 * @return array
	 */
	function lmtttmpts_get_options_default() {
		global $lmtttmpts_plugin_info;

		/*email addres that was setting Settings -> General -> E-mail Address */
		$email_address = get_bloginfo( 'admin_email' );

		$options_default = array(
			'plugin_option_version'                => $lmtttmpts_plugin_info['Version'],
			'allowed_retries'                      => '5',
			'days_of_lock'                         => '0',
			'hours_of_lock'                        => '1',
			'minutes_of_lock'                      => '30',
			'days_to_reset'                        => '0',
			'hours_to_reset'                       => '2',
			'minutes_to_reset'                     => '0',
			'allowed_locks'                        => '3',
			'days_to_reset_block'                  => '1',
			'hours_to_reset_block'                 => '0',
			'minutes_to_reset_block'               => '0',
			'days_to_clear_statistics'             => '30',
			'options_for_block_message'            => 'hide',
			'options_for_email_message'            => 'hide',
			'notify_email'                         => false,
			'mailto'                               => 'admin',
			'email_address'                        => $email_address,
			'failed_message'                       => sprintf( __( '%s attempts left before block.', 'limit-attempts' ), '%ATTEMPTS%' ),
			'blocked_message'                      => sprintf( __( 'Too many failed attempts. You have been blocked until %s.', 'limit-attempts' ), '%DATE%' ),
			'denylisted_message'                   => __( "You've been added to deny list. Please contact website administrator.", 'limit-attempts' ),
			'email_subject'                        => sprintf( __( '%1$s has been blocked on %2$s', 'limit-attempts' ), '%IP%', '%SITE_NAME%' ),
			'email_subject_denylisted'             => sprintf( __( '%1$s has been added to the deny list on %2$s', 'limit-attempts' ), '%IP%', '%SITE_NAME%' ),
			'email_blocked'                        => sprintf( __( 'IP %1$s has been blocked automatically on %2$s due to the excess of login attempts on your website %3$s.', 'limit-attempts' ), '%IP%', '%WHEN%', '<a href="%SITE_URL%">%SITE_NAME%</a>' ) . '<br/><br/>' . sprintf( __( 'Using the plugin %s', 'limit-attempts' ), '<a href="%PLUGIN_LINK%">Limit Attempts by BestWebSoft</a>' ),
			'email_denylisted'                     => sprintf( __( 'IP %1$s has been added automatically to the deny list on %2$s due to the excess of locks quantity on your website %3$s.', 'limit-attempts' ), '%IP%', '%WHEN%', '<a href="%SITE_URL%">%SITE_NAME%</a>' ) . '<br/><br/>' . sprintf( __( 'Using the plugin %s', 'limit-attempts' ), '<a href="%PLUGIN_LINK%">Limit Attempts by BestWebSoft</a>' ),
			'htaccess_notice'                      => '',
			'first_install'                        => strtotime( 'now' ),
			/* since v1.1.3 */
			'hide_login_form'                      => 0,
			'block_by_htaccess'                    => 0,
			/* since v 1.1.3 */
			'suggest_feature_banner'               => 1,
			/* CF options */
			'contact_form_restrict_sending_emails' => 0,
			'number_of_letters'                    => 1,
			'letters_days'                         => 0,
			'letters_hours'                        => 0,
			'letters_minutes'                      => 5,
			'letters_seconds'                      => 0,
		);
		return $options_default;
	}
}

if ( ! function_exists( 'lmtttmpts_plugin_action_links' ) ) {
	/**
	 * Function to handle action links
	 *
	 * @param array  $links Links array.
	 * @param string $file  File name.
	 * @return array $links
	 */
	function lmtttmpts_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=limit-attempts.php">' . __( 'Settings', 'limit-attempts' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists( 'lmtttmpts_register_plugin_links' ) ) {
	/**
	 * Function to register plugin links
	 *
	 * @param array  $links Links array.
	 * @param string $file  File name.
	 * @return array $links
	 */
	function lmtttmpts_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() ) {
				$links[] = '<a href="admin.php?page=limit-attempts.php">' . __( 'Settings', 'limit-attempts' ) . '</a>';
			}
			$links[] = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538789" target="_blank">' . __( 'FAQ', 'limit-attempts' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'limit-attempts' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'lmtttmpts_create_new_item' ) ) {
	/**
	 * Function to create new item
	 */
	function lmtttmpts_create_new_item() {
		global $wpdb, $lmtttmpts_options;

		$lmtttmpts_table = '';
		$lmtttmpts_type_new_item = '';
		require_once dirname( __FILE__ ) . '/includes/pro-tab.php';
		if ( isset( $_REQUEST['type'] ) ) {
			$lmtttmpts_table         = 'denylist' == $_REQUEST['type'] || 'denylist-email' == $_REQUEST['type'] ? 'denylist' : 'allowlist';
			$lmtttmpts_type_new_item = 'denylist' == $_REQUEST['type'] || 'allowlist' == $_REQUEST['type'] ? 'ip' : 'email';
			$message                 = '';
			$error                   = '';

			if ( isset( $_POST['lmtttmpts_form_submit'] ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
				/* save data here */
				if ( 'allowlist' == $lmtttmpts_table ) {
					$add_ip = isset( $_POST['lmtttmpts_add_to_allowlist_my_ip'] ) && isset( $_POST['lmtttmpts_add_to_allowlist_my_ip_value'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['lmtttmpts_add_to_allowlist_my_ip_value'] ) ) ) : false;
					$add_ip = ! $add_ip && isset( $_POST['lmtttmpts_add_to_allowlist'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['lmtttmpts_add_to_allowlist'] ) ) ) : $add_ip;
					if ( empty( $add_ip ) ) {
						$error = __( 'ERROR:', 'limit-attempts' ) . '&nbsp;' . __( 'You must type IP address', 'limit-attempts' );
					} elseif ( filter_var( $add_ip, FILTER_VALIDATE_IP ) ) {
						if ( lmtttmpts_is_ip_in_table( $add_ip, 'allowlist' ) ) {
							$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address has already been added to allow list', 'limit-attempts' ) . ' - ' . $add_ip;
						} else {
							if ( lmtttmpts_is_ip_in_table( $add_ip, 'denylist' ) ) {
								$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address is in deny list too, please check this to avoid errors', 'limit-attempts' ) . ' - ' . $add_ip;
								$flag     = false;
							} else {
								$flag = true;
							}

							lmtttmpts_remove_from_blocked_list( $add_ip );
							if ( false !== lmtttmpts_add_ip_to_allowlist( $add_ip ) ) {
								if ( ! empty( $message ) ) {
									$message .= '<br />';
								}
								$message .= $add_ip . '&nbsp;' . __( 'has been added to allow list', 'limit-attempts' );
							} else {
								if ( ! empty( $error ) ) {
									$error .= '<br />';
								}
								$error .= $add_ip . '&nbsp;' . __( "can't be added to allow list.", 'limit-attempts' );
							}
						}
					} else {
						$error .= sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' ) . '<br />' . $add_ip . '&nbsp;' . __( "can't be added to allow list.", 'limit-attempts' );
					}
				} elseif ( 'denylist' == $lmtttmpts_table ) {
					/* IP to add to denylist */
					$add_to_blacklist_ip = isset( $_POST['lmtttmpts_add_to_denylist'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['lmtttmpts_add_to_denylist'] ) ) ) : '';
					if ( '' == $add_to_blacklist_ip ) {
						$error = __( 'ERROR:', 'limit-attempts' ) . '&nbsp;' . __( 'You must type IP address', 'limit-attempts' );
					} else {
						if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $add_to_blacklist_ip ) ) {
							if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'denylist' ) ) {
								$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address has already been added to  deny list', 'limit-attempts' ) . ' - ' . $add_to_blacklist_ip;
							} else {
								if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'allowlist' ) ) {
									$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address is in allowlist too, please check this to avoid errors', 'limit-attempts' ) . ' - ' . $add_to_blacklist_ip;
								}

								lmtttmpts_remove_from_blocked_list( $add_to_blacklist_ip );
								if ( false !== lmtttmpts_add_ip_to_denylist( $add_to_blacklist_ip ) ) {
									if ( ! empty( $message ) ) {
										$message .= '<br />';
									}
									$message .= $add_to_blacklist_ip . '&nbsp;' . __( 'has been added to deny list', 'limit-attempts' );
								} else {
									if ( ! empty( $error ) ) {
										$error .= '<br />';
									}
									$error .= $add_to_blacklist_ip . '&nbsp;' . __( "can't be added to deny list.", 'limit-attempts' );
								}
							}
						} else {
							/* wrong IP format */
							$error .= sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' ) . '<br />' . $add_to_blacklist_ip . '&nbsp;' . __( "can't be added to deny list.", 'limit-attempts' );
						}
					}
				}
			}

			$page_title = sprintf(
				__( 'Add New %1$s : %2$s' ),
				( 'ip' == $lmtttmpts_type_new_item ? 'Ip' : 'Email' ),
				( 'denylist' == $lmtttmpts_table ? __( 'Denylist', 'limit-attempts' ) : __( 'Allowlist', 'limit-attempts' ) )
			);
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php echo esc_html( $page_title ); ?></h1>
			<?php
			if ( ! empty( $error ) ) {
				?>
				<div class="error inline"><p><?php echo esc_html( $error ); ?></p></div>
				<?php
			}
			if ( ! empty( $message ) ) {
				?>
				<div class="updated inline"><p><?php echo esc_html( $message ); ?></p></div>
				<?php
			}

			if ( 'ip' == $lmtttmpts_type_new_item ) {
				?>
				<form id="lmtttmpts_edit_list_form"
					  action="admin.php?page=lmtttmpts-create-new-item.php"
					  method="post">
					<input type="text" maxlength="31" name="lmtttmpts_add_to_<?php echo esc_attr( $lmtttmpts_table ); ?>"/>
					<?php
					$my_ip = lmtttmpts_get_ip();
					if ( 'denylist' != $lmtttmpts_table ) {
						?>
						<br/>
						<label>
							<input type="checkbox" name="lmtttmpts_add_to_allowlist_my_ip" value="1"/>
							<?php esc_html_e( 'My IP', 'limit-attempts' ); ?>
							<input type="hidden" name="lmtttmpts_add_to_allowlist_my_ip_value" value="<?php echo esc_attr( $my_ip ); ?>"/>
						</label>
					<?php } ?>
					<div>
						<span class="bws_info" style="display: inline-block;margin: 10px 0;">
							<?php esc_html_e( 'Allowed formats:', 'limit-attempts' ); ?><code>192.168.0.1</code>
						</span>
					</div>
					
					<span id="lmtttmpts_img_loader" style="display: none;position: absolute;"><img src="<?php echo esc_url( plugins_url( 'images/ajax-loader.gif', dirname( __FILE__ ) ) ); ?>" alt=""/></span>
					 <input class="button-primary" type="submit" name="lmtttmpts_form_submit" value="<?php esc_html_e( 'Add New', 'limit-attempts' ); ?>" />
					<input type="hidden" name="lmtttmpts_table" value="<?php echo esc_html( $lmtttmpts_table ); ?>" />
					<input type="hidden" name="type" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) ); ?>" />
					<?php wp_nonce_field( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ); ?>
				</form> <br/>
				<?php
			}
			lmtttmpts_display_advertising( sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) );
			?>
			</div> 
			<?php
		}
	}
}

if ( ! function_exists( 'lmtttmpts_settings_page' ) ) {
	/**
	 * Function for display limit attempts settings page in the admin area
	 */
	function lmtttmpts_settings_page() {
		global $lmtttmpts_plugin_info;
		?>
		<?php
		if ( 'limit-attempts.php' == $_GET['page'] ) { /* Showing settings tab */
			if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
				require_once dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php';
			}
			require_once dirname( __FILE__ ) . '/includes/class-lmtttmpts-settings.php';
			$page = new Lmtttmpts_Settings_Tabs( plugin_basename( __FILE__ ) );
			if ( method_exists( $page, 'add_request_feature' ) ) {
				$page->add_request_feature();
			}
			?>
			<div class="wrap">
				<h1>Limit Attempts 
				<?php
				if ( is_network_admin() ) {
					echo esc_html__( 'Network', 'limit-attempts' ) . ' ';
				} esc_html_e( 'Settings', 'limit-attempts' );
				?>
				</h1>
				<noscript><div class="error below-h2"><p><strong><?php esc_html_e( 'Please enable JavaScript in Your browser.', 'limit-attempts' ); ?></strong></p></div></noscript>
				<?php if ( $page->is_network_options ) { ?>
					<div id="lmtttmpts_network_notice" class="updated inline bws_visible"><p><strong><?php esc_html_e( 'Notice:', 'limit-attempts' ); ?></strong> <?php esc_html_e( 'This option will replace all current settings on separate sites.', 'limit-attempts' ); ?></p></div>
					<?php
				}
				$page->display_content();
		} else {
			?>
			<div class="wrap">
			<?php
			require_once dirname( __FILE__ ) . '/includes/pro-tab.php';
			if ( 'limit-attempts-log.php' == $_GET['page'] ) {
				?>
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<div id="lmtttmpts_statistics" class="lmtttmpts_list">
					<?php lmtttmpts_display_advertising( 'log' ); ?>
				</div>
				<?php
			} elseif ( 'limit-attempts-deny-and-allowlist.php' == $_GET['page'] ) {
				require_once dirname( __FILE__ ) . '/includes/edit-list-form.php';
				lmtttmpts_display_list();
			} elseif ( 'limit-attempts-blocked.php' == $_GET['page'] ) {
				require_once dirname( __FILE__ ) . '/includes/edit-list-form.php';
				lmtttmpts_display_blocked();
			} else {
				preg_match( '/limit-attempts-(.*?).php/', esc_attr( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ), $page_name );
				?>
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<?php
				if ( file_exists( dirname( __FILE__ ) . '/includes/' . $page_name[1] . '.php' ) ) {
					require_once dirname( __FILE__ ) . '/includes/' . $page_name[1] . '.php';
					call_user_func_array( 'lmtttmpts_display_' . $page_name[1], array( plugin_basename( __FILE__ ) ) );
				}
			}
				bws_plugin_reviews_block( $lmtttmpts_plugin_info['Name'], 'limit-attempts' );
		}
		?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'lmtttmpts_show_notices' ) ) {
	/**
	 * Add notises on plugins page
	 */
	function lmtttmpts_show_notices() {
		global $lmtttmpts_options, $hook_suffix, $lmtttmpts_plugin_info;

		register_lmtttmpts_settings();

		/* if limit-login-attempts is also installed */
		if ( 'plugins.php' == $hook_suffix && is_plugin_active( 'limit-login-attempts/limit-login-attempts.php' ) ) {
			echo '<div class="error"><p><strong>' . esc_html__( 'Notice:', 'limit-attempts' ) . '</strong> ' . esc_html__( "Limit Login Attempts plugin is activated on your site, as well as Limit Attempts plugin. Please note that Limit Attempts ensures maximum security when no similar plugins are activated. Using other plugins that limit user's login attempts at the same time may lead to undesirable behaviour on your WP site.", 'limit-attempts' ) . '</p></div>';
		}

		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner_to_settings( $lmtttmpts_plugin_info, 'lmtttmpts_options', 'limit-attempts', 'admin.php?page=limit-attempts.php' );
		}

		/**
		 * Need to update Htaccess
		 * if option 'htaccess_notice' is not empty and we are on the 'right' page
		 */
		if ( ! empty( $lmtttmpts_options['htaccess_notice'] ) && ( 'plugins.php' == $hook_suffix || 'update-core.php' == $hook_suffix || ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], array( 'limit-attempts.php', 'htaccess.php' ) ) ) ) ) {
			/* Save data for settings page */
			if ( isset( $_REQUEST['lmtttmpts_htaccess_notice_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_htaccess_notice_nonce_name' ) ) {
				$lmtttmpts_options['htaccess_notice'] = '';
				update_option( 'lmtttmpts_options', $lmtttmpts_options );
			} else {
				/* get action_slug */
				$action_slug = ( 'plugins.php' == $hook_suffix || 'update-core.php' == $hook_suffix ) ? $hook_suffix : 'admin.php?page=' . sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
				?>
				<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
					<div class="bws_banner_on_plugin_page">
						<form method="post" action="<?php echo esc_attr( $action_slug ); ?>">
							<div class="text" style="max-width: 100%;">
								<p>
									<strong><?php esc_html_e( 'ATTENTION!', 'limit-attempts' ); ?> </strong>
									<?php echo esc_html( $lmtttmpts_options['htaccess_notice'] ); ?>&nbsp;&nbsp;&nbsp;
									<input type="hidden" name="lmtttmpts_htaccess_notice_submit" value="submit" />
									<input type="submit" class="button-primary" value="<?php esc_html_e( 'Read and Understood', 'limit-attempts' ); ?>" />
								</p>
								<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_htaccess_notice_nonce_name' ); ?>
							</div>
						</form>
					</div>
				</div>
				<?php
			}
		}

		if ( isset( $_GET['page'] ) && 'limit-attempts.php' == $_GET['page'] ) {
			bws_plugin_suggest_feature_banner( $lmtttmpts_plugin_info, 'lmtttmpts_options', 'limit-attempts' );
		}
	}
}

if ( ! function_exists( 'lmtttmpts_get_ip' ) ) {
	/**
	 * Function to get correct IP address
	 */
	function lmtttmpts_get_ip() {
		$ip = '';
		if ( isset( $_SERVER ) ) {
			$server_vars = array( 'REMOTE_ADDR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR' );
			foreach ( $server_vars as $var ) {
				if ( isset( $_SERVER[ $var ] ) && ! empty( $_SERVER[ $var ] ) ) {
					if ( filter_var( sanitize_text_field( wp_unslash( $_SERVER[ $var ] ) ), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_IPV4 ) ) {
						$ip = sanitize_text_field( wp_unslash( $_SERVER[ $var ] ) );
						break;
					} else { /* if proxy */
						$ip_array = explode( ',', $_SERVER[ $var ] );
						if ( is_array( $ip_array ) && ! empty( $ip_array ) && filter_var( $ip_array[0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_IPV4 ) ) {
							$ip = $ip_array[0];
							break;
						}
					}
				}
			}
		}
		return $ip;
	}
}

if ( ! function_exists( 'lmtttmpts_is_ip_blocked' ) ) {
	/**
	 * Function for checking is current ip is blocked
	 *
	 * @param string $ip IP for block.
	 * @return string $ip_info
	 */
	function lmtttmpts_is_ip_blocked( $ip ) {
		global $wpdb;
		$ip_int = sprintf( '%u', ip2long( $ip ) );

		$ip_info = $wpdb->get_row(
			$wpdb->prepare(
				'
				SELECT
					`failed_attempts`,
					`block_quantity`,
					`block_till`
				FROM
					`' . $wpdb->prefix . 'lmtttmpts_failed_attempts`
				WHERE
					`ip_int` = %d AND 
					`block` = 1 AND 
					`block_by` = "ip"
					',
				$ip_int
			),
			ARRAY_A
		);

		return $ip_info;
	}
}

if ( ! function_exists( 'lmtttmpts_is_blocked' ) ) {
	/**
	 * Function for checking is current ip or email is blocked
	 *
	 * @param string $ip     IP for check.
	 * @param string $emails Emails for check.
	 * @return string $info
	 */
	function lmtttmpts_is_blocked( $ip = '', $emails = array() ) {
		global $wpdb;

		$ip_int      = sprintf( '%u', ip2long( $ip ) );
		$emails_list = '"' . implode( "','", $emails ) . '"';

		$info = $wpdb->get_var(
			$wpdb->prepare(
				'
				SELECT
					COUNT(*)
				FROM
					' . $wpdb->prefix . 'lmtttmpts_failed_attempts
				WHERE
						block = 1 AND
					( ip_int = %d OR email IN (' . $emails_list . ') )
				',
				$ip_int
			)
		);

		return $info;
	}
}

if ( ! function_exists( 'lmtttmpts_screen_options' ) ) {
	/**
	 * Function for options for screen
	 */
	function lmtttmpts_screen_options() {
		$screen = get_current_screen();
		$args   = array(
			'id'      => 'lmtttmpts',
			'section' => '200538789',
		);
		bws_help_tab( $screen, $args );

		if ( isset( $_GET['action'] ) && 'go_pro' != $_GET['action'] ) {
			$option = 'per_page';
			$args   = array(
				'label'   => __( 'Addresses per page', 'limit-attempts' ),
				'default' => 20,
				'option'  => 'addresses_per_page',
			);
			add_screen_option( $option, $args );
		}
	}
}

if ( ! function_exists( 'lmtttmpts_table_set_option' ) ) {
	/**
	 * Function for set option
	 *
	 * @param string $status Status.
	 * @param string $option Option.
	 * @param string $value  Value.
	 */
	function lmtttmpts_table_set_option( $status, $option, $value ) {
		return $value;
	}
}


if ( ! function_exists( 'lmtttmpts_remove_from_blocked_list' ) ) {
	/**
	 * Function for remove item from blocked list
	 *
	 * @param string $ip IP for remove.
	 */
	function lmtttmpts_remove_from_blocked_list( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$wpdb->update(
			"{$wpdb->prefix}lmtttmpts_failed_attempts",
			array( 'block' => 0 ),
			array( 'ip' => $ip )
		);
		if ( 1 == $lmtttmpts_options['block_by_htaccess'] ) {
			do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $ip );
		}
		wp_clear_scheduled_hook( 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
	}
}

if ( ! function_exists( 'lmtttmpts_is_ip_in_table' ) ) {
	/**
	 * Function for checking is current ip in current table
	 *
	 * @param string $ip    IP for check.
	 * @param string $table Table name.
	 */
	function lmtttmpts_is_ip_in_table( $ip, $table ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* integer value for our IP */
		$ip_int = sprintf( '%u', ip2long( $ip ) );
		if ( 'allowlist' == $table || 'denylist' == $table ) {
			/**
			 * For allowlist and denylist tables needs different method
			 * if $ip variable is ip mask
			 */
			$is_in = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT `ip` FROM `' . $prefix . $table . '` WHERE `ip` = %s;',
					$ip
				)
			);
		} elseif ( 'failed_attempts' == $table ) {
			$is_in = $wpdb->get_var(
				$wpdb->prepare(
					'
					SELECT 
						ip 
					FROM 
						' . $prefix . 'failed_attempts
					WHERE 
						ip_int = %d AND
						( block_by <> "email" OR
				    block_by IS NULL )
				',
					$ip_int
				)
			);
		} else { /* for other tables */
			$is_in = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT `ip` FROM ' . $prefix . $table . ' WHERE `ip_int` = %d;',
					$ip_int
				)
			);
		}
		return $is_in;
	}
}

if ( ! function_exists( 'lmtttmpts_is_email_in_table' ) ) {
	/**
	 * Check is current email or email domain in current table
	 *
	 * @param string $email Email for check.
	 * @param string $table Table name.
	 */
	function lmtttmpts_is_email_in_table( $email, $table ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';

		$parts  = explode( '@', $email );
		$domain = array_pop( $parts );
		$domain = '@' . $domain;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				' 
				SELECT 
						COUNT(*)
				FROM 
						`' . $prefix . $table . '`
				WHERE 
						email IN(%s, %s)
        ',
				$email,
				$domain
			)
		);

		return $result;
	}
}

if ( ! function_exists( 'lmtttmpts_add_ip_to_denylist' ) ) {
	/**
	 * Function for adding ip to denylist
	 *
	 * @param string $ip IP for add.
	 * @return bool true/false with the result of DB add operation
	 */
	function lmtttmpts_add_ip_to_denylist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* if IP isn't empty and isn't in denylist already */
		if ( '' != $ip && ! lmtttmpts_is_ip_in_table( $ip, 'denylist' ) ) {
			/* if insert single ip address */
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) {
				/* add a new row to db */
				$result = $wpdb->insert(
					$prefix . 'denylist',
					array(
						'ip'       => $ip,
						'add_time' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
					),
					'%s' /* all '%s' because max value in '%d' is 2147483647 */
				);
				if ( 1 == $lmtttmpts_options['block_by_htaccess'] ) {
					do_action( 'lmtttmpts_htaccess_hook_for_block', $ip );
				}
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'lmtttmpts_add_ip_to_allowlist' ) ) {
	/**
	 * Function for adding ip to allowlist
	 *
	 * @param string $ip IP to add.
	 * @return bool true/false with the result of DB add operation
	 */
	function lmtttmpts_add_ip_to_allowlist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* if IP isn't empty and isn't in allowlist already */
		if ( '' != $ip && ! lmtttmpts_is_ip_in_table( $ip, 'allowlist' ) ) {
			/* if insert single ip address */
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) {
				/* add a new row to db */
				$result = $wpdb->insert(
					$prefix . 'allowlist',
					array(
						'ip'       => $ip,
						'add_time' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
					),
					'%s' /* all '%s' because max value in '%d' is 2147483647 */
				);
				if ( 1 == $lmtttmpts_options['block_by_htaccess'] ) {
					do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $ip );
				}
				return $result;
			} else {
				return false;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'lmtttmpts_clear_statistics_completely' ) ) {
	/**
	 * Function to clear all statistics
	 */
	function lmtttmpts_clear_statistics_completely() {
		global $wpdb;

		$result = $wpdb->query( "DELETE FROM `{$wpdb->prefix}lmtttmpts_all_failed_attempts`" );

		return ( $wpdb->last_error ) ? false : $result;
	}
}

if ( ! function_exists( 'lmtttmpts_clear_statistics' ) ) {
	/**
	 * Function to clear single statistics entry
	 *
	 * @param number $id ID for delete item.
	 */
	function lmtttmpts_clear_statistics( $id ) {
		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . 'lmtttmpts_all_failed_attempts',
			array( 'id' => $id ),
			array( '%s' )
		);
		return $wpdb->last_error ? false : $result;
	}
}

if ( ! function_exists( 'lmtttmpts_clear_statistics_daily' ) ) {
	/**
	 * Function to cron clear statistics daily
	 */
	function lmtttmpts_clear_statistics_daily() {
		global $wpdb, $lmtttmpts_options;
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		$time = date( 'Y-m-d H:i:s', time() - 86400 * $lmtttmpts_options['days_to_clear_statistics'] );
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM `' . $wpdb->prefix . 'lmtttmpts_all_failed_attempts` WHERE `last_failed_attempt` <= %s',
				$time
			)
		);
	}
}

if ( ! function_exists( 'lmtttmpts_reset_failed_attempts' ) ) {
	/**
	 * Function to reset failed attempts
	 *
	 * @param string $ip IP for reset.
	 */
	function lmtttmpts_reset_failed_attempts( $ip ) {
		global $wpdb;

		if ( ! empty( $ip ) ) {
			$array      = array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) );
			$wpdb->update(
				$wpdb->prefix . 'lmtttmpts_failed_attempts',
				array( 'failed_attempts' => 0 ),
				$array,
				array( '%d' ),
				array( '%s' )
			);
		}
	}
}

if ( ! function_exists( 'lmtttmpts_reset_block' ) ) {
	/**
	 * Function to reset block
	 */
	function lmtttmpts_reset_block() {
		global $wpdb, $lmtttmpts_options;
		$reset_ip_db          = array();
		$reset_ip_in_htaccess = array();

		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}

		$unlocking_timestamp = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + 60 ) );
		$current_timestamp   = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) ) );
		$blockeds            = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `ip_int`, `ip` FROM `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` WHERE `block_till` <= %s and `block` = 1',
				$unlocking_timestamp
			),
			ARRAY_A
		);

		if ( ! empty( $blockeds ) ) {
			foreach ( $blockeds as $blocked ) {
				$reset_ip_in_htaccess[] = $blocked['ip'];
				$reset_ip_db[]          = $blocked['ip_int'];
			}
		}

		if ( ! empty( $reset_ip_db ) ) {
			$reset_ip_db_placeholders = implode( ', ', array_fill( 0, count( (array) $reset_ip_db ), '%d' ) );
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` 
					SET
						`block` = 0, 
						`block_till` = NULL,
						`block_by` = NULL 
					WHERE `ip_int` IN (' . $reset_ip_db_placeholders . ')',
					$reset_ip_db
				)
			);
		}

		$next_timestamp = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT `block_till` 
				FROM `' . $wpdb->prefix . 'lmtttmpts_failed_attempts` 
				WHERE `block_till` > %s
				ORDER BY `block_till` 
				LIMIT 1',
				$current_timestamp
			),
			ARRAY_A
		);
		if ( ! empty( $next_timestamp ) ) {
			$next_timestamp_unix_time = strtotime( $next_timestamp['block_till'] );
			wp_schedule_single_event( $next_timestamp_unix_time, 'lmtttmpts_event_for_reset_block' );
		}

		/* hook for deblocking by Htaccess */
		if ( 1 == $lmtttmpts_options['block_by_htaccess'] && ! empty( $reset_ip_in_htaccess ) ) {
			do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $reset_ip_in_htaccess );
		}
	}
}
if ( ! function_exists( 'lmtttmpts_reset_block_quantity' ) ) {
	/**
	 * Function to reset number of blocks
	 *
	 * @param string $ip       IP for reset.
	 * @param string $email    Email for reset.
	 * @param string $priority Priority for reset.
	 */
	function lmtttmpts_reset_block_quantity( $ip, $email = '', $priority = '' ) {
		global $wpdb;

		if ( 'ip' == $priority ) {
			$array = array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) );
		} else {
			$array = array( 'email' => $email );
		}

		$wpdb->update(
			$wpdb->prefix . 'lmtttmpts_failed_attempts',
			array( 'block_quantity' => 0 ),
			$array,
			array( '%d' ),
			array( '%s' )
		);
	}
}

if ( ! function_exists( 'lmtttmpts_set_html_content_type' ) ) {
	/**
	 * Filter to transfer message in html format
	 */
	function lmtttmpts_set_html_content_type() {
		return 'text/html';
	}
}

if ( ! function_exists( 'lmtttmpts_login_form_captcha_checking' ) ) {
	/**
	 * Checking for right captcha in login form
	 */
	function lmtttmpts_login_form_captcha_checking() {
		global $lmtttmpts_options;
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		if ( is_multisite() ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins, get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		if ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! isset( $lmtttmpts_options['login_form_captcha_check'] ) && ! cptch_lmtttmpts_interaction() ) {
			/* return false if only Captcha is instaled, is active, is exist in login form, user set consider captcha and captcha is invalid */
			return false;
		} elseif ( function_exists( 'cptchpls_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $active_plugins ) ) && ! isset( $lmtttmpts_options['login_form_captcha_check'] ) && ! cptchpls_lmtttmpts_interaction() ) {
			/* return false if only Captcha Plus is instaled, is active, is exist in login form, user set not consider captcha and captcha is invalid */
			return false;
		} elseif ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! isset( $lmtttmpts_options['login_form_captcha_check'] ) && ! cptchpr_lmtttmpts_interaction() ) {
			/* return false if only Captcha is instaled, is active, is exist in login form, user set consider captcha and captcha is invalid */
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'lmtttmpts_check_block_options' ) ) {
	/**
	 * Check plugin`s "block/denylist" options
	 *
	 * @param array $option plugin`s options.
	 * @return mixed the minimum period of time necessary for the user`s IP to be added to the denylist or false.
	 */
	function lmtttmpts_check_block_options( $option ) {
		/*
		 * Over what period of time the user can be blocked
		 */
		$time_to_block =
			$option['days_of_lock'] * 86400 +
			$option['hours_of_lock'] * 3600 +
			$option['minutes_of_lock'] * 60 +
			$option['allowed_retries'] * 60;

		/*
		 * The minimum period of time necessary for the user`s IP to be added to the denylist
		 */
		$time_for_blacklist = intval(
			(
					$option['days_to_reset_block'] * 86400 +
					$option['hours_to_reset_block'] * 3600 +
					$option['minutes_to_reset_block'] * 60
				) /
				$option['allowed_locks']
		);

		if ( $time_to_block > $time_for_blacklist ) {
			$days   = intval( ( $time_to_block ) / 86400 );
			$string = ( 0 < $days ? '&nbsp;' . $days . '&nbsp;' . _n( 'day', 'days', $days, 'limit-attempts' ) : '' );
			$sum    = $days * 86400;

			$hours   = intval( ( $time_to_block - $sum ) / 3600 );
			$string .= ( 0 < $hours ? '&nbsp;' . $hours . '&nbsp;' . _n( 'hour', 'hours', $hours, 'limit-attempts' ) : '' );
			$sum    += $hours * 3600;

			$minutes = intval( ( $time_to_block - $sum ) / 60 ) + 1;
			$string .= ( 0 < $minutes ? '&nbsp;' . $minutes . '&nbsp;' . _n( 'minute', 'minutes', $minutes, 'limit-attempts' ) : '' );
			return $string;
		}

		return false;
	}
}

if ( ! function_exists( 'lmtttmpts_restore_default_message' ) ) {
	/**
	 * Function (ajax) to restore default message
	 */
	function lmtttmpts_restore_default_message() {
		check_ajax_referer( 'lmtttmpts_ajax_nonce_value', 'lmtttmpts_nonce' );
		if ( isset( $_POST['message_option_name'] ) &&
			( 'error' == $_POST['message_option_name'] || 'email' == $_POST['message_option_name'] ) ) {
			/* get the list of default messages */
			if ( ! function_exists( 'lmtttmpts_get_default_messages' ) ) {
				require_once dirname( __FILE__ ) . '/includes/back-end-functions.php';
			}
			$default_messages = lmtttmpts_get_default_messages();

			if ( 'email' == $_POST['message_option_name'] ) {
				unset( $default_messages['failed_message'], $default_messages['blocked_message'], $default_messages['denylisted_message'] );
				$output_message = __( 'Email notifications have been restored to default.', 'limit-attempts' );
			} else {
				unset( $default_messages['email_subject'], $default_messages['email_subject_denylisted'], $default_messages['email_blocked'], $default_messages['email_denylisted'] );
				$output_message = __( 'Messages have been restored to default.', 'limit-attempts' );
			}
			/* set notice message, check what was changed - subject or body of the message */
			$output_message = '<div class="updated fade inline lmtttmpts_message lmtttmpts-restore-default-message"><p><strong>' . __( 'Notice', 'limit-attempts' ) . ':</strong> ' . $output_message . '</p><p><strong>' . __( 'Changes are not saved.', 'limit-attempts' ) . '</strong></p></div>';
			/* send default text of subject/body into ajax array */
			$restored_data = array(
				'restored_messages'    => $default_messages,
				'admin_notice_message' => $output_message,
			);
			echo json_encode( $restored_data );
		}
		die();
	}
}

if ( ! function_exists( 'lmtttmpts_plugin_uninstall' ) ) {
	/**
	 * Delete plugin for network
	 */
	function lmtttmpts_plugin_uninstall() {
		global $wpdb;
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins       = get_plugins();
		$pro_version_exist = array_key_exists( 'limit-attempts-pro/limit-attempts-pro.php', $all_plugins );
		if ( is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM {$wpdb->blogs};" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				lmtttmpts_delete_options( $pro_version_exist );
			}
			switch_to_blog( $old_blog );
		} else {
			lmtttmpts_delete_options( $pro_version_exist );
		}

		require_once dirname( __FILE__ ) . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

if ( ! function_exists( 'lmtttmpts_delete_blog' ) ) {
	/**
	 * Delete plugin blog
	 *
	 * @param number $blog_id ID for delete.
	 */
	function lmtttmpts_delete_blog( $blog_id ) {
		global $wpdb;
		if ( is_plugin_active_for_network( 'limit-attempts/limit-attempts.php' ) ) {
			$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			lmtttmpts_delete_options( false );
			switch_to_blog( $old_blog );
		}
	}
}

if ( ! function_exists( 'lmtttmpts_delete_options' ) ) {
	/**
	 * Function for deleting options when uninstal current plugin
	 *
	 * @param bool $pro_version_exist Flag for pro version.
	 */
	function lmtttmpts_delete_options( $pro_version_exist = false ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* drop tables */
		if ( ! $pro_version_exist ) {
			/**
			 * Delete options
			 * drop all tables
			 */
			$sql = "DROP TABLE IF EXISTS `{$prefix}all_failed_attempts`, `{$prefix}failed_attempts`, `{$prefix}email_list`, `{$prefix}denylist`, `{$prefix}denylist_email`, `{$prefix}allowlist`;";
			/* Remove IPs from .htaccess */
			do_action( 'lmtttmpts_htaccess_hook_for_delete_all' );
			delete_option( 'lmtttmpts_options' );
		} else {
			/* Drop FREE tables only */
			$sql = "DROP TABLE IF EXISTS `{$prefix}all_failed_attempts`;";
		}
		$wpdb->query( $sql );
		/* Clear hook to delete old statistics entries */
		wp_clear_scheduled_hook( 'lmtttmpts_daily_statistics_clear' );
	}
}

if ( ! function_exists( 'lmtttmpt_deactivate' ) ) {
	/**
	 * Function for deactivate plugin
	 */
	function lmtttmpt_deactivate() {
		$cptch_options = get_option( 'cptch_options' );
		if ( ! empty( $cptch_options ) ) {
			$cptch_options['use_limit_attempts_allowlist'] = 0;
			update_option( 'cptch_options', $cptch_options );
		}
	}
}

/* installation */
register_activation_hook( __FILE__, 'lmtttmpts_plugin_activate' );
add_action( 'wpmu_new_blog', 'lmtttmpts_new_blog', 10, 6 );
add_action( 'delete_blog', 'lmtttmpts_delete_blog', 10 );
add_action( 'plugins_loaded', 'lmtttmpts_plugins_loaded' );
/* register */
add_action( 'admin_menu', 'lmtttmpts_add_admin_menu' );
add_action( 'init', 'lmtttmpts_plugin_init' );
add_action( 'admin_init', 'lmtttmpts_plugin_admin_init' );
add_action( 'admin_head', 'lmtttmpts_admin_head' );
add_action( 'admin_enqueue_scripts', 'lmtttmpts_enqueue_scripts' );
add_filter( 'set-screen-option', 'lmtttmpts_table_set_option', 10, 3 );
add_filter( 'plugin_action_links', 'lmtttmpts_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'lmtttmpts_register_plugin_links', 10, 2 );

/* reset blocks */
add_action( 'lmtttmpts_event_for_reset_failed_attempts', 'lmtttmpts_reset_failed_attempts' );
add_action( 'lmtttmpts_event_for_reset_block', 'lmtttmpts_reset_block' );
add_action( 'lmtttmpts_event_for_reset_block_quantity', 'lmtttmpts_reset_block_quantity' );
add_action( 'lmtttmpts_daily_statistics_clear', 'lmtttmpts_clear_statistics_daily' );
add_action( 'admin_notices', 'lmtttmpts_show_notices' );
/* ajax function */
add_action( 'wp_ajax_lmtttmpts_restore_default_message', 'lmtttmpts_restore_default_message' );
register_deactivation_hook( __FILE__, 'lmtttmpt_deactivate' );
