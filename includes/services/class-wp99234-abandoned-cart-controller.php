<?php

/**
 * Troly Abandoned Cart Controller
 *
 * @todo Fix and document the logics.
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 * @since 2.19.21
 */
class TrolyAbandonedCartController {
	private $endpoint;

	public function __construct()
	{
		$this->endpoint = WP99234_Api::$endpoint . 'customers/analytics';
		add_action( 'set_abandoned_cart_cron', [$this, 'push'], 10, 1 );
		add_action( 'wp_loaded', [$this, 'set'], 99999 );
	}

	public function set()
	{
		$userID = get_current_user_id();
		$trolyUserID = get_user_meta( $userID, 'subs_id', true );

		if ( ! is_admin() && WC()->cart && ! WC()->cart->is_empty() && $trolyUserID ) $this->setCRON( $trolyUserID );
	}

	public function setCRON( $trolyUserID )
	{
		// Use 2 hours by default.
		$abandonedCartBuffer = get_option( 'troly_abandoned_cart_buffer', 2 );
		$cartItems  = $this->getAbandonedProducts();

		// Get rid of all the other CRONs associated to the logged in user.
		foreach ( _get_cron_array() as $timestamp => $cron ) {
			if ( isset( $cron['set_abandoned_cart_cron'] ) ) {
				foreach ( $cron['set_abandoned_cart_cron'] as $c ) {
					if ( in_array( $trolyUserID, $c['args'] ) ) {
						wp_unschedule_event( $timestamp, 'set_abandoned_cart_cron', $c['args'] );
					}
				}
			}
		}

		if ( ! wp_next_scheduled( 'set_abandoned_cart_cron', [ $cartItems, $trolyUserID ] ) ) {
			wp_schedule_single_event( time() + ( (int)$abandonedCartBuffer * 60 * 60 ), 'set_abandoned_cart_cron', [ $cartItems, $trolyUserID ] );
		}
	}

	private function getAbandonedProducts()
	{
		$products = [];
		$cartItems = WC()->cart ? WC()->cart->get_cart() : [];

		foreach ( $cartItems as $item ) {
			$trolyProductID = get_post_meta( $item['product_id'], 'subs_id', true );

			$products[] = [
				'id' => $trolyProductID,
				'qty' => $item['quantity'],
			];
		}

		return $products;
	}

	public function push( $cartItems, $trolyUserID )
	{
		$abandonedCartData = [
			'utm_campaign' => 'abandoned_cart',
			'custom_data' => $cartItems,
			'customer_id' => $trolyUserID,
		];

		WP99234()->_api->_call( $this->endpoint, $abandonedCartData, 'POST' );
	}
}