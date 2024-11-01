<?php
/**
 * Troly Gift Order class.
 *
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 * @since 2.19.21
 */
class TrolyGiftOrders {
	public function __construct()
	{
		add_action( 'woocommerce_review_order_after_cart_contents', [$this, 'addGiftOrderCheckbox'] );
		add_action( 'wp_ajax_triggerGiftWrapFees', [$this, 'triggerGiftWrapFees'] );
		add_action( 'wp_ajax_nopriv_triggerGiftWrapFees', [$this, 'triggerGiftWrapFees'] );
		add_action( 'wp_ajax_addGiftOrderMessage', [$this, 'addGiftOrderMessage'] );
		add_action( 'wp_ajax_nopriv_addGiftOrderMessage', [$this, 'addGiftOrderMessage'] );
		add_action( 'woocommerce_cart_calculate_fees', [$this, 'addGiftWrapFees'] );
		add_filter( 'woocommerce_shipping_fields', [$this, 'extraShippingFields'] );
		add_filter( 'woocommerce_admin_shipping_fields' , array( $this, 'admin_shipping_fields' ) );
	}

	/**
	 * Add additional fields to the shipping checkout form.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @param array $fields
	 * @return array $fields
	 */
	public function extraShippingFields( $fields )
	{
		$fields['shipping_phone'] = [
			'label' => __( 'Phone ', 'troly' ),
			'required' => true,
			'priority' => 90,
		];

		$fields['shipping_email'] = [
			'label' => __( 'Email address ', 'troly' ),
			'required' => true,
			'validate' => [ 'email' ],
			'priority' => 90,
		];

		return $fields;
	}

	/**
	 * Add new shipping fields meta in the admin area.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @param array $fields
	 * @return array $fields
	 */
	public function admin_shipping_fields( $fields )
	{
        $fields['email'] = [
            'label' => __( 'Email address', 'troly' )
		];

		$fields['phone'] = [
            'label' => __( 'Phone', 'troly' )
		];

        return $fields;
    }

	/**
	 * Load the view in checkout page to display the gift order functionality.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return void
	 */
	public function addGiftOrderCheckbox()
	{
		include_once TROLY_VIEWS_PATH . '/gift-order-view.php';
	}

	/**
	 * Store a WooCommerce session variable to add the gift wrap fees in the
	 * cart if option is selected in the checkout page.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return void
	 */
	public function triggerGiftWrapFees()
	{
		if ( is_admin() && ! defined( 'DOING_AJAX' ) && ! isset( $_POST['addFees'] ) ) return;

		if ( 'true' === $_POST['addFees'] ) WC()->session->set( 'troly_gift_order_wrap', 1 );
		else WC()->session->__unset( 'troly_gift_order_wrap' );

		wp_die();
	}

	/**
	 * Store the gift order message in WooCommerce session if provided in the
	 * checkout page.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return void
	 */
	public function addGiftOrderMessage()
	{
		if ( is_admin() && ! defined( 'DOING_AJAX' ) && ! isset( $_POST['makeGift'] ) ) return;

		if ( 'true' === $_POST['makeGift'] ) WC()->session->set( 'troly_gift_order_msg', $_POST['giftMsg'] );
		else WC()->session->__unset( 'troly_gift_order_msg' );

		wp_die();
	}

	/**
	 * Add a gift wrap fees to the cart if gift wrapping is selected
	 * in the checkout page.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.21
	 * @return void
	 */
	public function addGiftWrapFees()
	{
		if ( WC()->session->get( 'troly_gift_order_wrap' ) ) {
			$giftWrapPrice = get_option( 'troly_gift_wrap_price' );

			WC()->cart->add_fee( 'Gift wrapping:', $giftWrapPrice );
		}
	}
}