<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WP99234_Orders
 */

class WP99234_Orders {

	public $orders_endpoint;

	public function __construct() {
		$this->orders_endpoint = WP99234_Api::$endpoint . 'orders';
		
		add_action( 'wp_ajax_wp99234_edit_order_ajax_link', [$this, 'wp99234_edit_order_ajax_link'] );
		add_action( 'woocommerce_before_cart', [$this, 'setShippingMethod'] );
	}
	
	private function _single_endpoint($troly_order_id){
		return WP99234_Api::$endpoint . 'orders/' . $troly_order_id .'.json';
	}

	/**
	 * Returns an order from Troly for a given ID
	 */
	public function get_troly_order( $troly_order_id ){
		$troly_order = WP99234()->_api->_call( $this->_single_endpoint($troly_order_id));
		$errs = @(array)$troly_order->errors;

		/*
			Return false if we have errors. Log the errors.
		*/
		if (!empty($errs)) {
			WP99234()->logger->error( 'Failed to retrieve Troly order ' .$troly_order_id, var_export( $troly_order->errors, true ) );
			return false;
		} else {
			return $troly_order;
		}
	}

	/**
	 * Returns if an order from Troly can be edited
	 */
	public function is_troly_order_editable($troly_order){
		return  in_array($troly_order->status, ['draft', 'confirmed']);
	}

	public function setShippingMethod()
	{
		$orderID = isset( $_SESSION['editing-order-wc-order-id'] ) ? $_SESSION['editing-order-wc-order-id'] : false;
		$shippingMethod = isset( $_SESSION['troly_shipping_method'] ) &&
						! empty( $_SESSION['troly_shipping_method'] ) ?
						$_SESSION['troly_shipping_method'] :
						get_post_meta( $orderID, '_troly_order_shipping_method', true );

		if ( ! $shippingMethod || empty( $shippingMethod ) ) return;

		WC()->session->set( 'chosen_shipping_methods', [ $shippingMethod ] );
	}

	/**
	 * Updates carts when orders are placed with Troly
	 * @since 3.0
	 * @package Troly
	*/
	public function wp99234_update_existing_order( $orderID )
	{
		// Fetch existing WooCommerce order.
		$order = new WC_Order( $orderID );
		$products = $order->get_items();

		foreach ( $products as $product ) {
			WC()->cart->add_to_cart( $product[ 'product_id' ], $product[ 'qty' ] );
		}

		// Get discounts on order.
		$coupons = $order->get_items( 'coupon' );
		foreach ( $coupons as $coupon ) {
			if ( ! WC()->cart->has_discount( $coupon[ 'name' ] ) ) {
				WC()->cart->add_discount( $coupon['name'] );
			}
		}

		$fees = $order->get_items( 'fee' );
		foreach ( $fees as $fee ) {
			$_SESSION['wp99234_cart_fees'][] = [
				'name' => $fee[ 'name' ],
				'amount' => $fee[ 'line_total' ],
			];
		}

		return $order;
	}

  	/**
	 * Create carts when orders are placed with Troly
	 * @since 3.0
	 * @package Troly
	*/
	public function wp99234_create_new_order( $wc_order_id, $troly_order )
	{
		// Before committing the order, we need to make sure we have been given everything from Troly
		$data = [
			'billing_address' => $troly_order->billing_address,
			'billing_state' => $troly_order->billing_state,
			'billing_postcode' => $troly_order->billing_postcode,
			'billing_country' => $troly_order->billing_country,
			'billing_email' => null,
			'payment_method' => null,
		];

		$allShippingMethods = WC()->shipping->get_shipping_methods();

		if ( isset( $troly_order->shipment ) ) {
			array_push( $data, [
				'shipping_address' => $troly_order->shipment->delivery_address,
				'shipping_state' => $troly_order->shipment->delivery_state,
				'shipping_postcode' => $troly_order->shipment->delivery_postcode,
				'shipping_country' => $troly_order->shipment->delivery_country,
			] );

			$_SESSION['troly_shipping_method'] = 'wp99234_shipping_method';
		} else {
			foreach ( $allShippingMethods as $shippingMethod ) :
				if ( strpos( $shippingMethod->get_rate_id(), 'local_pickup' ) !== false  ) {
					$_SESSION['troly_shipping_method'] = $shippingMethod->get_rate_id();
					break;
				}
			endforeach;
		}
		
		// Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering.
		WC()->cart->calculate_totals();

		// editing order that already exists on woocommerce
		$order_id = WC()->checkout->create_order( $data );
		
		//This is critical. If we have not got this set, link to Troly!
		update_post_meta( $order_id, 'subs_id', $troly_order->id );

		// Saving this value in meta for "editing exiting order" case.
		update_post_meta( $order_id, '_troly_order_shipping_method', $_SESSION['troly_shipping_method'] );

		return $order_id;
	}

