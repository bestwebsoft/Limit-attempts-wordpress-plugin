<?php
/*
Plugin Name: Limit Attempts by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/
Description: The plugin Limit Attempts allows you to limit rate of login attempts by the ip, and create whitelist and blacklist.
Author: BestWebSoft
Version: 1.0.9
Author URI: http://bestwebsoft.com/
License: GPLv3 or later
*/

/*  © Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

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

/**
 * Function for adding menu and submenu 
 */
if ( ! function_exists( 'add_lmtttmpts_admin_menu' ) ) { 
	function add_lmtttmpts_admin_menu() {
		bws_add_general_menu( plugin_basename( __FILE__ ) );
		$hook = add_submenu_page( 'bws_plugins', __( 'Limit Attempts Settings', 'lmtttmpts' ), 'Limit Attempts', 'manage_options', "limit-attempts.php", 'lmtttmpts_settings_page' );
		add_action( "load-$hook", 'lmtttmpts_screen_options' );
	}
}

/**
 * Function initialisation plugin for init
 */
if ( ! function_exists( 'lmtttmpts_plugin_init' ) ) { 
	function lmtttmpts_plugin_init() {
		global $lmtttmpts_plugin_info;
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'lmtttmpts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		require_once( dirname( __FILE__ ) . '/bws_menu/bws_functions.php' );

		if ( empty( $lmtttmpts_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$lmtttmpts_plugin_info = get_plugin_data( __FILE__ );
		}

		/* check WordPress version */
		bws_wp_version_check( plugin_basename( __FILE__ ), $lmtttmpts_plugin_info, "3.6" );
	}
}

/**
 * Function initialisation plugin for admin_init
 */
if ( ! function_exists( 'lmtttmpts_plugin_admin_init' ) ) {
	function lmtttmpts_plugin_admin_init() {
		global $bws_plugin_info, $lmtttmpts_plugin_info;

		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '140', 'version' => $lmtttmpts_plugin_info["Version"] );
		
		/* Call register settings function */
		if ( ( isset( $_GET['page'] ) && "limit-attempts.php" == $_GET['page'] ) || ! is_admin() )
			register_lmtttmpts_settings(); 
	}
}

/**
 * Function to add stylesheets
 */
