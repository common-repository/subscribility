<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * (SEB)
 * The name "Endpoint" Seems tremendously confusing here...
 * I believe it shoudl change to someting
 * more apprioriate.
 */
class WP99234_WC_Filter {

	var $order_api_endpoint;
	var $payment_api_endpoint;

	function __construct(){

		$this->setup_actions();
		$this->setup_filters();

        $this->order_api_endpoint = WP99234_Api::$endpoint . 'orders.json'; /** why does this connect to orders? should this not be generic? **/
		$this->payment_api_endpoint = WP99234_Api::$endpoint . 'orders/'; /* Requires an order id for PUT */

		// @todo Need to be fixed. PSR-4 auto-loading recommended.
		require_once 'UserFiltersController.php';
		require_once 'class-wp99234-gift-orders.php';

		( new UserFiltersController );
		( new TrolyGiftOrders );
    }

	/**
	 * Setup Woocommerce specific actions.
	 */
	function setup_actions(){

		//woocommerce_checkout_init // 10/03/17 no longer being used but the hook reference is good so keeping it commented out.
		//add_action( 'woocommerce_checkout_init', array( $this, 'on_woocommerce_checkout_init' ) );

		add_filter( 'woocommerce_register_shop_order_post_statuses', [$this, 'registerTrolyMemberOrderStatus'] );
		add_filter( 'wc_order_statuses', [$this, 'templateOrderStatus'] );

		//Disable functionality for unentitled users.
		add_action( 'load-edit.php', array( $this, 'load_edit_page' ) );
		add_action( 'load-post.php', array( $this, 'load_post_page' ) );
		add_action( 'load-post-new.php', array( $this, 'load_post_new_page' ) );

		///Disable Woocommerce Emails.
		//add_action( 'woocommerce_email', array( $this, 'disable_emails' ) );

		//woocommerce_admin_order_actions_end
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'after_order_actions' ), 10, 1 );

		//admin_init
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'woocommerce_init', [ $this, 'woocommerce_init' ] );

		//check_wp99234_payment_status
		// add_action( 'wp_ajax_check_wp99234_payment_status', array( $this, 'check_wp99234_payment_status' ) );

		add_action( 'wp_insert_comment', array( $this, 'handle_ratings_and_reviews' ), 10, 2 );

		// Actions to be done on cart update
		add_action( 'woocommerce_cart_updated',                     array( $this, 'wp99234_store_cart_timestamp' ), 100 );
		add_action( 'woocommerce_add_to_cart',                      array( $this, 'wp99234_store_cart_timestamp' ), 100 );
		add_action( 'woocommerce_cart_item_removed',                array( $this, 'wp99234_store_cart_timestamp' ), 100 );
		add_action( 'woocommerce_cart_item_restored',               array( $this, 'wp99234_store_cart_timestamp' ), 100 );
		add_action( 'woocommerce_after_cart_item_quantity_update',  array( $this, 'wp99234_store_cart_timestamp' ), 100 );

        // Action to display message for membership discount
        if ( get_option( 'troly_club_membership_signup' ) !== '' ) {
			add_action( 'woocommerce_before_cart_table', [$this, 'displayClubMemberNotice'] );
			add_action( 'woocommerce_before_checkout_form', [$this, 'displayClubMemberNotice'] );
		}

		// Action to validate credit card
		add_action( 'woocommerce_after_checkout_validation',        array($this, 'wp99234_validate_credit_card_checkout'), 10, 2 );

		// Account details error handling
		add_action('woocommerce_save_account_details_errors', array($this, 'wp99234_validate_credit_card_account_details'), 10, 1);

		if ( 'checkout' === get_option( 'troly_require_dob' ) ||
			'both' === get_option( 'troly_require_dob' ) ) {
				add_action( 'woocommerce_checkout_fields', [$this, 'addDOBField'] );
				add_action( 'woocommerce_after_order_notes', [$this, 'wp99234_show_disclaimer_and_apply_datepicker'] );
				add_action('woocommerce_checkout_update_order_meta', [$this, 'update_order_meta_birthday'] );
		}

		add_action( 'woocommerce_admin_order_data_after_order_details', [$this, 'show_custom_subs_fields'], 10, 1 );
		add_action( 'woocommerce_edit_account_form', [$this, 'wp99234_add_billing_inputs_to_account_menu'], 0 );
		add_filter( 'woocommerce_my_account_my_orders_actions', [$this, 'wp99234_add_my_account_order_actions'], 10, 2 );
		add_action( 'woocommerce_after_account_orders', [$this, 'wp99234_my_account_edit_order_link'], 10, 1 );
		add_action( 'woocommerce_before_calculate_totals', [$this, 'wp99234_manipulate_cart_prices'], 10, 1 );
		add_filter( 'woocommerce_cart_item_remove_link', [$this, 'wp99234_validate_cart_remove_item'], 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', [$this, 'wp99234_validate_cart_quantity_input'], 10, 3 );
		add_filter( 'woocommerce_update_cart_validation', [$this, 'wp99234_validate_cart_quantity_update'], 10, 4 );
		add_filter( 'woocommerce_add_to_cart_validation', [$this, 'wp99234_validate_add_to_cart'], 10, 3 );
		add_action( 'woocommerce_check_cart_items', [$this, 'wp99234_enforce_minimum_quantity'], 10 );
		add_action( 'woocommerce_cart_calculate_fees', [$this, 'wp99234_add_cart_fees'], 10, 1 );
		//add_action( 'troly_order_status_check', [$this, 'check_wp99234_payment_status' ], 10, 1 );
		//add_action( 'woocommerce_order_status_cancelled' , [$this, 'removeOrderStatusCheckCRON'], 10 );
		add_filter( 'woocommerce_is_purchasable', [$this, 'restrictMembersOnlyProducts'], 20, 2 );
		add_filter( 'woocommerce_post_class', [$this, 'addMembersOnlyClass'], 20, 2 );
		add_filter( 'woocommerce_login_redirect', [$this, 'trolySourceRedirect'], 10, 2 );
		add_action( 'wp_loaded', [$this, 'applyReferralCoupon'], 100 );
		add_action( 'woocommerce_removed_coupon', [$this, 'removeReferralCoupon'], 100 );
		add_filter( 'woocommerce_cart_totals_coupon_label', [$this, 'referralCouponLabel'] );
		add_action( 'woocommerce_order_status_processing', [$this, 'exportPaymentToSubs' ], 10, 1 );
		add_filter( 'woocommerce_get_price_html', [$this, 'trolyProductPageUpsell'], 20, 2 );
		add_action( 'wp_loaded', [$this, 'createTemplateOrder'], 20 );
		add_action( 'init', [$this, 'templateOrderCartCustomization'], 20 );
		add_action( 'init', [$this, 'templateOrderThankyouCustomization'] );
	}

	/**
	 * Add a custom text when referral coupon code is applied.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @param string $text
	 * @return string $text
	 */
	public function referralCouponLabel( $text )
	{
		// Only change the text when referral code is used.
		if ( WC()->session->get( 'troly_referral_code_used' ) ) {
			$text = __( 'Referral Coupon Applied', 'troly' );
		}

		return $text;
	}

	/**
	 * Remove the session based reference of the referral coupon.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @return void
	 */
	public function removeReferralCoupon()
	{
		WC()->session->__unset( 'troly_referral_code_used' );
	}

	/**
	 * Apply the "referral coupon" to the cart if a valid "referral code" is provided.
	 *
	 * @todo maybe fix this.
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @return void
	 */
	public function applyReferralCoupon()
	{
		if ( is_admin() || ! isset( $_POST['coupon_code'] ) || empty( $_POST['coupon_code'] ) ) return;

		$referralCoupon = WP99234()->_customer->getReferralCoupon();
		$referralCode = $_POST['coupon_code'];

		if ( WP99234()->_customer->validateReferralCode( $referralCode ) && $referralCoupon ) {
			WC()->cart->apply_coupon( $referralCoupon );
			wC()->session->set( 'troly_referral_code_used', $referralCode );

			wc_print_notices();
			// We just want to do the coupon code validation and nothing more (like revalidating the cart items, cart total, showing admin notices etc.)
			// since wp_loaded is a very early hook in WordPress.
			// Bail out.
			wp_die();
		}

		// Check if the coupon is removed from the cart.
		if ( ! in_array( $referralCoupon, WC()->cart->get_applied_coupons() ) ) {
			wC()->session->__unset( 'troly_referral_code_used' );
		}
  }

  /**
	 * Send payment information to Troly once an order is paid or refunded
	 * in WordPress that does not use the Troly gateway.
	 *
	 * @param int $orderID
	 * @return void
	 */
	public function exportPaymentToSubs( $orderID ) {
		WP99234()->_woocommerce->export_payment( $orderID );
	}

	/**
	 * Display upsell for club membership by showing cheapest product price.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @param string $price_html
	 * @param object $product
	 * @return string $priceHTML
	 */
	public function trolyProductPageUpsell( $priceHTML, $product )
	{
		if ( ! is_admin() && 'yes' === get_option( 'troly_show_member_price' ) ) {
			if ( ! is_user_logged_in() || empty( get_user_meta( get_current_user_id(), 'current_memberships', true ) ) ) {

				$membershipPrices = WP99234()->_prices->get_membership_prices_for_product($product->get_id(), 'all');
				$sortedPrice = array();

				foreach( $membershipPrices as $price ) {
					$sortedPrice[ $price->price_id ] = $price->price;
				}

				asort( $sortedPrice );

				// Take the first one.
				$cheapestPrice = get_woocommerce_currency_symbol() . number_format( current( $sortedPrice ), 2 );
				$priceHTML .= '<span class="wp99234-product-upsell"><span>'. __( 'Member:', 'troly' ) . '</span> '. $cheapestPrice .'</span>';

			}
		}

		return $priceHTML;
	}

	public function templateOrderTotalCustomization( $price )
	{
		return "{$price}*";
	}

	public function templateOrderThankyouCustomization()
	{
		if ( isset( $_GET['troly_member_future_order'] ) ) {
			$orderID = $_GET['troly_member_future_order'];

			$order = wc_get_order( $orderID );

			if ( ! $order ) return;

			if ( $order->has_status( 'member-order' ) ) {
				add_action( 'woocommerce_endpoint_order-received_title', [$this, 'templateOrderReceivedTitle'], 20 );
				add_filter( 'woocommerce_thankyou_order_received_text', [$this, 'templateOrderReceivedText'], 20 );
			}
		}
	}

	public function templateOrderCartCustomization()
	{
		if ( isset( $_SESSION['troly_new_club_signup'] ) && true === $_SESSION['troly_new_club_signup'] ) {
			add_action( 'template_redirect', [$this, 'preventCheckoutAccess'] );
			add_filter( 'woocommerce_cart_total', [$this, 'templateOrderTotalCustomization'], 20 );
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
			add_filter( 'woocommerce_before_cart_totals', [$this, 'templateOrderCartTitle'], 20 );
			add_action( 'woocommerce_proceed_to_checkout', [$this, 'templateOrderCartButton'], 20 );
		}
	}

	public function registerTrolyMemberOrderStatus( $statuses ) {
		$statuses['wc-member-order'] = [
			'label'                     => 'Troly Member Order',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Troly Member Order <span class="count">(%s)</span>', 'Troly Member Order <span class="count">(%s)</span>' )
		];

		return $statuses;
	}

	public function templateOrderStatus( $statuses ) {
		$statuses['wc-member-order'] = __( 'Troly Member Order', 'woocommerce' );

		return $statuses;
	}

	public function preventCheckoutAccess()
	{
		if ( is_checkout() ) {
			wp_redirect( wc_get_cart_url() );
			exit;
		}
	}

	public function templateOrderReceivedTitle( $oldTitle )
	{
		return 'Thank You';
	}

	public function templateOrderCartButton()
	{
		$checkoutURL = wc_get_cart_url() . '?troly-template-order=true';
		echo '<small>' . __( '* total may vary in case of low stock and some products may be substituted.', 'troly' ) . '</small>
		<a href="' . $checkoutURL . '" class="checkout-button button alt wc-forward">' . __( 'Save your club selection', 'woocommerce' ) . '</a>';
	}

	public function createTemplateOrder()
	{
		if ( is_admin() ) return;

		$validEndpoint = isset( $_GET['troly-template-order'] ) && $_GET['troly-template-order'] === 'true' ? true : false;
		$templateOrderSetting = get_option( 'troly_club_membership_signup', '' ) === 'future_club_purchase_only' ? true : false;
		$newClubMember = isset( $_SESSION['troly_new_club_signup'] ) && $_SESSION['troly_new_club_signup'] === true ? true : false;

		// Don't proceed until ALL the conditions are satisfied.
		if ( ! $validEndpoint || ! $templateOrderSetting || ! is_user_logged_in() || ! $newClubMember ) return;

		$userMembership = WP99234()->_users->get_current_membership();
		$bottleCondition = $userMembership->delivery_size;
		$cartQuantity = WC()->cart->get_cart_contents_count();

		if ( $cartQuantity < $bottleCondition ) {
			wc_add_notice( __( sprintf( 'To proceed, your cart must have at least %d bottles.', $bottleCondition ) , 'troly' ), 'error' );
			wp_redirect( wc_get_cart_url() );
			exit;
		}

		$cartItems = WC()->cart->get_cart();
		$user = wp_get_current_user();
		$address = $this->getCustomerAddress();
		$order = wc_create_order();

		foreach ( $cartItems as $cartItem ) {
			$order->add_product( wc_get_product( $cartItem['product_id'] ), $cartItem['quantity'] );
		}

		$order->set_address( $address, 'billing' );
		$order->set_address( $address, 'shipping' );
		$order->calculate_totals();
		$order->update_status( 'member-order', 'Troly Future Order', true ); // look into this label name

		// Assign user to the order.
		update_post_meta( $order->get_id(), '_customer_user', get_current_user_id() );

		$response = $this->export_order( $order->get_id() );

		$orderKey = $order->get_order_key();
		$returnURL = site_url() . '/checkout/order-received/'.$order->get_id().'?key='.$orderKey.'&troly_member_future_order='.$order->get_id();

		unset( $_SESSION['troly_new_club_signup'] );

		wp_redirect( $returnURL );
		exit;
	}

	public function getCustomerAddress( $type = 'shipping' )
	{
		if ( ! get_current_user_id() ) return;

		$user = wp_get_current_user();
		$userMeta = get_user_meta( $user->ID );
		$return = [
			'email' => $user->user_email,
		];

		foreach ( $userMeta as $metaKey => $meta ) :
			if ( strpos( $metaKey, $type ) !== false ) {
				$returnKey = str_replace( $type . '_', '', $metaKey );
				$return[ $returnKey ] = $meta[0];
			}
		endforeach;

		return $return;
	}

	public function templateOrderCartTitle()
	{
		echo '<h2>Your member selection</h2>';
	}

	/**
	 * Redirects user back to the product they're browsing.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param string $redirect
	 * @param object|WP_User $user
	 * @return string $redirect
	 */
	public function trolySourceRedirect( $redirect, $user )
	{
		if ( $_GET['troly_redirect'] === 'product' && ! empty( $_GET['pid'] ) ) {
			return get_permalink( $_GET['pid'] );
		}

		return $redirect;
	}

	/**
	 * Adds a custom class for a product in a loop and in single product page.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param array $classes
	 * @param object $product
	 * @return array $classes
	 */
	public function addMembersOnlyClass( $classes, $product )
	{
		if ( has_term( 'purchase-by-members-only', 'product_tag', $product->get_id() ) ) {
			$classes[] = 'membersonly';
		}

		return $classes;
	}

	/**
	 * Adds button for non-members on "members only" products to promote
	 * Club Membership signup.
	 *
	 * @todo Need to fix the link for Club Membership signup page.
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	public function becomeClubMemberButton()
	{
		global $post;

		$upsellPageID = WP99234()->template->getUpsellPageID();

		if ( empty( $upsellPageID ) ) {
			_e( '<h5>The Club Membership Signup isn\'t setup by the owner.</h5>', 'troly' );
		} else {
			$upsellPagePermalink = get_permalink( $upsellPageID ) . '?troly_redirect=product&pid=' . $post->ID;
			$loginPagePermalink = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '?troly_redirect=product&pid=' . $post->ID;
			_e( '<p><a href="'. $upsellPagePermalink .'" class="button">Become A Member To Purchase</a></p>', 'troly' );
			if ( ! is_user_logged_in() ) {
				_e( '<p>Or, If already a member, <a href="'. $loginPagePermalink .'">Sign in</a> to purchase.</p>', 'troly' );
			}
		}
	}

	/**
	 * Makes "members only" product non-purchaseable by non-members.
	 * Promotes Club Membership signup.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param boolean $isPurchaseable
	 * @param object $product WC_Product
	 * @return boolean $isPurchaseable
	 */
	public function restrictMembersOnlyProducts( $isPurchaseable, $product )
	{
		if ( has_term( 'purchase-by-members-only', 'product_tag', $product->get_id() ) ) {

			if ( is_user_logged_in() ) {
				$isMember = get_user_meta( get_current_user_id(), 'current_memberships', true );

				// The user is not a member and cannot buy the product.
				if ( ! is_array( $isMember ) || empty( $isMember )) {
					add_action( 'woocommerce_single_product_summary', [$this, 'becomeClubMemberButton'], 20 );
				} else {
					// User is logged in AND a member, so let them buy the product.
					return $isPurchaseable;
				}
			} else {
				add_action( 'woocommerce_single_product_summary', [$this, 'becomeClubMemberButton'], 20 );
			}

			$isPurchaseable = false;
		}

		return $isPurchaseable;
	}

	public function wp99234_add_cart_fees($cart) {

		if ( ! isset( $_SESSION ) ) session_start();

		if ( isset( $_SESSION['wp99234_cart_fees'] ) && ! empty( $_SESSION['wp99234_cart_fees'] ) ) {
			foreach( $_SESSION['wp99234_cart_fees'] as $fee ) {
				$cart->add_fee( $fee['name'], $fee['amount'] );
			}
		}
	}

	/**
	 * ALL CART VALIDATION TO HANDLE CLUB RUN BUSINESS LOGIC.
	 *
	 * @return void
	 */
	public function wp99234_enforce_minimum_quantity() {
		if ( is_checkout() ) {
			$min_qty_met = false;
			$min_qty = 0;

			if ( isset( $_SESSION['order_min_qty'] ) && $_SESSION['order_min_qty'] >= 0 ) {
				$min_qty = $_SESSION['order_min_qty'];
			}
			else if ( ! isset( $_SESSION['editing-order'] )
			&& get_option( 'wp99234_min_order_qty' )
			&& get_option( 'wp99234_min_order_qty' ) > 0 ) {
				$min_qty = get_option('wp99234_min_order_qty');
			}

			// Get the current count of items in the cart
			$current_count = WC()->cart->get_cart_contents_count();

			if ( $current_count <= 0 && $min_qty <= 0 ) {
				/* Nothing required and nothing found */
				$min_qty_met = true;
			} else if ( $current_count > 0 && $current_count < $min_qty ) {
				/* Before proceeding, we need to make sure any closed packs
				have had their counts counted correctly! */
				foreach( WC()->cart->get_cart_contents() as $cart_item ) {
					if ( $min_qty_met ) break;

					if ( in_array( $cart_item[ 'product_id' ], $_SESSION[ 'composite_pre_pack_ids' ] ) ) {
						$endpoint = sprintf( '%sproducts/%s.json', WP99234_Api::$endpoint,get_post_meta($cart_item['product_id'],'subs_id',true));
						$composite_product = WP99234()->_api->_call( $endpoint );
						/* Open packs mean we count +0 as it is a "display only" orderline */
						$current_count += ($composite_product->split_ols ? 0 : $composite_product->subproducts_count);
					}

					$min_qty_met = ( $current_count >= $min_qty );
				}
			} else { $min_qty_met = true; }

			if ( ! $min_qty_met ) {
				wc_clear_notices();
				wc_add_notice( ('You need to purchase a minimum of ' . $min_qty . ' products before checking out your order'), 'error');
				wp_redirect(wc_get_cart_url());
				return;
			}
		}
	}

	/**
	 * Hooking into add_to_cart validation to handle validation from shop page
	 * this stops the user from adding items to cart if they cannot edit from
	 * club run options, or only adding new products if set.
	 *
	 * @param boolean $valid
	 * @param int $product_id
	 * @param int $quantity
	 * @return void
	 */
	public function wp99234_validate_add_to_cart( $valid, $product_id, $quantity ) {

		if (isset($_SESSION['order_can_edit']) && $_SESSION['order_can_edit'] == 'add_only' && isset($_SESSION['uneditable_products']) && !empty($_SESSION['uneditable_products']) && in_array($product_id, $_SESSION['uneditable_products'])) {
				$valid = false;
				// As we now handle adding more items in add_only mode, supress this warning.
			 // wc_clear_notices();
		  // wc_add_notice( 'This product is part of THE standard pack and cannot be modified. However you may add further products to this order.', 'error' );
		  // return false;
		} else if (isset($_SESSION['order_can_edit']) && $_SESSION['order_can_edit'] == 'n') {
		  $valid = false;
		  wc_clear_notices();
		  wc_add_notice( 'This is a standard club pack and cannot be changed. If you wish to order more, please place a separate order.', 'error' );
		  return false;
		}

		return $valid;
	}

	public function wp99234_validate_cart_quantity_update($valid, $cart_item_key, $values, $qty) {
		$cart_item_keys = WC()->cart->get_cart();

		if (isset($_SESSION['order_can_edit']) && $_SESSION['order_can_edit'] == 'n') {
		$valid = false;
		wc_clear_notices();
		wc_add_notice( 'This is a standard club pack and cannot be changed. If you wish to order more, please place a separate order.', 'error' );
		return false;
		}

	  foreach ($cart_item_keys as $key => $cart_item) {
		if ($cart_item_key == $key && isset($_SESSION['order_can_edit']) && $_SESSION['order_can_edit'] == 'add_only' && isset($_SESSION['uneditable_products']) && !empty($_SESSION['uneditable_products']) && in_array($cart_item['product_id'], $_SESSION['uneditable_products']) && $valid) {

				/*
					To prevent our uneditable products from being deleted,
					stop cart changes if the total available number of bottles
					is less than the amount required to successfully pay for the order
				*/
				if(isset($_SESSION['composite_non_pre_pack_objs'][$cart_item['product_id']])){
					if($qty < $_SESSION['composite_non_pre_pack_objs'][$cart_item['product_id']]['pack_qty']){
						$name = wc_get_product($cart_item['product_id'])->get_title();
						wc_add_notice( "To complete this order, you need to purchase at least {$_SESSION['composite_non_pre_pack_objs'][$cart_item['product_id']]['pack_qty']} x $name", 'error' );
						$valid = false;
						return false;
					}
				}

				/*
					To prevent closed packs from being removed, also check that
					the number of closed packs in the cart is the correct number
				*/
				if(isset($_SESSION['composite_pre_pack_objs'][$cart_item['product_id']])){
					if($qty < $_SESSION['composite_pre_pack_objs'][$cart_item['product_id']]['pack_qty']){
						$name = wc_get_product($cart_item['product_id'])->get_title();
						wc_add_notice( "To complete this order, you need to purchase at least {$_SESSION['composite_pre_pack_objs'][$cart_item['product_id']]['pack_qty']} x $name", 'error' );
						$valid = false;
						return false;
					}
				}
			}
		}
		return $valid;
	}

	public function wp99234_validate_cart_remove_item($remove_link, $cart_item_key) {
		$cart_item_keys = WC()->cart->get_cart();

		foreach ($cart_item_keys as $key => $cart_item) {
			if ( $cart_item_key == $key && isset($_SESSION['order_can_edit']) &&
				$_SESSION['order_can_edit'] == 'add_only' &&
				isset($_SESSION['uneditable_products']) &&
				! empty($_SESSION['uneditable_products']) &&
				in_array($cart_item['product_id'], $_SESSION['uneditable_products']) && $remove_link) {

				$remove_link = false;
				//wc_clear_notices();
				//wc_add_notice( 'This product is part of your standard pack and cannot be modified. However you may add further products to this order.', 'error' );
				return false;
			}
		}

		if ( isset( $_SESSION['order_can_edit'] ) && $_SESSION['order_can_edit'] == 'n' ) {
			$remove_link = false;
			// wc_clear_notices();
			// wc_add_notice( 'This is a standard club pack and cannot be changed. If you wish to order more, please place a separate order.', 'error' );
			return false;
		}

		return $remove_link;
	}

	public function wp99234_validate_cart_quantity_input($product_quantity, $cart_item_key, $cart_item) {
		if ( isset( $_SESSION['order_can_edit'] ) && $_SESSION['order_can_edit'] == 'n' ) {
			$remove_link = false;
			// wc_clear_notices();
			// wc_add_notice( 'This is a standard club pack and cannot be changed. If you wish to order more, please place a separate order.', 'error' );
			return $cart_item['quantity'];
		} elseif ( isset($_SESSION['uneditable_products']) && !empty($_SESSION['uneditable_products']) &&
			in_array($cart_item['product_id'], $_SESSION['uneditable_products'])) {
				return $cart_item['quantity'];
		}

		return $product_quantity;
	}

	/*
		Handles the manipulation of prices for certain open-pack
		products to be zero when added to cart.
		
		Does not run inside the admin panel.
	*/
	public function wp99234_manipulate_cart_prices( $cart_object ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ){
			return;
		}
		
		$compositeNonPrePackIds = isset( $_SESSION['composite_non_pre_pack_ids'] ) ? $_SESSION['composite_non_pre_pack_ids'] : [];

		foreach ( $cart_object->get_cart() as $cart_item ) {
			if ( in_array( $cart_item['product_id'], $compositeNonPrePackIds ) ) {
				$cart_item['data']->set_price( 0 );
				$cart_item['data']->set_regular_price( 0 );
			}
		}
	}

	public function wp99234_my_account_edit_order_link($has_orders) {
		if ($has_orders) {
		  ?>

		  <script>

			jQuery(".order-actions > .button.edit").on('click', function(e) {
			  e.preventDefault();

			  var url_arr = jQuery(this).attr('href').split("?");
			  var params = url_arr[1].split("&");

			  var data = {
				action: 'wp99234_edit_order_ajax_link'
			  };

			  params.forEach(function(e) {
				var param = e.split("=");

				data[param[0]] = param[1];
			  });

			  jQuery.post(woocommerce_params.ajax_url, data, function(response) {

				var res = JSON.parse(response);

				if (res['success']) {
				  window.location.href = res['redirect_url'];
				} else {
				  // TODO:: add some kind of error msg
				}
			  });
			});

		  </script>

		  <?php
		}
	}

	/**
	 * WooCommerce my-account override to add "order edit" url for a club run
	 *
	 * @param array $actions
	 * @param object $order
	 * @return void
	 */
	public function wp99234_add_my_account_order_actions( $actions, $order ) {

		if ($order->is_editable() && (empty($_SESSION['editing-order-wc-order-id']) || (!empty($_SESSION['editing-order-wc-order-id']) && $order->get_order_number() != $_SESSION['editing-order-wc-order-id']))) {
			$actions['edit'] = array(
				'url'  => wc_get_cart_url() . '?order_action=edit-order&order_id=' . $order->get_order_number(),
				'name' => __( 'Edit', 'wp99234' ),
			);
		}

		return $actions;
	}

	/**
	 * Add fields to the woocommerce my account 'account details' tab, specifically CC details.
	 *
	 * @return void
	 */
	public function wp99234_add_billing_inputs_to_account_menu() {

		$use_existing_checkbox = '<input type="checkbox" id="wp99234_use_existing_card" name="wp99234_use_existing_card" checked="checked" value="yes" class="wp99234_use_existing_card_checkbox" /> ';

		$existing_card = get_user_meta(get_current_user_id(), 'cc_number', true);

		$cc_name = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">';
		$cc_name .= '<label for="cc_name">Name on card</label>';
		$cc_name .= '<input type="text" maxlength="20" class="woocommerce-Input input-text" name="cc_name" id="cc_name"></p>';

		$cc_number = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">';
		$cc_number .= '<label for="cc_number">Card Number</label>';
		$cc_number .= '<input type="tel" inputmode="numeric" class="woocommerce-Input input-text" name="cc_number" id="cc_number" placeholder="•••• •••• •••• ••••"></p>';

		$cc_exp = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-first">';
		$cc_exp .= '<label for="cc_expiry">Expiry (MM/YY)</label>';
		$cc_exp .= '<input type="tel" inputmode="numeric" class="woocommerce-Input input-text" name="cc_expiry" id="cc_expiry" placeholder="MM / YY"></p>';

		$cc_cvv = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-last">';
		$cc_cvv .= '<label for="cc_cvv">Card Code</label>';
		$cc_cvv .= '<input type="tel" maxlength="4" inputmode="numeric" class="woocommerce-Input input-text" name="cc_cvv" id="cc_cvv" placeholder="CVC"></p>';

		echo '<fieldset class="wc-credit-card-form wc-payment-form wp99234-billing-details"><legend>Credit Card Details</legend>';
		if (is_checkout()) {
			echo "<label for='wp99234_use_existing_card'>" . $use_existing_checkbox . "Use your existing card ($existing_card)</label>";
		} elseif(!empty($existing_card)){
			echo "<label class='wp99234_use_existing_card' for='wp99234_use_existing_card'>" . $use_existing_checkbox . "Click here to change you current credit card ($existing_card)</label>";
		}
		echo '<div id="hidden_cc_form">';
		echo '<p>The details entered here will be stored securely for future use.</p>';
		echo $cc_name;
		echo $cc_number;
		echo $cc_exp;
		echo $cc_cvv;
		echo '</div>';
		echo '</fieldset>';
		echo '<div class="clear"></div>';
	}

	/**
	 * Adds additional Troly-captured information to order screen in wc admin. eg Order Number, Order ID, Birthday, etc.
	 *
	 * @param object $order
	 * @return void
	 */
	public function show_custom_subs_fields( $order )
	{
		$subs_id = get_post_meta( $order->get_id(), 'subs_id', true );
		$subs_order_no = get_post_meta( $order->get_id(), 'subs_order_no', true );
		echo '<h3>&nbsp;</h3><h3>Troly Information</h3>';

		// Show Order no
		if ($subs_order_no) {
			echo '<p><strong>Troly Order No: </strong>' . $subs_order_no . '</p>';
		} else {
			echo '<p><strong>Troly Order ID: </strong>' . $subs_id . '</p>';
		}

		if (get_post_meta( $order->get_id(), '_subs_birthday', true )) {
			echo '<p><strong>'.__('Recorded Birthday').':</strong> ' . get_post_meta( $order->get_id(), '_subs_birthday', true ) . '</p>';
		}

		# If we are returned 0, it is a guest
		if (get_user_meta($order->get_user_id(),'subs_id', true) > 0) {
			echo '<p><a href="' . WP99234_Api::$endpoint . 'c/' . get_user_meta($order->get_user_id(), 'subs_id', true) . '/o/' . $subs_id . '/edit?from=activity" target="_blank">View in Troly</a></p>';
		}
	}

	public function update_order_meta_birthday( $order_id ) {
		if ( ! empty( $_POST['subs_birthday'] ) && get_option('wp99234_display_legal_require_dob', false)) {
			update_post_meta( $order_id, '_subs_birthday', $_POST['subs_birthday'] );
		}
	}

	/*
	* Outputs the disclaimer message from the admin section
	* Activates date picker drop down Javascript
	*/
	public function wp99234_show_disclaimer_and_apply_datepicker(){
		$disclaimer_message = get_option('wp99234_legal_disclaimer_text','By law, we may only supply alcohol to persons aged '.get_option('wp99234_legal_drinking_age').' years or over. We will retain your date of birth for our records.');
		if($disclaimer_message)
			echo '<p>'.nl2br($disclaimer_message).'</p>';

		$limit = (int)(get_option('wp99234_legal_drinking_age'));
		$year = date('Y');

		// Used existing birthday
		$subs_birthday = "";
		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			$subs_birthday = get_user_meta($user_id, 'birthday', true);
		}
		echo '<script>jQuery( document ).ready( function($){$("#subs_birthday").datepicker({maxDate:"-'.$limit.'y",changeYear:true,changeMonth:true,minDate:"-105y",yearRange:"'.($year-105).':'.($year-$limit).'"});$("#subs_birthday").val("' . $subs_birthday . '");});</script>';
	}

	/**
	 * Displays a birthday field and requires them to fill it
	 */
	public function addDOBField( $fields )
	{
		$fields['order']['subs_birthday'] = [
			'type' => 'text',
			'class' => ['my-field-class form-row-wide'],
			'label' => __( 'Date of Birth', 'troly' ),
			'placeholder' => __( 'Date of Birth', 'troly' ),
			'required' => 'required',
			'id' => 'subs_birthday'
		];

		return $fields;
	}

	/**
	 * Setup woocommerce specific filters.
	 */
	function setup_filters(){
		//Remove the 2nd line address from billing and shipping fields to ensure data is compatible with woocommerce.
		add_filter( 'woocommerce_billing_fields', array( $this, 'filter_billing_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'filter_shipping_fields' ) );

		add_filter( 'woocommerce_register_post_type_shop_order', array( $this, 'filter_shop_order_args' ) );
		add_filter( 'woocommerce_register_post_type_product', array( $this, 'filter_product_args' ) );

		//No email classes, No Emails.
		//add_filter( 'woocommerce_email_classes', '__return_empty_array' );

		//woocommerce_get_price
		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_get_price' ), 10, 2 );

		//woocommerce_new_customer_data
		add_filter( 'woocommerce_new_customer_data', array( $this, 'filter_new_customer_data' ) );

		//wc_get_template_part
		add_filter( 'wc_get_template', array( $this, 'filter_wc_get_template' ), 10, 5 );

		// Override for password reset token to expire in 7 days (not 24 hours)
		add_filter('password_reset_expiration', array($this, 'filter_password_reset_expiration'), 10, 1);
		add_filter( 'woocommerce_shipping_methods', [ $this, 'shipping_methods' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'payment_gateways' ] );
	}

	public function templateOrderReceivedText()
	{
		return __( 'Thank you for joining our club. Your selection has been recorded for the next club dispatch.', 'troly' );
	}

	function admin_init(){
		if ( isset( $_GET[ 'export_order_to_subs' ] ) && wp_verify_nonce( $_GET[ '_nonce' ], 'wp99234_export_order' ) ) {
			$order_id = (int)$_GET['export_order_to_subs'];
			$this->export_order( $order_id );
		}
	}

	/**
	 * Bootstraps the custom shipping method class.
	 */
	public function woocommerce_init() {
		include_once( 'class-wp99234-wc-shipping-method.php' );
		include_once( 'class-wp99234-wc-payment-gateway.php' );
	}

	/**
	 * Enforce guest checkout disabled.
	 *
	 * @param $checkout
	 */
	public function on_woocommerce_checkout_init( $checkout ){

		if( $checkout->enable_guest_checkout ){
			update_option( 'woocommerce_enable_guest_checkout', 'no' );
			$checkout->enable_guest_checkout = false;
		}

	}

	/**
	 * Filter the billing fields displayed during checkout etc.
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	function filter_billing_fields( $fields ){

		if ( isset( $fields['billing_address_2'] ) ) {
			unset( $fields['billing_address_2'] );
		}

		return $fields;
	}

	/**
	 * Filter the shipping fields displayed during checkout etc.
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	function filter_shipping_fields( $fields )
	{
		// Only show this if authenticated User in
		if (is_user_logged_in()) {
			if (is_checkout()) {
				// Display option in Checkout page
				$fields['troly_shipping_as_permanent'] = array(
					'label' => 'Make these changes permanent',
					'type' => 'checkbox',
					'class' => ['form-row-wide'],
				);
			} else {
				// Add as hidden My Account > Address Details
				$fields['troly_shipping_as_permanent'] = array(
					'type' => 'text',
					'custom_attributes' => array(
						"style" => "display:none;"
					),
					'value' => true,
				);
			}
		}

		if( isset( $fields['shipping_address_2'] ) ){
			unset( $fields['shipping_address_2'] );
		}

		return $fields;

	}

	/**
	 * Filter the shop_order post type args.
	 *
	 * Hides the UI for the Orders.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function filter_shop_order_args( $args ){

		if( current_user_can( 'manage_wp99234_products' ) ){
			return $args;
		}

		$args['show_ui'] = false;

		return $args;

	}

	/**
	 * Filter the product post type args.
	 *
	 * Hides the UI for the Products.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function filter_product_args( $args ){

		if( current_user_can( 'manage_wp99234_products' ) ){
			return $args;
		}

		$args['show_ui'] = false;

		return $args;

	}

	/**
	 * Ensure that new customer created in WP has the login name set as their email address.
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	function filter_new_customer_data( $data ){

		$data['user_login'] = $data['user_email'];

		return  $data;

	}

	/**
	 * load-edit.php hook, disables management for products and orders.
	 */
	function load_edit_page(){

		if( current_user_can( 'manage_wp99234_products' ) ){
			return;
		}

		$redirect = false;

		if( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] === 'shop_order' ){
			WP99234()->_admin->add_notice( __( 'All orders are managed in Troly and have been disabled on this website.', 'wp99234' ), 'error' );
			$redirect = true;
		}

		if( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] === 'product' ){
			WP99234()->_admin->add_notice( __( 'All products are managed in Troly and have been disabled on this website.', 'wp99234' ), 'error' );
			$redirect = true;
		}

		if( $redirect ){
			wp_redirect( admin_url() );
			exit;
		}

	}

	/**
	 * load-post.php hook. disables management for products and orders.
	 */
	function load_post_page(){
		global $typenow;

		if( current_user_can( 'manage_wp99234_products' ) ){
			return;
		}

		$redirect = false;

		if( $typenow == 'shop_order' ){
			WP99234()->_admin->add_notice( __( 'All orders are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
			$redirect = true;
		}

		if( $typenow == 'product' ){
			WP99234()->_admin->add_notice( __( 'All products are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
			$redirect = true;
		}

		if( $redirect ){
			wp_redirect( admin_url() );
			exit;
		}

	}

	/**
	 * load-post-new.php hook, disables management for products and orders.
	 */
	function load_post_new_page(){
		global $typenow;

		if( current_user_can( 'manage_wp99234_products' ) ){
			return;
		}

		$redirect = false;

		if( $typenow == 'shop_order' ){
			WP99234()->_admin->add_notice( __( 'All orders are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
			$redirect = true;
		}

		if( $typenow == 'product' ){
			WP99234()->_admin->add_notice( __( 'All products are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
			$redirect = true;
		}

		if( $redirect ){
			wp_redirect( admin_url() );
			exit;
		}

	}

	/**
	 * Programatically disable all emails from woocommerce.
	 *
	 * The user, admin, order, stock, user notifications etc are all handled by troly
	 *
	 * @param $email_class
	 */
	function disable_emails( $email_class ) {

		remove_all_actions( 'woocommerce_low_stock_notification' );
		remove_all_actions( 'woocommerce_no_stock_notification' );
		remove_all_actions( 'woocommerce_product_on_backorder_notification' );

		remove_all_actions( 'woocommerce_order_status_pending_to_processing_notification' );
		remove_all_actions( 'woocommerce_order_status_pending_to_completed_notification' );
		remove_all_actions( 'woocommerce_order_status_pending_to_on-hold_notification' );
		remove_all_actions( 'woocommerce_order_status_failed_to_processing_notification' );
		remove_all_actions( 'woocommerce_order_status_failed_to_completed_notification' );
		remove_all_actions( 'woocommerce_order_status_failed_to_completed_notification' );
		remove_all_actions( 'woocommerce_order_status_failed_to_on-hold_notification' );

		remove_all_actions( 'woocommerce_order_status_pending_to_processing_notification' );
		remove_all_actions( 'woocommerce_order_status_pending_to_on-hold_notification' );
		remove_all_actions( 'woocommerce_order_status_completed_notification' );
		remove_all_actions( 'woocommerce_new_customer_note_notification' );

	}

	/**
	 * Filter the displayed price.
	 *
	 * Membership prices override the bulk purchases pricing.
	 *
	 * @TODO - Store this in a transient. Be sure to clear it when updating the products.
	 *
	 * @param $price
	 * @param $product
	 *
	 * @return mixed
	 */
	function filter_get_price( $price, $product ){

		////Frontend calcs only.
		if( ! defined( 'DOING_AJAX' ) ){
		    if( is_admin() ){
		        return $price;
		    }
		}

	  if (empty($_SESSION['editing_order']) || !empty($_SESSION['apply_membership_discounts'])) {

		/**
		 * If we are in the cart or checkout, then we need to find the product in the cart to be able to apply the bulk discounts to the displayed price.
		 */
		if( ( defined( 'WOOCOMMERCE_CART' ) && WOOCOMMERCE_CART ) || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) ) {

			$valid_item_key = WC()->cart->generate_cart_id( $product->get_id(), '', array(), array() );

			$cart_item_key = WC()->cart->find_product_in_cart( $valid_item_key );

			if( $cart_item_key ){

				$cart_item = WC()->cart->get_cart_item( $cart_item_key );

				$quantity = WC()->cart->cart_contents_count;

				$price_6pack = get_post_meta( $cart_item['product_id'], 'price_6pk' , true );
				$price_case  = get_post_meta( $cart_item['product_id'], 'price_case', true );

		   // Only use the specified 6 pack/case price if is positive
				if( $quantity >= 12 && is_numeric( $price_case ) && $price_case > 0){
					$price = (float)$price_case;
				} elseif( $quantity >= 6 && is_numeric( $price_6pack ) && $price_6pack > 0){
					$price = (float)$price_6pack;
				}

			}

		}

	  // User is logged in - we can attempt to get membership product prices
		if( is_user_logged_in() ){
			$current_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true);
			$current_membership = WP99234()->_users->get_current_membership();

			// Don't lookup membership prices if this user is not a current member
			if ( ! is_array( $current_memberships ) || empty( $current_memberships ) ) {
				return $price;
			}

			$member_prices = WP99234()->_prices->get_membership_prices_for_product( $product->get_id(), $current_memberships );

			// Member prices is an array.
			if(!is_array( $member_prices ) || empty( $member_prices ) ){
				return $price;
			}

			$using_member_price = false;

			foreach( $current_memberships as $current_membership ){

				//Get the membership ID we are looking at
				$membership_type_id = $current_membership->membership_type_id;

				//Sort through the prices for the product
				foreach( $member_prices as $member_price ){

					//If we are looking at a membership type that the user is associated with, we can use the price.
					if( $member_price->membership_id == $membership_type_id ){

						//Don't use prices that are 0. Allowing free stuff should be done with coupons not a globally set price.
						if( $member_price->price <= 0 ){
							continue;
						}

						// If no member price already used, then we can use it
						if( ! $using_member_price ){
							$price = (float)$member_price->price;
							$using_member_price = true;
						} else {
							// If we already have a member price, we need to ensure we are using the cheapest one.
							if( (float)$member_price->price < $price ){
								$price = (float)$member_price->price;
							}
						}

						/**
						 * @todo: Fix this on the base of IDs stored in the $_SESSION.
						 */
						$compositeNonPrePackIds = isset( $_SESSION['composite_non_pre_pack_ids'] ) ? $_SESSION['composite_non_pre_pack_ids'] : [];
						if ( in_array( $member_price->product_id, $compositeNonPrePackIds ) ) {
							$price = 0;
						}
					}

				}

			}

		}

	  }

	  return $price;

	}

	/**
	 * After Order Actions hook. Adds a button in WP to export the order to SUBS if it wasn't exported automatically.
	 *
	 * @param $order
	 */
	function after_order_actions( $order ) {
		$subs_id = get_post_meta( $order->get_id(), 'subs_id', true );

		if( $subs_id ){
			return;
		}

		$url = add_query_arg( array(
			'export_order_to_subs' => $order->get_id(),
			'_nonce' => wp_create_nonce( 'wp99234_export_order' )
		), admin_url( 'edit.php?post_type=shop_order' ) );

		printf( '<br /><a class="button tips" href="%s" data-tip="%s">%s</a>', esc_url( $url ), __( 'Export Order To Troly', 'wp99234' ), __( 'Export Order', 'wp99234' ) );

	}

	/**
	 * Export the order to Troly
	 *
	 * @param $order_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function export_order( $orderID )
	{
		WP99234()->_customer->order->setOrder( $orderID );
		WP99234()->_customer->setCustomerData();
		$order = WP99234()->_customer->order->getOrder();
		$trolyUserID = WP99234()->_customer->getTrolyUserID();
		$customerData = WP99234()->_customer->getCustomerData()['customer'];
		$trolyOrderID = get_post_meta( $orderID, 'subs_id', true );

		if ( $trolyOrderID && is_admin() ) {
			WP99234()->_admin->add_notice(
				__( 'Order has already been pushed to Troly.', 'troly' ),
				'error'
			);
			return;
		}

		// Prevent multiple submission of Order upon checkout
		if ( $trolyOrderID && defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) {
			return false;
		}

		$message = "Starting export of order to Troly.\nGetting order info to export";
		$shippingMethod = WP99234()->_customer->order->getShippingMethod();

		// Set basic order data.
		WP99234()->_customer->order->setOrderData();

		if ( $shippingMethod !== 'local_pickup' ) {
			WP99234()->_customer->order->updateOrderData( [
				'status' => 'confirmed',
				'shipment' => [
					'shipment_date' => date( 'Y-m-d' ),
					'customer_id'	=> $trolyUserID,
				],
				'shipment_date' => date( 'Y-m-d' ),
			] );

			foreach( $customerData as $key => $value ) {
				if ( strpos( $key, 'delivery_' ) !== false ) {
					$deliveryAddress[ $key ] = $value;
				}
			}

			// Update the delivery address in Order Data object.
			WP99234()->_customer->order->updateOrderData( [
				'shipment' => $deliveryAddress,
			] );

			if ( $shippingMethod == 'free_shipping' ) {
				WP99234()->_customer->order->updateOrderData( [
					'shipment' => [
						'shipping_fee_override' => true,
						'shipping_price' => 0
					]
				] );
			}

			if ( $shippingMethod == 'flat_rate' ) {
				WP99234()->_customer->order->updateOrderData( [
					'shipment' => [
						'shipping_fee_override' => true,
						'shipping_price' => WP99234()->_customer->order->getShippingCost(),
						'ship_carrier_pref' => 'ManualShipment'
					]
				] );
			}

		} else {
			WP99234()->_customer->order->updateOrderData( [
				'shipment_date' => 'none',
			] );
		}

		if ( $referralCode = WC()->session->get( 'troly_referral_code_used' ) ) {
			WP99234()->_customer->order->updateOrderData( [
				'referral_code' => $referralCode
			] );
		}

		if ( $UTMData = $_SESSION['troly_utm_tracking'] ) {
			WP99234()->_customer->order->updateOrderData( [
				'utm' => $UTMData
			] );
		}

		// Add gift order data to the order payload.
		if ( $giftMessage = WC()->session->get( 'troly_gift_order_msg' ) ) {
			WP99234()->_customer->order->updateOrderData( [
				'recipient' => [
					'fname' => $order->get_shipping_first_name() ,
					'lname' => $order->get_shipping_last_name(),
					'email' => get_post_meta( $order->get_id(), '_shipping_email', true ),
					'mobile' =>  get_post_meta( $order->get_id(), '_shipping_phone', true ),
				],
				'gift_message' => $giftMessage,
			] );
		}

		// Add gift wrapping price to the order payload.
		if ( $giftWrapPrice = WC()->session->get( 'troly_gift_order_wrap' ) ) {
			WP99234()->_customer->order->updateOrderData( [
				'gift_wrapping_price' => get_option( 'troly_gift_wrap_price' ),
			] );
		}

		// Set Order status as template
		if ( isset( $_SESSION['troly_new_club_signup'] ) && true === $_SESSION['troly_new_club_signup'] ) {
			WP99234()->_customer->order->updateOrderData( [
				'status' => 'template',
			] );
		}

		/*
		 * Changing delivery address
		 */
		if ( isset( $_POST[ 'ship_to_different_address' ] ) && $_POST[ 'ship_to_different_address' ]
			&& ( $order->get_billing_address_1() !== $order->get_shipping_address_1() ) ) {
				WP99234()->_customer->order->shipToDifferentAddress();
		}

		//Get credit card details for customer if provided
		if ( isset( $_POST[ 'payment_method' ] ) && $_POST[ 'payment_method' ] === 'wp99234_payment_gateway' && ! isset( $_POST['wp99234_use_existing_card' ] ) ) {
			WP99234()->_customer->setCreditCardDetails( $_POST );
		}

		/**
		 *
		 * Exporting order to Troly
		 *
		 * For authenticated user with Troly account
		 */
		WP99234()->_customer->order->updateOrderData( [
			'customer_id' => $trolyUserID,
		] );

		if ( is_user_logged_in() && $trolyUserID ) {
			// Add CC details to Order if provided
			if ( WP99234()->_customer->getCreditCardDetails() ) {
				if ( ! isset( $_POST[ 'troly_shipping_as_permanent' ] ) ) {
					// Fetch and store CC token from Troly API.
					$endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $trolyUserID );
					WP99234()->_customer->order->fetchCCToken( false, false, $endpoint, 'PUT' );
				}
			}
			elseif ( isset( $_POST[ 'wp99234_use_existing_card' ] )
					&& $_POST[ 'wp99234_use_existing_card' ] === 'yes' ) {
						// WP99234()->_customer->order->updateOrderData( [
						// 	'payment_type' => 'charge',
						// ] );
			}
		}
		elseif ( ( isset( $_POST[ 'createaccount' ] ) && $_POST[ 'createaccount' ] )
		&& ! $trolyUserID ) {

			/*
			* For customer who newly created WP account upon checkout, also needs to create new Troly account
			*/

			// Get newly created WP user ID.
			$WPUserID = get_current_user_id();

			// Push newly created WP user to Troly.
			define( 'WP99234_ALLOW_USER_EXPORT', true );
			$apiResponse = WP99234()->_users->export_user( $WPUserID,
			WP99234()->_customer->order->getShipping(),
			WP99234()->_customer->getCustomerData()['customer'], false,
			WP99234()->_customer->getCreditCardDetails() );

			if ( $apiResponse === false ) {
				throw new Exception( __( 'New customer could not be created and order processing has failed.', 'troly' ) );

				ob_start();
				$errs = ob_get_contents();
				ob_end_clean();
				WP99234()->logger->error($errs);
			} else {
				// Add flag to prevent updating customer for newly created account
				// TODO review global vars like this and simplify
				define('WP99234_DONE_USER_EXPORT', true);

				$message .= "\n New customer created successfully and will be use for this WC order ({$orderID})";

				// Fetching the Troly User ID after creating a new user in WP.
				$trolyUserID = WP99234()->_customer->getTrolyUserID();

				// Attach CC details for newly created customer.
				$endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $trolyUserID );
				WP99234()->_customer->order->fetchCCToken( true, false, $endpoint, 'PUT' );

				// Updating order data to include Troly user ID.
				WP99234()->_customer->order->updateOrderData( [
					'customer_id' => $trolyUserID,
					// 'payment_type' => 'charge',
					'shipment' => [
						'customer_id'	=> $trolyUserID,
					]
				] );
			}
		} else {
			/*
			 * For Guest or with WP account but not existed in Troly.
			 *
			 * Attached CC, billing and shipping details to Order
			 */

			if ( ! $trolyUserID ) {
				if ( WP99234()->_customer->getCreditCardDetails() ) {
					// WP99234()->_customer->order->updateOrderData( [
					// 	'payment_type' => 'charge',
					// ] );
				}

				// Remove CC details for guest customers.
				WP99234()->_customer->updateCustomerData( [
					'cc_name' => '',
					'cc_number' => '',
					'cc_exp_month' => '',
					'cc_exp_year' => '',
					'cc_cvv' => '',
				] );

				// Create Troly customer.
				$trolyUserID = WP99234()->_customer->createTrolyUser();

				// Fetch CC token for guest customers.
				$endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $trolyUserID );
				WP99234()->_customer->order->fetchCCToken( false, true, $endpoint, 'PUT' );
			} else {
				if ( WP99234()->_customer->getCreditCardDetails() ) {
					$endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $trolyUserID );

					WP99234()->_customer->order->fetchCCToken( false, true, $endpoint, 'PUT' );
				}
			}
		}

		$message .= "\nGetting orderlines for the order\nExporting order to Troly";

		$this->methodAllocator( $orderID, $message );
	}

	public function methodAllocator( $order_id, $message )
	{
		$order = new WC_Order( $order_id );
		$reporting_options = get_option( 'wp99234_reporting_sync', 'minimum' );
		$orderlines = WP99234()->_customer->order->getOrderData()['order']['orderlines'];

		// If we were editing an order
		if ( ! empty( $_SESSION[ 'editing-order' ] ) ) {
			$edited_order_id = (!empty($_SESSION['editing-order-troly-id']) ? 0 : $_SESSION['editing-order-wc-order-id']);

			$subs_order_id = (!empty($_SESSION['editing-order-troly-id']) ? $_SESSION['editing-order-troly-id'] : get_post_meta($edited_order_id, 'subs_id', true));

			// Get the current order object from Troly
			$response = WP99234()->_api->_call(WP99234_Api::$endpoint . 'orders/' . $subs_order_id . '.json');

			$errs = @(array)$response->errors;

			if (!empty($errs)) {

				//Log the errors
				WP99234()->logger->error('Troly payment errors. ' . var_export($response->errors, true));

				$message .= "\nExport failed, Troly payment errors. {${var_export($response->errors, true)}}";

				if ($reporting_options == "verbose" || $reporting_options == "minimum") {
					wp99234_log_troly( 0, 1, 1, 'Order Export to Troly', $message );
				}

				unset($_SESSION['editing-order']);
				unset($_SESSION['editing-order-wc-order-id']);
				unset($_SESSION['editing-order-troly-id']);

				if ($edited_order_id) {
					wp_delete_post($edited_order_id, true);
				}

				//Get the hell out of Dodge
				throw new \Exception(__('There was an error processing your payment. We will contact you shortly.', 'wp99234'));
			}


			/*

			  CODE NAME: ALLOCATOR

			  This next section is straight forward in its approach but represents a
			  meticilous approach to managing the three scenarios Troly encounters.

			  1. No open packs, just bottles.
				  This means we have a one-off order that can be happily fulfilled

			  2. Open packs on the order.
				  Oh boy, this is the fun one. Troly needs to know how to divvy up
				  all those bottles!

			  3. Closed packs on the order
				  Unlike its sibling, this pack type does not have sub-products
				  inside WooCommerce, so we just need to ensure that its qtys match

			  As options 1 and 3 are very straight forward, that leaves us with Open Packs.

			  Troly lets you have a club run order that has an "editable", "add only" and
			  "not editable" mode for orders. As WooCommerce has no way to handle these,
			  we need to use an allocation method to assign to each pack.

			  Oproduct in question will be removed from the order.

			  ur approach is to assign them top-down, just like the platform. When an
			  allocation is exhausted, the bottom-most orderline in Troly that has the
			  For non-editable orders, no changes are permitted and are dropped as part of the
			  relevant checkout and review process.

			  Of note, the orderline ordering is important from Troly. We will always return
			  our orderlines in ASC order, as our composite products, then sub-products,
			  have an easier time being rendered if they are in order!
			*/


			/*

				Step 1: Determine what is allotable

				Our next steps are to map from WooCommercer's cart contents and prepare
				what it is we are going to send to the server.

				Ordering is important. In an add-only situation for a club-run,
				it is impossible to remove the first X number of cart items.
				Qtys can increase, but that's it.

				We create $allocatable_items as we need an outside-scope to keep track
				of line item quantities.

				$allocatable_items[subs_product_id] uses the Troly product ID as the key to
				keep track of the amounts currently available.
			*/

			$allocatable_items = [];
			foreach ( WP99234()->_customer->getOrder()->get_items() as $key => $item ) {
				$al_subs_id = get_post_meta( (int) $item['product_id'], 'subs_id', true );
				/*
					If a cart is configured to split things up so orderlines have
					only 1 qty, this will cater for it as well.
				*/
				if ( ! WP99234()->_customer->getOrder()->get_id() ) {
					$allocatable_items[ $al_subs_id ] = (int) $item['qty'];
				} else {
					$allocatable_items[ $al_subs_id ] += (int) $item['qty'];
				}
			}

			/*
			  For each of our orderlines returned from Troly
			  allocate stock based on the cart levels

			  We also want to respect open packs wherever possible
			*/
			$orderlines_needing_more = [];
			$discountProductIDs = [50, 51, 52, 53, 54];
			$compositeProductID = null;

			foreach ( $response->orderlines as $troly_orderline ) {
				/*
					The  tricky catch for talking to Troly are the open packs
					whose contents *can* change.

					By default, when a product is added to Troly's order, the highest orderline ID
					will catch the update and increment its quantity.

					The next steps below replicate this behaviour

					If we ever encounter an orderline with a composite product ID and set to be a
					"display only" orderline, we can skip it.
				*/

				if ( isset( $troly_orderline->composite_product_id ) &&
					! empty( $troly_orderline->composite_product_id ) &&
					$troly_orderline->composite_product_id != '' ) {
					$compositeProductID = $troly_orderline->composite_product_id;
				}

				if ( isset( $troly_orderline->composite_product_id )
					&& $troly_orderline->display_only === true) {
					continue;
				}

				// If we can't edit them, we need to deduct the quantities from the pool
				if ( false === $troly_orderline->customer_editable ) {
					if ( in_array( $troly_orderline->product_id, $discountProductIDs ) ) {
						$orderlines[] = [
							'id' 			=> $troly_orderline->id,
							'product_id' 	=> $troly_orderline->product_id,
							'name' 			=> $troly_orderline->name,
							'qty' 			=> $troly_orderline->qty,
							'base_price' 	=> $troly_orderline->base_price,
						];
					} else {
						$orderlines[] = [
							'id' 			=> $troly_orderline->id,
							'product_id' 	=> $troly_orderline->product_id,
							'name' 			=> $troly_orderline->name,
							'qty' 			=> $troly_orderline->qty
						];
					}

					$allocatable_items[ $troly_orderline->product_id ] -= (int) $troly_orderline->qty;

					if ( $allocatable_items[ $troly_orderline->product_id ] == 0 )
						unset( $allocatable_items[ $troly_orderline->product_id ] );

					// Finish this iteration
					continue;
				}

				// If its not in the cart, delete it!
				if ( ! isset( $allocatable_items[ $troly_orderline->product_id ] ) ) {
					$orderlines[] = [
						'id'			=> $troly_orderline->id,
						'product_id'	=> $troly_orderline->product_id,
						'name'			=> $troly_orderline->name,
						'qty'			=> 0,
						'_destroy'		=> '1'
					];
					continue;
				}

				if ( $allocatable_items[ $troly_orderline->product_id ] > 0 ) {
					$used_qty = min( $allocatable_items[ $troly_orderline->product_id ],
					(int) $troly_orderline->qty );

					$orderlines[] = [
						'id'					=> $troly_orderline->id,
						'product_id'			=> $troly_orderline->product_id,
						'name'					=> $troly_orderline->name,
						'qty'					=> $used_qty,
						'composite_product_id'	=> $troly_orderline->composite_product_id,
					];

					// Deduct from the pool
					$allocatable_items[ $troly_orderline->product_id ] -= $used_qty;

					if ( $allocatable_items[ $troly_orderline->product_id ] == 0 ) {
						unset( $allocatable_items[ $troly_orderline->product_id ] );
					}
					elseif ( ! isset( $orderlines_needing_more[ $troly_orderline->product_id ] ) ) {
						// Handles situations where Troly says "2x Shiraz" but the cart says "1x Shiraz"
						$orderlines_needing_more[ $troly_orderline->product_id ] = count( $orderlines ) - 1;
					}
				} else {
					$orderlines[] = [
						'id' 			=> $troly_orderline->id,
						'product_id' 	=> $troly_orderline->product_id,
						'name' 			=> $troly_orderline->name,
						'qty' 			=> 0,
						'_destroy' 		=> '1'
					];

					unset( $allocatable_items[ $troly_orderline->product_id ] );
				}
			}
			/*
				If, for some reason, we still have orderlines, check we don't have existing
				orderlines to be sent (so we can update them) or create new ones.
			*/
			foreach ( $allocatable_items as $product_id => $qty ) {
				if ( isset( $orderlines_needing_more[ $product_id ] ) ) {
					$orderlines[ $orderlines_needing_more[ $product_id ] ]['qty'] += $qty;
				}
				elseif ( $compositeProductID && $product_id != $compositeProductID && ! in_array( $product_id, $discountProductIDs ) ) {
					$orderlines[] = [
						'product_id'	=> $product_id,
						'qty'			=> $qty,
					];
				}
			}

			WP99234()->_customer->order->updateOrderData( [
				'orderlines' => $orderlines,
			] );

			/*
				That's it! Now we call the Troly API to update the order.
				Once the order has been set, we will go ahead and charge the card
			*/
			$response = WP99234()->_api->_call(WP99234_Api::$endpoint . 'orders/' . $subs_order_id . '.json', WP99234()->_customer->order->getOrderData(), 'PUT');

			unset( $_SESSION[ 'editing-order' ] );
			unset( $_SESSION[ 'editing-order-wc-order-id' ] );
			unset( $_SESSION[ 'editing-order-troly-id' ] );

			if ( $edited_order_id ) {
				wp_delete_post( $edited_order_id, true );
			}

		} else {
			/*
				No order exists yet in Troly! This means that the customer has visited
				and is placing a one-off order! Yaay!
			*/
			foreach ( WP99234()->_customer->getOrder()->get_items() as $key => $item ) {
				$orderlines[] = [
					'name' => $item['name'],
					'qty' => $item['qty'],
					'product_id' => get_post_meta( (int) $item[ 'product_id' ], 'subs_id', true ),
				];
			}

			WP99234()->_customer->order->updateOrderData( [
				'orderlines' => $orderlines,
			] );

			$response = WP99234()->_api->_call( $this->order_api_endpoint, WP99234()->_customer->order->getOrderData(), 'POST' );
		}

		/**
		 * @todo Using this here but not certain. Triggering the callback cron for Troly.
		 */
		//if ( ! wp_next_scheduled( 'troly_order_status_check' ) ) {
		//	wp_schedule_event( time(), '15_seconds', 'troly_order_status_check', [ $order_id ] );
		//}

		if ( isset( $response->id ) ) {
			// Commenting this out since the `$response` only returns `channel_id` and never touches this block.
			update_post_meta( $order_id, 'subs_id', $response->id );

			update_post_meta( $order_id, 'subs_order_no', $response->number );

			//Enforce the final price
			if ( $response && isset( $response->total_value ) && $response->total_value > 0 ) {
				update_post_meta( $order_id, '_order_total', $response->total_value );
				update_post_meta( $order_id, '_order_tax', $response->total_tax1 + $response->total_tax2 );
			}

			if ( $response && isset( $response->shipment->shipping_price ) ) {
				update_post_meta( $order_id, '_order_shipping', $response->shipment->shipping_price );

				if ( $response->shipment->shipping_price <= 0 ) {
					/**
					 * Remove "shipping" line item from WC order if Troly rejects shipping price.
					 */
					foreach ( WP99234()->_customer->getOrder()->get_items( 'shipping' ) as $itemID => $item ) {
						$trolyShippingMethod = wc_get_order_item_meta( $itemID, 'method_id', true );

						if ( $trolyShippingMethod && isset( $trolyShippingMethod ) &&
							'wp99234_shipping_method' === $trolyShippingMethod ) :
							wc_delete_order_item( $itemID );
						endif;
					}
				} else {
					/** Update shipping line item price as per Troly data. */
					foreach ( WP99234()->_customer->getOrder()->get_items( 'shipping' ) as $itemID => $item ) {
						$trolyShippingMethod = wc_get_order_item_meta( $itemID, 'method_id', true );

						if ( $trolyShippingMethod && isset( $trolyShippingMethod ) &&
							'wp99234_shipping_method' === $trolyShippingMethod ) :
							wc_update_order_item_meta( $itemID, 'cost', $response->shipment->shipping_price );
						endif;
					}
				}
			}
		}

		$errs = $response ? (array)$response->errors : false;

		if (!is_admin() || defined('DOING_AJAX')) {
			/**
			 * If the order fails, display a generic order failure message telling the user that they will be contacted shortly.
			 */
			if (!$response || !empty($errs)) {

				//mark the order on hold
				//$order->update_status( 'on-hold', __( 'Troly payment failed.', 'wp99234' ) );

				//Log the errors
				WP99234()->logger->error('Troly payment errors. ' . var_export($response->errors, true));

				$message .= "\nExport failed, Troly payment errors. {${var_export($response->errors, true)}}";

				if ($reporting_options == "verbose" || $reporting_options == "minimum") {
					wp99234_log_troly( 0, 1, 1, 'Order Export to Troly', $message );
				}

				//Get the hell out of Dodge
				throw new \Exception(__('There was an error processing your payment. We will contact you shortly.', 'wp99234'));

			} else {
				//wc_add_notice( __( 'Your payment has been successful.', 'wp99234' ), 'success' );
				//$order->update_status( 'processing', __( 'Troly payment succeeded.', 'wp99234' ) );
				//WP99234()->logger->info( 'Troly payment for order id: ' . $order->id . ' succeeded' );
				//$order->payment_complete();

				$message .= "\nOrder successfully exported to Troly";
				if ($reporting_options == "verbose" || $reporting_options == "minimum") {
					wp99234_log_troly( 1, 1, 1, 'Order Export to Troly', $message );
				}

				// Trigger the charge manually as a $0 order does not get paid by woocommerce
				// and then the order does not get confirmed and won't show up in the Subs order list
				// by manually triggering the 'payment' we can confirm the order and have it displayed.
				if ($response->total_value == 0 && get_option( 'troly_club_membership_signup' ) !== 'future_club_purchase_only' ) {
					WP99234()->_woocommerce->trigger_charge_on_order($order_id, "charge");
				}
			}

			// Reduce stock levels
			wc_reduce_stock_levels($order->get_id());

			//Return subs ID
			return $response->id;

		} else {
			if (!$response || !empty($errs)) {
				WP99234()->_admin->add_notice(__('Order failed to push to Troly. Please check the error logs for details.', 'wp99234'), 'error');

				$message .= "\nExport failed, Troly payment errors. {${var_export($response ? $response->errors : '-', true)}}";

				if ($reporting_options == "verbose" || $reporting_options == "minimum") {
					wp99234_log_troly( 0, 1, 1, 'Order Export to Troly', $message );
				}

			} else {
				WP99234()->_admin->add_notice(__('Order pushed successfully to Troly', 'wp99234'), 'success');

				$message .= "\nOrder successfully exported to Troly";

				if ($reporting_options == "verbose" || $reporting_options == "minimum") {
					wp99234_log_troly( 1, 1, 1, 'Order Export to Troly', $message );
				}
				// $order->update_status( 'processing', __( 'Troly payment succeeded.', 'wp99234' ) );
			}
		}
	}

	/**
	 * Exports and pays for an order to Troly
	 *
	 * Returns the websocket channel existing, or throws an exception on failure.
	 *
	 * Adds a note to the order to say payment information exported to Troly
	 *
	 * @param $order_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function export_payment( $order_id )
	{
		$order = wc_get_order($order_id);
		$subs_id = get_post_meta( $order_id, 'subs_id', true );
		$trx_id = $order->get_transaction_id();
		$mtype = $order->get_payment_method();

		// Do not trigger this for Troly payment gateway.
		if ( 'wp99234_payment_gateway' !== $mtype ) {
			WP99234()->logger->notice("Processing $subs_id");
			WP99234()->logger->notice("Attempting to export a payment for $order_id (Troly ID $subs_id) with $mtype (TRXID $trx_id) as payment");

			switch($mtype){
				case 'bacs':
					$mtype = 'transfer';
					break;
				default:
					$mtype = $mtype;
				break;
			}

			# We set rrn to allow Stream objects to show reference
			# This will also be set on the Payment object as well

			if ( 'cod' !== $mtype && 'bacs' !== $mtype ) {
				$pay_data = [
					'order' => [
						'id'  => $subs_id,
						'payment_type' => 'offline',
						'payment_meth' => 'website',
						'payment_src'  => $mtype,
						'status' => 'confirmed' ,
						'rrn' => $trx_id
					],
					'id'  => $subs_id
				];
			} else {
				$pay_data = [
					'order' => [
						'id' => $subs_id,
						'payment_meth' => 'website',
						'payment_notes' => 'COD/Bank payment',
					],
					'id'  => $subs_id
				];
			}

			$response = WP99234()->_api->_call( $this->payment_api_endpoint.$subs_id, $pay_data, 'PUT' );

			if ( isset( $response->channel ) ) {
				$order->add_order_note( 'Exported payment information to Troly' );
				WP99234()->logger->notice("Succeeded exporting payment for $order_id (Troly ID $subs_id) with $mtype as payment");
				return true;
			} else {
				$order->add_order_note( 'Could not export payment information to Troly' );
				WP99234()->logger->notice("Failed exporting payment for $order_id (Troly ID $subs_id) with $mtype as payment");
				throw new \Exception( __( 'An error has occurred. Please get in touch with us as soon as possible.', 'wp99234' ) );
			}
		}
	}

	/**
	 * Triggers the actual auth charge on the order.
	 *
	 * Returns the websocket channel, or throws an exception on failure.
	 *
	 * @param $order_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	function trigger_charge_on_order( $order_id, $payment_type ){

		$subs_id = get_post_meta( $order_id, 'subs_id', true );

		$order = new WC_Order( $order_id );
		$rrn = $order->get_transaction_id();
		$order_status = $order->get_status() !== 'failed' ? 'in-progress' : false;

		if( ! $subs_id || ! $order ){
			return false;
		}

		$endpoint = $this->get_order_update_endpoint( $order_id );

		$data = array(
			'status' => 'confirmed', // $order_status
			'id'     => $subs_id, // maybe remove this
			'order'  => array(
				'status'       => 'confirmed',
				'payment_type' => $payment_type,
				'payment_meth' => 'website',
				'payment_src'  => 'wp99234_payment_gateway',
				'rrn' => $rrn,
			)
		);

		$cc_token = get_post_meta($order_id, 'wp99234_cc_token', true);
		if ($cc_token) {
			$data['order']['cc_token'] = $cc_token;
		}

		$results = WP99234()->_api->_call( $endpoint, $data, 'PUT' );

		if( isset( $results->channel ) ){
			return $results->channel;
		} else {
			throw new \Exception( __( 'An error has occurred. You will be contacted as soon as possible.', 'wp99234' ) );
		}

	}

	/**
	 * Retrieve a WC_Order object based on the subs_id.
	 *
	 * @param $subs_id
	 *
	 * @return mixed
	 */
	function get_order_by_subs_id( $subs_id ){

		$args = array(
			'post_type'      => 'shop_order',
			'meta_query'     => array(
				array(
					'key'     => 'subs_id',
					'value'   => $subs_id,
					'compare' => '=',
				),
			),
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'page'           => 1
		);
		$query = new WP_Query( $args );

		if( $query->have_posts() ){
			$post = array_shift( $query->posts );
			return new WC_Order( $post->ID );
		}

		return false;

	}

	/**
	 * Get the subs endpoint to update the given order.
	 *
	 * @param $order_id
	 * @return string
	 */
	function get_order_update_endpoint( $order_id ){

		$subs_id = get_post_meta( $order_id, 'subs_id', true );

		if( $subs_id ) {
			$endpoint = sprintf( WP99234_Api::$endpoint . 'orders/%s', $subs_id );
		} else {
			$endpoint = $this->order_api_endpoint;
		}

		return $endpoint;

	}

	/**
	 *
	 * @param int|string $orderID
	 * @todo removing this since the status of orders placed via Troly gateway are begin updated already
	 * @return void
	 */
	//public function check_wp99234_payment_status( $orderID ) {

	//	if ( ! $orderID ) return;
	//	$order = wc_get_order( $orderID );
	//	if ( ! $order ) return;

	//	$trolyOrderID = get_post_meta( $order->get_id(), 'subs_id', true );

	//	if ( ! $trolyOrderID || empty( $trolyOrderID) ) return;

	//	$endpoint = sprintf( "%sorders/%s.json", WP99234_Api::$endpoint, $trolyOrderID );
	//	$response = WP99234()->_api->_call( $endpoint );

	//	// Just taking in the case of "success" for now.
	//	if ( $response && is_object( $response ) && ( $response->payment_status === 'auth'
	//	|| $response->payment_status === 'paid' ) ) {
	//		$order->update_status( 'processing' );

	//		// Removing the specific CRON after order status has been updated.
	//		wp_clear_scheduled_hook( 'troly_order_status_check', [ $orderID ] );
	//	}

	//	exit;
	//}

	/**
	 * Removes the order the order status check cron.
	 *
	 * @since 2.9.18
	 * @param int $orderID
	 * @return void
	 */
	//public function removeOrderStatusCheckCRON( $orderID )
	//{
	//	wp_clear_scheduled_hook( 'troly_order_status_check', [ $orderID ] );
	//}

	/**
	 * Handle the pushing of product ratings and reviews to SUBS.
	 */
	function handle_ratings_and_reviews( $id, $comment ){
		global $post;

		if( isset($post) ) {
		 if ($post->post_type != WP99234()->_products->products_post_type)
			return;
		}

		// Fix notice
		if (!isset($_POST['comment_post_ID'])) return;

		// Get the current Post id
		$subs_id = get_post_meta( $_POST['comment_post_ID'], 'subs_id', true );

		if( ! $subs_id ){
			return;
		}

		if(  isset( $_POST['rating'] ) ){
			$rating = (float)$_POST['rating'];
		}

		$author = get_user_by( 'email', $comment->comment_author_email );

		$data = array(
			'val'          => ( $rating > 0 ) ? $rating : false ,
			'product_id'   => $subs_id,
			'comment'      => $comment->comment_content
		);

		if( $author ){

			$author_subs_id = get_user_meta( $author->ID, 'subs_id', true );

			$data['customer_id'] = ( $author_subs_id ) ? $author_subs_id : null ;

		} else {
			$data['customer_id'] = null;
		}

		// /products/:product_id/ratings.json
		$endpoint = sprintf( '%sproducts/%s/ratings.json', WP99234_Api::$endpoint, $subs_id );

		$results = WP99234()->_api->_call( $endpoint, $data, 'POST' );

		if( ! $results ){
			WP99234()->logger->error( 'Invalid result from API. Called ' . $endpoint );
		}

		$errors = (array)$results->errors;

		if( ! empty( $errors ) ){
			foreach( $errors as $error ){
				WP99234()->logger->error( $error );
			}
			return false;
		}

		WP99234()->_products->import_product( $results->product );

	}

	/**
	 * Capture the cart and insert the information of the cart into stream.
	 * @hook woocommerce_cart_updated
	 * @globals mixed $wpdb
	 * @globals mixed $woocommerce
	 * @since 2.9
	 */
	function wp99234_store_cart_timestamp()
	{
		WP99234()->_abandonedCart->set();
	}

	/**
	 * Displays WooCommerce notice on cart or checkout page.
	 * Promotes Club Membership Signup.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
   public function displayClubMemberNotice()
    {
		// Only display message when needed
        if ( WC()->cart->is_empty() ) return;

		$current_membership = WP99234()->_users->get_current_membership();

		if ( is_user_logged_in() && $current_membership ) {
			$current_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true );
			$save_amount = wc_price( WP99234()->_orders->cart_troly_member_save_amount( $current_memberships ) );
		} else {
			$save_amount = wc_price( WP99234()->_orders->cart_troly_member_save_amount() );
		}

		if ( $current_membership ) {
			$content = "<div class=\"woocommerce-info\">As a member of the <b>{$current_membership->name}</b>, you have just saved <b>{$save_amount}</b>!</div>";
		} else {
			$upsellPageID = WP99234()->template->getUpsellPageID();
			$redirectSource = is_cart() ? 'cart' : ( is_checkout() ? 'checkout' : false );
			$upsellPagePermalink = get_permalink( $upsellPageID ) . ( $redirectSource ? '?troly_redirect=' . $redirectSource : '' );
			$content = "<div class=\"woocommerce-info\">Become a member now and save up to <b>{$save_amount}</b> on this order. <a href=\"$upsellPagePermalink\" class=\"button show-troly-membership\">JOIN NOW!</a></div>";
		}

		echo $content;
    }

	/**
	 * Filter the wc_get_template so we can override the rating template.
	 *
	 * @param $located
	 * @param $template_name
	 * @param $args
	 * @param $template_path
	 * @param $default_path
	 *
	 * @return string
	 */
	function filter_wc_get_template( $located, $template_name, $args, $template_path, $default_path ){

		if( $template_name == 'loop/rating.php' ){
			$located = WP99234()->template->locate_template( 'rating.php' );
		}

		return $located;
	}

	/**
	 * Return override to email reset token expiration
	 *
	 * @param $expiration
	 * @return void
	 */
	function filter_password_reset_expiration($expiration)
	{
		return 604800; // 7 days in secconds
	}

	/**
	 * Get the rating count for the given product
	 *
	 * @param $product
	 *
	 * @return float
	 */
	function get_rating_count( $product ){

		$meta = get_post_meta( $product->get_id(), 'avg_rating', true );

		if( $meta && is_numeric( $meta ) ){
			return (float)$meta;
		}

		return $product->get_average_rating();

	}

	/**
	 * Get the average rating for the given product.
	 *
	 * @param $product
	 *
	 * @return float'
	 */
	function get_average_rating( $product ){

		$meta = get_post_meta( $product->get_id(), 'rating_count', true );

		if( $meta && is_numeric( $meta ) ){
			return (float)$meta;
		}

		return $product->get_rating_count();

	}

	/**
	 * Credit card validation on checkout
	 *
	 * This will check if using Credit Card as payment option
	 *
	 * @param  $fields
	 * @param  $errors
	 *
	 * @package Troly
	 * @since 2.9
	 */
	function wp99234_validate_credit_card_checkout( $fields, $errors )
	{
		if ($_POST['payment_method'] === 'wp99234_payment_gateway' && !isset($_POST['wp99234_use_existing_card'])) {

			$data = array(
				"cc_name"    => wp_kses($_POST['wp99234_payment_gateway-card-name'], array()),
				"cc_number"  => wp_kses($_POST['wp99234_payment_gateway-card-number'], array()),
				"cc_expiry"  => wp_kses($_POST['wp99234_payment_gateway-card-expiry'], array()),
				"cc_cvc"     => wp_kses($_POST['wp99234_payment_gateway-card-cvc'], array())
			);

			$this->wp99234_validate_credit_card($data, $errors);
		}
	}

	/**
	 * Credit card validation on checkout
	 *
	 * This will check if using Credit Card as payment option
	 *
	 * @param  $fields
	 * @param  $errors
	 *
	 * @package Troly
	 * @since 2.9
	 */
	function wp99234_validate_credit_card_account_details( $args )
	{
		if (!isset($_POST['wp99234_use_existing_card'])) {
			$data = [];
			foreach([
				'cc_name',
				'cc_number',
				'cc_expiry',
				'cc_cvc',
				'cc_cvv'
			] as $field){
				if(!isset($_POST[$field])) continue;
				$data[$field] = wp_kses($_POST[$field], array());
			}
			return $this->wp99234_validate_credit_card($data, $args, true);
		}
	}

	/**
	 * Credit card validation
	 *
	 * Checks if the card number and other details are valid
	 *
	 * @param  $fields
	 * @param  $errors
	 *
	 * @package Troly
	 * @since 2.9
	 */
	function wp99234_validate_credit_card( $fields, $errors, $return = false )
	{
		
		$validCard = true;

		$validate_cc_number = \Inacho\CreditCard::validCreditCard($fields["cc_number"]);
		$code = (isset($fields["cc_cvc"]) ? $fields["cc_cvc"] : $fields["cc_cvv"]);
		$validCvc = \Inacho\CreditCard::validCvc($code, $validate_cc_number['type']);

		if ( empty($fields["cc_name"]) ) {
			$errors->add( 'validation', '<strong>Name on Credit Card</strong> is a required field.' );
			$validCard = false;
		}

		if ( empty($fields["cc_number"]) ) {
			$errors->add( 'validation', '<strong>Credit Card Number</strong> is a required field.' );
			$validCard = false;
		} elseif ( empty($validate_cc_number['valid']) ) {
			$errors->add( 'validation', '<strong>Credit Card Number</strong> is invalid.' );
			$validCard = false;
		}

		if ( empty($fields["cc_expiry"]) ) {
			$errors->add( 'validation', '<strong>Credit Card Expiry</strong> is a required field.' );
			$validCard = false;
		}

		if ( empty($fields["cc_cvc"]) && empty($fields["cc_cvv"]) ) {
			$errors->add( 'validation', '<strong>Credit Card Code</strong> is a required field.' );
			$validCard = false;
		} elseif ( $validCvc === false || $fields["validCvc"] === false ) {
			$errors->add( 'validation', '<strong>Credit Card Code</strong> is invalid.' );
			$validCard = false;
		}

		if($return)
			return $validCard;
	}

	/**
	 * Initializes a custom shipping method.
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	public function shipping_methods( $methods ) {
		if ( class_exists( 'WP99234_WC_Shipping_Method' ) ) {
			$methods['wp99234_shipping_method'] = 'WP99234_WC_Shipping_Method';
		}

		return $methods;
	}

	/**
	 * Initialize the custom payment gateway.
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	public function payment_gateways( $methods ) {
		if ( class_exists( 'WP99234_WC_Payment_Gateway' ) ) {
			$methods[] = 'WP99234_WC_Payment_Gateway';
		}

		return $methods;
	}

	/**
	 * Resets our WooCommerce cart
	 */
	public function reset_entire_session(){
		WC()->cart->empty_cart();
		WC()->session->destroy_session();
		WC()->session->init();
	}
}
