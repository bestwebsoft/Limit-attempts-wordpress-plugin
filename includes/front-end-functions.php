<?php
/**
 * Functions to get or check necessary user`s data and
 * handle errors, which occured during an incorrect data entering
 * @package Limit Attempts
 * @since 1.1.4
 */

/**
 * Filter for authenticate access
 * @param       mixed           $user         an instance of class WP_Error/WP_User or null
 * @param       string          $username     value of "Username"-field
 * @param       string          $password     value of "Password"-field
 * @return        mixed         $user         an instance of class WP_Error/WP_User or null
 */
if ( ! function_exists( 'lmtttmpts_authenticate' ) ) {
	function lmtttmpts_authenticate( $user, $username, $password ) {
		global $lmtttmpts_options, $lmtttmpts_hide_form;
		$lmtttmpts_hide_form = false;

		/* get plugin`s options */
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			if ( ! $lmtttmpts_options ) {
				register_lmtttmpts_settings();
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			}
		}


		if ( isset( $_POST['wp-submit'] ) ) {
			$user = lmtttmpts_check_ip( $user );

			if ( ! is_wp_error( $user ) )
				return $user;

			$error_codes = $user->get_error_codes();
			$is_blocked  =
					is_array( $error_codes ) &&
					array_intersect( $error_codes, array( 'lmtttmpts_blacklisted', 'lmtttmpts_blocked' ) )
				?
					true
				:
					false;
			if ( ! $is_blocked )
				$user = lmtttmpts_handle_error( $user );

			$error_codes = is_wp_error( $user ) ? $user->get_error_codes() : false;
			$lmtttmpts_hide_form =
					is_array( $error_codes ) &&
					array_intersect( $error_codes, array( 'lmtttmpts_blacklisted', 'lmtttmpts_blocked' ) ) &&
					1 == $lmtttmpts_options['hide_login_form']
				?
					true
				:
					false;
		}

		return $user;
	}
}

/**
 * Check user`s IP during form data checking
 * @param    mixed    $user    object, an instance of classes WP_Error or WP_User or true
 * @return   mixed    $user    object, an instance of classes WP_Error or WP_User or true
 */
function lmtttmpts_form_check( $user = true ) {
	global $lmtttmpts_options, $lmtttmpts_hide_form;
	$lmtttmpts_hide_form = false;

		/* get plugin`s options */
	if ( empty( $lmtttmpts_options ) ) {
		$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		if ( ! $lmtttmpts_options ) {
			register_lmtttmpts_settings();
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
		}
	}

	if ( 0 == $lmtttmpts_options['hide_login_form'] )
		return $user;

	if ( is_wp_error( $user ) ) {
		$error_codes  = $user->get_error_codes();
		$ignore_codes = array( 'lmtttmpts_blacklisted', 'lmtttmpts_blocked' );
		$check_ip     = ! is_array( $error_codes ) || ! array_intersect( $error_codes, $ignore_codes ) ? true : false;
	} else {
		$check_ip = false;
	}

	if ( $check_ip )
		$user = lmtttmpts_check_ip( $user );

	if ( is_wp_error( $user ) ) {
		$error_codes = $user->get_error_codes();
		$hide_form   = is_array( $error_codes ) && array_intersect( $error_codes, array( 'lmtttmpts_blacklisted', 'lmtttmpts_blocked' ) ) ? true : false;
		if ( in_array( 'lmtttmpts_error', (array)$error_codes ) && $hide_form )
			$user->remove( 'lmtttmpts_error' );
		$lmtttmpts_hide_form = $hide_form ? true : false;
	}
	return $user;
}

/**
 * Check whether user`s IP in blacklist or it is blocked
 * @param      mixed       an instance of class WP_Error/WP_User or null
 * @return     mixed       an instance of class WP_Error/WP_User or null
 */
