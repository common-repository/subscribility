<?php
/**
 * Class WP99234_Users
 *
 * Class to handle the user synchronisation between Wordpress and Troly
 *
 * @package wp99234
 */
class WP99234_Users {

  var $users_create_endpoint;

  var $users_endpoint;

    var $current_user_update_endpoint = false;

    function __construct(){

        $this->setup_hooks();

    }

    function setup_hooks(){

        add_action( 'init', array( $this, 'on_init' ) );

        // add_action( 'profile_update', array( $this, 'export_user' ), 10, 2 );
		// add_action( 'user_register' , array( $this, 'export_user' ), 10, 1 );

		if ( 'none' !== get_option( 'wp99234_customer_sync', 'both' ) ) {
			// Action to Sync Customers data to Troly from My Account > Billing Address or Shipping Address page
			add_action( 'woocommerce_customer_save_address', array( $this, 'export_user' ), 10, 2 );

			/**
			 * Action to Sync Customer data to Troly from My Account > Account details page
			 * And Customer can be update their CC Details if given.
			 */
        	add_action( 'woocommerce_save_account_details', array( $this, 'export_user' ), 10, 1 );
		}
        // add_action( 'show_user_profile', array( $this, 'display_extra_profile_fields' ) );
        // add_action( 'edit_user_profile', array( $this, 'display_extra_profile_fields' ) );

        add_filter( 'woocommerce_customer_meta_fields', array( $this, 'filter_customer_meta_fields' ) );

        add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_checkout_order_processed' ), 10, 2 );

        add_action( 'wp_login', array( $this, 'on_login' ), 10, 2 );

        //Import users via admin-ajax.
        add_action( 'wp_ajax_subs_import_users', array( $this, 'on_ajax_subs_import_users' ) );

        //Export users via admin-ajax.
        add_action( 'wp_ajax_subs_export_users', array( $this, 'on_ajax_subs_export_users' ) );

        //authenticate
        //add_action( 'authenticate', array( $this, 'handle_authentication' ), 50, 3 );

        // Retrieve User's password reset token and push to Troly to be added in MailChimp
		add_action( 'retrieve_password_key', array($this, 'on_retrieve_password_key'), 10, 2);
		add_action( 'wp99234_cron_export_users', [$this, 'cron_export_users_to_subs'] );
    }

    function on_init(){

        $this->users_create_endpoint = WP99234_Api::$endpoint . 'customers';

        $this->users_endpoint = WP99234_Api::$endpoint . 'customers.json';

        //If we are logged in, get us the endpoint to update the user.
        if( is_user_logged_in() ){

            $user_subs_id = get_user_meta( get_current_user_id(), 'subs_id', true );

            if( $user_subs_id ){
                $this->current_user_update_endpoint = WP99234_Api::$endpoint . sprintf( 'customers/%s.json', $user_subs_id );
            }

        }

    }

    /**
     * Get Troly membership for current user
     * @since 2.9
     * @package Troly
     *
     * @return object
     */
    public function get_current_membership()
    {
        $user_id = get_current_user_id();
        if ($user_id === 0) return false;

        $membership = get_user_meta($user_id, 'current_memberships', true);
        if (empty($membership) || !is_array($membership)) return false;

        return array_pop($membership);
    }