	/*
		Reset our sessions and information
	*/
	public function wp99234_reset_order_session($troly_order){
		/*
			If no session has been set beforehand, we're gonqna need it.
				This check ensures no errors are logged.
		*/
		if(session_status() != PHP_SESSION_ACTIVE){
			session_start();
		}

		/*
			Setup our session data that will link our order data and Troly data
		*/
		$_SESSION['wp99234_cart_fees'] = array();
		$_SESSION['uneditable_products'] = array();
		$_SESSION['composite_subproduct_ids'] = array();
		$_SESSION['apply_membership_discounts'] = (empty($troly_order->batch_order->membership_options->apply_discount) ? true : false);
		$_SESSION['order_min_qty'] = @$troly_order->batch_order->min_qty;

		$_SESSION['composite_non_pre_pack_ids'] = [];
		$_SESSION['composite_non_pre_pack_objs'] = [];
		$_SESSION['composite_pre_pack_ids'] = [];
		$_SESSION['composite_pre_pack_objs'] = [];
	}

	/**
	 * Sets up session data for an order from Troly for handling if an order
	 * is editable. Influences the cart being editable
	 */
	private function wp99234_set_batch_session_data($troly_order){
		if(isset($troly_order->batch_order)){
				$_SESSION['order_can_edit'] = $troly_order->batch_order->ols_customer_editable;
		} else {
			if (isset($troly_order->ols_customer_editable) && $troly_order->ols_customer_editable == 'y') {
				$_SESSION['order_can_edit'] = $troly_order->ols_customer_editable;
				if (isset($troly_order->orderlines[0]) && !$troly_order->orderlines[0]->customer_editable) {
					$_SESSION['order_can_edit'] = 'add_only';
				}
			} else {
				$_SESSION['order_can_edit'] = 'n';
			}
		}
	}

	/**
	 * Handles the setting up of fee data onto the cart and current session
	 */
	private function wp99234_set_fees_for_discount($orderline, $order_action){
		$troly_discount_product_ids = array(50, 51, 52, 53, 54);
		if(in_array($orderline->product_id, $troly_discount_product_ids)){

			/*
				This IF statement cannot be included in the if statement above because we want the above
				condition to be true for both order action types regardless but only want to add fees if its a token order.
			*/
			if ($order_action == 'edit-order-token') {
				$discount_name = 'Discount';

				if ($orderline->product_id == 52) {
					$discount_name = 'Membership Prepayment';
				}

				$_SESSION['wp99234_cart_fees'][] = array('name' => $discount_name, 'amount' => ((float) $orderline->price));
			}

			// If the ID is in the array, we must return true
			return true;
		} else {
			return false;
		}
	}

	/*
		Deletes references in Troly, moves the order to Trash.
		Straight-deleting orders would not work out well, so moving
		to trash can allow for diagnosis later on, if needed.
	*/
	public function wp99234_trash_order($order_id){
		update_post_meta( $order_id, 'subs_id', null );
		wp_trash_post( $order_id );
	}

