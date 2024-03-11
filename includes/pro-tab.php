<?php
/**
 *
 * @package Limit Attempts
 * @since 1.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'lmtttmpts_display_advertising' ) ) {
	/**
	 * Display advertising
	 *
	 * @param string $what Flag for display.
	 */
	function lmtttmpts_display_advertising( $what ) {
		global $lmtttmpts_plugin_info, $wp_version, $lmtttmpts_options;
		if ( isset( $_POST['bws_hide_premium_options'] ) ) {
			check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' );
			$result = bws_hide_premium_options( $lmtttmpts_options );
			update_option( 'lmtttmpts_options', $result['options'] ); ?>
			<div class="updated fade inline"><p><strong><?php echo esc_html( $result['message'] ); ?></strong></p></div>
			<?php
		} elseif ( ! bws_hide_premium_options_check( $lmtttmpts_options ) ) {
			?>
			<form method="post" action=""
			<?php
			if ( 'allowlist' === $what || 'denylist' === $what || 'allowlist-email' === $what || 'denylist-email' === $what ) {
				echo ' style="max-width: 610px;"';
			}
			?>
			>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'limit-attempts' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<div style="padding: 5px;">
							<?php if ( 'allowlist' === $what || 'denylist' === $what ) { ?>
								<div class="lmtttmpts_edit_list_form">
									<table>
										<tr>
											<td>
												<label><?php esc_html_e( 'Enter IP', 'limit-attempts' ); ?></label>
												<?php
												$content = __( 'Allowed formats', 'limit-attempts' ) . ':<br /><code>192.168.0.1, 192.168.0.,<br/>192.168., 192.,<br/>192.168.0.1/8,<br/>123.126.12.243-185.239.34.54</code>
												<p>' . __( 'Allowed range', 'limit-attempts' ) . ':<br />
													<code>0.0.0.0 - 255.255.255.255</code>
												</p>
												<p>' . __( 'Allowed separators', 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'ordinary space, tab, new line or carriage return', 'limit-attempts' ) . '</p>';
												echo wp_kses_post( bws_add_help_box( $content ) );
												?>
												<br>
												<input type="text" disabled="disabled" />
											</td>
											<td>
												<label><?php esc_html_e( 'Reason for IP', 'limit-attempts' ); ?></label>
												<?php echo wp_kses_post( bws_add_help_box( __( 'Allowed separators', 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'tab, new line or carriage return', 'limit-attempts' ) ) ); ?>
												<br>
												<input type="text" disabled="disabled" />
											</td>
										</tr>
										<tr>
											<td valign="top">
												<label><?php esc_html_e( 'Select country', 'limit-attempts' ); ?></label><br>
												<select disabled="disabled" style="width: 100%;"></select>
											</td>
											<td>
												<label><?php esc_html_e( 'Reason for country', 'limit-attempts' ); ?></label><br>
												<input type="text" disabled="disabled" />
											</td>
										</tr>
									</table>
								</div>
							<?php } elseif ( 'allowlist-email' === $what ) { ?>
								<div class="lmtttmpts_edit_list_form">
									<table>
										<tr>
											<td>
												<label><?php esc_html_e( 'Enter Email', 'limit-attempts' ); ?></label>
												<?php
												$content = __( 'Forbidden symbols', 'limit-attempts' ) . ':<br /><code>! # $ % & \' * + /=  ? ^ ` { | } ~</code>
												<p>' . __( 'Allowed separators', 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'ordinary space, tab, new line or carriage return', 'limit-attempts' ) . '</p>';
												echo wp_kses_post( bws_add_help_box( $content ) );
												?>
												<br>
												<input type="text" disabled="disabled" />
											</td>
											<td>
												<label><?php esc_html_e( 'Reason for Email', 'limit-attempts' ); ?></label>
												<?php echo wp_kses_post( bws_add_help_box( __( 'Allowed separators', 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'tab, new line or carriage return', 'limit-attempts' ) ) ); ?>
												<br>
												<input type="text" disabled="disabled" />
											</td>
										</tr>
										<tr>
											<td>
												<label style="display:inline-block; padding-bottom: 10px;" disabled="disabled" for="lmtttmpts_my_email"><input type="checkbox" id="lmtttmpts_my_email" name="lmtttmpts_my_email" /><?php esc_html_e( 'My Email', 'limit-attempts' ); ?></label>
											</td>
										</tr>
										<tr>
											<td style="position: relative;">
												<input class="button-primary" type="submit" disabled="disabled" value="<?php esc_html_e( 'Add New', 'limit-attempts' ); ?>" />
											</td>
										</tr>
									</table>
								</div>
								<?php
							} elseif ( 'allowlist-email-table' === $what ) {
								?>
								<p class="search-box">
									<input disabled="disabled" type="search" name="s" />
									<input disabled="disabled" type="submit" value="<?php esc_html_e( 'Search Email', 'limit-attempts' ); ?>" class="button" />
								</p>
								<div class="tablenav top">
									<div class="alignleft actions bulkactions">
										<select disabled="disabled">
											<option><?php esc_html_e( 'Bulk Actions', 'limit-attempts' ); ?></option>
										</select>
										<input disabled="disabled" type="submit" value="Apply" class="button action" />
									</div>
									<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div>
									<br class="clear">
								</div>
								<table class="wp-list-table widefat fixed">
									<thead>
									<tr>
										<th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox" /></th>
										<th class="manage-column column-primary" scope="col"><a href="#"><span><?php esc_html_e( 'Email', 'limit-attempts' ); ?></span></a></th>
										<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Reason', 'limit-attempts' ); ?></span></a></th>
										<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Date Added', 'limit-attempts' ); ?></span></a></th>
									</tr>
									</thead>
									<tfoot>
									<tr>
										<th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox" /></th>
										<th class="manage-column column-primary" scope="col"><a href="#"><span><?php esc_html_e( 'Email', 'limit-attempts' ); ?></span></a></th>
										<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Reason', 'limit-attempts' ); ?></span></a></th>
										<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Date Added', 'limit-attempts' ); ?></span></a></th>
									</tr>
									</tfoot>
									<tbody>
									<tr class="alternate">
										<th class="check-column" scope="row"><input disabled="disabled" type="checkbox"></th>
										<td class="column-primary">example@example.com</td>
										<td><?php esc_html_e( 'My Email', 'limit-attempts' ); ?></td>
										<td>November 25, 2014 11:55 am</td>
									</tr>
									</tbody>
								</table>
								<div class="tablenav bottom">
									<div class="alignleft actions bulkactions">
										<select disabled="disabled">
											<option><?php esc_html_e( 'Bulk Actions', 'limit-attempts' ); ?></option>
										</select>
										<input disabled="disabled" type="submit" value="Apply" class="button action" />
									</div>
									<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div>
									<br class="clear">
								</div>
								<?php
							} elseif ( 'summaries' === $what ) {
								?>
								<div>
									<img class="lmtttmpts_attempts" src="<?php echo esc_url( plugins_url( '../images/attempts.png', __FILE__ ) ); ?>" alt="" />
								</div>
								<?php
							} elseif ( 'log' === $what ) {
								?>
								<p class="search-box">
									<input disabled="disabled" type="search" name="s" />
									<input disabled="disabled" type="submit" value="<?php esc_html_e( 'Search IP', 'limit-attempts' ); ?>" class="button" />
								</p>
								<input disabled="disabled" type="submit" value="<?php esc_html_e( 'Clear Log', 'limit-attempts' ); ?>" class="button" />
								<div class="tablenav top">
									<div class="alignleft actions bulkactions">
										<select disabled="disabled">
											<option><?php esc_html_e( 'Delete log entry', 'limit-attempts' ); ?></option>
										</select>
										<input disabled="disabled" type="submit" value="Apply" class="button action" />
									</div>
									<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div>
									<br class="clear">
								</div>
								<table class="wp-list-table widefat fixed">
									<thead>
										<tr>
											<th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox" /></th>
											<th class="manage-column column-primary" scope="col"><a href="#"><span><?php esc_html_e( 'IP address', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Email', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Login', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Password', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Hostname', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Event', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Form', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Event time', 'limit-attempts' ); ?></a></th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox" /></th>
											<th class="manage-column column-primary" scope="col"><a href="#"><span><?php esc_html_e( 'IP address', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Email', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Login', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Password', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Hostname', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Event', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Form', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php esc_html_e( 'Event time', 'limit-attempts' ); ?></a></th>
										</tr>
									</tfoot>
									<tbody>
										<tr class="alternate">
											<th class="check-column" scope="row"><input disabled="disabled" type="checkbox"></th>
											<td class="column-primary">127.0.0.1</td>
											<td>example@gmail.com</td>
											<td>admin</td>
											<td>123456</td>
											<td>localhost</td>
											<td><?php esc_html_e( 'Failed attempt', 'limit-attempts' ); ?></td>
											<td><?php esc_html_e( 'Login form', 'limit-attempts' ); ?></td>
											<td>November 25, 2014 11:55 am</td>
										</tr>
									</tbody>
								</table>
								<div class="tablenav bottom">
									<div class="alignleft actions bulkactions">
										<select disabled="disabled">
											<option><?php esc_html_e( 'Delete log entry', 'limit-attempts' ); ?></option>
										</select>
										<input disabled="disabled" type="submit" value="Apply" class="button action" />
									</div>
									<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div>
									<br class="clear">
								</div>
								<?php
							} elseif ( 'denylist-email' === $what ) {
								?>
								<div class="lmtttmpts_edit_list_form">
									<table>
										<tr>
											<td>
												<label><?php esc_html_e( 'Enter Email', 'limit-attempts' ); ?></label>
												<?php
												$content = __( 'Forbidden symbols', 'limit-attempts' ) . ':<br /><code>! # $ % & \' * + /=  ? ^ ` { | } ~</code>
													<p>' . __( 'Allowed separators', 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'ordinary space, tab, new line or carriage return', 'limit-attempts' ) . '</p>';
												echo wp_kses_post( bws_add_help_box( $content ) );
												?>
												<br>
												<textarea rows="2" cols="32" disabled></textarea>
											</td>
											<td>
												<label><?php esc_html_e( 'Reason for Email', 'limit-attempts' ); ?></label>
												<?php echo wp_kses_post( bws_add_help_box( __( 'Allowed separators', 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'tab, new line or carriage return', 'limit-attempts' ) ) ); ?>
												<br>
												<textarea rows="2" cols="32" disabled></textarea>
											</td>
										</tr>
										<tr>
											<td style="position: relative;">
												<input class="button-primary" type="submit" disabled="disabled" value="<?php esc_html_e( 'Add New', 'limit-attempts' ); ?>" />
											</td>
										</tr>
									</table>
								</div>
						   <?php } ?>
						</div>
					</div>
					<div class="bws_pro_version_tooltip">
						<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/limit-attempts/?k=fdac994c203b41e499a2818c409ff2bc&pn=140&v=<?php echo esc_attr( $lmtttmpts_plugin_info['Version'] ); ?>&wp_v=<?php echo esc_attr( $wp_version ); ?>" target="_blank" title="Limit Attempts Pro"><?php esc_html_e( 'Upgrade to Pro', 'limit-attempts' ); ?></a>
						<div class="clear"></div>
					</div>
				</div>
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
			</form>
			<?php
		} elseif ( 'log' === $what ) {
			?>
			<p>
				<?php
				esc_html_e( 'This tab contains Pro options only.', 'limit-attempts' );
				echo ' ' . sprintf(
					esc_html__( '%1$sChange the settings%2$s to view the Pro options.', 'limit-attempts' ),
					'<a href="admin.php?page=limit-attempts.php&bws_active_tab=misc">',
					'</a>'
				);
				?>
			</p>
			<?php
		}
	}
}