if ( ! function_exists( 'lmtttmpts_admin_head' ) ) {
	function lmtttmpts_admin_head() {
		if ( isset( $_REQUEST['page'] ) && ( 'limit-attempts.php' == $_REQUEST['page'] ) ) {
			wp_enqueue_style( 'lmtttmpts_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			/* script */
			$script_vars = array(
				'lmtttmpts_ajax_nonce' => wp_create_nonce( 'lmtttmpts_ajax_nonce_value' ),
			);
			wp_enqueue_script( 'lmtttmpts_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'lmtttmpts_script', 'lmtttmptsScriptVars', $script_vars );
		}
	}
}

/**
 * Get $default_messages array with info on defaults messages
 *
 * @return array $default_messages with info on default messages
 */
if ( ! function_exists( 'lmtttmpts_get_default_messages' ) ) {
	function lmtttmpts_get_default_messages() {
		/* Default set of messages */
		$lmtttmpts_messages_defaults = array(
			'failed_message_default'			=> 'Retries to lock: %ATTEMPTS%',
			'blocked_message_default'			=> 'Too many retries. You have been blocked till %DATE%', 
			'blacklisted_message_default'		=> 'You have been added to blacklist. Please contact with administrator to resolve this problem', 
			'email_subject_default'				=> '%IP% was blocked in %SITE_NAME%',
			'email_subject_blacklisted_default'	=> '%IP% was added to the blacklist in %SITE_NAME%',
			'email_blocked_default'				=> '%WHEN% IP %IP% automatically blocked due to the excess of login attempts on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
			'email_blacklisted_default'			=> '%WHEN% IP %IP% automatically added to the blacklist due to the excess of locks quantity on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
		);
		return $lmtttmpts_messages_defaults;
	}
}

/**
 * Activation plugin function
 */
if ( ! function_exists( 'lmtttmpts_plugin_activate' ) ) {
	function lmtttmpts_plugin_activate( $networkwide ) {
		global $wpdb;
		/* Activation function for network, check if it is a network activation - if so, run the activation function for each blog id */
		if ( function_exists( 'is_multisite' ) && is_multisite() && $networkwide ) {
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
		lmtttmpts_create_table();
	}
}


/**
 * Activation function for new blog in network
 */
if ( ! function_exists( 'lmtttmpts_new_blog' ) ) {
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

/**
 * Initial tables create
 */
if ( ! function_exists( 'lmtttmpts_create_table' ) ) {
	function lmtttmpts_create_table() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		/* Query for create table with current number of failed attempts and block quantity, block status and time when addres will be deblocked */
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "failed_attempts` (
			`ip` CHAR(31) NOT NULL,
			`ip_int` BIGINT,
			`failed_attempts` INT(3) NOT NULL DEFAULT '0',
			`block` BOOL DEFAULT FALSE,
			`block_quantity` INT(3) NOT NULL DEFAULT '0',
			`block_till` DATETIME,
			PRIMARY KEY  (`ip`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/* Query for create table with all number of failed attempts and block quantity, block status and time when addres will be deblocked */
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "all_failed_attempts` (
			`ip` CHAR(31) NOT NULL,
			`ip_int` BIGINT,
			`failed_attempts` INT(4) NOT NULL DEFAULT '0',
			`block_quantity` INT(3) NOT NULL DEFAULT '0',
			`last_failed_attempt` TIMESTAMP,
			PRIMARY KEY  (`ip`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/* Query for create table with whitelisted addresses */
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "whitelist` (
			`ip` CHAR(31) NOT NULL,
			`ip_from` CHAR(15) NOT NULL,
			`ip_to` CHAR(15) NOT NULL,
			`ip_from_int` BIGINT,
			`ip_to_int` BIGINT,
			PRIMARY KEY  (`ip`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/* Query for create table with blacklisted addresse */
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "blacklist` (
			`ip` CHAR(31) NOT NULL,
			`ip_from` CHAR(15) NOT NULL,
			`ip_to` CHAR(15) NOT NULL,
			`ip_from_int` BIGINT,
			`ip_to_int` BIGINT,
			PRIMARY KEY  (`ip`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
	}
}

/**
 * Register settings function 
 */
if ( ! function_exists( 'register_lmtttmpts_settings' ) ) {
	function register_lmtttmpts_settings() {
		global $lmtttmpts_options, $lmtttmpts_plugin_info;
		/* email addres that was setting Settings -> General -> E-mail Address */
		$lmtttmpts_email_address = get_bloginfo( 'admin_email' );
		$lmtttmpts_db_version = "1.1";
		/*Default options for plugin*/
		$lmtttmpts_option_defaults = array(
			'plugin_db_version'				=> $lmtttmpts_db_version,
			'allowed_retries'				=> '5',
			'days_of_lock'					=> '0',
			'hours_of_lock'					=> '1',
			'minutes_of_lock'				=> '30',
			'days_to_reset'					=> '0',
			'hours_to_reset'				=> '2',
			'minutes_to_reset'				=> '0',
			'allowed_locks'					=> '3',
			'days_to_reset_block'			=> '1',
			'hours_to_reset_block'			=> '0',
			'minutes_to_reset_block'		=> '0',
			'days_to_clear_statistics'		=> '30',
			'plugin_option_version'			=> $lmtttmpts_plugin_info["Version"],
			'options_for_block_message'		=> 'hide',
			'options_for_email_message'		=> 'hide',
			'notify_email'					=> false,
			'mailto'						=> 'admin',
			'email_address'					=> $lmtttmpts_email_address,
			'failed_message'				=> 'Retries to lock: %ATTEMPTS%',
			'blocked_message'				=> 'Too many retries. You have been blocked till %DATE%',
			'blacklisted_message'			=> 'You have been added to blacklist. Please contact with administrator to resolve this problem', 
			'email_subject'					=> '%IP% was blocked in %SITE_NAME%',
			'email_subject_blacklisted'		=> '%IP% was added to the blacklist in %SITE_NAME%',
			'email_blocked'					=> '%WHEN% IP %IP% automatically blocked due to the excess of login attempts on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
			'email_blacklisted'				=> '%WHEN% IP %IP% automatically added to the blacklist due to the excess of locks quantity on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
			'htaccess_notice'				=> '',
		);
		/* Install the option defaults */
		if ( ! get_option( 'lmtttmpts_options' ) ) {
			add_option( 'lmtttmpts_options', $lmtttmpts_option_defaults );
			/* Schedule event to clear statistics daily */
			$time = time() - fmod( time(), 86400 ) + 86400;
			wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_statistics_clear' );
		}
		/* Get options from the database */
		$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		/* Update options when update plugin */
		if ( ! isset( $lmtttmpts_options['plugin_option_version'] ) || $lmtttmpts_options['plugin_option_version'] != $lmtttmpts_plugin_info["Version"] ) {
			/* delete default messages from wp_options - since v 1.0.6 */
			$lmtttmpts_messages_defaults = lmtttmpts_get_default_messages();
			foreach ( $lmtttmpts_messages_defaults as $key => $value ) {
				if ( isset( $lmtttmpts_options[ $key ] ) )
					unset( $lmtttmpts_options[ $key ] );
			}
			/* rename hooks from 'log' to 'statistics' - since v 1.0.6 */
			if ( isset( $lmtttmpts_options[ 'days_to_clear_log' ] ) ) {
				$lmtttmpts_options[ 'days_to_clear_statistics' ] = $lmtttmpts_options[ 'days_to_clear_log' ];
				/* delete old 'log' cron hook */
				if ( wp_next_scheduled( 'lmtttmpts_daily_log_clear' ) ) {
					wp_clear_scheduled_hook( 'lmtttmpts_daily_log_clear' );
					if ( 0 != $lmtttmpts_options[ 'days_to_clear_statistics' ] ) {
						$time = time() - fmod( time(), 86400 ) + 86400;
						wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_statistics_clear' );	
					}
				}
				unset( $lmtttmpts_options[ 'days_to_clear_log' ] );
			}

			/* check if old version of htaccess is used */
			if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
				$all_plugins = get_plugins();
				if ( is_plugin_active( 'htaccess/htaccess.php' ) || ( array_key_exists( 'htaccess/htaccess.php', $all_plugins ) && ! array_key_exists( 'htaccess-pro/htaccess-pro.php', $all_plugins ) ) ) {
					global $htccss_plugin_info;
					if ( ! $htccss_plugin_info )
						$htccss_plugin_info = get_plugin_data( plugin_dir_path( dirname( __FILE__ ) ) . 'htaccess/htaccess.php' );
					if ( $htccss_plugin_info["Version"] < '1.6.2' ) {
						if ( is_plugin_active( 'htaccess/htaccess.php' ) ) {
							do_action( 'lmtttmpts_htaccess_hook_for_delete_all' );
						}
						unset( $lmtttmpts_options['block_by_htaccess'] );
						$lmtttmpts_options['htaccess_notice'] = __( "Limit Attempts interaction with Htaccess was turned off since you are using an outdated Htaccess plugin version. If you want to keep using this interaction, please update Htaccess plugin at least to v 1.6.2.", 'lmtttmpts' );
					}
				}
			}

			$lmtttmpts_options = array_merge( $lmtttmpts_option_defaults, $lmtttmpts_options );
			$lmtttmpts_options['plugin_option_version'] = $lmtttmpts_plugin_info["Version"];
			$update_option = true;
		}
		/* Update tables when update plugin and tables changes*/
		if ( ! isset( $lmtttmpts_options['plugin_db_version'] ) || $lmtttmpts_options['plugin_db_version'] != $lmtttmpts_db_version ) {
			lmtttmpts_create_table();
			global $wpdb;
			$prefix = $wpdb->prefix . 'lmtttmpts_';
			/* crop table 'all_failed_attempts' */
			$column_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $prefix . "all_failed_attempts` LIKE 'invalid_captcha_from_login_form';" );
			/* drop columns */
			if ( ! empty( $column_exists ) )
				$wpdb->query( "ALTER TABLE `" . $prefix . "all_failed_attempts` 
					DROP `invalid_captcha_from_login_form`,
					DROP `invalid_captcha_from_registration_form`,
					DROP `invalid_captcha_from_reset_password_form`,
					DROP `invalid_captcha_from_comments_form`,
					DROP `invalid_captcha_from_contact_form`,
					DROP `invalid_captcha_from_subscriber`,
					DROP `invalid_captcha_from_bp_registration_form`,
					DROP `invalid_captcha_from_bp_comments_form`,
					DROP `invalid_captcha_from_bp_create_group_form`,
					DROP `invalid_captcha_from_contact_form_7`;" );
			/* update DB version */
			$lmtttmpts_options['plugin_db_version'] = $lmtttmpts_db_version;
			$update_option = true;
		}
		if ( isset( $update_option ) )
			update_option( 'lmtttmpts_options', $lmtttmpts_options );
	}
}

/**
 * Function to handle action links
 */
if ( ! function_exists( 'lmtttmpts_plugin_action_links' ) ) {
	function lmtttmpts_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename(__FILE__);

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=limit-attempts.php">' . __( 'Settings', 'lmtttmpts' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/**
 * Function to register plugin links
 */
if ( ! function_exists( 'lmtttmpts_register_plugin_links' ) ) {
	function lmtttmpts_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[]	=	'<a href="admin.php?page=limit-attempts.php">' . __( 'Settings', 'lmtttmpts' ) . '</a>';
			$links[]	=	'<a href="http://wordpress.org/plugins/limit-attempts/faq/" target="_blank">' . __( 'FAQ', 'lmtttmpts' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support', 'lmtttmpts' ) . '</a>';
		}
		return $links;
	}
}

/**
 * Function for display limit attempts settings page 
 * in the admin area and register new settings
 */
if ( ! function_exists( 'lmtttmpts_settings_page' ) ) {
	function lmtttmpts_settings_page() {		
		global $bstwbsftwppdtplgns_options, $lmtttmpts_options, $wpdb, $lmtttmpts_plugin_info, $wp_version;

		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$error = $message = '';
		$plugin_basename = plugin_basename( __FILE__ );
		
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();

		if ( is_multisite() ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		
		/* get admins for emails */
		$userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&role=administrator' );

		/* Start updating and verification options from Settings form */
		/* If form was submited - whether "Save changes" button was pressed or not - check for inputed values and firstly update var $lmtttmpts_options only */
		if ( isset( $_POST['lmtttmpts_form_submit'] )
			/* && ! isset( $_POST['lmtttmpts_return_default'] ) */
			&& check_admin_referer( $plugin_basename, 'lmtttmpts_nonce_name' ) ) {
			/*Verification and updating option with allowed retries, after which address will be blocked automatically*/
			if ( ( isset( $_POST['lmtttmpts_allowed_retries'] ) ) && ( $_POST['lmtttmpts_allowed_retries'] >= 1 ) && ( is_numeric( $_POST['lmtttmpts_allowed_retries'] ) ) )
				$lmtttmpts_options['allowed_retries'] = floor( $_POST['lmtttmpts_allowed_retries'] );
			/*Verification and updating option with days of lock*/
			if ( ( isset( $_POST['lmtttmpts_days_of_lock'] ) ) && ( $_POST['lmtttmpts_days_of_lock'] >= 0 ) && ( is_numeric( $_POST['lmtttmpts_days_of_lock'] ) ) )
				$lmtttmpts_options['days_of_lock'] = floor( $_POST['lmtttmpts_days_of_lock'] );
			/*Verification and updating option with hours of lock*/
			if ( isset( $_POST['lmtttmpts_hours_of_lock'] ) && ( $_POST['lmtttmpts_hours_of_lock'] >= 0 ) && ( $_POST['lmtttmpts_hours_of_lock'] <= 23 ) && ( is_numeric( $_POST['lmtttmpts_hours_of_lock'] ) ) )
				$lmtttmpts_options['hours_of_lock'] = floor( $_POST['lmtttmpts_hours_of_lock'] );
			elseif ( $_POST['lmtttmpts_hours_of_lock'] > 23 )
				$lmtttmpts_options['hours_of_lock'] = 23;
			/*Verification and updating option with minutes of lock*/
			if ( isset( $_POST['lmtttmpts_minutes_of_lock'] ) && ( $_POST['lmtttmpts_minutes_of_lock'] >= 0 ) && ( $_POST['lmtttmpts_minutes_of_lock'] <= 59 ) && ( is_numeric( $_POST['lmtttmpts_minutes_of_lock'] ) ) )
				$lmtttmpts_options['minutes_of_lock'] = floor( $_POST['lmtttmpts_minutes_of_lock'] );
			elseif ( $_POST['lmtttmpts_minutes_of_lock'] > 59 )
				$lmtttmpts_options['minutes_of_lock'] = 59;
			/*Verification and updating option with days to reset failed attempts quantity*/
			if ( ( isset( $_POST['lmtttmpts_days_to_reset'] ) ) && ( $_POST['lmtttmpts_days_to_reset'] >= 0 ) && ( is_numeric( $_POST['lmtttmpts_days_to_reset'] ) ) )
				$lmtttmpts_options['days_to_reset'] = floor( $_POST['lmtttmpts_days_to_reset'] );
			/*Verification and updating option with minutes to reset failed attempts quantity*/
			if ( isset( $_POST['lmtttmpts_hours_to_reset'] ) && ( $_POST['lmtttmpts_hours_to_reset'] >= 0 ) && ( $_POST['lmtttmpts_hours_to_reset'] <= 23 ) && ( is_numeric( $_POST['lmtttmpts_hours_to_reset'] ) ) )
				$lmtttmpts_options['hours_to_reset'] = floor( $_POST['lmtttmpts_hours_to_reset'] );
			elseif ( $_POST['lmtttmpts_hours_to_reset'] > 23 )
				$lmtttmpts_options['hours_to_reset'] = 23;
			/*Verification and updating option with minutes to reset failed attempts quantity*/
			if ( isset( $_POST['lmtttmpts_minutes_to_reset'] ) && ( $_POST['lmtttmpts_minutes_to_reset'] >= 0 ) && ( $_POST['lmtttmpts_minutes_to_reset'] <= 59 ) && ( is_numeric( $_POST['lmtttmpts_minutes_to_reset'] ) ) )
				$lmtttmpts_options['minutes_to_reset'] = floor( $_POST['lmtttmpts_minutes_to_reset'] );
			elseif ( $_POST['lmtttmpts_minutes_to_reset'] > 59 )
				$lmtttmpts_options['minutes_to_reset'] = 59;
			/*Verification and updating option with allowed locks, after which address will be blacklisted automatically*/
			if ( ( isset( $_POST['lmtttmpts_allowed_locks'] ) ) && ( $_POST['lmtttmpts_allowed_locks'] >= 1 ) && ( is_numeric( $_POST['lmtttmpts_allowed_locks'] ) ) )
				$lmtttmpts_options['allowed_locks'] = floor( $_POST['lmtttmpts_allowed_locks'] );
			/*Verification and updating option with days to reset blocks quantity*/
			if ( ( isset( $_POST['lmtttmpts_days_to_reset_block'] ) ) && ( $_POST['lmtttmpts_days_to_reset_block'] >= 0 ) && ( is_numeric( $_POST['lmtttmpts_days_to_reset_block'] ) ) )
				$lmtttmpts_options['days_to_reset_block'] = floor( $_POST['lmtttmpts_days_to_reset_block'] );
			/*Verification and updating option with hours to reset blocks quantity*/
			if ( isset( $_POST['lmtttmpts_hours_to_reset_block'] ) && ( $_POST['lmtttmpts_hours_to_reset_block'] >= 0 ) && ( $_POST['lmtttmpts_hours_to_reset_block'] <= 23 ) && ( is_numeric( $_POST['lmtttmpts_hours_to_reset_block'] ) ) )
				$lmtttmpts_options['hours_to_reset_block'] = floor( $_POST['lmtttmpts_hours_to_reset_block'] );
			elseif ( $_POST['lmtttmpts_hours_to_reset_block'] > 23 )
				$lmtttmpts_options['hours_to_reset_block'] = 23;
			/*Verification and updating option with minutes to reset blocks quantity*/
			if ( isset( $_POST['lmtttmpts_minutes_to_reset_block'] ) && ( $_POST['lmtttmpts_minutes_to_reset_block'] >= 0 ) && ( $_POST['lmtttmpts_minutes_to_reset_block'] <= 59 ) && ( is_numeric( $_POST['lmtttmpts_minutes_to_reset_block'] ) ) )
				$lmtttmpts_options['minutes_to_reset_block'] = floor( $_POST['lmtttmpts_minutes_to_reset_block'] );
			elseif ( $_POST['lmtttmpts_minutes_to_reset_block'] > 59 )
				$lmtttmpts_options['minutes_to_reset_block'] = 59;

			/* Veification and updating option with days to clear statistics */
			if ( isset( $_POST['lmtttmpts_days_to_clear_statistics'] ) && $_POST['lmtttmpts_days_to_clear_statistics'] >= 0 && is_numeric( $_POST['lmtttmpts_days_to_clear_statistics'] ) ) {
				if ( $lmtttmpts_options['days_to_clear_statistics'] != floor( $_POST['lmtttmpts_days_to_clear_statistics'] ) && isset( $_POST['lmtttmpts_form_submit_button'] ) ) {
					if ( $lmtttmpts_options['days_to_clear_statistics'] == 0 ) {
						$time = time() - fmod( time(), 86400 ) + 86400;
						wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_statistics_clear' );
					} elseif ( $_POST['lmtttmpts_days_to_clear_statistics'] == 0 ) {
						wp_clear_scheduled_hook( 'lmtttmpts_daily_statistics_clear' );
					}
				}
				$lmtttmpts_options['days_to_clear_statistics'] = floor( $_POST['lmtttmpts_days_to_clear_statistics'] );
			}

			/*Updating options with notify by email options*/
			$lmtttmpts_options['notify_email'] = ( isset( $_POST['lmtttmpts_notify_email'] ) ) ? true : false;

			if ( isset( $_POST['lmtttmpts_mailto'] ) ) {
				$lmtttmpts_options['mailto'] = $_POST['lmtttmpts_mailto'];
				if ( 'admin' == ( $_POST['lmtttmpts_mailto'] ) ) {
					$lmtttmpts_options['email_address'] = $_POST['lmtttmpts_user_email_address'];
				} elseif ( 'custom' == ( $_POST['lmtttmpts_mailto'] ) && isset( $_POST['lmtttmpts_email_address'] ) && is_email( $_POST['lmtttmpts_email_address'] ) ) {
					$lmtttmpts_options['email_address'] = $_POST['lmtttmpts_email_address'];
				}
			}

			/*Updating options of interaction with Htaccess plugin*/
			if ( isset( $_POST['lmtttmpts_block_by_htaccess'] ) ) {
				if ( ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $active_plugins ) ) || 0 < count( preg_grep( '/htaccess-pro\/htaccess-pro.php/', $active_plugins ) ) ) && ! isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_copy_all' );
				}
				$lmtttmpts_options['block_by_htaccess'] = $_POST['lmtttmpts_block_by_htaccess'];
			} else {
				if ( ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $active_plugins ) ) || 0 < count( preg_grep( '/htaccess-pro\/htaccess-pro.php/', $active_plugins ) ) ) && isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_delete_all' );
				}
				unset( $lmtttmpts_options['block_by_htaccess'] );
			}
			/*Updating options of interaction with Captcha plugin in login form*/
			if ( isset( $_POST['lmtttmpts_login_form_captcha_check'] ) )
				$lmtttmpts_options['login_form_captcha_check'] = $_POST['lmtttmpts_login_form_captcha_check'];
			else
				unset( $lmtttmpts_options['login_form_captcha_check'] );

			/* array for saving and restoring default messages */
			$lmtttmpts_messages = array(
				'failed_message', 'blocked_message', 'blacklisted_message', 'email_subject', 'email_subject_blacklisted', 'email_blocked', 'email_blacklisted',
			);
			/* Update messages when login failed, address blocked or blacklisted, email subject and text when address blocked or blacklisted */
			foreach ( $lmtttmpts_messages as $lmtttmpts_single_message ) {
				if ( isset( $_POST['lmtttmpts_' . $lmtttmpts_single_message ] ) ) {
					$lmtttmpts_options[ $lmtttmpts_single_message ] = stripslashes( $_POST['lmtttmpts_' . $lmtttmpts_single_message ] );
				}
			}
			/* Restore default messages */
			if ( isset( $_POST['lmtttmpts_return_default'] ) && in_array( $_POST['lmtttmpts_return_default'], $lmtttmpts_messages ) ) {
				$lmtttmpts_messages_defaults = lmtttmpts_get_default_messages();
				$lmtttmpts_options[ $_POST['lmtttmpts_return_default'] ] = $lmtttmpts_messages_defaults[ $_POST['lmtttmpts_return_default'] . '_default'];
			}

			/* save show/hide status of message-textarea blocks on setting page */
			if ( isset( $_POST['lmtttmpts_options_for_block_message'] ) ) {
				$lmtttmpts_options['options_for_block_message'] = $_POST['lmtttmpts_options_for_block_message'];
			}
			if ( isset( $_POST['lmtttmpts_options_for_email_message'] ) ) {
				$lmtttmpts_options['options_for_email_message'] = $_POST['lmtttmpts_options_for_email_message'];
			}
			/*Verification and updating option with time of block if they are zero total*/
			if ( ( $lmtttmpts_options['days_of_lock'] == 0 ) && ( $lmtttmpts_options['hours_of_lock'] == 0 ) && ( $lmtttmpts_options['minutes_of_lock'] == 0 ) )
				$lmtttmpts_options['minutes_of_lock'] = 1;
			/*Verification and updating option with time of reset failed attempts quantity if they are zero total*/
			if ( ( $lmtttmpts_options['days_to_reset'] == 0 ) && ( $lmtttmpts_options['hours_to_reset'] == 0 ) && ( $lmtttmpts_options['minutes_to_reset'] == 0 ) )
				$lmtttmpts_options['minutes_to_reset'] = 1;
			/*Verification and updating option with time of reset blocks quantity if they are zero total*/
			if ( ( $lmtttmpts_options['days_to_reset_block'] == 0 ) && ( $lmtttmpts_options['hours_to_reset_block'] == 0 ) && ( $lmtttmpts_options['minutes_to_reset_block'] == 0 ) )
				$lmtttmpts_options['minutes_to_reset_block'] = 1;

			/* Updating options in wp_options table if button "Save changes" is pressed */
			if ( isset( $_POST['lmtttmpts_form_submit_button'] ) )
				update_option( 'lmtttmpts_options', $lmtttmpts_options );
			/* Finish updating and verification options from Settings form */ 
		}

		/* GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			$go_pro_result = bws_go_pro_tab_check( $plugin_basename );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
		}
		
		/* allowed symbol to enter in black- and whitelist */
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'blacklist', 'whitelist' ) ) ) {
			$allowed_symbols = '<span class="lmtttmpts_little lmtttmpts_grey">' . __( "Allowed formats:", 'lmtttmpts' ) . ' <code>192.168.0.1</code></span>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<td valign="top">' . __( 'Reason', 'lmtttmpts' ) . ' <input/><br /><span class="lmtttmpts_little lmtttmpts_grey">' .
								__( "Allowed formats:", 'lmtttmpts' ) . ' <code>192.168.0.1</code>, <code>192.168.0.</code>, <code>192.168.</code>, <code>192.</code>, <code>192.168.0.1/8</code>, <code>192.168.0.1-192.168.2.255</code><br />' . __( "Allowed separators:", 'lmtttmpts' ) . ' ' . __( 'a comma', 'lmtttmpts' ) . ' (<code>,</code>), ' . __( 'semicolon', 'lmtttmpts' ) . ' (<code>;</code>), ' . __( 'ordinary space, tab, new line or carriage return', 'lmtttmpts' ) . 
								'</span></td>
							</tr>
						</table>
					</div>
					<div class="bws_pro_version_tooltip">
						<div class="bws_info">' .
							__( 'Unlock premium options by upgrading to a PRO version.', 'lmtttmpts' ) .
							' <a href="http://bestwebsoft.com/products/limit-attempts/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=' . $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version .'" target="_blank" title="Limit Attempts Pro">' . __( "Learn More", 'lmtttmpts' ) . '</a>
						</div>
						<a class="bws_button" href="http://bestwebsoft.com/products/limit-attempts/buy/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=' . $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank" title="Limit Attempts Pro">' .
							__( 'Go', 'lmtttmpts' ) . ' <strong>PRO</strong>
						</a>
						<div class="clear"></div>
					</div>
				</div>';
		} ?>
		<div class="wrap">
			<h2><?php _e( 'Limit Attempts Settings', 'lmtttmpts' ); ?></h2>
			<div id="lmtttmpts_settings_notice" class="updated fade" style="display:none">
				<p><strong><?php _e( "Notice:", 'lmtttmpts' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'lmtttmpts' ); ?></p>
			</div>

			<?php /* action message when working with blocked/black/white lists or statistics */
			$action_message = lmtttmpts_list_actions(); 
			if ( ! empty( $error ) )
				$action_message['error'] = $error; ?>
			<div class="error" <?php if ( empty( $action_message['error'] ) ) echo 'style="display:none"'; ?>><p><strong><?php if ( ! empty( $action_message['error'] ) ) echo $action_message['error']; ?></strong></div>
			<div class="updated" <?php if ( empty( $action_message['done'] ) ) echo 'style="display: none;"'?>><p><?php if ( ! empty( $action_message['done'] ) ) echo $action_message['done'] ?></p></div>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php"><?php _e( 'Settings', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'blocked' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;action=blocked"><?php _e( 'Blocked IP', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'blacklist' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;action=blacklist"><?php _e( 'Blacklist', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'whitelist' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;action=whitelist"><?php _e( 'Whitelist', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'log' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;action=log"><?php _e( 'Log', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'statistics' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;action=statistics"><?php _e( 'Statistics', 'lmtttmpts' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/limit-attempts/faq/" target="_blank"><?php _e( 'FAQ', 'lmtttmpts' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;action=go_pro"><?php _e( 'Go PRO', 'lmtttmpts' ); ?></a>
			</h2>
			<?php if ( ! isset( $_GET['action'] ) ) { /* Showing settings tab */
				/* display hidden error/email messages blocks - for disabled JS primarily */
				$hide_login_message_block = ( isset( $_GET['login_error_tab'] ) || ( isset( $_POST['lmtttmpts_options_for_block_message'] ) && 'show' == $_POST['lmtttmpts_options_for_block_message'] ) ) ? false : true;
				$hide_email_message_block = ( isset( $_GET['email_error_tab'] ) || ( isset( $_POST['lmtttmpts_options_for_email_message'] ) && 'show' == $_POST['lmtttmpts_options_for_email_message'] ) ) ? false : true; ?>
				<div id="lmtttmpts_settings">
					<form method="post" action="admin.php?page=limit-attempts.php">
						<table id="lmtttmpts_lock_options" class="form-table lmtttmpts_options_table">
							<tr>
								<th><?php _e( 'Block address', 'lmtttmpts' ) ?></th>
								<td class="lmtttmpts-lock-options">
									<p>
										<?php _e( 'for', 'lmtttmpts' ) ?>
										<span id="lmtttmpts-time-of-lock-display" class="lmtttmpts_hidden lmtttmpts-display">
											<span <?php if ( 0 == $lmtttmpts_options['days_of_lock'] ) echo 'class="lmtttmpts-zero-value"' ?> ><span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['days_of_lock']; ?></span> <?php echo _n( 'day', 'days', $lmtttmpts_options['days_of_lock'], 'lmtttmpts' ) ?></span>
											<span <?php if ( 0 == $lmtttmpts_options['hours_of_lock'] ) echo 'class="lmtttmpts-zero-value"' ?> ><span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['hours_of_lock']; ?></span> <?php echo _n( 'hour', 'hours', $lmtttmpts_options['hours_of_lock'], 'lmtttmpts' ) ?></span>
											<span <?php if ( 0 == $lmtttmpts_options['minutes_of_lock'] ) echo 'class="lmtttmpts-zero-value"' ?> ><span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['minutes_of_lock']; ?></span> <?php echo _n( 'minute', 'minutes', $lmtttmpts_options['minutes_of_lock'], 'lmtttmpts' ) ?></span>
											<span id="lmtttmpts-time-of-lock-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'lmtttmpts' ) ?></span>
										</span>
										<span id="lmtttmpts-time-of-lock" class="lmtttmpts-hidden-input">
											<input id="lmtttmpts-days-of-lock-display" type="number" max="999" min="0" step="1" maxlength="3" value="<?php echo $lmtttmpts_options['days_of_lock'] ; ?>" name="lmtttmpts_days_of_lock" /> <?php _e( 'days', 'lmtttmpts' ) ?> 
											<input id="lmtttmpts-hours-of-lock-display" type="number" max="23" min="0" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['hours_of_lock'] ; ?>" name="lmtttmpts_hours_of_lock" /> <?php _e( 'hours', 'lmtttmpts' ) ?> 
											<input id="lmtttmpts-minutes-of-lock-display" type="number" max="59" min="0" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['minutes_of_lock'] ; ?>" name="lmtttmpts_minutes_of_lock" /> <?php _e( 'minutes', 'lmtttmpts' ) ?>
										</span>
									</p>
									<p>
										<?php _e( 'after', 'lmtttmpts' ) ?>
										<span id="lmtttmpts-allowed-retries-display" class="lmtttmpts_hidden lmtttmpts-display">
											<span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['allowed_retries']; ?></span> <?php echo _n( 'failed attempt', 'failed attempts', $lmtttmpts_options['allowed_retries'], 'lmtttmpts' ) ?>
											<span id="lmtttmpts-allowed-retries-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'lmtttmpts' ) ?></span>
										</span>
										<span id="lmtttmpts-allowed-retries" class="lmtttmpts-hidden-input">
											<input id="lmtttmpts-allowed-retries-number-display" type="number" min="1" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['allowed_retries'] ; ?>" name="lmtttmpts_allowed_retries" /> <?php _e( 'failed attempts', 'lmtttmpts' ) ?>
										</span>
									</p>
									<p>
										<?php _e( 'per', 'lmtttmpts' ) ?> 
										<span id="lmtttmpts-time-to-reset-display" class="lmtttmpts_hidden lmtttmpts-display">
											<span <?php if ( 0 == $lmtttmpts_options['days_to_reset'] ) echo 'class="lmtttmpts-zero-value"' ?> > <span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['days_to_reset']; ?></span> <?php echo _n( 'day', 'days', $lmtttmpts_options['days_to_reset'], 'lmtttmpts' ) ?></span>
											<span <?php if ( 0 == $lmtttmpts_options['hours_to_reset'] ) echo 'class="lmtttmpts-zero-value"' ?> > <span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['hours_to_reset']; ?></span> <?php echo _n( 'hour', 'hours', $lmtttmpts_options['hours_to_reset'], 'lmtttmpts' ) ?></span>
											<span <?php if ( 0 == $lmtttmpts_options['minutes_to_reset'] ) echo 'class="lmtttmpts-zero-value"' ?> > <span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['minutes_to_reset']; ?></span> <?php echo _n( 'minute', 'minutes', $lmtttmpts_options['minutes_to_reset'], 'lmtttmpts' ) ?></span>
											<span id="lmtttmpts-time-to-reset-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'lmtttmpts' ) ?></span>
										</span>
										<span id="lmtttmpts-time-to-reset" class="lmtttmpts-hidden-input">
											<input id="lmtttmpts-days-to-reset-display" type="number" max="999" min="0" step="1" maxlength="3" value="<?php echo $lmtttmpts_options['days_to_reset'] ; ?>" name="lmtttmpts_days_to_reset" /> <?php _e( 'days', 'lmtttmpts' ) ?> 
											<input id="lmtttmpts-hours-to-reset-display" type="number" max="23" min="0" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['hours_to_reset'] ; ?>" name="lmtttmpts_hours_to_reset" /> <?php _e( 'hours', 'lmtttmpts' ) ?> 
											<input id="lmtttmpts-minutes-to-reset-display" type="number" max="59" min="0" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['minutes_to_reset'] ; ?>" name="lmtttmpts_minutes_to_reset" /> <?php _e( 'minutes', 'lmtttmpts' ); ?>
										</span>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Add to the blacklist', 'lmtttmpts') ?></th>
								<td>
									<p>
										<?php _e( 'after', 'lmtttmpts' ) ?>
										<span id="lmtttmpts-allowed-locks-display" class="lmtttmpts_hidden lmtttmpts-display">
											<span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['allowed_locks']; ?></span> <?php echo _n( 'block', 'blocks', $lmtttmpts_options['allowed_locks'], 'lmtttmpts' ) ?>
											<span id="lmtttmpts-allowed-locks-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'lmtttmpts' ) ?></span>
										</span>
										<span id="lmtttmpts-allowed-locks" class="lmtttmpts-hidden-input">
											<input id="lmtttmpts-allowed-locks-number-display" type="number" min="1" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['allowed_locks'] ; ?>" name="lmtttmpts_allowed_locks" /> <?php _e( 'blocks','lmtttmpts' );?>
										</span>
									</p>
									<p>
										<?php _e( 'per', 'lmtttmpts' ) ?>
										<span id="lmtttmpts-time-to-reset-block-display" class="lmtttmpts_hidden lmtttmpts-display">
											<span <?php if ( 0 == $lmtttmpts_options['days_to_reset_block'] ) echo 'class="lmtttmpts-zero-value"' ?> ><span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['days_to_reset_block']; ?></span> <?php echo _n( 'day', 'days', $lmtttmpts_options['days_to_reset_block'], 'lmtttmpts' ) ?></span>
											<span <?php if ( 0 == $lmtttmpts_options['hours_to_reset_block'] ) echo 'class="lmtttmpts-zero-value"' ?> ><span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['hours_to_reset_block']; ?></span> <?php echo _n( 'hour', 'hours', $lmtttmpts_options['hours_to_reset_block'], 'lmtttmpts' ) ?></span>
											<span <?php if ( 0 == $lmtttmpts_options['minutes_to_reset_block'] ) echo 'class="lmtttmpts-zero-value"' ?> ><span class="lmtttmpts-unit-measure" ><?php echo $lmtttmpts_options['minutes_to_reset_block']; ?></span> <?php echo _n( 'minute', 'minutes', $lmtttmpts_options['minutes_to_reset_block'], 'lmtttmpts' ) ?></span>
											<span id="lmtttmpts-time-to-reset-block-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'lmtttmpts' ) ?></span>
										</span>
										<span id="lmtttmpts-time-to-reset-block" class="lmtttmpts-hidden-input">
											<input id="lmtttmpts-days-to-reset-block-display" type="number" max="999" min="0" step="1" maxlength="3" value="<?php echo $lmtttmpts_options['days_to_reset_block'] ; ?>" name="lmtttmpts_days_to_reset_block" /> <?php _e( 'days', 'lmtttmpts' ) ?> 
											<input id="lmtttmpts-hours-to-reset-block-display" type="number" max="23" min="0" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['hours_to_reset_block'] ; ?>" name="lmtttmpts_hours_to_reset_block" /> <?php _e( 'hours', 'lmtttmpts' ) ?> 
											<input id="lmtttmpts-minutes-to-reset-block-display" type="number" max="59" min="0" step="1" maxlength="2" value="<?php echo $lmtttmpts_options['minutes_to_reset_block'] ; ?>" name="lmtttmpts_minutes_to_reset_block" /> <?php _e( 'minutes', 'lmtttmpts' ); ?>
										</span>
									</p>
								</td>
							</tr>
						</table>
						<div class="bws_pro_version_bloc">
							<div class="bws_pro_version_table_bloc">
								<div class="bws_table_bg"></div>
								<table class="form-table bws_pro_version lmtttmpts_options_table_demo">
									<tr>
										<th><?php _e( 'Remove log entries that are over', 'lmtttmpts' ) ?></th>
										<td style="min-width: 210px;">
											<input disabled="disabled" type="number" min="0" step="1" maxlength="3" value="30"/> <?php _e( 'days', 'lmtttmpts' ) ?><br/>
											<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Set "0" if you do not want to clear the log.', 'lmtttmpts' ) ?><br />
											<?php echo __( 'Current size of DB table', 'lmtttmpts' ) . '&asymp; <strong>' . '1.234' . '</strong> ' . __( 'Mb', 'lmtttmpts' ); ?></span>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" colspan="3">
											* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'lmtttmpts' ); ?>
										</th>
									</tr>
								</table>
							</div>
							<div class="bws_pro_version_tooltip">
								<div class="bws_info">
									<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'lmtttmpts' ); ?>
									<a href="http://bestwebsoft.com/products/limit-attempts/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro"><?php _e( "Learn More", 'lmtttmpts' ); ?></a>
								</div>
								<a class="bws_button" href="http://bestwebsoft.com/products/limit-attempts/buy/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro">
								<?php _e( 'Go', 'lmtttmpts' ); ?> <strong>PRO</strong>
								</a>
								<div class="clear"></div>
							</div>
						</div>	
						<table class="form-table lmtttmpts_options_table">
							<tr>
								<th><?php _e( 'Remove statistics entry in case no failed attempts occurred for', 'lmtttmpts' ) ?></th>
								<td>
									<input type="number" min="0" step="1" maxlength="3" value="<?php echo $lmtttmpts_options['days_to_clear_statistics']; ?>" name="lmtttmpts_days_to_clear_statistics" /> <?php _e( 'days', 'lmtttmpts' ) ?><br/>
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Set "0" if you do not want to clear the statistics.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<th><?php _e( 'Error messages', 'lmtttmpts' ) ?></th>
							<td>
								<button id="lmtttmpts_hide_options_for_block_message_button" class="button-secondary" <?php if ( $hide_login_message_block ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_block_message" value="hide"><?php _e( 'Hide', 'lmtttmpts' ) ?></button>
								<button id="lmtttmpts_show_options_for_block_message_button" class="button-secondary" <?php if ( ! $hide_login_message_block ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_block_message" value="show"><?php _e( 'Show', 'lmtttmpts' ) ?></button>
							</td>
						</table>
						<h3 id="lmtttmpts_nav_tab_message_no_js" class="nav-tab-wrapper lmtttmpts_block_message_block <?php if ( $hide_login_message_block ) echo "lmtttmpts_hidden" ?>">
							<a class="nav-tab<?php if ( ! isset( $_GET['login_error_tab'] ) || 'failed' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;login_error_tab=failed"><?php _e( 'For invalid attempt', 'lmtttmpts' ); ?></a>
							<a class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blocked' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;login_error_tab=blocked"><?php _e( 'For blocked user', 'lmtttmpts' ); ?></a>
							<a class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blacklisted' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;login_error_tab=blacklisted"><?php _e( 'For blacklisted user', 'lmtttmpts' ); ?></a>
						</h3>
						<h3 id="lmtttmpts_nav_tab_message_js" style="display:none" class="nav-tab-wrapper lmtttmpts_block_message_block <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'hide' == $lmtttmpts_options['options_for_block_message'] ) echo "lmtttmpts_hidden" ?>">
							<p id="lmtttmpts_message_invalid_attempt" style="cursor:pointer" class="nav-tab<?php if ( ! isset( $_GET['login_error_tab'] ) ) echo ' nav-tab-active'; ?>" ><?php _e( 'For invalid attempt', 'lmtttmpts' ); ?></p>
							<p id="lmtttmpts_message_blocked" style="cursor:pointer" class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blocked' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" ><?php _e( 'For blocked user', 'lmtttmpts' ); ?></p>
							<p id="lmtttmpts_message_blacklisted" style="cursor:pointer" class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blacklisted' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" ><?php _e( 'For blacklisted user', 'lmtttmpts' ); ?></p>
						</h3>
						<table class="form-table lmtttmpts_block_message_block <?php if ( $hide_login_message_block ) echo "lmtttmpts_hidden" ?>">
							<tr id="lmtttmpts_message_invalid_attempt_area" <?php if ( isset( $_GET['login_error_tab'] ) && 'failed' != $_GET['login_error_tab'] ) echo 'class="lmtttmpts_hidden"' ?>>
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ); ?></p>
									<ul>
										<li>'%ATTEMPTS%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display quantity of allowed attempts', 'lmtttmpts' ); ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="failed_message"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="5" cols="100" name="lmtttmpts_failed_message"><?php echo $lmtttmpts_options['failed_message'] ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr id="lmtttmpts_message_blocked_area" <?php if ( ! isset( $_GET['login_error_tab'] ) || ( isset( $_GET['login_error_tab'] ) && 'blocked' != $_GET['login_error_tab'] ) ) echo 'class="lmtttmpts_hidden"' ?>>
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p> 
									<ul>
										<li>'%DATE%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display date when block is removed', 'lmtttmpts' ); ?>)</span></li>
										<li>'%MAIL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display administrator&rsquo;s e-mail for feedback', 'lmtttmpts' ); ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="blocked_message"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="5" cols="100" name="lmtttmpts_blocked_message"><?php echo $lmtttmpts_options['blocked_message'] ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr id="lmtttmpts_message_blacklisted_area" <?php if ( ! isset( $_GET['login_error_tab'] ) || ( isset( $_GET['login_error_tab'] ) && 'blacklisted' != $_GET['login_error_tab'] ) ) echo 'class="lmtttmpts_hidden"'?>>
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p> 
									<ul>
										<li>'%MAIL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display administrators e-mail for feedback', 'lmtttmpts' ); ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="blacklisted_message"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="5" cols="100" name="lmtttmpts_blacklisted_message"><?php echo $lmtttmpts_options['blacklisted_message'] ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
						</table>
						<table id="lmtttmpts_notify_options" class="form-table">
							<tr>
								<th><?php _e( 'Send email with notification', 'lmtttmpts' ) ?></th>
								<td style="width:15px" class="lmtttmpts_align_top">
									<input id="lmtttmpts_notify_email_options" type="checkbox" name="lmtttmpts_notify_email" value="1" <?php if ( $lmtttmpts_options['notify_email'] ) echo 'checked="checked" ' ?>/><br />
								</td>
								<td class="lmtttmpts_align_top lmtttmpts_notify_email_block <?php if ( isset( $lmtttmpts_options['notify_email'] ) && false === $lmtttmpts_options['notify_email'] ) echo "lmtttmpts_hidden" ?>" style="max-width:150px;">
									<input type="radio" id="lmtttmpts_user_mailto" name="lmtttmpts_mailto" value="admin" <?php if ( isset( $lmtttmpts_options['mailto'] ) && $lmtttmpts_options['mailto'] == 'admin' ) echo 'checked="checked" ' ?>/><label for="lmtttmpts_user_mailto"><?php _e( "Email to user's address", 'lmtttmpts' ) ?></label>
									<select name="lmtttmpts_user_email_address" onfocus="document.getElementById('lmtttmpts_user_mailto').checked = true;">
										<option disabled><?php _e( "Choose a username", 'lmtttmpts' ); ?></option>
										<?php foreach ( $userslogin as $key => $value ) {
											if ( $value->data->user_email != '' ) { ?>
												<option value="<?php echo $value->data->user_email; ?>" <?php if ( $value->data->user_email == $lmtttmpts_options['email_address'] ) echo 'selected="selected" '; ?>><?php echo $value->data->user_login; ?></option>
											<?php }
										} ?>
									</select></br>
									<input type="radio" id="lmtttmpts_custom_mailto" name="lmtttmpts_mailto" value="custom" <?php if ( isset( $lmtttmpts_options['mailto'] ) && $lmtttmpts_options['mailto'] == 'custom' ) echo 'checked="checked" ' ?>/><label for="lmtttmpts_custom_mailto"><?php _e( 'Email to another address', 'lmtttmpts' ) ?></label> <input type="email" maxlength="100" name="lmtttmpts_email_address" value="<?php if ( $lmtttmpts_options['mailto'] == 'custom' ) echo $lmtttmpts_options['email_address']; ?>" onfocus="document.getElementById('lmtttmpts_custom_mailto').checked = true;"/>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Additional options for email with notification', 'lmtttmpts' ) ?></th>
								<td>
									<button id="lmtttmpts_hide_options_for_email_message_button" class="button-secondary" <?php if ( $hide_email_message_block ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_email_message" value="hide"><?php _e( 'Hide', 'lmtttmpts' ) ?></button>
									<button id="lmtttmpts_show_options_for_email_message_button" class="button-secondary" <?php if ( ! $hide_email_message_block ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_email_message" value="show"><?php _e( 'Show', 'lmtttmpts' ) ?></button>
								</td>
								<td></td>
							</tr>
						</table>
						<h3 id="lmtttmpts_nav_tab_email_no_js_a" class="nav-tab-wrapper lmtttmpts_email_message_block <?php if ( $hide_email_message_block ) echo "lmtttmpts_hidden" ?>">
							<a class="nav-tab<?php if ( ! isset( $_GET['email_error_tab'] ) || 'blocked' == $_GET['email_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;email_error_tab=blocked"><?php _e( 'Email to admistrator when user is blocked', 'lmtttmpts' ); ?></a>
							<a class="nav-tab<?php if ( isset( $_GET['email_error_tab'] ) && 'blacklisted' == $_GET['email_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;email_error_tab=blacklisted"><?php _e( 'Email to admistrator when user is blacklisted', 'lmtttmpts' ); ?></a>
						</h3>
						<h3 id="lmtttmpts_nav_tab_email_js_a" style="display:none" class="nav-tab-wrapper lmtttmpts_email_message_block <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'hide' == $lmtttmpts_options['options_for_email_message'] ) echo "lmtttmpts_hidden" ?>">
							<p id="lmtttmpts_email_blocked" class="nav-tab<?php if ( ! isset( $_GET['email_error_tab'] ) ) echo ' nav-tab-active'; ?>" ><?php _e( 'Email to admistrator when user is blocked', 'lmtttmpts' ); ?></p>
							<p id="lmtttmpts_email_blacklisted" class="nav-tab<?php if ( isset( $_GET['email_error_tab'] ) && 'blacklisted' == $_GET['email_error_tab'] ) echo ' nav-tab-active'; ?>" ><?php _e( 'Email to admistrator when user is blacklisted', 'lmtttmpts' ); ?></p>
						</h3>
						<table class="form-table lmtttmpts_email_message_block <?php if ( $hide_email_message_block ) echo "lmtttmpts_hidden" ?>">
							<tr>
								<th><?php _e( 'Subject', 'lmtttmpts' ) ?></th>
							</tr>
							<tr id="lmtttmpts_email_subject_area" <?php if ( isset( $_GET['email_error_tab'] ) && 'blocked' != $_GET['email_error_tab'] ) echo 'class="lmtttmpts_hidden"' ?> >
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p>
									<ul>
										<li>'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display blocked IP address', 'lmtttmpts' ) ?>)</span></li>
										<li>'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_subject"><?php _e( 'Restore default subject', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="1" cols="100" name="lmtttmpts_email_subject"><?php echo $lmtttmpts_options['email_subject']; ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr id="lmtttmpts_email_subject_blacklisted_area" <?php if ( ! isset( $_GET['email_error_tab'] ) || ( isset( $_GET['email_error_tab'] ) && 'blacklisted' != $_GET['email_error_tab'] ) ) echo 'class="lmtttmpts_hidden"' ?> >
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p>
									<ul>
										<li>'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display blacklisted IP address', 'lmtttmpts' ) ?>)</span></li>
										<li>'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_subject_blacklisted"><?php _e( 'Restore default subject', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="1" cols="100" name="lmtttmpts_email_subject_blacklisted"><?php echo $lmtttmpts_options['email_subject_blacklisted']; ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Message', 'lmtttmpts' ) ?></th>
							</tr>
							<tr id="lmtttmpts_email_blocked_area" <?php if ( isset( $_GET['email_error_tab'] ) && 'blocked' != $_GET['email_error_tab'] ) echo 'class="lmtttmpts_hidden"' ?> >
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p>
									<ul>
										<li>'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display IP address that is blocked', 'lmtttmpts' ) ?>)</span></li>
										<li>'%PLUGIN_LINK%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display link for Limit Attempts plugin on your site', 'lmtttmpts' ) ?>)</span></li>
										<li>'%WHEN%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display date and time when IP address was blocked', 'lmtttmpts' ) ?>)</span></li>
										<li>'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span></li>
										<li>'%SITE_URL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( "display your site's URL", 'lmtttmpts' ) ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_blocked"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="5" cols="100" name="lmtttmpts_email_blocked"><?php echo $lmtttmpts_options['email_blocked']; ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr id="lmtttmpts_email_blacklisted_area" <?php if ( ! isset( $_GET['email_error_tab'] ) || ( isset( $_GET['email_error_tab'] ) && 'blacklisted' != $_GET['email_error_tab'] ) ) echo 'class="lmtttmpts_hidden"' ?> >
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p>
									<ul>
										<li>'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display IP address that is added to the blacklist', 'lmtttmpts' ) ?>)</span></li>
										<li>'%PLUGIN_LINK%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display link for Limit Attempts plugin on your site', 'lmtttmpts' ) ?>)</span></li>
										<li>'%WHEN%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display date and time when IP address was blacklisted', 'lmtttmpts' ) ?>)</span></li>
										<li>'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span></li>
										<li>'%SITE_URL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( "display your site's URL", 'lmtttmpts' ) ?>)</span></li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_blacklisted"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="5" cols="100" name="lmtttmpts_email_blacklisted"><?php echo $lmtttmpts_options['email_blacklisted'] ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
						</table>
						<table id="lmtttmpts_interaction_settings" class="form-table">
							<tr>
								<th><?php _e( "Htaccess plugin", 'lmtttmpts' ); ?> </th>
								<td>
									<?php if ( array_key_exists( 'htaccess/htaccess.php', $all_plugins ) || array_key_exists( 'htaccess-pro/htaccess-pro.php', $all_plugins ) ) {
										$htaccess_free_active = ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $active_plugins ) ) ) ? true : false;
										$htaccess_pro_active = ( 0 < count( preg_grep( '/htaccess-pro\/htaccess-pro.php/', $active_plugins ) ) ) ? true : false;
										if ( $htaccess_free_active || $htaccess_pro_active ) { 
											if ( $htaccess_pro_active && ! $htaccess_free_active ) { ?>
												<input type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo 'checked="checked"'; ?> />
												<span class="bws_info"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=htaccess-pro.php">Htaccess Pro</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>)</span>
											<?php } elseif ( $htaccess_free_active && isset( $all_plugins['htaccess/htaccess.php']['Version'] ) && $all_plugins['htaccess/htaccess.php']['Version'] >= '1.6.2' ) { ?>
												<input type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo 'checked="checked"'; ?> />
												<span class="bws_info"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=htaccess.php">Htaccess</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>)</span>
											<?php } else { ?>
												<input disabled="disabled" type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo 'checked="checked"'; ?> />
												<span class="bws_info">(<?php _e( 'Using Htaccess powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Update Htaccess at least to v.1.6.2', 'lmtttmpts' ); ?></a></span>
											<?php }
										} else { ?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo 'checked="checked"'; ?> />
											<span class="bws_info">(<?php _e( 'Using Htaccess powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Activate Htaccess', 'lmtttmpts' ); ?></a></span>
										<?php }
									} else { ?>
										<input disabled="disabled" type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" />
										<span class="bws_info">(<?php _e( 'Using Htaccess powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="http://bestwebsoft.com/products/htaccess/"><?php _e( 'Download Htaccess', 'lmtttmpts' ); ?></a></span>
									<?php } ?>
									<br /><span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Allow Htaccess plugin block IP to reduce the database workload.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Captcha plugin', 'lmtttmpts' ) ?></th>
								<td>
									<?php if ( array_key_exists( 'captcha/captcha.php', $all_plugins ) || array_key_exists( 'captcha-plus/captcha-plus.php', $all_plugins ) || array_key_exists( 'captcha-pro/captcha_pro.php', $all_plugins ) ) {
										/* if captcha is installed */
										if ( 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) || 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) || 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $active_plugins ) ) ) {
											/* if captcha plugin is active */
											if ( 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) ) { 	
												/* if Captcha PRO is active */
												if ( isset( $all_plugins['captcha-pro/captcha_pro.php']['Version'] ) && $all_plugins['captcha-pro/captcha_pro.php']['Version'] >= '1.4.4' ) { ?>
													<!-- Checkbox for Login form captcha checking -->
													<label>
														<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options['login_form_captcha_check'] ) ) echo 'checked="checked"'; ?> />
														<span><?php _e( 'Login form', 'lmtttmpts' ); ?></span>
													</label>
													<span class="bws_info"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=captcha_pro.php">Captcha Pro</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>)</span>
												<?php } else { ?>
													<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> />
													<span class="bws_info">(<?php _e( 'Using Captcha Pro powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Update Captcha Pro at least to v.1.4.4', 'lmtttmpts' ); ?></a></span>
												<?php }
											} elseif ( 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $active_plugins ) ) ) {
												/* if Captcha Plus is active */?>
												<label>
													<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options['login_form_captcha_check'] ) ) echo 'checked="checked"'; ?> />
													<span><?php _e( 'Login form', 'lmtttmpts' ); ?></span>
												</label>
												<span class="bws_info"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=captcha-plus.php">Captcha Plus</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>)</span>
											<?php } else {
												/* Captcha free is active */
												if ( isset( $all_plugins['captcha/captcha.php']['Version'] ) && $all_plugins['captcha/captcha.php']['Version'] >= '4.0.2' ) { ?>
													<label>
														<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options['login_form_captcha_check'] ) ) echo 'checked="checked"'; ?> />
														<span><?php _e( 'Login form', 'lmtttmpts' ); ?></span>
													</label>
													<span class="bws_info"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=captcha.php">Captcha</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>)</span>
												<?php } else { ?>
													<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> />
													<span class="bws_info">(<?php _e( 'Using Captcha powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Update Captcha at least to v.4.0.2', 'lmtttmpts' ); ?></a></span>
												<?php }
											}
										} else {
											/* if no plugin is active */
											if ( array_key_exists( 'captcha-pro/captcha_pro.php', $all_plugins ) ) {
												$using_plugin_name = 'Captcha Pro';
											} elseif ( array_key_exists( 'captcha-plus/captcha-plus.php', $all_plugins ) ) {
												$using_plugin_name = 'Captcha Plus';
											} else {
												$using_plugin_name = 'Captcha';
											}
											?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> /><span class="bws_info"> (<?php printf( __( 'Using %s powered by', 'lmtttmpts' ), $using_plugin_name ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php printf( __( 'Activate %s', 'lmtttmpts' ), $using_plugin_name ); ?>
											</a></span>
										<?php }
									} else { ?>
										<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" />
										<span class="bws_info">(<?php _e( 'Using Captcha powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/products/">bestwebsoft.com</a>) <a href="http://bestwebsoft.com/products/captcha/"><?php _e( 'Download Captcha', 'lmtttmpts' ); ?></a></span>
									<?php } ?>
									<br /><span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Consider the incorrect captcha input as an invalid attempt.', 'lmtttmpts' ) ?></span>

									<?php $captcha_span = '<span class="bws_info"> (' . __( 'Using', 'lmtttmpts' ) . ' <a href="">Captcha Pro</a> ' . __( 'powered by', 'lmtttmpts' ) . ' <a href="">bestwebsoft.com</a>)</span>' ?>
									<div class="bws_pro_version_bloc" style="max-width: 590px;">
										<div class="bws_pro_version_table_bloc">
											<div class="bws_table_bg"></div>
											<table class="form-table bws_pro_version lmtttmpts_options_table_demo">
												<tr>
													<td style="min-width: 430px;">
														<label><input disabled="disabled" type="checkbox" checked="checked"/><span> <?php _e( 'Registration form', 'lmtttmpts' ); ?></span></label><? echo $captcha_span ?><br />
														<label><input disabled="disabled" type="checkbox"/><span> <?php _e( 'Reset Password form', 'lmtttmpts' ); ?></span></label><? echo $captcha_span ?><br />
														<label><input disabled="disabled" type="checkbox"/><span> <?php _e( 'Comments form', 'lmtttmpts' ); ?></span></label><? echo $captcha_span ?><br />
														<label><input disabled="disabled" type="checkbox" checked="checked"/><span> <?php _e( 'Contact form', 'lmtttmpts' ); ?></span></label><span class="bws_info"> (<?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="">bestwebsoft.com</a>)</span><br />
														<label><input disabled="disabled" type="checkbox"/><span> <?php _e( 'Buddypress registration form', 'lmtttmpts' ); ?></span></label><br />
														<label><input disabled="disabled" type="checkbox"/><span> <?php _e( 'Buddypress comments form', 'lmtttmpts' ); ?></span></label><br />
														<label><input disabled="disabled" type="checkbox"/><span> <?php _e( 'Buddypress "Create a Group" form', 'lmtttmpts' ); ?></span></label><br />
														<label><input disabled="disabled" type="checkbox" checked="checked"/><span> <?php _e( 'Contact Form 7', 'lmtttmpts' ); ?></span></label>
													</td>
												</tr>
												<tr valign="top">
													<th scope="row" colspan="3">
														<p>* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'lmtttmpts' ); ?></p>
														<p>* <?php _e( 'You also need Captcha PRO to use these options.', 'lmtttmpts' ); ?></p>
													</th>
												</tr>
											</table>
										</div>
										<div class="bws_pro_version_tooltip">
											<div class="bws_info">
												<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'lmtttmpts' ); ?>
												<a href="http://bestwebsoft.com/products/limit-attempts/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro"><?php _e( "Learn More", 'lmtttmpts' ); ?></a>
											</div>
											<a class="bws_button" href="http://bestwebsoft.com/products/limit-attempts/buy/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro">
											<?php _e( 'Go', 'lmtttmpts' ); ?> <strong>PRO</strong>
											</a>
											<div class="clear"></div>
										</div>
									</div>
								</td>
							</tr>
						</table>
						<input type="hidden" name="lmtttmpts_form_submit" value="submit" />
						<p class="submit">
							<input type="submit" name="lmtttmpts_form_submit_button" class="button-primary" value="<?php _e( 'Save Changes', 'lmtttmpts' ) ?>" />
						</p>
						<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'blocked' == $_GET['action'] ) {
				/* Showing blocked list table using wp_list_table class */ ?>
				<div id="lmtttmpts_blocked">
					<?php $lmtttmpts_blocked_list = new Lmtttmpts_Blocked_list();
					$lmtttmpts_blocked_list->prepare_items(); ?>
					<form method="get" action="admin.php">
						<?php $lmtttmpts_blocked_list->search_box( __( 'Search IP', 'lmtttmpts' ), 'search_blocked_ip' );?>
						<input type="hidden" name="page" value="limit-attempts.php" />
						<input type="hidden" name="action" value="blocked" />
					</form>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;action=blocked">
						<?php $lmtttmpts_blocked_list->display();
						wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'blacklist' == $_GET['action'] ) {
				/* Showing blacklist table using wp_list_table class */ ?>
				<div id="lmtttmpts_blacklist">
					<form method="post" action="admin.php?page=limit-attempts.php&amp;action=blacklist">
						<td><input type="text" maxlength="31" name="lmtttmpts_add_to_blacklist" /></td>
						<td><input type="submit" class="button-secondary" value="<?php _e( 'Add IP to blacklist', 'lmtttmpts' ) ?>" /></td>
						<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
					</form>
					<?php echo $allowed_symbols;
					$lmtttmpts_blacklist_table = new Lmtttmpts_Blacklist();
					$lmtttmpts_blacklist_table->prepare_items(); ?>
					<form method="get" action="admin.php">
						<?php $lmtttmpts_blacklist_table->search_box( __( 'Search IP', 'lmtttmpts' ), 'search_blacklisted_ip' ); ?>
						<input type="hidden" name="page" value="limit-attempts.php" />
						<input type="hidden" name="action" value="blacklist" />
					</form>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;action=blacklist">
						<?php $lmtttmpts_blacklist_table->display();
						wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'whitelist' == $_GET['action'] ) {
				/* Showing whitelist table using wp_list_table class */ ?>
				<div id="lmtttmpts_whitelist">
					<form method="post" action="admin.php?page=limit-attempts.php&amp;action=whitelist">
						<td><input type="text" maxlength="31" name="lmtttmpts_add_to_whitelist" /></td>
						<td><input type="submit" class="button-secondary" value="<?php _e( 'Add IP to whitelist', 'lmtttmpts' ) ?>" /></td>
						<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
					</form>
					<?php echo $allowed_symbols;
					$lmtttmpts_whitelist_table = new Lmtttmpts_Whitelist();
					$lmtttmpts_whitelist_table->prepare_items(); ?>
					<form method="get" action="admin.php">
						<?php $lmtttmpts_whitelist_table->search_box( __( 'Search IP', 'lmtttmpts' ), 'search_whitelisted_ip' ); ?>
						<input type="hidden" name="page" value="limit-attempts.php" />
						<input type="hidden" name="action" value="whitelist" />
					</form>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;action=whitelist">
						<?php $lmtttmpts_whitelist_table->display();
						wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'log' == $_GET['action'] ) {
				/* PRO-teaser of 'Log' tab */ ?>
				<div id="lmtttmpts_log">
					<div style="max-width: 100%" class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<div class="bws_table_bg"></div>
							<div style="padding: 5px;">
								<form><p class="search-box"><input disabled="disabled" type="search" name="s"><input disabled="disabled" type="submit" value="<?php _e( 'Search IP', 'lmtttmpts' ); ?>" class="button"></p></form>
								<form><input disabled="disabled" type="submit" value="<?php _e( 'Clear Log', 'lmtttmpts' ); ?>" class="button"></form>
								<form>
									<div class="tablenav top"><div class="alignleft actions bulkactions"><select disabled="disabled"><option><?php _e( 'Delete log entry', 'lmtttmpts' ); ?></option></select><input disabled="disabled" type="submit" value="Apply" class="button action"></div><div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div><br class="clear"></div>
									<table class="wp-list-table widefat fixed bws-plugins_page_limit-attempts-pro">
										<thead><tr><th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox"></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'IP address', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Internet Hostname', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Event', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Form', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Event time', 'lmtttmpts' ); ?></a></th></tr></thead>
										<tfoot><tr><th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox"></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'IP address', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Internet Hostname', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Event', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Form', 'lmtttmpts' ); ?></span></a></th><th class="manage-column" scope="col"><a href=""><span><?php _e( 'Event time', 'lmtttmpts' ); ?></a></th></tr></tfoot>
										<tbody><tr class="alternate"><th class="check-column" scope="row"><input disabled="disabled" type="checkbox"></th><td>127.0.0.1</td><td>localhost</td><td><?php _e( 'Failed attempt', 'lmtttmpts' ); ?></td><td><?php _e( 'Login form', 'lmtttmpts' ); ?></td><td>November 25, 2014 11:55 am</td></tr>
										</tbody>
									</table>
									<div class="tablenav bottom"><div class="alignleft actions bulkactions"><select disabled="disabled"><option><?php _e( 'Delete log entry', 'lmtttmpts' ); ?></option></select><input disabled="disabled" type="submit" value="Apply" class="button action"></div><div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div><br class="clear"></div>
								</form>
							</div>
						</div>
						<div class="bws_pro_version_tooltip">
							<div class="bws_info">
								<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'lmtttmpts' ); ?>
								<a href="http://bestwebsoft.com/products/limit-attempts/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro"> <?php _e( "Learn More", 'lmtttmpts' ); ?></a>
							</div>
							<a class="bws_button" href="http://bestwebsoft.com/products/limit-attempts/buy/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro"> <?php _e( 'Go', 'lmtttmpts' ); ?> <strong>PRO</strong>
							</a>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			<?php } elseif ( 'statistics' == $_GET['action'] ) {
				/* Showing statistics table using wp_list_table class */ 
				if ( isset( $_POST['lmtttmpts_clear_statistics_complete'] ) && check_admin_referer( $plugin_basename, 'lmtttmpts_nonce_name' ) ) { ?>
					<div id="lmtttmpts_clear_statistics_confirm">
						<p><?php _e( 'Are you sure you want to delete all statistics entries?', 'lmtttmpts' ) ?></p>
						<form method="post" action="admin.php?page=limit-attempts.php&amp;action=statistics">
							<button class="button" name="lmtttmpts_clear_statistics_complete_confirm"><?php _e( 'Yes, delete these entries', 'lmtttmpts' ) ?></button>
							<button class="button" name="lmtttmpts_clear_statistics_complete_deny"><?php _e( 'No, go back to the Statistics page', 'lmtttmpts' ) ?></button>
							<?php wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
						</form>
					</div>
				<?php } else { ?>
					<div id="lmtttmpts_statistics">
						<?php $lmtttmpts_statistics_list = new Lmtttmpts_Statistics();
						$lmtttmpts_statistics_list->prepare_items(); ?>
						<form method="get" action="admin.php">
							<?php $lmtttmpts_statistics_list->search_box( __( 'Search IP', 'lmtttmpts' ), 'search_statistics_ip' ); ?>
							<input type="hidden" name="page" value="limit-attempts.php" />
							<input type="hidden" name="action" value="statistics" />
						</form>
						<form method="post" action="admin.php?page=limit-attempts.php&amp;action=statistics">
							<input type="hidden" name="lmtttmpts_clear_statistics_complete" />
							<input type="submit" class="button" value="<?php _e( 'Clear Statistics', 'lmtttmpts' ) ?>" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
						</form>
						<form method="post" action="admin.php?page=limit-attempts.php&amp;action=statistics">
							<?php $lmtttmpts_statistics_list->display(); 
							wp_nonce_field( $plugin_basename, 'lmtttmpts_nonce_name' ); ?>
						</form>
					</div>
				<?php }
			} elseif ( 'go_pro' == $_GET['action'] ) {
				bws_go_pro_tab( $lmtttmpts_plugin_info, $plugin_basename, 'limit-attempts.php', 'limit-attempts-pro.php', 'limit-attempts-pro/limit-attempts-pro.php', 'limit-attempts', 'fdac994c203b41e499a2818c409ff2bc', '140', isset( $go_pro_result['pro_plugin_is_activated'] ) );
			}
			bws_plugin_reviews_block( $lmtttmpts_plugin_info['Name'], 'limit-attempts' ); ?>
		</div>
	<?php } 
}

