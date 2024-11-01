<?php
/**
 * Plugin Name: Troly
 * Plugin URI: https://wordpress.org/plugins/subscribility/
 * Description: Manage and fulfil your sales of wine, beers and other crafted beverages, through clubs and other direct-to-consumer sales channels.
 * Version: 2.9.26
 * Author: Troly
 * Author URI: https://troly.io
 * Text Domain: wp99234
 * Domain Path: /i18n/languages/
 *
 * @package WP99234
 * @category Core
 * @author Troly
 */

/**
 * @TODO Testing
 *
 * Product Update In Subs pushes to WP
 *  -- How to test?
 *
 * @TODO - Roadmap
 *
 * -- Create products to enable users to register as a member in Troly
 *
 * -- Give the user the option change their stored CC details in checkout, user profile and wp-admin profile.
 *
 * -- Stock level Integration.
 *  -- Stock levels are pulled in with the product import (and pushed from subs on change, including an order event.)
 *
 *
 * @TODO - Unit Tests for all classes and functions.
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
}

//Load the composer autoload file
include_once( 'vendor/autoload.php' );


/**
 * Main Troly Class
 */

use Troly\common\InfoDump;
use Troly\bootstrap\BootstrapController;
use Troly\frontend\controllers\CustomerController;

final class WP99234 {

	/**
	 * wp99234 version.
	 *
	 * @var string
	 */
	public $version = '1.4';
	public $db_version = '1.2';

	/**
	 * The single instance of the class.
	 *
	 * @var wp99234
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * The administration options
	 *.
		* @var WS_Admin
		*/
	var $_admin = null;

	/**
	 * The API handle
	 *
	 * @var WS_Api
	 */
	var $_api = null;

	/**
	 *
	 *
	 * @var WS_Products
	 */
	var  $_products = null;

	/**
	 *
	 *
	 * @var WS_Woocommerce
	 */
	var $_woocommerce = null;

	/**
	 *
	 *
	 * @var WS_Users
	 */
	var $_users = null;

	/**
	 *
	 *
	 * @var WPS_Registration
	 */
	var $_registration = null;

	/**
	 *
	 *
	 * @var WS_Newsletter
	 */
	var $_newsletter = null;

	/**
	 *
	 *
	 * @var WS_Company
	 */
	var $_company = null;

	/**
	 *
	 *
	 * @var WS_Template
	 */
	var $template = null;

	/**
	 *
	 *
	 * @var WS_Prices
	 */
	var $_prices = null;

	/**
	 * Generic errors array.
	 *
	 * @var WS_Errors
	 */
	var $_errors = array();

	/**
	 * Logger
	 *
	 * @var WS_Logger
	 */
	var $_logger = null;

	public $_customer;

	/**
	 * WP99234 Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->check_requirements();

		if ( ! empty( $this->errors ) ) {
			add_action( 'admin_notices', [ $this, 'failedChecksNotice' ] );
			return;
		}

		$this->includes();
		$this->init_hooks();

		do_action( 'wp99234_loaded' );

		BootstrapController::boot();
	}

	/**
	 * PLugin prerequisite/dependency checks.
	 *
	 * @return void
	 */
	private function check_requirements() {
		/**
		 * Check if WooCommerce is installed and active.
		 */
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$this->errors[] = new Exception( __( 'Troly requires <a href="https://wordpress.org/extend/plugins/woocommerce/" target="_blank">WooCommerce</a> to be installed and configured to operate correctly.', 'troly' ) );
		}

