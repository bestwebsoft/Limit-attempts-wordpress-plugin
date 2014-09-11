<?php /*
Plugin Name: Limit Attempts
Plugin URI: http://bestwebsoft.com/plugin/
Description: The plugin Limit Attempts allows you to limit rate of login attempts by the ip, and create whitelist and blacklist.
Author: BestWebSoft
Version: 1.0.5
Author URI: http://bestwebsoft.com/
License: GPLv3 or later
*/

/*  © Copyright 2014  BestWebSoft  ( http://support.bestwebsoft.com )

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

/*
* Function for adding menu and submenu 
*/
if ( ! function_exists( 'add_lmtttmpts_admin_menu' ) ) { 
	function add_lmtttmpts_admin_menu() {
		global $bstwbsftwppdtplgns_options, $wpmu, $bstwbsftwppdtplgns_added_menu;
		$bws_menu_info = get_plugin_data( plugin_dir_path( __FILE__ ) . "bws_menu/bws_menu.php" );
		$bws_menu_version = $bws_menu_info["Version"];
		$base = plugin_basename( __FILE__ );
		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			if ( 1 == $wpmu ) {
				if ( ! get_site_option( 'bstwbsftwppdtplgns_options' ) )
					add_site_option( 'bstwbsftwppdtplgns_options', array(), '', 'yes' );
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				if ( ! get_option( 'bstwbsftwppdtplgns_options' ) )
					add_option( 'bstwbsftwppdtplgns_options', array(), '', 'yes' );
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}
		if ( isset( $bstwbsftwppdtplgns_options['bws_menu_version'] ) ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			unset( $bstwbsftwppdtplgns_options['bws_menu_version'] );
			update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] ) || $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] < $bws_menu_version ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_added_menu ) ) {
			$plugin_with_newer_menu = $base;
			foreach ( $bstwbsftwppdtplgns_options['bws_menu']['version'] as $key => $value ) {
				if ( $bws_menu_version < $value && is_plugin_active( $base ) ) {
					$plugin_with_newer_menu = $key;
				}
			}
			$plugin_with_newer_menu = explode( '/', $plugin_with_newer_menu );
			$wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? basename( WP_CONTENT_DIR ) : 'wp-content';
			if ( file_exists( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' ) )
				require_once( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' );
			else
				require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
			$bstwbsftwppdtplgns_added_menu = true;
		}
		add_menu_page( 'BWS Plugins', 'BWS Plugins', 'manage_options', 'bws_plugins', 'bws_add_menu_render', plugins_url( "images/px.png", __FILE__ ), 1001 );
		$hook = add_submenu_page( 'bws_plugins', __( 'Limit Attempts Settings', 'lmtttmpts' ), __( 'Limit Attempts', 'lmtttmpts' ), 'manage_options', "limit-attempts.php", 'lmtttmpts_settings_page' );
		add_action( "load-$hook", 'lmtttmpts_screen_options' );
	}
}

/*
* Function initialisation plugin 
*/
if ( ! function_exists ( 'lmtttmpts_plugin_init' ) ) { 
	function lmtttmpts_plugin_init() {
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'lmtttmpts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/*
* Initial tables create
*/
if ( ! function_exists( 'lmtttmpts_create_table' ) ) {
	function lmtttmpts_create_table() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		/*Query for create table with current number of failed attempts and block quantity, block status and time when addres will be deblocked*/
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
		/*Query for create table with all number of failed attempts and block quantity, block status and time when addres will be deblocked*/
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "all_failed_attempts` (
			`ip` CHAR(31) NOT NULL,
			`ip_int` BIGINT,
			`failed_attempts` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_login_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_registration_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_reset_password_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_comments_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_contact_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_subscriber` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_bp_registration_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_bp_comments_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_bp_create_group_form` INT(4) NOT NULL DEFAULT '0',
			`invalid_captcha_from_contact_form_7` INT(4) NOT NULL DEFAULT '0',
			`block_quantity` INT(3) NOT NULL DEFAULT '0',
			`last_failed_attempt` TIMESTAMP,
			PRIMARY KEY  (`ip`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/*Query for create table with whitelisted addresse*/
		$sql = "CREATE TABLE IF NOT EXISTS `" . $prefix . "whitelist` (
			`ip` CHAR(31) NOT NULL,
			`ip_from` CHAR(15) NOT NULL,
			`ip_to` CHAR(15) NOT NULL,
			`ip_from_int` BIGINT,
			`ip_to_int` BIGINT,
			PRIMARY KEY  (`ip`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql );
		/*Query for create table with blacklisted addresse*/
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

/*
* Function initialisation plugin 
*/
if ( ! function_exists ( 'lmtttmpts_plugin_admin_init' ) ) {
	function lmtttmpts_plugin_admin_init() {
		global $bws_plugin_info, $lmtttmpts_plugin_info;
		$lmtttmpts_plugin_info = get_plugin_data( __FILE__, false );
		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '140', 'version' => $lmtttmpts_plugin_info["Version"] );
		/* Check version on WordPress */
		lmtttmpts_version_check();
		/* Call register settings function */
		if ( isset( $_GET['page'] ) && "limit-attempts.php" == $_GET['page'] )
			register_lmtttmpts_settings(); 
	}
}

/*
* Function to add stylesheets
*/
if ( ! function_exists ( 'lmtttmpts_admin_head' ) ) {
	function lmtttmpts_admin_head() {
		if ( isset( $_REQUEST['page'] ) && ( 'limit-attempts.php' == $_REQUEST['page'] ) ) {
			wp_enqueue_style( 'lmtttmpts_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'lmtttmpts_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
		}
	}
}

/* 
* Function check if plugin is compatible with current WP version 
*/
if ( ! function_exists ( 'lmtttmpts_version_check' ) ) {
	function lmtttmpts_version_check() {
		global $wp_version, $lmtttmpts_plugin_info;
		$require_wp		=	"3.6"; /* Wordpress at least requires version */
		$plugin			=	plugin_basename( __FILE__ );
	 	if ( version_compare( $wp_version, $require_wp, "<" ) ) {
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				wp_die( "<strong>" . $lmtttmpts_plugin_info['Name'] . " </strong> " . __( 'requires', 'lmtttmpts' ) . " <strong>WordPress " . $require_wp . "</strong> " . __( 'or higher, that is why it has been deactivated! Please upgrade WordPress and try again.', 'lmtttmpts') . "<br /><br />" . __( 'Back to the WordPress', 'lmtttmpts') . " <a href='" . get_admin_url( null, 'plugins.php' ) . "'>" . __( 'Plugins page', 'lmtttmpts') . "</a>." );
			}
		}
	}
}

/* 
* Register settings function 
*/
if ( ! function_exists( 'register_lmtttmpts_settings' ) ) {
	function register_lmtttmpts_settings() {
		global $wpmu, $lmtttmpts_options, $lmtttmpts_plugin_info;
		$lmtttmpts_email_address = get_bloginfo( 'admin_email' ); /*email addres that was setting Settings -> General -> E-mail Address */
		$lmtttmpts_db_version = "1.0";
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
			'days_to_clear_log'				=> '30',
			'plugin_option_version'			=> $lmtttmpts_plugin_info["Version"],
			'options_for_block_message'		=> 'hide',
			'options_for_email_message'		=> 'hide',
			'notify_email'					=> false,
			'mailto'						=> 'admin',
			'email_address'					=> $lmtttmpts_email_address,
			'failed_message'				=> 'Retries to lock: %ATTEMPTS%',
			'failed_message_default'		=> 'Retries to lock: %ATTEMPTS%',
			'blocked_message'				=> 'Too many retries. You have been blocked till %DATE%',
			'blocked_message_default'		=> 'Too many retries. You have been blocked till %DATE%', 
			'blacklisted_message'			=> 'You have been added to blacklist. Please contact with administrator to resolve this problem: %MAIL%', 
			'blacklisted_message_default'	=> 'You have been added to blacklist. Please contact with administrator to resolve this problem: %MAIL%', 
			'email_subject'					=> '%IP% was blocked in %SITE_NAME%',
			'email_subject_default'			=> '%IP% was blocked in %SITE_NAME%',
			'email_blocked'					=> '%WHEN% IP %IP% automatically blocked due to the excess of login attempts on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
			'email_blocked_default'			=> '%WHEN% IP %IP% automatically blocked due to the excess of login attempts on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
			'email_blacklisted'				=> '%WHEN% IP %IP% automatically added to the blacklist due to the excess of locks quantity on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
			'email_blacklisted_default'		=> '%WHEN% IP %IP% automatically added to the blacklist due to the excess of locks quantity on your website <a href="%SITE_URL%">%SITE_NAME%</a>.<br/><br/> Using the plugin <a href="%PLUGIN_LINK%">Limit Attempts</a> by <a href="http://bestwebsoft.com/">BestWebSoft</a>',
		);
		/* Install the option defaults */
		if ( 1 == $wpmu ) {
			if ( ! get_site_option( 'lmtttmpts_options' ) ) {
				add_site_option ( 'lmtttmpts_options', $lmtttmpts_option_defaults, '', 'yes' );
				/*Schedule event to clear log daily*/
				$time = time() - 86400 * $lmtttmpts_options['days_to_clear_log'];
				wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_log_clear' );
			}
			/* Get options from the database */
			$lmtttmpts_options = get_site_option( 'lmtttmpts_options' );
		} else {
			if ( ! get_option( 'lmtttmpts_options' ) ) {
				add_option( 'lmtttmpts_options', $lmtttmpts_option_defaults, '', 'yes' );
				/*Schedule event to clear log daily*/
				$time = time() - 86400 * $lmtttmpts_options['days_to_clear_log'];
				wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_log_clear' );
			}
			/* Get options from the database */
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
		/* Update options when update plugin */
		if ( ! isset( $lmtttmpts_options['plugin_option_version'] ) || $lmtttmpts_options['plugin_option_version'] != $lmtttmpts_plugin_info["Version"] ) {
			$lmtttmpts_options = array_merge( $lmtttmpts_option_defaults, $lmtttmpts_options );
			$lmtttmpts_options['plugin_option_version'] = $lmtttmpts_plugin_info["Version"];
		}
		/* Update tables when update plugin and tables changes*/
		if ( ! isset( $lmtttmpts_options['plugin_db_version'] ) || $lmtttmpts_options['plugin_db_version'] != $lmtttmpts_db_version ) {
			lmtttmpts_create_table();
			$lmtttmpts_options['plugin_db_version'] = $lmtttmpts_db_version;
			update_option( 'lmtttmpts_options', $lmtttmpts_options );
		}
	}
}

/*
* Function to handle action links
*/
if ( ! function_exists( 'lmtttmpts_plugin_action_links' ) ) {
	function lmtttmpts_plugin_action_links( $links, $file ) {
		/* Static so we don't call plugin_basename on every plugin row. */
		static $this_plugin;
		if ( ! $this_plugin )
			$this_plugin = plugin_basename(__FILE__);

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=limit-attempts.php">' . __( 'Settings', 'lmtttmpts' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

if ( ! function_exists( 'lmtttmpts_register_plugin_links' ) ) {
	function lmtttmpts_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$links[]	=	'<a href="admin.php?page=limit-attempts.php">' . __( 'Settings', 'lmtttmpts' ) . '</a>';
			$links[]	=	'<a href="http://wordpress.org/plugins/limit-attempts/faq/" target="_blank">' . __( 'FAQ', 'lmtttmpts' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support', 'lmtttmpts' ) . '</a>';
		}
		return $links;
	}
}

/* 
* Function for display limit attempts settings page 
* in the admin area and register new settings
*/
if ( ! function_exists( 'lmtttmpts_settings_page' ) ) {
	function lmtttmpts_settings_page() { 
		global $wpmu, $bstwbsftwppdtplgns_options, $lmtttmpts_options, $wpdb, $lmtttmpts_plugin_info, $wp_version; 
		
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();

		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&who=authors' );

		/* Start updating and verification options from Settings form */
		/*First of all checking that clicked "Save changes button"*/
		if ( isset( $_POST['lmtttmpts_form_submit'] ) && ! isset( $_POST['lmtttmpts_return_default'] ) && check_admin_referer( plugin_basename(__FILE__), 'lmtttmpts_nonce_name' ) ) {
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
			/*Veification and updating option with days to clerar log*/
			if ( isset( $_POST['lmtttmpts_days_to_clear_log'] ) && $_POST['lmtttmpts_days_to_clear_log'] >= 0 && is_numeric( $_POST['lmtttmpts_days_to_clear_log'] ) ) {
				if ( $lmtttmpts_options['days_to_clear_log'] != floor( $_POST['lmtttmpts_days_to_clear_log'] ) ) {
					if ( $lmtttmpts_options['days_to_clear_log'] == 0 ) {
						$time = time() - floor( $_POST['lmtttmpts_days_to_clear_log'] ) * 86400;
						wp_schedule_event( $time, 'daily', 'lmtttmpts_daily_log_clear' );
					} elseif ( $_POST['lmtttmpts_days_to_clear_log'] == 0 ) {
						wp_clear_scheduled_hook( 'lmtttmpts_daily_log_clear' );
					}
				}
				$lmtttmpts_options['days_to_clear_log'] = floor( $_POST['lmtttmpts_days_to_clear_log'] );
			}
			/*Updating options with notify by email options*/
			if ( isset( $_POST['lmtttmpts_notify_email'] ) ) 
				$lmtttmpts_options['notify_email'] = true;
			else
				$lmtttmpts_options['notify_email'] = false;
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
				if ( ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $active_plugins ) ) || is_plugin_active_for_network( 'htaccess/htaccess.php' ) ) && ! isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_copy_all' );
				}
				$lmtttmpts_options['block_by_htaccess'] = $_POST['lmtttmpts_block_by_htaccess'];
			} else {
				if ( ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $active_plugins ) ) || is_plugin_active_for_network( 'htaccess/htaccess.php' ) ) && isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_delete_all' );
				}
				unset( $lmtttmpts_options['block_by_htaccess'] );
			}
			/*Updating options of interaction with Captcha plugin in login form*/
			if ( isset( $_POST['lmtttmpts_login_form_captcha_check'] ) )
				$lmtttmpts_options['login_form_captcha_check'] = $_POST['lmtttmpts_login_form_captcha_check'];
			else
				unset( $lmtttmpts_options['login_form_captcha_check'] );			
			/*Updating message that displayed when login failed*/
			if ( isset( $_POST['lmtttmpts_failed_message'] ) ) {
				$lmtttmpts_options['failed_message'] = stripslashes( $_POST['lmtttmpts_failed_message'] );
			}
			/*Updating message that displayed when address blocked*/
			if ( isset( $_POST['lmtttmpts_blocked_message'] ) ) {
				$lmtttmpts_options['blocked_message'] = stripslashes( $_POST['lmtttmpts_blocked_message'] );
			}
			/*Updating message that displayed when address blacklisted*/
			if ( isset( $_POST['lmtttmpts_blacklisted_message'] ) ) {
				$lmtttmpts_options['blacklisted_message'] = stripslashes( $_POST['lmtttmpts_blacklisted_message'] );
			}
			/*Updating subject in email message*/
			if ( isset( $_POST['lmtttmpts_email_subject'] ) ) {
				$lmtttmpts_options['email_subject'] = stripslashes( $_POST['lmtttmpts_email_subject'] );
			}
			/*Updating email message text that sent when address automatically blocked*/
			if ( isset( $_POST['lmtttmpts_email_blocked'] ) ) {
				$lmtttmpts_options['email_blocked'] = stripslashes( $_POST['lmtttmpts_email_blocked'] );
			}
			/*Updating email message text that sent when address automatically blacklisted*/
			if ( isset( $_POST['lmtttmpts_email_blacklisted'] ) ) {
				$lmtttmpts_options['email_blacklisted'] = stripslashes( $_POST['lmtttmpts_email_blacklisted'] );
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
			/*Updating options in wp_options table*/
			if ( 1 == $wpmu ) {
				update_site_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			} else {
				update_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			}
			/* Finish updating and verification options from Settings form */ 
		}
		/*Realization restoring default options*/
		if ( isset( $_POST['lmtttmpts_return_default'] ) && check_admin_referer( plugin_basename(__FILE__), 'lmtttmpts_nonce_name' ) ) {
			if ( 'message_invalid_attempts' == $_POST['lmtttmpts_return_default'] ) {
				$lmtttmpts_options['failed_message'] = $lmtttmpts_options['failed_message_default'];
			} elseif ( 'message_blocked_user' == $_POST['lmtttmpts_return_default'] ) {
				$lmtttmpts_options['blocked_message'] = $lmtttmpts_options['blocked_message_default'];
			} elseif ( 'message_blacklisted_user' == $_POST['lmtttmpts_return_default'] ) {
				$lmtttmpts_options['blacklisted_message'] = $lmtttmpts_options['blacklisted_message_default'];
			} elseif ( 'email_subject' == $_POST['lmtttmpts_return_default'] ) {
				$lmtttmpts_options['email_subject'] = $lmtttmpts_options['email_subject_default'];
			} elseif ( 'email_user_blocked' == $_POST['lmtttmpts_return_default'] ) {
				$lmtttmpts_options['email_blocked'] = $lmtttmpts_options['email_blocked_default'];
			} elseif ( 'email_user_blacklisted' == $_POST['lmtttmpts_return_default'] ) {
				$lmtttmpts_options['email_blacklisted'] = $lmtttmpts_options['email_blacklisted_default'];
			}
			if ( 1 == $wpmu ) {
				update_site_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			} else {
				update_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			}
		}
		if ( isset( $_POST['lmtttmpts_options_for_block_message'] ) && check_admin_referer( plugin_basename(__FILE__), 'lmtttmpts_nonce_name' ) ) {
			$lmtttmpts_options['options_for_block_message'] = $_POST['lmtttmpts_options_for_block_message'];
			if ( 1 == $wpmu ) {
				update_site_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			} else {
				update_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			}
		}
		if ( isset( $_POST['lmtttmpts_options_for_email_message'] ) && check_admin_referer( plugin_basename(__FILE__), 'lmtttmpts_nonce_name' ) ) {
			$lmtttmpts_options['options_for_email_message'] = $_POST['lmtttmpts_options_for_email_message'];
			if ( 1 == $wpmu ) {
				update_site_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			} else {
				update_option( 'lmtttmpts_options', $lmtttmpts_options, '', 'yes' );
			}
		}
		/*Realization action in table with blocked addresses*/
		if ( isset( $_GET['lmtttmpts_reset_block'] ) && check_admin_referer( 'lmtttmpts_reset_block_' . $_GET['lmtttmpts_reset_block'], 'lmtttmpts_nonce_name' ) )
			lmtttmpts_reset_block( $_GET['lmtttmpts_reset_block'] );
		/*Realization bulk action in table with blocked addresses*/
		if ( ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'reset_blocks' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'reset_blocks' ) ) && isset ( $_POST['ip'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' )	) {
			$ips = ( $_POST['ip'] );
			foreach ( $ips as $ip ) {
				lmtttmpts_reset_block( $ip );
			}
			unset( $ip );
		}
		/*Realization of added to blacklist*/
		if ( isset( $_POST['lmtttmpts_add_to_blacklist'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) 
			&& ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ) ) ) )
			lmtttmpts_add_ip_to_blacklist( str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ) );
		/*Realization action in blacklist table*/
		if ( isset( $_GET['lmtttmpts_remove_from_blacklist'] ) && check_admin_referer( 'lmtttmpts_remove_from_blacklist_' . $_GET['lmtttmpts_remove_from_blacklist'], 'lmtttmpts_nonce_name' ) )
			lmtttmpts_delete_ip_from_blacklist( $_GET['lmtttmpts_remove_from_blacklist'] );
		/*Realization bulk action in blacklist table*/
		if ( ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'remove_from_blacklist_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_blacklist_ips' ) ) && isset ( $_POST['ip'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
			$ips = ( $_POST['ip'] );
			foreach ( $ips as $ip ) {
				lmtttmpts_delete_ip_from_blacklist( $ip );
			}
			unset( $ip );
		}
		/*Realization of added to whitelist*/
		if ( isset( $_POST['lmtttmpts_add_to_whitelist'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) 
			&& ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ) ) ) )
			lmtttmpts_add_ip_to_whitelist( str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ) );
		/*Realization action in whitelist table*/
		if ( isset( $_GET['lmtttmpts_remove_from_whitelist'] ) && check_admin_referer( 'lmtttmpts_remove_from_whitelist_' . $_GET['lmtttmpts_remove_from_whitelist'], 'lmtttmpts_nonce_name' ) )
			lmtttmpts_delete_ip_from_whitelist( $_GET['lmtttmpts_remove_from_whitelist'] ); 
		/*Realization bulk action in whitelist table*/
		if ( ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'remove_from_whitelist_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_whitelist_ips' ) ) && isset ( $_POST['ip'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
			$ips = ( $_POST['ip'] );
			foreach ( $ips as $ip ) {
				lmtttmpts_delete_ip_from_whitelist( $ip );
			}
			unset( $ip );
		}

		/* GO PRO */
		if ( isset( $_GET['tab'] ) && 'go_pro' == $_GET['tab'] ) {
			global $bstwbsftwppdtplgns_options;
			$error = $message = "";
			$bws_license_key = ( isset( $_POST['bws_license_key'] ) ) ? trim( esc_html( $_POST['bws_license_key'] ) ) : "";

			if ( isset( $_POST['bws_license_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_license_nonce_name' ) ) {
				if ( '' != $bws_license_key ) { 
					if ( strlen( $bws_license_key ) != 18 ) {
						$error = __( "Wrong license key", 'lmtttmpts' );
					} else {
						$bws_license_plugin = stripslashes( esc_html( $_POST['bws_license_plugin'] ) );
						if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] ) && $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] < ( time() + (24 * 60 * 60) ) ) {
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] + 1;
						} else {
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = 1;
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] = time();
						}	

						/* download Pro */						
						if ( ! function_exists( 'is_plugin_active_for_network' ) )
							require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
						$active_plugins = get_option( 'active_plugins' );
						
						if ( ! array_key_exists( $bws_license_plugin, $all_plugins ) ) {
							$current = get_site_transient( 'update_plugins' );
							if ( is_array( $all_plugins ) && !empty( $all_plugins ) && isset( $current ) && is_array( $current->response ) ) {
								$to_send = array();
								$to_send["plugins"][ $bws_license_plugin ] = array();
								$to_send["plugins"][ $bws_license_plugin ]["bws_license_key"] = $bws_license_key;
								$to_send["plugins"][ $bws_license_plugin ]["bws_illegal_client"] = true;
								$options = array(
									'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3 ),
									'body' => array( 'plugins' => serialize( $to_send ) ),
									'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );
								$raw_response = wp_remote_post( 'http://bestwebsoft.com/wp-content/plugins/paid-products/plugins/update-check/1.0/', $options );

								if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) ) {
									$error = __( "Something went wrong. Try again later. If the error will appear again, please, contact us <a href=http://support.bestwebsoft.com>BestWebSoft</a>. We are sorry for inconvenience.", 'lmtttmpts' );
								} else {
									$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );
									
									if ( is_array( $response ) && !empty( $response ) ) {
										foreach ( $response as $key => $value ) {
											if ( "wrong_license_key" == $value->package ) {
												$error = __( "Wrong license key", 'lmtttmpts' ); 
											} elseif ( "wrong_domain" == $value->package ) {
												$error = __( "This license key is bind to another site", 'lmtttmpts' );
											} else if ( "time_out" == $value->package ) {
												$message = __( 'This license key is valid, but Your license has expired. If you want to use our plugin in future, you should extend the license.', 'lmtttmpts' );
											} elseif ( "you_are_banned" == $value->package ) {
												$error = __( "Unfortunately, you have exceeded the number of available tries. Please, upload the plugin manually.", 'lmtttmpts' );
											}
										}
										if ( '' == $error ) {
											$bstwbsftwppdtplgns_options[ $bws_license_plugin ] = $bws_license_key;

											$url = 'http://bestwebsoft.com/wp-content/plugins/paid-products/plugins/downloads/?bws_first_download=' . $bws_license_plugin . '&bws_license_key=' . $bws_license_key . '&download_from=5';
											$uploadDir = wp_upload_dir();
											$zip_name = explode( '/', $bws_license_plugin );
										    if ( file_put_contents( $uploadDir["path"] . "/" . $zip_name[0] . ".zip", file_get_contents( $url ) ) ) {
										    	@chmod( $uploadDir["path"] . "/" . $zip_name[0] . ".zip", octdec( 755 ) );
										    	if ( class_exists( 'ZipArchive' ) ) {
													$zip = new ZipArchive();
													if ( $zip->open( $uploadDir["path"] . "/" . $zip_name[0] . ".zip" ) === TRUE ) {
														$zip->extractTo( WP_PLUGIN_DIR );
														$zip->close();
													} else {
														$error = __( "Failed to open the zip archive. Please, upload the plugin manually", 'lmtttmpts' );
													}								
												} elseif ( class_exists( 'Phar' ) ) {
													$phar = new PharData( $uploadDir["path"] . "/" . $zip_name[0] . ".zip" );
													$phar->extractTo( WP_PLUGIN_DIR );
												} else {
													$error = __( "Your server does not support either ZipArchive or Phar. Please, upload the plugin manually", 'lmtttmpts' );
												}
												@unlink( $uploadDir["path"] . "/" . $zip_name[0] . ".zip" );										    
											} else {
												$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'lmtttmpts' );
											}

											/* activate Pro */
											if ( file_exists( WP_PLUGIN_DIR . '/' . $zip_name[0] ) ) {			
												array_push( $active_plugins, $bws_license_plugin );
												update_option( 'active_plugins', $active_plugins );
												$pro_plugin_is_activated = true;
											} elseif ( '' == $error ) {
												$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'lmtttmpts' );
											}																				
										}
									} else {
										$error = __( "Something went wrong. Try again later or upload the plugin manually. We are sorry for inconvienience.", 'lmtttmpts' ); 
					 				}
					 			}
				 			}
						} else {
							/* activate Pro */
							if ( ! ( in_array( $bws_license_plugin, $active_plugins ) || is_plugin_active_for_network( $bws_license_plugin ) ) ) {			
								array_push( $active_plugins, $bws_license_plugin );
								update_option( 'active_plugins', $active_plugins );
								$pro_plugin_is_activated = true;
							}						
						}
						update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options, '', 'yes' );
			 		}
			 	} else {
		 			$error = __( "Please, enter Your license key", 'lmtttmpts' );
		 		}
		 	}
		}
		/* Clear Log */
		if ( isset( $_POST['lmtttmpts_clear_log_complete_confirm'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
			lmtttmpts_clear_log_completely();
		} 
		if ( ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'clear_log_for_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'clear_log_for_ips' ) ) && isset ( $_POST['ip'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
			$ips = ( $_POST['ip'] );
			foreach ( $ips as $ip ) {
				lmtttmpts_clear_log( $ip );
			}
			unset( $ip );
		} ?>
		<div class="wrap">
			<h2><?php _e( 'Limit Attempts Settings', 'lmtttmpts' ); ?></h2>
			<div id="lmtttmpts_settings_notice" class="updated fade" style="display:none">
				<p><strong><?php _e( "Notice:", 'lmtttmpts' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'lmtttmpts' ); ?></p>
			</div>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['tab'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php"><?php _e( 'Settings', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['tab'] ) && 'blocked' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;tab=blocked"><?php _e( 'Blocked addresses', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['tab'] ) && 'blacklist' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;tab=blacklist"><?php _e( 'Blacklist', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['tab'] ) && 'whitelist' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;tab=whitelist"><?php _e( 'Whitelist', 'lmtttmpts' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['tab'] ) && 'log' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;tab=log"><?php _e( 'Log', 'lmtttmpts' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/plugin/limit-attempts/#faq" target="_blank"><?php _e( 'FAQ', 'lmtttmpts' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;tab=go_pro"><?php _e( 'Go PRO', 'lmtttmpts' ); ?></a>
			</h2>			
			<?php if ( ! isset( $_GET['tab'] ) ) { /* Showing settings tab */?>
				<div id="lmtttmpts_settings">
					<form method="post" action="admin.php?page=limit-attempts.php">
						<table id="lmtttmpts_lock_options" class="form-table">
							<tr>
								<th><?php _e( 'Lock options:', 'lmtttmpts') ?></th>
								<td>
									<?php _e( 'Block address for' ) ?> <input type="text" size="3" maxlength="3" value="<?php echo $lmtttmpts_options['days_of_lock'] ; ?>" name="lmtttmpts_days_of_lock" /> <?php _e( 'days', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['hours_of_lock'] ; ?>" name="lmtttmpts_hours_of_lock" /> <?php _e( 'hours', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['minutes_of_lock'] ; ?>" name="lmtttmpts_minutes_of_lock" /><?php printf( __( 'minutes', 'lmtttmpts' ) . '&nbsp;</br>' . __( 'after', 'lmtttmpts' ) ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['allowed_retries'] ; ?>" name="lmtttmpts_allowed_retries" /> <?php printf( __( 'failed attempts', 'lmtttmpts' ) . '&nbsp;' . __( 'per', 'lmtttmpts' ) )?> <input type="text" size="3" maxlength="3" value="<?php echo $lmtttmpts_options['days_to_reset'] ; ?>" name="lmtttmpts_days_to_reset" /> <?php _e( 'days', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['hours_to_reset'] ; ?>" name="lmtttmpts_hours_to_reset" /> <?php _e( 'hours', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['minutes_to_reset'] ; ?>" name="lmtttmpts_minutes_to_reset" /> <?php _e( 'minutes', 'lmtttmpts' ); ?>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Block options:', 'lmtttmpts') ?></th>
								<td>
									<?php _e( 'Add to the blacklist after', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['allowed_locks'] ; ?>" name="lmtttmpts_allowed_locks" /> <?php _e( 'blocks per', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="3" value="<?php echo $lmtttmpts_options['days_to_reset_block'] ; ?>" name="lmtttmpts_days_to_reset_block" /> <?php _e( 'days', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['hours_to_reset_block'] ; ?>" name="lmtttmpts_hours_to_reset_block" /> <?php _e( 'hours', 'lmtttmpts' ) ?> <input type="text" size="3" maxlength="2" value="<?php echo $lmtttmpts_options['minutes_to_reset_block'] ; ?>" name="lmtttmpts_minutes_to_reset_block" /> <?php _e( 'minutes', 'lmtttmpts' ) ?><br />
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Remove log entry in case no failed attempts occurred for', 'lmtttmpts' ) ?></th>
								<td>
									<input type="text" size="3" maxlength="3" value="<?php echo $lmtttmpts_options['days_to_clear_log']; ?>" name="lmtttmpts_days_to_clear_log" /> <?php _e( 'days', 'lmtttmpts' ) ?><br/>
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Set "0" in order not to clear the log.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<th><?php _e( 'Additonal options for form messages', 'lmtttmpts' ) ?></th>
							<td>
								<button id="lmtttmpts_hide_options_for_block_message_button" class="button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'hide' == $lmtttmpts_options['options_for_block_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_block_message" value="hide"><?php _e( 'Hide', 'lmtttmpts' ) ?></button>
								<button id="lmtttmpts_show_options_for_block_message_button" class="button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'show' == $lmtttmpts_options['options_for_block_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_block_message" value="show"><?php _e( 'Show', 'lmtttmpts' ) ?></button>
								<input type="button" id="lmtttmpts_show_options_for_block_message" class="lmtttmpts_hidden button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'show' == $lmtttmpts_options['options_for_block_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_block_message" value="<?php _e( 'Show', 'lmtttmpts' ) ?>">
								<input type="button" id="lmtttmpts_hide_options_for_block_message" class="lmtttmpts_hidden button-secondary " <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'hide' == $lmtttmpts_options['options_for_block_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_block_message" value="<?php _e( 'Hide', 'lmtttmpts' ) ?>">
							</td>
						</table>
						<h3 id="lmtttmpts_nav_tab_message_no_js" class="nav-tab-wrapper lmtttmpts_block_message_block <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'hide' == $lmtttmpts_options['options_for_block_message'] ) echo "lmtttmpts_hidden" ?>">
							<a class="nav-tab<?php if ( ! isset( $_GET['login_error_tab'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php"><?php _e( 'Error message for invalid attempt', 'lmtttmpts' ); ?></a>
							<a class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blocked' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;login_error_tab=blocked"><?php _e( 'Error message for blocked user', 'lmtttmpts' ); ?></a>
							<a class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blacklisted' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;login_error_tab=blacklisted"><?php _e( 'Error message for blacklisted user', 'lmtttmpts' ); ?></a>
						</h3>
						<h3 id="lmtttmpts_nav_tab_message_js" style="display:none" class="nav-tab-wrapper lmtttmpts_block_message_block <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'hide' == $lmtttmpts_options['options_for_block_message'] ) echo "lmtttmpts_hidden" ?>">
							<p id="lmtttmpts_message_invalid_attempt" style="cursor:pointer" class="nav-tab<?php if ( ! isset( $_GET['login_error_tab'] ) ) echo ' nav-tab-active'; ?>" ><?php _e( 'Error message for invalid attempt', 'lmtttmpts' ); ?></p>
							<p id="lmtttmpts_message_blocked" style="cursor:pointer" class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blocked' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" ><?php _e( 'Error message for blocked user', 'lmtttmpts' ); ?></p>
							<p id="lmtttmpts_message_blacklisted" style="cursor:pointer" class="nav-tab<?php if ( isset( $_GET['login_error_tab'] ) && 'blacklisted' == $_GET['login_error_tab'] ) echo ' nav-tab-active'; ?>" ><?php _e( 'Error message for blacklisted user', 'lmtttmpts' ); ?></p>
						</h3>
						<table class="form-table lmtttmpts_block_message_block <?php if ( isset( $lmtttmpts_options['options_for_block_message'] ) && 'hide' == $lmtttmpts_options['options_for_block_message'] ) echo "lmtttmpts_hidden" ?>">
							<tr id="lmtttmpts_message_invalid_attempt_area" <?php if ( isset( $_GET['login_error_tab'] ) ) echo 'class="lmtttmpts_hidden"' ?>>
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ); ?></p>
									<ul>
										<li>
											'%ATTEMPTS%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display quantity of allowed attempts', 'lmtttmpts' ); ?>)</span>
										</li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="message_invalid_attempts"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
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
										<li>
											'%DATE%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display date when block is removed', 'lmtttmpts' ); ?>)</span>
										</li>
										<li>
											'%MAIL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display administrator&rsquo;s e-mail for feedback', 'lmtttmpts' ); ?>)</span>
										</li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="message_blocked_user"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
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
										<li>
											'%MAIL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display administrators e-mail for feedback', 'lmtttmpts' ); ?>)</span>
										</li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="message_blacklisted_user"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="5" cols="100" name="lmtttmpts_blacklisted_message"><?php echo $lmtttmpts_options['blacklisted_message'] ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>							 
						</table>
						<table id="lmtttmpts_notify_options" class="form-table">
							<tr>
								<th><?php _e( 'Send mail with notification', 'lmtttmpts' ) ?></th>
								<td style="width:15px" class="lmtttmpts_align_top">
									<input id="lmtttmpts_notify_email_options" type="checkbox" name="lmtttmpts_notify_email" value="1" <?php if ( $lmtttmpts_options['notify_email'] ) echo "checked=\"checked\" " ?>/><br />
								</td>
								<td class="lmtttmpts_align_top lmtttmpts_notify_email_block <?php if ( isset( $lmtttmpts_options['notify_email'] ) && false === $lmtttmpts_options['notify_email'] ) echo "lmtttmpts_hidden" ?>" style="max-width:150px;">
									<input type="radio" id="lmtttmpts_user_mailto" name="lmtttmpts_mailto" value="admin" <?php if ( isset( $lmtttmpts_options['mailto'] ) && $lmtttmpts_options['mailto'] == 'admin' ) echo "checked=\"checked\" " ?>/><?php _e( 'Email to user\'s address', 'lmtttmpts' ) ?>
									<select name="lmtttmpts_user_email_address" onfocus="document.getElementById('lmtttmpts_user_mailto').checked = true;">
										<option disabled><?php _e( "Choose a username", 'contact_form' ); ?></option>
										<?php foreach ( $userslogin as $key => $value ) {
											if ( $value->data->user_email != '' ) { ?>
												<option value="<?php echo $value->data->user_email; ?>" <?php if ( $value->data->user_email == $lmtttmpts_options['email_address'] ) echo "selected=\"selected\" "; ?>><?php echo $value->data->user_login; ?></option>
											<?php }
										} ?>
									</select></br>
									<input type="radio" id="lmtttmpts_custom_mailto" name="lmtttmpts_mailto" value="custom" <?php if ( isset( $lmtttmpts_options['mailto'] ) && $lmtttmpts_options['mailto'] == 'custom' ) echo "checked=\"checked\" " ?>/><?php _e( 'Email to another address', 'lmtttmpts' ) ?> <input type="email" name="lmtttmpts_email_address" value="<?php if( $lmtttmpts_options['mailto'] == 'custom' ) echo $lmtttmpts_options['email_address']; ?>" onfocus="document.getElementById('lmtttmpts_custom_mailto').checked = true;"/>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Additonal options for email with notification', 'lmtttmpts' ) ?></th>
								<td>
									<button id="lmtttmpts_hide_options_for_email_message_button" class="button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'hide' == $lmtttmpts_options['options_for_email_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_email_message" value="hide"><?php _e( 'Hide', 'lmtttmpts' ) ?></button>
									<button id="lmtttmpts_show_options_for_email_message_button" class="button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'show' == $lmtttmpts_options['options_for_email_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_email_message" value="show"><?php _e( 'Show', 'lmtttmpts' ) ?></button>
									<input type="button" id="lmtttmpts_show_options_for_email_message" class="lmtttmpts_hidden button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'show' == $lmtttmpts_options['options_for_email_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_email_message" value="<?php _e( 'Show', 'lmtttmpts' ) ?>" />
									<input type="button" id="lmtttmpts_hide_options_for_email_message" class="lmtttmpts_hidden button-secondary" <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'hide' == $lmtttmpts_options['options_for_email_message'] ) echo 'style="display: none;"' ?> name="lmtttmpts_options_for_email_message" value="<?php _e( 'Hide', 'lmtttmpts' ) ?>" />
								</td>
								<td></td>
							</tr>
						</table>

						<h3 id="lmtttmpts_nav_tab_email_no_js_a" class="nav-tab-wrapper lmtttmpts_email_message_block <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'hide' == $lmtttmpts_options['options_for_email_message'] ) echo "lmtttmpts_hidden" ?>">
							<a class="nav-tab<?php if ( ! isset( $_GET['email_error_tab'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php"><?php _e( 'Email to admistrator when user is blocked', 'lmtttmpts' ); ?></a>
							<a class="nav-tab<?php if ( isset( $_GET['email_error_tab'] ) && 'blacklisted' == $_GET['email_error_tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=limit-attempts.php&amp;email_error_tab=blacklisted"><?php _e( 'Email to admistrator when user is blacklisted', 'lmtttmpts' ); ?></a>
						</h3>
						<h3 id="lmtttmpts_nav_tab_email_js_a" style="display:none" class="nav-tab-wrapper lmtttmpts_email_message_block <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'hide' == $lmtttmpts_options['options_for_email_message'] ) echo "lmtttmpts_hidden" ?>">
							<p id="lmtttmpts_email_blocked" class="nav-tab<?php if ( ! isset( $_GET['email_error_tab'] ) ) echo ' nav-tab-active'; ?>" ><?php _e( 'Email to admistrator when user is blocked', 'lmtttmpts' ); ?></p>
							<p id="lmtttmpts_email_blacklisted" class="nav-tab<?php if ( isset( $_GET['email_error_tab'] ) && 'blacklisted' == $_GET['email_error_tab'] ) echo ' nav-tab-active'; ?>" ><?php _e( 'Email to admistrator when user is blacklisted', 'lmtttmpts' ); ?></p>
						</h3>
						<table class="form-table lmtttmpts_email_message_block <?php if ( isset( $lmtttmpts_options['options_for_email_message'] ) && 'hide' == $lmtttmpts_options['options_for_email_message'] ) echo "lmtttmpts_hidden" ?>">
							<tr>
								<th><?php _e( 'Email subject', 'lmtttmpts' ) ?></th>
							</tr>
							<tr>
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p>
									<ul>
										<li>
											'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display blocked ip address', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span>
										</li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_subject"><?php _e( 'Restore default subject', 'lmtttmpts' ) ?></button>
								</td>
								<td>
									<textarea rows="1" cols="100" name="lmtttmpts_email_subject"><?php echo $lmtttmpts_options['email_subject']; ?></textarea><br />
									<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'You can use standart HTML tags and attributes.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Email message', 'lmtttmpts' ) ?></th>
							</tr>							
							<tr id="lmtttmpts_email_blocked_area" <?php if ( isset( $_GET['email_error_tab'] ) ) echo 'class="lmtttmpts_hidden"' ?> >
								<td>
									<p><?php _e( 'Allowed Variables:', 'lmtttmpts' ) ?></p>
									<ul>
										<li>
											'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display ip address that is blocked', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%PLUGIN_LINK%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display link for Limit Attempts plugin on your site', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%WHEN%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display date and time when ip address was blocked or blacklisted', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%SITE_URL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( "display your site's URL", 'lmtttmpts' ) ?>)</span>
										</li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_user_blocked"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
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
										<li>
											'%IP%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display ip address that is blocked', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%PLUGIN_LINK%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display link for Limit Attempts plugin on your site', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%WHEN%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display date and time when ip address was blocked or blacklisted', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%SITE_NAME%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( 'display name of your site', 'lmtttmpts' ) ?>)</span>
										</li>
										<li>
											'%SITE_URL%' <span class="lmtttmpts_little lmtttmpts_grey">(<?php _e( "display your site's URL", 'lmtttmpts' ) ?>)</span>
										</li>
									</ul>
									<button class="button-secondary" name="lmtttmpts_return_default" value="email_user_blacklisted"><?php _e( 'Restore default message', 'lmtttmpts' ) ?></button>
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
									<?php if ( array_key_exists( 'htaccess/htaccess.php', $all_plugins ) ) {
										if ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $active_plugins ) ) || is_plugin_active_for_network( 'htaccess/htaccess.php' ) ) { 
											if ( isset( $all_plugins['htaccess/htaccess.php']['Version'] ) && $all_plugins['htaccess/htaccess.php']['Version'] >= '1.4' ) { ?>
												<input type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo "checked=\"checked\""; ?> />
												<span style="color: #888888;font-size: 10px;"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=htaccess.php">Htaccess</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>)</span>
											<?php } else { ?>
												<input disabled="disabled" type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo "checked=\"checked\""; ?> />
												<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Htaccess powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Update Htaccess at least to v.1.4', 'lmtttmpts' ); ?></a></span>
											<?php }
										} else { ?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" <?php if ( isset( $lmtttmpts_options["block_by_htaccess"] ) ) echo "checked=\"checked\""; ?> />
											<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Htaccess powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Activate Htaccess', 'lmtttmpts' ); ?></a></span>
										<?php }
									} else { ?>
										<input disabled="disabled" type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" />
										<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Htaccess powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="http://bestwebsoft.com/plugin/htaccess/"><?php _e( 'Download Htaccess', 'lmtttmpts' ); ?></a></span>
									<?php } ?>
									<br /><span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Allow Htaccess plugin block ip to reduce the database workload.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Captcha plugin', 'lmtttmpts' ) ?></th>
								<td>
									<?php if ( array_key_exists( 'captcha/captcha.php' , $all_plugins ) || array_key_exists( 'captcha-pro/captcha_pro.php' , $all_plugins ) ) {
										if ( 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) || is_plugin_active_for_network( 'captcha/captcha.php' ) || 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) || is_plugin_active_for_network( 'captcha-pro/captcha_pro.php' ) ) {
											if ( 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) || is_plugin_active_for_network( 'captcha-pro/captcha_pro.php' ) ) { 
												if ( isset( $all_plugins['captcha-pro/captcha_pro.php']['Version'] ) && $all_plugins['captcha-pro/captcha_pro.php']['Version'] >= '1.4.4' ) { ?>
													<!-- Checkbox for Login form captcha checking -->
													<label>
														<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options['login_form_captcha_check'] ) ) echo "checked=\"checked\""; ?> />
														<span><?php _e( 'Login form', 'lmtttmpts' ); ?></span>
													</label>
													<span style="color: #888888;font-size: 10px;"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=captcha_pro.php">Captcha Pro</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>)</span>
												<?php } else { ?>
													<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options["login_form_captcha_check"] ) ) echo "checked=\"checked\""; ?> />
													<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Captcha Pro powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Update Captcha Pro at least to v.1.4.4', 'lmtttmpts' ); ?></a></span>
												<?php }
											} else { 
												if ( isset( $all_plugins['captcha/captcha.php']['Version'] ) && $all_plugins['captcha/captcha.php']['Version'] >= '4.0.2' ) { ?>
													<label>
														<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options['login_form_captcha_check'] ) ) echo "checked=\"checked\""; ?> />
														<span><?php _e( 'Login form', 'lmtttmpts' ); ?></span>
													</label>
													<span style="color: #888888;font-size: 10px;"> (<?php _e( 'Using', 'lmtttmpts' ); ?> <a href="admin.php?page=captcha.php">Captcha</a> <?php _e( 'powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>)</span>
												<?php } else { ?>
													<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options["login_form_captcha_check"] ) ) echo "checked=\"checked\""; ?> />
													<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Captcha powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Update Captcha at least to v.4.0.2', 'lmtttmpts' ); ?></a></span>
												<?php }
											}
										} else { ?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $lmtttmpts_options["login_form_captcha_check"] ) ) echo "checked=\"checked\""; ?> />
											<?php if ( array_key_exists( 'captcha-pro/captcha_pro.php' , $all_plugins ) ) { ?>
												<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Captcha Pro powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Activate Captcha Pro', 'lmtttmpts' ); ?></a></span>
											<?php } else { ?>
												<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Captcha powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="<?php echo bloginfo("url"); ?>/wp-admin/plugins.php"><?php _e( 'Activate Captcha', 'lmtttmpts' ); ?></a></span>
											<?php }
										}
									} else { ?>
										<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" />
										<span style="color: #888888;font-size: 10px;">(<?php _e( 'Using Captcha powered by', 'lmtttmpts' ); ?> <a href="http://bestwebsoft.com/plugin/">bestwebsoft.com</a>) <a href="http://bestwebsoft.com/plugin/captcha-plugin/"><?php _e( 'Download Captcha', 'lmtttmpts' ); ?></a></span>
									<?php } ?>
									<br /><span class="lmtttmpts_little lmtttmpts_grey"><?php _e( 'Consider the incorrect captcha input as an invalid attempt.', 'lmtttmpts' ) ?></span>
								</td>
							</tr>
						</table>
						<input type="hidden" name="lmtttmpts_form_submit" value="submit" />
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'lmtttmpts' ) ?>" />
						</p>
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'blocked' == $_GET['tab'] ) { /*Showing blocked list table using wp_list_table class*/ ?>
				<div id="lmtttmpts_blocked">
					<?php $lmtttmpts_blocked_list = new Lmtttmpts_Blocked_list();
					$lmtttmpts_blocked_list->prepare_items(); ?>
					<form method="get" action="admin.php">
						<?php $lmtttmpts_blocked_list->search_box( __( 'Search ip', 'lmtttmpts' ), 'search_bkocked_ip' );?>
						<input type="hidden" name="page" value="limit-attempts.php" />
						<input type="hidden" name="tab" value="blocked" />
					</form>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=blocked">
						<?php $lmtttmpts_blocked_list->display();
						wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'blacklist' == $_GET['tab'] ) { /*Showing blacklist table using wp_list_table class*/ ?>
				<div id="lmtttmpts_blacklist">
					<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=blacklist">
						<td><input type="text" maxlength="31" name="lmtttmpts_add_to_blacklist" /></td>
						<td><input type="submit" class="button-secondary" value="<?php _e( 'Add IP to blacklist', 'lmtttmpts' ) ?>" /></td>
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
					</form>
					<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( "Allowed formats:", 'lmtttmpts' ) ?> <code>192.168.0.1</code></span>
					<?php $lmtttmpts_blacklist_table = new Lmtttmpts_Blacklist();
					$lmtttmpts_blacklist_table->prepare_items(); ?>
					<form method="get" action="admin.php">
						<?php $lmtttmpts_blacklist_table->search_box( __( 'Search ip', 'lmtttmpts' ), 'search_bkocked_ip' ); ?>
						<input type="hidden" name="page" value="limit-attempts.php" />
						<input type="hidden" name="tab" value="blacklist" />
					</form>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=blacklist">
						<?php $lmtttmpts_blacklist_table->display();
						wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'whitelist' == $_GET['tab'] ) { /*Showing whitelist table using wp_list_table class*/ ?>
				<div id="lmtttmpts_whitelist">
					<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=whitelist">
						<td><input type="text" maxlength="31" name="lmtttmpts_add_to_whitelist" /></td>
						<td><input type="submit" class="button-secondary" value="<?php _e( 'Add IP to whitelist', 'lmtttmpts' ) ?>" /></td>
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
					</form>
					<span class="lmtttmpts_little lmtttmpts_grey"><?php _e( "Allowed formats:", 'lmtttmpts' ) ?> <code>192.168.0.1</code></span>
					<?php $lmtttmpts_whitelist_table = new Lmtttmpts_Whitelist();
					$lmtttmpts_whitelist_table->prepare_items(); ?>
					<form method="get" action="admin.php">
						<?php $lmtttmpts_whitelist_table->search_box( __( 'Search ip', 'lmtttmpts' ), 'search_whitelisted_ip' ); ?>
						<input type="hidden" name="page" value="limit-attempts.php" />
						<input type="hidden" name="tab" value="whitelist" />
					</form>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=whitelist">
						<?php $lmtttmpts_whitelist_table->display();
						wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
					</form>
				</div>
			<?php } elseif ( 'log' == $_GET['tab'] ) { /*Showing log table using wp_list_table class*/ 
				if ( isset( $_POST['lmtttmpts_clear_log_complete'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) { ?>
					<div id="lmtttmpts_clear_log_confirm">
						<p><?php _e( 'Are you sure you want to delete all log entries?', 'lmtttmpts' ) ?></p>
						<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=log">
							<button class="button" name="lmtttmpts_clear_log_complete_confirm"><?php _e( 'Yes, delete these entries', 'lmtttmpts' ) ?></button>
							<button class="button" name="lmtttmpts_clear_log_complete_deny"><?php _e( 'No, go back to the Log page', 'lmtttmpts' ) ?></button>
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
						</form>
					</div>
				<?php } else { ?>
					<div id="lmtttmpts_log">
						<?php $lmtttmpts_log_list = new Lmtttmpts_Log();
						$lmtttmpts_log_list->prepare_items(); ?>
						<form method="get" action="admin.php">
							<?php $lmtttmpts_log_list->search_box( __( 'Search ip', 'lmtttmpts' ), 'search_logged_ip' ); ?>
							<input type="hidden" name="page" value="limit-attempts.php" />
							<input type="hidden" name="tab" value="log" />
						</form>
						<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=log">
							<input type="hidden" name="lmtttmpts_clear_log_complete" />
							<input type="submit" class="button" value="<?php _e( 'Clear Log', 'lmtttmpts' ) ?>" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
						</form>
						<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=log">
							<?php $lmtttmpts_log_list->display(); 
							wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
						</form>
					</div>
				<?php }
			} elseif ( 'go_pro' == $_GET['tab'] ) { ?>
				<div class="updated fade" <?php if ( empty( $message ) || "" != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
				<div class="error" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
				<?php if ( isset( $pro_plugin_is_activated ) && true === $pro_plugin_is_activated ) { ?>
					<script type="text/javascript">
						window.setTimeout( function() {
						    window.location.href = 'admin.php?page=limit-attempts.php';
						}, 5000 );
					</script>				
					<p><?php _e( "Congratulations! The PRO version of the plugin is successfully download and activated.", 'lmtttmpts' ); ?></p>
					<p>
						<?php _e( "Please, go to", 'lmtttmpts' ); ?> <a href="admin.php?page=limit-attempts.php"><?php _e( 'the setting page', 'lmtttmpts' ); ?></a> 
						(<?php _e( "You will be redirected automatically in 5 seconds.", 'lmtttmpts' ); ?>)
					</p>
				<?php } else { ?>
					<form method="post" action="admin.php?page=limit-attempts.php&amp;tab=go_pro">
						<p>
							<?php _e( 'You can download and activate', 'lmtttmpts' ); ?> 
							<a href="http://bestwebsoft.com/plugin/limit-attempts/?k=fdac994c203b41e499a2818c409ff2bc&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Limit Attempts Pro">PRO</a> 
							<?php _e( 'version of this plugin by entering Your license key.', 'lmtttmpts' ); ?><br />
							<span style="color: #888888;font-size: 10px;">
								<?php _e( 'You can find your license key on your personal page Client area, by clicking on the link', 'lmtttmpts' ); ?> 
								<a href="http://bestwebsoft.com/wp-login.php">http://bestwebsoft.com/wp-login.php</a> 
								<?php _e( '(your username is the email you specify when purchasing the product).', 'lmtttmpts' ); ?>
							</span>
						</p>
						<?php if ( isset( $bstwbsftwppdtplgns_options['go_pro']['limit-attempts-pro/limit-attempts-pro.php']['count'] ) &&
							'5' < $bstwbsftwppdtplgns_options['go_pro']['limit-attempts-pro/limit-attempts-pro.php']['count'] &&
							$bstwbsftwppdtplgns_options['go_pro']['limit-attempts-pro/limit-attempts-pro.php']['time'] < ( time() + ( 24 * 60 * 60 ) ) ) { ?>
							<p>
								<input disabled="disabled" type="text" name="bws_license_key" value="<?php echo $bws_license_key; ?>" />
								<input disabled="disabled" type="submit" class="button-primary" value="<?php _e( 'Activate', 'lmtttmpts' ); ?>" />
							</p>
							<p>
								<?php _e( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'lmtttmpts' ); ?>
							</p>
						<?php } else { ?>
							<p>
								<input type="text" name="bws_license_key" value="<?php echo $bws_license_key; ?>" />
								<input type="hidden" name="bws_license_plugin" value="limit-attempts-pro/limit-attempts-pro.php" />
								<input type="hidden" name="bws_license_submit" value="submit" />
								<input type="submit" class="button-primary" value="<?php _e( 'Activate', 'lmtttmpts' ); ?>" />
								<?php wp_nonce_field( plugin_basename(__FILE__), 'bws_license_nonce_name' ); ?>
							</p>
						<?php } ?>
					</form>
				<?php }
			} ?>
			<div class="bws-plugin-reviews">
				<div class="bws-plugin-reviews-rate">
					<?php _e( 'If you enjoy our plugin, please give it 5 stars on WordPress', 'lmtttmpts' ); ?>:
					<a href="http://wordpress.org/support/view/plugin-reviews/limit-attempts" target="_blank" title="Limit Attempts"><?php _e( 'Rate the plugin', 'lmtttmpts' ); ?></a>
				</div>
				<div class="bws-plugin-reviews-support">
					<?php _e( 'If there is something wrong about it, please contact us', 'lmtttmpts' ); ?>:
					<a href="http://support.bestwebsoft.com">http://support.bestwebsoft.com</a>
				</div>
			</div>
		</div>
	<?php } 
}

/* 
* Function to customize error message 
* and show remaining attempts
*/
if ( ! function_exists( 'lmtttmpts_error_message' ) ) { 
	function lmtttmpts_error_message() {
		global $error, $wpdb, $wpmu, $lmtttmpts_options;
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		$ip = lmtttmpts_get_address(); /*current user ip address*/
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( ! lmtttmpts_is_ip_blocked( $ip ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && $ip != '' ) {
			if ( isset( $_POST['wp-submit'] ) && ! isset( $_GET['loggedout'] ) && isset( $_POST['log'] ) && '' != $_POST['log'] && isset( $_POST['pwd'] ) && '' != $_POST['pwd'] ) {
				$tries = ( $wpdb->get_var( 
					"SELECT `failed_attempts` 
					FROM `" . $prefix . "failed_attempts` 
					WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
				) );
				$allowed_tries = max ( $lmtttmpts_options['allowed_retries'] - $tries, 0 ); /*calculation of allowed retries*/
				$error = str_replace( '%ATTEMPTS%' , $allowed_tries, $lmtttmpts_options['failed_message'] ); /* Show custom message with remaining attempts */
			}
		}
		$attempts = $wpdb->get_var(  /*quantity of attempts by current user*/
			"SELECT `failed_attempts` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
		);
		$blocks = $wpdb->get_var( /*quantity of blocks by current user*/
			"SELECT `block_quantity` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
		);
		if ( ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) || ( ( $attempts >= $lmtttmpts_options['allowed_retries']-1 ) && $blocks >= $lmtttmpts_options['allowed_locks']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && ( ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! cptch_lmtttmpts_interaction() ) || ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! cptchpr_lmtttmpts_interaction() ) ) ) {
			$error =  str_replace( '%MAIL%' , $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
		}
		$when = ( $wpdb->get_var( 
			"SELECT `block_till` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
		) );
		if ( ( ( lmtttmpts_is_ip_blocked( $ip ) || ( $attempts >= $lmtttmpts_options['allowed_retries']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) ) && ( ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! cptch_lmtttmpts_interaction() ) || ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! cptchpr_lmtttmpts_interaction() ) ) ) {
			$error = str_replace( array( '%DATE%', '%MAIL%' ) , array( $when, $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] );
		}
		if ( isset( $_POST['log'] ) ) {
			$registered_user = get_user_by( 'login', $_POST['log'] );
			if ( !$registered_user ) {
				if ( ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) || ( ( $attempts >= $lmtttmpts_options['allowed_retries']-1 ) && $blocks >= $lmtttmpts_options['allowed_locks']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) )
					$error =  str_replace( '%MAIL%' , $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
				if ( ( ( lmtttmpts_is_ip_blocked( $ip ) || ( $attempts >= $lmtttmpts_options['allowed_retries']-1 ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) ) )
					$error = str_replace( array( '%DATE%', '%MAIL%' ) , array( $when, $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] );
			}
		}
	}
}

/*
* Function to add/update data into tables 
* and perform other actions when login was failed
*/
if ( ! function_exists( 'lmtttmpts_login_failed' ) ) {
	function lmtttmpts_login_failed() { /*if user set wrong login and/or password*/
		global $wpdb, $lmtttmpts_options, $wpmu;

		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();

		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		$ip = lmtttmpts_get_address();
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_blocked( $ip ) && $ip != '' && lmtttmpts_login_form_captcha_checking() ) { /*if ip is whitelisted, blacklisted, blocked or not identified then nothing to add to statistic*/
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'failed_attempts' ) ) { /*add a new row to the table if this is his first wrong attempt*/
				$wpdb->insert( 
					$prefix . 'failed_attempts', 
					array( 
						'ip' 		=> $ip,
						'ip_int' 	=> sprintf( '%u', ip2long( $ip ) ),
					 ), 
					array( '%s', '%s' )
				);
			}
			$failed_attempts = ( $wpdb->get_var( 
				"SELECT `failed_attempts` 
				FROM `" . $prefix . "failed_attempts` 
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
			) );
			if ( $failed_attempts == 0 ) { /* countdown to reset failed attempts */
				wp_schedule_single_event( time() + $lmtttmpts_options['minutes_to_reset'] * 60 + $lmtttmpts_options['hours_to_reset'] * 3600 + $lmtttmpts_options['days_to_reset'] * 86400, 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );
			}
			/*increment value with failed attempts*/
			$wpdb->update(
				$prefix . 'failed_attempts', 
				array( 'failed_attempts' => $failed_attempts + 1 ),
				array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
				array( '%d' ),
				array( '%s' )
			);
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'all_failed_attempts' ) ) { /*add a new row to the archive table if this is his first wrong attempt*/
				$wpdb->insert( 
					$prefix . 'all_failed_attempts', 
					array( 
						'ip' 		=> $ip,
						'ip_int' 	=> sprintf( '%u', ip2long( $ip ) ),
					), 
					array( '%s', '%s' )
				);
			}
			$all_failed_attempts = ( $wpdb->get_var( 
				"SELECT `failed_attempts` 
				FROM `" . $prefix . "all_failed_attempts` 
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
			) );
			/*increment value with failed attempts in archive table*/
			$wpdb->update(
				$prefix . 'all_failed_attempts', 
				array( 'failed_attempts' => $all_failed_attempts + 1 ),
				array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
				array( '%d' ),
				array( '%s' )
			);
			if ( $failed_attempts +1 >= $lmtttmpts_options['allowed_retries'] ) { /*if user exceeded allow retries then reset number of failed attempts, set block to true and set time when block will be reset*/
				$block_quantity = ( $wpdb->get_var( 
					"SELECT `block_quantity` 
					FROM `" . $prefix . "failed_attempts` 
					WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
				) );
				$block_till = current_time( 'timestamp' ) + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400 ;
				$wpdb->update(
					$prefix . 'failed_attempts',
					array( 'block' 		=> true, 
						'failed_attempts' 	=> 0, 
						'block_quantity' 	=> $block_quantity + 1, 
						'block_till' 		=> date ( 'Y-m-d H:i:s', $block_till ) ),
					array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
					array( '%s', '%d', '%s', '%s' ),
					array( '%s' )
				);
				/*countdown to reset block*/
				wp_schedule_single_event( time() + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400, 'lmtttmpts_event_for_reset_block', array( $ip ) );/*event for unblock*/
				/* interaction with Htaccess plugin for blocking */
				if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_block', $ip ); /* hook for blocking by Htaccess */
					wp_schedule_single_event( time() + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400, 'lmtttmpts_htaccess_hook_for_reset_block', array( $ip ) ); /* event for unblock by Htaccess */
				}
				$lmtttmpts_subject = str_replace( array( '%IP%', '%SITE_NAME%' ) , array( $ip, get_bloginfo( 'name' ) ), $lmtttmpts_options['email_subject'] );
				$lmtttmpts_message = str_replace( array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%'  ) , array( $ip, esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ), $lmtttmpts_options['email_blocked'] );
				if ( $block_quantity == 0 ) { /*if this first block (maybe after reset) then they will be reset after some time*/
					wp_schedule_single_event( time() + $lmtttmpts_options['minutes_to_reset_block'] * 60 + $lmtttmpts_options['hours_to_reset_block'] * 3600 + $lmtttmpts_options['days_to_reset_block'] * 86400 , 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
				}
				$all_block_quantity = ( $wpdb->get_var( 
					"SELECT `block_quantity` 
					FROM `" . $prefix . "all_failed_attempts` 
					WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
				) );
				/*update statistic*/
				$wpdb->update(
					$prefix . 'all_failed_attempts',
					array( 'block_quantity' => $all_block_quantity + 1 ),
					array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
					array( '%s' ),
					array( '%s' )
				);
				if ( $block_quantity + 1 >= $lmtttmpts_options['allowed_locks'] ) { /*if user exceed number of allowed locks per some period, his IP will be added to blacklist*/
					$lmtttmpts_message = str_replace( array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%'  ) , array( $ip, esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ), $lmtttmpts_options['email_blacklisted'] );
					$wpdb->update(
						$prefix . 'failed_attempts',
						array( 'block' 		=> false, 
							'failed_attempts' 	=> 0, 
							'block_quantity' 	=> 0 ),
						array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
						array( '%s', '%d', '%d' ),
						array( '%s' )
					);
					/* adding address to blacklist table */
					$wpdb->insert(
						$prefix . 'blacklist', 
						array( 
							'ip' 			=> $ip, 
							'ip_from_int' 	=> sprintf( '%u', ip2long( $ip ) ),
							'ip_to_int' 	=> sprintf( '%u', ip2long( $ip ) ),
						), 
						array( '%s', '%s', '%s' )
					);
					if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_block', $ip ); /* hook for blocking by Htaccess */
					}
				}
				if ( $lmtttmpts_options['notify_email'] ) { /*send mail to admin if this option was set in admin page*/
					add_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
					wp_mail ( $lmtttmpts_options['email_address'], $lmtttmpts_subject, $lmtttmpts_message );
					remove_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
				}
			}
		}
	}
}

