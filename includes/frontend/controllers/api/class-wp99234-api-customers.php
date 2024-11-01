<?php

/**
 * Class to handle customer updates and management through the WP99234 API.
 *
 * Class WP99234_Api_Customers
 */
class WP99234_Api_Customers extends WP99234_Api_Server {

    /**
     * Object Constructor
     */
    function __construct(){

        define( 'WP99234_INVALID_SUBS_CUST_ID', __( 'Invalid Subs Customer ID', 'wp99234' ) );

        $this->accepted_methods = array( 'PUT' );

        parent::__construct();

    }

    /**
     * Serve the current API Request.
     *
     * @param $route
     */
    function serve_request( $route ){

        array_shift( $route );

        if( $this->method == 'PUT' ){
            if ( 'none' !== get_option( 'wp99234_customer_sync', 'both' ) ) {
				$this->response = $this->update_user( $this->body );
			}
            $this->respond();
        }

        WP99234()->logger->error( 'Unable to serve request. No method found.' );
        $this->errors[] = WP99234_INVALID_REQUEST;
        $this->respond();

    }

    /**
     * Update a product with the given data.
     *
     * @param $data
     *
     * @return array|bool
     */
    function update_user( $data ){

        define( 'WP99234_DOING_SUBS_USER_IMPORT', true );

        $user_id = WP99234()->_users->import_user( $data );

        $reporting_options = get_option('wp99234_reporting_sync');
        $message = '';

        if( is_wp_error( $user_id ) ){
            $message .= 'Customer failed to be updated. The following error occurred\n' . $user_id->get_error_message();

            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
              wp99234_log_troly( 0, 3, 0, $message );
            }

            WP99234()->logger->error( printf( 'Customer failed to import. The following error occurred: %s', $user_id->get_error_message() ) );
            return false;
        }

        $user = get_user_by( 'id', $user_id );

        $message = 'Customer successfully updated ('.$user_id.')';

        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
          wp99234_log_troly( 1, 3, 0, 'Customer', $message );
        }

        return array(
            'customer' => $user,
            'subs_id'  => $data->id
        );

    }


}