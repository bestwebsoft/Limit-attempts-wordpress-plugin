<?php
/**
 * Display form for displaying, edititng or deleting of blacklist/whitelist
 * @package Limit Attempts
 * @since 1.1.8
 */
if ( ! function_exists( 'lmtttmpts_display_list' ) ) {
	function lmtttmpts_display_list() {
		global $wpdb, $wp_version;
		$list = isset( $_GET['list'] ) && 'whitelist' == $_GET['list'] ? 'whitelist' : 'blacklist';
		require_once( dirname( __FILE__ ) . '/' . $list . '.php' );
		$list_table = 'whitelist' == $list ? new Lmtttmpts_Whitelist() : new Lmtttmpts_Blacklist();
		$blacklist_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}lmtttmpts_blacklist`" );
		$whitelist_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}lmtttmpts_whitelist`" );
		if ( $wp_version >= '4.7' ) { ?>
			<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?></h1>
			<hr class="wp-header-end">
		<?php } else { ?>

		<?php } ?>
		<ul class="subsubsub">
			<li><a<?php if ( 'blacklist' == $list ) echo ' class="current"'; ?> href="admin.php?page=limit-attempts-black-and-whitelist.php&amp;list=blacklist<?php if ( ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) || isset( $_GET['edit_action'] ) ) echo '&amp;action=edit'; ?>"><?php _e( 'Blacklisted', 'limit-attempts' ); if ( ! empty( $blacklist_count ) ) echo ' (' . $blacklist_count . ')'; ?></a></li>
			&nbsp;|&nbsp;
			<li><a<?php if ( 'whitelist' == $list ) echo ' class="current"'; ?> href="admin.php?page=limit-attempts-black-and-whitelist.php&amp;list=whitelist<?php if ( ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) || isset( $_GET['edit_action'] ) ) echo '&amp;action=edit'; ?>"><?php _e( 'Whitelisted', 'limit-attempts' ); if ( ! empty( $whitelist_count ) ) echo ' (' . $whitelist_count . ')'; ?></a></li>
		</ul>
		<div class="clear"></div>
        <div id="lmtttmpts_<?php echo $list; ?>" class="lmtttmpts_list">
            <?php lmtttmpts_edit_list();
            $list_table->action_message();
            $list_table->prepare_items();
            if( isset( $_GET['tab-action']) != 'whitelist_email' ) {?>
                <form method="get" action="admin.php">
                    <?php $list_table->search_box( __( 'Search IP', 'limit-attempts' ), 'search_' . $list . 'ed_ip' ); ?>
                    <input type="hidden" name="page" value="limit-attempts-black-and-whitelist.php" />
                    <input type="hidden" name="list" value="<?php echo $list; ?>" />
                </form>
                <form method="post" action="admin.php?page=limit-attempts-black-and-whitelist.php&list=<?php echo $list; ?>">
                    <?php $list_table->display(); ?>
                </form>
            <?php }?>
        </div>
	<?php }
}