/*
* Filter for authenticate access
*/
if ( ! function_exists( 'lmtttmpts_authenticate_user' ) ) {
	function lmtttmpts_authenticate_user( $user, $password ) {
		global $wpdb, $lmtttmpts_options, $wpmu;
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$ip = lmtttmpts_get_address();
		$attempts = $wpdb->get_var(  /*quantity of attempts by current user*/
			"SELECT `failed_attempts` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
		) ;
		$attempts += 1;
		$blocks = $wpdb->get_var( /*quantity of blocks by current user*/
			"SELECT `block_quantity` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
		) ;
		$registered_user = get_user_by( 'login', $_POST['log'] );
		$error = new WP_Error();
		if ( ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) || ( $attempts >= $lmtttmpts_options['allowed_retries'] && $blocks >= $lmtttmpts_options['allowed_locks'] && ( !$registered_user || !wp_check_password($password, $user->user_pass, $user->ID) ) ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			$error->add( 'lmtttmpts_blacklisted', str_replace( '%MAIL%' , $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] ) );
			return $error;  /*return error if address blacklisted */
		}
		if ( ( lmtttmpts_is_ip_blocked( $ip ) || ( $attempts >= $lmtttmpts_options['allowed_retries'] && ( !$registered_user || !wp_check_password($password, $user->user_pass, $user->ID) ) ) ) && ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			$when = ( $wpdb->get_var( 
				"SELECT `block_till` 
				FROM `" . $prefix . "failed_attempts` 
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
			) ) ;
			if ( ! $when ) {
				$block_till = current_time( 'timestamp' ) + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400 ;
				$when = date ( 'Y-m-d H:i:s', $block_till ) ;
			}
			$error->add( 'lmtttmpts_blocked', str_replace( array( '%DATE%', '%MAIL%' ) , array( $when, $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] ) ) ;
			return $error; /* return error if address blocked */
		}
		if ( is_wp_error( $user ) || ( ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_blocked( $ip ) && ( $attempts <= $lmtttmpts_options['allowed_retries'] ) ) || lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
			return $user;
		}
		return $error;
	}
}