if ( ! function_exists( "lmtttmpts_check_ip" ) ) {
	function lmtttmpts_check_ip( $user = true ) {
		global $wpdb, $lmtttmpts_options;

		/* get plugin`s options */
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			if ( ! $lmtttmpts_options ) {
				register_lmtttmpts_settings();
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			}
		}

		/* get user`s IP */
		$ip = lmtttmpts_get_ip();

		/* check if ip in blacklist */
		if ( lmtttmpts_is_ip_in_table( $ip, 'blacklist' ) ) {
			/* create new WP_ERROR object to skip brute force */
			$user  = new WP_Error();
			$error = str_replace( '%MAIL%', $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
			$error = wp_specialchars_decode( $error, ENT_COMPAT );
			$user->add( 'lmtttmpts_blacklisted', $error );
			return $user;
		}

		/* check if ip in blocked list */
		$ip_info = lmtttmpts_is_ip_blocked( $ip );
		if ( $ip_info && is_array( $ip_info ) && ! empty( $ip_info ) ) {
			$block_till =
					! isset( $ip_info['block_till'] ) ||
					is_null( $ip_info['block_till'] )
				?
					date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400 )
				:
					$ip_info['block_till'];
			if ( ! is_wp_error( $user ) )
				$user = new WP_Error();
			$error = str_replace(
					array( '%DATE%', '%MAIL%' ),
					array( lmtttmpts_block_time( $block_till ), $lmtttmpts_options['email_address'] ),
					$lmtttmpts_options['blocked_message']
				);
			$error = wp_specialchars_decode( $error, ENT_COMPAT );
			$user->add( 'lmtttmpts_blocked', $error );
		}
		return $user;
	}
}

/**
 * Add info about user`s IP in to database
 * @param     mixed       an instance of class WP_Error/WP_User or string
 * @return    mixed       an instance of class WP_Error/WP_User or string
 */