/**
 * Function to customize error message 
 * and show remaining attempts
 */
if ( ! function_exists( 'lmtttmpts_error_message' ) ) { 
	function lmtttmpts_error_message() {
		global $error, $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( is_multisite() ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		/* current user ip address */
		$ip = lmtttmpts_get_address();
		/* info on failed ip */
		$info_on_ip = $wpdb->get_row(
			"SELECT `failed_attempts`, `block_quantity`, `block_till` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'", ARRAY_A
		);
		$attempts = $info_on_ip['failed_attempts'];
		$blocks = $info_on_ip['block_quantity'];
		$when = $info_on_ip['block_till'];
		/* if failed attempt is not the last allowed */
		if ( ! lmtttmpts_is_ip_blocked( $ip ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && $ip != '' ) {
			if ( ( isset( $_POST['wp-submit'] ) && ! isset( $_GET['loggedout'] ) && isset( $_POST['log'] ) && '' != $_POST['log'] && isset( $_POST['pwd'] ) && '' != $_POST['pwd'] ) || ( ( ( isset( $_POST['pwd'] ) && '' == $_POST['pwd'] ) || ( isset( $_POST['log'] ) && '' == $_POST['log'] ) )  && ( ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! cptch_lmtttmpts_interaction() ) || ( function_exists( 'cptchpls_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $active_plugins ) ) && ! cptchpls_lmtttmpts_interaction() ) || ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! cptchpr_lmtttmpts_interaction() ) ) ) ) {
				$allowed_tries = max( $lmtttmpts_options['allowed_retries'] - $attempts, 0 ); /*calculation of allowed retries*/
				$error = str_replace( '%ATTEMPTS%' , $allowed_tries, $lmtttmpts_options['failed_message'] ); /* Show custom message with remaining attempts */
			} 
		}

		/* if IP is in blacklist */
		if ( ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) || ( ( $attempts > $lmtttmpts_options['allowed_retries']-1 ) && $blocks > $lmtttmpts_options['allowed_locks']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && ( ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! cptch_lmtttmpts_interaction() ) || ( function_exists( 'cptchpls_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $active_plugins ) ) && ! cptchpls_lmtttmpts_interaction() ) || ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! cptchpr_lmtttmpts_interaction() ) ) ) {
			$error =  str_replace( '%MAIL%' , $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
		} elseif ( ( ( lmtttmpts_is_ip_blocked( $ip ) || ( $attempts > $lmtttmpts_options['allowed_retries']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) && ( ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! cptch_lmtttmpts_interaction() ) || ( function_exists( 'cptchpls_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $active_plugins ) ) && ! cptchpls_lmtttmpts_interaction() ) || ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! cptchpr_lmtttmpts_interaction() ) ) ) {
			/* if IP is blocked */
			$error = str_replace( array( '%DATE%', '%MAIL%' ) , array( $when, $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] );
		}

		/* if user with such login doesn't exist */
		if ( isset( $_POST['log'] ) ) {
			$registered_user = get_user_by( 'login', $_POST['log'] );
			if ( ! $registered_user ) {
				if ( ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) || ( ( $attempts > $lmtttmpts_options['allowed_retries']-1 ) && $blocks > $lmtttmpts_options['allowed_locks']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) )
					$error =  str_replace( '%MAIL%' , $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
				if ( ( ( lmtttmpts_is_ip_blocked( $ip ) || ( $attempts > $lmtttmpts_options['allowed_retries']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) ) )
					$error = str_replace( array( '%DATE%', '%MAIL%' ) , array( $when, $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] );
			}
		}
	}
}

/**
 * Function to add/update data into tables 
 * and perform other actions when login was failed
 */
if ( ! function_exists( 'lmtttmpts_login_failed' ) ) {
	function lmtttmpts_login_failed() { 
		/*if user set wrong login and/or password*/
		global $wpdb, $lmtttmpts_options;
		/* get real IP */
		$ip = lmtttmpts_get_address();
		/* if ip is whitelisted, blacklisted, blocked or not identified then nothing to add to statistic */
		if ( ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_blocked( $ip ) && $ip != '' && lmtttmpts_login_form_captcha_checking() ) {
			if ( empty( $lmtttmpts_options ) ) {
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			}

			/* get IP's integer-equivalent & our tables' prefix */
			$ip_int = sprintf( '%u', ip2long( $ip ) );
			$prefix = $wpdb->prefix . 'lmtttmpts_';

			/* add a new row to the table if this is his first wrong attempt */
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'failed_attempts' ) ) { 
				$wpdb->insert( 
					$prefix . 'failed_attempts', 
					array( 
						'ip' 		=> $ip,
						'ip_int' 	=> $ip_int
					 ), 
					'%s'
				);
			}
			/* get number of failed attempts for this IP that was before this failed login */
			$failed_attempts = $wpdb->get_var( 
				"SELECT `failed_attempts` 
				FROM `" . $prefix . "failed_attempts` 
				WHERE `ip_int` = '" . $ip_int . "'" 
			);
			/* countdown to reset failed attempts */
			if ( $failed_attempts == 0 ) { 
				wp_schedule_single_event( time() + $lmtttmpts_options['minutes_to_reset'] * 60 + $lmtttmpts_options['hours_to_reset'] * 3600 + $lmtttmpts_options['days_to_reset'] * 86400, 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );
			}
			/*increment value with failed attempts*/
			$wpdb->update(
				$prefix . 'failed_attempts', 
				array( 'failed_attempts' => $failed_attempts + 1 ),
				array( 'ip_int' => $ip_int ),
				array( '%d' ),
				array( '%s' )
			);

			/* check if this IP is absolutely new for our plugin (log and statistics wise)  and add a new row to the archive table if this is his first wrong attempt*/
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'all_failed_attempts' ) ) { 
				$wpdb->insert( 
					$prefix . 'all_failed_attempts', 
					array( 
						'ip' 		=> $ip,
						'ip_int' 	=> $ip_int
					), 
					'%s'
				);
			}
			$all_failed_attempts = $wpdb->get_var( 
				"SELECT `failed_attempts` 
				FROM `" . $prefix . "all_failed_attempts` 
				WHERE `ip_int` = '" . $ip_int . "'" 
			);
			/*increment value with failed attempts in archive table*/
			$wpdb->update(
				$prefix . 'all_failed_attempts', 
				array( 'failed_attempts' => $all_failed_attempts + 1 ),
				array( 'ip_int' => $ip_int ),
				'%d',
				'%s'
			);

			/* if user exceeded allow retries then reset number of failed attempts, set block to true and set time when block will be reset */
			if ( $failed_attempts + 1 >= $lmtttmpts_options['allowed_retries'] ) {
				/* get current number of blocks for IP */
				$block_quantity = $wpdb->get_var( 
					"SELECT `block_quantity` 
					FROM `" . $prefix . "failed_attempts` 
					WHERE `ip_int` = '" . $ip_int . "'" 
				);
				$block_till = current_time( 'timestamp' ) + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400;
				/* block IP in our table */
				$wpdb->update(
					$prefix . 'failed_attempts',
					array( 
						'block' 			=> true, 
						'failed_attempts' 	=> 0, 
						'block_quantity' 	=> $block_quantity + 1, 
						'block_till' 		=> date( 'Y-m-d H:i:s', $block_till ) ),
					array( 'ip_int' => $ip_int ),
					array( '%s', '%d', '%s', '%s' ),
					'%s'
				);
				/* clear hook for reset the number of failed attempts */
				wp_clear_scheduled_hook( 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );
				/* countdown and event to reset block */
				if ( ! wp_next_scheduled( 'lmtttmpts_event_for_reset_block' ) ) {
					$new_timestamp = time() + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400;
					wp_schedule_single_event( $new_timestamp, 'lmtttmpts_event_for_reset_block' );
				}				
				/* interaction with Htaccess plugin for blocking */
				if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					/* hook for blocking by Htaccess */
					do_action( 'lmtttmpts_htaccess_hook_for_block', $ip );
				}

				/* if this first block (maybe after reset) then they will be reset after some time */
				if ( $block_quantity == 0 ) {
					wp_schedule_single_event( time() + $lmtttmpts_options['minutes_to_reset_block'] * 60 + $lmtttmpts_options['hours_to_reset_block'] * 3600 + $lmtttmpts_options['days_to_reset_block'] * 86400 , 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
				}
				/* get number of blocks in statistics table */
				$all_block_quantity = $wpdb->get_var( 
					"SELECT `block_quantity` 
					FROM `" . $prefix . "all_failed_attempts` 
					WHERE `ip_int` = '" . $ip_int . "'" 
				);
				/*update statistic*/
				$wpdb->update(
					$prefix . 'all_failed_attempts',
					array( 'block_quantity' => $all_block_quantity + 1 ),
					array( 'ip_int' => $ip_int ),
					'%d',
					'%s'
				);
				/* if user exceed number of allowed locks per some period, his IP will be added to blacklist */
				if ( $block_quantity + 1 >= $lmtttmpts_options['allowed_locks'] ) { 
					/* set block to 'false', set numbers of blocks & failed attempts to zero and add IP to blacklist */
					$wpdb->update(
						$prefix . 'failed_attempts',
						array( 
							'block' 			=> false, 
							'failed_attempts' 	=> 0, 
							'block_quantity' 	=> 0 
						),
						array( 'ip_int' => $ip_int ),
						array( '%s', '%d', '%d' ),
						'%s'
					);
					/* delete hook for reset the number of blocks */
					wp_clear_scheduled_hook( 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
					/* adding address to blacklist table */
					$wpdb->insert(
						$prefix . 'blacklist', 
						array( 
							'ip' 			=> $ip, 
							'ip_from_int' 	=> $ip_int,
							'ip_to_int' 	=> $ip_int,
						), 
						'%s'
					);
					if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
						/* hook for blocking by Htaccess */
						do_action( 'lmtttmpts_htaccess_hook_for_block', $ip );
					}
				}
				/* send mail to admin if this option was set in admin page */
				if ( $lmtttmpts_options['notify_email'] ) {
					/* get subject ant text for email message if IP is blocked*/
					if ( $block_quantity + 1 >= $lmtttmpts_options['allowed_locks'] ) {
						/* if IP was added to blacklist */
						$lmtttmpts_subject = str_replace( array( '%IP%', '%SITE_NAME%' ), array( $ip, get_bloginfo( 'name' ) ), $lmtttmpts_options['email_subject_blacklisted'] );
						$lmtttmpts_message = str_replace( array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%' ), array( $ip, esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ), $lmtttmpts_options['email_blacklisted'] );
					} else {
						/* if IP was just blocked */
						$lmtttmpts_subject = str_replace( array( '%IP%', '%SITE_NAME%' ) , array( $ip, get_bloginfo( 'name' ) ), $lmtttmpts_options['email_subject'] );
						$lmtttmpts_message = str_replace( array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%' ), array( $ip, esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ), $lmtttmpts_options['email_blocked'] );
					}

					add_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
					wp_mail( $lmtttmpts_options['email_address'], $lmtttmpts_subject, $lmtttmpts_message );
					remove_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
				}
			}
		}
	}
}

/**
 * Filter for authenticate access
 */
if ( ! function_exists( 'lmtttmpts_authenticate_user' ) ) {
	function lmtttmpts_authenticate_user( $user, $password ) {
		global $wpdb, $lmtttmpts_options;
		if ( empty( $lmtttmpts_options ) ) {
			if ( ! get_option( 'lmtttmpts_options' ) )
				register_lmtttmpts_settings(); 
			else
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		$prefix = $wpdb->prefix . 'lmtttmpts_';

		/* current user ip address */
		$ip = lmtttmpts_get_address();
		/* info on failed ip */
		$info_on_ip = $wpdb->get_row(
			"SELECT `failed_attempts`, `block_quantity`, `block_till` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'", ARRAY_A
		);
		$attempts = $info_on_ip['failed_attempts'] + 1;
		$blocks = $info_on_ip['block_quantity'] + 1;

		$registered_user = get_user_by( 'login', $_POST['log'] );
		$error = new WP_Error();
		/*return error if address blacklisted */
		if ( ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) || ( $attempts >= $lmtttmpts_options['allowed_retries'] && $blocks >= $lmtttmpts_options['allowed_locks'] && ( ! $registered_user || !wp_check_password( $password, $user->user_pass, $user->ID) ) ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			$error->add( 'lmtttmpts_blacklisted', str_replace( '%MAIL%', $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] ) );
			return $error;  
		}
		/* return error if address blocked */
		if ( ( lmtttmpts_is_ip_blocked( $ip ) || ( $attempts >= $lmtttmpts_options['allowed_retries'] && ( !$registered_user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			$when = $info_on_ip['block_till'];
			if ( ! $when || ( strtotime( $when ) < current_time( 'timestamp' ) ) ) {
				$block_till = current_time( 'timestamp' ) + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400;
				$when = date( 'Y-m-d H:i:s', $block_till );
			}
			$error->add( 'lmtttmpts_blocked', str_replace( array( '%DATE%', '%MAIL%' ), array( $when, $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] ) ) ;
			return $error;
		}
		if ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			$error->add( 'lmtttmpts_blacklisted', str_replace( '%MAIL%', $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] ) );
			return $error;
		}
		if ( is_wp_error( $user ) || ( ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_blocked( $ip ) && ( $attempts <= $lmtttmpts_options['allowed_retries'] ) ) || lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			return $user;
		}
		return $error;
	}
}

/**
 * Add notises on plugins page
 */
if ( ! function_exists( 'lmtttmpts_show_notices' ) ) {
	function lmtttmpts_show_notices() {
		global $lmtttmpts_options, $hook_suffix;
		lmtttmpts_plugin_banner();
		/* if limit-login-attempts is also installed */
		if ( 'plugins.php' == $hook_suffix && is_plugin_active( 'limit-login-attempts/limit-login-attempts.php' ) ) {
			echo '<div class="error"><p><strong>' . __( 'Notice:', 'lmtttmpts' ) . '</strong> ' . __( "Limit Login Attempts plugin is activated on your site, as well as Limit Attempts plugin. Please note that Limit Attempts ensures maximum security when no similar plugins are activated. Using other plugins that limit user's login attempts at the same time may lead to undesirable behaviour on your WP site.", 'lmtttmpts' ) . '</p></div>';
		}
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		/* Need to update Htaccess */
		/* if option 'htaccess_notice' is not empty and we are on the 'right' page */
		if ( ! empty( $lmtttmpts_options['htaccess_notice'] ) && ( $hook_suffix == 'plugins.php' || $hook_suffix == 'update-core.php' || ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], array( 'limit-attempts.php', 'htaccess.php' ) ) ) ) ) {
			/* Save data for settings page */
			if ( isset( $_REQUEST['lmtttmpts_htaccess_notice_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'lmtttmpts_htaccess_notice_nonce_name' ) ) {
				$lmtttmpts_options['htaccess_notice'] = '';
				update_option( 'lmtttmpts_options', $lmtttmpts_options );
			} else { 
				/* get action_slug */
				$action_slug = ( $hook_suffix == 'plugins.php' || $hook_suffix == 'update-core.php' ) ? $hook_suffix : 'admin.php?page=' . $_REQUEST['page']; ?>
				<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
					<div class="bws_banner_on_plugin_page">
						<form method="post" action="<?php echo $action_slug; ?>">
							<div class="text" style="max-width: 100%;">
								<p>
									<strong><?php _e( "ATTENTION!", 'lmtttmpts' ); ?> </strong>
									<?php echo $lmtttmpts_options['htaccess_notice']; ?>&nbsp;&nbsp;&nbsp;
									<input type="hidden" name="lmtttmpts_htaccess_notice_submit" value="submit" />
									<input type="submit" class="button-primary" value="<?php _e( 'Read and Understood', 'lmtttmpts' ); ?>" />
								</p>
								<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_htaccess_notice_nonce_name' ); ?>
							</div>
						</form>
					</div>
				</div>
			<?php }
		}

		/* if buttons were pressed on "Settings" tab */
		if ( isset( $_POST['lmtttmpts_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'lmtttmpts_nonce_name' ) ) {
			/* if button "Save changes" was pressed */
			if ( isset( $_POST['lmtttmpts_form_submit_button'] ) ) {
				$notice_messages = array(); 
				/* Show notices for Settings form */
				if ( isset( $_POST['lmtttmpts_allowed_retries'] ) ) { 
					/* Show notices for wrong allowed retries input */
					if ( ! is_numeric( $_POST['lmtttmpts_allowed_retries'] ) ) {
						$notice_messages[] = __( 'Allowed retries must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_allowed_retries'] < 1 ) {
						$notice_messages[] = __( 'Allowed retries must be more than zero, it was automatically changed to the previous value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_days_of_lock'] ) ) { 
					/* Show notices for wrong days of lock input */
					if ( ! is_numeric( $_POST['lmtttmpts_days_of_lock'] ) ) {
						$notice_messages[] = __( 'Days of lock must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_days_of_lock'] < 0 ) {
						$notice_messages[] = __( 'Days of lock can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					}
				} 
				if ( isset( $_POST['lmtttmpts_hours_of_lock'] ) ) { 
					/* Show notices for wrong hours of lock input */
					if ( ! is_numeric( $_POST['lmtttmpts_hours_of_lock'] ) ) {
						$notice_messages[] = __( 'Hours of lock must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_hours_of_lock'] < 0 ) {
						$notice_messages[] = __( 'Hours of lock can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_hours_of_lock'] > 23 ) {
						$notice_messages[] = __( 'Hours of lock can&rsquo;t be more than 23, it was automatically changed to the max value', 'lmtttmpts' );
					}
				} 
				if ( isset( $_POST['lmtttmpts_minutes_of_lock'] ) ) { 
					/* Show notices for wrong minutes of lock input */
					if ( ! is_numeric( $_POST['lmtttmpts_minutes_of_lock'] ) ) {
						$notice_messages[] = __( 'Minutes of lock must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_minutes_of_lock'] < 0 ) {
						$notice_messages[] = __( 'Minutes of lock can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_minutes_of_lock'] > 59 ) {
						$notice_messages[] = __( 'Minutes of lock can&rsquo;t be more than 59, it was automatically changed to the max value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_days_to_reset'] ) ) {
					/* Show notices for wrong days to reset input */
					if ( ! is_numeric( $_POST['lmtttmpts_days_to_reset'] ) ) {
						$notice_messages[] = __( 'Days to reset the number of tries must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_days_to_reset'] < 0 ) {
						$notice_messages[] = __( 'Days to reset the number of tries can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_hours_to_reset'] ) ) {
					/* Show notices for wrong hours to reset input */
					if ( ! is_numeric( $_POST['lmtttmpts_hours_to_reset'] ) ) {
						$notice_messages[] = __( 'Hours to reset the number of tries must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_hours_to_reset'] < 0 ) {
						$notice_messages[] = __( 'Hours to reset the number of tries can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_hours_to_reset'] > 23 ) {
						$notice_messages[] = __( 'Hours to reset the number of tries can&rsquo;t be more than 23, it was automatically changed to the max value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_minutes_to_reset'] ) ) {
					/* Show notices for wrong minutes to reset input */
					if ( ! is_numeric( $_POST['lmtttmpts_minutes_to_reset'] ) ) {
						$notice_messages[] = __( 'Minutes to reset the number of tries must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_minutes_to_reset'] < 0 ) {
						$notice_messages[] = __( 'Minutes to reset the number of tries can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_minutes_to_reset'] > 59 ) {
						$notice_messages[] = __( 'Minutes to reset the number of tries can&rsquo;t be more than 59, it was automatically changed to the max value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_allowed_locks'] ) ) { 
					/* Show notices for wrong allowed locks before add to blacklist input*/
					if ( ! is_numeric( $_POST['lmtttmpts_allowed_locks'] ) ) {
						$notice_messages[] = __( 'Allowed blocks must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_allowed_locks'] < 1 ) {
						$notice_messages[] = __( 'Allowed blocks must be more than zero, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_allowed_locks'] == 1 ) {
						$notice_messages[] = __( 'Add to the blacklist option is set on after 1 blocking, this means that address will get into the blacklist right away, instead of blocking for some time.', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_days_to_reset_block'] ) ) { 
					/* Show notices for wrong days to reset number of locks input */
					if ( ! is_numeric( $_POST['lmtttmpts_days_to_reset_block'] ) ) {
						$notice_messages[] = __( 'Days to reset the number of locks must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_days_to_reset_block'] < 0 ) {
						$notice_messages[] = __( 'Days to reset the number of locks can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_hours_to_reset_block'] ) ) {
					/* Show notices for wrong hours to reset number of locks input */
					if ( ! is_numeric( $_POST['lmtttmpts_hours_to_reset_block'] ) ) {
						$notice_messages[] = __( 'Hours to reset the number of locks must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_hours_to_reset_block'] < 0 ) {
						$notice_messages[] = __( 'Hours to reset the number of locks can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_hours_to_reset_block'] > 23 ) {
						$notice_messages[] = __( 'Hours to reset the number of locks can&rsquo;t be more than 23, it was automatically changed to the max value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_minutes_to_reset_block'] ) ) { 
					/* Show notices for wrong minutes to reset number of locks input */
					if ( ! is_numeric( $_POST['lmtttmpts_minutes_to_reset_block'] ) ) {
						$notice_messages[] = __( 'Minutes to reset the number of locks must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_minutes_to_reset_block'] < 0 ) {
						$notice_messages[] = __( 'Minutes to reset the number of locks can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_minutes_to_reset_block'] > 59 ) {
						$notice_messages[] = __( 'Minutes to reset the number of locks can&rsquo;t be more than 59, it was automatically changed to the max value', 'lmtttmpts' );
					}
				} 
				if ( isset( $_POST['lmtttmpts_days_to_clear_statistics'] ) ) {
					if ( ! is_numeric( $_POST['lmtttmpts_days_to_clear_statistics'] ) ) {
						$notice_messages[] = __( 'Days to clear statistics must be numeric, it was automatically changed to the previous value', 'lmtttmpts' );
					} elseif ( $_POST['lmtttmpts_days_to_clear_statistics'] < 0 ) {
						$notice_messages[] = __( 'Days to clear statistics can&rsquo;t be negative, it was automatically changed to the previous value', 'lmtttmpts' );
					}
				}
				if ( isset( $_POST['lmtttmpts_notify_email'] ) && isset( $_POST['lmtttmpts_mailto'] ) && 'custom' == $_POST['lmtttmpts_mailto'] && isset( $_POST['lmtttmpts_email_address'] ) && ! is_email( $_POST['lmtttmpts_email_address'] ) ) { 
					/* Show notices for wrong email input */
					$notice_messages[] = __( 'Wrong email, it was automatically changed to the previous value', 'lmtttmpts' );
				}
				if ( ( $_POST['lmtttmpts_days_of_lock'] == 0 ) && ( $_POST['lmtttmpts_hours_of_lock'] == 0 ) && ( $_POST['lmtttmpts_minutes_of_lock'] == 0 ) ) { 
					/* Show notices when time of lock is less than 1 minute */
					$notice_messages[] = __( 'Time of lock can&rsquo;t be less than 1 minute, it was automatically changed to the min value', 'lmtttmpts' );
				}
				if ( ( $_POST['lmtttmpts_days_to_reset'] == 0 ) && ( $_POST['lmtttmpts_hours_to_reset'] == 0 ) && ( $_POST['lmtttmpts_minutes_to_reset'] == 0 ) ) {
					/* Show notices when time to reset block is less than 1 minute */
					$notice_messages[] = __( 'Time to reset block can&rsquo;t be less than 1 minute, it was automatically changed to the min value', 'lmtttmpts' );
				}
				if ( ( $_POST['lmtttmpts_days_to_reset_block'] == 0 ) && ( $_POST['lmtttmpts_hours_to_reset_block'] == 0 ) && ( $_POST['lmtttmpts_minutes_to_reset_block'] == 0 ) ) {
					/* Show notices when time to reset number of locks is less than 1 minute */
					$notice_messages[] = __( 'Time to reset the number of locks can&rsquo;t be less than 1 minute, it was automatically changed to the min value', 'lmtttmpts' );
				}
				/* display notices if exists */
				$output_message = '<div class="updated fade bellow-h2">';
				if ( ! empty( $notice_messages ) ) {
					/* loop through amd dispay every notice message */
					foreach ( $notice_messages as $notice_message ) {
						$output_message .= '<p><strong>' . __( 'Notice:', 'lmtttmpts' ) . '</strong> ' . $notice_message . ' </p>';
					}
				}
				$output_message .= '<p><strong>' . __( 'All changes have been saved', 'lmtttmpts' ) . '</strong></p></div>';
				echo $output_message;
			} elseif ( isset( $_POST['lmtttmpts_return_default'] ) ) {
				/* if button "Return default" was pressed */
				$output_message = '<div class="updated fade"><p><strong>' . __( 'Notice:', 'lmtttmpts' ) . '</strong> ';
				if ( 'email_subject' == $_POST['lmtttmpts_return_default'] || 'email_subject_blacklisted' == $_POST['lmtttmpts_return_default'] ) {
					$output_message .= __( 'Subject has been restored to default', 'lmtttmpts' );
				} else {
					$output_message .= __( "Message has been restored to default", 'lmtttmpts' );
				}
				$output_message .= '</p><p><strong>' . __( 'Changes are not saved', 'lmtttmpts' ) . '</strong></p></div>';
				echo $output_message;
			}
		} elseif ( isset( $_GET['page'] ) && 'limit-attempts.php' == $_GET['page'] && isset( $_GET['action'] ) && isset( $_GET['s'] ) ) {
			/* if on non-'settings' tab */
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])?(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])?){0,3}?$/', str_replace( " ", "", trim( $_GET['s'] ) ) ) ) { 
				$output_message = '<div class="updated fade"><p>';
				if ( 'blocked' == $_GET['action'] || 'statistics' == $_GET['action'] ) {
					$output_message .= __( 'Search results for', 'lmtttmpts' ) . '&nbsp;' . str_replace( " ", "", trim( $_GET['s'] ) );
				} else {
					$output_message .= str_replace( " ", "", trim( $_GET['s'] ) ) . '&nbsp;' . __( 'is in the following entries', 'lmtttmpts' );
				}
				$output_message .= '</p></div>';
			} else {
				$output_message = '<div class="error"><p><strong>' . __( 'ERROR:', 'lmtttmpts' ) . '</strong> ' . __( 'Wrong format or it does not lie in diapason 0.0.0.0 - 255.255.255.255.', 'lmtttmpts' ) . '</p></div>';
			}
			echo $output_message;
		}
	}
}

/**
 * Function to handle actions from "settings" page (tabs other than 'settings')
 * @return array with messages about action results
 */
if ( ! function_exists( 'lmtttmpts_list_actions' ) ) {
	function lmtttmpts_list_actions() {
		global $wpdb, $lmtttmpts_options;
		$table_lists = $wpdb->get_results( "SHOW TABLES FROM `" . DB_NAME . "`", ARRAY_N );
		$table_list = array();
		foreach ( $table_lists as $value ) {
			$table_list[] = $value[0];
		}
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$action_message = array(
			'error' 	=> false,
			'done'  	=> false
		);
		/* if we on 'blocked', 'blacklist', 'whitelist' or 'statistics' tab */
		if ( isset( $_REQUEST['page'] ) && ( 'limit-attempts.php' == $_REQUEST['page'] ) && isset( $_GET['action'] ) && ! in_array( $_GET['action'] , array( 'faq', 'log' ) ) ) {
			/* counter variables */
			$error = $done = '';
			$message_list = array(
				/* general */
				'notice'						=> __( 'Notice:', 'lmtttmpts' ),
				'error'							=> __( 'ERROR:', 'lmtttmpts' ),
				'empty_ip_list'					=> __( 'No address has been selected', 'lmtttmpts' ),
				'empty_ip_textarea'				=> __( 'You must type IP address', 'lmtttmpts' ),
				'wrong_ip_format_error'			=> __( 'Wrong format or it does not lie in diapason 0.0.0.0 - 255.255.255.255.', 'lmtttmpts' ),
				/* blocked tab */
				'block_reset_done'				=> __( 'Block has been reset for', 'lmtttmpts' ),
				'block_reset_error'				=> __( 'Error while reseting block for', 'lmtttmpts' ),
				/* blacklisted tab */
				'blacklist_add_done'			=> __( 'has been added to blacklist', 'lmtttmpts' ),
				'blacklist_add_error'			=> __( 'can&rsquo;t be added to blacklist.', 'lmtttmpts' ),
				'ip_already_in_blacklist'		=> __( 'This IP address has already been added to blacklist', 'lmtttmpts' ),
				'ip_also_in_whitelist'			=> __( 'This IP address is in whitelist too, please check this to avoid errors', 'lmtttmpts' ),
				'blacklisted_delete_done'		=> __( 'has been deleted from blacklist', 'lmtttmpts' ),
				'blacklisted_delete_done_many'	=> __( 'have been deleted from blacklist', 'lmtttmpts' ),
				'blacklisted_delete_error'		=> __( 'Error while deleting from blacklist', 'lmtttmpts' ),
				/* whitelist */
				'whitelist_add_done'			=> __( 'has been added to whitelist', 'lmtttmpts' ),
				'whitelist_add_error'			=> __( 'can&rsquo;t be added to whitelist.', 'lmtttmpts' ),
				'ip_already_in_whitelist'		=> __( 'This IP address has already been added to whitelist', 'lmtttmpts' ),
				'ip_also_in_blacklist'			=> __( 'This IP address is in blacklist too, please check this to avoid errors', 'lmtttmpts' ),
				'whitelisted_delete_done'		=> __( 'has been deleted from whitelist', 'lmtttmpts' ),
				'whitelisted_delete_done_many'	=> __( 'have been deleted from whitelist', 'lmtttmpts' ),
				'whitelisted_delete_error'		=> __( 'Error while deleting from whitelist', 'lmtttmpts' ),
				/* statistics */
				'clear_stats_complete_done'		=> __( 'Statistics has been cleared completely', 'lmtttmpts' ),
				'stats_already_empty'			=> __( 'Statistics is already empty', 'lmtttmpts' ),
				'clear_stats_complete_error'	=> __( 'Error while clearing statistics completely', 'lmtttmpts' ),
				'clear_stats_for_ips_done'		=> __( 'Selected statistics entry (entries) has been deleted', 'lmtttmpts' ),
				'clear_stats_for_ips_error'		=> __( 'Error while deleting statistics entry (entries)', 'lmtttmpts' ),
				'database error'				=> __( 'Error when creating the database. For the plugins correct work, please, deactivate and activate it again', 'lmtttmpts' )
			);

			/* actions on 'blocked' tab */
			if ( 'blocked' == $_GET['action'] ) {
				if ( ! in_array( $prefix . 'failed_attempts', $table_list ) )
					$action_message['error'] = $message_list['database error'];

				/* Realization action in table with blocked addresses */
				if ( isset( $_GET['lmtttmpts_reset_block'] ) && check_admin_referer( 'lmtttmpts_reset_block_' . $_GET['lmtttmpts_reset_block'], 'lmtttmpts_nonce_name' ) ) {
					/* single IP de-block */
					$result_reset_block = $wpdb->update(
						$wpdb->prefix . 'lmtttmpts_failed_attempts',
						array( 'block' => false ),
						array( 'ip_int' => sprintf( '%u', ip2long( $_GET['lmtttmpts_reset_block'] ) ) ),
						array( '%s' ),
						array( '%s' )
					);

					if ( false !== $result_reset_block ) {
						/* if operation with DB was succesful */
						$action_message['done'] = $message_list['block_reset_done'] . '&nbsp;' . $_GET['lmtttmpts_reset_block'];

						if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
							do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $_GET['lmtttmpts_reset_block'] ); /* hook for deblocking by Htaccess */
						}
					} else {
						/* if error */
						$action_message['error'] = $message_list['block_reset_error'] . '&nbsp;' . $_GET['lmtttmpts_reset_block'];
					}
				} elseif ( ( ( isset( $_POST['action'] ) && $_POST['action'] == 'reset_blocks' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'reset_blocks' ) ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
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
						
						if ( isset( $lmtttmpts_options['block_by_htaccess'] ) && ! empty( $done_reset_block ) ) {
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
			} elseif ( 'blacklist' == $_GET['action'] ) {
				if ( ! in_array( $prefix . 'blacklist', $table_list ) )
					$action_message['error'] = $message_list['database error'];

				/* Realization of adding to blacklist */
				if ( isset( $_POST['lmtttmpts_add_to_blacklist'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
					/* IP to add to blacklist */
					$add_to_blacklist_ip = str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] );
					if ( '' == $add_to_blacklist_ip ) {
						/* empty IP */
						$action_message['error'] = $message_list['error'] . '&nbsp;' . $message_list['empty_ip_textarea'];
					} else {
						if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $add_to_blacklist_ip ) ) {
							/* add to blacklist */
							if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'blacklist' ) ) {
								/* if already in blacklist - do nothing */
								$action_message['done'] .= $message_list['notice'] . '&nbsp;' . $message_list['ip_already_in_blacklist'] . ' - ' . $add_to_blacklist_ip;
							} else {
								if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'whitelist' ) ) {
									/* if the same in whitelist - add to blacklist, but show notice */
									$action_message['done'] .= $message_list['notice'] . '&nbsp;' . $message_list['ip_also_in_whitelist'] . ' - ' . $add_to_blacklist_ip;
								}
								/* finaly add to blacklist */
								if ( false !== lmtttmpts_add_ip_to_blacklist( $add_to_blacklist_ip ) ) {
									/* if success */
									if ( ! empty( $action_message['done'] ) ) {
										$action_message['done'] .= '<br />';
									}
									$action_message['done'] .= $add_to_blacklist_ip . '&nbsp;' . $message_list['blacklist_add_done'];
									/* hook for blocking by Htaccess */
									if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
										do_action( 'lmtttmpts_htaccess_hook_for_block', $add_to_blacklist_ip );
									}
								} else {
									/* if error */
									if ( ! empty( $action_message['error'] ) ) {
										$action_message['error'] .= '<br />';
									}
									$action_message['error'] .= $add_to_blacklist_ip . '&nbsp;' . $message_list['blacklist_add_error'];
								}
							}
						} else {
							/* wrong IP format */
							$action_message['error'] .= $message_list['wrong_ip_format_error'] . '<br />' . stripslashes( esc_html( $add_to_blacklist_ip ) ) . '&nbsp;' . $message_list['blacklist_add_error'];
						}
					}
				} elseif ( isset( $_GET['lmtttmpts_remove_from_blacklist'] ) && check_admin_referer( 'lmtttmpts_remove_from_blacklist_' . $_GET['lmtttmpts_remove_from_blacklist'], 'lmtttmpts_nonce_name' ) ) { 
					/* single IP de-blacklisted */
					$result_delete_ip_from_blacklist = $wpdb->delete(
						$wpdb->prefix . 'lmtttmpts_blacklist',
						array( 'ip' => $_GET['lmtttmpts_remove_from_blacklist'] ),
						array( '%s' )
					);
					if ( false !== $result_delete_ip_from_blacklist ) {
						/* if operation with DB was succesful */
						$action_message['done'] = $_GET['lmtttmpts_remove_from_blacklist'] . '&nbsp;' . $message_list['blacklisted_delete_done'];
						/* hook for deblocking by Htaccess */
						if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
							do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $_GET['lmtttmpts_remove_from_blacklist'] );
						}
					} else {
						/* if error */
						$action_message['error'] = $_GET['lmtttmpts_remove_from_blacklist'] . '&nbsp;-&nbsp;' . $message_list['blacklisted_delete_error'];
					}
				} elseif ( ( ( isset( $_POST['action'] ) && $_POST['action'] == 'remove_from_blacklist_ips' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_blacklist_ips' ) ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
					/* Realization of bulk delete in blacklist table */
					/* if we have IPs to delete */
					if ( isset( $_POST['ip'] ) ) {
						/* array for loop */
						$ips = $_POST['ip'];
						foreach ( $ips as $ip ) {
							$result_delete_ip_from_blacklist = $wpdb->delete(
								$wpdb->prefix . 'lmtttmpts_blacklist',
								array( 'ip' => $ip ),
								array( '%s' )
							);
							if ( false !== $result_delete_ip_from_blacklist ) {
								/* if success */
								$done .= empty( $done ) ? $ip : ', ' . $ip;
								$done_ips[] = $ip;
							} else {
								/* if error */
								$error .= empty( $error ) ? $ip : ', ' . $ip;
							}
						}
						if ( isset( $lmtttmpts_options['block_by_htaccess'] ) && ! empty( $done_ips ) ) {
							/* hook for deblocking by Htaccess */
							do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $done_ips );
						}
						if ( ! empty( $done ) ) {
							/* if some IPs were de-blacklisted */
							$action_message['done'] = $done . '&nbsp;' . $message_list['blacklisted_delete_done'];
							$action_message['done'] = $done . '&nbsp;' . ( false === strstr( $done, ', ' ) ? $message_list['blacklisted_delete_done'] : $message_list['blacklisted_delete_done_many'] );
						}
						if ( ! empty( $error ) ) {
							/* if some IPs were not de-blacklisted because of error in DB */
							$action_message['error'] = $error . '&nbsp;-&nbsp;' . $message_list['blacklisted_delete_error'];
						}
					} else {
						/* if empty IP list */
						$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
					}
				}
			} elseif ( 'whitelist' == $_GET['action'] ) {
				if ( ! in_array( $prefix . 'whitelist', $table_list ) )
					$action_message['error'] = $message_list['database error'];

				/* Realization of action in whitelist table */
				if ( isset( $_POST['lmtttmpts_add_to_whitelist'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
					/* Realization of adding IP to whitelist */
					$add_to_whitelist_ip = str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] );
					if ( '' == $add_to_whitelist_ip ) {
						/* empty IP */
						$action_message['error'] = $message_list['error'] . '&nbsp;' . $message_list['empty_ip_textarea'];
					} else {
						if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $add_to_whitelist_ip ) ) {
							/* add to whitelist */
							if ( lmtttmpts_is_ip_in_table( $add_to_whitelist_ip, 'whitelist' ) ) {
								/* if already in whitelist - do nothing */
								$action_message['done'] .= $message_list['notice'] . '&nbsp;' . $message_list['ip_already_in_whitelist'] . ' - ' . $add_to_whitelist_ip;
							} else {
								if ( lmtttmpts_is_ip_in_table( $add_to_whitelist_ip, 'blacklist' ) ) {
									/* if the same in blacklist - add to whitelist, but show notice */
									$action_message['done'] .= $message_list['notice'] . '&nbsp;' . $message_list['ip_also_in_blacklist'] . ' - ' . $add_to_whitelist_ip;
								}
								/* finaly add to whitelist */
								if ( false !== lmtttmpts_add_ip_to_whitelist( $add_to_whitelist_ip ) ) {
									/* if success */
									if ( ! empty( $action_message['done'] ) ) {
										$action_message['done'] .= '<br />';
									}
									$action_message['done'] .= $add_to_whitelist_ip . '&nbsp;' . $message_list['whitelist_add_done'];
									/* hook for adding to "allow" by Htaccess */
									if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
										do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $add_to_whitelist_ip );
									}
								} else {
									/* if error */
									if ( ! empty( $action_message['error'] ) ) {
										$action_message['error'] .= '<br />';
									}
									$action_message['error'] .= $add_to_whitelist_ip . '&nbsp;' . $message_list['whitelist_add_error'];
								}
							}
						} else {
							/* wrong IP format */
							$action_message['error'] .= $message_list['wrong_ip_format_error'] . '<br />' . stripslashes( esc_html( $add_to_whitelist_ip ) ) . '&nbsp;' . $message_list['whitelist_add_error'];
						}
					}
				} elseif ( isset( $_GET['lmtttmpts_remove_from_whitelist'] ) && check_admin_referer( 'lmtttmpts_remove_from_whitelist_' . $_GET['lmtttmpts_remove_from_whitelist'], 'lmtttmpts_nonce_name' ) ) {
					/* single IP delete from whitelist */
					$result_delete_ip_from_whitelist = $wpdb->delete(
						$wpdb->prefix . 'lmtttmpts_whitelist',
						array( 'ip' => $_GET['lmtttmpts_remove_from_whitelist'] ),
						array( '%s' )
					);
					if ( false !== $result_delete_ip_from_whitelist ) {
						/* if operation with DB was succesful */
						$action_message['done'] = $_GET['lmtttmpts_remove_from_whitelist'] . '&nbsp;' . $message_list['whitelisted_delete_done'];
						/* hook for deleting from whitelist by Htaccess */
						if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
							do_action( 'lmtttmpts_htaccess_hook_for_delete_from_whitelist', $_GET['lmtttmpts_remove_from_whitelist'] );
						}
					} else {
						/* if error */
						$action_message['error'] = $_GET['lmtttmpts_remove_from_whitelist'] . '&nbsp;-&nbsp;' . $message_list['whitelisted_delete_error'];
					}
				} elseif ( ( ( isset( $_POST['action'] ) && $_POST['action'] == 'remove_from_whitelist_ips' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_whitelist_ips' ) ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
					/* Realization bulk action in whitelist table */
					/* if we have IPs to delete */
					if ( isset( $_POST['ip'] ) ) {
						/* array for loop */
						$ips = $_POST['ip'];
						foreach ( $ips as $ip ) {
							$result_delete_ip_from_whitelist = $wpdb->delete(
								$wpdb->prefix . 'lmtttmpts_whitelist',
								array( 'ip' => $ip ),
								array( '%s' )
							);							
							if ( false !== $result_delete_ip_from_whitelist ) {
								/* if success */
								$done .= empty( $done ) ? $ip : ', ' . $ip;
								$done_ips[] = $ip;
							} else {
								/* if error */
								$error .= empty( $error ) ? $ip : ', ' . $ip;
							}
						}
						/* hook for deleting from whitelist by Htaccess */
						if ( isset( $lmtttmpts_options['block_by_htaccess'] ) && ! empty( $done_ips ) ) {
							do_action( 'lmtttmpts_htaccess_hook_for_delete_from_whitelist', $done_ips );
						}

						if ( ! empty( $done ) ) {
							/* if some IPs were de-whitelisted */
							$action_message['done'] = $done . '&nbsp;' . ( false === strstr( $done, ', ' ) ? $message_list['whitelisted_delete_done'] : $message_list['whitelisted_delete_done_many'] );
						}
						if ( ! empty( $error ) ) {
							/* if some IPs were not de-whitelisted because of error in DB */
							$action_message['error'] = $error . '&nbsp;-&nbsp;' . $message_list['whitelisted_delete_error'];
						}
					} else {
						/* if empty IP list */
						$action_message['done'] = $message_list['notice'] . '&nbsp;' . $message_list['empty_ip_list'];
					}
				}
			} elseif ( 'statistics' == $_GET['action'] ) {
				if ( ! in_array( $prefix . 'all_failed_attempts', $table_list ) )
					$action_message['error'] = $message_list['database error'];

				/* Clear Statistics */
				if ( isset( $_POST['lmtttmpts_clear_statistics_complete_confirm'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
					/* if clear completely */
					$result = lmtttmpts_clear_statistics_completely();
					if ( false === $result ) {
						/* if error */
						$action_message['error'] = $message_list['clear_stats_complete_error'];
					} elseif ( 0 === $result ) {
						/* if empty */
						$action_message['done'] = $message_list['notice'] . ' ' . $message_list['stats_already_empty'];
					} else {
						/* if success */
						$action_message['done'] = $message_list['clear_stats_complete_done'];
					}
				} elseif ( ( ( isset( $_POST['action'] ) && $_POST['action'] == 'clear_statistics_for_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'clear_statistics_for_ips' ) ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
					/* Clear some entries */
					if ( isset( $_POST['ip'] ) ) {
						/* if statistics entries exist */
						$ips = $_POST['ip'];
						$error = $done = 0;
						foreach ( $ips as $ip ) {
							if ( false === lmtttmpts_clear_statistics( $ip ) ) {
								$error++;
							} else {
								$done++;
							}
						}
						if ( 0 < $error ) {
							$action_message['error'] = $message_list['clear_stats_for_ips_error'] . '. ' . __( 'Total', 'lmtttmpts') . ': ' . $error . ' ' . _n( 'entry', 'entries', $error, 'lmtttmpts' );
						}
						if ( 0 < $done ) {
							$action_message['done'] = $message_list['clear_stats_for_ips_done'] . '. ' . __( 'Total', 'lmtttmpts') . ': ' . $done . ' ' . _n( 'entry', 'entries', $done, 'lmtttmpts' );
						}
					} else {
						/* if no entries selected */
						$action_message['done'] = $message_list['notice'] . ' ' . $message_list['empty_ip_list'];
					}
				}
			}
		}
		return $action_message;
	}
}

if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ) ) {

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	/**
	 * Create new class for displaying list with blocked ips
	 */
	class Lmtttmpts_Blocked_list extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'ip'			=> __( 'Ip address', 'lmtttmpts' ),
				'block_till'	=> __( 'The lock expires', 'lmtttmpts' )
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
				'reset_block'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&action=%s&lmtttmpts_reset_block=%s', $_GET['page'], $_GET['action'], $item['ip'] ), 'lmtttmpts_reset_block_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Reset block', 'lmtttmpts' ) . '</a>'
			);
			return sprintf('%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'reset_blocks'	=> __( 'Reset block', 'lmtttmpts' )
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
			$part_ip = isset( $_GET['s'] ) ? trim( htmlspecialchars( $_GET['s'] ) ) : '';
			/* query for total number of IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "failed_attempts` WHERE `block` = true";
			/* if search */
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_GET['s'] ) ) ) );
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
			$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) && 'ip' != $_GET['orderby'] ) ? $_GET['orderby'] : 'ip_int';
			$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array( 'asc', 'desc' ) ) ) ? $_GET['order'] : 'asc';
			/* calculate offset for pagination */
			$paged = ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] ) && 0 < $_GET['paged'] ) ? $_GET['paged'] : 1;
			$offset = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `block_till` FROM `" . $prefix . "failed_attempts` WHERE `block` = true";
			/* if search */
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_GET['s'] ) ) ) );
				if ( 0 != $search_ip || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$query .= " AND `ip_int` = " . $search_ip ." OR `ip` LIKE '%" . $part_ip . "%'";
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
	}

	/**
	 * Create new class for displaying blacklist
	 */
	class Lmtttmpts_Blacklist extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'ip'			=> __( 'Ip address', 'lmtttmpts' ),
				'ip_from'		=> __( 'Diapason from', 'lmtttmpts' ),
				'ip_to'			=> __( 'Diapason till', 'lmtttmpts' ),
			);
			return $columns;
		}

		function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'ip' 		=> array( 'ip', true ),
				'ip_from' 	=> array( 'ip_from', false ),
				'ip_to' 	=> array( 'ip_to', false )
			);
			return $sortable_columns;
		}

		function column_ip( $item ) {
			/* adding action to 'ip' collumn */
			$actions = array(
				'remove_from_blacklist'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&action=%s&lmtttmpts_remove_from_blacklist=%s', $_GET['page'], $_GET['action'], $item['ip'] ), 'lmtttmpts_remove_from_blacklist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Remove from blacklist', 'lmtttmpts' ) . '</a>'
			);
			return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'remove_from_blacklist_ips'	=> __( 'Remove from blacklist', 'lmtttmpts' )
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
			$part_ip = isset( $_GET['s'] ) ? trim( htmlspecialchars( $_GET['s'] ) ) : '';
			/* query for total number of blacklisted IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "blacklist`";
			/* if search */
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_GET['s'] ) ) ) );
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
			if ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) && 'ip' != $_GET['orderby'] ) {
				/* not by single IP column */
				switch ( $_GET['orderby'] ) {
					case 'ip_from':
						$orderby = 'ip_from_int';
						break;
					case 'ip_to':
						$orderby = 'ip_to_int';
						break;
					default:
						$orderby = $_GET['orderby'];
						break;
				}
			} else {
				/* sort by IP in following cases: by default and if user clicks on 'IP' column */
				$orderby = 'ip_from_int';
			}
			$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array( 'asc', 'desc' ) ) ) ? $_GET['order'] : 'asc';
			/* calculate offset for pagination */
			$paged = ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] ) && 0 < $_GET['paged'] ) ? $_GET['paged'] : 1;
			$offset = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `ip_from`, `ip_to` FROM `" . $prefix . "blacklist`";
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", trim( $_GET['s'] ) ) ) );
				if ( 0 != $search_ip ||  preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$query .= " WHERE ( `ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip . ") OR `ip` LIKE '%" . $part_ip . "%'";
				}
			}

			/* add calculated values (order and pagination) to our query */
			$query .= " ORDER BY `" . $orderby. "` " . $order . " LIMIT " . $offset . "," . $perpage;

			$columns 				= $this->get_columns();
			$hidden 				= array();
			$sortable 				= $this->get_sortable_columns();
			$this->_column_headers	= array( $columns, $hidden, $sortable );
			$this->items 			= $wpdb->get_results( $query, ARRAY_A );
		}

		function column_default( $item, $column_name ) {
			/* setting default view for collumn items */
			switch( $column_name ) {
				case 'ip':
				case 'ip_from':
				case 'ip_to':
					return $item[ $column_name ];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true ) ;
			}
		}
	}

	/**
	 * Create new class for displaying whitelist
	 */
	class Lmtttmpts_Whitelist extends WP_List_Table {
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'ip'			=> __( 'Ip address', 'lmtttmpts' ),
				'ip_from'		=> __( 'Diapason from', 'lmtttmpts' ),
				'ip_to'			=> __( 'Diapason till', 'lmtttmpts' ),
			);
			return $columns;
		}

		function get_sortable_columns() {
			/* seting sortable collumns */
			$sortable_columns = array(
				'ip' 		=> array( 'ip', true ),
				'ip_from' 	=> array( 'ip_from', false ),
				'ip_to' 	=> array( 'ip_to', false )
			);
			return $sortable_columns;
		}

		function column_ip( $item ) {
			/* adding action to 'ip' collumn */
			$actions = array(
				'remove_from_whitelist'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&action=%s&lmtttmpts_remove_from_whitelist=%s' ,$_GET['page'],$_GET['action'], $item['ip'] ) , 'lmtttmpts_remove_from_whitelist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Remove from whitelist', 'lmtttmpts' ) . '</a>'
			);
			return sprintf('%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'remove_from_whitelist_ips'	=> __( 'Remove from whitelist', 'lmtttmpts' ),
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
			$part_ip = isset( $_GET['s'] ) ? trim( htmlspecialchars( $_GET['s'] ) ) : '';
			/* query for total number of blacklisted IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "whitelist`";
			/* if search */
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
				if ( 0 != $search_ip || preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$count_query .= " WHERE ( `ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip. ") OR `ip` LIKE '%" . $part_ip . "%'";
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
			if ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) && 'ip' != $_GET['orderby'] ) {
				/* sort by any column other than default IP */
				switch ( $_GET['orderby'] ) {
					case 'ip_from':
						$orderby = 'ip_from_int';
						break;
					case 'ip_to':
						$orderby = 'ip_to_int';
						break;
					default:
						$orderby = $_GET['orderby'];
						break;
				}
			} else {
				/* sort by IP */
				$orderby = 'ip_from_int';
			}
			$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array( 'asc', 'desc' ) ) ) ? $_GET['order'] : 'asc';
			/* calculate offset for pagination */
			$paged = ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] ) && 0 < $_GET['paged'] ) ? $_GET['paged'] : 1;
			$offset = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `ip_from`, `ip_to` FROM `" . $prefix . "whitelist`";
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
				if ( 0 != $search_ip ||  preg_match( "/^(\.|\d)?(\.?[0-9]{1,3}?\.?){1,4}?(\.|\d)?$/i", $part_ip ) ) {
					$query .= " WHERE (`ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip. ") OR `ip` LIKE '%" . $part_ip . "%'";
				}
			}
			/* add calculated values (order and pagination) to our query */
			$query .= " ORDER BY `" . $orderby . "` " . $order . " LIMIT " . $offset . "," . $perpage;

			$columns 				= $this->get_columns();
			$hidden 				= array();
			$sortable 				= $this->get_sortable_columns();
			$this->_column_headers 	= array( $columns, $hidden, $sortable );
			$this->items 			= $wpdb->get_results( $query, ARRAY_A );
		}

		function column_default( $item, $column_name ) {
			/* setting default view for collumn items */
			switch ( $column_name ) {
				case 'ip':
				case 'ip_from':
				case 'ip_to':
					return $item[ $column_name ];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true );
			}
		}
	}

	/**
	 * Create new class for displaying statistics
	 */
	class Lmtttmpts_Statistics extends WP_List_Table { 
		function get_columns() {
			/* adding collumns to table and their view */
			$columns = array(
				'cb'				=> '<input type="checkbox" />',
				'ip'				=> __( 'Ip address', 'lmtttmpts' ),
				'failed_attempts'	=> __( 'Number of failed attempts', 'lmtttmpts' ),
				'block_quantity'	=> __( 'Number of blocks', 'lmtttmpts' ),
				'status'			=> __( 'Status', 'lmtttmpts' )
			);
			return $columns;
		}

		function get_bulk_actions() {
			/* adding bulk action */
			$actions = array(
				'clear_statistics_for_ips'	=> __( 'Delete statistics entry', 'lmtttmpts' )
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
			$part_ip = isset( $_GET['s'] ) ? trim( htmlspecialchars( $_GET['s'] ) ) : '';
			/* query for total number of IPs */
			$count_query = "SELECT COUNT(*) FROM `" . $prefix . "all_failed_attempts`";
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
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
			$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) && 'ip' != $_GET['orderby'] ) ? $_GET['orderby'] : 'ip_int';
			$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array('asc', 'desc') ) ) ? $_GET['order'] : 'asc';

			/* calculate offset for pagination */
			$paged = ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] ) && 0 < $_GET['paged'] ) ? $_GET['paged'] : 1;
			/* set pagination arguments */
			$offset = ( $paged - 1 ) * $perpage;

			/* general query */
			$query = "SELECT `ip`, `failed_attempts`, `block_quantity` FROM `" . $prefix . "all_failed_attempts`";
			if ( isset( $_GET['s'] ) ) {
				$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
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
					if ( lmtttmpts_is_ip_in_table( $statistic['ip'], 'whitelist' ) ) {
						$statistic['status'] = '<a href="?page=' . $_GET['page'] . '&action=whitelist&s=' . $statistic['ip'] . '">' . __( 'whitelisted', 'lmtttmpts' ) . '</a>';
						$statistic['row_class'] = 'lmtttmpts_whitelist';
					} elseif ( lmtttmpts_is_ip_in_table( $statistic['ip'], 'blacklist' ) ) {
						$statistic['status'] = '<a href="?page=' . $_GET['page'] . '&action=blacklist&s=' . $statistic['ip'] . '">' . __( 'blacklisted', 'lmtttmpts' ) . '</a>';
						$statistic['row_class'] = 'lmtttmpts_blacklist';
					} elseif ( lmtttmpts_is_ip_blocked( $statistic['ip'] ) ) {
						$statistic['status'] = '<a href="?page=' . $_GET['page'] . '&action=blocked&s=' . $statistic['ip'] . '">' . __( 'blocked', 'lmtttmpts' ) . '</a>';
						$statistic['row_class'] = 'lmtttmpts_blocked';
					} else {
						$statistic['status'] = __( 'not blocked', 'lmtttmpts' );
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
	}
}