/*
* Add notises on plugins page
*/
if ( ! function_exists( 'lmtttmpts_show_notices' ) ) {
	function lmtttmpts_show_notices() {
		global $lmtttmpts_options, $wpmu;
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( isset( $_POST['lmtttmpts_form_submit'] ) && ! isset( $_POST['lmtttmpts_return_default'] ) ) { /* Show notices for Settings form */?>
			<div class="updated fade bellow-h2">
			<?php if ( isset( $_POST['lmtttmpts_allowed_retries'] ) ) { /* Show notices for wrong allowed retries input */
				if ( ! is_numeric( $_POST['lmtttmpts_allowed_retries'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Allowed retries must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_allowed_retries'] < 1 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Allowed retries must be more than zero, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_days_of_lock'] ) ) { /* Show notices for wrong days of lock input */
				if ( ! is_numeric( $_POST['lmtttmpts_days_of_lock'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days of lock must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_days_of_lock'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days of lock can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php }
			} 
			if ( isset( $_POST['lmtttmpts_hours_of_lock'] ) ) { /* Show notices for wrong hours of lock input */
				if ( !is_numeric( $_POST['lmtttmpts_hours_of_lock'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours of lock must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_hours_of_lock'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours of lock can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_hours_of_lock'] > 23 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours of lock can&rsquo;t be more than 23, it was automatically changed to max value', 'lmtttmpts' ) ?></p>
				<?php }
			} 
			if ( isset( $_POST['lmtttmpts_minutes_of_lock'] ) ) { /* Show notices for wrong minutes of lock input */
				if ( !is_numeric( $_POST['lmtttmpts_minutes_of_lock'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes of lock must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_minutes_of_lock'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes of lock can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_minutes_of_lock'] > 59 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes of lock can&rsquo;t be more than 59, it was automatically changed to max value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_days_to_reset'] ) ) { /* Show notices for wrong days to reset input */
				if ( ! is_numeric( $_POST['lmtttmpts_days_to_reset'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days to reset number of tries must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_days_to_reset'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days to reset number of tries can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_hours_to_reset'] ) ) { /* Show notices for wrong hours to reset input */
				if ( !is_numeric( $_POST['lmtttmpts_hours_to_reset'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours to reset number of tries must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_hours_to_reset'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours to reset number of tries can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_hours_to_reset'] > 23 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours to reset number of tries can&rsquo;t be more than 23, it was automatically changed to max value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_minutes_to_reset'] ) ) { /* Show notices for wrong minutes to reset input */
				if ( !is_numeric( $_POST['lmtttmpts_minutes_to_reset'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes to reset number of tries must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_minutes_to_reset'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes to reset number of tries can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_minutes_to_reset'] > 59 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes to reset number of tries can&rsquo;t be more than 59, it was automatically changed to max value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_allowed_locks'] ) ) { /* Show notices for wrong allowed locks before add to blacklist input*/
				if ( ! is_numeric( $_POST['lmtttmpts_allowed_locks'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Allowed blocks must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_allowed_locks'] < 1 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Allowed blocks must be more than zero, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_days_to_reset_block'] ) ) { /* Show notices for wrong days to reset number of locks input */
				if ( ! is_numeric( $_POST['lmtttmpts_days_to_reset_block'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days to reset number of locks must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_days_to_reset_block'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days to reset number of locks can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_hours_to_reset_block'] ) ) { /* Show notices for wrong hours to reset number of locks input */
				if ( !is_numeric( $_POST['lmtttmpts_hours_to_reset_block'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours to reset number of locks must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_hours_to_reset_block'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours to reset number of locks can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_hours_to_reset_block'] > 23 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Hours to reset number of locks can&rsquo;t be more than 23, it was automatically changed to max value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_minutes_to_reset_block'] ) ) { /* Show notices for wrong minutes to reset number of locks input */
				if ( ! is_numeric( $_POST['lmtttmpts_minutes_to_reset_block'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes to reset number of locks must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_minutes_to_reset_block'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes to reset number of locks can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_minutes_to_reset_block'] > 59 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Minutes to reset number of locks can&rsquo;t be more than 59, it was automatically changed to max value', 'lmtttmpts' ) ?></p>
				<?php }
			} 
			if ( isset( $_POST['lmtttmpts_days_to_clear_log'] ) ) {
				if ( ! is_numeric( $_POST['lmtttmpts_days_to_clear_log'] ) ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days to clear log must be numeric, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php } elseif ( $_POST['lmtttmpts_days_to_clear_log'] < 0 ) { ?>
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Days to clear log can&rsquo;t be negative, it was automatically changed to previous value', 'lmtttmpts' ) ?></p>
				<?php }
			}
			if ( isset( $_POST['lmtttmpts_notify_email'] ) && isset( $_POST['lmtttmpts_mailto'] ) && 'custom' == $_POST['lmtttmpts_mailto'] && isset( $_POST['lmtttmpts_email_address'] ) && ! is_email( $_POST['lmtttmpts_email_address'] ) ) { /* Show notices for wrong email input */?>
				<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Wrong e-mail, it was automatically changed to previous value', 'lmtttmpts' ) ?>
			<?php }
			if ( ( $_POST['lmtttmpts_days_of_lock'] == 0 ) && ( $_POST['lmtttmpts_hours_of_lock'] == 0 ) && ( $_POST['lmtttmpts_minutes_of_lock'] == 0 ) ) { /* Show notices when time of lock is less than 1 minute */?>
				<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Time of lock can&rsquo;t be less than 1 minute, it was automatically changed to min value', 'lmtttmpts' ) ?>
			<?php }
			if ( ( $_POST['lmtttmpts_days_to_reset'] == 0 ) && ( $_POST['lmtttmpts_hours_to_reset'] == 0 ) && ( $_POST['lmtttmpts_minutes_to_reset'] == 0 ) ) { /* Show notices when time to reset block is less than 1 minute */?>
				<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Time to reset block can&rsquo;t be less than 1 minute, it was automatically changed to min value', 'lmtttmpts' ) ?>
			<?php }
			if ( ( $_POST['lmtttmpts_days_to_reset_block'] == 0 ) && ( $_POST['lmtttmpts_hours_to_reset_block'] == 0 ) && ( $_POST['lmtttmpts_minutes_to_reset_block'] == 0 ) ) { /* Show notices when time to reset number of locks is less than 1 minute */?>
				<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'Time to reset number of locks can&rsquo;t be less than 1 minute, it was automatically changed to min value', 'lmtttmpts' ) ?>
			<?php } ?>
			<p><strong><?php _e( 'All changes have been saved', 'lmtttmpts' ) ?></strong></p>
			</div>
		<?php }
		if ( isset( $_POST['lmtttmpts_return_default'] ) ) {
			if ( 'email_subject' == $_POST['lmtttmpts_return_default'] ) { ?>
				<div class="updated fade">
					<p><strong><?php _e( "Notice:", 'lmtttmpts' ); ?></strong> <?php _e( "Subject has been restored to default", 'lmtttmpts' ); ?></p>
				</div>
			<?php } else { ?>
				<div class="updated fade">
					<p><strong><?php _e( "Notice:", 'lmtttmpts' ); ?></strong> <?php _e( "Message has been restored to default", 'lmtttmpts' ); ?></p>
				</div>
			<?php }
		}
		if ( isset( $_GET['lmtttmpts_reset_block'] ) ) { /* Show notice when admin reset block for user */?>
			<div class="updated fade">
				<p><strong><?php _e( 'Block has been reset for', 'lmtttmpts' ); echo '&nbsp;', $_GET['lmtttmpts_reset_block'] ?></strong></p>
			</div>
		<?php }
		if ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'reset_blocks' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'reset_blocks' ) ) { /* Show notice when admin reset multipple block for user using Bulk Action*/
			if ( isset( $_POST['ip'] ) ) { 
				$ips = implode( ', ', $_POST['ip'] ); ?>
				<div class="updated fade">
					<p><strong><?php _e( 'Block has been reset for', 'lmtttmpts' ); echo '&nbsp;', $ips; ?></strong></p>
				</div>
			<?php } else { /* Show notice when admin has not selected ip addresses using Bulk Action*/?>
				<div class="updated fade">
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'No address has been selected', 'lmtttmpts' ) ?>
				</div>
			<?php }
		}
		if ( isset( $_POST['lmtttmpts_add_to_blacklist'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) { 
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ) ) ) { ?>
				<div class="updated fade">
					<?php if ( lmtttmpts_is_ip_in_table( str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ), 'whitelist' ) ) { /* Show notice when address is in whitelist too*/?>
						<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'This ip address or mask is in whitelist too, please check this to avoid errors', 'lmtttmpts' ) ?></p>
					<?php } /* Show notice when admin add address to the blacklist*/
					if ( lmtttmpts_is_ip_in_table( str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ), 'blacklist' ) ) { ?>
						<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'This ip address or mask has already been added to blacklist', 'lmtttmpts' ) ?></p>
					<?php } else { ?>
						<p><strong><?php printf( '%s' . '&nbsp;' . __( 'has been added to blacklist', 'lmtttmpts' ), str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ) ) ?></strong></p>
					<?php } ?>
				</div>
			<?php } else { 
				if ( '' == str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ) ) { ?>
					<div class="updated fade">
						<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'You must type ip address', 'lmtttmpts' ) ?></p>
					</div>
				<?php } else { ?>
					<div class="error">
						<p><strong><?php _e( 'ERROR:', 'lmtttmpts' ) ?></strong> <?php _e( 'Wrong format or it does not lie in diapason 0.0.0.0 - 255.255.255.255. Allowed formats:', 'lmtttmpts' ) ?> <code>192.168.0.1</code></p>
						<p><strong><?php printf( '%s' . '&nbsp;' . __( 'can&rsquo;t be added to blacklist.', 'lmtttmpts' ), str_replace( " ", "", $_POST['lmtttmpts_add_to_blacklist'] ) ); ?></strong></p>
					</div>
				<?php }
			}
		}
		if ( isset( $_GET['lmtttmpts_remove_from_blacklist'] ) && check_admin_referer( 'lmtttmpts_remove_from_blacklist_' . $_GET['lmtttmpts_remove_from_blacklist'], 'lmtttmpts_nonce_name' ) ) { /* Show notice when admin delete address from the blacklist*/?>
			<div class="updated fade">
				<p><strong><?php echo $_GET['lmtttmpts_remove_from_blacklist'], '&nbsp;' ;_e( 'has been deleted from blacklist', 'lmtttmpts' ) ?></strong></p>
			</div>
		<?php }
		if ( ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'remove_from_blacklist_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_blacklist_ips' ) ) && 
			check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) {
			if ( isset( $_POST['ip'] ) ) { /* Show notice when admin delete multiple addresses from the blacklist using Bulk Action*/
				$ips = implode( ', ', $_POST['ip'] ); ?>
				<div class="updated fade">
					<p><strong><?php echo $ips, '&nbsp;' ;_e( 'has been deleted from blacklist', 'lmtttmpts' ) ?></strong></p>
				</div>
			<?php } else { /* Show notice when admin has not selected addresses to delete from the blacklist using Bulk Action*/?>
				<div class="updated fade">
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'No address has been selected', 'lmtttmpts' ) ?>
				</div>
			<?php }
		}
		if ( isset( $_POST['lmtttmpts_add_to_whitelist'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ) ) { 
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ) ) ) { ?>
				<div class="updated fade">
				<?php 
					if ( lmtttmpts_is_ip_in_table( str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ), 'blacklist' ) ) { /* Show notice when address is in whitelist too*/?>
						<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'This ip address or mask is in blacklist too, please check this to avoid errors', 'lmtttmpts' ) ?></p>
					<?php } /* Show notice when admin add address to the whitelist*/
					if ( lmtttmpts_is_ip_in_table( str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ), 'whitelist' ) ) { ?>
						<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'This ip address or mask has already been added to whitelist', 'lmtttmpts' ) ?></p>
					<?php } else { ?>
						<p><strong><?php printf( '%s' . '&nbsp;' . __( 'has been added to whitelist', 'lmtttmpts' ), str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ) ) ?></strong></p>
					<?php } ?>
				</div>
			<?php } else { 
				if ( '' == str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ) ) { ?>
					<div class="updated fade">
						<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'You must type ip address', 'lmtttmpts' ) ?></p>
					</div>
				<?php } else {?>
					<div class="error">
						<p><strong><?php _e( 'ERROR:', 'lmtttmpts' ) ?></strong> <?php _e( 'Wrong format or it does not lie in diapason 0.0.0.0 - 255.255.255.255. Allowed formats:', 'lmtttmpts' ) ?> <code>192.168.0.1</code></p>
						<p><strong><?php printf( '%s' . '&nbsp;' . __( 'can&rsquo;t be added to whitelist.', 'lmtttmpts' ), str_replace( " ", "", $_POST['lmtttmpts_add_to_whitelist'] ) ); ?></strong></p>
					</div>
				<?php }
			}
		}
		if ( isset( $_GET['lmtttmpts_remove_from_whitelist'] ) && check_admin_referer( 'lmtttmpts_remove_from_whitelist_' . $_GET['lmtttmpts_remove_from_whitelist'], 'lmtttmpts_nonce_name' ) ) {/* Show notice when admin delete address from the whitelist*/ ?>
			<div class="updated fade">
				<p><strong><?php echo $_GET['lmtttmpts_remove_from_whitelist'], '&nbsp;' ;_e( 'has been deleted from whitelist', 'lmtttmpts' ) ?></strong></p>
			</div>
		<?php }
		if ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'remove_from_whitelist_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'remove_from_whitelist_ips' ) ) {
			if ( isset( $_POST['ip'] ) ) { /* Show notice when admin delete multiple addresses from the whitelist using Bulk Action*/
				$ips = implode( ', ', $_POST['ip'] ); ?>
				<div class="updated fade">
					<p><strong><?php echo $ips, '&nbsp;' ;_e( 'has been deleted from whitelist', 'lmtttmpts' ) ?></strong></p>
				</div>
			<?php } else { /* Show notice when admin has not selected addresses to delete from the whitelist using Bulk Action*/?>
				<div class="updated fade">
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'No address has been selected', 'lmtttmpts' ) ?>
				</div>
			<?php }
		}
		if ( isset( $_GET['s'] ) && isset( $_GET['page'] ) && 'limit-attempts.php' == $_GET['page'] ) {
			if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', str_replace( " ", "", $_GET['s'] ) ) ) { ?>
				<div class="updated fade">
				<?php if ( 'blocked' == $_GET['tab'] || 'log' == $_GET['tab'] ) { ?>
					<p><?php _e( 'Search results for', 'lmtttmpts' ); echo '&nbsp;', str_replace( " ", "", $_GET['s'] ); ?></p>
				<?php } elseif ( 'blacklist' == $_GET['tab'] || 'whitelist' == $_GET['tab'] ) { ?>
					<p><?php echo str_replace( " ", "", $_GET['s'] ), '&nbsp;'; _e( 'is in the following entries', 'lmtttmpts' ); ?></p>
				<?php }?>
				</div>
			<?php } else { ?>
				<div class="error">
					<p><strong><?php _e( 'ERROR:', 'lmtttmpts' ) ?></strong> <?php _e( 'Wrong format or it does not lie in diapason 0.0.0.0 - 255.255.255.255.', 'lmtttmpts' ); ?></p>
				</div>
			<?php }
		}
		if ( isset( $_POST['lmtttmpts_clear_log_complete_confirm'] ) ) { ?>
			<div class="updated fade">
				<p><strong><?php _e( 'Log has been cleared completely', 'lmtttmpts' ) ?></strong></p>
			</div>
		<?php }
		if ( ( isset ( $_POST['action'] ) && $_POST['action'] == 'clear_log_for_ips' ) || ( isset ( $_POST['action2'] ) && $_POST['action2'] == 'clear_log_for_ips' ) ) {
			if ( isset( $_POST['ip'] ) ) { /* Show notice when admin delete multiple addresses from the whitelist using Bulk Action*/ ?>
				<div class="updated fade">
					<p><strong><?php _e( 'Selected log entry (entries) has been deleted', 'lmtttmpts' ) ?></strong></p>
				</div>
			<?php } else { /* Show notice when admin has not selected addresses to delete from the whitelist using Bulk Action*/?>
				<div class="updated fade">
					<p><strong><?php _e( 'Notice:', 'lmtttmpts' ) ?></strong> <?php _e( 'No address has been selected', 'lmtttmpts' ) ?>
				</div>
			<?php }
		}
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/*
* Create new class for displaying list with blocked ips
*/
class Lmtttmpts_Blocked_list extends WP_List_Table {
	function get_columns() { /* adding collumns to table and their view */
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'ip'			=> __( 'Ip address', 'lmtttmpts' ),
			'block_till'	=> __( 'The lock expires', 'lmtttmpts' ),
		);
		return $columns;
	}

	function get_sortable_columns() { /* seting sortable collumns */
		$sortable_columns = array(
			'ip'			=> array( 'ip', true ),
			'block_till'	=> array( 'block_till', false ),
		);
		return $sortable_columns;
	}

	function column_ip( $item ) { /* adding action to 'ip' collumn */
		$actions = array(
			'reset_block'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&tab=%s&lmtttmpts_reset_block=%s' ,$_GET['page'],$_GET['tab'], $item['ip'] ) , 'lmtttmpts_reset_block_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Reset block', 'lmtttmpts' ) . '</a>'
		);
		return sprintf('%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
	}

	function get_bulk_actions() { /* adding bulk action */
		$actions = array(
			'reset_blocks'	=> __( 'Reset block', 'lmtttmpts' ),
		);
		return $actions;
	}

	function column_cb( $item ) { /* customize displaying cb collumn */
		return sprintf(
			'<input type="checkbox" name="ip[]" value="%s" />', $item['ip']
		);
	}

	function prepare_items() { /* preparing table items */
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$query = "SELECT `ip` , `block_till` FROM `" . $prefix . "failed_attempts` WHERE `block` = true";
		if ( isset( $_GET['s'] ) ) {
			$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
			if ( 0 != $search_ip ) {
				$query .= " AND `ip_int` = " . $search_ip;
			}
		}
		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'ip';
		$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array('asc', 'desc') ) ) ? $_GET['order'] : 'asc';
		$query .= " ORDER BY `" . $orderby. "` " . $order;
		$totalitems = $wpdb->query( $query );
		$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );
		$paged = ! empty( $_GET['paged'] ) ? $_GET['paged'] : '';
		if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0) {
			$paged = 1;
		}
		$totalpages = ceil( $totalitems / $perpage );
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ($paged - 1) * $perpage;
			$query .= " LIMIT " . $offset . "," . $perpage;
		}
		 $this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage
		) );
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	function column_default( $item, $column_name ) { /* setting default view for collumn items */
		switch( $column_name ) {
			case 'ip':
			case 'block_till':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; /*Show whole array for bugfix*/
		}
	}
}

