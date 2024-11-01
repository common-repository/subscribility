<?php
/**
 * Admin Configuration
 *
 * @author      EmpireOne Group
 * @category    Admin
 * @package     Troly/Admin
 * @version     1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Get all WooCommerce screen ids.
 *
 * @return array
 */
function wp99234_get_screen_ids () {

	$wp99234_screen_id = sanitize_title( __( 'Troly', 'wp99234' ) );
	$screen_ids   = array(
		'toplevel_page_' . $wp99234_screen_id,
        'toplevel_page_wp99234',
		$wp99234_screen_id . '_page_wp99234',
		$wp99234_screen_id . '_page_wp99234-operations',
		'toplevel_page_wp99234-operations',
	);

	return apply_filters( 'wp99234_screen_ids', $screen_ids );
}


function query_time_server ( $timeserver, $socket ) {
	$fp = fsockopen( $timeserver, $socket, $err, $errstr, 5 );
	# parameters: server, socket, error code, error text, timeout
	if ( $fp ) {
		fputs( $fp, "\n" );
		$timevalue = fread( $fp, 49 );
		fclose( $fp ); # close the connection
	} else {
		$timevalue = " ";
	}

	$ret    = array();
	$ret[ ] = $timevalue;
	$ret[ ] = $err;     # error code
	$ret[ ] = $errstr;  # error text

	return ( $ret );
} # function query_time_server


function check_timestamp () {

	$timeserver = "ntp.pads.ufrj.br";
	$timercvd   = query_time_server( $timeserver, 37 );

	//if no error from query_time_server
	if ( ! $timercvd[ 1 ] ) {

		$current_time = time();

		$timevalue = bin2hex( $timercvd[ 0 ] );
		$timevalue = abs( HexDec( '7fffffff' ) - HexDec( $timevalue ) - HexDec( '7fffffff' ) );
		$timestamp  = $timevalue - 2208988800; # convert to UNIX epoch time stamp

		$diff = $timestamp - $current_time;

		wp_die( 'The time difference is currently ' . $diff . ' seconds.' );

	} else {
		echo "Unfortunately, the time server $timeserver could not be reached at this time. ";
		echo "$timercvd[1] $timercvd[2].<br>\n";
		exit;
	}
}

/**
 * Validates date according to the supplied format.
 *
 * @param string $format
 * @param string $date
 * @param boolean $return_date
 * @return void
 */
function verifyDate( $format, $date, $return_date = false )
{
	$datepicker_date_format = str_replace(
		array(
			'd', 'j', 'l', 'z', // day
			'F', 'M', 'n', 'm', // month
			'Y', 'y'            // year
		),
		array(
			'dd', 'd', 'DD', 'o',
			'MM', 'M', 'm', 'mm',
			'yy', 'y'
		),
		$format
	);

	$d = DateTime::createFromFormat($format, $date);
	if($return_date)
		return $d;
	else
		return $d && $d->format($format) === $date;
}

/**
 * Display a legal drinking age disclaimer if selected to display from settings.
 * This will display the disclaimer either as a website overlay on initial load,
 * or as a wc_print_notice on the woocommerce checkout page.
 *
 * @return void
 */
function wp99234_check_disclaimer_display() {

	$display_disclaimer = get_option('wp99234_display_legal_drinking_disclaimer');

	switch($display_disclaimer) {
		case 'overlay':
		    // Disclaimer modal render in footer to prevent html from breaking
			add_action('get_footer', 'wp99234_overlay_legal_drinking_age_disclaimer');
			break;
		case 'checkout':
			add_action('woocommerce_before_checkout_form', 'wp99234_checkout_legal_drinking_age_disclaimer');
			break;
		default:
			break;
	}
}
add_action( 'init', 'wp99234_check_disclaimer_display' );

/**
 * Display legal disclaimer as a notice on checkout page.
 */
