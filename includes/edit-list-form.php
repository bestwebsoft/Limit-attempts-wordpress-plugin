<?php
/**
 * Display form for displaying, edititng or deleting of denylist/allowlist
 * @package Limit Attempts
 * @since 1.1.8
 */
if ( ! function_exists( 'lmtttmpts_display_list' ) ) {
	function lmtttmpts_display_list() {
		global $wpdb, $wp_version;

		$list = isset( $_GET['list'] ) && 'allowlist' == $_GET['list'] ? 'allowlist' : 'denylist';

		if ( 'allowlist' == $list ) {
			$file = $list;
		} else {
			$file = ( isset( $_GET['tab-action'] ) ) ? 'denylist-email' : 'denylist';
		}

		require_once( dirname( __FILE__ ) . '/'. $file . '.php' );

		switch ( $file ) {
			case 'allowlist' :
				$list_table = new Lmtttmpts_Allowlist();
				break;
			case 'denylist' :
				$list_table = new Lmtttmpts_Denylist();
				break;
			case 'denylist-email' :
				$list_table = new Lmtttmpts_Denylist_Email();
				break;
		}

		$blacklist_count = $wpdb->get_var( "
            SELECT 
                SUM(T.id) 
            FROM 
            ( 
                SELECT 
                    COUNT(*) AS id 
                FROM `{$wpdb->prefix}lmtttmpts_denylist_email` 
                UNION ALL
                SELECT 
                    COUNT(*) AS id 
                FROM 
                    `{$wpdb->prefix}lmtttmpts_denylist`
            ) T
        " );
		$whitelist_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}lmtttmpts_allowlist`" );

		if ( $wp_version >= '4.7' ) { ?>
			<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?></h1>
			<hr class="wp-header-end">
		<?php } ?>
		<ul class="subsubsub">
			<li><a<?php if ( 'denylist' == $list ) echo ' class="current"'; ?> href="admin.php?page=limit-attempts-deny-and-allowlist.php&amp;list=denylist<?php if ( ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) || isset( $_GET['edit_action'] ) ) echo '&amp;action=edit'; ?>"><?php _e( 'Deny list', 'limit-attempts' ); if ( ! empty( $blacklist_count ) ) echo ' (' . $blacklist_count . ')'; ?></a></li>
			&nbsp;|&nbsp;
			<li><a<?php if ( 'allowlist' == $list ) echo ' class="current"'; ?> href="admin.php?page=limit-attempts-deny-and-allowlist.php&amp;list=allowlist<?php if ( ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) || isset( $_GET['edit_action'] ) ) echo '&amp;action=edit'; ?>"><?php _e( 'Allow list', 'limit-attempts' ); if ( ! empty( $whitelist_count ) ) echo ' (' . $whitelist_count . ')'; ?></a></li>
		</ul>
		<div class="clear"></div>
        <div id="lmtttmpts_<?php echo $list; ?>" class="lmtttmpts_list">
            <?php lmtttmpts_edit_list();
            $list_table->action_message();
            $list_table->prepare_items();
            if ( isset( $_GET['tab-action'] ) && 'denylist_email' == $_GET['tab-action'] ) {
				$list = "denylist&#38;tab-action=denylist_email";
                $search_text = __( 'Search Email', 'limit-attempts' );
            } else {
				$search_text =__( 'Search IP', 'limit-attempts' );
			}
            if ( ! isset( $_GET['tab-action'] ) || ( isset( $_GET['tab-action'] ) && 'allowlist_email' != $_GET['tab-action'] ) ) { ?>
                <form method="get" action="admin.php">
                    <?php $list_table->search_box( $search_text, 'search_' . $list . 'ed_ip' ); ?>
                    <input type="hidden" name="page" value="limit-attempts-deny-and-allowlist.php" />
                    <input type="hidden" name="list" value="<?php echo $list; ?>" />
                </form>
                <form method="post" action="admin.php?page=limit-attempts-deny-and-allowlist.php&list=<?php echo $list; ?>">
                    <?php $list_table->display(); ?>
                </form>
            <?php } ?>
        </div>
	<?php }
}

if ( ! function_exists( 'lmtttmpts_edit_list' ) ) {
	function lmtttmpts_edit_list( ) {
		global $wpdb, $lmtttmpts_options;
		$lmtttmpts_table = isset( $_GET['list'] ) && 'allowlist' == $_GET['list'] ? 'allowlist' : 'denylist';

		$message = $error = '';

		if ( ( isset( $_POST['lmtttmpts_add_to_allowlist_my_ip'] ) || isset( $_POST['lmtttmpts_add_to_allowlist'] ) ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
			$add_ip = isset( $_POST['lmtttmpts_add_to_allowlist_my_ip'] ) ? esc_html( trim( $_POST['lmtttmpts_add_to_allowlist_my_ip_value'] ) ) : false;
			$add_ip = ! $add_ip && isset( $_POST['lmtttmpts_add_to_allowlist'] ) ? esc_html( trim( $_POST['lmtttmpts_add_to_allowlist'] ) ) : $add_ip;
			if ( empty( $add_ip ) ) {
				$error = __( 'ERROR:', 'limit-attempts' ) . '&nbsp;' . __( 'You must type IP address', 'limit-attempts' );
			} elseif ( filter_var( $add_ip, FILTER_VALIDATE_IP ) ) {
				if ( lmtttmpts_is_ip_in_table( $add_ip, 'allowlist' ) ) {
					$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address has already been added to allow list', 'limit-attempts' ) . ' - ' . $add_ip;
				} else {
					if ( lmtttmpts_is_ip_in_table( $add_ip, 'denylist' ) ) {
						$message .= __( 'Notice:', 'limit-attempts' ) . '&nbsp;' . __( 'This IP address is in deny list too, please check this to avoid errors', 'limit-attempts' ) . ' - ' . $add_ip;
						$flag = false;
					} else {
						$flag = true;
					}

					lmtttmpts_remove_from_blocked_list( $add_ip );
					if ( false !== lmtttmpts_add_ip_to_allowlist( $add_ip ) ) {
						if ( ! empty( $message ) )
							$message .= '<br />';
						$message .= $add_ip . '&nbsp;' . __( 'has been added to allow list', 'limit-attempts' );
					} else {
						if ( ! empty( $error ) )
							$error .= '<br />';
						$error .= $add_ip . '&nbsp;' . __( "can't be added to allow list.", 'limit-attempts' );
					}
				}
			} else {
				$error .= sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' ) . '<br />' . $add_ip . '&nbsp;' . __( "can't be added to allow list.", 'limit-attempts' );
			}
		} else
		if ( isset( $_POST['lmtttmpts_add_to_denylist'] ) && check_admin_referer( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ) ) {
			/* IP to add to denylist */
			$add_to_blacklist_ip = esc_html( trim( $_POST['lmtttmpts_add_to_denylist'] ) );
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
							if ( ! empty( $message ) )
								$message .= '<br />';
							$message .= $add_to_blacklist_ip . '&nbsp;' . __( 'has been added to deny list', 'limit-attempts' );
						} else {
							if ( ! empty( $error ) )
								$error .= '<br />';
							$error .= $add_to_blacklist_ip . '&nbsp;' . __( "can't be added to deny list.", 'limit-attempts' );
						}
					}
				} else {
					/* wrong IP format */
					$error .= sprintf( __( 'Wrong format or it does not lie in range %s.', 'limit-attempts' ), '0.0.0.0 - 255.255.255.255' ) . '<br />' . $add_to_blacklist_ip . '&nbsp;' . __( "can't be added to deny list.", 'limit-attempts' );
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
		    $display_style = ( bws_hide_premium_options_check( $lmtttmpts_options ) && 'allowlist' == $lmtttmpts_table ) ? 'display:none;' : 'display:block;';
            $background = ( 'allowlist' == $lmtttmpts_table ) ? 'background: #f2eccc;' : '';
            ?>
            <h2 class="nav-tab-wrapper" <?php if ( ! isset( $_GET['tab-action'] ) ) echo ' style="margin-bottom: 20px;"'; ?>>
                <a class="nav-tab <?php if ( ! isset( $_GET['tab-action'] ) ) echo ' nav-tab-active'; ?>"
                   href="admin.php?page=limit-attempts-deny-and-allowlist.php&list=<?php echo $lmtttmpts_table ?>"><?php _e( 'IP Address', 'limit-attempts' ); ?>
                </a>
                <a class="nav-tab <?php if ( isset( $_GET['tab-action'] ) ) echo ' nav-tab-active'; ?>"
                   style="<?php echo $background; echo  $display_style ?>"
                   href="admin.php?page=limit-attempts-deny-and-allowlist.php&amp;list=<?php echo $lmtttmpts_table ?>&amp;tab-action=<?php echo $lmtttmpts_table ?>_email"><?php _e( 'Email', 'limit-attempts' ); ?>
                </a>
            </h2>
            <?php if ( ! isset( $_GET['tab-action'] ) ) { ?>
                <form id="lmtttmpts_edit_list_form"
                      action="admin.php?page=limit-attempts-deny-and-allowlist.php&amp;list=<?php echo $lmtttmpts_table; ?>"
                      method="post">
                    <input type="text" maxlength="31" name="lmtttmpts_add_to_<?php echo $lmtttmpts_table; ?>"/>
                    <input class="button-primary" type="submit" value="<?php _e( 'Add IP', 'limit-attempts' ) ?>"/>
                    <?php $my_ip = lmtttmpts_get_ip();
                    if ( ! empty( $my_ip ) && isset( $_GET['list'] ) && ( 'denylist' != $_GET['list'] ) ) { ?>
                        <br/>
                        <label>
                            <input type="checkbox" name="lmtttmpts_add_to_allowlist_my_ip" value="1"/>
                            <?php _e( 'My IP', 'limit-attempts' ); ?>
                            <input type="hidden" name="lmtttmpts_add_to_allowlist_my_ip_value" value="<?php echo $my_ip; ?>"/>
                        </label>
                    <?php } ?>
                    <div>
                        <span class="bws_info" style="display: inline-block;margin: 10px 0;">
                            <?php _e( "Allowed formats:", 'limit-attempts' ); ?><code>192.168.0.1</code>
                        </span>
                    </div>
                    <input type="hidden" name="lmtttmpts_table" value="<?php echo $lmtttmpts_table; ?>"/>
                    <?php wp_nonce_field( 'limit-attempts/limit-attempts.php', 'lmtttmpts_nonce_name' ); ?>
                </form>
                <?php lmtttmpts_display_advertising( $lmtttmpts_table );
            } elseif (
                    isset( $_GET['tab-action'] ) &&
                    ( 'allowlist_email' ==  $_GET['tab-action'] || 'denylist_email' ==  $_GET['tab-action'] )
            ) {
                lmtttmpts_display_advertising( $_GET['tab-action'] );
            }
        }
	}
}

if ( ! function_exists( 'lmtttmpts_display_blocked' ) ) {
	function lmtttmpts_display_blocked() {
		$list = ( isset( $_GET['tab-action'] ) ) ? 'blocked-email' : 'blocked';
		require_once( dirname( __FILE__ ) . '/'. $list . '.php' );
		$list_table = ( 'blocked' == $list ) ? new Lmtttmpts_Blocked_List() : new Lmtttmpts_Blocked_List_Email();
		?>
        <h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?></h1>
        <div id="lmtttmpts_blocked" class="lmtttmpts_list">
            <h2 class="nav-tab-wrapper">
                <a class="nav-tab <?php if ( ! isset( $_GET['tab-action'] ) ) echo ' nav-tab-active'; ?>"
                   href="admin.php?page=limit-attempts-blocked.php"><?php _e( 'IP Address', 'limit-attempts' ); ?>
                </a>
                <a class="nav-tab <?php if ( isset( $_GET['tab-action'] ) ) echo ' nav-tab-active'; ?>"
                   href="admin.php?page=limit-attempts-blocked.php&amp;tab-action=email"><?php _e( 'Email', 'limit-attempts' ); ?>
                </a>
            </h2>
			<?php
			$list_table->action_message();
			$list_table->prepare_items();
			if ( isset( $_GET['tab-action'] ) && 'email' == $_GET['tab-action'] ) {
                $tab_action = "&tab-action=email";
				$search_text = __( 'Search Email', 'limit-attempts' );
			} else {
				$search_text =__( 'Search IP', 'limit-attempts' );
                $tab_action = "";
			}
			?>
            <form method="get" action="admin.php">
                <?php $list_table->search_box( $search_text, 'search_' . $list . 'ed_ip' ); ?>
                <input type="hidden" name="page" value="limit-attempts-blocked.php" />
                <input type="hidden" name="list" value="<?php echo $list; ?>" />
                <?php if ( isset( $_GET['tab-action'] ) && 'email' == $_GET['tab-action'] ) { ?>
                    <input type="hidden" name="tab-action" value="email" />
                <?php } ?>
            </form>
            <form method="post" action="admin.php?page=limit-attempts-blocked.php<?php echo $tab_action; ?>">
                <?php $list_table->display(); ?>
            </form>
        </div>
	<?php }
}