    /**
     * Import a customer into wordpress.
     *
     * @param $user_data
     *
     * @return int|WP_Error
     */
    function import_user( $user_data ,$pass=''){

        if( ! defined( 'WP99234_DOING_SUBS_USER_IMPORT' ) ){
            define( 'WP99234_DOING_SUBS_USER_IMPORT', true );
        }

        $_user_data = array(
            'first_name'           => $user_data->fname,
            'last_name'            => $user_data->lname,
            'user_email'           => $user_data->email,
            'user_login'           => $user_data->email,
            'show_admin_bar_front' => false
        );

        $is_update = false;

        $user_id = false;
        $user_role = false;

        //Look first by SUBS ID
        $user = $this->get_user_by_subs_id( $user_data->id );

        if( $user ){
            $user_id = $user->ID;
            $user_role = $user->roles[0];
        }

        //Else the email
        if( ! $user_id ){
            $user_id = email_exists( $user_data->email );
        }

        //Else the username.
        if( ! $user_id ){
            $user_id = username_exists( $user_data->email );

            //Why would 2 similar function return a different error result??
            if( $user_id === null ){
                $user_id = false;
            }
        }

        //If we have no user, generate a password and ensure they are a customer.
        if( $user_id === false ){
            //$pass = wp_generate_password();
            $_user_data['user_pass'] = $pass;
            $_user_data['role'] = $user_role ? $user_role : 'customer';
        } else {
            //Tag the user id in WP so that user will be updated instead of creating a new one.
            $_user_data['ID'] = $user_id; //Update the user.
            $pass = false;
            $is_update = true;
        }

        if( $is_update ){
            $user_id = wp_update_user( $_user_data );
        } else {
            $user_id = wp_insert_user( $_user_data );
        }

        if( is_wp_error( $user_id ) ){
            return $user_id;
        }

        /**
         * Ensure that all the users current memberships are in the current companies memberships.
         *
         * Also make the user memberships data an associative array so processing and searching later becomes much easier.
         */
        if( isset( $user_data->current_memberships ) && is_array( $user_data->current_memberships ) ){

            //@TODO - store this in an object somewhere so we can avoid unnecessary DB lookups.
            $current_company_memberships = get_option( 'wp99234_company_membership_types' );

            $current_memberships_raw = $user_data->current_memberships;

            $current_memberships = array();

            /**
             * Cycle through the memberships for the user from SUBS, ensure that the membership is in the current company memberships.
             * If it is, add it to the memberships array with the ID as the array key.
             */
            foreach( $current_memberships_raw as $current_membership_raw ){

                if( isset( $current_company_memberships[$current_membership_raw->membership_type_id] ) ){
                    $current_memberships[$current_membership_raw->membership_type_id] = $current_membership_raw;
                }

            }

            //Set the memberships to the data to be saved as meta.
            $user_data->current_memberships = $current_memberships;

        }

        // Iterate through the data map and insert all mapped meta
        foreach( $this->user_meta_map() as $key => $field ){

            if(strpos( $key, 'country') > 0){
              $val = WP99234()->_api->get_formatted_country_code( $user_data->{$field} );
            } elseif($key == 'birthday' && !empty($user_data->{$field})){
              $val = DateTime::createFromFormat('j M Y', $user_data->{$field});
              if($val != false){
                $val = $val->format(get_option('date_format'));
              } else {
                $val = null;
              }
            } else {
                if( isset( $user_data->{$field} ) ){
                    $val = $user_data->{$field};
                } else {
                    $val = '';
                }
            }

            update_user_meta( $user_id, $key, $val );

    }

      // Add metas that are required for wc and are not present in subs data
//	    update_user_meta( $user_id, 'billing_country', 'AU' );
//	    update_user_meta( $user_id, 'shipping_country', 'AU' );

        // Add the subs_id to the user meta
        update_user_meta( $user_id, 'subs_id', $user_data->id );

        // Add the last time this user was updated by subs to user meta
        update_user_meta( $user_id, 'last_updated_by_subs', date('d/m/Y g:i A') );

        //Handle address logic
        if ($user_data->same_billing == true) {
            $customer = new WC_Customer($user_id);

            $country = WP99234()->_api->get_formatted_country_code($user_data->delivery_country);
            $customer->set_billing_location(
                $country,
                $user_data->delivery_state,
                $user_data->delivery_postcode,
                $user_data->delivery_suburb
            );
            $customer->set_billing_address($user_data->delivery_address);

            $customer->save();
        }

        // flag whether or not the user has CC details stored in SUBS.
        if( isset( $user_data->cc_number ) && strpos( $user_data->cc_number, '#' ) !== false ){
            update_user_meta( $user_id, 'has_subs_cc_data', true );
        }

        return $user_id;

    }

    /**
     * Updates a customers data in wordpress.
     *
     * @param $user_id
     * @param $user_data
     */
    function update_customer_metadata( $user_id, $user_data ){

        foreach( $this->user_meta_map() as $key => $field ){

            if( is_object( $user_data ) ){
                $value = ( isset( $user_data->{$key} ) ) ? $user_data->{$key} : '';
            } elseif( is_array( $user_data ) ){
                $value = ( isset( $user_data[$key] ) ) ? $user_data[$key] : '';
            }

            update_user_meta( $user_id, $key, $value );

        }

        return $user_id;

    }

    /**
     *
     * @hooked profile_update
     * @param $user_id
     * @param $old_user_data
     */
    function on_user_update( $user_id, $old_user_data ){

        $this->export_user( $user_id, $old_user_data );

    }

//    /**
//     *
//     */
//    function on_user_create(){
//
//    }

