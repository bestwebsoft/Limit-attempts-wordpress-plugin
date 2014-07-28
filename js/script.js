( function( $ ) {
	$( document ).ready( function() {
		$( '#lmtttmpts_settings input' ).bind( "change click select", function() {
			if ( $( this ).attr( 'type' ) != 'submit' ) {
				$( '#lmtttmpts_settings_notice' ).css( 'display', 'block' );
			};
		});
		$( '#lmtttmpts_settings textarea' ).click( function() {
			$( '#lmtttmpts_settings_notice' ).css( 'display', 'block' );
		});

		$( '#lmtttmpts_show_options_for_block_message' ).removeClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_hide_options_for_block_message' ).removeClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_show_options_for_block_message_button' ).addClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_hide_options_for_block_message_button' ).addClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_show_options_for_block_message' ).click( function() {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_hide_options_for_block_message' ).css( 'display', 'block' );
			$( '.lmtttmpts_block_message_block' ).removeClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts_hide_options_for_block_message' ).click( function() {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_show_options_for_block_message' ).css( 'display', 'block' );
			$( '.lmtttmpts_block_message_block' ).addClass( 'lmtttmpts_hidden' );
		});

		$( '#lmtttmpts_show_options_for_email_message' ).removeClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_hide_options_for_email_message' ).removeClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_show_options_for_email_message_button' ).addClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_hide_options_for_email_message_button' ).addClass( 'lmtttmpts_hidden' );
		$( '#lmtttmpts_show_options_for_email_message' ).click( function() {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_hide_options_for_email_message' ).css( 'display', 'block' );
			$( '.lmtttmpts_email_message_block' ).removeClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts_hide_options_for_email_message' ).click( function() {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_show_options_for_email_message' ).css( 'display', 'block' );
			$( '.lmtttmpts_email_message_block' ).addClass( 'lmtttmpts_hidden' );
		});

		$( '#lmtttmpts_nav_tab_message_js' ).css( 'display', 'block' );
		$( '#lmtttmpts_nav_tab_message_no_js' ).css( 'display', 'none' );
		$( '#lmtttmpts_nav_tab_email_js_a' ).css( 'display', 'block' );
		$( '#lmtttmpts_nav_tab_email_no_js_a' ).css( 'display', 'none' );

		$( '#lmtttmpts_message_invalid_attempt' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_blocked' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_blacklisted' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt_area' ).removeClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blocked_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blacklisted_area' ).addClass( 'lmtttmpts_hidden' );
		} );
		$( '#lmtttmpts_message_blocked' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_blacklisted' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blocked_area' ).removeClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blacklisted_area' ).addClass( 'lmtttmpts_hidden' );
		} );
		$( '#lmtttmpts_message_blacklisted' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_blocked' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blocked_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blacklisted_area' ).removeClass( 'lmtttmpts_hidden' );
		} );

		$( '#lmtttmpts_email_blocked' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_blacklisted' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_blocked_area' ).removeClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_email_blacklisted_area' ).addClass( 'lmtttmpts_hidden' );
		} );
		$( '#lmtttmpts_email_blacklisted' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_blocked' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_blocked_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_email_blacklisted_area' ).removeClass( 'lmtttmpts_hidden' );
		} );

		$( '#lmtttmpts_email_message_options' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.lmtttmpts_email_message_block' ).removeClass( 'lmtttmpts_hidden' );
			}
			else {
				$( '.lmtttmpts_email_message_block' ).addClass( 'lmtttmpts_hidden' );
			}
		});
		$( '#lmtttmpts_notify_email_options' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.lmtttmpts_notify_email_block' ).removeClass( 'lmtttmpts_hidden' );
			}
			else {
				$( '.lmtttmpts_notify_email_block' ).addClass( 'lmtttmpts_hidden' );
			}
		});
	});
} )(jQuery);