/*
* Create new class for displaying blacklist
*/
class Lmtttmpts_Blacklist extends WP_List_Table {
	function get_columns() {/* adding collumns to table and their view */
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'ip'			=> __( 'Ip address', 'lmtttmpts' ),
			'ip_from'		=> __( 'Diapason from', 'lmtttmpts' ),
			'ip_to'			=> __( 'Diapason till', 'lmtttmpts' ),
		);
		return $columns;
	}

	function get_sortable_columns() {/* seting sortable collumns */
		$sortable_columns = array(
			'ip' 		=> array( 'ip', true ),
			'ip_from' 	=> array( 'ip_from', false ),
			'ip_to' 	=> array( 'ip_to', false )
		);
		return $sortable_columns;
	}

	function column_ip( $item ) {/* adding action to 'ip' collumn */
		$actions = array(
			'remove_from_blacklist'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&tab=%s&lmtttmpts_remove_from_blacklist=%s' ,$_GET['page'],$_GET['tab'], $item['ip'] ) , 'lmtttmpts_remove_from_blacklist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Remove from blacklist', 'lmtttmpts' ) . '</a>'
		);
		return sprintf( '%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
	}

	function get_bulk_actions() {/* adding bulk action */
		$actions = array(
			'remove_from_blacklist_ips'	=> __( 'Remove from blacklist', 'lmtttmpts' ),
		);
		return $actions;
	}

	function column_cb( $item ) { /* customize displaying cb collumn */
		return sprintf( '<input type="checkbox" name="ip[]" value="%s" />', $item['ip'] );
	}

	function prepare_items() { /* preparing table items */
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$query = "SELECT `ip`, `ip_from`, `ip_to` FROM `" . $prefix . "blacklist`";
		if ( isset( $_GET['s'] ) ) {
			$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
			if ( 0 != $search_ip ) {
				$query .= " WHERE `ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip;
			}
		}
		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'ip';
		$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array('asc', 'desc') ) ) ? $_GET['order'] : 'asc';
		$query .= " ORDER BY `" . $orderby . "` " . $order;
		$totalitems = $wpdb->query( $query );
		$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );
		$paged = ! empty( $_GET['paged'] ) ? $_GET['paged'] : '';
		if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0) {
			$paged = 1;
		}
		$totalpages = ceil( $totalitems / $perpage );
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ($paged - 1) * $perpage;
			$query .= " LIMIT " . $offset . "," . $perpage;
		}
		 $this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage
		) );
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	function column_default( $item, $column_name ) { /* setting default view for collumn items */
		switch( $column_name ) {
			case 'ip':
			case 'ip_from':
			case 'ip_to':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; /*Show whole array for bugfix*/
		}
	}
}

