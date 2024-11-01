<?php

/**
 * UTM tracking for Troly.
 *
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 * @since 2.19.21
 */
class TrolyUTMTrackingController {
	/**
	 * Troly Endpoint for UTM tracking and analytics
	 *
	 * @var string
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 */
	public $endpoint;

	/**
	 * Array of valid UTM key/value pair.
	 *
	 * @var array
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 */
	protected $utmParams = [];

	/**
	 * List of all the required UTM keys to validate the data.
	 *
	 * @var array
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 */
	protected $validKeys = [
		'utm_source',
		'utm_medium',
		'utm_campaign',
	];

	public function __construct()
	{
		$this->endpoint = WP99234_Api::$endpoint . 'customers/analytics';

		add_action( 'init', [$this, 'detectUTMParams'] );
		add_action( 'wp_login', [$this, 'afterUserLoginCheck'], 10, 2 );
	}

	/**
	 * Check if there are any query params starting with "utm".
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return void
	 */
	public function detectUTMParams()
	{
		$queryParams = $_GET;
		$utmParams = [];

		foreach ( $queryParams as $param => $value ) {
			if ( strpos( $param, 'utm_' ) !== false ) {
				$utmParams[ $param ] = $value;
			}

			// Separate check for customer ID in the query string.
			if ( 'cid' === $param ) {
				$utmParams['customer_id'] = $value;
			}
		}

		if ( $this->validate( $utmParams ) ) {
			if ( ! isset( $utmParams['customer_id'] ) ) {
				// Check if user is already logged in and has a Troly ID.
				if ( is_user_logged_in() ) {
					$userTrolyID = get_user_meta( get_current_user_id(), 'subs_id', true );

					if ( $userTrolyID ) {
						$utmParams['customer_id'] = $userTrolyID;
						$this->utmParams = $utmParams;
						$this->store()->push();
					}
				}
			}

			$this->utmParams = $utmParams;
			$this->store();
		}
	}

	/**
	 * Check if the user has Troly ID, and UTM session set, after they log in,
	 * and push event to Troly.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @param string $userLogin
	 * @param object WP_User $user
	 * @return void
	 */
	public function afterUserLoginCheck( $userLogin, $user )
	{
		// Only proceed if UTM data is set in session.
		if ( $utmParams = $this->get() ) {
			$userTrolyID = get_user_meta( $user->ID, 'subs_id', true );

			if ( $userTrolyID ) {
				$utmParams['customer_id'] = $userTrolyID;
				$this->utmParams = $utmParams;
				$this->store()->push();
			}
		}
	}

	/**
	 * Get the UTM data stored in session.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return array|null
	 */
	public function get()
	{
		return $_SESSION['troly_utm_tracking'] ?? null;
	}

	/**
	 * Make sure that the required UTM keys are present in the collection.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @param array $utmParams
	 * @return bool
	 */
	private function validate( $utmParams = [] )
	{
		foreach ( $this->validKeys as $key )
			if ( ! array_key_exists( $key, $utmParams ) ) return false;

		return true;
	}

	/**
	 * Store the data in sessions for later use.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return void
	 */
	private function store()
	{
		$_SESSION['troly_utm_tracking'] = $this->utmParams;

		return $this;
	}

	/**
	 * Push event data to Troly.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return mixed
	 */
	private function push()
	{
		$UTMData = $this->get();

		if ( ! $UTMData ) return;

		$response = WP99234()->_api->_call( $this->endpoint, $UTMData, 'POST' );
	}
}