/**
 * Function to get correct IP address
 */
if ( ! function_exists( 'lmtttmpts_get_address' ) ) {
	function lmtttmpts_get_address() {
		$realip = '';
		if ( isset( $_SERVER ) ) {
			if ( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
				$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			} elseif ( isset( $_SERVER["HTTP_CLIENT_IP"] ) ) {
				$realip = $_SERVER["HTTP_CLIENT_IP"];
			} elseif ( isset( $_SERVER["REMOTE_ADDR"] ) ) {
				$realip = $_SERVER["REMOTE_ADDR"];
			}
		}
		return $realip;
	}
}

/** 
 * Function for checking is current ip is blocked 
 */
if ( !function_exists( 'lmtttmpts_is_ip_blocked' ) ) { 
	function lmtttmpts_is_ip_blocked( $ip ) {
		global $wpdb;
		$is_blocked = $wpdb->get_var( $wpdb->prepare( 
			'SELECT `block` FROM %1$s WHERE `ip_int` = %2$s', $wpdb->prefix . 'lmtttmpts_failed_attempts', sprintf( '%u', ip2long( $ip ) )
		) );
		return $is_blocked;
	}
}

if ( ! function_exists( 'lmtttmpts_screen_options' ) ) {
	function lmtttmpts_screen_options() {
		$option = 'per_page';
		$args = array(
			'label'   => __( 'Addresses per page', 'lmtttmpts' ),
			'default' => 30,
			'option'  => 'addresses_per_page'
		);
		add_screen_option( $option, $args );
	}
}