/*
* Create new class for displaying whitelist
*/
class Lmtttmpts_Whitelist extends WP_List_Table {
	function get_columns() { /* adding collumns to table and their view */
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'ip'			=> __( 'Ip address', 'lmtttmpts' ),
			'ip_from'		=> __( 'Diapason from', 'lmtttmpts' ),
			'ip_to'			=> __( 'Diapason till', 'lmtttmpts' ),
		);
		return $columns;
	}

	function get_sortable_columns() { /* seting sortable collumns */
		$sortable_columns = array(
			'ip' => array( 'ip', true ),
			'ip_from' 	=> array( 'ip_from', false ),
			'ip_to' 	=> array( 'ip_to', false )
		);
		return $sortable_columns;
	}

	function column_ip( $item ) { /* adding action to 'ip' collumn */
		$actions = array(
			'remove_from_whitelist'	=> '<a href="' . wp_nonce_url( sprintf( '?page=%s&tab=%s&lmtttmpts_remove_from_whitelist=%s' ,$_GET['page'],$_GET['tab'], $item['ip'] ) , 'lmtttmpts_remove_from_whitelist_' . $item['ip'], 'lmtttmpts_nonce_name' ) . '">' . __( 'Remove from whitelist', 'lmtttmpts' ) . '</a>'
		);
		return sprintf('%1$s %2$s', $item['ip'], $this->row_actions( $actions ) );
	}

	function get_bulk_actions() { /* adding bulk action */
		$actions = array(
			'remove_from_whitelist_ips'	=> __( 'Remove from whitelist', 'lmtttmpts' ),
		);
		return $actions;
	}

	function column_cb( $item ) { /* customize displaying cb collumn */
		return sprintf(
			'<input type="checkbox" name="ip[]" value="%s" />', $item['ip']
		);
	}

	function prepare_items() { /* preparing table items */
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$query = "SELECT `ip`, `ip_from`, `ip_to` FROM `" . $prefix . "whitelist`";
		if ( isset( $_GET['s'] ) ) {
			$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
			if ( 0 != $search_ip ) {
				$query .= " WHERE `ip_from_int` <= " . $search_ip . " AND `ip_to_int`>= " . $search_ip;
			}
		}
		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'ip';
		$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array('asc', 'desc') ) ) ? $_GET['order'] : 'asc';
		$query .= " ORDER BY `" . $orderby. "` " . $order;
		$totalitems = $wpdb->query( $query );
		$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );
		$paged = ! empty( $_GET['paged'] ) ? $_GET['paged'] : '';
		if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0) {
			$paged = 1;
		}
		$totalpages = ceil( $totalitems / $perpage );
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query .= " LIMIT " . $offset . "," . $perpage;
		}
		 $this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage
		) );
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	function column_default( $item, $column_name ) { /* setting default view for collumn items */
		switch ( $column_name ) {
			case 'ip':
			case 'ip_from':
			case 'ip_to':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; /*Show whole array for bugfix*/
		}
	}
}

