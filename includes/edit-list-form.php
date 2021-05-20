<?php
/**
 * Display form for displaying, edititng or deleting of denylist/allowlist
 * @package Limit Attempts
 * @since 1.1.8
 */
if ( ! function_exists( 'lmtttmpts_display_list' ) ) {
	function lmtttmpts_display_list() {
		global $wpdb, $wp_version, $lmtttmpts_options;

		$list = isset( $_GET['list'] ) && 'allowlist' == $_GET['list'] ? 'allowlist' : 'denylist';

		if ( 'allowlist' == $list ) {
			$file = ( isset( $_GET['tab-action'] ) ) ? 'allowlist-email' : 'allowlist';
		} else {
			$file = ( isset( $_GET['tab-action'] ) ) ? 'denylist-email' : 'denylist';
		}
		if ( $file != 'allowlist-email' ) {
			require_once( dirname( __FILE__ ) . '/'. $file . '.php' );
		}

		$title = '';

		switch ( $file ) {
			case 'allowlist' :
				$list_table = new Lmtttmpts_Allowlist();
				$title = __( 'IP Allow List', 'limit-attempts' );
				break;
			case 'denylist' :
				$list_table = new Lmtttmpts_Denylist();
				$title = __( 'IP Deny List', 'limit-attempts' );
				break;
			case 'denylist-email' :
				$list_table = new Lmtttmpts_Denylist_Email();
				$title = __( 'Email Deny List', 'limit-attempts' );
				break;
			case 'allowlist-email' :
				$title = __( 'Email Allow List', 'limit-attempts' );
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
			<h1 class="wp-heading-inline"><?php echo $title; ?></h1>
			<a style=" <?php echo ( 'denylist' != $file && 'allowlist' != $file ) ? 'background: #f2eccc;' : '';  
				echo ( bws_hide_premium_options_check( $lmtttmpts_options ) && 'denylist-email' == $file ) ? 'display:none;' : '' ?>"
				href="<?php echo 'admin.php?page=lmtttmpts-create-new-item.php&type=' . $file ?>" class="add-new-h2"><?php _e( 'Add New', 'limit-attempts' ); ?></a>		
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
            if ( $file != 'allowlist-email' ) {
	            $list_table->action_message();
	            $list_table->prepare_items();
	        }
	        else {
	        	lmtttmpts_display_advertising( 'allowlist-email-table' );
	        }
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

		if ( ! empty( $lmtttmpts_table ) ) {
		    $display_style = ( bws_hide_premium_options_check( $lmtttmpts_options ) && 'allowlist' == $lmtttmpts_table ) ? 'display:none;' : 'display:block;';
            $background = ( 'allowlist' == $lmtttmpts_table ) ? 'background: #f2eccc;' : '';
            ?>
            <h2 class="nav-tab-wrapper">
                <a class="nav-tab <?php if ( ! isset( $_GET['tab-action'] ) ) echo ' nav-tab-active'; ?>"
                   href="admin.php?page=limit-attempts-deny-and-allowlist.php&list=<?php echo $lmtttmpts_table ?>"><?php _e( 'IP Address', 'limit-attempts' ); ?>
                </a>
                <a class="nav-tab <?php if ( isset( $_GET['tab-action'] ) ) echo ' nav-tab-active'; ?>"
                   style="<?php echo $background; echo  $display_style ?>"
                   href="admin.php?page=limit-attempts-deny-and-allowlist.php&amp;list=<?php echo $lmtttmpts_table ?>&amp;tab-action=<?php echo $lmtttmpts_table ?>_email"><?php _e( 'Email', 'limit-attempts' ); ?>
                </a>
            </h2>
        <?php }
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