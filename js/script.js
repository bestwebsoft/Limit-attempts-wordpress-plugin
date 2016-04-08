( function( $ ) {
	$( document ).ready( function() {
		/* hide zero values */
		$( '.lmtttmpts-zero-value' ).addClass( 'lmtttmpts_hidden' );

		/* hide "block/add to blacklist" time options at the page load */
		$( '.lmtttmpts-hidden-input, .lmtttmpts-display' ).toggleClass( 'lmtttmpts_hidden' );

		/* display inputs if 'Edit' was clicked*/
		$( '#lmtttmpts-time-of-lock-edit' ).click( function(){
			$( '#lmtttmpts-time-of-lock-display, #lmtttmpts-time-of-lock' ).toggleClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts-allowed-retries-edit' ).click( function(){
			$( '#lmtttmpts-allowed-retries-display, #lmtttmpts-allowed-retries' ).toggleClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts-time-to-reset-edit' ).click( function(){
			$( '#lmtttmpts-time-to-reset-display, #lmtttmpts-time-to-reset' ).toggleClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts-allowed-locks-edit' ).click( function(){
			$( '#lmtttmpts-allowed-locks-display, #lmtttmpts-allowed-locks' ).toggleClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts-time-to-reset-block-edit' ).click( function(){
			$( '#lmtttmpts-time-to-reset-block-display, #lmtttmpts-time-to-reset-block' ).toggleClass( 'lmtttmpts_hidden' );
		});

		$('div.updated.lmttmpts_message, div.error.lmttmpts_message').insertAfter( $('div.wrap h2:first') );

			/* time-of-lock */
		var daysOfLock = $( '#lmtttmpts-days-of-lock-display' ).val(),
			hoursOfLock = $( '#lmtttmpts-hours-of-lock-display' ).val(),
			minutesOfLock = $( '#lmtttmpts-minutes-of-lock-display' ).val(),
			/* allowed-retries */
			allowedRetries = $( '#lmtttmpts-allowed-retries-number-display' ).val(),
			/* time-to-reset */
			daysToReset = $( '#lmtttmpts-days-to-reset-display' ).val(),
			hoursToReset = $( '#lmtttmpts-hours-to-reset-display' ).val(),
			minutesToReset = $( '#lmtttmpts-minutes-to-reset-display' ).val(),
			/* allowed-locks */
			allowedLocks = $( '#lmtttmpts-allowed-locks-number-display' ).val(),
			/* time-to-reset-block */
			daysToResetBlock = $( '#lmtttmpts-days-to-reset-block-display' ).val(),
			hoursToResetBlock = $( '#lmtttmpts-hours-to-reset-block-display' ).val(),
			minutesToResetBlock = $( '#lmtttmpts-minutes-to-reset-block-display' ).val();
		$( document ).click( function( event ) {
			/* hide time-of-lock inputs if clicked outside and values not changed */
			if ( ! $( event.target ).closest( "#lmtttmpts-time-of-lock-edit, #lmtttmpts-time-of-lock" ).length &&
				daysOfLock == $( '#lmtttmpts-days-of-lock-display' ).val() &&
				hoursOfLock == $( '#lmtttmpts-hours-of-lock-display' ).val() &&
				minutesOfLock == $( '#lmtttmpts-minutes-of-lock-display' ).val() ) {
				$( '#lmtttmpts-time-of-lock-display' ).removeClass( 'lmtttmpts_hidden' );
				$( '#lmtttmpts-time-of-lock' ).addClass( 'lmtttmpts_hidden' );
			};
			/* hide allowed-retries inputs if clicked outside and values not changed */
			if ( ! $( event.target ).closest( "#lmtttmpts-allowed-retries-edit, #lmtttmpts-allowed-retries" ).length &&
				allowedRetries == $( '#lmtttmpts-allowed-retries-number-display' ).val() ) {
				$( '#lmtttmpts-allowed-retries-display' ).removeClass( 'lmtttmpts_hidden' );
				$( '#lmtttmpts-allowed-retries' ).addClass( 'lmtttmpts_hidden' );
			};
			/* hide time-to-reset inputs if clicked outside and values not changed */
			if ( ! $( event.target ).closest( "#lmtttmpts-time-to-reset-edit, #lmtttmpts-time-to-reset" ).length &&
				daysToReset == $( '#lmtttmpts-days-to-reset-display' ).val() &&
				hoursToReset == $( '#lmtttmpts-hours-to-reset-display' ).val() &&
				minutesToReset == $( '#lmtttmpts-minutes-to-reset-display' ).val() ) {
				$( '#lmtttmpts-time-to-reset-display' ).removeClass( 'lmtttmpts_hidden' );
				$( '#lmtttmpts-time-to-reset' ).addClass( 'lmtttmpts_hidden' );
			};
			/* hide allowed-locks inputs if clicked outside and values not changed */
			if ( ! $( event.target ).closest( "#lmtttmpts-allowed-locks-edit, #lmtttmpts-allowed-locks" ).length &&
				allowedLocks == $( '#lmtttmpts-allowed-locks-number-display' ).val() ) {
				$( '#lmtttmpts-allowed-locks-display' ).removeClass( 'lmtttmpts_hidden' );
				$( '#lmtttmpts-allowed-locks' ).addClass( 'lmtttmpts_hidden' );
			};
			/* hide time-to-reset-block inputs if clicked outside and values not changed */
			if ( ! $( event.target ).closest( '#lmtttmpts-time-to-reset-block-edit, #lmtttmpts-time-to-reset-block' ).length &&
				daysToResetBlock == $( '#lmtttmpts-days-to-reset-block-display' ).val() &&
				hoursToResetBlock == $( '#lmtttmpts-hours-to-reset-block-display' ).val() &&
				minutesToResetBlock == $( '#lmtttmpts-minutes-to-reset-block-display' ).val() ) {
				$( '#lmtttmpts-time-to-reset-block-display' ).removeClass( 'lmtttmpts_hidden' );
				$( '#lmtttmpts-time-to-reset-block' ).addClass( 'lmtttmpts_hidden' );
			};
			event.stopPropagation();
		});

		/* hide/display messages */
		$( '#lmtttmpts_show_options_for_block_message_button' ).click( function( event ) {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_hide_options_for_block_message_button' ).css( 'display', 'block' );
			$( '.lmtttmpts_block_message_block' ).removeClass( 'lmtttmpts_hidden' );
			event.preventDefault();
		});
		$( '#lmtttmpts_hide_options_for_block_message_button' ).click( function( event ) {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_show_options_for_block_message_button' ).css( 'display', 'block' );
			$( '.lmtttmpts_block_message_block' ).addClass( 'lmtttmpts_hidden' );
			event.preventDefault();
		});

		$( '#lmtttmpts_show_options_for_email_message_button' ).click( function( event ) {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_hide_options_for_email_message_button' ).css( 'display', 'block' );
			$( '.lmtttmpts_email_message_block' ).removeClass( 'lmtttmpts_hidden' );
			event.preventDefault();
		});
		$( '#lmtttmpts_hide_options_for_email_message_button' ).click( function( event ) {
			$( this ).css( 'display', 'none' );
			$( '#lmtttmpts_show_options_for_email_message_button' ).css( 'display', 'block' );
			$( '.lmtttmpts_email_message_block' ).addClass( 'lmtttmpts_hidden' );
			event.preventDefault();
		});

		$( '#lmtttmpts_nav_tab_message_js, #lmtttmpts_nav_tab_email_js_a' ).css( 'display', 'block' );
		$( '#lmtttmpts_nav_tab_message_no_js, #lmtttmpts_nav_tab_email_no_js_a' ).css( 'display', 'none' );

		/* click on front-end messages tabs */
		$( '#lmtttmpts_message_invalid_attempt' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_blocked, #lmtttmpts_message_blacklisted' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_blocked_area, #lmtttmpts_message_blacklisted_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_invalid_attempt_area' ).removeClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts_message_blocked' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt, #lmtttmpts_message_blacklisted' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt_area, #lmtttmpts_message_blacklisted_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blocked_area' ).removeClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts_message_blacklisted' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt, #lmtttmpts_message_blocked' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_message_invalid_attempt_area, #lmtttmpts_message_blocked_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_message_blacklisted_area' ).removeClass( 'lmtttmpts_hidden' );
		});

		/* click on email subject and messages tabs */
		$( '#lmtttmpts_email_blocked' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_blacklisted' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_subject_blacklisted_area, #lmtttmpts_email_blacklisted_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_email_subject_area, #lmtttmpts_email_blocked_area' ).removeClass( 'lmtttmpts_hidden' );
		});
		$( '#lmtttmpts_email_blacklisted' ).click( function() {
			$( this ).addClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_blocked' ).removeClass( 'nav-tab-active' );
			$( '#lmtttmpts_email_subject_area, #lmtttmpts_email_blocked_area' ).addClass( 'lmtttmpts_hidden' );
			$( '#lmtttmpts_email_subject_blacklisted_area, #lmtttmpts_email_blacklisted_area' ).removeClass( 'lmtttmpts_hidden' );
		});

		$( '#lmtttmpts_notify_email_options' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.lmtttmpts_notify_email_block' ).removeClass( 'lmtttmpts_hidden' );
				$( this ).closest( 'td' ).css( "width", "15px" );
			} else {
				$( '.lmtttmpts_notify_email_block' ).addClass( 'lmtttmpts_hidden' );
				$( this ).closest( 'td' ).css( "width", "auto" );
			}
		});

		$( 'select[name="lmtttmpts_user_email_address"]' ).on( 'focus', function() {
			document.getElementById( 'lmtttmpts_user_mailto' ).checked = true;
		});

		$( 'input[name="lmtttmpts_email_address"]' ).on( 'focus', function() {
			document.getElementById( 'lmtttmpts_custom_mailto' ).checked = true;
		});

		/* prevent form submit but get defaut text into form textarea */
		$( 'button[name="lmtttmpts_return_default"]' ).click( function( event ) {
			var restoreMessage = $( this ).val();
			$.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					action: 				'lmtttmpts_restore_default_message',
					message_option_name: 	restoreMessage,
					'lmtttmpts_nonce': 		lmtttmptsScriptVars.lmtttmpts_ajax_nonce,
				},
				success: function ( result ) {
					var data = $.parseJSON( result );
					/* remove blocks not neccessary elements */
					$( '.lmtttmpts-restore-default-message' ).remove();
					name = 'lmtttmpts_' + restoreMessage;
					$( '.updated, .error' ).hide();
					$( '#lmtttmpts_settings' ).find( 'textarea[name="' + name + '"]' ).val( data['restored_message_text'] );
					$( '.nav-tab-wrapper:first' ).after( data['admin_notice_message'] );
				},
				error: function( request, status, error ) {
					alert( error + request.status );
					errors == 0;
				}
			});
			event.preventDefault();
			return false;
		});

		$( 'input[name="lmtttmpts_add_to_whitelist_my_ip"]' ).click( function() {
			if ( $( this ).is( ':checked' ) )
				$( 'input[name="lmtttmpts_add_to_whitelist"]' ).val( $( 'input[name="lmtttmpts_add_to_whitelist_my_ip_value"]' ).val() ).attr( 'readonly', 'readonly' );
			else
				$( 'input[name="lmtttmpts_add_to_whitelist"]' ).val( '' ).removeAttr( 'readonly' );
		});

		$( '.bws_help_box_first' ).mouseenter( function() {
			$( '.bws_help_box_second' ).hide();
		}).mouseleave( function() {
			$( '.bws_help_box_second' ).show();
		});
	});
} )(jQuery);