		/**
		 * Checks and compares running PHP version on the server.
		 */
		if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
			$this->errors[] = new Exception( __( 'Troly requires PHP to be version 5.6 or higher to function correctly. Your PHP version is: <strong>'. PHP_VERSION . '</strong>' , 'troly' ) );
		}

		/**
		 * Checks and sets up log dir for Troly.
		 */
		$dir = wp_normalize_path( TROLY_LOG_DIR );
		if ( ! is_dir( $dir ) ) {
			if ( ! @mkdir( $dir, 0770 ) ) {
				// return new WP_Error(
				//     'no_writable_directory',
				//     __('Could not find a suitable location for the log file.', 'troly')
				// );
				$this->errors[] = new Exception( __('Could not find a suitable location for the log file.', 'troly') );
			}
		}
	}

	/**
	 * Admin notices for critical dependency check.
	 *
	 * @return void
	 */
	public function failedChecksNotice()
	{ ?>
		<div class="error notice">
			<h3>Oops! There are some error(s) with Troly</h3>
			<ul>
			<?php foreach( $this->errors as $error ) : ?>
				<li>&mdash; <?php _e( $error->getMessage(), 'troly' ); ?></li>
			<?php endforeach; ?>
			</ul>
		</div>
	<?php }

	/**
	 * Defines some commonly used things used in this plugin.
	 */
	private function define_constants() {
		$this->define( 'TROLY_PLUGIN_FILE', __FILE__ );
		$this->define( 'TROLY_PLUGIN_PATH', plugin_dir_path( TROLY_PLUGIN_FILE ) );
		$this->define( 'TROLY_VIEWS_PATH', TROLY_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'views' );
		$this->define( 'WP99234_HOST_IP', '172.105.161.151');
		$this->define( 'WP99234_DOMAIN', isset( $_ENV['TROLY_API_URL'] ) ? $_ENV['TROLY_API_URL'] : 'https://app.troly.io');
		$this->define( 'WP99234_ABSPATH', trailingslashit( WP_PLUGIN_DIR . '/' . str_replace(basename( __FILE__ ) , "" , plugin_basename( __FILE__ ) ) ) );
		$this->define( 'WP99234_URI', str_replace( array( 'http://', 'https://' ), '//', trailingslashit( WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__ ) , "" , plugin_basename( __FILE__ ) ) ) ) );
		$this->define( 'WP99234_DBVERSION', $this->db_version );
		$this->define( 'WP99234_VERSION', $this->version );

		$this->define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
		$this->define('TROLY_LOG_DIR', TROLY_PLUGIN_PATH . 'logs/');
		$this->define('TROLY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Includes the required files from the lib directory and bootstraps the classes.
	 * @since  1.3
	 */
	function includes() {
		include_once( 'includes/admin/controllers/class-wp99234-admin.php' );

		$this->_admin = new WP99234_Admin();

		//include_once( 'includes/class-wp99234-menus.php' );
		//PHP Compatibility functions.
		include_once( 'includes/common/functions/php_compat.php' );
		include_once( 'includes/common/functions/class-wp99234-functions.php' );
		include_once( 'includes/common/class-wp99234-info-dump.php');
		include_once( 'includes/bootstrap/class-wp99234-bootstrap-controller.php');
		include_once( 'includes/common/models/class-wp99234-price.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-logger.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-api.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-clubs.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-forms.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-newsletter-forms.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-prices.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-orders.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-products.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-registration-forms.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-users.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-wc-filter.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-template.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-themes-footer.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-customer-controller.php' );
		include_once( 'includes/frontend/controllers/class-wp99234-order-controller.php' );
		include_once( 'includes/services/class-wp99234-utm-tracking-controller.php' );
		include_once( 'includes/services/class-wp99234-abandoned-cart-controller.php' );

		$this->_products = new WP99234_Products();
		$this->_users = new WP99234_Users();
		$this->_registration = new WP99234_Registration_Forms();
		$this->_newsletter = new WP99234_Newsletter_Forms();
		$this->_clubs = new WP99234_Clubs();
		$this->_prices = new WP99234_Prices();
		$this->_orders = new WP99234_Orders();
		$this->template = new WP99234_Template();
		$this->_woocommerce = new WP99234_WC_Filter();
		$this->_logger = new TrolyLogger();
		$this->_customer = new CustomerController();
		$this->_infoDump = new InfoDump();
		$this->_utmTracking = new TrolyUTMTrackingController();
		$this->_abandonedCart = new TrolyAbandonedCartController();

		// Trigger supported themes' footer override.
		TrolySupportedThemesFooter::trigger();

		try {
			$this->_api = new WP99234_Api();
		} catch( WP99234_Api_Exception $e ) {
			$this->errors[] = $e->getMessage();
			wp99234_log_troly( 0, 0, 0, 'Plugin includes loading', $e->getMessage() );
		}

		//Cloudinary setup
		Cloudinary::config( array(
			'cloud_name' => 'subscribility-p'
		));

		//Initlalize the logger class.
		$this->logger = new Katzgrau\KLogger\Logger(TROLY_LOG_DIR, 'error');

		//Set log level to debug.
		$this->logger->setLogLevelThreshold( 'debug' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  1.3
	 */
	function init_hooks()
	{
		// Add session
		add_action( 'init', array($this, 'boot_session' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ));
		add_action( 'wp_enqueue_scripts', array ( $this, 'wp_styles' ));
		add_action( 'wp_enqueue_scripts', array ( $this, 'wp_scripts' ));
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		//add_action( 'init', array( $this, 'init_late' ), 20 );

		/* Remove Troly shipping if we have an issue */
		// add_filter( 'woocommerce_package_rates', array( $this, 'remove_troly_shipping'), 10, 2);
	}

	function boot_session() {
		if (!headers_sent() && '' == session_id()) {
			@ob_start();
			session_start();
		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
					define( $name, $value );
			}
	}


	/**
	 * plugins_loaded hook.
	 */
	function plugins_loaded() {

		$stored_dbversion = get_option('wp99234_db_version');

		// Run DB upgrade if no stored version or new version set
		if (!$stored_dbversion || $stored_dbversion !== WP99234_DBVERSION) {
			$this->handle_db_update( WP99234_DBVERSION );
		}

		//Load the i18n textdomain.
		load_plugin_textdomain( 'wp99234', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n' );
	}

	/**
	 * Removes the Troly shipping method if we have an error
	 * on the server side
	 *
	 * Currently disabled, pending investigation due to local_pickup issue
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	function remove_troly_shipping( $rates, $package ) {
		if(isset($rates['wp99234_shipping_method']) && $rates['wp99234_shipping_method']->get_meta_data()){
			if($rates['wp99234_shipping_method']->get_meta_data()['troly_error'] == '1'){
				WC()->session->set( 'chosen_shipping_methods', null );
				unset($rates['wp99234_shipping_method']);
			}
		}
		return $rates;
	}


	/**
	 * Late Init hook. Add a session cookie for the user.
	 */
	function init_late() {
		if ( ! is_user_logged_in() && ! is_admin() ){
			if ( isset( WC()->session ) ){
				WC()->session->set_customer_session_cookie( true );
			}
		}
	}

	/**
	 * Main WP Subs Instance
	 *
	 * Ensures only one instance of WP Subs is loaded or can be loaded.
	 *
	 * @return WP99234 - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Enqueue plugin specific CSS
	 */
	public function wp_styles() {
		$wp_scripts = wp_scripts();
		wp_enqueue_style('plugin_name-admin-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css');
		wp_enqueue_style( 'wp99234_frontend_css', WP99234_URI . 'includes/frontend/assets/css/wp99234_frontend.css', ['dashicons', 'woocommerce-layout'] );
	}

	/**
	 * Enqueue plugin specific JS
	 */
	public function wp_scripts () {
		wp_enqueue_script( 'jquery_debounce_js', WP99234_URI . 'includes/frontend/assets/js/jquery.ba-throttle-debounce.js', $deps = array('jquery'),$ver = false, true );
		wp_enqueue_script( 'wp99234_frontend_js', WP99234_URI . 'includes/frontend/assets/js/wp99234_frontend.js', $deps = array(),$ver = false, true );

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-payment-troly', WP99234_URI . 'includes/frontend/assets/js/jquery-payment/jquery.payment.min.js' );

		if ( is_page( wc_get_page_id( 'checkout' ) ) ){
				wp_enqueue_script( 'wp99234_websocket_rails', WP99234_URI . 'includes/frontend/assets/js/WebSocketRails.js' );
				// wp_enqueue_script( 'wp99234_checkout', WP99234_URI . 'includes/frontend/assets/js/wp99234_checkout.js', null, true );
		}
	}

	/**
	 * Enqueue plugin specific CSS
	 */
	public function admin_styles () {
		$wp_scripts = wp_scripts();
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		// Register admin styles
		wp_register_style( 'wp99234_admin_styles', WP99234()->plugin_url() . '/includes/admin/assets/css/wp99234-admin.css', array(), WP99234_VERSION );
		//wp_register_style( 'woocommerce_admin_menu_styles', WC()->plugin_url() . '/assets/css/menu.css', array(), WC_VERSION );
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		//wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

		// Sitewide menu CSS
		wp_enqueue_style( 'woocommerce_admin_menu_styles' );

		// Admin styles for WC pages only
		if ( in_array( $screen_id, wp99234_get_screen_ids() ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			//wp_enqueue_style( 'jquery-ui-style' );
		}
		wp_enqueue_style( 'wp99234_admin_styles' );
	}

	public function admin_scripts() {

		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$wp99234_screen_id = sanitize_title( __( 'Troly', 'wp99234' ) );
		//$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$suffix = '';

		//wp_register_script( 'wp99234_admin', WP99234()->plugin_url() . '/include/assets/js/admin/wp99234_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WP99234_VERSION );
		wp_register_script( 'wp99234_admin', WP99234()->plugin_url() . '/includes/admin/assets/js/wp99234-admin' . $suffix . '.js', array( 'jquery', 'jquery-ui-core', 'jquery-tiptip', 'select2' ), WP99234_VERSION );
		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );

		wp_enqueue_script( 'wp99234_admin' );
	}

	/**
	 * Get a var dump as a string for logging purposes.
	 *
	 * @param $var
	 *
	 * @return string
	 */
	public function get_var_dump( $var ){
		ob_start();
		var_dump( $var );
		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

	/**
	 * Handle Database version updates.
	 *
	 * @param $current_version
	 */
	public function handle_db_update($current_version)
	{
		$versions = array('1.1', '1.2'); // Add new version here

		foreach ($versions as $version) {
			$to_upgrade = false;
			switch ($version) {
				case '1.1':
					add_option('wp99234_product_display_show_composite_pricing_pack', 'all');
					add_option('wp99234_product_display_pack_6_title', '6-pack');
					add_option('wp99234_product_display_pack_12_title', 'Case');
					add_option('wp99234_product_display_show_member_pricing_single', 'all');
					add_option('wp99234_product_display_show_single_pricing_pack', 'cheapest');
					add_option('wp99234_product_display_show_member_pricing_composite', 'cheapest');
					add_option('wp99234_product_display_pack_cheapest_title', 'Member Price');
					add_option('wp99234_newsletter_collect_mobile', 'no');
					add_option('wp99234_newsletter_collect_postcode', 'no');
					$to_upgrade = true; // set to upgrade DB immediately
				case '1.2':
					// Make this optional to upgrade in 1st run/install
					$to_upgrade = WP99234_Clubs::setup_customer_tags();
			}
			if ($to_upgrade) {
				update_option( 'wp99234_db_version', $version );
			}
		}
	}

	/**
	 * Deliver a message for an SSE listener.
	 *
	 * @param $id
	 * @param $message
	 * @param string $event
	 * @param int $progress
	 */
	function send_sse_message( $id, $message, $event = 'message', $progress = 0 ) {

		if(ob_get_level()){ ob_flush(); ob_end_clean(); }

		// Our array to send
		$d = array( 'message' => $message, 'progress' => $progress, 'type' => $event);

		// Server-Side Events (SSE) payload
		echo "event: $event" . PHP_EOL;
		echo "id: $id" . PHP_EOL;
		echo "data: " . json_encode( $d ) . PHP_EOL;
		echo PHP_EOL;

		// Flush to forcefully send it!

		flush();
	}

	// Handles setting up authentication information
	public function sign_user_in($troly_customer_id){
		// If we have a user_id from troly, get the wordpress user_id and log them in automatically
		// This is set inside the order check
		$_SESSION['troly_user_id'] = $troly_customer_id;
		
		if (isset($_SESSION['troly_user_id']) && $_SESSION['troly_user_id'] > 0) {
			// get wordpress user_id
			$user = WP99234()->_users->get_user_by_subs_id($_SESSION['troly_user_id']);

			if ($user && get_current_user_id() != $user->ID) {
				// Get the wordpress returned object rather than sql object above
				$user = get_user_by('id', $user->ID);

				// Clear the Wordpress auth cookies
				wp_clear_auth_cookie();

				/* Tell WooCommerce to handle logging in*/
				wc_set_customer_auth_cookie($user->ID);

				// Return true, indicating we did setup a new session
				return true;
			} else if($user) {
				return get_current_user_id() == $user->ID;
			}
		}
		// All other instances, return false
		return false;
	}
}

/**
 * Function to call when referencing the main object.
 *
 * @return WP99234
 */
function WP99234() {
		return WP99234::instance();
}

// Global for backwards compatibility.
$GLOBALS['WP99234'] = WP99234();

/**
 * Workaround for CRON job to work
 */
// Force certificate validation. You need a valid certificate in the site, self generated certificates are NOT VALID.
add_filter( 'https_local_ssl_verify', '__return_true' );

// Setting a custom timeout value for cURL. Using a high value for priority to ensure the function runs after any other added to the same action hook.
add_action('http_api_curl', 'sar_custom_curl_timeout', 9999, 1);
function sar_custom_curl_timeout( $handle ){
    curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 ); // 30 seconds. Too much for production, only for testing.
    curl_setopt( $handle, CURLOPT_TIMEOUT, 30 ); // 30 seconds. Too much for production, only for testing.
}
// Setting custom timeout for the HTTP request
add_filter( 'http_request_timeout', 'sar_custom_http_request_timeout', 9999 );
function sar_custom_http_request_timeout( $timeout_value ) {
    return 30; // 30 seconds. Too much for production, only for testing.
}
// Setting custom timeout in HTTP request args
add_filter('http_request_args', 'sar_custom_http_request_args', 9999, 1);
function sar_custom_http_request_args( $r ){
    $r['timeout'] = 30; // 30 seconds. Too much for production, only for testing.
    return $r;
}
