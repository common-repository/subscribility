<?php

/**
 * Class to handle membership tpye updates and management through the WP99234 API.
 *
 * Class WP99234_API_Membership_Types
 */
class WP99234_API_Membership_Types extends WP99234_API_Server{

	/**
	* Object Constructor
	*/
	function __construct(){

		define( 'WP99234_INVALID_SUBS_MEMBERSHIP_TYPE_ID', __( 'Invalid Subs Membership Tpye ID', 'wp99234' ) );

		$this->accepted_methods = array('PUT');

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
			if ( 'none' !== get_option( 'wp99234_club_sync', 'both' ) ) {
				$this->response = $this->update_membership_type( $this->body );
			}
			$this->respond();
		}

		WP99234()->logger->error( 'Unable to serve request. No method found.' );
		$this->errors[] = WP99234_INVALID_REQUEST;
		$this->respond();

	}

	/**
	* Update a membership type with the given data.
	*
	* @param $data
	*
	* @return array|bool
	*/
	function update_membership_type( $data ){

		if( defined( 'WP_DEBUG' ) && WP_DEBUG ){

			WP99234()->logger->debug( 'Updating Membership Type...' );

			ob_start();
			var_dump( $data );
			$str = ob_get_contents();
			ob_end_clean();

			WP99234()->logger->debug( $str );

		}

		$reporting_options = get_option('wp99234_reporting_sync');
		$message = 'Updating membership type (id: ' . $data->id . ', name: ' . $data->name . ') from Troly';

		//Make the results an associative array to make processing users and finding prices a much easier operation later.
		$types = get_option( 'wp99234_company_membership_types' );

		if (!$types) {
			$types = array();
		}
		$types[$data->id] = $data;

		/* Remove membership types which are disabled, as these memberships are no longer in use
		*  This is used if a previously active membership was disabled, in that case remove it from our records */
		foreach( $types as $id => $membership_type ) {
			if ( isset( $membership_type->visibility) && $membership_type->visibility == 'disabled') {
				unset($types[$id]);
			}
		}

		$result = update_option( 'wp99234_company_membership_types', $types );

		$message .= "\nMembership Type updated successfully";

		if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
			wp99234_log_troly( 1, 3, 0, 'Membership Type update from Subs', $message );
		}

		return array(
			'membership_type' => $data,
			'subs_id' => $data->id
		);
	}
}