/*
* Create new class for displaying log
*/
class Lmtttmpts_Log extends WP_List_Table { 
	function get_columns() { /* adding collumns to table and their view */
		global $lmtttmpts_options, $wpmu;
		$all_plugins = get_plugins();
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		$columns = array(
			'cb'				=> '<input type="checkbox" />',
			'ip'				=> __( 'Ip address', 'lmtttmpts' ),
			'failed_attempts'	=> __( 'Number of failed attempts', 'lmtttmpts' ),
		);
		$columns['block_quantity']	= __( 'Number of blocks', 'lmtttmpts' );
		$columns['status']			= __( 'Status', 'lmtttmpts' );
		return $columns;
	}

	function get_bulk_actions() { /* adding bulk action */
		$actions = array(
			'clear_log_for_ips'	=> __( 'Delete log entry', 'lmtttmpts' ),
		);
		return $actions;
	}

	function column_cb( $item ) { /* customize displaying cb collumn */
		return sprintf(
			'<input type="checkbox" name="ip[]" value="%s" />', $item['ip']
		);
	}

	function get_sortable_columns() { /* seting sortable collumns */
		global $lmtttmpts_options, $wpmu;
		$all_plugins = get_plugins();
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		$sortable_columns = array(
			'ip'				=> array( 'ip', true ),
			'failed_attempts'	=> array( 'failed_attempts', false ),
		);
		$sortable_columns['block_quantity']	= array( 'block_quantity', false );
		$sortable_columns['status']	= array( 'status', false );
		return $sortable_columns;
	}

	function single_row( $item ) {
		global $wpdb;
		$row_class = '';
		if ( lmtttmpts_is_ip_in_table( $item['ip'], 'whitelist' ) ) {
			$row_class = ' class="lmtttmpts_whitelist"';
		} elseif ( lmtttmpts_is_ip_in_table( $item['ip'], 'blacklist' ) ) {
			$row_class = ' class="lmtttmpts_blacklist"';
		} elseif ( lmtttmpts_is_ip_blocked( $item['ip'] ) ) {
			$row_class = ' class="lmtttmpts_blocked"';
		}
		echo '<tr' . $row_class . '>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	function prepare_items() { /* preparing table items */
		global $wpdb, $lmtttmpts_options, $wpmu;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$query = "SELECT `ip`, `failed_attempts`";
		$all_plugins = get_plugins();
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		
		$query .= ", `block_quantity`";
		$query .= " FROM `" . $prefix . "all_failed_attempts`";
		if ( isset( $_GET['s'] ) ) {
			$search_ip = sprintf( '%u', ip2long( str_replace( " ", "", $_GET['s'] ) ) );
			if ( 0 != $search_ip ) {
				$query .= " WHERE `ip_int` = " . $search_ip;
			}
		}
		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'ip';
		$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array('asc', 'desc') ) ) ? $_GET['order'] : 'asc';
		$totalitems = $wpdb->query( $query );
		$perpage = $this->get_items_per_page( 'addresses_per_page', 20 );
		$paged = ! empty( $_GET['paged'] ) ? $_GET['paged'] : '';
		if ( empty( $paged ) || !is_numeric( $paged ) || $paged <= 0) {
			$paged = 1;
		}
		$totalpages = ceil( $totalitems / $perpage );
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage
		) );
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$logs = ( $wpdb->get_results ( $query, ARRAY_A ) );
		if ( $logs ) {
			foreach ( $logs as &$log ) {
				if ( lmtttmpts_is_ip_in_table( $log['ip'], 'whitelist' ) ) {
					$log['status'] = '<a href="?page=' . $_GET['page'] . '&tab=whitelist&s=' . $log['ip'] . '">' . __( 'whitelisted', 'lmtttmpts' ) . '</a>';
				} elseif ( lmtttmpts_is_ip_in_table( $log['ip'], 'blacklist' ) ) {
					$log['status'] = '<a href="?page=' . $_GET['page'] . '&tab=blacklist&s=' . $log['ip'] . '">' . __( 'blacklisted', 'lmtttmpts' ) . '</a>';
				} elseif ( lmtttmpts_is_ip_blocked( $log['ip'] ) ) {
					$log['status'] = '<a href="?page=' . $_GET['page'] . '&tab=blocked&s=' . $log['ip'] . '">' . __( 'blocked', 'lmtttmpts' ) . '</a>';
				} else {
					$log['status'] = __( 'not blocked', 'lmtttmpts' );
				}				
			}
		}
		function usort_reorder( $a,$b ) { /* function for sorting items by collumns */
			$orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'ip'; /*If no sort, default to title*/
			$order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; /*If no order, default to asc*/
			$result = strcmp( $a[$orderby], $b[$orderby] ); /*Determine sort order*/
			return ( $order==='asc' ) ? $result : -$result; /*Send final sort direction to usort*/
		}
		usort( $logs, 'usort_reorder' );
		$logs = array_slice($logs,(($paged-1)*$perpage),$perpage);
		$this->items = $logs;
	}

	function column_default( $item, $column_name ) { /* setting default view for collumn items */
		switch( $column_name ) {
			case 'ip':
			case 'failed_attempts':
			case 'block_quantity':
			case 'status':
			case 'form' :
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; /*Show whole array for bugfix*/
		}
	}
}

