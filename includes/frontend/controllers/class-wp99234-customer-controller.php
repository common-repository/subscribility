<?php namespace Troly\frontend\controllers;
/**
 * Troly customers class.
 *
 */

use Troly\frontend\controllers\OrderController;
class CustomerController {
	public $order;
	private $_user;
	private $_trolyUserID;
	private $_orderData;
	private $_customerData;
	private $_creditCardDetails;
	private $_customer;

	public function __construct()
	{
		$this->order = new OrderController;

		add_action( 'init', [$this, 'addAccountEndpoints'] );
		add_filter( 'woocommerce_account_menu_items', [$this, 'addViewReferralsLink'] );
		add_action( 'woocommerce_account_troly-referrals_endpoint', [$this, 'viewReferralStat'] );
		add_filter( 'the_title', [ $this, 'referralEndpointTitle' ], 20 );
	}

	/**
	 * Change the referral endpoint title.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @param string $title
	 * @return string $title
	 */
	public function referralEndpointTitle( $title ) {
		global $wp_query;

		$referralEndpoint = isset( $wp_query->query_vars[ 'troly-referrals' ] );

		if ( $referralEndpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			$title = __( 'Your Referrals', 'troly' );

			remove_filter( 'the_title', [ $this, 'referralEndpointTitle' ], 20 );
		}

		return $title;
	}

	/**
	 * Add link in WooCommerce user profile to view membership details.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param array $menuLinks
	 * @return array $menuLinks
	 */
	public function addViewReferralsLink( $menuLinks )
	{
		$menuLinks = array_slice( $menuLinks, 0, 5, true )
		+ [ 'troly-referrals' => 'Referrals' ]
		+ array_slice( $menuLinks, 5, null, true );

		return $menuLinks;
	}

