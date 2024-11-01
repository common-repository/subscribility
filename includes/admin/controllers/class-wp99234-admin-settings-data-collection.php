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

if ( ! class_exists( 'WP99234_Settings_DataCollection' ) ) :

  /**
   * WP99234_Settings_DataCollection defines the general configurations
   */
  class WP99234_Settings_DataCollection extends WP99234_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

	  $this->id    = 'collection';
	  $this->label = __( 'Data Collection', 'wp99234' );

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
		  'title' => __( 'Newsletter Sign-up', 'wp99234' ),
		  'type'  => 'title',
		  'desc'  => __( 'Customise how the newsletter sign up appears.', 'wp99234' ),
		  'id'    => 'newsletter_title'
		),
		array(
		  'title'    => __( 'Use Placeholders', 'wp99234' ),
		  'desc'     => __( 'For input fields, use placeholders instead of labels to indicate field functionality. For example, Don\'t show the label fields, instead make them inline of the input field.', 'wp99234' ),
		  'id'       => 'wp99234_newsletter_use_placeholders',
		  'css'      => 'min-width:550px;',
		  'default'  => true,
		  'type'     => 'checkbox',
		  'desc_tip' => true,
		),
		array(
		  'title'    => __( 'Collect Mobile Phone', 'wp99234' ),
		  'desc'     => __( 'Capture the customer\'s mobile phone number as part of the newsletter signup and also store it in the customer profile in Troly.', 'wp99234' ),
		  'id'       => 'wp99234_newsletter_collect_mobile',
		  'css'      => 'min-width:550px;',
		  'default'  => false,
		  'type'     => 'checkbox',
		  'desc_tip' => true,
		  'value'    => true,
		),
		array(
		  'title'    => __( 'Collect Postcode', 'wp99234' ),
		  'desc'     => __( 'Capture the customer\'s postcode as part of the newsletter signup and also store it in the customer profile in Troly.', 'wp99234' ),
		  'id'       => 'wp99234_newsletter_collect_postcode',
		  'css'      => 'min-width:550px;',
		  'default'  => true,
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' => true,
		  'options'  => array(
			true     => __( 'Display and require a valid postcode', 'wp99234' ),
			false    => __( 'Display and do not require a valid postcode', 'wp99234' ),
			'hidden' => __( 'Do not display', 'wp99234' )
		  )
		),
		array( 'type' => 'sectionend', 'id' => 'newsletter_title' ),

		array(
		  'title' => __( 'Data Synchronisation', 'wp99234' ),
		  'type'  => 'title',
		  'desc'  => __( 'The following options set how data is synchronised between your Website and Troly. For more information go to <a target="_blank" href="https://troly.io/help/articles/configuring-the-troly-plugin-in-wordpress/">Configuring The Troly Plugin In WordPress</a>.', 'wp99234' ),
		  'id'    => 'sync_options'
		),

		array(
		  'title'    => __( 'Products', 'wp99234' ),
		  'desc'     => __( 'Select how your products are synchronised between Troly and Woocommerce.', 'wp99234' ),
		  'id'       => 'wp99234_product_sync',
		  'css'      => 'min-width:550px;',
		  'default'  => 'both',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' => true,
		  'options'  => array(
			'both' => __( 'Send AND receive products (Troly ↔ Website)', 'wp99234' ),
			'push' => __( 'Only send products (Troly ← Website)', 'wp99234' ),
			'pull' => __( 'Only receive products (Troly → Website)', 'wp99234' ),
			'none' => __( 'Disable Sync', 'troly' ),
		  )
		),

		array(
		  'title'    => __( 'Customers', 'wp99234' ),
		  'desc'     => __( 'Select how your customers are synchronised between Troly and Woocommerce.', 'wp99234' ),
		  'id'       => 'wp99234_customer_sync',
		  'css'      => 'min-width:550px;',
		  'default'  => '',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' => true,
		  'options'  => array(
			'both' => __( 'Send AND receive customers (Troly ↔ Website)', 'wp99234' ),
			'push' => __( 'Only send customers (Troly ← Website)', 'wp99234' ),
			'pull' => __( 'Only receive customers (Troly → Website)', 'wp99234' ),
			'none' => __( 'Disable Sync', 'troly' ),
		  )
		),
		/* TODO: we need a setting to associate club members to a different wp user type */

		array(
		  'title'    => __( 'Clubs', 'wp99234' ),
		  'desc'     => __( 'Shows how your clubs will be synced between Troly and Woocommerce.', 'wp99234' ),
		  'id'       => 'wp99234_club_sync',
		  'css'      => 'min-width:550px;',
		  'default'  => '',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' => true,
		  'options'  => array(
			'both' => __( 'Send AND receive clubs (Troly ↔ Website)', 'wp99234' ),
			'none' => __( 'Disable Sync', 'troly' ),
			// 'push'=>__('Only send clubs (Troly ← Website)','wp99234'),
			// 'pull' =>__('Only receive clubs (Troly → Website)','wp99234')
		  )

		),

		array( 'type' => 'sectionend', 'id' => 'sync_options' ),
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

return new WP99234_Settings_DataCollection();
