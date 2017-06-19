<?php
/**
 *
 * @package Limit Attempts
 * @since 1.1.3
 */

if ( ! function_exists( 'lmtttmpts_display_advertising' ) ) {
	function lmtttmpts_display_advertising( $what ) {
		global $lmtttmpts_plugin_info, $wp_version, $lmtttmpts_options;		
		if ( isset( $_POST['bws_hide_premium_options'] ) ) {
			check_admin_referer( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' );
			$result = bws_hide_premium_options( $lmtttmpts_options );
			update_option( 'lmtttmpts_options', $result['options'] ); ?>
			<div class="updated fade inline"><p><strong><?php echo $result['message']; ?></strong></p></div>
		<?php } elseif ( ! bws_hide_premium_options_check( $lmtttmpts_options ) ) { ?>
			<form method="post" action=""<?php if ( 'whitelist' == $what || 'blacklist' == $what ) echo ' style="max-width: 610px;"'; ?>>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'limit-attempts' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<div style="padding: 5px;">
							<?php if ( 'whitelist' == $what || 'blacklist' == $what ) { ?>
								<div class="lmtttmpts_edit_list_form">
									<ul style="float: none; margin: 0; padding: 5px 0 0 5px;" class="subsubsub">
										<li><a href="#" class="current"><?php _e( 'Add', 'limit-attempts' ); ?></a>&nbsp;|</li>
										<li><a href="#"><?php _e( 'Delete', 'limit-attempts' ); ?></a></li>
									</ul>
									<table>
										<tr>
											<td>
												<label><?php _e( 'Enter IP', 'limit-attempts' ); ?></label>
												<?php $content = __( "Allowed formats", 'limit-attempts' ) . ':<br /><code>192.168.0.1, 192.168.0.,<br/>192.168., 192.,<br/>192.168.0.1/8,<br/>123.126.12.243-185.239.34.54</code>
												<p>' . __( "Allowed range", 'limit-attempts' ) . ':<br />
													<code>0.0.0.0 - 255.255.255.255</code>
												</p>
												<p>' . __( "Allowed separators", 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'ordinary space, tab, new line or carriage return', 'limit-attempts' ) . '</p>';
												echo bws_add_help_box( $content ); ?>
												<br>
												<input type="text" disabled="disabled" />
											</td>
											<td>
												<label><?php _e( 'Reason for IP', 'limit-attempts' ); ?></label>
												<?php echo bws_add_help_box( __( "Allowed separators", 'limit-attempts' ) . ':<br />' . __( 'a comma', 'limit-attempts' ) . '&nbsp;(<code>,</code>), ' . __( 'semicolon', 'limit-attempts' ) . ' (<code>;</code>), ' . __( 'tab, new line or carriage return', 'limit-attempts' ) ); ?>
												<br>
												<input type="text" disabled="disabled" />
											</td>
										</tr>
										<tr>
											<td valign="top">
												<label><?php _e( 'Select country', 'limit-attempts' ); ?></label><br>
												<select disabled="disabled" style="width: 100%;"></select>
											</td>
											<td>
												<label><?php _e( 'Reason for country', 'limit-attempts' ); ?></label><br>
												<input type="text" disabled="disabled" />
											</td>
										</tr>
									</table>
								</div>
							<?php } elseif ( 'summaries' == $what ) { ?>
								<div>
									<img class="lmtttmpts_attempts" src="<?php echo plugins_url( '../images/attempts.png', __FILE__ ); ?>" alt="" />
								</div>							
							<?php } elseif ( 'log' == $what ) { ?>
								<p class="search-box">
									<input disabled="disabled" type="search" name="s" />
									<input disabled="disabled" type="submit" value="<?php _e( 'Search IP', 'limit-attempts' ); ?>" class="button" />
								</p>
								<input disabled="disabled" type="submit" value="<?php _e( 'Clear Log', 'limit-attempts' ); ?>" class="button" />
								<div class="tablenav top">
									<div class="alignleft actions bulkactions">
										<select disabled="disabled">
											<option><?php _e( 'Delete log entry', 'limit-attempts' ); ?></option>
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
											<th class="manage-column column-primary" scope="col"><a href="#"><span><?php _e( 'IP address', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Internet Hostname', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Event', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Form', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Event time', 'limit-attempts' ); ?></a></th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th class="manage-column check-column" scope="col"><input disabled="disabled" type="checkbox" /></th>
											<th class="manage-column column-primary" scope="col"><a href="#"><span><?php _e( 'IP address', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Internet Hostname', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Event', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Form', 'limit-attempts' ); ?></span></a></th>
											<th class="manage-column" scope="col"><a href="#"><span><?php _e( 'Event time', 'limit-attempts' ); ?></a></th>
										</tr>
									</tfoot>
									<tbody>
										<tr class="alternate">
											<th class="check-column" scope="row"><input disabled="disabled" type="checkbox"></th>
											<td class="column-primary">127.0.0.1</td>
											<td>localhost</td>
											<td><?php _e( 'Failed attempt', 'limit-attempts' ); ?></td>
											<td><?php _e( 'Login form', 'limit-attempts' ); ?></td>
											<td>November 25, 2014 11:55 am</td>
										</tr>
									</tbody>
								</table>
								<div class="tablenav bottom">
									<div class="alignleft actions bulkactions">
										<select disabled="disabled">
											<option><?php _e( 'Delete log entry', 'limit-attempts' ); ?></option>
										</select>
										<input disabled="disabled" type="submit" value="Apply" class="button action" />
									</div>
									<div class="tablenav-pages one-page"><span class="displaying-num">1 item</span></div>
									<br class="clear">
								</div>
							<?php } ?>
						</div>
					</div>
					<div class="bws_pro_version_tooltip">
						<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/limit-attempts/?k=fdac994c203b41e499a2818c409ff2bc&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Limit Attempts Pro"><?php _e( "Upgrade to Pro", 'limit-attempts' ); ?></a>
						<div class="clear"></div>
					</div>
				</div>
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'lmtttmpts_nonce_name' ); ?>
			</form>
		<?php }
	}
}