if ( ! function_exists( "lmtttmpts_handle_error" ) ) {
	function lmtttmpts_handle_error( $user = true ) {
		global $wpdb, $lmtttmpts_options;

		/* get plugin`s options */
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			if ( ! $lmtttmpts_options ) {
				register_lmtttmpts_settings();
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			}
		}

		/* get necessary data */
		$ip          = lmtttmpts_get_ip();
		$ip_int      = sprintf( '%u', ip2long( $ip ) );
		$prefix      = "{$wpdb->prefix}lmtttmpts_";
		$timestamp   = time();
		$time_offset = $lmtttmpts_options['minutes_of_lock'] * 60 + $lmtttmpts_options['hours_of_lock'] * 3600 + $lmtttmpts_options['days_of_lock'] * 86400;
		$start_time  = $timestamp + $time_offset;
		$wp_error    =  is_wp_error( $user );
		$error = $error_code = '';

		/* get full info about IP */
		$ip_info = $wpdb->get_results(
			"SELECT
				`{$prefix}failed_attempts`.`block` AS `blocked`,
				`{$prefix}failed_attempts`.`failed_attempts`,
				`{$prefix}failed_attempts`.`block_quantity`,
				`{$prefix}failed_attempts`.`block_till`,
				`{$prefix}all_failed_attempts`.`failed_attempts` AS `stat_attempts_number`,
				`{$prefix}all_failed_attempts`.`block_quantity` AS `stat_block_quantity`,
				`{$prefix}blacklist`.`id` AS `in_blacklist`,
				`{$prefix}whitelist`.`id` AS `in_whitelist`
			FROM `{$prefix}failed_attempts`
			LEFT JOIN `{$prefix}all_failed_attempts`
				ON `{$prefix}all_failed_attempts`.`ip_int`=`{$prefix}failed_attempts`.`ip_int`
			LEFT JOIN `{$prefix}blacklist`
				ON `{$prefix}blacklist`.`ip_from_int`<={$ip_int} AND `{$prefix}blacklist`.`ip_to_int`>={$ip_int}
			LEFT JOIN `{$prefix}whitelist`
				ON `{$prefix}whitelist`.`ip_from_int`<={$ip_int} AND `{$prefix}whitelist`.`ip_to_int`>={$ip_int}
			WHERE `{$prefix}failed_attempts`.`ip_int`={$ip_int}
			LIMIT 1;"
		);
		/* if IP in blacklist */
		if ( isset( $ip_info[0]->in_blacklist ) && ! is_null( $ip_info[0]->in_blacklist ) ) {
			$error = str_replace( '%MAIL%', $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
			$error = wp_specialchars_decode( $error, ENT_COMPAT );
			/* create new WP_ERROR object to skip brute force */
			$user = new WP_Error();
			$user->add( 'lmtttmpts_blacklisted', $error );
			return $user;
		}

		/* if IP is blocked */
		if ( isset( $ip_info[0]->blocked ) && '1' == $ip_info[0]->blocked ) {
			$block_till =
					isset( $ip_info[0]->block_till ) && ! empty( $ip_info[0]->block_till )
				?
					$ip_info[0]->block_till
				:
					date( 'Y-m-d H:i:s', $start_time );
			$error = str_replace( array( '%DATE%', '%MAIL%' ), array( lmtttmpts_block_time( $block_till ), $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] );
			$error = wp_specialchars_decode( $error, ENT_COMPAT );
			if ( ! $wp_error )
				$user = new WP_Error;
			$user->add( 'lmtttmpts_blocked', $error );
			return $user;
		}

		/* get some additional data */
		$ip_in_whitelist = isset( $ip_info[0]->in_whitelist ) && ! is_null( $ip_info[0]->in_whitelist ) ? true : false;
		$error_codes     = $user->get_error_codes();
		$array_intersect = is_array( $error_codes ) ? array_intersect( $error_codes, array( 'cptchpr_error', 'cptchpls_error', 'cptch_error' ) ) : array();
		if ( $ip_in_whitelist || ( ! empty( $array_intersect ) && ! isset( $lmtttmpts_options['login_form_captcha_check'] ) ) ) {
			/*
			* skip errors handling for whitelisted ip or
			* for BWS CAPTCHA`s errors if corresponding option is disabled
			*/
			$event = 'failed_attempt';
		} else {
			if ( isset( $ip_info[0]->failed_attempts ) && ! is_null( $ip_info[0]->failed_attempts ) ) {
				$failed_attempts_number = $ip_info[0]->failed_attempts + 1;
				/*
				 * if IP will be blocked
				 */
				if ( $failed_attempts_number >= $lmtttmpts_options['allowed_retries'] ) {
					$event = 'auto_blocked';
					$failed_attempts_number = 0;
					$block_quantity = isset( $ip_info[0]->block_quantity ) ? $ip_info[0]->block_quantity + 1 : 1;
					$block = 1;
					$block_till   = date( 'Y-m-d H:i:s', $start_time );
					$add_to_blacklist = $block_quantity >= $lmtttmpts_options['allowed_locks'] ? true : false;
					/* clear hook for reset the number of failed attempts */
					wp_clear_scheduled_hook( 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );

					/* if IP will not be added to blacklist  */
					if ( ! $add_to_blacklist ) {
						/* countdown to reset block */
						if ( ! wp_next_scheduled( 'lmtttmpts_event_for_reset_block' ) )
							wp_schedule_single_event( $start_time, 'lmtttmpts_event_for_reset_block' );
						/* if this first block (maybe after reset) then they will be reset after some time */
						if ( 1 == $block_quantity ) {
							$reset_time = $timestamp + $lmtttmpts_options['minutes_to_reset_block'] * 60 + $lmtttmpts_options['hours_to_reset_block'] * 3600 + $lmtttmpts_options['days_to_reset_block'] * 86400;
							wp_schedule_single_event( $reset_time, 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
						}
						/*
						 * interaction with Htaccess plugin for blocking
						 * hook for blocking by Htaccess
						 */
						if ( 1 == $lmtttmpts_options["block_by_htaccess"] )
							do_action( 'lmtttmpts_htaccess_hook_for_block', $ip );
						/* send e-mail to admin */
						if ( $lmtttmpts_options['notify_email'] ) {
							lmtttmpts_send_email(
								$lmtttmpts_options['email_address'],
								$lmtttmpts_options['email_subject'],
								$lmtttmpts_options['email_blocked'],
								$ip
							);
						}
						/* create new WP_ERROR object to skip brute force */
						if ( $wp_error )
							$user = new WP_Error();
						/* getting an error message */
						$error_code = 'lmtttmpts_blocked';
						$error      = str_replace( array( '%DATE%', '%MAIL%' ), array( lmtttmpts_block_time( $block_till ), $lmtttmpts_options['email_address'] ), $lmtttmpts_options['blocked_message'] );
						$error      = wp_specialchars_decode( $error, ENT_COMPAT );
					}
				} else {
					$event = 'failed_attempt';
					$block = 0;
					$block_quantity   = isset( $ip_info[0]->block_quantity ) ? $ip_info[0]->block_quantity : 0;
					$block_till       = null;
					$add_to_blacklist = false;
					/* countdown to reset failed attempts */
					if ( 1 == $failed_attempts_number ) {
						$reset_time = $timestamp + $lmtttmpts_options['minutes_to_reset'] * 60 + $lmtttmpts_options['hours_to_reset'] * 3600 + $lmtttmpts_options['days_to_reset'] * 86400;
						wp_schedule_single_event( $reset_time, 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );
					}
					/* getting an error message */
					$error_code = 'lmtttmpts_failed_attempts';
					$error      = str_replace( '%ATTEMPTS%', max( $lmtttmpts_options['allowed_retries'] - $failed_attempts_number, 0 ), $lmtttmpts_options['failed_message'] );
					$error      = wp_specialchars_decode( $error, ENT_COMPAT );
				}
				/*
				 * if IP will be added to blacklist
				 */
				if ( $add_to_blacklist ) {
					$add_reason = serialize( array(
						'added_by' 		=> 'auto',
						'blocks_number' => $block_quantity
					) );
					$event = 'auto_blacklisted';
					$block = 0;
					$block_quantity = 0;
					$block_till     = null;
					/* delete hook for reset the number of blocks */
					wp_clear_scheduled_hook( 'lmtttmpts_event_for_reset_block_quantity', array( $ip ) );
					/*
					 * interaction with Htaccess plugin for blocking
					 * hook for blocking by Htaccess
					 */
					if ( 1 == $lmtttmpts_options["block_by_htaccess"] )
						do_action( 'lmtttmpts_htaccess_hook_for_block', $ip );
					/*
					 * update blacklist
					 */
					$wpdb->insert(
						"{$prefix}blacklist",
						array(
							'ip' 			=> $ip,
							'ip_from_int' 	=> $ip_int,
							'ip_to_int' 	=> $ip_int,
							'add_time' 		=> date( 'Y-m-d H:i:s', $timestamp )
						)
					);
					/* send e-mail to admin */
					if ( $lmtttmpts_options['notify_email'] ) {
						lmtttmpts_send_email(
							$lmtttmpts_options['email_address'],
							$lmtttmpts_options['email_subject_blacklisted'],
							$lmtttmpts_options['email_blacklisted'],
							$ip
						);
					}
					/* getting an error message */
					$error_code = 'lmtttmpts_blacklisted';
					$error      = str_replace( '%MAIL%', $lmtttmpts_options['email_address'], $lmtttmpts_options['blacklisted_message'] );
					$error      = wp_specialchars_decode( $error, ENT_COMPAT );
				}

				/*
				 * update failed attempt info
				 */
				$wpdb->update(
					"{$prefix}failed_attempts",
					array(
						'failed_attempts' => $failed_attempts_number,
						'block'           => $block,
						'block_quantity'  => $block_quantity,
						'block_till'      => $block_till
					),
					array( 'ip_int' => $ip_int )
				);
			} else {
				$event = 'failed_attempt';
				/* add data about failed attempts */
				$wpdb->insert(
					"{$prefix}failed_attempts",
					array(
						'ip'              => $ip,
						'ip_int'          => $ip_int,
						'failed_attempts' => 1
					)
				);
				/* countdown to reset failed attempts */
				$reset_time = $timestamp + $lmtttmpts_options['minutes_to_reset'] * 60 + $lmtttmpts_options['hours_to_reset'] * 3600 + $lmtttmpts_options['days_to_reset'] * 86400;
				wp_schedule_single_event( $reset_time, 'lmtttmpts_event_for_reset_failed_attempts', array( $ip ) );
				/* getting an error message */
				$error_code = 'lmtttmpts_failed_attempts';
				$error      = str_replace( '%ATTEMPTS%', max( $lmtttmpts_options['allowed_retries'] - 1, 0 ), $lmtttmpts_options['failed_message'] );
				$error      = wp_specialchars_decode( $error, ENT_COMPAT );
			}
		}

		/*
		 * update statistics
		 */
		if ( ( ! isset( $ip_info[0]->stat_attempts_number ) ) || is_null( $ip_info[0]->stat_attempts_number ) ) {
			/**
			 * Checking for existence of statistical data
			 * @deprecated since 1.1.3
			 * @todo remove after 1.06.2016 except $wpdb->insert( "{$prefix}all_failed_attempts" ... ); rows
			 */
			$stat_data = $wpdb->get_row( "SELECT * FROM `{$prefix}all_failed_attempts` WHERE `ip_int`={$ip_int};", ARRAY_A );
			if ( is_array( $stat_data ) && ! empty( $stat_data ) ) {
				$attempts_number = $stat_data['failed_attempts'] + 1;
				$block_number    = ! $ip_in_whitelist && $stat_data['block_quantity'] + 1 >= $lmtttmpts_options['allowed_retries'] ? $stat_data['block_quantity'] + 1 : $stat_data['block_quantity'];
				$wpdb->update(
					"{$prefix}all_failed_attempts",
					array(
						'failed_attempts'		=> $attempts_number,
						'block_quantity'		=> $block_number,
						'last_failed_attempt'	=> date( 'Y-m-d H:i:s', $timestamp )
					),
					array(
						'ip' => $ip
					)
				);
			} else {
				$wpdb->insert(
					"{$prefix}all_failed_attempts",
					array(
						'ip' 					=> $ip,
						'ip_int'				=> $ip_int,
						'failed_attempts'		=> 1,
						'block_quantity'		=> 0,
						'last_failed_attempt'	=> date( 'Y-m-d H:i:s', $timestamp )
					)
				);
			}
		} else {
			$attempts_number = $ip_info[0]->stat_attempts_number + 1;
			$block_number    = ! $ip_in_whitelist && $ip_info[0]->failed_attempts + 1 >= $lmtttmpts_options['allowed_retries'] ? $ip_info[0]->stat_block_quantity + 1 : $ip_info[0]->stat_block_quantity;
			$wpdb->update(
				"{$prefix}all_failed_attempts",
				array(
					'failed_attempts'		=> $attempts_number,
					'block_quantity'		=> $block_number,
					'last_failed_attempt'	=> date( 'Y-m-d H:i:s', $timestamp )
				),
				array(
					'ip' => $ip
				)
			);
		}

		if ( ! empty( $error_code ) && ! empty( $error ) ) {
			if ( ! $wp_error || 'lmtttmpts_blacklisted' == $error_code )
				$user = new WP_Error;
			$user->add( $error_code, $error );
		}
		return $user;
	}
}

/**
 * Send e-mails to admin
 * with notices about blocked or blacklisted IPs
 * @param   string   $to        admin e-mail
 * @param   string   $subject   subject of message
 * @param   string   $message   text of message
 * @param   string   $ip        blocked/blacklisted IP
 * @return  void
 */
if ( ! function_exists( 'lmtttmpts_send_email' ) ) {
	function lmtttmpts_send_email( $to, $subject, $message, $ip ) {
		$subject = str_replace(
			array( '%IP%', '%SITE_NAME%' ),
			array( $ip, get_bloginfo( 'name' ) ),
			$subject
		);
		$message = str_replace(
			array( '%IP%', '%PLUGIN_LINK%', '%WHEN%', '%SITE_NAME%', '%SITE_URL%' ),
			array( "{$ip}", esc_url( admin_url( 'admin.php?page=limit-attempts.php' ) ), current_time( 'mysql' ), get_bloginfo( 'name' ), esc_url( site_url() ) ),
			$message
		);
		add_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
		wp_mail( $to, wp_specialchars_decode( $subject, ENT_COMPAT ), wp_specialchars_decode( $message, ENT_COMPAT ) );
		remove_filter( 'wp_mail_content_type', 'lmtttmpts_set_html_content_type' );
	}
}

/**
 * Filter to transfer message in html format
 * @param   void
 * @return  string  content type of e-mail
 */
if ( ! function_exists( 'lmtttmpts_set_html_content_type' ) ) {
	function lmtttmpts_set_html_content_type() {
		return 'text/html';
	}
}

/**
 * Hide login/lostpassword/register forms for blacklisted or blocked IPs
 * @param  void
 * @return void
 */
if ( ! function_exists( 'lmtttmpts_login_scripts' ) ) {
	function lmtttmpts_login_scripts() {
		global $lmtttmpts_options, $lmtttmpts_hide_form, $error;

		/* get plugin`s options */
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			if ( ! $lmtttmpts_options ) {
				register_lmtttmpts_settings();
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			}
		}

		/*
		 * Check user`s IP on "login", "register" and "lostpassword" pages before sending of forms
		 */
		if ( ! isset( $_REQUEST['wp-submit'] ) && 1 == $lmtttmpts_options['hide_login_form'] ) {
			$result = lmtttmpts_check_ip();
			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
				$lmtttmpts_hide_form = true;
			}
		}
		if ( $lmtttmpts_hide_form ) {
			echo
			'<style type="text/css">
				.login #loginform,
				.login #registerform,
				.login #lostpasswordform,
				.login .message,
				.login #nav {
					display: none;
				}
			</style>';
		}
	}
}


