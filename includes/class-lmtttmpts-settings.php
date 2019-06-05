<?php
/**
 * Displays the content on the plugin settings page
 */

require_once( dirname( dirname( __FILE__ ) ) . '/bws_menu/class-bws-settings.php' );

if ( ! class_exists( 'Lmtttmpts_Settings_Tabs' ) ) {
	class Lmtttmpts_Settings_Tabs extends Bws_Settings_Tabs {
		public $active_plugins;
		public $all_plugins;
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename
		 */
		public function __construct( $plugin_basename ) {
			global $lmtttmpts_options, $lmtttmpts_plugin_info;

			$tabs = array(
				'settings' 		=> array( 'label' => __( 'Settings', 'limit-attempts' ) ),
				'errors' 		=> array( 'label' => __( 'Errors', 'limit-attempts' ) ),
				'notifications' => array( 'label' => __( 'Notifications', 'limit-attempts' ) ),
				'misc' 			=> array( 'label' => __( 'Misc', 'limit-attempts' ) ),
				'custom_code' 	=> array( 'label' => __( 'Custom Code', 'limit-attempts' ) ),
				'license'		=> array( 'label' => __( 'License Key', 'limit-attempts' ) )
			);

			parent::__construct( array(
				'plugin_basename' 	 => $plugin_basename,
				'plugins_info'		 => $lmtttmpts_plugin_info,
				'prefix' 			 => 'lmtttmpts',
				'default_options' 	 => lmtttmpts_get_options_default(),
				'options' 			 => $lmtttmpts_options,
				'tabs' 				 => $tabs,
				'wp_slug'			 => 'limit-attempts',
				'pro_page' 			 => 'admin.php?page=limit-attempts-pro.php',
				'bws_license_plugin' => 'limit-attempts-pro/limit-attempts-pro.php',
				'link_key' 			 => 'fdac994c203b41e499a2818c409ff2bc',
				'link_pn' 			 => '140'
			) );

			add_action( get_parent_class( $this ) . '_additional_misc_options_affected', array( $this, 'additional_misc_options_affected' ) );
		}

		/**
		 * Save plugin options to the database
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function save_options() {
			global $wpdb;

			$message = '';

			if ( ! $this->all_plugins ) {
				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$this->all_plugins = get_plugins();
			}
			if ( ! $this->active_plugins ) {
				if ( $this->is_multisite ) {
					$this->active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					$this->active_plugins = array_merge( $this->active_plugins , get_option( 'active_plugins' ) );
				} else {
					$this->active_plugins = get_option( 'active_plugins' );
				}
			}

			$numeric_options = array(
				'allowed_retries', 'days_of_lock', 'hours_of_lock', 'minutes_of_lock',
				'days_to_reset', 'hours_to_reset', 'minutes_to_reset', 'allowed_locks',
				'days_to_reset_block', 'hours_to_reset_block', 'minutes_to_reset_block'
			);
			$force_reset_block_event = false;
			foreach ( $numeric_options as $option ) {
				if ( isset( $_POST["lmtttmpts_{$option}"] ) && $_POST["lmtttmpts_{$option}"] != $this->options[ $option ] )
					$force_reset_block_event = true;
				$this->options[ $option ] = isset( $_POST["lmtttmpts_{$option}"] ) ? $_POST["lmtttmpts_{$option}"] : $this->options[ $option ];
			}
			if ( $this->options['days_of_lock'] == 0 && $this->options['hours_of_lock'] == 0 && $this->options['minutes_of_lock'] == 0 )
				$this->options['minutes_of_lock'] = 1;
			if ( $this->options['days_to_reset'] == 0 && $this->options['hours_to_reset'] == 0 && $this->options['minutes_to_reset'] == 0 )
				$this->options['minutes_to_reset'] = 1;
			if ( $this->options['days_to_reset_block'] == 0 && $this->options['hours_to_reset_block'] == 0 && $this->options['minutes_to_reset_block'] == 0 )
				$this->options['minutes_to_reset_block'] = 1;

			if ( $force_reset_block_event ) {
				wp_clear_scheduled_hook( 'lmtttmpts_event_for_reset_block' );
				lmtttmpts_reset_block();
			}

			if ( isset( $_POST["lmtttmpts_days_to_clear_statistics"] ) ) {
				if ( $this->options["days_to_clear_statistics"] != $_POST["lmtttmpts_days_to_clear_statistics"] ) {
					if ( $this->options["days_to_clear_statistics"] == 0 ) {
						wp_schedule_event( $time, 'daily', "lmtttmpts_daily_statistics_clear" );
					} elseif ( $_POST["lmtttmpts_days_to_clear_statistics"] == 0 ) {
						wp_clear_scheduled_hook( "lmtttmpts_daily_statistics_clear" );
					}
				}
				$this->options["days_to_clear_statistics"] = $_POST["lmtttmpts_days_to_clear_statistics"];
			}

			$this->options['hide_login_form'] = isset( $_POST['lmtttmpts_hide_login_form'] ) ? 1: 0;

			/* Updating options of interaction with Htaccess plugin */
			$htaccess_is_active = 0 < count( preg_grep( '/htaccess\/htaccess.php/', $this->active_plugins ) ) || 0 < count( preg_grep( '/htaccess-pro\/htaccess-pro.php/', $this->active_plugins ) ) ? true : false;
			if ( isset( $_POST['lmtttmpts_block_by_htaccess'] ) ) {
				if ( $htaccess_is_active && 0 == $this->options['block_by_htaccess'] ) {
					$blocked_ips = $wpdb->get_col( "SELECT `ip` FROM `{$wpdb->prefix}lmtttmpts_blacklist`;" );
					if ( is_array( $blocked_ips ) && ! empty( $blocked_ips ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_block', $blocked_ips );
					}

					$whitelisted_ips = $wpdb->get_col( "SELECT `ip` FROM `{$wpdb->prefix}lmtttmpts_whitelist`;" );
					if ( is_array( $whitelisted_ips ) && ! empty( $whitelisted_ips ) ) {
						do_action( 'lmtttmpts_htaccess_hook_for_add_to_whitelist', $whitelisted_ips );
					}
				}
				$this->options['block_by_htaccess'] = 1;
			} else {
				if ( $htaccess_is_active && 1 == $this->options['block_by_htaccess'] ) {
					do_action( 'lmtttmpts_htaccess_hook_for_delete_all' );
				}
				$this->options['block_by_htaccess'] = 0;
			}

			/*Updating options of interaction with Captcha plugin in login form*/
			if ( isset( $_POST['lmtttmpts_login_form_captcha_check'] ) )
				$this->options['login_form_captcha_check'] = $_POST['lmtttmpts_login_form_captcha_check'];
			else
				unset( $this->options['login_form_captcha_check'] );

			if ( isset( $_POST['lmtttmpts_login_form_recaptcha_check'] ) )
				$this->options['login_form_recaptcha_check'] = $_POST['lmtttmpts_login_form_recaptcha_check'];
			else
				unset( $this->options['login_form_recaptcha_check'] );

			/* Updating options with notify by email options */
			$this->options['notify_email'] = isset( $_POST['lmtttmpts_notify_email'] ) && ! empty( $_POST['lmtttmpts_email_blacklisted'] ) && ! empty( $_POST['lmtttmpts_email_blocked'] ) ? true : false;
			if ( isset( $_POST['lmtttmpts_mailto'] ) ) {
				$this->options['mailto'] = $_POST['lmtttmpts_mailto'];
				if ( 'admin' == $_POST['lmtttmpts_mailto'] && isset( $_POST['lmtttmpts_user_email_address'] ) ) {
					$this->options['email_address'] = $_POST['lmtttmpts_user_email_address'];
				} elseif ( 'custom' == $_POST['lmtttmpts_mailto'] && isset( $_POST['lmtttmpts_email_address'] ) && is_email( $_POST['lmtttmpts_email_address'] ) ) {
					$this->options['email_address'] = $_POST['lmtttmpts_email_address'];
				}
			}
			/* array for saving and restoring default messages */
			$messages = array(
				'failed_message', 'blocked_message', 'blacklisted_message', 'email_subject', 'email_subject_blacklisted',
				'email_blocked', 'email_blacklisted'
			);
			/* Update messages when login failed, address blocked or blacklisted, email subject and text when address blocked or blacklisted */
			foreach ( $messages as $single_message ) {
				if ( ! empty( $_POST["lmtttmpts_{$single_message}"] ) )
					$this->options[ $single_message ] = trim( esc_html( $_POST["lmtttmpts_{$single_message}"] ) );
			}

			/* Restore default messages */
			if ( isset( $_POST['lmtttmpts_return_default'] ) ) {
				$default_messages = lmtttmpts_get_default_messages();
				if ( 'email' == $_POST['lmtttmpts_return_default'] ) {
					unset( $default_messages['failed_message'], $default_messages['blocked_message'], $default_messages['blacklisted_message'] );
					$message = __( 'Email notifications have been restored to default.', 'limit-attempts' ) . '<br />';
				} else {
					unset( $default_messages['email_subject'], $default_messages['email_subject_blacklisted'], $default_messages['email_blocked'], $default_messages['email_blacklisted'] );
					$message = __( 'Messages have been restored to default.', 'limit-attempts' ) . '<br />';
				}
				foreach ( $default_messages as $key => $value ) {
					$this->options[ $key ] = $value;
				}
			}

			$this->options = array_map( 'stripslashes_deep', $this->options );

			$message .= __( 'Settings saved.', 'limit-attempts' );

			update_option( 'lmtttmpts_options', $this->options );

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 *
		 */
		public function tab_settings() {
			global $wp_version;
			if ( ! $this->all_plugins ) {
				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$this->all_plugins = get_plugins();
			}
			if ( ! $this->active_plugins ) {
				if ( $this->is_multisite ) {
					$this->active_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					$this->active_plugins = array_merge( $this->active_plugins , get_option( 'active_plugins' ) );
				} else {
					$this->active_plugins = get_option( 'active_plugins' );
				}
			} ?>
			<h3 class="bws_tab_label"><?php _e( 'Limit Attempts Settings', 'limit-attempts' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table lmtttmpts_settings_form">
				<tr>
					<th><?php _e( 'Block IP Address After', 'limit-attempts' ); ?></th>
					<td>
						<input type="number" min="1" max="99" step="1" maxlength="2" value="<?php echo $this->options['allowed_retries']; ?>" name="lmtttmpts_allowed_retries" /> <?php echo _n( 'attempt', 'attempts', $this->options['allowed_retries'], 'limit-attempts' ); ?>
						<div class="bws_info"><?php printf( __( 'Number of failed attempts (default is %d).', 'limit-attempts' ), 5 ); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Block IP Address For', 'limit-attempts' ); ?></th>
					<td>
						<fieldset id="lmtttmpts-time-of-lock-display" class="lmtttmpts_hidden lmtttmpts-display">
							<label<?php if ( 0 == $this->options['days_of_lock'] ) echo ' class="lmtttmpts-zero-value"'; ?>><span class="lmtttmpts-unit-measure" ><?php echo $this->options['days_of_lock']; ?></span> <?php echo _n( 'day', 'days', $this->options['days_of_lock'], 'limit-attempts' ); ?></label>
							<label<?php if ( 0 == $this->options['hours_of_lock'] ) echo ' class="lmtttmpts-zero-value"'; ?>><span class="lmtttmpts-unit-measure" ><?php echo $this->options['hours_of_lock']; ?></span> <?php echo _n( 'hour', 'hours', $this->options['hours_of_lock'], 'limit-attempts' ); ?></label>
							<label<?php if ( 0 == $this->options['minutes_of_lock'] ) echo ' class="lmtttmpts-zero-value"'; ?>><span class="lmtttmpts-unit-measure" ><?php echo $this->options['minutes_of_lock']; ?></span> <?php echo _n( 'minute', 'minutes', $this->options['minutes_of_lock'], 'limit-attempts' ); ?></label>
							<label id="lmtttmpts-time-of-lock-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'limit-attempts' ); ?></label>
						</fieldset>
						<fieldset id="lmtttmpts-time-of-lock" class="lmtttmpts-hidden-input">
							<label><input id="lmtttmpts-days-of-lock-display" type="number" max="999" min="0" step="1" maxlength="3" value="<?php echo $this->options['days_of_lock']; ?>" name="lmtttmpts_days_of_lock" /> <?php echo _n( 'day', 'days', $this->options['days_of_lock'], 'limit-attempts' ); ?></label>
							<label><input id="lmtttmpts-hours-of-lock-display" type="number" max="23" min="0" step="1" maxlength="2" value="<?php echo $this->options['hours_of_lock']; ?>" name="lmtttmpts_hours_of_lock" /> <?php echo _n( 'hour', 'hours', $this->options['hours_of_lock'], 'limit-attempts' ); ?></label>
							<label><input id="lmtttmpts-minutes-of-lock-display" type="number" max="59" min="0" step="1" maxlength="2" value="<?php echo $this->options['minutes_of_lock']; ?>" name="lmtttmpts_minutes_of_lock" /> <?php echo _n( 'minute', 'minutes', $this->options['minutes_of_lock'], 'limit-attempts' ); ?></label>
						</fieldset>
						<div class="bws_info">
							<?php printf( __( 'Time IP address will be blocked for (default is %d hour %d minutes).', 'limit-attempts' ), 1, 30); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Reset Failed Attempts After', 'limit-attempts' ); ?></th>
					<td>
						<fieldset id="lmtttmpts-time-to-reset-display" class="lmtttmpts_hidden lmtttmpts-display">
							<label <?php if ( 0 == $this->options['days_to_reset'] ) echo 'class="lmtttmpts-zero-value"'; ?> > <span class="lmtttmpts-unit-measure" ><?php echo $this->options['days_to_reset']; ?></span> <?php echo _n( 'day', 'days', $this->options['days_to_reset'], 'limit-attempts' ); ?></label>
							<label <?php if ( 0 == $this->options['hours_to_reset'] ) echo 'class="lmtttmpts-zero-value"'; ?> > <span class="lmtttmpts-unit-measure" ><?php echo $this->options['hours_to_reset']; ?></span> <?php echo _n( 'hour', 'hours', $this->options['hours_to_reset'], 'limit-attempts' ); ?></label>
							<label <?php if ( 0 == $this->options['minutes_to_reset'] ) echo 'class="lmtttmpts-zero-value"'; ?> > <span class="lmtttmpts-unit-measure" ><?php echo $this->options['minutes_to_reset']; ?></span> <?php echo _n( 'minute', 'minutes', $this->options['minutes_to_reset'], 'limit-attempts' ); ?></label>
							<label id="lmtttmpts-time-to-reset-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'limit-attempts' ); ?></label>
						</fieldset>
						<fieldset id="lmtttmpts-time-to-reset" class="lmtttmpts-hidden-input">
							<label><input id="lmtttmpts-days-to-reset-display" type="number" max="999" min="0" step="1" maxlength="3" value="<?php echo $this->options['days_to_reset'] ; ?>" name="lmtttmpts_days_to_reset" /> <?php echo _n( 'day', 'days', $this->options['days_to_reset'], 'limit-attempts' ); ?></label>
							<label><input id="lmtttmpts-hours-to-reset-display" type="number" max="23" min="0" step="1" maxlength="2" value="<?php echo $this->options['hours_to_reset'] ; ?>" name="lmtttmpts_hours_to_reset" /> <?php echo _n( 'hour', 'hours', $this->options['hours_to_reset'], 'limit-attempts' ); ?></label>
							<label><input id="lmtttmpts-minutes-to-reset-display" type="number" max="59" min="0" step="1" maxlength="2" value="<?php echo $this->options['minutes_to_reset'] ; ?>" name="lmtttmpts_minutes_to_reset" /> <?php echo _n( 'minute', 'minutes', $this->options['minutes_to_reset'], 'limit-attempts' ); ?></label>
						</fieldset>
						<div class="bws_info">
							<?php _e( 'Time after which the failed attempts will be reset.', 'limit-attempts' ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Reset Blocking After', 'limit-attempts' ); ?></th>
					<td>
						<fieldset id="lmtttmpts-time-to-reset-block-display" class="lmtttmpts_hidden lmtttmpts-display">
							<label <?php if ( 0 == $this->options['days_to_reset_block'] ) echo 'class="lmtttmpts-zero-value"'; ?> ><span class="lmtttmpts-unit-measure" ><?php echo $this->options['days_to_reset_block']; ?></span> <?php echo _n( 'day', 'days', $this->options['days_to_reset_block'], 'limit-attempts' ); ?></label>
							<label <?php if ( 0 == $this->options['hours_to_reset_block'] ) echo 'class="lmtttmpts-zero-value"'; ?> ><span class="lmtttmpts-unit-measure" ><?php echo $this->options['hours_to_reset_block']; ?></span> <?php echo _n( 'hour', 'hours', $this->options['hours_to_reset_block'], 'limit-attempts' ); ?></label>
							<label <?php if ( 0 == $this->options['minutes_to_reset_block'] ) echo 'class="lmtttmpts-zero-value"'; ?> ><span class="lmtttmpts-unit-measure" ><?php echo $this->options['minutes_to_reset_block']; ?></span> <?php echo _n( 'minute', 'minutes', $this->options['minutes_to_reset_block'], 'limit-attempts' ); ?></label>
							<label id="lmtttmpts-time-to-reset-block-edit" class="lmtttmpts-edit"><?php _e( 'Edit', 'limit-attempts' ); ?></label>
						</fieldset>
						<fieldset id="lmtttmpts-time-to-reset-block" class="lmtttmpts-hidden-input">
							<label><input id="lmtttmpts-days-to-reset-block-display" type="number" max="999" min="0" step="1" maxlength="3" value="<?php echo $this->options['days_to_reset_block'] ; ?>" name="lmtttmpts_days_to_reset_block" /> <?php echo _n( 'day', 'days', $this->options['days_to_reset_block'], 'limit-attempts' ); ?></label>
							<label><input id="lmtttmpts-hours-to-reset-block-display" type="number" max="23" min="0" step="1" maxlength="2" value="<?php echo $this->options['hours_to_reset_block'] ; ?>" name="lmtttmpts_hours_to_reset_block" /> <?php echo _n( 'hour', 'hours', $this->options['hours_to_reset_block'], 'limit-attempts' ); ?></label>
							<label><input id="lmtttmpts-minutes-to-reset-block-display" type="number" max="59" min="0" step="1" maxlength="2" value="<?php echo $this->options['minutes_to_reset_block'] ; ?>" name="lmtttmpts_minutes_to_reset_block" /> <?php echo _n( 'minute', 'minutes', $this->options['minutes_to_reset_block'], 'limit-attempts' ); ?></label>
						</fieldset>
						<div class="bws_info">
							<?php _e( 'Time after which the blocking will be reset.', "limit-attempts" ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Blacklist IP Address After', 'limit-attempts' ); ?></th>
					<td>
						<input type="number" min="1" max="99" step="1" maxlength="2" value="<?php echo $this->options['allowed_locks']; ?>" name="lmtttmpts_allowed_locks" /> <?php echo _n( 'blocking', 'blockings', $this->options['allowed_locks'], 'limit-attempts' ); ?>
						<div class="bws_info"><?php _e( 'Number of blocking after which the IP address will be blacklisted.', 'limit-attempts' ); ?></div>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th><?php _e( 'Non-Existing Username Login Attempts Action', 'limit-attempts' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input disabled="disabled" checked="checked" type="radio" name="lmtttmpts_action_with_not_existed_user" value="default" />
											<?php _e( 'Default', 'limit-attempts' ); ?>
										</label>
										<br />
										<label>
											<input disabled="disabled" type="radio" name="lmtttmpts_action_with_not_existed_user" value="block" />
											<?php _e( 'Block', 'limit-attempts' ); ?>
										</label>
										<br />
										<label>
											<input disabled="disabled" type="radio" name="lmtttmpts_action_with_not_existed_user" value="blacklist" class="bws_option_affect" data-affect-show=".lmtttmpts_not_existed_user_message_blacklisted" data-affect-hide=".lmtttmpts_not_existed_user_message_block" />
											<?php _e( 'Blacklist', 'limit-attempts' ); ?>
										</label>
									</fieldset>
									<span class="bws_info"><?php _e( 'Detect login attempts for non-existing username and apply actions (default will block and/or blacklist IP addresses based on the settings above).', 'limit-attempts' ); ?></span>
								</td>
							</tr>
                            <tr>
                                <th><?php _e( 'Lists Priority', 'limit-attempts' ); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input disabled="disabled" type="radio" name="lmtttmpts_lists_priority" value="blacklist" checked="checked"/>
                                            <?php _e( 'Blacklist', 'limit-attempts' ); ?>
                                        </label>
                                        <br />
                                        <label>
                                            <input disabled="disabled" type="radio" name="lmtttmpts_lists_priority" value="whitelist"  class="bws_option_affect" data-affect-show=".lmtttmpts_not_existed_user_message_blacklisted" data-affect-hide=".lmtttmpts_not_existed_user_message_block" />
                                            <?php _e( 'Whitelist', 'limit-attempts' ); ?>
                                        </label>
                                    </fieldset>
                                    <span class="bws_info"><?php _e( 'Choose the list which will be used if the user is in both lists (black and white).', 'limit-attempts' ); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Failed Login and Password', 'limit-attempts' ); ?></th>
                                <td>
                                    <input type="checkbox" name="lmtttmpts_enbl_login_pass" value="0"<?php checked( 0 ); ?> /> <span class="bws_info"><?php _e( 'Enable to save and display login and password that was used in the failed attempt.', 'limit-attempts' ); ?></span>
                                </td>
                            </tr>
                        </table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table lmtttmpts_settings_form">
				<tr>
					<th><?php _e( 'Hide Forms', 'limit-attempts' ); ?></th>
					<td>
						<input type="checkbox" name="lmtttmpts_hide_login_form" value="1"<?php checked( 1, $this->options['hide_login_form'] ); ?> /> <span class="bws_info"><?php _e( 'Enable to hide login, registration, reset password forms from blocked or blacklisted IP addresses.', 'limit-attempts' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php _e( "Htaccess Plugin", 'limit-attempts' ); ?> </th>
					<td>
						<?php if ( array_key_exists( 'htaccess/htaccess.php', $this->all_plugins ) || array_key_exists( 'htaccess-pro/htaccess-pro.php', $this->all_plugins ) ) {
							$htaccess_free_active = ( 0 < count( preg_grep( '/htaccess\/htaccess.php/', $this->active_plugins ) ) ) ? true : false;
							$htaccess_pro_active = ( 0 < count( preg_grep( '/htaccess-pro\/htaccess-pro.php/', $this->active_plugins ) ) ) ? true : false;
							if ( $htaccess_free_active || $htaccess_pro_active ) {
								if ( ( $htaccess_pro_active && ! $htaccess_free_active ) || ( $htaccess_free_active && isset( $this->all_plugins['htaccess/htaccess.php']['Version'] ) && $this->all_plugins['htaccess/htaccess.php']['Version'] >= '1.6.2' ) ) {
									$htaccess_settings_link = $htaccess_pro_active ? 'admin.php?page=htaccess-pro.php' : 'admin.php?page=htaccess.php';
									$attr = $this->change_permission_attr;
									if ( 1 == $this->options["block_by_htaccess"] )
										$attr .= ' checked="checked"';
									$status_message = ' <a href="' . network_admin_url( $htaccess_settings_link ) . '">' . sprintf( __( 'Go to %s Settings', 'limit-attempts' ), 'Htaccess' ) . '</a>';
								} else {
									$attr = ' disabled="disabled"';
									if ( 1 == $this->options["block_by_htaccess"] )
										$attr .= ' checked="checked"';
									$status_message = ' <a href="' . self_admin_url( '/plugins.php' ) . '">' . sprintf( __( 'Update %s at least to %s', 'limit-attempts' ), 'Htaccess', 'v.1.6.2' ) . '</a>';
								}
							} else {
								$attr = ' disabled="disabled"';
								if ( 1 == $this->options["block_by_htaccess"] )
									$attr .= ' checked="checked"';
								$status_message = ' <a href="' . self_admin_url( '/plugins.php' ) . '">' . __( 'Activate', 'limit-attempts' ) . '</a>';
							}
						} else {
							$attr = ' disabled="disabled"';
							$status_message = ' <a href="https://bestwebsoft.com/products/wordpress/plugins/htaccess/?k=d349566ffcac58d885e8dc9ff34c6174">' . __( 'Install Now', 'limit-attempts' ) . '</a>';
						} ?>
						<input<?php echo $attr; ?> type="checkbox" name="lmtttmpts_block_by_htaccess" value="1" />
						<span class="bws_info">
							<?php _e( 'Enable to reduce database workload.', 'limit-attempts' ); ?>
							<?php echo $status_message; ?>
						</span>
						<?php echo bws_add_help_box( __( 'When you turn on this option, all IPs from the blocked list and from the blacklist will be added to the direction "deny from" of file .htaccess. IP addresses which will be added to the blocked list or to the blacklist after that, also will be added to the direction "deny from" of the file .htaccess automatically.', "limit-attempts" ) ); ?>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Captcha Plugin', 'limit-attempts' ); ?></th>
					<td>
						<fieldset>
							<?php if (
								array_key_exists( 'captcha-bws/captcha-bws.php', $this->all_plugins ) ||
								array_key_exists( 'captcha-plus/captcha-plus.php', $this->all_plugins ) ||
								array_key_exists( 'captcha-pro/captcha_pro.php', $this->all_plugins )
							) {
								if (
									0 < count( preg_grep( '/captcha-bws\/captcha-bws.php/', $this->active_plugins ) ) ||
									0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $this->active_plugins ) ) ||
									0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $this->active_plugins ) )
								) {
									if ( 0 < count( preg_grep( '/captcha-pro\/captcha_pro.php/', $this->active_plugins ) ) ) {
										if ( isset( $this->all_plugins['captcha-pro/captcha_pro.php']['Version'] ) && $this->all_plugins['captcha-pro/captcha_pro.php']['Version'] >= '1.4.4' ) { ?>
											<!-- Checkbox for Login form captcha checking -->
											<label>
												<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options['login_form_captcha_check'] ) ) echo 'checked="checked"'; ?> />
												<span><?php _e( 'Login form', 'limit-attempts' ); ?></span>
											</label>
											<div class="bws_info">
												<?php _e( 'Incorrect captcha for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?>  <a href="<?php echo self_admin_url( 'admin.php?page=captcha_pro.php' ); ?>"><?php printf( __( 'Go to %s Settings', 'limit-attempts' ), 'Captcha Pro' ); ?></a>
											</div>
										<?php } else { ?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> />
											<span class="bws_info">
												<a href="<?php echo self_admin_url( '/plugins.php' ); ?>"><?php printf( __( 'Update %s at least to %s', 'limit-attempts' ), 'Captcha Pro', 'v1.4.4' ); ?></a>
											</span>
										<?php }
									} elseif ( 0 < count( preg_grep( '/captcha-plus\/captcha-plus.php/', $this->active_plugins ) ) ) {
										/* if Captcha Plus is active */?>
										<label>
											<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options['login_form_captcha_check'] ) ) echo 'checked="checked"'; ?> />
											<span><?php _e( 'Login form', 'limit-attempts' ); ?></span>
										</label>
										<div class="bws_info">
											<?php _e( 'Incorrect captcha for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="admin.php?page=captcha-plus.php"><?php printf( __( 'Go to %s Settings', 'limit-attempts' ), 'Captcha Plus' ); ?></a>
										</div>
									<?php } else {
										if ( isset( $this->all_plugins['captcha-bws/captcha-bws.php']['Version'] ) && $this->all_plugins['captcha-bws/captcha-bws.php']['Version'] >= '5.0.0' ) { ?>
											<label>
												<input type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options['login_form_captcha_check'] ) ) echo 'checked="checked"'; ?> />
												<span><?php _e( 'Login form', 'limit-attempts' ); ?></span>
											</label>
											<div class="bws_info">
												<?php _e( 'Incorrect captcha for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="admin.php?page=captcha.php"><?php printf( __( 'Go to %s Settings', 'limit-attempts' ), 'Captcha' ); ?></a>
											</div>
										<?php } else { ?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> />
											<span class="bws_info">
												<?php _e( 'Incorrect captcha for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="<?php echo self_admin_url( '/plugins.php' ); ?>"><?php printf( __( 'Update %s at least to %s', 'limit-attempts' ), 'Captcha', 'v4.0.2' ); ?></a>
											</span>
										<?php }
									}
								} else { /* if no plugin is active */ ?>
									<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> />
									<span class="bws_info">
										<?php _e( 'Incorrect captcha for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="<?php echo self_admin_url( '/plugins.php' ); ?>"><?php _e( 'Activate', 'limit-attempts' ); ?></a>
									</span>
								<?php }
							} else { ?>
								<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" />
								<span class="bws_info">
									<?php _e( 'Incorrect captcha for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=6edfbbf264c8ee2d45ecb91d0994c89e"><?php _e( 'Install Now', 'limit-attempts' ); ?></a>
								</span>
							<?php }
							if ( ! $this->hide_pro_tabs ) { ?>
								<div class="bws_pro_version_bloc">
									<div class="bws_pro_version_table_bloc">
										<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
										<div class="bws_table_bg"></div>
										<div class="bws_pro_version">
											<fieldset>
													<?php $captcha_pro_forms = array(
													__( 'Registration form', 'limit-attempts' ),
													__( 'Reset Password form', 'limit-attempts' ),
													__( 'Comments form', 'limit-attempts' ),
													'Contact Form by BestWebSoft',
													'Subscriber by BestWebSoft',
													__( 'Buddypress registration form', 'limit-attempts' ),
													__( 'Buddypress "Create a Group" form', 'limit-attempts' ),
													'Contact Form 7',
													__( 'WooCommerce Login form', 'limit-attempts' ),
													__( 'WooCommerce Register form', 'limit-attempts' ),
													__( 'WooCommerce Lost Password form', 'limit-attempts' ),
													__( 'WooCommerce Checkout Billing form', 'limit-attempts' )
												);
												foreach ( $captcha_pro_forms as $form_name ) {
													printf(
														'<label><input disabled="disabled" type="checkbox" /><span> %s</span></label><br />',
														$form_name
													);
												} ?>
											</fieldset>
											<p style="position: relative;z-index: 2;"><strong>* <?php printf( __( 'You also need %s to use these options.', 'limit-attempts' ), '<a href="https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=da48686c77c832045c113eb82447d40d&pn=140&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Captcha Pro</a>' ); ?></strong></p>
										</div>
									</div>
									<?php $this->bws_pro_block_links(); ?>
								</div>
							<?php } ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Google Captcha Plugin', 'limit-attempts' ); ?></th>
					<td>
						<fieldset>
							<?php if (
								array_key_exists( 'google-captcha/google-captcha.php', $this->all_plugins ) ||
								array_key_exists( 'google-captcha-pro/google-captcha-pro.php', $this->all_plugins )
							) {
								if (
									/*0 < count( preg_grep( '/captcha-bws\/captcha-bws.php/', $this->active_plugins ) ) ||*/
									0 < count( preg_grep( '/google-captcha\/google-captcha.php/', $this->active_plugins ) ) ||
									0 < count( preg_grep( '/google-captcha-pro\/google-captcha-pro.php/', $this->active_plugins ) )
								) {
									if ( 0 < count( preg_grep( '/google-captcha-pro\/google-captcha-pro.php/', $this->active_plugins ) ) ) {
										if (
											isset( $this->all_plugins['google-captcha-pro/google-captcha-pro.php']['Version'] ) &&
											version_compare( $this->all_plugins['google-captcha-pro/google-captcha-pro.php']['Version'], '1.32', '>=' )
										) { ?>
											<!-- Checkbox for Login form captcha checking -->
											<label>
												<input type="checkbox" name="lmtttmpts_login_form_recaptcha_check" value="1" <?php if ( ! empty( $this->options['login_form_recaptcha_check'] ) ) echo 'checked="checked"'; ?> />
												<span><?php _e( 'Login form', 'limit-attempts' ); ?></span>
											</label>
											<div class="bws_info">
												<?php _e( 'Failed reCAPTCHA validation for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?>  <a href="<?php echo self_admin_url( 'admin.php?page=google-captcha-pro.php' ); ?>"><?php printf( __( 'Go to %s Settings', 'limit-attempts' ), 'Google Captcha Pro' ); ?></a>
											</div>
										<?php } else { ?>
											<input disabled="disabled" type="checkbox" />
											<span class="bws_info">
												<a href="<?php echo self_admin_url( '/plugins.php' ); ?>"><?php printf( __( 'Update %s at least to %s', 'limit-attempts' ), 'Google Captcha Pro', 'v1.32' ); ?></a>
											</span>
										<?php }
									} else {
										if (
											isset( $this->all_plugins['google-captcha/google-captcha.php']['Version'] ) &&
											version_compare( $this->all_plugins['google-captcha/google-captcha.php']['Version'], '1.32', '>=' )
										) { ?>
											<label>
												<input type="checkbox" name="lmtttmpts_login_form_recaptcha_check" value="1" <?php if ( isset( $this->options['login_form_recaptcha_check'] ) ) echo 'checked="checked"'; ?> />
												<span><?php _e( 'Login form', 'limit-attempts' ); ?></span>
											</label>
											<div class="bws_info">
												<?php _e( 'Failed reCAPTCHA validation for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="admin.php?page=google-captcha.php"><?php printf( __( 'Go to %s Settings', 'limit-attempts' ), 'Google Captcha' ); ?></a>
											</div>
										<?php } else { ?>
											<input disabled="disabled" type="checkbox" name="lmtttmpts_login_form_captcha_check" value="1" <?php if ( isset( $this->options["login_form_captcha_check"] ) ) echo 'checked="checked"'; ?> />
											<span class="bws_info">
												<?php _e( 'Failed reCAPTCHA validation for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="<?php echo self_admin_url( '/plugins.php' ); ?>"><?php printf( __( 'Update %s at least to %s', 'limit-attempts' ), 'Google Captcha', 'v1.32' ); ?></a>
											</span>
										<?php }
									}
								} else { /* if no plugin is active */ ?>
									<input disabled="disabled" type="checkbox" />
									<span class="bws_info">
										<?php _e( 'Failed reCAPTCHA validation for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="<?php echo self_admin_url( '/plugins.php' ); ?>"><?php _e( 'Activate', 'limit-attempts' ); ?></a>
									</span>
								<?php }
							} else { ?>
								<input disabled="disabled" type="checkbox" />
								<span class="bws_info">
									<?php _e( 'Failed reCAPTCHA validation for selected forms will be considered as an invalid attempt.', 'limit-attempts' ); ?> <a href="https://bestwebsoft.com/products/wordpress/plugins/google-captcha/?k=fd764017a5f3f57d9c307ef96b4b9935&pn=140&v=<?php echo $this->plugins_info['Version'] . '&wp_v=' . $wp_version; ?>" target="_blank"><?php _e( 'Install Now', 'limit-attempts' ); ?></a>
								</span>
							<?php }
							if ( ! $this->hide_pro_tabs ) { ?>
								<div class="bws_pro_version_bloc">
									<div class="bws_pro_version_table_bloc">
										<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
										<div class="bws_table_bg"></div>
										<div class="bws_pro_version">
											<fieldset>
												<?php $recaptcha_pro_forms = array(
													__( 'Registration form', 'limit-attempts' ),
													__( 'Reset password form', 'limit-attempts' ),
													__( 'Comments form', 'limit-attempts' ),
													'Contact Form',
													'Contact Form 7',
													'Fast Secure Contact Form',
													__( 'Jetpack Contact Form', 'limit-attempts' ),
													'Subscriber',
													__( 'bbPress New Topic form', 'limit-attempts' ),
													__( 'bbPress Reply form', 'limit-attempts' ),
													__( 'BuddyPress Registration form', 'limit-attempts' ),
													__( 'BuddyPress Comments form', 'limit-attempts' ),
													__( 'BuddyPress Add New Group form', 'limit-attempts' ),
													__( 'WooCommerce Login form', 'limit-attempts' ),
													__( 'WooCommerce Registration form', 'limit-attempts' ),
													__( 'WooCommerce Reset password form', 'limit-attempts' ),
													__( 'WooCommerce Checkout form', 'limit-attempts' ),
													__( 'wpForo Login form', 'limit-attempts' ),
													__( 'wpForo Registration form', 'limit-attempts' ),
													__( 'wpForo New Topic form', 'limit-attempts' ),
													__( 'wpForo Reply form', 'limit-attempts'),
												);
												foreach ( $recaptcha_pro_forms as $form_name ) {
													printf(
														'<label><input disabled="disabled" type="checkbox" /><span> %s</span></label><br />',
														$form_name
													);
												} ?>
											</fieldset>
											<p style="position: relative;z-index: 2;"><strong>* <?php printf( __( 'You also need %s to use these options.', 'limit-attempts' ), '<a href="https://bestwebsoft.com/products/wordpress/plugins/google-captcha/?k=fd764017a5f3f57d9c307ef96b4b9935&pn=140&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Google Captcha Pro</a>' ); ?></strong></p>
										</div>
									</div>
									<?php $this->bws_pro_block_links(); ?>
								</div>
							<?php } ?>
						</fieldset>
					</td>
				</tr>
			</table>
		<?php }

		/**
		 *
		 */
		public function tab_errors() { ?>
			<h3 class="bws_tab_label"><?php _e( 'Error Messages Settings', 'limit-attempts' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th scope="row"><?php _e( 'Invalid Attempt', 'limit-attempts' ); ?></th>
					<td>
						<textarea rows="5" name="lmtttmpts_failed_message"><?php echo $this->options['failed_message']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%ATTEMPTS%' - <?php _e( 'quantity of attempts left', 'limit-attempts' ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Blocked', 'limit-attempts' ); ?></th>
					<td>
						<textarea rows="5" name="lmtttmpts_blocked_message"><?php echo $this->options['blocked_message']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%DATE%' - <?php _e( 'blocking time', 'limit-attempts' ); ?><br/>
							'%MAIL%' - <?php _e( 'administrator&rsquo;s email address', 'limit-attempts' ); ?>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Blacklisted', 'limit-attempts' ); ?></th>
					<td>
						<textarea rows="5" name="lmtttmpts_blacklisted_message"><?php echo $this->options['blacklisted_message']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%MAIL%' - <?php _e( 'administrator&rsquo;s email address', 'limit-attempts' ); ?>
						</div>
					</td>
				</tr>
            </table>
            <?php if ( ! $this->hide_pro_tabs ) { ?>
                <div class="bws_pro_version_bloc">
                    <div class="bws_pro_version_table_bloc">
                        <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
                        <div class="bws_table_bg"></div>
                        <table class="form-table bws_pro_version">
                            <tr>
                                <th scope="row"><?php _e( 'Non-Existing Username', 'limit-attempts' ); ?></th>
                                <td>
                                    <div class="lmtttmpts_not_existed_user_message_block">
                                        <textarea cols="10000"  rows="5" name="lmtttmpts_user_not_exists_blocked_message" disabled="disabled" ><?php _e( "You've been blocked for %DATE% because such username does not exist.", 'limit-attempts' ); ?></textarea>
                                        <div class="bws_info">
                                            <?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
                                            '%DATE%' - <?php _e( 'blocking time', 'limit-attempts' ); ?><br/>
                                            '%MAIL%' - <?php _e( 'administrator&rsquo;s email address', 'limit-attempts' ); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php $this->bws_pro_block_links(); ?>
                </div>
            <?php } ?>
            <table class="form-table">
				<tr>
					<th scope="row"><?php _e( 'Restore Default Error Messages', 'limit-attempts' ); ?></th>
					<td>
						<button class="button-secondary" name="lmtttmpts_return_default" value="error"><?php _e( 'Restore Error Messages', 'limit-attempts' ) ?></button>
					</td>
				</tr>
			</table>
		<?php }

		/**
		 *
		 */
		public function tab_notifications() {
			/* get admins for emails */
			$userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&role=administrator' ); ?>
			<h3 class="bws_tab_label"><?php _e( 'Email Notifications Settings', 'limit-attempts' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table lmtttmpts_settings_form">
				<tr>
					<th><?php _e( 'Email Notifications', 'limit-attempts' ); ?></th>
					<td>
						<input type="checkbox" name="lmtttmpts_notify_email" value="1" <?php checked( $this->options['notify_email'], 1 ); ?> class="bws_option_affect" data-affect-show=".lmtttmpts_email_notifications" /> <span class="bws_info"><?php _e( 'Enable to receive email notifications.', 'limit-attempts' ); ?></span>
					</td>
				</tr>
				<tr class="lmtttmpts_email_notifications">
					<th><?php _e( 'Send Email Notifications to', 'limit-attempts' ) ?></th>
					<td>
						<label>
							<input type="radio" id="lmtttmpts_user_mailto" name="lmtttmpts_mailto" value="admin" <?php checked( $this->options['mailto'], 'admin' ); ?> />
							<select name="lmtttmpts_user_email_address">
								<option disabled><?php _e( "Choose a username", 'limit-attempts' ); ?></option>
								<?php foreach ( $userslogin as $key => $value ) {
									if ( $value->data->user_email != '' ) { ?>
										<option value="<?php echo $value->data->user_email; ?>" <?php selected( $value->data->user_email, $this->options['email_address'] ); ?>><?php echo $value->data->user_login; ?></option>
									<?php }
								} ?>
							</select>
						</label>
						<br/>
						<label>
							<input type="radio" id="lmtttmpts_custom_mailto" name="lmtttmpts_mailto" value="custom" <?php checked( $this->options['mailto'], 'custom' ); ?> />
							<input type="email" name="lmtttmpts_email_address" maxlength="100" value="<?php if ( $this->options['mailto'] == 'custom' ) echo $this->options['email_address']; ?>" />
						</label>
						<div class="bws_info"><?php _e( 'Select an existing administrator or a custom email.', 'limit-attempts' ); ?></div>
					</td>
				</tr>
				<tr class="lmtttmpts_email_notifications">
					<th><?php _e( 'Block Notifications', 'limit-attempts' ); ?></th>
					<td>
						<p><?php _e( 'Subject', 'limit-attempts' ); ?></p>
						<textarea rows="5" name="lmtttmpts_email_subject"><?php echo $this->options['email_subject']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%IP%' - <?php _e( 'blocked IP address', 'limit-attempts' ); ?><br/>
							'%SITE_NAME%' - <?php _e( 'website name', 'limit-attempts' ); ?>
						</div>
						<p style="margin-top: 6px;"><?php _e( 'Message', 'limit-attempts' ); ?></p>
						<textarea rows="5" name="lmtttmpts_email_blocked"><?php echo $this->options['email_blocked']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%IP%' - <?php _e( 'blocked IP address', 'limit-attempts' ); ?><br/>
							'%PLUGIN_LINK%' - <?php _e( 'Limit Attempts plugin link', 'limit-attempts' ); ?><br/>
							'%WHEN%' - <?php _e( 'date and time when IP address was blocked', 'limit-attempts' ); ?><br/>
							'%SITE_NAME%' - <?php _e( 'website name', 'limit-attempts' ); ?><br/>
							'%SITE_URL%' - <?php _e( 'website URL', 'limit-attempts' ); ?>
						</div>
					</td>
				</tr>
				<tr class="lmtttmpts_email_notifications">
					<th><?php _e( 'Blacklist Notifications', 'limit-attempts' ); ?></th>
					<td>
						<p><?php _e( 'Subject', 'limit-attempts' ); ?></p>
						<textarea rows="5" name="lmtttmpts_email_subject_blacklisted"><?php echo $this->options['email_subject_blacklisted']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%IP%' - <?php _e( 'blacklisted IP address', 'limit-attempts' ); ?><br/>
							'%SITE_NAME%' - <?php _e( 'website name', 'limit-attempts' ); ?>
						</div>
						<p style="margin-top: 6px;"><?php _e( 'Message', 'limit-attempts' ); ?></p>
						<textarea rows="5" name="lmtttmpts_email_blacklisted"><?php echo $this->options['email_blacklisted']; ?></textarea>
						<div class="bws_info">
							<?php _e( 'Allowed Variables:', 'limit-attempts' ); ?><br/>
							'%IP%' - <?php _e( 'blacklisted IP address', 'limit-attempts' ); ?><br/>
							'%PLUGIN_LINK%' - <?php _e( 'Limit Attempts plugin link', 'limit-attempts' ); ?><br/>
							'%WHEN%' - <?php _e( 'date and time when IP address was blocked', 'limit-attempts' ); ?><br/>
							'%SITE_NAME%' - <?php _e( 'website name', 'limit-attempts' ); ?><br/>
							'%SITE_URL%' - <?php _e( 'website URL', 'limit-attempts' ); ?>
						</div>
					</td>
				</tr>
				<tr class="lmtttmpts_email_notifications">
					<th scope="row"><?php _e( 'Restore Default Email Notifications', 'limit-attempts' ); ?></th>
					<td>
						<button class="button-secondary" name="lmtttmpts_return_default" value="email"><?php _e( 'Restore Email Notifications', 'limit-attempts' ) ?></button>
					</td>
				</tr>
			</table>
		<?php }

		/**
		 * Display custom options on the 'misc' tab
		 * @access public
		 */
		public function additional_misc_options_affected() {
			global $wpdb, $lmtttmpts_country_table;
			/* get DB size or update if it's empty 1 hour old or more */
			if ( empty( $this->options['db_size'] ) || ( $this->options['db_size']['last_updated_timestamp'] + 3600 ) < time() ) {
				/* get the size of 'log' and 'statistics' tables in DB */
				$tables = $wpdb->get_results(
					"SHOW TABLE STATUS WHERE `Name` in ( '{$wpdb->prefix}lmtttmpts_failed_attempts_statistics', '{$wpdb->prefix}lmtttmpts_failed_forms_by_ip' )",
					ARRAY_A );
				if ( $tables && 3 == count( $tables ) ) {
					foreach ( $tables as $value ) {
						$tables[ $value['Name'] ] = $value['Data_length'];
					}
					$db_size = array(
						'stats_size' 				=> (string) round( ( $tables[ $wpdb->prefix . 'lmtttmpts_failed_attempts_statistics' ] + $tables[ $wpdb->prefix . 'lmtttmpts_failed_forms_by_ip' ] ) / 1000000, 3 ),
						'last_updated_timestamp' 	=> time()
					);
				} else {
					$db_size = '';
				}
				unset( $tables );
				/* update options with new data */
				$this->options['db_size'] = $db_size;
				update_option( 'lmtttmpts_options', $this->options );
			} else {
				$db_size = $this->options['db_size'];
			} ?>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th><?php _e( 'Remove Log Entries Older Than', 'limit-attempts' ); ?></th>
								<td>
									<fieldset>
										<label><input disabled="disabled" type="number" min="0" max="999" step="1" maxlength="3" value="30" name="lmtttmpts_days_to_clear_log" /> <?php _e( 'days', 'limit-attempts' ) ?></label><br/>
										<span class="bws_info"><?php _e( 'Set "0" if you do not want to clear the log.', 'limit-attempts' ); ?></span>
									</fieldset>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table lmtttmpts_settings_form">
			<tr>
				<th><?php _e( 'Remove Stats Entries Older Than', 'limit-attempts' ) ?></th>
				<td>
					<fieldset>
						<label><input type="number" min="0" max="999" step="1" maxlength="3" value="<?php echo $this->options['days_to_clear_statistics']; ?>" name="lmtttmpts_days_to_clear_statistics" /> <?php _e( 'days', 'limit-attempts' ) ?></label>
						<br/>
						<span class="bws_info"><?php _e( 'Set "0" if you do not want to clear the statistics.', 'limit-attempts' ) ?></span>
						<?php if ( ! empty( $db_size ) && isset( $db_size['stats_size'] ) && is_numeric( $db_size['stats_size'] ) ) { ?>
							<p class="bws_info_small"><?php printf( __( 'Current size of DB table is %s', 'limit-attempts' ), '&asymp; ' . $db_size['stats_size'] . __( 'Mb', 'limit-attempts' ) ); ?></p>
						<?php } ?>
					</fieldset>
				</td>
			</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th><?php _e( 'Update GeoIP Every', 'limit-attempts' ); ?></th>
								<td>
									<fieldset>
										<input disabled="disabled" type="number" min="0" max="10" step="1" name="lmtttmpts_geo" value="0" />&nbsp;<?php _e( 'months', 'limit-attempts' ); ?>
										<div style="margin-top: 10px;"><input disabled="disabled" type="submit" class="button" value="<?php _e( 'Update Now', 'limit-attempts' ); ?>" /></div>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Update White- & Blacklist After', 'limit-attempts' ); ?></th>
								<td>
									<fieldset>
										<input disabled="disabled" type="number" min="0" max="10" step="1" name="lmtttmpts_after_updates" value="0" />&nbsp;<?php _e( 'updates of GeoIP', 'limit-attempts' ); ?>
										<div style="margin-top: 10px;"><input disabled="disabled" type="submit" class="button" value="<?php _e( 'Update Now', 'limit-attempts' ); ?>" /></div>
									</fieldset>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table lmtttmpts_settings_form">
		<?php }
	}
}