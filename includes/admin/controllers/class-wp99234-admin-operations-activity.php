<?php
/**
 * Troly WP99234 Activity Operations.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Operations_Activity' ) ) :

    /**
     * WP99234_Operations_Activity defines the general configurations
     */
    class WP99234_Operations_Activity extends WP99234_Operations_Page {

        /**
         * Constructor.
         */
        public function __construct() {

            $this->id    = 'activity';
            $this->label = __( 'Activity', 'wp99234' );

            add_filter( 'wp99234_operations_tabs_array', array( $this, 'add_operations_page' ), 20 );
            add_action( 'wp99234_settings_' . $this->id, array( $this, 'output' ) );
            add_action( 'wp99234_settings_save_' . $this->id, array( $this, 'save' ) );
        }
    }

endif;

return new WP99234_Operations_Activity();
