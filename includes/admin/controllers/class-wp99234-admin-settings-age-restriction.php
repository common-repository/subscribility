<?php
/**
 * Troly WP99234 General Settings.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

defined( 'ABSPATH' ) || exit;

class AgeRestrictionSettings extends WP99234_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

	  $this->id    = 'age_restriction';

	  $this->label = __( 'Age Restriction', 'troly' );


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
		  'title' => __( 'Age Restriction Registration Settings', 'wp99234' ),
		  'type'  => 'title',
		  'desc'  => __( 'Configure the registration settings based on the minimum drinking age of your customers and the related disclaimers that will be shown to your customers at registration.', 'wp99234' ),
		  'id'    => 'registration_title'
		),
		array(
		  'title'    => __( 'Minimum Drinking Age', 'wp99234' ),
		  'desc'     => __( 'The minimum drinking age of your customers.', 'wp99234' ),
		  'id'       => 'wp99234_legal_drinking_age',
		  'css'      => 'width:35px;',
		  'default'  => 18,
		  'type'     => 'text',
		  'desc_tip' => true,
		),

		array( 'type' => 'sectionend', 'id' => 'miscellaneous_options' ),
		array(
		  'title' => __( '', 'wp99234' ),
		  'type'  => 'title',
		  'desc'  => __( '', 'wp99234' ),
		  'id'    => 'miscellaneous_options'
		),

		array(
		  'title'    => __( 'When and where to show the minimum drinking age disclaimer', 'wp99234' ),
		  'desc'     => __( 'Set when and how to display a minimum drinking age disclaimer on your website. This will show the first time a customer visits your website.', 'wp99234' ),
		  'id'       => 'wp99234_display_legal_drinking_disclaimer',
		  'css'      => 'min-width:550px;',
		  'default'  => 'overlay',
		  'type'     => 'select',
		  'class' => 'troly-wc-select',
		  'desc_tip' => true,
		  'options'  => array(
			'overlay'  => __( 'Show on all pages as a pop-up for all first time visitors', 'wp99234' ),
			'checkout' => __( 'Show on checkout page as a warning', 'wp99234' ),
			'no'       => __( 'Don\'t display the disclaimer', 'wp99234' )
		  )
		),
		array(
		  'title'    => __( 'Minimum Drinking Disclaimer Text', 'wp99234' ),
		  'desc'     => __( 'Set the text to display on the minimum drinking age disclaimer.', 'wp99234' ),
		  'id'       => 'wp99234_legal_disclaimer_text',
		  'css'      => 'min-width:550px; min-height:80px;',
		  'default'  => 'By law, we may only supply alcohol to persons aged ' . get_option( 'wp99234_legal_drinking_age' ) . ' years or over. We will retain your date of birth for our records.',
		  'type'     => 'textarea',
		  'desc_tip' => true,
		),

		array(
		  'title'       => __( 'Minimum Age not met message', 'wp99234' ),
		  'desc'        => __( 'If the minimum age is not met, set the message to be displayed to the customer.', 'wp99234' ),
		  'id'          => 'wp99234_legal_age_error_text',
		  'css'         => 'min-width:550px; min-height:80px;',
		  'default'     => 'You must be at least 18 years of age purchase alcohol or be a club member from this site.',
		  'placeholder' => 'You must be at least 18 years of age purchase alcohol or be a club member from this site.',
		  'type'        => 'textarea',
		  'desc_tip'    => true,
		),

		array( 'type' => 'sectionend', 'id' => 'registration_title' ),
	  ) );

	  return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
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

return new AgeRestrictionSettings;