	/**
	 * Adds an endpoint for WordPress to understand.
	 *
	 * @todo might need to revamp this.
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	public function addAccountEndpoints()
	{
		add_rewrite_endpoint( 'troly-referrals', EP_PAGES );
		flush_rewrite_rules(); // should we call this every time?
	}

	/**
	 * Returns referral data in tabular form.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	public function viewReferralStat()
	{
		$referralData = $this->getReferralData();
		$referralOrders = $referralData && $referralData->total_referral_units > 0 ?
			$referralData->orders : [];

		if ( ! empty( $referralOrders ) ) : ?>
			<table class="shop_table shop_table_responsive account-orders-table account-referrals-table">
				<thead>
					<tr>
						<th>Referred Date</th>
						<th>Referred Customer</th>
						<th>Referral Redeemed</th>
						<th>Bottles</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $referralOrders as $order ) : ?>
						<tr>
							<td><?php echo $order->created_date; ?></td>
							<td><?php echo $order->name; ?></td>
							<td><?php echo $order->value; ?></td>
							<td><?php echo $order->bottle_count; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else:
			echo '<p>' . __( 'No referrals found.', 'troly' ) . '</p>';
		endif;
	}

	public function getOrder()
	{
		return $this->order->getOrder();
	}

	public function setCustomerData()
	{
		$this->_customerData = array(
			'customer' => array(
				'fname'             => $this->getOrder()->get_billing_first_name(),
				'lname'             => $this->getOrder()->get_billing_last_name(),
				'email'             => $this->getOrder()->get_billing_email(),
				'phone'             => $this->getOrder()->get_billing_phone(),
				'company_name'      => $this->getOrder()->get_billing_company(),
				'billing_address'   => $this->getOrder()->get_billing_address_1(),
				'billing_suburb'    => $this->getOrder()->get_billing_city(),
				'billing_postcode'  => $this->getOrder()->get_billing_postcode(),
				'billing_state'     => $this->getOrder()->get_billing_state(),
				'billing_country'   => WC()->countries->countries[$this->getOrder()->get_billing_country()],
				'same_billing'      => true,
				'delivery_address'  => $this->getOrder()->get_billing_address_1(),
				'delivery_suburb'   => $this->getOrder()->get_billing_city(),
				'delivery_postcode' => $this->getOrder()->get_billing_postcode(),
				'delivery_state'    => $this->getOrder()->get_billing_state(),
				'delivery_country'  => WC()->countries->countries[$this->getOrder()->get_billing_country()],
				'notify_shipments'  => '@|mail'
			)
		);

		if ( isset( $_POST[ 'subs_birthday' ] ) ) {
			$date = \DateTime::createFromFormat( get_option( 'date_format' ), $_POST[ 'subs_birthday' ] );
			if ( $date != false ) {
				$this->updateCustomerData( [
					'birthday' => $date->format( 'j M Y' ),
				] );
			}
		}
	}

	public function getCustomerData()
	{
		return $this->_customerData;
	}

	public function updateCustomerData( array $updatedCustomerData )
	{
		$this->_customerData[ 'customer' ] = array_replace_recursive( $this->_customerData[ 'customer' ], $updatedCustomerData );
	}

	public function getWPUser()
	{
		if ( is_user_logged_in() ) :
			$this->_user = $this->getOrder()->get_user();
		else:
			$this->_user = get_user_by( 'email', $this->getOrder()->get_billing_email() );
		endif;

		return $this->_user;
	}

	public function getTrolyUserID()
	{
		$this->_customer = $this->getWPUser();

		return $this->_trolyCustomerID = $this->_customer ? get_user_meta( $this->_customer->ID, 'subs_id', true ) : false;
	}

	public function createTrolyUser()
	{
		$reporting_options = get_option( 'wp99234_reporting_sync', 'minimum' );
		$apiResponse = WP99234()->_api->_call( WP99234()->_users->users_create_endpoint, $this->getCustomerData(), 'POST' );

		if ( isset( $apiResponse->id ) ) {
			$trolyUserID = $apiResponse->id;
			$this->order->updateOrderData( [
				'customer_id' => $trolyUserID,
			] );

			update_user_meta( $this->getWPUser()->ID, 'subs_id', $trolyUserID );

			if ( isset( $apiResponse->birthday ) && ! empty( $apiResponse->birthday ) ) {
				update_user_meta( $this->getWPUser()->ID, 'birthday', $apiResponse->birthday );
			}

			if ( isset( $apiResponse->cc_number ) && ! empty( $apiResponse->cc_number ) ) {
				update_user_meta( $this->getWPUser()->ID, 'has_subs_cc_data', 'yes' );
				update_user_meta( $this->getWPUser()->ID, 'cc_number', $apiResponse->cc_number );
			}

			// Add flag to prevent updating customer for newly created account
			define( 'WP99234_DONE_USER_EXPORT', true );

			return $trolyUserID;
		} else {
			$message = "\n New customer could not be created and order processing has failed";

			if ( $reporting_options == "verbose" || $reporting_options == "minimum" ) {
				wp99234_log_troly( 0, 1, 0, 'Order Export to Troly', $message );
			}

			if ( is_admin() ) {
				WP99234()->_admin->add_notice( __( 'Could not retrieve the customer for the order.', 'troly' ), 'error' );
				return false;
			}

			throw new \Exception( __( 'There was an error processing your order, please try again shortly.', 'troly' ) );
		}
	}

	// private function setTrolyUserID()
	// {
	// 	$trolyID = get_post_meta( $order_id, 'troly_id', true );
	// 	// Fallback to the older "key" string if the newer one fails to return data.
	// 	$trolyID = $trolyID && ! empty( $trolyID ) ? $trolyID : get_post_meta( $order_id, 'subs_id', true );

	// 	$this->_trolyUserID = $trolyID;
	// }


	public function setCreditCardDetails( array $creditCardDetails )
	{
		$cc_exp = explode( '/', str_replace( ' ', '', wp_kses( $creditCardDetails[ 'wp99234_payment_gateway-card-expiry' ], [] ) ) );

		if ( count( $cc_exp ) > 1 ) {
			$cardNumber = wp_kses( $creditCardDetails[ 'wp99234_payment_gateway-card-number' ], [] );
			$cardNumber = str_replace(' ', '', $cardNumber);

			$this->_creditCardDetails = [
				'cc_name'      => wp_kses( $creditCardDetails[ 'wp99234_payment_gateway-card-name' ], [] ),
				'cc_number'    => $cardNumber,
				'cc_exp_month' => $cc_exp[0],
				'cc_exp_year'  => $cc_exp[1],
				'cc_cvv'       => wp_kses( $creditCardDetails[ 'wp99234_payment_gateway-card-cvc' ], [] ),
			];

			// Set CC details to Customer Data object as well.
			$this->updateCustomerData( [
				'cc_name'      => wp_kses( $creditCardDetails[ 'wp99234_payment_gateway-card-name' ], [] ),
				'cc_number'    => $cardNumber,
				'cc_exp_month' => $cc_exp[0],
				'cc_exp_year'  => $cc_exp[1],
				'cc_cvv'       => wp_kses( $creditCardDetails[ 'wp99234_payment_gateway-card-cvc' ], [] ),
			] );
		}
	}

	public function getCreditCardDetails()
	{
		return $this->_creditCardDetails;
	}

	/**
	 * Retrieve the coupon code associated with the referral code.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @return string|bool
	 */
	public function getReferralCoupon()
	{
		return get_option( 'troly_member_referral_coupon' );
	}

	/**
	 * Retrieve referral data associated with the logged in user.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @return object|bool
	 */
	public function getReferralData()
	{
		$referralMeta = get_user_meta( get_current_user_id(), 'company_customers', true );

		return ! empty( $referralMeta ) ? current( $referralMeta )->referrals : false;
	}

	/**
	 * Checks if the provided referral code is valid and exits in the database.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @param string $code
	 * @return object|bool
	 */
	public function validateReferralCode( string $code )
	{
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}usermeta WHERE meta_key = 'company_customers' AND meta_value LIKE '%{$code}%'" );
	}
}