	public function wp99234_edit_order_check($order_action = 'edit-order', $troly_order, $wc_order_id = 0) {
		/* Access the global WordPress DataBase variable */
		global $wpdb;
	
		/*
			In the event the order is editable from Troly but not in WooCommerce,
			and the workflow needs to allow the user to reset their order,
			we need to delete the link between this order and Troly.
		*/
		if($order_action == 'edit-order-reset'){
			$this->wp99234_trash_order($wc_order_id);
			$order_action = 'edit-order-token';
			$wc_order_id = 0;
		}

		/*
			In the event the customer closes a tab with the cart open, then click the link in the email again,
			the order may have been created in WooCommerce (because of this plugin) BUT nothing has changed.

			In this specific use case, we need to show the cart page with the order, but drop the specific
			order in the backend to remove any stale data
		*/
		if (empty($_SESSION['editing-order']) || $wc_order_id == 0 || $_SESSION['editing-order-wc-order-id'] != $wc_order_id) {

			// Track errors
			$error = false;

			/*
				As this function is only called from the api-orders.php file, the session information
				and cart information has been destroyed and recreated

				Setup our session variables used to map cart contents around
				and hold into composite products
			*/
			$this->wp99234_reset_order_session($troly_order);

			/*
				To determine if an order is editable, it is incumbent we look at the batch order.
				If I have a logic of "Open Pack" and "Editable Order", our logic of
			
				(order editable == 'y') && (first orderline editable == false)
			
				triggers add_only mode which is wrong!
			*/

			$this->wp99234_set_batch_session_data($troly_order);

			/* Setup the list of composite products we have in this order */
			$composite_products = $this->_build_composite_products_session_data($troly_order);

			/*
				For all products / orderlines on the order, check if the product id
				is one of the constant discount ids from Troly, and add as a 'negative'
				fee if it is, negative will act as a discount to the order total.
				Else find the product and add it to cart or throw an error if it doesn't exist
				
				We only add fees or items to cart if the order does not already exist in woocommerce
				because the woocommerce order may have been edited and not synced back to Troly yet.
			*/
			
			$open_pack_ids = [];
			$closed_pack_ids = [];
			$remove_stock_names = [];
			foreach ($troly_order->orderlines as $orderline) {
				$cart_key = null;
				$product_id = null;
				if (!$this->wp99234_set_fees_for_discount($orderline, $order_action)) {
					$res = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta INNER JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.id = {$wpdb->prefix}postmeta.post_id WHERE {$wpdb->prefix}postmeta.meta_key = 'subs_id' AND {$wpdb->prefix}postmeta.meta_value = %s AND {$wpdb->prefix}posts.post_type = 'product' ORDER BY post_id DESC LIMIT 1", $orderline->product_id));

					$product_id = (isset($res) ? $res->post_id : null);

					if ($product_id) {
						if (
							isset($orderline->composite_product_id) && 
							$orderline->composite_product_id != $orderline->product_id && 
							empty($composite_products[$orderline->composite_product_id]->split_ols)
						) {
							$_SESSION['composite_subproduct_ids'][] = $product_id;
						} else {
							/* We are on a composite product orderline from Troly */
							if (isset($orderline->composite_product_id) && $orderline->composite_product_id == $orderline->product_id) {
								if($orderline->display_only) {
									array_push($open_pack_ids, $orderline->product_id);
									/* For open packs, we must only show it during a club run (at this stage) and not allow editing */
									if ($order_action == 'edit-order-token') {
										array_push($_SESSION['composite_non_pre_pack_ids'], (int)$product_id);
										/* Add to cart */
										$cart_key = WC()->cart->add_to_cart($product_id, $orderline->qty);
									}
								} else {
									/* For closed packs, we mark subproducts as 'uneditable' as Wordpress will only ever know about the pack itself */
									if ($order_action == 'edit-order-token') {
										$cart_key = WC()->cart->add_to_cart($product_id, $orderline->qty);
									}
									$_SESSION['composite_pre_pack_ids'][] = $product_id;
									$_SESSION['composite_pre_pack_objs'][$product_id] = array('orderline_id'=>$orderline->id, 'pack_qty'=>intval($orderline->qty));
									$closed_pack_ids[] = $orderline->product_id;
								}
							} else {
								if (isset($_SESSION['order_can_edit']) && $_SESSION['order_can_edit'] != 'y') {
									if(in_array($orderline->composite_product_id, $open_pack_ids) && $orderline->customer_editable===false)
										$_SESSION['composite_non_pre_pack_objs'][$product_id] = array('orderline_id'=>$orderline->id, 'pack_qty'=>intval($orderline->qty));
								}

								if ($order_action == 'edit-order-token' && !in_array($orderline->composite_product_id, $closed_pack_ids)) {
									$cart_key = WC()->cart->add_to_cart($product_id, $orderline->qty);
								}
							}
						}
						if($orderline->customer_editable === false){
							$_SESSION['uneditable_products'][] = $product_id;
						}
					} else {
						$cart_key = false;
					}
				}

				/*
					In the event we have attempted to add to cart, if WooCommerce
					sees no stock on that specific product, it will not add the product.
					So, we need to import this product as a one-off.

					Whilst we don't be showing this on the website, it must exist in WC for
					cart functionality to work correctly.
				*/
				if($cart_key === false){
					$product = WP99234()->_products->handle_single_import_from_troly($orderline->product_id, true);
					if($product !== false){
						$cart_key = WC()->cart->add_to_cart($product_id, $orderline->qty);
					}
					/* Something went bang, and we cannot add this product. Halt the process and
					show an error message */
					if($cart_key === false){
						WP99234()->_woocommerce->reset_entire_session();
						wc_add_notice("Failed to create order and set cart", 'error');
						wp_redirect(home_url());
						return false;
					} else {

						/*
							The single import method will reset the stock status back to "out of stock"
							to ensure that we do not leave the product is In Stock if disabled
						*/
						if($product->get_stock_status() == 'outofstock'){
							$remove_stock_names[] = $product;
						}
					}
				}
			}

			/*
				If we are editing a woocommerce order that already exists, 
				or if we've come from a token edit order link
				and the order does not yet exist
			*/
			$return_order = null;
			if ($order_action == 'edit-order' && !empty($wc_order_id) && $wc_order_id > 0) {
				$return_order = $this->wp99234_update_existing_order($wc_order_id);
			} else if ($order_action == 'edit-order-token') {
				// token order from Troly
				$_SESSION['editing-order-troly-id'] = $troly_order->id;
				$return_order = $this->wp99234_create_new_order($wc_order_id, $troly_order);
			}

			if (!$error) {
				$_SESSION['editing-order'] = 1;
				$_SESSION['editing-order-wc-order-id'] = $wc_order_id;

				/*
					Remove the "could not add to stock" notices when adding items to cart.

					The "Sorry <product> is not in stock" message will still show and cause issues at checkout.

					This little snippet is to prevent issues with showing too many error messages.
				*/
				if(count($remove_stock_names) > 0){
					foreach($remove_stock_names as $product){
						$notices = WC()->session->get('wc_notices', array());
						foreach( $notices['error'] as $idx => &$error_notices){
							foreach( $error_notices as &$notice){
								if(is_array($notice))
									continue;
								if( strpos( $notice, $product->get_name() ) !== false && strpos($notice, 'stock') !== false){
									unset( $notices['error'][$idx] );
								}
							}
						}
						if(empty($notices['error'])){
							unset($notices['error']);
						}
						if(empty($notices)){
							$notices = null;
						}
						WC()->session->set('wc_notices', $notices);

						// The only way we ended up in the list was being out stock to begin with
						// Order created, now put the stock level back
						$product->save();
					}
				}
				
				return $return_order;
			}
		}

		return false;
	}