function wp99234_overlay_legal_drinking_age_disclaimer() {

	$disclaimer_title = get_option('wp99234_legal_disclaimer_title');
	$disclaimer_message = get_option('wp99234_legal_disclaimer_text');

	if (!empty($disclaimer_message)) {

		$is_first_visit = true;

		if ( (isset($_COOKIE['_wp99234_age_disclaimer']) && $_COOKIE['_wp99234_age_disclaimer'] == 'accepted') || is_user_logged_in()) {
			$is_first_visit = false;
		}

		if ($is_first_visit) {
			$html_output = "<section id='wp99234-disclaimer_overlay'>";
			$html_output .= "  <div class='wp99234-disclaimer-window'>";
			$html_output .= "    <div class='wp99234-disclaimer-text'>".nl2br($disclaimer_message)."</div>";
			$html_output .= "    <div class='wp99234-disclaimer-button-area'>";
			$html_output .= "      <div style='text-align:center;'>";
			$html_output .= "        <button class='woocommerce-Button button' onclick='window.location.href = \"about:blank\";'>Exit</button>&nbsp;";
			$html_output .= "        <button class='woocommerce-Button button' onclick='remove_overlay()'>I agree</button>";
			$html_output .= "      </div>";
			$html_output .= "    </div>";
			$html_output .= "  </div>";
			$html_output .= "</section>";

			$html_output .= "<script type='application/javascript'>";
			$html_output .= "  document.body.style.overflow = 'hidden';";
			$html_output .= "  function remove_overlay() {";
			$html_output .= "    document.getElementById('wp99234-disclaimer_overlay').style.display = 'none';";
			$html_output .= "    document.body.style.overflow = 'scroll';";
			$html_output .= "    var expdate = new Date(new Date().getTime() + (1000*60*60*24*28));";
			$html_output .= "    document.cookie = '_wp99234_age_disclaimer=accepted;expires=' + expdate + ';path=/';";
			$html_output .= "  }";
			$html_output .= "</script>";

			echo $html_output;
		}
	}
}

/**
 * Display legal disclaimer as a notice on checkout page.
 */
function wp99234_checkout_legal_drinking_age_disclaimer() {

	$disclaimer_message = get_option('wp99234_legal_disclaimer_text');

	if (!empty($disclaimer_message)) {
		wc_print_notice($disclaimer_message, 'notice');
	}
}

/**
 * Remove all Troly session settings on logout.
 *
 * @return void
 */
function wp99234_remove_session() {
    unset($_SESSION['wp99234_cart_fees']);
    unset($_SESSION['uneditable_products']);
    unset($_SESSION['composite_subproduct_ids']);
    unset($_SESSION['troly_user_id']);
    unset($_SESSION['apply_membership_discounts']);
    unset($_SESSION['order_min_qty']);
	unset($_SESSION['order_can_edit']);
	unset($_SESSION['composite_non_pre_pack_objs']);
	unset($_SESSION['composite_pre_pack_ids']);
	unset($_SESSION['composite_pre_pack_objs']);
}
add_action('wp_logout', 'wp99234_remove_session');

/**
 * Troly needs to create this function on those hosts so as to prevent 500 errors being thrown
 * Fix from https://wordpress.org/support/topic/call-to-undefined-function-getallheaders/
 */
if (!function_exists('getallheaders')) {
	/**
	 * @return array
	 */
	function getallheaders() {
		$headers = [];
			foreach ($_SERVER as $name => $value) {
				if (substr($name, 0, 5) == 'HTTP_') {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
		return $headers;
	}
}

if ( ! function_exists( 'verifyBirthday' ) ) {
	// Apply compatibility option for DOB
	if ( 'checkout' === get_option( 'troly_require_dob' ) ||
		'both' === get_option( 'troly_require_dob' ) ) {
			add_action( 'woocommerce_checkout_process', 'verifyBirthday' );
	}

	/**
	 * Verifies date of birth in Checkout page.
	 *
	 * @param array $data
	 * @param boolean $print_missing_dob_notice
	 * @return void
	 */
	function verifyBirthday( $data, $print_missing_dob_notice = false )
	{
		if ( ! isset( $data ) || empty( $data ) ) $data = $_POST;

		$dateFormat = get_option( 'date_format' );

		// Check if set, if its not set add an error.
		if ( ! isset( $data['subs_birthday'] ) || empty( $data['subs_birthday'] ) ) {
			if ( $print_missing_dob_notice )
				wc_add_notice( __( 'To continue, please enter a date of birth' ), 'error' );

			return;
		}
		elseif ( ! verifyDate( $dateFormat, $data['subs_birthday'] ) ) {
			wc_add_notice( __( 'Please enter a valid date of birth in the format '.verifyDate( $dateFormat, $data['subs_birthday']), 'error' ) );

			return;
		} elseif( time() < strtotime( '+' . get_option('wp99234_legal_drinking_age', '18') . 'years', verifyDate( $dateFormat, $data['subs_birthday'], true )->format( 'U' ) ) ) {
			wc_add_notice( __( get_option('wp99234_legal_age_error_text', 'You must be at least 18 years of age to purchase alcohol from this site.')), 'error');
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'troly_15s_cron' ) ) {
	/**
	 * Create a 15 seconds schedule for Troly CRON operations.
	 *
	 * @param array $schedules
	 * @return array $schedules
	 */
	function troly_15s_cron( $schedules ) {
		$schedules['15_seconds'] = [
			'interval' => 15,
			'display'  => esc_html__( 'Every Fifteen Seconds' ),
		];

		return $schedules;
	}

	add_filter( 'cron_schedules', 'troly_15s_cron' );
}