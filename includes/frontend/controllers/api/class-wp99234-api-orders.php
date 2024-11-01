<?php


class WP99234_Api_Orders extends WP99234_Api_Server {

	/**
	 * Object Constructor
	 */
	function __construct(){

		define( 'WP99234_INVALID_SUBS_CUST_ID', __( 'Invalid Subs Customer ID', 'wp99234' ) );

		$this->accepted_methods = array( 'PUT', 'GET' );

		parent::__construct();

	}

	/**
	 * Serve the current request.
	 *
	 * @param $route
	 */
	function serve_request( $route ){

		/*
		  When we are on a token_edit_order or edit_order link
		  we don't need to know about it beyond this point.

		  Shift the array to remove anything should not be there
		*/
		array_shift( $route );

		if( $this->method == 'PUT' ){
			$this->response = $this->update();
			$this->respond();
		}

		if( $this->method == 'GET' ){
			$this->response = $this->get( $route );
			$this->respond();
		}

		WP99234()->logger->error( 'Unable to serve request. No method found.' );
		$this->errors[] = WP99234_INVALID_REQUEST;
		$this->respond();

	}


	/**
	 * Update an order status based on the given data.
	 *
	 * @return array 'order' => WC_Order
	 */
	public function update()
	{
		if ( $this->method !== 'PUT' ) {
			$this->errors[] = WP99234_INVALID_REQUEST;
			$this->respond();
		}

		$request_data = $this->body;
		$trolyOrderID = $request_data->order->id;
		$reporting_options = get_option( 'wp99234_reporting_sync' );

		wp99234_log_troly( 1, 2, 1, 'Received Order ' . $trolyOrderID . ' from Troly' );

		$order = WP99234()->_woocommerce->get_order_by_subs_id( $trolyOrderID );

		if ( ! $order ) {
			wp99234_log_troly( 0, 2, 1, 'Order ' . $trolyOrderID . ' does not exist in WooCommerce. Skipping updates until customer attempts to edit on this site.' );

			return;
		} else {
			wp99234_log_troly( 1, 2, 1, 'Order ' . $trolyOrderID . ' found in WooCommerce as order '. $order->get_order_number() . '. Order status will be amended.' );
		}

		$subs_status = $request_data->order->status;

		$_map = [
			'draft'       => 'pending',
			'confirmed'   => 'pending',
			'in-progress' => 'processing',
			'completed'   => 'completed',
			'cancelled'   => 'cancelled',
			'template'    => 'completed'
		];

		if ( isset( $_map[ $subs_status ] ) ) {
			$order->update_status( $_map[$subs_status ] );
		}

		if ($reporting_options == 'verbose' || $reporting_options == 'minimum') {
			wp99234_log_troly( 1, 2, 1, 'Order successfully updated.' );
		}

		return [ 'order' => $order ];
	}

