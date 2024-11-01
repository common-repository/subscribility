/**
 * Created by bcasey on 26/03/15.
 */
jQuery(document).ready(function($) {
  // Hide the CC form if required
  $('#hidden_cc_form').hide();
  var cc_details = {};
  $(document.body).on('updated_checkout', function() {
    $("#wp99234_use_existing_card").change(function() {
      if ($(this).is(':checked')) {
        $('#hidden_cc_form').hide();
        $("#hidden_cc_form :input").removeAttr('required');
      } else {
        $('#hidden_cc_form').show();
        $("#hidden_cc_form :input").attr('required', '1');
      }
    });

    // Watch for changes in payment method
    // This will prevent mistaken submission of CC details if not required
    $('input[name="payment_method"]').change(function() {
      var payment_method = $(this).val();
      if (payment_method === 'wp99234_payment_gateway') {
        if (!jQuery.isEmptyObject(cc_details)) {
          // Restore CC details
          Object.keys(cc_details).forEach(function(key) {
            $('#' + key).val(cc_details[key]);
          });
        }
      } else {
        $("#wc-wp99234_payment_gateway-cc-form :input").each(function(i, elem) {
          var value = $(elem).val();
          if (value !== "") {
            // Save temporarily the CC details
            cc_details[$(elem).attr('id')] = value;

            // Set as empty field to prevent mistaken payload if payment method is not 'wp99234_payment_gateway'
            $(elem).val('');
          }
        })
      }
    });
  });
});