if ( ! function_exists( 'lmtttmpts_table_set_option' ) ) {
	function lmtttmpts_table_set_option( $status, $option, $value ) {
		return $value;
	}
}

/**
 * Function for checking is current ip in current table
 */
if ( ! function_exists( 'lmtttmpts_is_ip_in_table' ) ) {
	function lmtttmpts_is_ip_in_table( $ip, $table ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* integer value for our IP */
		$ip_int = sprintf( '%u', ip2long( $ip ) );
		if ( $table == 'whitelist' || $table == 'blacklist' ) {
			/* for whitelist and blacklist tables needs different method */
			if ( $ip_int != 0 ) {
				/* checking is $ip variable is ip address and not a ip mask */
				$is_in = $wpdb->get_var( $wpdb->prepare( 
					'SELECT `ip` FROM %1$s WHERE `ip_from_int` <= %2$s AND `ip_to_int` >= %2$s ', $prefix . $table, $ip_int 
				) );
			} else {
				/* if $ip variable is ip mask */
				$is_in = $wpdb->get_var( $wpdb->prepare(
					"SELECT `ip` FROM `" . $prefix . $table . "` WHERE `ip` = %s ", $ip
				) );
			}
		} else { /* for other tables */
			$is_in = $wpdb->get_var( $wpdb->prepare(
				'SELECT `ip` FROM %1$s WHERE `ip_int` = %2$s ', $prefix . $table, $ip_int
			) );
		}
		return $is_in;
	}
}