	/**
	 * Retrieves the order object based on the SUBS ID (not implemented)
	 * or redirect to order edit
	 *
	 * @param $route
	 *
	 * @return mixed
	 */
	function get( $route ){
		$troly_order_id = $_GET['troly_order_id'];
		$token_edit_order = $_GET['token_edit_order'];

		if ($troly_order_id && $token_edit_order) {

			// If a previous user was logged in then terminate the session
			if (is_user_logged_in()) {
				wp_logout();
				// Also reset anything WooCommerce related
				WP99234()->_woocommerce->reset_entire_session();
			}

			/*
				The only orders that can be edited on Troly are ones with a status of 'draft'
				or 'confirmed'. If we have an existing WC order, then we need to cancel the order
				in question. This applies at all times due to WC not allowing orders to be
				editable.

				That said, if an order is not editable in Troly, we still need to show the cart
				page to alert the user to the fact that the order cannot be changed.

				Orders are always created in WooCommerce when clicking on the link from the Troly
				email.

				Before we start, reset the session information due to the above logout process
				now being completed.

				0. Find the order status from Troly
				Troly is the source of truth, so we can always ignore WooCommerce
				*/
			$troly_order = WP99234()->_orders->get_troly_order($troly_order_id);
			if($troly_order === false){
				wc_add_notice("The order you attempted to modify can no longer be edited", 'error');
				wp_redirect(home_url());
				return;
			}

			if(WP99234()->sign_user_in($troly_order->customer_id)){
				// ^ User session was created
				
				/* 
					1. Find an existing order in WooCommerce
				*/
				$wc_order = WP99234()->_woocommerce->get_order_by_subs_id( $troly_order_id );

				/*
					2. Check if Troly lets us say the order is editable
				*/
				$troly_editable = WP99234()->_orders->is_troly_order_editable($troly_order);

				/*
					3. 	If the order is not editable, enforce this on the order in WC and redirect the user
						If the order can be changed, cancel the order in question, unlink it and then
						populate the cart with order contents from Troly

						Reset their cart to be completely sure if this the case
				*/
				if(!$troly_editable && $wc_order !== false){
					WC()->cart->empty_cart();
					wc_add_notice("The order you attempted to modify can no longer be edited", 'error');
					wp_redirect(home_url());
					return;
				}

				if($troly_editable && $wc_order !== false){
					$wc_order->set_status('cancelled', 'User clicked link from email to edit order. As WooCommerce does not allow orders to be edited, this order has been cancelled and will be recreated. The order in Troly is not affected.');
					$wc_order->save();
					WP99234()->_orders->wp99234_trash_order($wc_order);
				}

				/*
					If the order has been marked as editable in Troly but not in WooCommerce, it means we now have to cancel the current
					order in WooCommerce, unlink it, and let the process run to assume that the order is, in fact, a "new" order.

					This is because WooCommerce does not allow orders that have been placed to be changed once again.

					So, the workflow can look like 3 ways:

					User clicks link ---> WC reports it as editable ---> check with Troly ---> Troly reports order editable --> dupe old order from Woo, migrate subs_id across
					User clicks link ---> WC reports it as non-editable ---> check with Troly ---> Troly reports order editable --> dupe old order from Woo, migrate subs_id across
					User clicks link ---> WC reports it as non-editable ---> check with Troly ---> Troly reports order non-editable --> display notice indicating order finalisation
					
					Create the cart from the existing order in WooCommerce (any changes would have been pushed from Troly already)
					Cancel the previous order from Troly
					Remove subs_id from this order
					Let the process proceed as normal from this point
					
					(Overwrite $wc_order as we only care about the new order now)
				*/
				$existing_wc_order_id = ($wc_order === false ? 0 : $wc_order->get_order_number());
				$wc_order = WP99234()->_orders->wp99234_edit_order_check('edit-order-reset', $troly_order, $existing_wc_order_id);

				// Redirect to order details page
				if ($wc_order !== false) {
					/* 
						If our cart contents are empty, it means our order cannot be edited
						and WooCommerce has deemed it ready for payment.
					*/
					if(WC()->cart->get_cart_contents_count() == 0){
						wp_redirect(get_permalink( woocommerce_get_page_id( 'pay' )));
					} else {
						// This is the default
						wp_redirect(wc_get_cart_url());
					}
				} else {
					wp_redirect(get_permalink(wc_get_page_id('myaccount')) . '/orders');
				}
				// Always stop here due to wp_redirect()
				return;

			} else {
				// The user does not exist in Wordpresses' database.
				// Redirect them back to the homepage
				wp_redirect(home_url());
				return;
			}
		} else {
			$subs_id = (int)$route[0];

			$order = WP99234()->_woocommerce->get_order_by_subs_id( $subs_id );

			if( ! $order ){
				$this->errors[] = __( 'Order was not found', 'wp99234' );
				$this->respond();
			}

			return $order;
		}
	}
}
