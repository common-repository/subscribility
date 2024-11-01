jQuery( document ).ready( function($){

    $( '.selected_membership_radio' ).css( {
        'position' : 'absolute',
        'top' : 0,
        'left' : '-1000px'
    });

    //@TODO - Optimise this
    $( '.wp99234-selected_membership' ).on( 'click', function(e){
        e.preventDefault();

        $( '.wp99234-selected_membership' ).each( function(){
            $( this ).text( $( this ).data( 'original_text' ) );
        });

        $( this ).prev( 'input' ).click();

        $( '.wp99234-membership_option.selected' ).removeClass( 'selected' );
        $( '.wp99234-membership_option.inactive' ).removeClass( 'inactive' );

        $( this ).parent( '.wp99234-membership_option' ).addClass( 'selected' );

        $( '.wp99234-membership_option' ).not( $( this ).parent( '.wp99234-membership_option' ) ).addClass( 'inactive' );

        var button = $( this );

        button.text( $( this ).data( 'selected_text' ) );// = 'TEST';

        // enable membeship variations dropdown
        var membership_option_id = $(this).attr('data-membership_option_id');
        // Initialise by hiding all variation dropdowns and cleaning up variation dropdown name attribute
        $('select.membership_variation').addClass('hide');
        $('select.membership_variation').attr('name', '');
        // Show selected variation dropdown for variation membership only
        $('select#membership_option_' + membership_option_id).removeClass('hide');
        // Set selected variation attribute name
        $('select#membership_option_' + membership_option_id).attr('name', 'variation_id');
    });

	$('body').on('load', function () {

        //Hide the CC form if required
        $('#hidden_cc_form').hide();
        $("#hidden_cc_form :input").removeAttr('required');

        $('input[name=cc_exp]').payment('formatCardExpiry');
        $('input[name=cc_number]').payment('formatCardNumber');

        $('input[name=cc_number]' ).on( 'blur', function(){
            if( ! jQuery.payment.validateCardNumber( $( this ).val() ) ){
                $( this ).addClass( 'invalid' )
            } else {
                $( this ).removeClass( 'invalid' )
            }
        });

    } )

    $( 'a.subs-toggle-member-benefits' ).click(function(e) {
        e.preventDefault(); el = $( this );
        el.parent().next( 'div.wp99234-membership_benefits' ).show(0, function(e) {
          el.remove();
        });
    })
	$('#confirm_password').on('keyup', function () {
		if ($(this).val() == $('#password').val()) {
			$("#member_submit").prop("disabled", false);
			$('#message').html('');
		} else {
			$('#message').html('Password not matching').css('color', 'red');
			$("#member_submit").prop("disabled", true);
		}

	});
    // Use this whever we can
    // For checkout page, this code is called from checkout.js
    // But has to use the specific WooCommerce mechanism
    // As our event listeners are destroyed
    $("#wp99234_use_existing_card").change(function () {
        if ($(this).is(':checked')) {
            $('#hidden_cc_form').hide();
            $("#hidden_cc_form :input").removeAttr('required');
        } else {
            $('#hidden_cc_form').show();
            $("#hidden_cc_form :input").attr('required', '1');
        }
	  });

	/**
	 * Hide shipping calculator, shipping form if local pickup is pre-selected.
	 */
	if ( $( 'input[type=radio].shipping_method:checked' ).length > 0 && $( 'input[type=radio].shipping_method:checked' ).val().indexOf( 'local_pickup' ) >= 0 ) {
		$( '.woocommerce-shipping-destination, .woocommerce-shipping-calculator, .woocommerce-shipping-fields, #order_comments_field' ).hide()
	}

	/**
	 * Hide shipping calculator, shipping form if local pickup is selected.
	 */
	$( 'body' ).on( 'click', 'input[type=radio].shipping_method', function() {
		const method = $(this).val()

		// Only trigger it once all the AJAX has stopped.
		$( document ).ajaxStop( function() {
			if ( method.indexOf( 'local_pickup' ) >= 0 ) {
				$( '.woocommerce-shipping-destination, .woocommerce-shipping-calculator, .woocommerce-shipping-fields, #order_comments_field' ).hide()
			} else {
				$( '.woocommerce-shipping-destination, .woocommerce-shipping-calculator, .woocommerce-shipping-fields, #order_comments_field' ).show()
			}
		});
	})
    $('#wp99234-registration-form-container #wp99234_member_registration_form').hide();
	$('#wp99234-registration-form-container #wp99234_troly_club_options').hide();

	/**
	 * Gift Orders Logic
	 */
	// Toggle gift order options visibility if checkbox value is pre-selected.
	$( document ).ajaxStop( function() {
		if ( $( '#troly_gift_order' ).is( ':checked' ) ) {
			$( '#wp99234-gift-order-message div, #troly-gift-order-wrapping' ).show()
		} else {
			$( '#wp99234-gift-order-message div, #troly-gift-order-wrapping' ).hide()
		}
	})

	// Toggle visibility for gift order options.
	$('body').on( 'change', '#troly_gift_order', function() {
		let addGiftWrapFees = false

		if ( $(this).is( ':checked' ) ) {
			addGiftWrapFees = true;

			$( '#wp99234-gift-order-message div' ).slideToggle( 'fast' ).show()
			$( '#troly-gift-order-wrapping' ).show()
		} else {
			$( '#wp99234-gift-order-message div' ).slideToggle( 'fast' ).hide()
			$( '#troly_gift_order_wrap' ).is( ':checked' ) ? $( '#troly_gift_order_wrap' ).trigger( 'click' ) : false;
			$( '#troly-gift-order-wrapping' ).hide()
		}

		$.ajax( {
			url: woocommerce_params.ajax_url,
			type: 'POST',
			data: {
				action: 'addGiftOrderMessage',
				makeGift: addGiftWrapFees,
				giftMsg: $( '#troly_gift_order_message' ).val()
			},
			success: function( resp ) {
				$( document.body ).trigger( 'update_checkout' )
			}
		} )
	} )

	// Trigger the "gift wrap" fees.
	$( 'body' ).on( 'change', '#troly_gift_order_wrap', function() {
		let addGiftWrapFees = false

		if ( $( this ).is( ':checked' ) ) {
			addGiftWrapFees = true
		}

		$.ajax( {
			url: woocommerce_params.ajax_url,
			type: 'POST',
			data: {
				action: 'triggerGiftWrapFees',
				addFees: addGiftWrapFees
			},
			success: function( resp ) {
				$( document.body ).trigger( 'update_checkout' )
			}
		} )
	} )

	// Save gift message in session.
	$( 'body' ).on( 'blur', '#troly_gift_order_message',function() {
		let addGiftWrapFees = false

		if ( $( '#troly_gift_order' ).is( ':checked' ) ) {
			addGiftWrapFees = true
		}

		$.ajax( {
			url: woocommerce_params.ajax_url,
			type: 'POST',
			data: {
				action: 'addGiftOrderMessage',
				makeGift: addGiftWrapFees,
				giftMsg: $(this).val()
			}
		} )
	} )
});