/**
 * Function for adding ip to blacklist
 * @param ip - (string) IP
 * @return bool true/false with the result of DB add operation
 */
if ( ! function_exists( 'lmtttmpts_add_ip_to_blacklist' ) ) { 
	function lmtttmpts_add_ip_to_blacklist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* if IP isn't empty and isn't in blacklist already */
		if ( '' != $ip && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) ) {
			/* if insert single ip address */
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) { 
				$ip_int = sprintf( '%u', ip2long( $ip ) ); 
				/* add a new row to db */
				$result = $wpdb->insert(
					$prefix . 'blacklist',
					array( 
						'ip' 			=> $ip, 
						'ip_from' 		=> $ip,
						'ip_to' 		=> $ip,
						'ip_from_int' 	=> $ip_int,
						'ip_to_int' 	=> $ip_int
					),
					'%s' /* all '%s' because max value in '%d' is 2147483647 */
				);
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

/**
 * Function for adding ip to whitelist
 * @param ip - (string) IP
 * @return bool true/false with the result of DB add operation
 */
if ( ! function_exists( 'lmtttmpts_add_ip_to_whitelist' ) ) { 
	function lmtttmpts_add_ip_to_whitelist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* if IP isn't empty and isn't in whitelist already */
		if ( '' != $ip && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			/* if insert single ip address */
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) {
				$ip_int = sprintf( '%u', ip2long( $ip ) );
				/* add a new row to db */
				$result = $wpdb->insert(
					$prefix . 'whitelist',
					array( 
						'ip' 			=> $ip, 
						'ip_from' 		=> $ip,
						'ip_to' 		=> $ip,
						'ip_from_int' 	=> $ip_int,
						'ip_to_int' 	=> $ip_int
					),
					'%s' /* all '%s' because max value in '%d' is 2147483647 */
				);
				return $result;
			} else {
				return false;
			}
		}
	}
}