/**
 * Hide register forms for blacklisted or blocked IPs on multisite
 * @param  void
 * @return void
 */
if ( ! function_exists( 'lmtttmpts_signup_scripts' ) ) {
	function lmtttmpts_signup_scripts() {
		global $lmtttmpts_options, $lmtttmpts_hide_form, $error;

		/* get plugin`s options */
		if ( empty( $lmtttmpts_options ) ) {
			$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			if ( ! $lmtttmpts_options ) {
				register_lmtttmpts_settings();
				$lmtttmpts_options = get_option( 'lmtttmpts_options' );
			}
		}

		if ( 0 == $lmtttmpts_options['hide_login_form'] )
			return false;

		$result = lmtttmpts_check_ip();
		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_message();
			if ( ! $lmtttmpts_hide_form )
				$lmtttmpts_hide_form = true;
			add_action( 'after_signup_form', 'lmtttmpts_display_error' );
		}

		if ( $lmtttmpts_hide_form ) {
			echo
			'<style type="text/css">
				#setupform {
					display: none;
				}
			</style>';
		}
	}
}

/**
 * Display error message on "register" page on multisite
 * @param  void
 * @return void
 */
if ( ! function_exists( 'lmtttmpts_display_error' ) ) {
	function lmtttmpts_display_error() {
		global $error;
		echo
			'<div class="widecolumn" id="lmtttmpts_mu_error">
				<div class="mu_register wp-signup-container">
					<p class="error">' .
						$error .
					'</p>
				</div>
			</div>';
	}
}


