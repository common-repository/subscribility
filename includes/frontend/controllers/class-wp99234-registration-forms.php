<?php
/**
 * Wrapper class to handle the registration form
 */

class WP99234_Registration_Forms extends WP99234_Forms {

    var $submit_name = 'registration_submit';

    var $nonce_name = '_wp99234_registration_nonce';

    var $nonce_action = 'wp99234_handle_registration';

    var $template = 'registration_form.php';

    var $errors = array();

    function __construct(){
		parent::__construct();
	}

    /**
     * Handle the form submission.
     *
     * @return bool
     */
    function handle_submit(){

        if( ! isset( $_POST[$this->nonce_name] ) ){
            return false;
        }

        if( ! wp_verify_nonce( $_POST[$this->nonce_name], $this->nonce_action ) ){
            return false;
        }

        $fields = array(
            'first_name' => array(
                'required' => __( 'Please enter your first name', 'wp99234' ),
            ),
            'last_name' => array(
                'required' => __( 'Please enter your last name', 'wp99234' ),
            ),
            'reg_email' => array(
                //'email_exists' => __( 'Your email address is already registered. Please login.', 'wp99234' ),
                'is_email'     => __( 'Please enter a valid email address.', 'wp99234' ),
                'required' => __( 'Please enter your email address', 'wp99234' ),
            ),
            'phone' => array(),
            'mobile' => array(),
            'company_name' => array(),
            'shipping_address_1' => array(
                'required' => __( 'Please enter your Shipping address.', 'wp99234' ),
            ),
            'shipping_suburb' => array(
                'required' => __( 'Please enter your shipping suburb.', 'wp99234' ),
            ),
            'shipping_postcode' => array(
                'required'   => __( 'Please enter your shipping postcode', 'wp99234' ),
                'numeric' => __( 'Please enter a valid postcode.', 'wp99234' ),
            ),
            'shipping_state' => array(
                'required' => __( 'Please enter a valid state.', 'wp99234' ),
            ),
            'shipping_country' => array(
                'required' => __( 'Please enter a valid country.', 'wp99234' ),
            ),
            'use_existing_card' => array(),
            'selected_membership' => array(
                'required'   => __( 'Please select a membership option', 'wp99234' ),
                'is_numeric' => __( 'Invalid membership option.', 'wp99234' )
            ),
            'variation_id' => array(), // membership variation id
            'shipping_instructions' => array(),
            'subs_birthday' => array(),
            'tag_ids' => array()
        );

		$requireDOB = get_option( 'troly_require_dob' );
		$isRequiredDOB = $requireDOB === 'membership' || $requireDOB === 'both' ? true : false;

		if ( $isRequiredDOB ) {
			$fields['subs_birthday'] = [
				'required'   => true,
            ];
        }

        if(!is_user_logged_in()){
            $fields['user_pass'] = array(
                'required' => __( 'Please enter password', 'wp99234' ),
            );
            $fields['conf_pass'] = array(
                'required' => __( 'Please confirm your password', 'wp99234' ),
            );
        }

        //CC Data if the user wants to update it or doesn't have an existing one.
        if ( !isset( $_POST['wp99234_use_existing_card'] ) || $_POST['wp99234_use_existing_card'] !== 'yes' ) {
            $fields['cc_name'] = array(
                'required' => __( 'Please enter the name on your card', 'wp99234' ),
            );
            $fields['cc_number'] = array(
                'required' => __( 'Please enter your credit card number.', 'wp99234' ),
            );
            $fields['cc_expiry'] = array(
                'required' => __( 'Please enter your card expiry date.', 'wp99234' ),
                'contains' => array(
					'check_val' => '/',
					'length' => '9', // We are using 9 chars since we are also counting the spaces and the '/'.
					'error_msg' => __( 'Incorrect format for credit card expiry date. Please enter the expiry date in the format MM/YYYY.', 'wp99234' ),
                ),
            );

            // Validate only when field exist in the payload
            if (isset($_POST['cc_cvv'])) {
                $fields['cc_cvv'] = array(
                    'required' => __( 'Please enter your card code.', 'wp99234' ),
                );
            }
        }

        $data = array();

        foreach( $fields as $key => $validation ){

            $value = ( isset( $_POST[$key] ) ) ? sanitize_text_field( $_POST[$key] ) : '' ;

            if( ! empty( $validation ) ){
                $value = $this->validate_field( $key, $value, $validation );
            }

            $data[$key] = $value;

        }

        if (empty($_POST['phone']) && empty($_POST['mobile'])) {
            wc_add_notice( __( 'Please enter a phone or mobile number.', 'wp99234' ), 'error' );
            return false;
        }

        if (is_user_logged_in()) {
            if( $this->user_is_registered_for_membership( get_current_user_id(), $data['selected_membership'] ) ){
                wc_add_notice( __( 'You are already registered for that membership. Please contact us if you have any issues.', 'wp99234' ), 'error' );
                return false;
            }
        }

        /* If we have enabled a birthday, let's validate it
            Bail if we have an issue! */
        $subs_birthday = null;
        if ( $isRequiredDOB ) {
          if(!verifyBirthday($_POST, true)){
            return false;
          } else {
            $subs_birthday = DateTime::createFromFormat(get_option('date_format'), $data[ 'subs_birthday' ]);
            if($subs_birthday != false)
              $subs_birthday = $subs_birthday->format('j M Y');
          }
        }

        $membership_obj = new StdClass();
        $membership_obj->membership_type_id = $data['selected_membership'];
        // membership variaion id
        $membership_obj->variation_id = $data['variation_id'];


        /*
            If we have a variation-enabled membership, lets make sure we have correctly set it
        */
        $membership_options = get_option( 'wp99234_company_membership_types' );
        foreach($membership_options as $mt){
            if($mt->id == $membership_obj->membership_type_id){
                if($membership_obj->variation_id == '' && isset($mt->is_variation_membership) && $mt->is_variation_membership ){
                    wc_add_notice( __( 'Please choose a membership variation to continue.', 'wp99234' ), 'error' );
                }
            }
        }

        //If we have errors, GTFO
        if( ! empty( $this->errors ) ){
            return false;
        }

        if(!is_user_logged_in()){
            $password= wp_hash_password($data[ 'user_pass' ]);
        }else{
            global $current_user;
            $current_user = wp_get_current_user();
            $password= $current_user->user_pass;
        }

        $post_data = array(
            'customer' => array(
                'fname'                  => $data[ 'first_name' ],
                'lname'                  => $data[ 'last_name' ],
                'email'                  => $data[ 'reg_email' ],
                'phone'                  => $data[ 'phone' ],
                'mobile'                 => $data[ 'mobile' ],
                'birthday'               => $subs_birthday,
                'password'               => $password,
                'company_name'           => $data[ 'company_name' ],
                'delivery_address'       => $data[ 'shipping_address_1' ],
                'delivery_suburb'        => $data[ 'shipping_suburb' ],
                'delivery_postcode'      => $data[ 'shipping_postcode' ],
                'delivery_state'         => $data[ 'shipping_state' ],
                'delivery_country'       => WP99234()->_api->get_formatted_country_name( $data[ 'shipping_country' ] ),
                'delivery_instructions'  => $data[ 'shipping_instructions' ],
                'memberships_attributes' => array(
                    0 => $membership_obj
                )
            )
        );

        if ( !isset( $_POST['wp99234_use_existing_card'] ) || $_POST['wp99234_use_existing_card'] !== 'yes' ) {

            $exp_array = explode( '/', str_replace( ' ', '', wp_kses( $data['cc_expiry'], array() ) ) );

            $exp_month   = $exp_array[0];
            $exp_year    = $exp_array[1];

            if (!is_numeric($exp_month) || !is_numeric($exp_year)) {
              wc_add_notice( __( 'Incorrect format for credit card expiry date. Please enter the expiry date in the format MM/YY.', 'wp99234' ), 'error' );
              return false;
            }

            $post_data['customer']['cc_name']      = $data['cc_name'];
            $post_data['customer']['cc_number']    = $data['cc_number'];
            $post_data['customer']['cc_exp_month'] = $exp_month;
            $post_data['customer']['cc_exp_year']  = $exp_year;
            $post_data['customer']['cc_cvv']       = $data['cc_cvv'];
        }

        if (isset($_POST['tag_ids'])) {
            $tag_ids = explode( ',', $data['tag_ids'] );
            $post_data['customer']['tag_ids'] = $tag_ids;

            $customer_tags = get_option('troly_customer_tags');
            foreach ($customer_tags as $tag) {
                if (in_array($tag->id, $tag_ids)) {
                    $post_data['customer']['customer_tags'][] = $tag;
                }
            }
        }

        $user_id = false;
        $subs_id = false;
        $method = 'POST';

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        } else {
            $user_id = email_exists( $data['reg_email'] );
        }

