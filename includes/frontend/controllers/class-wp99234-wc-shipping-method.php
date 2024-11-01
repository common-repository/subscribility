<?php
/**
 * Custom shipping method for WP99234.
 */
class WP99234_WC_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Constructor for your shipping class
	 *
	 * @access public
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'wp99234_shipping_method'; // Id for your shipping method. Should be uunique.
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Troly Shipping', 'wp99234' ); // Title shown in admin
		$this->method_description = __( 'When enabled, shipping rates will be calculated as per your Troly configuration. Shipping prices you have defined for club members and other customers, based on distance or location, will be applied.<br/>More details at <a target="_blank" href="https://troly.io/help/articles/understanding-the-shipping-stage-4-of-5/">https://troly.io/help/articles/understanding-the-shipping-stage-4-of-5/</a>' ); // Description shown in admin
		$this->instance_form_fields = array(
		  'enabled' => array(
			'title'       => __( 'Enable/Disable' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable this shipping method' ),
			'default'     => 'yes'
		  ),
		  'title'   => array(
			'title'       => __( 'Shipping title' ),
			'type'        => 'text',
			'description' => __( 'Shipping using your preferred shipping provider from Troly, if none is set then the cheapest provider will be used.' ),
			'default'     => __( 'Shipping' ),
			'desc_tip'    => false
		  )
		);
		$this->enabled = $this->get_option( 'enabled' );//"yes"; // This can be added as an setting but for this example its forced enabled
		$this->title   = $this->get_option( 'title' );//__( 'Shipping', 'wp99234' ); // This can be added as an setting but for this example its forced.

		$this->supports = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );

		$this->init();
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * Triggers a call to the SUBS shipping API to calculate the shipping price.
	 *
	 * @access public
	 *
	 * @param mixed $package
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = Array() )
	{
		/* Return nothing if empty postcode or we're local pickup */
		if ( empty( $package['destination']['postcode'] ) ||
			 explode( ':', WC()->session->get( 'chosen_shipping_methods' )[0] )[0] == 'local_pickup' )
		{
			$rate = [
				'id' => $this->id,
				'label' => $this->title,
				'cost' => 0.00,
				'calc_tax' => 'per_item',
			];
		} else if(
			!empty( $package['destination']['postcode'] ) &&
			explode( ':', WC()->session->get( 'chosen_shipping_methods' )[0] )[0] == 'flat_rate'){
			$rate = [
				'id' => $this->id,
				'label' => $this->title,
				'cost' => $package['rates']['flat_rate']->cost,
				'calc_tax' => 'per_item',
			];
		} else {
			$data = [
				'delivery_address' => $package[ 'destination' ][ 'address' ],
				'delivery_suburb' => $package[ 'destination' ][ 'city' ],
				'delivery_postcode' => $package[ 'destination' ][ 'postcode' ],
				'delivery_state' => $package[ 'destination' ][ 'state' ],
				'delivery_country' => WP99234()->_api->get_formatted_country_name( $package[ 'destination' ][ 'country' ] ),
				'products' => [],
			];

			$uneditableProductIDs = isset( $_SESSION['uneditable_products'] ) ? $_SESSION['uneditable_products'] : [];

			foreach ( $package['contents'] as $key => $item ) {
				if ( ! in_array( $item['product_id'], $uneditableProductIDs ) ) {
					$subs_product_id = get_post_meta( $item['product_id'], 'subs_id', true );
					$data['products'][] = [
						'id'  => $subs_product_id,
						'qty' => $item['quantity'],
					];
				}
			}

			if ( is_user_logged_in() ) {
				$current_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true );

				if ( is_array( $current_memberships ) && ! empty( $current_memberships ) ) {
					$current_membership = array_pop( $current_memberships );

					if ( isset( $current_membership ) ) {
						$data['membership_type_id'] = $current_membership->membership_type_id;
					}
				}
			}

			$endpoint = WP99234_Api::$endpoint . 'shipments/quote.json';
			$results = WP99234()->_api->_call( $endpoint, $data, 'POST' );

			WP99234()->logger->error( 'Shipping Call Results Below( ' . $endpoint . ' )' );
			WP99234()->logger->error( json_encode( $results ) );

			if ( $results && isset( $results->price ) ) {
				if ( 0 === (int) $results->price ) {
					$rate = [
						'id' => $this->id,
						'label' => 'Free Shipping',
						'calc_tax' => 'per_item',
						'cost' => $results->price,
						'meta_data' => [
							'troly_error' => false,
						]
					];
				} else {
					$rate = [
						'id' => $this->id,
						'label' => $this->title,
						'cost' => $results->price,
						'calc_tax' => 'per_item',
						'meta_data' => [
							'troly_error' => false
						]
					];
				}
			} else {
				$rate = [
					'id' => $this->id,
					'label' => $this->title . " : A shipping fee could not be calculated. Please check your address details or contact us to place this order.",
					'cost' => 0,
					'meta_data' => [
						'troly_error' => true
					]
				];
			}
		}

		$this->add_rate( $rate );
	}
}