/**
 * How much time is left until the moment when IP would be unblocked
 * @param    string   $unblock_date
 * @return   string
 */
if ( ! function_exists( 'lmtttmpts_block_time' ) ) {
	function lmtttmpts_block_time ( $unblock_date ) {
		/* time difference */
		$time_diff = strtotime( $unblock_date ) - time();

		/* time limit for blocking has been exhausted or unidentified */
		if ( $time_diff <= 0 ) {
			$string = __( 'some time. Try to reload the page. Perhaps, you already have been unlocked', 'limit-attempts' );
			lmtttmpts_reset_block();
			return $string;
		}

		/* less then 1 months */
		if ( $time_diff > 0 && $time_diff < 2635200 ) {

			$weeks   = intval( $time_diff / 604800 );
			$string  = ( 0 < $weeks ? '&nbsp;' . $weeks . '&nbsp;' . _n( 'week', 'weeks', $weeks, 'limit-attempts' ) : '' );
			$sum     = $weeks * 604800;

			$days    = intval( ( $time_diff - $sum ) / 86400 );
			$string .= ( 0 < $days ? '&nbsp;' . $days . '&nbsp;' . _n( 'day', 'days', $days, 'limit-attempts' ) : '' );
			$sum    += $days * 86400;

			$hours   = intval( ( $time_diff - $sum ) / 3600 );
			$string .= ( 0 < $hours ? '&nbsp;' . $hours . '&nbsp;' . _n( 'hour', 'hours', $hours, 'limit-attempts' ) : '' );
			$sum    += $hours * 3600;

			$minutes = intval( ( $time_diff - $sum ) / 60 );
			$string .= ( 0 < $minutes ? '&nbsp;' . $minutes . '&nbsp;' . _n( 'minute', 'minutes', $minutes, 'limit-attempts' ) : '' );
			$sum    += $minutes * 60;

			$seconds = $time_diff - $sum;
			$string .= ( 0 < $seconds ? '&nbsp;' . $seconds . '&nbsp;' . _n( 'second', 'seconds', $seconds, 'limit-attempts' ) : '' );

			return $string;
		}

		/* from 1 to 6 months */
		if ( $time_diff >= 2635200 && $time_diff < 15768000 ) {
			$months = intval( $time_diff / 2635200 );
			$days   = $time_diff % 2635200;
			$days_string = 0 < $days ? '&nbsp;' . $days . '&nbsp;' . _n( 'day', 'days', $days, 'limit-attempts' ) : '';
			return $months .'&nbsp;' . _n( 'month', 'months', $months, 'limit-attempts' ) . $days;
		}

		/* from 6 to 12 months */
		if ( $time_diff >= 15768000 && $time_diff < 31536000 )
			return round( $time_diff / 15768000, 2 ) . '&nbsp;' . __( 'months', 'limit-attempts' );

		/* more than one year */
		if ( $time_diff >= 31536000 ) {
			$years = round( $time_diff / 31536000, 2 );
			return $years . '&nbsp;' . _n( 'year', 'years', $years, 'limit-attempts' );
		}

		return false;
	}
}

/* handle login form */
add_filter( 'authenticate', 'lmtttmpts_authenticate', 99999, 3 );
add_filter( 'allow_password_reset', 'lmtttmpts_form_check', 99999 );
add_filter( 'registration_errors', 'lmtttmpts_form_check', 99999, 1 );
add_action( 'login_head', 'lmtttmpts_login_scripts' );
add_action( 'signup_header', 'lmtttmpts_signup_scripts' );
?>