    /**
     * Retrieve user's reset token and to provider data in Troly
     * @param  string $user_login
     * @param  string $key
     */
    function on_retrieve_password_key( $user_login, $key ) {
        $user = get_user_by( 'login', $user_login );
        $subs_id = get_user_meta( $user->ID, 'subs_id', true );

        $payload = array(
            'customer' => array(
                'id' => $subs_id
            ),
            'wp_user_id' => $user->ID,
            'wp_pwd_token' => $key
        );

        if ($subs_id) {
            $method = 'PUT';
            $message = 'Updating user (id: ' . $subs_id . ', email: ' . $user->email . ') on Troly';

            $endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $subs_id );
            $results = WP99234()->_api->_call( $endpoint, $payload, 'PUT' );

            if (false === $results) {
                return;
            }

            //Ensure the SUBS ID is recorded
            if ( $results->id && ! $subs_id ) {
                update_user_meta( $user->ID, 'subs_id', $results->id );
            }

            $errors = (array)$results->errors;

            if ( ! empty( $errors ) ) {
                $message .= "\nFailed to update user on Troly because of: {${WP99234()->get_var_dump($errors)}}";
                $reporting_options = get_option('wp99234_reporting_sync');

                if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                    wp99234_log_troly( 0, 1, 0, $message );
                }
            }
        }
    }

    /**
     * Export a user to SUBS.
     *
     * Pass true to the $quiet param to disable admin messages.
     *
     * @param integer $user_id
     * @param null $load_address
     * @param array $override_data
     * @param boolean $quiet
     * @param boolean $assign_new_card
     *
     * @return array|bool|mixed
     */
    public function export_user($user_id, $load_address = null, $override_data = array(), $quiet = false, $assign_new_card = false)
    {
		$user = get_user_by( 'id', $user_id );

        if (!$user) {
            return false;
		}

        // Prevent updating customer for newly created account
        if (defined('WP99234_DONE_USER_EXPORT') && WP99234_DONE_USER_EXPORT) {
            return;
		}

        // If we are checking out and haven't yet reached the order_processed hook, skip this.
        if ((defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT)
             && (!defined('WP99234_ALLOW_USER_EXPORT') || !WP99234_ALLOW_USER_EXPORT)) {
            return;
        }

        //If we are importing users, this is unnecessary.
        if (defined('WP99234_DOING_SUBS_USER_IMPORT') && WP99234_DOING_SUBS_USER_IMPORT) {
            return;
		}

        $subs_id = get_user_meta( $user_id, 'subs_id', true );

        $meta = array(
            'fname'                 => 'billing_first_name',
            'lname'                 => 'billing_last_name',
            'email'                 => 'billing_email',
            'gender'                => 'gender',
            'phone'                 => 'billing_phone',
            'birthday'              => 'birthday',
            'notify_shipments'      => 'notify_shipments',
            'notify_payments'       => 'notify_payments',
            'notify_newsletters'    => 'notify_newsletters',
            'notify_renewals'       => 'notify_renewals',
            'delivery_instructions' => 'delivery_instructions',
            'same_billing'          => 'same_billing',
            'mobile'                => 'mobile',
            'company_name'          => 'billing_company_name',

            'billing_address'      => 'billing_address_1',
            'billing_suburb'       => 'billing_city',
            'billing_postcode'     => 'billing_postcode',
            'billing_state'        => 'billing_state',
            'billing_country'      => 'billing_country',

            'delivery_address'      => 'shipping_address_1',
            'delivery_suburb'       => 'shipping_city',
            'delivery_postcode'     => 'shipping_postcode',
            'delivery_state'        => 'shipping_state',
            'delivery_country'      => 'shipping_country',
        );

        if (defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT) {
            // If we are adding or updating Credit Card, then add CC mapping as override data.
            if (isset($_POST) && $assign_new_card) {
                $meta['cc_name']      = 'cc_name';
                $meta['cc_number']    = 'cc_number';
                $meta['cc_exp_month'] = 'cc_exp_month';
                $meta['cc_exp_year']  = 'cc_exp_year';
                $meta['cc_cvv']       = 'cc_cvv';
            }
        }

        $user_data = array(
            'customer' => array()
        );

        /**
         * Build the user meta data into the customer array.
         */
        foreach( $meta as $key => $meta_field ){

            //Use the override data over the meta-data
            $value = ( isset( $override_data[$meta_field] ) ) ? $override_data[$meta_field] : $user->{$meta_field};

            //We need to validate that gender is in the list of m, f or -
            if ($key == 'gender') {
                if (!$value || empty($value) || !in_array($value, array('m', 'f', '-'))) {
                    $value = '-';
                }
            } elseif ($key == 'birthday' && !empty($value)) {
              $date_value = DateTime::createFromFormat(get_option('date_format'), $value);
              if ($date_value != false) {
                $value = $date_value->format('j M Y'); // This is the format Troly expects
              } else {
                $value = null;
              }
            }

            $user_data['customer'][$key] = $value ;

        }

        // New users must always be setup to for all communications
        if (!$subs_id) {
          $user_data['customer']['notify_shipments'] = '@|mail';
          $user_data['customer']['notify_payments'] = '@|mail';
          $user_data['customer']['notify_newsletters'] = '@|mail';
        }

        /**
         * If Customer want's to update Credit card from Customer details page then add to payload.
         */
        if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) && ! isset( $_POST[ 'wp99234_use_existing_card' ] ) ) {
            $cc_exp = explode('/', str_replace(' ', '', wp_kses($_POST['cc_expiry'], array())));

            if (count($cc_exp) > 1) {
                $cc_number = wp_kses($_POST['cc_number'], array());
                $cc_number = str_replace(' ', '', $cc_number);

                $user_data['customer']['cc_name']      = wp_kses($_POST['cc_name'], array());
                $user_data['customer']['cc_number']    = $cc_number;
                $user_data['customer']['cc_exp_month'] = $cc_exp[0];
                $user_data['customer']['cc_exp_year']  = $cc_exp[1];
                $user_data['customer']['cc_cvv']       = wp_kses($_POST['cc_cvv'], array());
            }
        }

        $shipping_state = $user->shipping_state;//$this->get_formatted_state( $user->shipping_state );
        $billing_state = $user->billing_state;// $this->get_formatted_state( $user->billing_state );

        /**
         * Handle Billing and shipping logic.
         *
         * Also handles edge cases where the user has mixed data, the address doesn't get fuddled.
         */
        if ( ($user->shipping_address_1 && strlen( $user->shipping_address_1 ) > 0) || $load_address == 'shipping' ) {
            $user_data['customer']['delivery_address']  = $user->shipping_address_1;
            $user_data['customer']['delivery_suburb']   = $user->shipping_city;
            $user_data['customer']['delivery_state']    = $shipping_state;
            $user_data['customer']['delivery_postcode'] = $user->shipping_postcode;
            $user_data['customer']['delivery_country']  = WP99234()->_api->get_formatted_country_name( $user->shipping_country );
        } else {
            $user_data['customer']['delivery_address']  = $user->billing_address_1;
            $user_data['customer']['delivery_suburb']   = $user->billing_city;
            $user_data['customer']['delivery_state']    = $billing_state;
            $user_data['customer']['delivery_postcode'] = $user->billing_postcode;
            $user_data['customer']['delivery_country']  = WP99234()->_api->get_formatted_country_name( $user->billing_country );
        }

        /* If the customer has changed their address on the website, and it now no longer matches
          the same_billing flag, we will turn this off for the customer and set the required values */
        if(!empty($user->billing_address_1) && ($user->billing_address_1 != $user->shipping_address_1) &&
          (
            ($user->billing_address_1 != $user->shipping_address_1) ||
            ($user->billing_city      != $user->shipping_city) ||
            ($billing_state           != $shipping_state) ||
            ($user->billing_postcode  != $user->shipping_postcode) ||
            (WP99234()->_api->get_formatted_country_name( $user->billing_country ) != $user_data['customer']['delivery_country'])
          )
        )
        {
            //We need to send them both sets of details.
            $user_data['customer']['same_billing']     = false;
            $user_data['customer']['billing_address']  = $user->billing_address_1;
            $user_data['customer']['billing_suburb']   = $user->billing_city;
            $user_data['customer']['billing_state']    = $billing_state;
            $user_data['customer']['billing_postcode'] = $user->billing_postcode;
            $user_data['customer']['billing_country']  = WP99234()->_api->get_formatted_country_name( $user->billing_country );
        } else {
            //Same Billing is true.
            $user_data['customer']['same_billing'] = true;
        }

        // Apply override for same_billing
        if (!empty($override_data) && isset($override_data['same_billing'])) {
            $user_data['customer']['same_billing'] = $override_data['same_billing'];
        }

        if (isset($_POST['tag_ids'])) {
            $tag_ids = explode( ',', $_POST['tag_ids'] );
            $user_data['customer']['tag_ids'] = $tag_ids;

            $customer_tags = get_option('troly_customer_tags');
            foreach ($customer_tags as $tag) {
                if (in_array($tag->id, $tag_ids)) {
                    $user_data['customer']['customer_tags'][] = $tag;
                }
            }
        }

        /**
         * Add in the raw user password.
         *
         * account_password - Woocommerce checkout
         *
         * pass2 - WP-Admin Create new user
         *
         */
        $password = false;
        if( isset( $_POST['account_password'] ) ){
            $password = trim( $_POST['account_password'] );
        }

        if( isset( $_POST['pass2'] ) ){
            $password = trim( $_POST['pass2'] );
        }

        if( $password ){
            $user_data['customer']['user_attributes']['password'] = $password;
        }

        $reporting_options = get_option('wp99234_reporting_sync');

        if ($subs_id) {
            $method = 'PUT';
            $message = 'Updating user (id: ' . $subs_id . ', email: ' . $user_data['customer']['email'] . ') on Troly';
        } else {
            $method = 'POST';
            $message = 'Exporting new user (email: ' . $user_data['customer']['email'] . ') to Troly';
        }

		$endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $subs_id );
		// Doing this because in some cases the $endpoint was just empty.
		if ( ! $endpoint || empty( $endpoint ) ) {
			$endpoint = $method == 'POST' ? WP99234_Api::$endpoint . 'customers.json' : WP99234_Api::$endpoint . sprintf( 'customers/%s.json', $subs_id );
		}
		$results = WP99234()->_api->_call( $endpoint, $user_data, $method );

        /*
            To prevent the entire workflow breaking on a bad save to Troly,
            return early if a false object is given. Prevents "non-object read"
            error messages.
		*/
        if(false === $results)
            return;

        //Ensure the SUBS ID is recorded
        if( $results->id && ! $subs_id ){
            update_user_meta( $user_id, 'subs_id', $results->id );
        }

        $errors = (array)$results->errors;

        if( ! empty( $errors ) ){

            $details = '';
            if(is_array($errors)){
                foreach($errors as $field => $ferrors){
                    $details .= "{$field} ";
                    if(is_array($ferrors)){
                        foreach($ferrors as $ferror){
                            $details .= "{$ferror} ";
                        }
                    } else {
                        $details .= "{$ferror} ";
                    }
                }
            }

            $message .= " failed.";

            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly( 0, 1, 0, $message, $details );
            }

            return $results;
        }

        //If we are checking out, save the hashed CC details and flag the user as having data.
        if( (defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT) || isset($user_data['customer']['cc_number']) ){

            if( $results->cc_number ){
                update_user_meta( $user_id, 'has_subs_cc_data', 'yes' );
                update_user_meta( $user_id, 'cc_number', $results->cc_number );
            }
        }

        if (isset($_POST['customers_tags']) && $results->customers_tags) {
            update_user_meta( $user_id, 'customers_tags', $results->customers_tags);
        }

        if (is_admin() && !$quiet) {
            WP99234()->_admin->add_notice( __( 'User was successfully exported to Troly.', 'wp99234' ), 'success' );
        }

        if ($subs_id) {
            $message .= "\nSuccessfully updated user on Troly";
        } else {
            $message .= "\nSuccessfully exported user to Troly";
        }

        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly( 1, 1, 0, $message );
        }

        return true;
	}

    /**
     * Find a user based on the given subs ID.
     *
     * @param $subs_id
     *
     * @return bool|mixed
     */
    function get_user_by_subs_id( $subs_id ){

        $user_query = new WP_User_Query( array( 'meta_key' => 'subs_id', 'meta_value' => $subs_id ) );

        $users = $user_query->get_results();

        if( $users && ! empty( $users ) ){
            return array_shift( $users );
        }

        return false;
    }

  /**
   * Get the mapping of subs user fields to wp user meta
   * @return array
   */
  function user_meta_map(){

        return array(
            'first_name'            => 'fname',
            'last_name'             => 'lname',
            'gender'                => 'gender',
            'billing_phone'         => 'phone',
            //'cc_name'               => 'cc_name',
            'cc_number'             => 'cc_number',
            //'cc_exp_month'          => 'cc_exp_month',
            //'cc_exp_year'           => 'cc_exp_year',
            'birthday'              => 'birthday',
            'notify_shipments'      => 'notify_shipments',
            'notify_payments'       => 'notify_payments',
            'notify_newsletters'    => 'notify_newsletters',
            'notify_renewals'       => 'notify_renewals',
            'same_billing'          => 'same_billing',
            'mobile'                => 'mobile',
            'company_name'          => 'company_name',

            'billing_first_name'    => 'fname',
            'billing_last_name'     => 'lname',
            'billing_email'         => 'email',
            'billing_company'       => 'company_name',
            'shipping_first_name'   => 'fname',
            'shipping_last_name'    => 'lname',
            'shipping_company'      => 'company_name',

            'billing_address_1'     => 'billing_address',
            'billing_city'          => 'billing_suburb',
            'billing_state'         => 'billing_state',
            'billing_postcode'      => 'billing_postcode',
            'billing_country'       => 'billing_country',

            'shipping_address_1'    => 'delivery_address',
            'shipping_city'         => 'delivery_suburb',
            'shipping_state'        => 'delivery_state',
            'shipping_postcode'     => 'delivery_postcode',
            'shipping_country'      => 'delivery_country',

            'delivery_area'         => 'delivery_area',
            'billing_area'          => 'billing_area',
            'delivery_region'       => 'delivery_region',
            'billing_region'        => 'billing_region',

            'delivery_instructions' => 'delivery_instructions',

            'current_memberships'   => 'current_memberships',
            'company_customers'   => 'company_customers',
        );

  }

  /**
   * Handle bulk imports
   */

    /**
     * Handle Bulk Import. If is SSE event, will send appropriate messages.
     *
     * @param bool $is_sse
     */
  function handle_bulk_import( $is_sse = false ){

    //Set the importing users define.
    //define( 'WP99234_DOING_SUBS_USER_IMPORT', true );

    //This could take some time.
    @set_time_limit( 0 );

    $start_time = time();
    $reporting_options = get_option('wp99234_reporting_sync');

    if( $is_sse ){
        WP99234()->send_sse_message( $start_time, 'Starting Customer Import.', 'start', 0);
    }

    $page = 1;

    $limit_per_call = 1000;

    $endpoint = esc_url_raw( add_query_arg( array(
        'l'                   => $limit_per_call,
        'current_memberships' => true,
        'p'                   => $page
    ), $this->users_endpoint ) );

    $import_is_allowed = true;

    /* Call the endpoint */
    $response = WP99234()->_api->_call( $endpoint );

    if ( $is_sse )
      WP99234()->send_sse_message( $start_time, __( 'Processing response from Troly...', 'wp99234' ));


    if( is_null( $response ) ) {
      if( $is_sse ){
          WP99234()->send_sse_message( $start_time, 'Import Failed - Invalid Response', 'fatal', 0 );
      }

      wp99234_log_troly( 0, 3, 0, 'An empty response was received from the server.' );

      $import_is_allowed = false;

    } elseif($response->count <= 0){
      if( $is_sse ){
          WP99234()->send_sse_message( $start_time, 'No customers were found in Troly for importing.', 'fatal', 0 );
      }
      wp99234_log_troly( 0, 3, 0, 'No customers were found in Troly for importing.' );
    }

    if(!$import_is_allowed)
      return;

    /**
     * Gather ALL users to be imported into one array, this may be via multiple calls to subs
     */

    $total_to_import = $response->count;

    $results_to_import = $response->results;

    $ready_to_process = count( $results_to_import );

    while( $total_to_import > $ready_to_process ){

        $page++;

        //Limit the paging to 100. If the site has more than 10000 users, we need to run a manual CSV import.
        if( $page >= 100 ){
          if( $is_sse )
              WP99234()->send_sse_message( $start_time, 'More than 10,000 users detected. Please run a manual CSV import', 'fatal');
          wp99234_log_troly( 2, 3, 0, 'Too many customers', '(>10,000) customers found; manual CSV import required. Process halted.');
          exit;
        }

        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'Received list ' . $page . ' ( ' . $ready_to_process . ' of ' . $total_to_import . ' customers)...', 'message', 0 );
        }

        $endpoint = esc_url_raw( add_query_arg( array(
            'l'                   => $limit_per_call,
            'current_memberships' => true,
            'p'                   => $page
        ), $this->users_endpoint ) );

        /* Call the endpoint, this time with a page query param */
        $response = WP99234()->_api->_call( $endpoint );

        //Validate that we have a result, try again if it fails the first time.
        if( ! is_array( $response->results ) ){
          $retry_count = 0;
          while( ! is_array( $response->results ) && $retry_count < 6 ){
              if( $is_sse ){
                  WP99234()->send_sse_message( $start_time, 'Invalid response received. Waiting 5 seconds and trying again......', 'message', 0 );
              }

              wp99234_log_troly( 2, 3, 0, 'Invalid response received.', 'Waiting 5 seconds and trying again...' );

              sleep( 5 );

              //Try Again.
              $response = WP99234()->_api->_call( $endpoint );
              $retry_count++;
          }

          //if we get to this stage and we still don't have results, we can just stop.
          if( ! is_array( $response->results ) ){
            if( $is_sse ){
                WP99234()->send_sse_message( $start_time, 'Invalid response received. Aborting......', 'fatal', 0 );
			}

            wp99234_log_troly( 2, 3, 0, 'Invalid response received!', var_export( $response, true ) );
            exit;
          }
        }

        $results_to_import = array_merge( $results_to_import, $response->results );

        $ready_to_process = count( $results_to_import );

    }

    //Remove duplicates.
    $results_to_import = array_map( 'unserialize', array_unique( array_map( 'serialize', $results_to_import ) ) );

    if( $is_sse ){
        WP99234()->send_sse_message( $start_time, 'Importing ' . count( $results_to_import ) . ' customers...', 'message', 0 );
    }

    wp99234_log_troly( 2, 3, 0, 'Importing ' . count($results_to_import) . ' customers...' );

    $progress = 0;

    $failed_user_ids = array();

    /**
     * Import all users in one foreach loop.
     */
    $imported = 0;

    foreach( $results_to_import as $user ){

      $user_id = $this->import_user( $user );
      if( is_wp_error( $user_id ) ){
        $failed_user_ids[$user->id] = $user_id->get_error_message();
        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, '&gt; Importing <i><a href="//'.WP99234_DOMAIN.'/c/'.$user->id.'">'.$user->fname . ' ' . $user->lname . '</a></i> failed.', 'message', $progress );
            WP99234()->send_sse_message( $start_time, '&gt; <span class="wp99234_mono wp99234_error wp99234_indented">'.$user_id->get_error_message()."</span>", 'validation_error_code', $progress );
            WP99234()->send_sse_message( $start_time, '&gt; <span class="wp99234_error wp99234_indented">Check the user has an email address and first name before trying again</span>', 'validation_error_code', $progress );
        } else {
            WP99234()->_admin->add_notice( 'Customer ' . $user->id . ' ('.$user->fname . ' ' . $user->lname . ') failed to import: ' . $user_id->get_error_message(), 'error' );
        }

        wp99234_log_troly( 2, 3, 0, 'Customer ' . $user->id . ' ('.$user->fname . ' ' . $user->lname . ') failed to import', $user_id->get_error_message() );

      } else {
        $imported++;
        if( $is_sse ){
          WP99234()->send_sse_message( $start_time, '&gt; Importing <i><a href="//'.WP99234_DOMAIN.'/c/'.$user->id.'">'.$user->fname . ' ' . $user->lname . '</a></i> succeeded', 'message', $progress );
        }
      }
      $progress = number_format( ( $imported / count( $results_to_import ) ) * 100, 2 );
    }

    if( $is_sse ){
      WP99234()->send_sse_message( $start_time, 'Successfully imported ' . $imported .  ' out of ' . $ready_to_process . ' customers.', 'message', $progress );
    } else {
      WP99234()->_admin->add_notice( $imported . ' customers were successfully imported.', 'success' );
    }

    wp99234_log_troly( 1, 3, 0, 'Successfully imported ' . $imported . ' customers' );

    if( ! empty( $failed_user_ids ) ){

      WP99234()->logger->error( 'The following customers failed to import. ' );
      WP99234()->logger->error( WP99234()->get_var_dump( $failed_user_ids ) );

      wp99234_log_troly( 2, 3, 0, 'Some customers failed to import.', 'Refer to the technical logs for more information.' );

      if( $is_sse ){
        WP99234()->send_sse_message( $start_time, 'Some customers failed to import; review the log for detailed information.', 'fatal', 100 );
      }

    } else {
      if ( $is_sse ){
        WP99234()->send_sse_message( $start_time, 'Customer import completed successfully!', 'close', 100 );
      } else {
        wp_redirect( admin_url( 'users.php' ) );
      }
      wp99234_log_troly( 1, 3, 0, 'Customer import completed successfully.' );
    }

    //Allow the current user to run the import again if required.
    $current_user = wp_get_current_user();
    $current_user->add_cap( 'manage_wp99234_users' );

    update_option( 'wp99234_user_import_has_run', true );
    exit;
  }

    /**
     * Update a user ( Customer ) in the SUBS system.
     *
     * @param $user_data
     * @return array|mixed
     */
    function update_subs_user( $user_data ){

        $data = array(
            'customer' => $user_data
        );

        if( $this->current_user_update_endpoint !== false ){

            $results = WP99234()->_api->_call( $this->current_user_update_endpoint, $data, 'PUT' );

        } else {

            //The user doesn't exist in SUBS, lets create one.
            $results = WP99234()->_api->_call( $this->users_endpoint, $data, 'POST' );

		}

        return $results;

    }

    /**
     * Get the update endpoint for a given user ID.
     *
     * If no user ID, gets the endpoint to add a user.
     *
     * @param $user_id
     *
     * @return string
     */
    function get_update_endpoint_for_user_id( $user_id ){

        if( $user_id ){
            return WP99234_Api::$endpoint . sprintf( 'customers/%s.json', $user_id );
        } else {
            return $this->users_endpoint;
        }

    }

    function filter_customer_meta_fields( $fields ){

        $fields['extra'] = array(
            'title' => __( 'Troly Profile Information', 'wp99234' ),
            'fields' => array(
                'gender' => array(
                    'label' => __( 'Gender', 'wp99234' ),
                    'description' => 'Must be either m, f or -'
                ),
                'phone' => array(
                  'label' => __( 'Phone Number', 'wp99234' ),
                  'description' => ''
                ),
                'birthday' => array(
                    'label' => __( 'Birthday', 'wp99234' ),
                    'description' => 'The birthday used from Troly or at the last sale done through WooCommerce'
                ),
                'notify_shipments' => array(
                    'label' => __( 'Notify Shipments', 'wp99234' ),
                    'description' => 'Will the customer receive information about shipments'
                ),
                'notify_payments' => array(
                    'label' => __( 'Notify Newsletter', 'wp99234' ),
                    'description' => 'Will the customer receive emails about declined payments or expired cards'
                ),
                'notify_renewals' => array(
                    'label' => __( 'Notify Renewals', 'wp99234' ),
                    'description' => 'Will the customer recieve emails about renewed memberships'
                ),
                'delivery_instructions' => array(
                    'label' => __( 'Delivery Instructions', 'wp99234' ),
                    'description' => 'What are the instructions for a shipment placed'
                ),
                'same_billing' => array(
                    'label' => __( 'Same Billing', 'wp99234' ),
                    'description' => 'Ignore delivery address and use billing address',
                    'type' => 'checkbox'
                ),
                'mobile' => array(
                    'label' => __( 'Mobile Number', 'wp99234' ),
                    'description' => ''
                ),
              //  'company_name' => array(
              //      'label' => __( 'Company Name', 'wp99234' ),
              //      'description' => ''
              //  )
            )
        );

        return $fields;

    }

