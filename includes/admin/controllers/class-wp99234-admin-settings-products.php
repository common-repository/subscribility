<?php
/**
 * Troly WP99234 General Settings.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Settings_Product' ) ) :

  /**
   * WP99234_Settings_Product defines the general configurations
   */
  class WP99234_Settings_Product extends WP99234_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

	  $this->id    = 'product';
	  $this->label = __( 'Product Presentation', 'wp99234' );

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

	  $settings = apply_filters( 'wp99234_general_settings', array(

		array(
		  'title' => __( 'Product Images', 'wp99234' ),
		  'type' => 'title',
		  'desc' => __( 'Choose to use the Troly product images or images uploaded on WooCommerce on this website. Default option is to populate your website with Troly images (set within Troly).', 'wp99234' ),
		  'id' => 'product_display_options'
		),

		array(
		  'title'    => __( 'Use WooCommerce Images', 'wp99234' ),
		  'desc'     => __( 'Enable WooCommerce to use its own product images that you have uploaded and assigned to each product, instead of Troly\'s product images.', 'wp99234' ),
		  'id'       => 'wp99234_use_wc_product_images',
		  'css'      => 'min-width:550px;',
		  'default'  => '',
		  'type'     => 'checkbox',
		  'desc_tip' =>  true,
		),

		array( 'type' => 'sectionend', 'id' => 'product_display_title'),

		array(
		  'title' => __( 'Product Pricing', 'wp99234' ),
		  'type' => 'title',
		  'desc' => __( 'Set when product prices are displayed based on the type of products in Troly. See <a target="_blank" href="https://troly.io/help/articles/understanding-product-pricing-stock/">Understanding Product Pricing Stock</a> for further details on the product types in Troly.', 'wp99234' ),
		  'id' => 'product_display_pricing_options'
		),

		array(
		  'title'    => __( 'Single Product - Member Options', 'wp99234' ),
		  'desc'     => __( 'Display the club price of the product for all public or restricted clubs.', 'wp99234' ),
		  'id'       => 'wp99234_product_display_show_member_pricing_single',
		  'css'      => 'min-width:550px;',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'default'  => 'always',
		  'desc_tip' =>  true,
		  'options'  => array(
			'always'=>__('Always show available member prices','wp99234'),
			'cheapest'=>__('Show the club with the cheapest price','wp99234'),
			'never' => __('Never show the member prices')
		  )
		),

		array(
		  'title'    => __( 'Single Product - Bundle Options', 'wp99234' ),
		  'desc'     => __( 'Allow single bottles to show the 6 or 12 pack pricing. Pricing is shown if the price point is less than the retail price.', 'wp99234' ),
		  'id'       => 'wp99234_product_display_show_single_pricing_pack',
		  'css'      => 'min-width:550px;',
		  'default'  => 'all',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' =>  true,
		  'options'  => array(
			'all'=>__('Show both 6/12 pack pricing','wp99234'),
			'12-pack'=>__('Show 12 pack pricing only','wp99234'),
			'6-pack'=>__('Show 6 pack pricing only','wp99234'),
			'never'=>__('Never show 6/12 pack pricing','wp99234')
		  )
		),

		array(
		  'title'    => __( 'Case/Pack - Member Options', 'wp99234' ),
		  'desc'     => __( 'Display the club price of the pack for all public or restricted clubs.', 'wp99234' ),
		  'id'       => 'wp99234_product_display_show_member_pricing_composite',
		  'css'      => 'min-width:550px;',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'default'  => 'always',
		  'desc_tip' =>  true,
		  'options'  => array(
			'always'=>__('Always show available member prices','wp99234'),
			'cheapest'=>__('Show the club with the cheapest price','wp99234'),
			'never' => __('Never show member prices')
		  )
		),

		array(
		  'title'    => __( 'Case/Pack - Bundle Options', 'wp99234' ),
		  'desc'     => __( 'Allow pack to show the 6 or 12 quantity pricing. Pricing is shown if the price point is less than the retail price.', 'wp99234' ),
		  'id'       => 'wp99234_product_display_show_composite_pricing_pack',
		  'css'      => 'min-width:550px;',
		  'default'  => 'all',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' =>  true,
		  'options'  => array(
			'all'=>__('Show both 6/12 pack pricing','wp99234'),
			'12-pack'=>__('Show 12 pack pricing only','wp99234'),
			'6-pack'=>__('Show 6 pack pricing only','wp99234'),
			'never'=>__('Never show 6/12 pack pricing','wp99234')
		  )
		),

		array(
		  'title'    => __( '6 Pack Title', 'wp99234' ),
		  'desc'     => __( 'What is the text to use for showing 6-pack pricing?', 'wp99234' ),
		  'id'       => 'wp99234_product_display_pack_6_title',
		  'css'      => 'min-width:550px;',
		  'default'  => '6-pack',
		  'type'     => 'text',
		  'desc_tip' =>  true
		),

		array(
		  'title'    => __( '12 Pack Title', 'wp99234' ),
		  'desc'     => __( 'What is the text to use for showing 12-pack pricing?', 'wp99234' ),
		  'id'       => 'wp99234_product_display_pack_12_title',
		  'css'      => 'min-width:550px;',
		  'default'  => '12-pack',
		  'type'     => 'text',
		  'desc_tip' =>  true
		),

		array(
		  'title'    => __( 'Cheapest Price Title', 'wp99234' ),
		  'desc'     => __( 'What is the text to use to indicate lowest member pricing? This will only apply if you choose to show the cheapest price in the above options', 'wp99234' ),
		  'id'       => 'wp99234_product_display_pack_cheapest_title',
		  'css'      => 'min-width:550px;',
		  'default'  => 'Member Price',
		  'type'     => 'text',
		  'desc_tip' =>  true
		),

		[
			'title' => __( 'Show members price with products?', 'troly' ),
			'desc' => __( 'Display an cheapest membership price for each product. Promotes upsell.', 'troly' ),
			'id' => 'troly_show_member_price',
			'css' => 'min-width:550px;',
			'type' => 'checkbox',
			'default' => false,
			'desc_tip' =>  true
		],

		array( 'type' => 'sectionend', 'id' => 'product_display_options'),

	  ) );

	  return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {

	  $settings = $this->get_settings();

	  WP99234_Admin_Settings::save_fields( $settings );
	}

  }

endif;

return new WP99234_Settings_Product();