/**
 * Function to clear all statistics
 */
if ( ! function_exists( 'lmtttmpts_clear_statistics_completely' ) ) {
	function lmtttmpts_clear_statistics_completely() {
		global $wpdb;
		$result = $wpdb->query( "DELETE FROM `" . $wpdb->prefix . "lmtttmpts_all_failed_attempts`" );
		return $result;
	}
}

/**
 * Function to clear single statistics entry
 */
if ( ! function_exists( 'lmtttmpts_clear_statistics' ) ) {
	function lmtttmpts_clear_statistics( $ip ) {
		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . 'lmtttmpts_all_failed_attempts',
			array( 'ip' => $ip ),
			array( '%s' )
		);
		return $result;
	}
}

/**
 * Function to cron clear statistics daily
 */
if ( ! function_exists( 'lmtttmpts_clear_statistics_daily' ) ) {
	function lmtttmpts_clear_statistics_daily() {
		global $wpdb, $lmtttmpts_options;
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		$time = date( 'Y-m-d H:i:s', time() - 86400 * $lmtttmpts_options['days_to_clear_statistics'] );
		$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "lmtttmpts_all_failed_attempts` WHERE `last_failed_attempt` <= '" . $time . "'" );
	}
}

/**
 * Function to reset failed attempts
 */
