jQuery( document ).ready( function($){

	var tiptip_args = {
		'attribute': 'data-tip',
		'fadeIn': 50,
		'fadeOut': 50,
		'delay': 200
	};

	$( '.tips, .help_tip, .woocommerce-help-tip' ).tipTip( tiptip_args );

	// Add tiptip to parent element for widefat tables
	$( '.parent-tips' ).each( function() {
		$( this ).closest( 'a, th' ).attr( 'data-tip', $( this ).data( 'tip' ) ).tipTip( tiptip_args ).css( 'cursor', 'help' );
	});

  checkDisclaimerVisibility();
  $('#wp99234_display_legal_drinking_disclaimer').change(function () {
    checkDisclaimerVisibility();
  });
});

var disclaimer_fields = [
  'wp99234_legal_disclaimer_text',
  {'select': 'wp99234_legal_require_dob'},
  {'select': 'troly_require_dob'},
  'wp99234_legal_age_error_text',
  'troly_forms_layout'
];

/**
 * Check disclaimer option and enable or disable related fields
 */
function checkDisclaimerVisibility() {
  var disclaimer = jQuery('#wp99234_display_legal_drinking_disclaimer option:selected').val() !== 'no';
  jQuery(disclaimer_fields).each(function(index, field) {
    if (typeof field !== 'object') {
      jQuery('#' + field).prop('readonly', !disclaimer);
    } else {
      jQuery('#' + field.select).prop('disabled', !disclaimer);
    }
  });
}

jQuery( function( $ ) {
	if ( $( '.troly-wc-select') && $( '.troly-wc-select').length > 0 ) {
		$( '.troly-wc-select').select2( {
			// Hide the search bar.
			minimumResultsForSearch: -1
		} )
		$( '.troly-wc-select--has-search').select2()
	}
} )