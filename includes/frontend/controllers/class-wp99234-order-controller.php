<?php namespace Troly\Frontend\Controllers;

/**
 * Controller for Customer Orders.
 */

class OrderController {
	private $_orderData;
	private $_order;
	private $_shipping;
	private $_discount = 0;
	private $_discountTitle = 'Coupon(s): ';

	public function getOrder()
	{
		return $this->_order;
	}

	public function setOrder( int $orderID )
	{
		$this->_order = new \WC_Order( $orderID );
	}

	public function setOrderData()
	{
		$this->_orderData[ 'order' ] = [
			'source' => 'web',
			'status' => 'confirmed',
			'fname' => $this->_order->get_billing_first_name(),
			'lname' => $this->_order->get_billing_last_name(),
			'company_name' => $this->_order->get_billing_company(),
			'user_id' => '',
			'total_qty' => count( $this->_order->get_items() ),
			'orderlines' => []
		];

		$this->setOrderDiscount();

		// Attach billing info to Order
		foreach( WP99234()->_customer->getCustomerData()['customer'] as $key => $value) {
			if ( strpos( $key, 'billing_' ) !== false ) {
				$this->updateOrderData( [
					$key => $value,
				] );
			}
		}

		// Add delivery instructions
        if ( isset( $_POST[ 'order_comments' ] ) && ! empty( $_POST[ 'order_comments' ] ) ) {
			WP99234()->_customer->order->updateOrderData( [
				'shipment' => [
					'delivery_instructions' => $_POST[ 'order_comments' ],
				]
			] );
        }
	}

	public function setOrderDiscount()
	{
		$couponDescription = null;

        foreach ( $this->getOrder()->get_items('coupon') as $key => $item ) {
			$couponObject = new \WC_Coupon( $item['name'] );
			$this->_discount += $item['discount_amount'];
			$this->_discountTitle .= $item['name'] . ' | ';
			$couponDescription .= $couponObject->get_description() . ' | ';
		}

		$orderlines = WP99234()->_customer->order->getOrderData()['order']['orderlines'];

        if ( $this->_discount ) {
			$orderlines[] = [
				'name' => rtrim( $this->_discountTitle, ' | ' ),
				'price' => -$this->_discount,
				'product_id' => 50
			];

			WP99234()->_customer->order->updateOrderData( [
				// added for the "birthday coupon" functionality.
				'coupons' => rtrim( $this->_discountTitle, ' | ' ),
				'description' => rtrim( $couponDescription, ' | ' ),
				'orderlines' => $orderlines,
			] );
		}
	}

	public function getOrderDiscount()
	{
		return $this->_discount;
	}

	public function updateOrderData( array $updatedData )
	{
		$this->_orderData[ 'order' ] = array_replace_recursive( $this->_orderData[ 'order' ], $updatedData );
	}

	public function getOrderData()
	{
		return $this->_orderData;
	}

	public function getShippingMethod()
	{
        $shipping = array_values( $this->_order->get_shipping_methods() );

		return $shipping ? explode( ':', $shipping[0]->get_method_id() )[0] : null;
	}

	public function getShippingCost()
	{
		// array_values returns null if called on an empty array
        $shipping_methods = array_values( $this->_order->get_shipping_methods() );

		return $shipping_methods ? $shipping_methods[0]->get_total() : null;
	}

	public function shipToDifferentAddress()
	{
		$this->_shipping = 'shipping';
		$this->updateOrderData( [
			'shipment' => [
				'name' => $this->_order->get_shipping_first_name() . ' ' . $this->_order->get_shipping_last_name(),
				'delivery_address' 	=> $this->_order->get_shipping_address_1(),
				'delivery_suburb' 	=> $this->_order->get_shipping_city(),
				'delivery_postcode' => $this->_order->get_shipping_postcode(),
				'delivery_state' 	=> $this->_order->get_shipping_state(),
				'delivery_country' 	=> WC()->countries->countries[ $this->_order->get_shipping_country() ],
				'same_billing'		=> false,
			]
		] );

		if ( ( $this->_order->get_billing_first_name() !== $this->_order->get_shipping_first_name() ) ||
			( $this->_order->get_billing_last_name() !== $this->_order->get_shipping_last_name())) {

			$this->_orderData['order']['fname']        = $this->_order->get_shipping_first_name();
			$this->_orderData['order']['lname']        = $this->_order->get_shipping_last_name();
		}

		if ($this->_order->get_billing_company() !== $this->_order->get_shipping_company()) {
			$this->_orderData['order']['company_name']       = $this->_order->get_shipping_company();
			$customer_data['customer']['company_name'] = $this->_order->get_shipping_company();
		}

	}

	public function getShipping()
	{
		return $this->_shipping;
	}

	public function fetchCCToken( bool $attachCCDetails = false, bool $isGuest = true, $endpoint, $method )
	{
		define( 'WP99234_GUEST_CC_DETAILS', $isGuest );
		$apiData = WP99234()->_customer->getCreditCardDetails();

		if ( $attachCCDetails ) :
			WP99234()->_customer->updateCustomerData( WP99234()->_customer->getCreditCardDetails() );
			$apiData = WP99234()->_customer->getCustomerData()['customer'];
		endif;

		if ( $isGuest ) :
			// This is what we are attaching to the order.
			WP99234()->_customer->updateCustomerData( [
				'cc_name' => '',
				'cc_number' => '',
				'cc_exp_month' => '',
				'cc_exp_year' => '',
				'cc_cvv' => '',
			] );
		endif;

		try {
			$result = WP99234()->_api->_call( $endpoint, [
				'customer' => $apiData,
			], $method );

			if ( isset( $result->id ) && isset( $result->cc_token ) && ! empty( $result->cc_token ) ) {
				$this->updateCCToken( $result->cc_token );
			} else {
				throw new \Exception( __( 'Could not update user and order processing has failed.', 'troly' ) );
			}

		} catch( \Exception $e ) {
			echo $e->getMessage();

			ob_start();
			$errs = ob_get_contents();
			ob_end_clean();
			WP99234()->logger->error($errs);
		}
	}

	private function updateCCToken( $ccToken )
	{
		update_post_meta( $this->getOrder()->get_id(), 'wp99234_cc_token', $ccToken );
	}
}