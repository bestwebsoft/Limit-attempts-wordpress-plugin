( function( $ ) {
	$( document ).ready( function() {

		// for Contact Form option
		$( '#restrict-sending-emails' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.contact-form-checked' ).show();
			} else {
				$( '.contact-form-checked' ).hide();
			}
		} ).trigger('change');

		$( 'input[name="lmtttmpts_notify_email"]' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.lmtttmpts_email_notifications' ).show();
			} else {
				$( '.lmtttmpts_email_notifications' ).hide();
			}
		} ).trigger('change');

		/* hide zero values */
		$( '.lmtttmpts-zero-value' ).addClass( 'lmtttmpts_hidden' );
		/* hide "block/add to denylist" time options at the page load */
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
		$( '#lmtttmpts-time-interval-for-cntctfrm-edit' ).click( function() {
			$( '#lmtttmpts-time-interval-for-cntctfrm-display, #lmtttmpts-time-interval-for-cntctfrm' ).toggleClass( 'lmtttmpts_hidden' );
		} );

		/* write zero if input empty */
		$( '[type = number]' ).on( 'change',function () {
			var $this = $(this);
			if( '' == $this.val() ){
				$this.val( 0 );
			}
		});

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
			minutesToResetBlock = $( '#lmtttmpts-minutes-to-reset-block-display' ).val(),
		/* time-interval-for-cntctfrm */
		daysTimeIntervalCntctfrm = $( '#lmtttmpts-days-time-interval-for-cntctfrm-display' ).val(),
			hoursTimeIntervalCntctfrm = $( '#lmtttmpts-hours-time-interval-for-cntctfrm-display' ).val(),
			minutesTimeIntervalCntctfrm = $( '#lmtttmpts-minutes-time-interval-for-cntctfrm-display' ).val(),
			secondsTimeIntervalCntctfrm = $( '#lmtttmpts-seconds-time-interval-for-cntctfrm-display' ).val();
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
			if ( ! $( event.target ).closest( '#lmtttmpts-time-interval-for-cntctfrm-edit, #lmtttmpts-time-interval-for-cntctfrm' ).length &&
				daysTimeIntervalCntctfrm == $( '#lmtttmpts-days-time-interval-for-cntctfrm-display' ).val() &&
				hoursTimeIntervalCntctfrm == $( '#lmtttmpts-hours-time-interval-for-cntctfrm-display' ).val() &&
				minutesTimeIntervalCntctfrm == $( '#lmtttmpts-minutes-time-interval-for-cntctfrm-display' ).val() &&
				secondsTimeIntervalCntctfrm == $( '#lmtttmpts-seconds-time-interval-for-cntctfrm-display' ).val() ) {
				$( '#lmtttmpts-time-interval-for-cntctfrm-display' ).removeClass( 'lmtttmpts_hidden' );
				$( '#lmtttmpts-time-interval-for-cntctfrm' ).addClass( 'lmtttmpts_hidden' );
			};
			event.stopPropagation();
		});

		$( 'select[name="lmtttmpts_user_email_address"]' ).on( 'focus', function() {
			$( '#lmtttmpts_user_mailto'  ).attr( 'checked', 'checked' );
		});
		$( 'input[name="lmtttmpts_email_address"]' ).on( 'focus', function() {
			$( '#lmtttmpts_custom_mailto' ).attr( 'checked', 'checked' );
		});

		/* prevent form submit but get defaut text into form textarea */
		$( 'button[name="lmtttmpts_return_default"]' ).click( function( event ) {
			var restore_type = $( this ).val();
			$.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					action: 				'lmtttmpts_restore_default_message',
					message_option_name: 	restore_type,
					lmtttmpts_nonce: 		lmtttmptsScriptVars.lmtttmpts_ajax_nonce,
				},
				success: function ( result ) {
					var data = $.parseJSON( result );
					/* add notice */
					$( '.lmtttmpts-restore-default-message' ).remove();
					$( '.updated, .error' ).hide();
					$( '#bws_save_settings_notice' ).after( data['admin_notice_message'] );

					$.each( data['restored_messages'],  function( key, val ) {
						name = 'lmtttmpts_' + key;
						$( 'textarea[name="' + name + '"]' ).val( val );
					} );
				}
			});
			event.preventDefault();
			return false;
		});

		$( 'input[name="lmtttmpts_add_to_allowlist_my_ip"]' ).click( function() {
			if ( $( this ).is( ':checked' ) )
				$( 'input[name="lmtttmpts_add_to_allowlist"]' ).val( $( 'input[name="lmtttmpts_add_to_allowlist_my_ip_value"]' ).val() ).attr( 'readonly', 'readonly' );
			else
				$( 'input[name="lmtttmpts_add_to_allowlist"]' ).val( '' ).removeAttr( 'readonly' );
		});
	});
} )(jQuery);