//    function get_formatted_state( $state ){
//
//        if( ! $state || empty( $state ) ) {
//            return $state;
//        }
//
//        switch( strtolower( $state ) ){
//
//            case 'queensland':
//                return 'QLD';
//                break;
//
//            case 'new south wales':
//                return 'NSW';
//                break;
//
//            case 'australian capital territory':
//                return 'ACT';
//                break;
//
//            case 'northern territory':
//                return 'NT';
//                break;
//
//            case 'south australia':
//                return 'SA';
//                break;
//
//            case 'tasmania':
//                return 'TAS';
//                break;
//
//            case 'victoria':
//                return 'VIC';
//                break;
//
//            case 'western australia':
//                return 'WA';
//                break;
//
//            default:
//                return $state;
//                break;
//
//        }
//
//    }

    /**
     * Handle user creation / update on checkout.
     * Export User's info when done placing order to sync shipping address.
     *
     * @param $order_id
     * @param $posted
     *
     * @return bool
     * @throws Exception
     */
    function on_checkout_order_processed($order_id, $posted)
    {
        /**
         * Exporting order to Subs after order is processed rather than when processing payment
         * this allows us to export ALL orders to Subs, including ones with a $0 value that normally
         * wouldn't be exported due to the payment processing not running when an orders total value is $0
         **/
         WP99234()->_woocommerce->export_order($order_id);

        $order = new WC_Order($order_id);

        //get the user email from the order
        $order_email = $order->get_billing_email();

        // Get User to apply changes base order or customer details
        $user    = $order->get_user();
        $user_id = $user ? $user->ID : false;

        if (!$user_id) {
            WP99234()->logger->error('Order ' . $order_id . ' was created, and the user was not logged in.');
            return false;
        }

        $billing_address = $order->get_formatted_billing_address();
        $shipping_address = $order->get_formatted_shipping_address();

        $load_address = null;
        $user_override = array();
        $assign_new_card = false;
        // Also Export User's info when done placing order to sync shipping address.
        if ($billing_address !== $shipping_address && $posted['ship_to_different_address']) {
            $load_address = 'shipping';
            update_user_meta($user_id, 'same_billing', false);

            // Update Customer billing details
            if (isset($posted['troly_shipping_as_permanent']) && $posted['troly_shipping_as_permanent']) {

                $customer = new WC_Customer($user_id);

                $customer->set_billing_location(
                    $posted['shipping_country'],
                    $posted['shipping_state'],
                    $posted['shipping_postcode'],
                    $posted['shipping_city']
                );
                $customer->set_billing_address($posted['shipping_address_1']);

                $user_response = $customer->save();

                if (is_wp_error($user_response)) {
                    WP99234()->logger->error(sprintf('A WordPress error occurred saving "%s". This user could not be save. (%s)', $user_id, $user_response->get_error_message()));
                } else {
                    if ($_POST['payment_method'] === 'wp99234_payment_gateway' && !isset($_POST['wp99234_use_existing_card'])) {
                        $cc_exp = explode('/', str_replace(' ', '', wp_kses($_POST['wp99234_payment_gateway-card-expiry'], array())));

                        if (count($cc_exp) > 1) {
                            $assign_new_card = true;

                            $cc_number = wp_kses($_POST['wp99234_payment_gateway-card-number'], array());
                            $cc_number = str_replace(' ', '', $cc_number);

                            $user_override = array(
                                'cc_name'      => wp_kses($_POST['wp99234_payment_gateway-card-name'], array()),
                                'cc_number'    => $cc_number,
                                'cc_exp_month' => $cc_exp[0],
                                'cc_exp_year'  => $cc_exp[1],
                                'cc_cvv'       => wp_kses($_POST['wp99234_payment_gateway-card-cvc'], array()),
                            );
                        }
                    }

                    $user_override['same_billing'] = false;
                    foreach ($posted as $key => $value) {
                        if (strpos($key, 'billing_') !== false) {
                            $user_override[$key] = $value;
                        }
                    }

                    foreach ($posted as $key => $value) {
                        if (strpos($key, 'shipping_') !== false) {
                            $user_override[$key] = $value;
                        }
                    }

                    // Allow export Customer details as the user asking to make changes and make it permanent
                    if (!defined('WP99234_ALLOW_USER_EXPORT')) {
                        define( 'WP99234_ALLOW_USER_EXPORT', true );
                    }
                }
            }
        } else {
            update_user_meta($user_id, 'same_billing', true);
        }

        //Update user first / last name.
        update_user_meta($user_id, 'first_name', $posted['billing_first_name']);
        update_user_meta($user_id, 'last_name', $posted['billing_last_name']);

        //Phone and company name
        update_user_meta($user_id, 'phone', $posted['billing_phone']);
        update_user_meta($user_id, 'company_name', $posted['billing_company']);

        if (isset($posted['order_comments'])) {
            update_user_meta($user_id, 'delivery_instructions', esc_html($posted['order_comments']));
        }

        // Update birthday information if present in POST
        if ( isset( $posted['subs_birthday'] ) && get_option( 'troly_require_dob' ) != '' ) {
            update_user_meta($user_id, 'birthday', $posted['subs_birthday']);
        }

        if (defined('WP99234_DONE_USER_EXPORT') && WP99234_DONE_USER_EXPORT) return false;

        $user_response = $this->export_user($user_id, $load_address, $user_override, true, $assign_new_card);

        if ($user_response === false) {
            throw new Exception(__('An error has occurred, and we could not process your payment. Please ensure your credit card details are correct and try again. You will be contacted via phone ASAP to ensure your order is processed as soon as possible.', 'troly'));

            ob_start();
            $errs = ob_get_contents();
            ob_end_clean();
            WP99234()->logger->error($errs);

        }

    }

    /**
     * Handle user membership pricing fetch if none already exist for the user.
     *
     * @notes
     * I will leave this function here as it may come in handy during the setup to authorise login using troly.
     * The code the get user membership data on login is no longer required as it is now imported on the initial import and pushed to WP when a user is updated in subs.
     *
     * @param $user_login
     * @param WP_User $user
     */
    public function on_login( $user_login, WP_User $user ){

        /*if( ! WP99234()->_api ){
            return;
        }*/

    }

    /**
     * Handle an AJAX call to import the users via SUBS api.
     */
    function on_ajax_subs_import_users(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        $this->handle_bulk_import( true );

        exit;

    }

    /**
     * Handle the export of all current customers to SUBS.
     */
    function on_ajax_subs_export_users(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        $users = get_users( array(
            'role' => 'Customer'
        ));

        $timestart = time();

        $message = 'Starting export of customers to subs, exporting ' . count($users) . ' customers';
        WP99234()->send_sse_message( $timestart, sprintf( __( 'Exporting %s users', 'wp99234' ), count( $users ) ) );

        $success = 0;

        $total_to_export = count( $users );
        $next_set = 0;
        $cron_increment_run_time = time() + 60;
        $user_ids = array();

        foreach ( $users as $user ) {
          $user_ids[] = $user->ID;
        }

        WP99234()->send_sse_message( $timestart, 'Queuing export of ' . $total_to_export . ' customers.', 'message', 100 );
        $message .= "\nQueuing export of {$total_to_export} customers.";

        while ($next_set < $total_to_export) {

          $slice = array_slice($user_ids, $next_set, 5);

          // should always exist but just an extra check
          if (isset($slice)) {
            // schedule a one time cron task to run and export the above users slice
            wp_schedule_single_event( $cron_increment_run_time, 'wp99234_cron_export_users', array($slice) );
          }

          $cron_increment_run_time += 600;
          $next_set += 5;

        };

        WP99234()->send_sse_message( $timestart, 'Customer export has been scheduled to run and will complete over the day.', 'close', 100 );

        $message .= "\nCustomer export has been successfully scheduled to run and export {$total_to_export} customers.";

        $reporting_options = get_option('wp99234_reporting_sync');

        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly( 2, 1, 0, 'Customer', $message );
        }

        update_option( 'wp99234_user_export_has_run', true );

        exit;

    }

    /**
     * Authenticate user with Subs if they have a subs_id.
     *
     * @param $user
     * @param $login
     * @param $password
     */
    function handle_authentication( $user, $login, $password ){

        $user_obj = get_user_by( 'login', $login );

        if( $user_obj ){

            $subs_id = get_user_meta( $user_obj->ID, 'subs_id', true );

            if( $subs_id && $subs_id !== '' ){

                $signin_data = array(
                    'user' => array(
                        'email'    => $user_obj->user_email,
                        'password' => (string)$password
                    )
                );

                $endpoint = sprintf( '%s/users/sign_in.json', WP99234_Api::$endpoint );

            }

        } else {
            return $user;
        }

        $break = 1;

	}

	/**
	 * Adding action for a cron task to be used when exporting users
	 * Required due to different maximum script execution times and some
	 * sites will kill the export script before it completes normally
	 **/
	public function cron_export_users_to_subs($user_ids) {

		$reporting_options = get_option('wp99234_reporting_sync');
		$message = 'Started exporting users in cron task';
		$exported = 0;
		$failed = 0;
		$total = count($user_ids);

		foreach( $user_ids as $id ){

			$results = WP99234()->_users->export_user( $id, null, array(), true );

			if( !$results || is_array( $results ) ){
				$failed++;
				$message .= "\nCustomer with id {$id} failed to export.";
			} else {
				$exported++;
			}
		}

		$message .= "\nUser export completed successfully with {$exported} users exported and {$failed} which failed to export.";

		if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
			wp99234_log_troly( 1, 1, 0, 'Bulk Users Export', $message );
		}
    }
}