	private function _build_composite_products_session_data($troly_order){
		$composite_products = [];
		global $wpdb;
		/* Loop over our orderlines to create the cart */
		foreach ($troly_order->orderlines as $orderline) {
			if (isset($orderline->composite_product_id) && $orderline->composite_product_id == $orderline->product_id) {
				$res = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta INNER JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.id = {$wpdb->prefix}postmeta.post_id WHERE {$wpdb->prefix}postmeta.meta_key = 'subs_id' AND {$wpdb->prefix}postmeta.meta_value = %s AND {$wpdb->prefix}posts.post_type = 'product' ORDER BY post_id DESC LIMIT 1", $orderline->product_id));

				/*
					In the event that the product is not in Troly here,
					we need to import it
				*/
				if(!$res){
					$product = WP99234()->_products->handle_single_import_from_troly($orderline->product_id, true);
					if($product !== false){
						$product_id = $product->get_id();
					}
				} else {
					$product_id = $res->post_id;
				}

				/*
					A product ID being present will mean we can continue here
				*/
				if ($product_id) {

					$endpoint = sprintf( '%sproducts/%s', WP99234_Api::$endpoint, $orderline->product_id );

					$composite_products[$orderline->product_id] = WP99234()->_api->_call( $endpoint );
				} else {
					self::wp99234_order_status_error_notice($troly_order->status);
					wp_redirect(home_url());
					return;
				}
			}
		}
		return $composite_products;
	}

  /**
	 * Message for non-editable orders
	 * @since 2.9
	 * @package Troly
	 */
  private static function wp99234_order_status_error_notice($status)
  {
	switch ($status) {
		case "paid":
			$message = "The order you attempted to modify is in progress and cannot be edited";
			break;
		case "completed":
			$message = "The order you attempted to modify is fulfilled and can no longer be edited";
			break;
		case "cancel":
			$message = "The order you attempted to modify is cancelled and cannot be edited";
			break;
		default:
			$message = null;
	}

	if ($message) {
		WP99234()->_admin->add_notice( __( $message, 'wp99234' ), 'error' );
	}
  }

    /**
     * Get the cheapest price of the Product(s) in the Cart
     * @since 2.9
     * @package Troly
     */
    public function cart_troly_member_save_amount( $currentMembership = 'all' )
    {
        $cart_items = WC()->cart->get_cart();
		$discountAmount = 0;

		foreach ( $cart_items as $value ) {
            $wc_product = new WC_Product($value['product_id']);
			$product_prices = WP99234()->_prices->get_membership_prices_for_product( $wc_product->get_id(), $currentMembership );
            $sorted_price = array();

			foreach($product_prices as $row) {
                $sorted_price[$row->price_id] = (double)$row->price;
			}
			asort($sorted_price);

			$member_discount = (isset(array_values($sorted_price)[0]) ? array_values($sorted_price)[0] : 0);

			if ( is_user_logged_in() ) {
				$discountAmount += $value['quantity'] * ( $wc_product->get_regular_price() - $member_discount );
			} else {
				$discountAmount += $value['quantity'] * ( $wc_product->get_price() - $member_discount );
			}
        }

        return $discountAmount;
    }

	public function wp99234_edit_order_ajax_link() {
		echo json_encode(array('success' => WP99234()->_orders->wp99234_edit_order_check($_POST['order_action'], $_POST['order_id']), 'redirect_url' => wc_get_cart_url()));

		wp_die();
	}

}
?>