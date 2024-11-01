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

if ( ! class_exists( 'WP99234_Settings_Connection' ) ) :

  /**
   * WP99234_Settings_Connection defines the general configurations
   */
  class WP99234_Settings_Connection extends WP99234_Settings_Page {

    /**
     * Constructor.
     */
    public function __construct() {

      $this->id    = 'connection';
      $this->label = __( 'Connection to Troly', 'wp99234' );

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

        $consumer_key = get_option( 'wp99234_consumer_key' );
        $resource_key = get_option( 'wp99234_resource_key' );
        $check_no = get_option( 'wp99234_check_no' );

        // Get last connected date
        $last_connected = get_option( 'wp99234_last_connected_date' );

        // Check if connection settings provided
        if ($consumer_key == '' || $resource_key == '' || $check_no == '') {
            $last_connected = false;
        }

      $settings = apply_filters( 'wp99234_general_settings', array(

        array(
          'title' => __( 'Account Access', 'sp99234' ),
          'type'  => 'title',
          'desc'  => __( 'The following values are used to communicate with Troly. Each can be found in the <a href="//' . WP99234_DOMAIN . '/a/single?addon=Wordpress">Wordpress add-on</a> page of your account.' ),
          'id'    => 'general_options'
        ),
        array(
          'title'    => __( 'Consumer key', 'wp99234' ),
          'desc'     => __( 'Provided by Troly to uniquely identify the data accessed.', 'wp99234' ),
          'id'       => 'wp99234_consumer_key',
          'css'      => 'min-width:350px;',
          'default'  => '',
          'type'     => 'text',
          'desc_tip' => true,
        ),

        array(
          'title'    => __( 'Resource Key', 'wp99234' ),
          'desc'     => __( 'Provided by Troly, uniquely identifies the data accessed.', 'wp99234' ),
          'id'       => 'wp99234_resource_key',
          'default'  => '',
          'type'     => 'text',
          'css'      => 'min-width: 350px;',
          'desc_tip' => true,
        ),

        array(
          'title'    => __( 'Check Number', 'wp99234' ),
          'desc'     => __( 'Provided by Troly to validate the values above.', 'wp99234' ),
          'id'       => 'wp99234_check_no',
          'css'      => 'min-width: 350px;',
          'default'  => '',
          'type'     => 'text',
          'desc_tip' => true
        ),

        array( 'type' => 'sectionend', 'id' => 'general_options' ),

        array(
          'title' => __( '', 'wp99234' ),
          'type'  => ($last_connected ? 'title' : 'hidden'),
          'desc'  => __( '<img width="32" height="32" src="' . WP99234_URI . '/includes/admin/assets/images/icons8-checkmark-512.png" /> <span class="wp99234-last-connected">Last Successful connection to Troly on ' . $last_connected . '</span>', 'wp99234' ),
          'id'    => 'log_options'
        ),

        array( 'type' => 'sectionend', 'id' => 'general_last_connected' ),
      ) );

      return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
    }

    /**
     * Save settings.
     */
    public function save() {

      $settings = $this->get_settings();

      WP99234_Admin_Settings::save_fields( $settings );

        // also set last connected date
        $date = date('l jS \of F Y h:i:s A');
        update_option( 'wp99234_last_connected_date', $date );
    }

  }

endif;

return new WP99234_Settings_Connection();