/*
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

/* 
* Function for checking is current ip is blocked 
*/
if ( !function_exists( 'lmtttmpts_is_ip_blocked' ) ) { 
	function lmtttmpts_is_ip_blocked( $ip ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$is_blocked = ( $wpdb->get_var( 
			"SELECT `block` 
			FROM `" . $prefix . "failed_attempts` 
			WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
		) );
		return $is_blocked;
	}
}

if ( ! function_exists( 'lmtttmpts_screen_options' ) ) {
	function lmtttmpts_screen_options() {
		global $sndr_reports_list;
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

/*
* Function for checking is current ip in current table
*/
if ( ! function_exists( 'lmtttmpts_is_ip_in_table' ) ) {
	function lmtttmpts_is_ip_in_table( $ip, $table ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( $table == 'whitelist' || $table == 'blacklist' ) { /* for whitelist and blacklist tables needs different method */
			if ( sprintf( '%u', ip2long( $ip ) ) != 0 ) { /* checking is $ip variable is ip address and not a ip mask */
				$is_in = ( $wpdb->get_row(
					"SELECT *
					FROM `" . $prefix . $table . "`
					WHERE `ip_from_int` <= '" . sprintf( '%u', ip2long( $ip ) ) . "' 
					AND `ip_to_int` >= '" . sprintf( '%u', ip2long( $ip ) ) . "' "
				) );
			} else { /* if $ip variable is ip mask */
				$is_in = ( $wpdb->get_row(
					"SELECT *
					FROM `" . $prefix . $table . "`
					WHERE `ip` = '" . $ip . "' "
				) );
			}
		} else { /* for other tables */
			$is_in = ( $wpdb->get_row(
				"SELECT *
				FROM `" . $prefix . $table . "`
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
			) );
		}
		return $is_in;
	}
}

/*
* Function for deleting ip from blacklist
*/
if ( ! function_exists( 'lmtttmpts_delete_ip_from_blacklist' ) ) { 
	function lmtttmpts_delete_ip_from_blacklist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$wpdb->delete(
			$prefix . 'blacklist',
			array( 'ip' => $ip ),
			array( '%s' )
		);
		if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
			do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $ip ); /* hook for deblocking by Htaccess */
		}
	}
}

/*
* Function for deleting ip from whitelist
*/
if ( ! function_exists( 'lmtttmpts_delete_ip_from_whitelist' ) ) { 
	function lmtttmpts_delete_ip_from_whitelist ( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$wpdb->delete(
			$prefix . 'whitelist',
			array( 'ip' => $ip ),
			array( '%s' )
		);
		if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
			do_action( 'lmtttmpts_htaccess_hook_for_delete_from_whitelist', $ip ); /* hook for deleting from whitelist by Htaccess */
		}
	}
}

/*
* Function for adding ip to blacklist
*/
if ( ! function_exists( 'lmtttmpts_add_ip_to_blacklist' ) ) { 
	function lmtttmpts_add_ip_to_blacklist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( '' != $ip ) {
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) ) {
				if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) { /*if insert single ip address*/
					$ip_from_int = sprintf( '%u', ip2long( $ip ) ); /*because adding a single address diapason will contain one address*/
					$ip_to_int = sprintf( '%u', ip2long( $ip ) );
					$wpdb->insert( /*add a new row to db*/
						$prefix . 'blacklist',
						array( 
							'ip' 			=> $ip, 
							'ip_from' 		=> $ip,
							'ip_to' 		=> $ip,
							'ip_from_int' 	=> $ip_from_int,
							'ip_to_int' 	=> $ip_to_int,
						),
						array( '%s', '%s', '%s','%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
					);
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){0,2}\.$/', $ip ) ) { /*if insert ip mask like 'xxx.' or xxx.xxx. or xxx.xxx.xxx.*/
					$dot_entry = substr_count( $ip, '.' );
					switch ($dot_entry) {
						case 3: /*in case if mask like xxx.xxx.xxx.*/
							$ip_from = $ip . '0';
							$ip_to = $ip . '255';
							break;
						case 2: /*in case if mask like xxx.xxx.*/
							$ip_from = $ip . '0.0';
							$ip_to = $ip . '255.255';
							break;
						case 1: /*in case if mask like xxx.*/
							$ip_from = $ip . '0.0.0';
							$ip_to = $ip . '255.255.255';
							break;
						default: /*insurance*/
							$ip_from = '0.0.0.0';
							$ip_to = '0.0.0.0';
							break;
					}
					$wpdb->insert(/*add a new row to db*/
						$prefix . 'blacklist',
						array( 
							'ip' 			=> $ip, 
							'ip_from' 		=> $ip_from,
							'ip_to' 		=> $ip_to,
							'ip_from_int' 	=> sprintf( '%u', ip2long( $ip_from ) ),
							'ip_to_int' 	=> sprintf( '%u', ip2long( $ip_to ) ),
						),
						array( '%s', '%s', '%s', '%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
					);
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}\-(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) { /*if insert diapason of ip addresses like xxx.xxx.xxx.xxx-yyy.yyy.yyy.yyy*/
					$ips = explode( '-', $ip ); /*$ips[0] - diapason from, $ips[1] - diapason to*/
					if ( sprintf( '%u', ip2long( $ips[0] ) ) <= sprintf( '%u', ip2long( $ips[1] ) ) ) {
						$wpdb->insert( /*add a new row to db*/
							$prefix . 'blacklist',
							array( 
								'ip' 			=> $ip, 
								'ip_from' 		=> $ips[0],
								'ip_to' 		=> $ips[1],
								'ip_from_int' 	=> sprintf( '%u', ip2long( $ips[0] ) ),
								'ip_to_int' 	=> sprintf( '%u', ip2long( $ips[1] ) ),
							),
							array( '%s', '%s', '%s', '%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
						);
					}
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}\/(3[0-2]|[1-2][0-9]|[0-9])$/', $ip ) ) { /*if insert ip mask like xxx.xxx.xxx.xxx/yy*/
					$mask = explode( '/' , $ip ); /* $mask[0] - is ip address, $mask[1] - is cidr mask */
					$nmask = 4294967295 - ( pow( 2 , 32 - $mask[1] ) - 1 ); /* calculation netmask in decimal view from cidr mask */
					$ip_from_int = ip2long( $mask[0] ) & $nmask; /*calculating network address signed (this is doing for correct worl with netmsk)*/
					$ip_from_int = sprintf( '%u', $ip_from_int ); /*and now unsigned*/
					$ip_to_int = $ip_from_int + ( pow( 2 , 32 - $mask[1] ) - 1 ); /*calculating broadcast*/
					$wpdb->insert(
						$prefix . 'blacklist',
						array( 
							'ip' 			=> $ip, 
							'ip_from' 		=> long2ip( $ip_from_int ),
							'ip_to' 		=> long2ip( $ip_to_int ),
							'ip_from_int' 	=> $ip_from_int,
							'ip_to_int' 	=> $ip_to_int,
						),
						array( '%s', '%s', '%s', '%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
					);
				}
				if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_block', $ip ); /* hook for blocking by Htaccess */
				}
			}
		}
	}
}

/*
* Function for adding ip to whitelist
*/
if ( ! function_exists( 'lmtttmpts_add_ip_to_whitelist' ) ) { 
	function lmtttmpts_add_ip_to_whitelist( $ip ) {
		global $wpdb, $lmtttmpts_options;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( '' != $ip ) {
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) ) {
				if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) { /*if insert single ip address*/
					$ip_from_int = sprintf( '%u', ip2long( $ip ) ); /*because adding a single address diapason will contain one address*/
					$ip_to_int = sprintf( '%u', ip2long( $ip ) );
					$wpdb->insert( /*add a new row to db*/
						$prefix . 'whitelist',
						array( 
							'ip' 			=> $ip, 
							'ip_from' 		=> $ip,
							'ip_to' 		=> $ip,
							'ip_from_int' 	=> $ip_from_int,
							'ip_to_int' 	=> $ip_to_int,
						),
						array( '%s', '%s', '%s','%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
					);
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){0,2}\.$/', $ip ) ) { /*if insert ip mask like 'xxx.' or xxx.xxx. or xxx.xxx.xxx.*/
					$dot_entry = substr_count( $ip, '.' );
					switch ( $dot_entry ) {
						case 3: /*in case if mask like xxx.xxx.xxx.*/
							$ip_from = $ip . '0';
							$ip_to = $ip . '255';
							break;
						case 2: /*in case if mask like xxx.xxx.*/
							$ip_from = $ip . '0.0';
							$ip_to = $ip . '255.255';
							break;
						case 1: /*in case if mask like xxx.*/
							$ip_from = $ip . '0.0.0';
							$ip_to = $ip . '255.255.255';
							break;
						default: /*insurance*/
							$ip_from = '0.0.0.0';
							$ip_to = '0.0.0.0';
							break;
					}
					$wpdb->insert(/*add a new row to db*/
						$prefix . 'whitelist',
						array( 
							'ip' 			=> $ip, 
							'ip_from' 		=> $ip_from,
							'ip_to' 		=> $ip_to,
							'ip_from_int' 	=> sprintf( '%u', ip2long( $ip_from ) ),
							'ip_to_int' 	=> sprintf( '%u', ip2long( $ip_to ) ),
						),
						array( '%s', '%s', '%s', '%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
					);
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}\-(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip ) ) { /*if insert diapason of ip addresses like xxx.xxx.xxx.xxx-yyy.yyy.yyy.yyy*/
					$ips = explode( '-', $ip ); /*$ips[0] - diapason from, $ips[1] - diapason to*/
					if ( sprintf( '%u', ip2long( $ips[0] ) ) <= sprintf( '%u', ip2long( $ips[1] ) ) ) {
						$wpdb->insert( /*add a new row to db*/
							$prefix . 'whitelist',
							array( 
								'ip' 			=> $ip, 
								'ip_from' 		=> $ips[0],
								'ip_to' 		=> $ips[1],
								'ip_from_int' 	=> sprintf( '%u', ip2long( $ips[0] ) ),
								'ip_to_int' 	=> sprintf( '%u', ip2long( $ips[1] ) ),
							),
							array( '%s', '%s', '%s', '%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
						);
					}
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}\/(3[0-2]|[1-2][0-9]|[0-9])$/', $ip ) ) { /*if insert ip mask like xxx.xxx.xxx.xxx/yy*/
					$mask = explode( '/' , $ip ); /* $mask[0] - is ip address, $mask[1] - is cidr mask */
					$nmask = 4294967295 - ( pow( 2 , 32 - $mask[1] ) - 1 ); /* calculation netmask in decimal view from cidr mask */
					$ip_from_int = ip2long( $mask[0] ) & $nmask; /*calculating network address signed (this is doing for correct worl with netmsk)*/
					$ip_from_int = sprintf( '%u', $ip_from_int ); /*and now unsigned*/
					$ip_to_int = $ip_from_int + ( pow( 2 , 32 - $mask[1] ) - 1 ); /*calculating broadcast*/
					$wpdb->insert(
						$prefix . 'whitelist',
						array( 
							'ip' 			=> $ip, 
							'ip_from' 		=> long2ip( $ip_from_int ),
							'ip_to' 		=> long2ip( $ip_to_int ),
							'ip_from_int' 	=> $ip_from_int,
							'ip_to_int' 	=> $ip_to_int,
						),
						array( '%s', '%s', '%s', '%s', '%s' )/*all '%s' because max value in '%d' is 2147483647*/
					);
				}
				if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $ip ); /* hook for blocking by Htaccess */
				}
			}
		}
	}
}

/*
* Function to clear all log
*/
if ( ! function_exists( 'lmtttmpts_clear_log_completely' ) ) {
	function lmtttmpts_clear_log_completely() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$wpdb->query( "DELETE FROM `" . $prefix . "all_failed_attempts`" );
	}
}

/*
* Function to clear single log
*/
if ( ! function_exists( 'lmtttmpts_clear_log' ) ) {
	function lmtttmpts_clear_log( $ip ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$wpdb->delete(
			$prefix . 'all_failed_attempts',
			array( 'ip' => $ip ),
			array( '%s' )
		);
	}
}

if ( ! function_exists( 'lmtttmpts_clear_log_daily' ) ) {
	function lmtttmpts_clear_log_daily() {
		global $wpdb, $wpmu, $lmtttmpts_options;
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$time = date( 'Y-m-d H:i:s', time() - 86400 * $lmtttmpts_options['days_to_clear_log'] );
		$wpdb->query( "DELETE FROM `" . $prefix . "all_failed_attempts` WHERE `last_failed_attempt` <= '" . $time . "'" );
	}
}

/*
* Function to reset failed attempts
*/
if ( ! function_exists( 'lmtttmpts_reset_failed_attempts' ) ) { 
	function lmtttmpts_reset_failed_attempts( $ip ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$wpdb->update(
			$prefix . 'failed_attempts',
			array( 'failed_attempts' => 0 ),
			array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
			array( '%d' ),
			array( '%s' )
		);
	}
}

/*
* Function to reset block
*/
if ( ! function_exists( 'lmtttmpts_reset_block' ) ) { 
	function lmtttmpts_reset_block( $ip ) {
		if ( lmtttmpts_is_ip_blocked( $ip ) ) {
			global $wpdb, $wpmu, $lmtttmpts_options;
			if ( '' == $lmtttmpts_options ) {
				$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
			}
			$prefix = $wpdb->prefix . 'lmtttmpts_';
			$wpdb->update(
				$prefix . 'failed_attempts',
				array( 'block' => false ),
				array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
				array( '%s' ),
				array( '%s' )
			);
			wp_clear_scheduled_hook ( 'lmtttmpts_event_for_reset_block', array( $ip ) ); /* clear event for automatis deblocking */
			if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
				do_action( 'lmtttmpts_htaccess_hook_for_reset_block', $ip ); /* hook for deblocking by Htaccess */
				wp_clear_scheduled_hook( 'lmtttmpts_htaccess_hook_for_reset_block', array( $ip ) ); /* clear event for automatis deblocking by Htaccess */
			}
		}
	}
}

/*
* Function to reset number of failed attempts
*/
if ( ! function_exists( 'lmtttmpts_reset_block_quantity' ) ) { 
	function lmtttmpts_reset_block_quantity( $ip ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		$wpdb->update(
			$prefix . 'failed_attempts',
			array( 'block_quantity' => 0 ),
			array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
			array( '%d' ),
			array( '%s' )
		);
	}
}

