<?php 
/**
 *
 * @package Limit Attempts
 * @since 1.1.3
 */

if ( ! function_exists( 'lmtttmpts_display_log' ) ) {
	function lmtttmpts_display_log() { 
		global $lmtttmpts_plugin_info, $wp_version; ?>
		<div id="lmtttmpts_log">
			<div style="max-width: 100%" class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">
					<div class="bws_table_bg"></div>
					<div style="padding: 5px;">
						<form>
							<p class="search-box">
								<input disabled="disabled" type="search" name="s" />
								<input disabled="disabled" type="submit" value="<?php _e( 'Search IP', 'limit-attempts' ); ?>" class="button" />
							</p>
						</form>
						<form>
							<input disabled="disabled" type="submit" value="<?php _e( 'Clear Log', 'limit-attempts' ); ?>" class="button" />
						</form>
						<form>
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
							<table class="wp-list-table widefat fixed bws-plugins_page_limit-attempts-pro">
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
						</form>
					</div>
				</div>
				<div class="bws_pro_version_tooltip">
					<div class="bws_info"><?php _e( 'Unlock premium options by upgrading to Pro version', 'limit-attempts' ); ?></div>
					<a class="bws_button" href="http://bestwebsoft.com/products/limit-attempts/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro"><?php _e( "Learn More", 'limit-attempts' ); ?></a>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	<?php }
}

if ( ! function_exists( 'lmtttmpts_display_summaries' ) ) {
	function lmtttmpts_display_summaries() { 
		global $lmtttmpts_plugin_info, $wp_version; ?>
		<div id="lmtttmpts_summaries">
			<div style="max-width: 100%" class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">
					<div class="bws_table_bg"></div>
					<div style="padding: 5px;">
						<p><?php _e( 'For last 24 hours the plugin prevented * hacking attempts.', 'limit-attempts' ); ?></p>
						<p><?php _e( 'For last month the plugin prevented * hacking attempts.', 'limit-attempts' ); ?></p>
						<p><?php _e( 'For last half-year the plugin prevented * hacking attempts.', 'limit-attempts' ); ?></p>
						<img src="<?php echo plugins_url( '../images/summaries-tab.png', __FILE__ ); ?>" alt="" />
					</div>
				</div>
				<div class="bws_pro_version_tooltip">
					<div class="bws_info"><?php _e( 'Unlock premium options by upgrading to Pro version', 'limit-attempts' ); ?></div>
					<a class="bws_button" href="http://bestwebsoft.com/products/limit-attempts/?k=33bc89079511cdfe28aeba317abfaf37&pn=140&v=<?php echo $lmtttmpts_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Limit Attempts Pro"><?php _e( "Learn More", 'limit-attempts' ); ?></a>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	<?php }
}