        if ($user_id) {

            //Mark the user as updating if they are logged in (already a member ).
            $subs_id = get_user_meta( $user_id, 'subs_id', true );

            if ($subs_id) {
                $post_data['customer']['id'] = $subs_id;
                $method = 'PUT';
            }
        }

        if (!$subs_id) {
            // registration form forces membership so we can safely add them to all notifications
            $post_data['customer']['notify_newsletters'] = '@|mail';
            $post_data['customer']['notify_shipments'] = '@|mail';
            $post_data['customer']['notify_payments'] = '@|mail';
        }

        $endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $subs_id );
		$results = WP99234()->_api->_call( $endpoint, $post_data, $method );

        //If they are a new user, import them from the SUBS data.
        if ( $results && isset($results->id) ) {

            $errors = (array)$results->errors;

            if ( !empty($errors) ) {
                wc_add_notice( 'Your registration could not be processed, Please contact us if you wish to proceed.', 'error' );
                return false;
            }

            //Always import the user so that the membership data is saved, address is validated and saved as their delivery address even if they already exist..
            $userId = WP99234()->_users->import_user($results,$data['user_pass']);

			if ( get_option( 'troly_club_membership_signup' ) === 'future_club_purchase_only' ) {
				$this->setNewClubSignupSession();
			}

			if ( isset($_POST) && isset($data[ 'user_pass' ]) ) {
                if ($userId) {
                    wp_set_current_user($userId);
                    wp_set_auth_cookie($userId);
                }
                // #FIXME: We used to redirect to this page and append a question mark (?)
                // Probably to avoid refreshing from posting the data again.
                // This prevents Woocommerce notices from being displayed, so was removed.
                // wp_redirect("");
			}

            if ( isset($_POST) && isset($_POST['tag_ids']) ) {
                update_user_meta($user_id, 'tag_ids', $data['tag_ids']);
			}

			if ( isset( $_GET['troly_redirect'] ) ) {
				if ( $_GET['troly_redirect'] === 'cart' ) {
                    wc_add_notice( 'Thank you for joining our club. Your membership benefits have been applied to this cart.', 'success' );
					wp_redirect( wc_get_cart_url() );
					exit;
				}
				elseif ( $_GET['troly_redirect'] === 'checkout' ) {
                    wc_add_notice( 'Thank you for joining our club. Your membership benefits have been applied to this order.', 'success' );
					wp_redirect( wc_get_checkout_url() );
					exit;
				}
				elseif ( $_GET['troly_redirect'] === 'product' && ! empty( $_GET['pid'] ) ) {
                    wc_add_notice( 'Thank you for joining our club!', 'success' );
					wp_redirect( get_permalink( $_GET['pid'] ) );
					exit;
				}
			} else {
                /* They are signing up on the page directly. */
                wc_add_notice( 'Thank you for joining our club!', 'success' );
            }
        } else {
            /* Troly may have reported errors. Lets attempt to break these down and let the
            user know that they need to amend their details */
            $field_mappings = [
                "cc_exp_month" => "Card expiry month",
                "cc_exp_year" => "Card expiry year",
                "cc_number" => "Card number",
                "cc_name" => "Card name",
                "cc_exp_date" => "Card expiry date"
            ];
            if(isset($results->errors)){
                wc_add_notice( 'Some details could not be accepted. Please review them and try again', 'error' );
                foreach($results->errors as $field => $elist){
                    if(strpos($field, '.') > -1) continue;
                    foreach($elist as $erroritem){
                        wc_add_notice( $field_mappings[$field] . " $erroritem", 'error');
                    }
                }
            } else {
                wc_add_notice( 'An unknown error has occurred. Please try again.', 'error' );
            }
        }
	}

	public function setNewClubSignupSession()
	{
		// Initialize session for early access.
		if ( ! headers_sent() && '' == session_id() ) {
			@ob_start();
			session_start();
		}

		$_SESSION['troly_new_club_signup'] = true;
	}
}