/*
* Filter to transfer message in html format
*/
if ( ! function_exists( 'lmtttmpts_set_html_content_type' ) ) { 
	function lmtttmpts_set_html_content_type() {
		return 'text/html';
	}
}

/*
* Checking for right captcha in login form
*/
if ( ! function_exists( 'lmtttmpts_login_form_captcha_checking' ) ) {
	function lmtttmpts_login_form_captcha_checking() {
		global $wpmu, $lmtttmpts_options;
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		if ( function_exists( 'cptch_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha\/captcha.php/', $active_plugins ) ) && ! isset( $lmtttmpts_options['login_form_captcha_check'] ) && ! cptch_lmtttmpts_interaction() ) { 
			return false;/*return false if only Captcha is instaled, is active, is exist in login form, user set consider captcha and captcha is invalid*/
		} elseif ( function_exists( 'cptchpr_lmtttmpts_interaction' ) && 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $active_plugins ) ) && ! isset( $lmtttmpts_options['login_form_captcha_check'] ) && ! cptchpr_lmtttmpts_interaction() ) { 
			return false;/*return false if only Captcha is instaled, is active, is exist in login form, user set consider captcha and captcha is invalid*/
		}
		return true;
	}
}

/*
* Failed captcha attempt action
*/
if ( ! function_exists( 'lmtttmpts_failed_with_captcha' ) ) {
	function lmtttmpts_failed_with_captcha( $form ) {
		global $wpdb, $lmtttmpts_options, $wpmu;
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();
		if ( '' == $lmtttmpts_options ) {
			$lmtttmpts_options = ( 1 == $wpmu ) ? get_site_option( 'lmtttmpts_options' ) : get_option( 'lmtttmpts_options' );
		}
		if ( 1 == $wpmu ) {
			$active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins , get_option( 'active_plugins' ) );
		} else {
			$active_plugins = get_option( 'active_plugins' );
		}
		$ip = lmtttmpts_get_address();
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( ! lmtttmpts_is_ip_in_table( $ip, 'whitelist' ) && ! lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) && ! lmtttmpts_is_ip_blocked( $ip ) && $ip != ''  ) {
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'failed_attempts' ) ) {
				$wpdb->insert( 
					$prefix . 'failed_attempts', 
					array( 
						'ip' 		=> $ip,
						'ip_int' 	=> sprintf( '%u', ip2long( $ip ) ),
					 ), 
					array( '%s', '%s' )
				);
			}
			$failed_attempts = ( $wpdb->get_var( 
				"SELECT `failed_attempts` 
				FROM `" . $prefix . "failed_attempts` 
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
			) );
			if ( $failed_attempts == 0 ) { /* countdown to reset failed attempts */
				wp_schedule_single_event( time() + $lmtttmpts_options['minutes_to_reset'] * 60 + $lmtttmpts_options['hours_to_reset'] * 3600 + $lmtttmpts_options['days_to_reset'] * 86400, 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );
			}
			/*increment value with failed attempts*/
			$wpdb->update(
				$prefix . 'failed_attempts', 
				array( 'failed_attempts' => $failed_attempts + 1 ),
				array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
				array( '%d' ),
				array( '%s' )
			);
			if ( ! lmtttmpts_is_ip_in_table( $ip, 'all_failed_attempts' ) ) { /*add a new row to the archive table if this is his first wrong attempt*/
				$wpdb->insert( 
					$prefix . 'all_failed_attempts', 
					array( 
						'ip' 		=> $ip,
						'ip_int' 	=> sprintf( '%u', ip2long( $ip ) ),
					), 
					array( '%s', '%s' )
				);
			}
			$all_failed_attempts = ( $wpdb->get_var( 
				"SELECT `failed_attempts` 
				FROM `" . $prefix . "all_failed_attempts` 
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
			) );
			$form_failed_attempts = ( $wpdb->get_var(
				"SELECT `invalid_captcha_from_" . $form . "` 
				FROM `" . $prefix . "all_failed_attempts` 
				WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'"
			) );
			/*increment value with failed attempts in archive table*/
			$wpdb->update(
				$prefix . 'all_failed_attempts', 
				array( 'failed_attempts' => $all_failed_attempts + 1, 'invalid_captcha_from_' . $form => $form_failed_attempts + 1 ),
				array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
				array( '%d', '%d' ),
				array( '%s' )
			);
			if ( $failed_attempts +1 >= $lmtttmpts_options['allowed_retries'] ) { /*if user exceeded allow retries then reset number of failed attempts, set block to true and set time when block will be reset*/
				$block_quantity = ( $wpdb->get_var( 
					"SELECT `block_quantity` 
					FROM `" . $prefix . "failed_attempts` 
					WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
				) );
				$block_till = current_time( 'timestamp' ) + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400 ;
				$wpdb->update(
					$prefix . 'failed_attempts',
					array( 'block' 			=> true, 
						'failed_attempts' 	=> 0, 
						'block_quantity' 	=> $block_quantity + 1, 
						'block_till' 		=> date ( 'Y-m-d H:i:s', $block_till ) ),
					array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
					array( '%s', '%d', '%s', '%s' ),
					array( '%s' )
				);
				/*countdown to reset block*/
				wp_schedule_single_event( time() + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400, 'lmtttmpts_event_for_reset_block', array( $ip ) );/*event for unblock*/
				/* interaction with Htaccess plugin for blocking */
				if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
					do_action( 'lmtttmpts_htaccess_hook_for_block', $ip ); /* hook for blocking by Htaccess */
					wp_schedule_single_event( time() + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400, 'lmtttmpts_htaccess_hook_for_reset_block', array( $ip ) ); /* event for unblock by Htaccess */
				}
				$lmtttmpts_subject = str_replace( array( '%IP%', '%SITE_NAME%' ) , array( $ip, get_bloginfo( 'name' ) ), $lmtttmpts_options['email_subject'] );
				$lmtttmpts_message = str_replace( array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%'  ) , array( $ip, esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ), $lmtttmpts_options['email_blocked'] );
				if ( $block_quantity == 0 ) { /*if this first block (maybe after reset) then they will be reset after some time*/
					wp_schedule_single_event( time() + $lmtttmpts_options['minutes_to_reset_block'] * 60 + $lmtttmpts_options['hours_to_reset_block'] * 3600 + $lmtttmpts_options['days_to_reset_block'] * 86400 , 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
				}
				$all_block_quantity = ( $wpdb->get_var( 
					"SELECT `block_quantity` 
					FROM `" . $prefix . "all_failed_attempts` 
					WHERE `ip_int` = '" . sprintf( '%u', ip2long( $ip ) ) . "'" 
				) );
				/*update statistic*/
				$wpdb->update(
					$prefix . 'all_failed_attempts',
					array( 'block_quantity' => $all_block_quantity + 1 ),
					array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
					array( '%s' ),
					array( '%s' )
				);
				if ( $block_quantity + 1 >= $lmtttmpts_options['allowed_locks'] ) { /*if user exceed number of allowed locks per some period, his IP will be added to blacklist*/
					$lmtttmpts_message = str_replace( array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%'  ) , array( $ip, esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ), $lmtttmpts_options['email_blacklisted'] );
					$wpdb->update(
						$prefix . 'failed_attempts',
						array( 'block' 		=> false, 
							'failed_attempts' 	=> 0, 
							'block_quantity' 	=> 0 ),
						array( 'ip_int' => sprintf( '%u', ip2long( $ip ) ) ),
						array( '%s', '%d', '%d' ),
						array( '%s' )
					);
					/* adding address to blacklist table */
					$wpdb->insert(
						$prefix . 'blacklist', 
						array( 
							'ip' 			=> $ip, 
							'ip_from_int' 	=> sprintf( '%u', ip2long( $ip ) ),
							'ip_to_int' 	=> sprintf( '%u', ip2long( $ip ) ),
						), 
						array( '%s', '%s', '%s' )
					);
					if ( isset( $lmtttmpts_options['block_by_htaccess'] ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_block', $ip ); /* hook for blocking by Htaccess */
					}
				}
				if ( $lmtttmpts_options['notify_email'] ) { /*send mail to admin if this option was set in admin page*/
					add_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
					wp_mail ( $lmtttmpts_options['email_address'], $lmtttmpts_subject, $lmtttmpts_message );
					remove_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
				}
			}
		}
	}
}

if ( ! function_exists ( 'lmtttmpts_plugin_banner' ) ) {
	function lmtttmpts_plugin_banner() {
		global $hook_suffix;	
		if ( 'plugins.php' == $hook_suffix ) {  
			global $lmtttmpts_plugin_info, $bstwbsftwppdtplgns_cookie_add;
			$banner_array = array(
				array( 'lmtttmpts_hide_banner_on_plugin_page', 'limit-attempts/limit-attempts.php', '1.0.2' ),
				array( 'sndr_hide_banner_on_plugin_page', 'sender/sender.php', '0.5' ),
				array( 'srrl_hide_banner_on_plugin_page', 'user-role/user-role.php', '1.4' ),
				array( 'pdtr_hide_banner_on_plugin_page', 'updater/updater.php', '1.12' ),
				array( 'cntctfrmtdb_hide_banner_on_plugin_page', 'contact-form-to-db/contact_form_to_db.php', '1.2' ),
				array( 'cntctfrmmlt_hide_banner_on_plugin_page', 'contact-form-multi/contact-form-multi.php', '1.0.7' ),
				array( 'gglmps_hide_banner_on_plugin_page', 'bws-google-maps/bws-google-maps.php', '1.2' ),
				array( 'fcbkbttn_hide_banner_on_plugin_page', 'facebook-button-plugin/facebook-button-plugin.php', '2.29' ),
				array( 'lmtttmpts_hide_banner_on_plugin_page', 'twitter-plugin/twitter.php', '2.34' ),
				array( 'pdfprnt_hide_banner_on_plugin_page', 'pdf-print/pdf-print.php', '1.7.1' ),
				array( 'gglplsn_hide_banner_on_plugin_page', 'google-one/google-plus-one.php', '1.1.4' ),
				array( 'gglstmp_hide_banner_on_plugin_page', 'google-sitemap-plugin/google-sitemap-plugin.php', '2.8.4' ),
				array( 'cntctfrmpr_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-pro/contact_form_pro.php', '1.14' ),
				array( 'cntctfrm_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.62' ),
				array( 'cntctfrm_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.47' ),
				array( 'cptch_hide_banner_on_plugin_page', 'captcha/captcha.php', '3.8.4' ),
				array( 'gllr_hide_banner_on_plugin_page', 'gallery-plugin/gallery-plugin.php', '3.9.1' )
			);
			if ( ! $lmtttmpts_plugin_info )
				$lmtttmpts_plugin_info = get_plugin_data( __FILE__ );
			
			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			$active_plugins = get_option( 'active_plugins' );			
			$all_plugins = get_plugins();
			$this_banner = 'lmtttmpts_hide_banner_on_plugin_page';
			foreach ( $banner_array as $key => $value ) {
				if ( $this_banner == $value[0] ) {
					global $wp_version;
		       		if ( ! isset( $bstwbsftwppdtplgns_cookie_add ) ) {
						echo '<script type="text/javascript" src="' . plugins_url( 'js/c_o_o_k_i_e.js', __FILE__ ) . '"></script>';
						$bstwbsftwppdtplgns_cookie_add = true;
					} ?>
					<script type="text/javascript">		
						(function($) {
							$(document).ready( function() {		
								var hide_message = $.cookie( "lmtttmpts_hide_banner_on_plugin_page" );
								if ( hide_message == "true") {
									$( ".lmtttmpts_message" ).css( "display", "none" );
								} else {
									$( ".lmtttmpts_message" ).css( "display", "block" );
								}
								$( ".lmtttmpts_close_icon" ).click( function() {
									$( ".lmtttmpts_message" ).css( "display", "none" );
									$.cookie( "lmtttmpts_hide_banner_on_plugin_page", "true", { expires: 32 } );
								});	
							});
						})(jQuery);				
					</script>
					<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">					                      
						<div class="lmtttmpts_message bws_banner_on_plugin_page" style="display: none;">
							<img class="lmtttmpts_close_icon close_icon" title="" src="<?php echo plugins_url( 'images/close_banner.png', __FILE__ ); ?>" alt=""/>
							<div class="button_div">
								<a class="button" target="_blank" href="http://bestwebsoft.com/plugin/limit-attempts-pro/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>"><?php _e( "Learn More", 'lmtttmpts' ); ?></a>				
							</div>
							<div class="text"><?php
								_e( "It's time to upgrade your <strong>Limit Attempts</strong> to <strong>PRO</strong> version", 'lmtttmpts' ); ?>!<br />
								<span><?php _e( 'Extend standard plugin functionality with new great options', 'lmtttmpts' ); ?>.</span>
							</div> 	
							<div class="icon">				
								<img title="" src="<?php echo plugins_url( 'images/banner.png', __FILE__ ); ?>" alt=""/>
							</div>	
						</div>  
					</div>
					<?php break;
				}
				if ( isset( $all_plugins[ $value[1] ] ) && $all_plugins[ $value[1] ]["Version"] >= $value[2] && ( 0 < count( preg_grep( '/' . str_replace( '/', '\/', $value[1] ) . '/', $active_plugins ) ) || is_plugin_active_for_network( $value[1] ) ) && ! isset( $_COOKIE[ $value[0] ] ) ) {
					break;
				}
			}    
		}
	}
}

/* 
* Function for deleting options when uninstal current plugin
*/
if ( ! function_exists( 'lmtttmpts_delete_options' ) ) { 
	function lmtttmpts_delete_options() {
		global $wpdb, $wpmu;
		$prefix = $wpdb->prefix . 'lmtttmpts_';
		if ( 1 == $wpmu )
			delete_site_option( 'lmtttmpts_options' );
		else
			delete_option( 'lmtttmpts_options' );

		$all_plugins = get_plugins();
		if ( ! array_key_exists( 'limit-attempts-pro/limit-attempts-pro.php', $all_plugins ) ) {
			$sql = "DROP TABLE `" . $prefix . "all_failed_attempts`, `" . $prefix . "failed_attempts`, `" . $prefix . "blacklist`, `" . $prefix . "whitelist`;";
			$wpdb->query( $sql );
		}
		wp_clear_scheduled_hook( 'lmtttmpts_daily_log_clear' );
	}
}

register_activation_hook( __FILE__, 'lmtttmpts_create_table' );
add_action( 'admin_menu', 'add_lmtttmpts_admin_menu' );
add_action( 'init', 'lmtttmpts_plugin_init' );
add_action( 'admin_init', 'lmtttmpts_plugin_admin_init' );
add_action( 'admin_enqueue_scripts', 'lmtttmpts_admin_head' );
add_filter( 'set-screen-option', 'lmtttmpts_table_set_option', 10, 3 );
add_filter( 'plugin_action_links', 'lmtttmpts_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'lmtttmpts_register_plugin_links', 10, 2 );
add_action( 'login_head', 'lmtttmpts_error_message' );
add_action( 'wp_login_failed', 'lmtttmpts_login_failed' );
add_filter( 'wp_authenticate_user', 'lmtttmpts_authenticate_user', 99999, 2 );
add_action( 'lmtttmpts_event_for_reset_failed_attempts', 'lmtttmpts_reset_failed_attempts' );
add_action( 'lmtttmpts_event_for_reset_block', 'lmtttmpts_reset_block' );
add_action( 'lmtttmpts_event_for_reset_block_quantity', 'lmtttmpts_reset_block_quantity' );
add_action( 'lmtttmpts_daily_log_clear', 'lmtttmpts_clear_log_daily' );
add_action( 'lmtttmpts_wrong_captcha', 'lmtttmpts_failed_with_captcha' );
add_action( 'admin_notices', 'lmtttmpts_show_notices' );
/* Adding banner */
add_action( 'admin_notices', 'lmtttmpts_plugin_banner' );
register_uninstall_hook( __FILE__, 'lmtttmpts_delete_options' );