if ( ! function_exists( 'lmtttmpts_reset_failed_attempts' ) ) { 
	function lmtttmpts_reset_failed_attempts( $ip ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'lmtttmpts_failed_attempts',
			array( 'failed_attempts' => 0 ),
			array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
			array( '%d' ),
			array( '%s' )
		);
	}
}

/**
 * Function to reset block
 */
if ( ! function_exists( 'lmtttmpts_reset_block' ) ) { 
	function lmtttmpts_reset_block() {
		global $wpdb, $lmtttmpts_options;
		$reset_ip_db = '';

		if ( empty( $lmtttmpts_options ) )
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );

		$unlocking_timestamp =  date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + 60 ) );
		$current_timestamp = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) ) );
		$blockeds = $wpdb->get_results( "SELECT `ip_int`, `ip` FROM `" . $wpdb->prefix . "lmtttmpts_failed_attempts` WHERE `block_till` <= '" . $unlocking_timestamp . "' and `block` = '1'", ARRAY_A );
		if ( ! empty( $blockeds ) ) {
			foreach ( $blockeds as $blocked ) {
				$reset_ip_in_htaccess[] = $blocked['ip'];
				$reset_ip_db .= ( '' == $reset_ip_db ) ? "'" . $blocked['ip_int'] . "'" : ",'" . $blocked['ip_int'] . "'";
			}
		}
		$next_timestamp = $wpdb->get_row( "SELECT `block_till` FROM `" . $wpdb->prefix . "lmtttmpts_failed_attempts` WHERE `block_till` > '" . $current_timestamp . "' ORDER BY `block_till`", ARRAY_A ); 
		if ( ! empty( $next_timestamp ) ) {
			$next_timestamp_unix_time = strtotime( $next_timestamp['block_till'] );
			wp_schedule_single_event( $next_timestamp_unix_time, 'lmtttmpts_event_for_reset_block' );
		}
		if ( '' != $reset_ip_db ) {
			$wpdb->query( "UPDATE `" . $wpdb->prefix . "lmtttmpts_failed_attempts` SET `block` = '0' WHERE `ip_int` IN (" . $reset_ip_db . ")" );
		}
		/* hook for deblocking by Htaccess */
		if ( isset( $lmtttmpts_options['block_by_htaccess'] ) && ! empty( $reset_ip_in_htaccess ) ) {
			do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $reset_ip_in_htaccess );
		}
	}
}

/**
 * Function to reset number of blocks
 */
if ( ! function_exists( 'lmtttmpts_reset_block_quantity' ) ) { 
	function lmtttmpts_reset_block_quantity( $ip ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'lmtttmpts_failed_attempts',
			array( 'block_quantity' => 0 ),
			array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
			array( '%d' ),
			array( '%s' )
		);
	}
}

/**
 * Filter to transfer message in html format
 */
if ( ! function_exists( 'lmtttmpts_set_html_content_type' ) ) { 
	function lmtttmpts_set_html_content_type() {
		return 'text/html';
	}
}

/**
 * Checking for right captcha in login form
 */
if ( ! function_exists( 'lmtttmpts_login_form_captcha_checking' ) ) {
	function lmtttmpts_login_form_captcha_checking() {
		global $lmtttmpts_options;
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		if ( is_multisite() ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
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

/**
 * Function (ajax) to restore default message
 * @return void
 */
if ( ! function_exists( 'lmtttmpts_restore_default_message' ) ) {
	function lmtttmpts_restore_default_message() {
		check_ajax_referer( 'lmtttmpts_ajax_nonce_value', 'lmtttmpts_nonce' );
		/* get the list of default messages */
		$lmtttmpts_messages_defaults = lmtttmpts_get_default_messages();
		if ( isset( $_POST['action'] ) && 'lmtttmpts_restore_default_message' == $_POST['action'] ) {
			$message_option_name_default = $_POST['message_option_name'] . "_default";
			if ( array_key_exists( $message_option_name_default, $lmtttmpts_messages_defaults ) ) {
				/* set notice message, check what was changed - subject or body of the message */
				$output_message = '<div class="updated fade lmtttmpts-restore-default-message"><p><strong>' . __( 'Notice:', 'lmtttmpts' ) . '</strong> ';
				if ( 'email_subject' == $_POST['message_option_name'] || 'email_subject_blacklisted' == $_POST['message_option_name'] ) {
					$output_message .= __( 'Subject has been restored to default', 'lmtttmpts' );
				} else {
					$output_message .= __( "Message has been restored to default", 'lmtttmpts' );
				}
				$output_message .= '</p><p><strong>' . __( 'Changes are not saved', 'lmtttmpts' ) . '</strong></p></div>';
				/* send default text of subject/body into ajax array */
				$message_restored = array(
					'restored_message_text' => $lmtttmpts_messages_defaults[ $message_option_name_default ],
					'admin_notice_message' 	=> $output_message,
				);
				echo json_encode( $message_restored );
				die();
			}
		}
	}
}

if ( ! function_exists( 'lmtttmpts_plugin_banner' ) ) {
	function lmtttmpts_plugin_banner() {
		global $hook_suffix, $lmtttmpts_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {  
			bws_plugin_banner( $lmtttmpts_plugin_info, 'lmtttmpts', 'limit-attempts', '33bc89079511cdfe28aeba317abfaf37', '140', '//ps.w.org/limit-attempts/assets/icon-128x128.png' ); 	
		}
	}
}

/**
 * Delete plugin for network
 */
if ( ! function_exists( 'lmtttmpts_plugin_uninstall' ) ) {
	function lmtttmpts_plugin_uninstall() {
		global $wpdb;
		$all_plugins = get_plugins();
		$pro_version_exist = array_key_exists( 'limit-attempts-pro/limit-attempts-pro.php', $all_plugins ) ? true : false;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			/* check if it is a multisite - if so, run the uninstall function for each blog id */
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				lmtttmpts_delete_options( $pro_version_exist );
			}
			switch_to_blog( $old_blog );
			return;
		}
		lmtttmpts_delete_options( $pro_version_exist );
	}
}

/**
 * Delete plugin  blog
 */
if ( ! function_exists( 'lmtttmpts_delete_blog' ) ) {
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

/**
 * Function for deleting options when uninstal current plugin
 */
if ( ! function_exists( 'lmtttmpts_delete_options' ) ) { 
	function lmtttmpts_delete_options( $pro_version_exist ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		/* delete options */
		delete_option( 'lmtttmpts_options' );
		/* drop tables */
		if ( ! $pro_version_exist ) {
			/* drop all tables */
			$sql = "DROP TABLE `" . $prefix . "all_failed_attempts`, `" . $prefix . "failed_attempts`, `" . $prefix . "blacklist`, `" . $prefix . "whitelist`;";
		} else {
			/* drop FREE tables only */
			$sql = "DROP TABLE `" . $prefix . "all_failed_attempts`;";
		}
		$wpdb->query( $sql );
		/* clear hook to delete old statistics entries */
		wp_clear_scheduled_hook( 'lmtttmpts_daily_statistics_clear' );
	}
}

/* installation */
register_activation_hook( __FILE__, 'lmtttmpts_plugin_activate' );
add_action( 'wpmu_new_blog', 'lmtttmpts_new_blog', 10, 6 );
add_action( 'delete_blog', 'lmtttmpts_delete_blog', 10 );
/* register */
add_action( 'admin_menu', 'add_lmtttmpts_admin_menu' );
add_action( 'init', 'lmtttmpts_plugin_init' );
add_action( 'admin_init', 'lmtttmpts_plugin_admin_init' );
add_action( 'admin_enqueue_scripts', 'lmtttmpts_admin_head' );
add_filter( 'set-screen-option', 'lmtttmpts_table_set_option', 10, 3 );
add_filter( 'plugin_action_links', 'lmtttmpts_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'lmtttmpts_register_plugin_links', 10, 2 );
/* login functions */
add_action( 'login_head', 'lmtttmpts_error_message' );
add_action( 'wp_login_failed', 'lmtttmpts_login_failed' );
add_filter( 'wp_authenticate_user', 'lmtttmpts_authenticate_user', 99999, 2 );
/* reset blocks */
add_action( 'lmtttmpts_event_for_reset_failed_attempts', 'lmtttmpts_reset_failed_attempts' );
add_action( 'lmtttmpts_event_for_reset_block', 'lmtttmpts_reset_block' );
add_action( 'lmtttmpts_event_for_reset_block_quantity', 'lmtttmpts_reset_block_quantity' );
add_action( 'lmtttmpts_daily_statistics_clear', 'lmtttmpts_clear_statistics_daily' );
add_action( 'admin_notices', 'lmtttmpts_show_notices' );
/* ajax function */
add_action( 'wp_ajax_lmtttmpts_restore_default_message', 'lmtttmpts_restore_default_message' );
/* Adding banner */
register_uninstall_hook( __FILE__, 'lmtttmpts_plugin_uninstall' );