if ( ! function_exists( 'lmtttmpts_edit_list' ) ) {
	function lmtttmpts_edit_list( ) {
		global $wpdb, $lmtttmpts_options;
		$lmtttmpts_table = isset( $_GET['list'] ) && 'whitelist' == $_GET['list'] ? 'whitelist' : 'blacklist';
		$my_ip = lmtttmpts_get_ip();

		$message = $error = '';

		if ( ( isset( $_POST['lmtttmpts_add_to_whitelist_my_ip'] ) || isset( $_POST['lmtttmpts_add_to_whitelist'] ) ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
			$add_ip = isset( $_POST['lmtttmpts_add_to_whitelist_my_ip'] ) ? esc_html( trim( $_POST['lmtttmpts_add_to_whitelist_my_ip_value'] ) ) : false;
			$add_ip = ! $add_ip && isset( $_POST['lmtttmpts_add_to_whitelist'] ) ? esc_html( trim( $_POST['lmtttmpts_add_to_whitelist'] ) ) : $add_ip;
			if ( empty( $add_ip ) ) {
				$error = __( 'ERROR:', 'limit-attempts' ) . '&nbsp;' . __( 'You must type IP address', 'limit-attempts' );
			} elseif ( filter_var( $add_ip, FILTER_VALIDATE_IP ) ) {
				if ( lmtttmpts_is_ip_in_table( $add_ip, 'whitelist' ) ) {
					$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address has already been added to whitelist', 'limit-attempts' ) . ' - ' . $add_ip;
				} else {
					if ( lmtttmpts_is_ip_in_table( $add_ip, 'blacklist' ) ) {
						$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address is in blacklist too, please check this to avoid errors', 'limit-attempts' ) . ' - ' . $add_ip;
						$flag = false;
					} else {
						$flag = true;
					}

					lmtttmpts_remove_from_blocked_list( $add_ip );
					if ( false !== lmtttmpts_add_ip_to_whitelist( $add_ip ) ) {
						if ( ! empty( $message ) )
							$message .= '<br />';
						$message .= $add_ip . '&nbsp;' . __( 'has been added to whitelist', 'limit-attempts' );
					} else {
						if ( ! empty( $error ) )
							$error .= '<br />';
						$error .= $add_ip . '&nbsp;' . __( "can't be added to whitelist.", 'limit-attempts' );
					}
				}
			} else {
				$error .= sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' ) . '<br />' . $add_ip . '&nbsp;' . __( "can't be added to whitelist.", 'limit-attempts' );
			}
		} else
		if ( isset( $_POST['lmtttmpts_add_to_blacklist'] ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
			/* IP to add to blacklist */
			$add_to_blacklist_ip = esc_html( trim( $_POST['lmtttmpts_add_to_blacklist'] ) );
			if ( '' == $add_to_blacklist_ip ) {
				$error = __( 'ERROR:', 'limit-attempts' ) . '&nbsp;' . __( 'You must type IP address', 'limit-attempts' );
			} else {
				if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $add_to_blacklist_ip ) ) {
					if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'blacklist' ) ) {
						$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address has already been added to blacklist', 'limit-attempts' ) . ' - ' . $add_to_blacklist_ip;
					} else {
						if ( lmtttmpts_is_ip_in_table( $add_to_blacklist_ip, 'whitelist' ) ) {
							$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address is in whitelist too, please check this to avoid errors', 'limit-attempts' ) . ' - ' . $add_to_blacklist_ip;
						}

						lmtttmpts_remove_from_blocked_list( $add_to_blacklist_ip );
						if ( false !== lmtttmpts_add_ip_to_blacklist( $add_to_blacklist_ip ) ) {
							if ( ! empty( $message ) )
								$message .= '<br />';
							$message .= $add_to_blacklist_ip . '&nbsp;' . __( 'has been added to blacklist', 'limit-attempts' );
						} else {
							if ( ! empty( $error ) )
								$error .= '<br />';
							$error .= $add_to_blacklist_ip . '&nbsp;' . __( "can't be added to blacklist.", 'limit-attempts' );
						}
					}
				} else {
					/* wrong IP format */
					$error .= sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' ) . '<br />' . $add_to_blacklist_ip . '&nbsp;' . __( "can't be added to blacklist.", 'limit-attempts' );
				}
			}
		}

		if ( ! empty( $error ) ) { ?>
			<div class="error inline"><p><?php echo $error; ?></p></div>
		<?php }
		if ( ! empty( $message ) ) { ?>
			<div class="updated inline"><p><?php echo $message; ?></p></div>
		<?php }
		if ( ! empty( $lmtttmpts_table ) ) {
            if (!empty('whitelist' == $lmtttmpts_table)) { ?>
                <h2 class="nav-tab-wrapper" <?php if (!isset($_GET['tab-action']) || 'whitelist_email' != $_GET['tab-action']) echo ' style="margin-bottom: 20px;"'; ?>>
                    <a class="nav-tab<?php if (!isset($_GET['tab-action'])) echo ' nav-tab-active'; ?>"
                       href="admin.php?page=limit-attempts-black-and-whitelist.php&list=whitelist"><?php _e('IP Address', 'limit-attempts'); ?></a>
                    <a class="nav-tab  <?php if (isset($_GET['tab-action']) && 'whitelist_email' == $_GET['tab-action']) echo ' nav-tab-active'; ?>"
                       style=" background: #f2eccc;
                            <?php if( bws_hide_premium_options_check( $lmtttmpts_options ) ) {
                                echo 'display:none;'; } else {
                                echo 'display:block;'; }
                            ?> "
                       href="admin.php?page=limit-attempts-black-and-whitelist.php&amp;list=whitelist&amp;tab-action=whitelist_email"><?php _e('Email', 'limit-attempts'); ?></a>
                </h2>
            <?php } ?>
            <?php if ( isset($_GET['tab-action']) != 'whitelist_email') { ?>
                <form id="lmtttmpts_edit_list_form"
                      action="admin.php?page=limit-attempts-black-and-whitelist.php&amp;list=<?php echo $lmtttmpts_table; ?>"
                      method="post">
                    <input type="text" maxlength="31" name="lmtttmpts_add_to_<?php echo $lmtttmpts_table; ?>"/>
                    <input class="button-primary" type="submit" value="<?php _e('Add IP', 'limit-attempts') ?>"/>
                    <?php $my_ip = lmtttmpts_get_ip();
                    if (!empty($my_ip) && isset($_GET['list']) && ('blacklist' != $_GET['list'])) { ?>
                        <br/>
                        <label>
                            <input type="checkbox" name="lmtttmpts_add_to_whitelist_my_ip" value="1"/>
                            <?php _e('My IP', 'limit-attempts'); ?>
                            <input type="hidden" name="lmtttmpts_add_to_whitelist_my_ip_value"
                                   value="<?php echo $my_ip; ?>"/>
                        </label>
                    <?php } ?>
                    <div>
                        <span class="bws_info"
                              style="display: inline-block;margin: 10px 0;"><?php _e("Allowed formats:", 'limit-attempts'); ?><code>192.168.0.1</code></span>
                    </div>
                    <input type="hidden" name="lmtttmpts_table" value="<?php echo $lmtttmpts_table; ?>"/>
                    <?php wp_nonce_field('limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name'); ?>
                </form>
                <?php lmtttmpts_display_advertising($lmtttmpts_table); ?>
            <?php } elseif(isset($_GET['tab-action']) == 'whitelist_email') {
                lmtttmpts_display_advertising('whitelist_email');
            }
        }
	}
}