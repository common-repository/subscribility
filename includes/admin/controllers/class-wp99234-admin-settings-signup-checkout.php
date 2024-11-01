<?php

defined( 'ABSPATH' ) || exit;

class SignupCheckoutSettings extends WP99234_Settings_Page {

	public function __construct()
	{
		$this->id    = 'signup_checkout';
		$this->label = __( 'Signup & Checkout', 'troly' );

		add_filter( 'wp99234_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wp99234_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wp99234_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = apply_filters( 'wp99234_general_settings', [
			[
				'title' => __( 'Membership Signup', 'troly' ),
				'type'  => 'title',
				'desc'  => __( 'Configure the membership signup settings.', 'troly' ),
				'id'    => 'registration_title'
			],
			[
				'title'    => __( 'Capture Date of Birth', 'troly' ),
				'desc'     => __( 'Set if the date of birth needs to be captured at checkout, membership form or both.', 'troly' ),
				'id'       => 'troly_require_dob',
				'css'      => 'min-width:550px;',
				'class' => 'troly-wc-select',
				'default'  => true,
				'type'     => 'select',
				'desc_tip' => true,
				'options'  => [
					'' => __( 'Don\'t Capture', 'troly' ),
					'checkout' => __( 'Capture on Checkout Page Only', 'troly' ),
					'membership' => __( 'Capture on Membership Form Only', 'troly' ),
					'both' => __( 'Capture on Both', 'troly' ),
				]
			],
			[
				'title'    => __( 'Upsell Membership on Checkout', 'troly' ),
				'desc'     => __( 'This will offer your customers with possibility to become a member with their current purchase.', 'troly' ),
				'id'       => 'troly_club_membership_signup',
				'css'      => 'min-width:550px;',
				'default'  => 'overlay',
				'type'     => 'select',
				'class' => 'troly-wc-select',
				'desc_tip' => true,
				'options'  => [
					''                          => __( 'Disabled', 'troly' ),
					'purchase_now_and_signup'   => __( 'Immediately offer club benefits on current order to new members', 'troly' ),
					'future_club_purchase_only' => __( 'Don\'t process order immediately, record selection for future club run', 'troly' ),
				],
			],
			[
				'title'    => __( 'Club Signup Form', 'troly' ),
				'desc'     => __( 'Select the Club Membership Signup page from the list.', 'troly' ),
				'id'       => 'troly_upsell_redirect_page',
				'css'      => 'min-width:550px;',
				'class' => 'troly-wc-select troly-wc-select--has-search',
				'default'  => 'overlay',
				'type'     => 'select',
				'desc_tip' => true,
				'options'  => $this->getAllPublishedPages(),
			],
			[
				'title' => __( 'Troly Forms Layout', 'troly' ),
				'desc_tip' => true,
				'desc' => __( 'Stylize the form fields with placeholders or labels.' ),
				'id' => 'troly_forms_layout',
				'css' => 'min-width:550px;',
				'class' => 'troly-wc-select',
				'type' => 'select',
				'options' => [
					'placeholder' => __( 'Use Placeholders Instead of Labels', 'troly' ),
					'label' => __( 'Use Labels Instead of Placeholders', 'troly' ),
					'both' => __( 'Use Both Labels and Placeholders', 'troly' ),
				],
			],
			[
				'title' => __( 'Abandoned Cart (in hrs)', 'troly' ),
				'desc_tip' => true,
				'desc' => __( 'Set the time after which the cart is set to abandoned.' ),
				'id' => 'troly_abandoned_cart_buffer',
				'css' => 'min-width:550px;',
				'type' => 'text',
				'placeholder' => 'eg. 2'
       ],
//        [
// 				'title' => __( 'Gift Wrapping Price', 'troly' ),
// 				'desc_tip' => true,
// 				'desc' => __( 'Enter a flat gift wrap price to enable. Leave blank to disable' ),
// 				'id' => 'troly_gift_wrap_price',
// 				'css' => 'min-width:550px;',
// 				'type' => 'text',
// 				'placeholder' => get_woocommerce_currency_symbol(). ' Wrapping fees'
// 			],

			[
				'title' => __( 'Select a Coupon', 'troly' ),
				'type' => 'select',
				'class' => 'troly-wc-select',
				'desc' => __( 'Select a coupon to assign it with your referral code.', 'troly' ),
				'desc_tip' => true,
				'id' => 'troly_member_referral_coupon',
				'css' => 'min-width:550px;',
				'options' => $this->getAllCoupons(),
			],

			array( 'type' => 'sectionend', 'id' => 'registration_title' ),

		] );


		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Get all the coupons found in the DB
	 */
	public function getAllCoupons()
	{
		$coupons = [
			'' => 'Select a Coupon',
		];

		$allCoupons = get_posts( [
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'asc',
			'post_type' => 'shop_coupon',
			'post_status' => 'publish',
		] );

		// @todo maybe show the coupon type (fixed, percentage) info as well
		foreach ( $allCoupons as $coupon ) :
			$coupons[ $coupon->post_name ] = $coupon->post_title;
		endforeach;

		return $coupons;
	}

	public function getAllPublishedPages()
	{
		$pages = [
			'' => 'Select a Page',
		];

		$allPages = get_pages();

		foreach ( $allPages as $page ) :
			$pages[ $page->ID ] = $page->post_title;
		endforeach;

		return $pages;
	}

	/**
	 * Save settings.
	 */
	public function save() {

	  $settings = $this->get_settings();

	  WP99234_Admin_Settings::save_fields( $settings );
	}
}

return new SignupCheckoutSettings;
