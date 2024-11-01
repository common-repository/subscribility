<?php
/**
 * Troly WP99234 Operations Page.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Operations_Page' ) ) :

/**
 * WC_Operations_Page.
 */
abstract class WP99234_Operations_Page {

    /**
     * Operations page id.
     *
     * @var string
     */
    protected $id = '';

    /**
     * Operations page label.
     *
     * @var string
     */
    protected $label = '';

    /**
     * Constructor.
     */
    public function _construct() {
        //No need for now
    }

    /**
     * Add this page to operations.
     */
    public function add_operations_page( $pages ) {
        $pages[ $this->id ] = $this->label;

        return $pages;
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        return apply_filters( 'wp99234_get_settings_' . $this->id, array() );
    }

    /**
     * Output the settings.
     */
    public function output() {
        $settings = $this->get_settings();

        WP99234_Admin_Operations::output_fields( $settings );
    }

    /**
     * Save settings.
     */
    public function save() {
        global $current_section;

        $settings = $this->get_settings();
        WP99234_Admin_Settings::save_fields( $settings );

        if ( $current_section ) {
            do_action( 'wp99234_update_options_' . $this->id . '_' . $current_section );
        }
    }
}

endif;
