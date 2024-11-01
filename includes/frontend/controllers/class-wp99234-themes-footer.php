<?php

/**
 * Troly supported themes footer override class.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class TrolySupportedThemesFooter {
	private static $_instance;

	public function __construct()
	{
		add_action( 'wp_footer', [$this, 'et_get_footer_credits' ] );
		add_filter( 'x_option_x_footer_content', [$this, 'xtheme_troly_credits'], 10, 1 );
	}

	/**
	 * Override Divi footer
	 *
	 * NOTE: This applies to Divi Template only
	 */
	public function et_get_footer_credits() {
		$credits_format = '<%2$s id="footer-info">%1$s</%2$s>';

		if ( !function_exists( 'et_get_option' )) {
			return;
		}

		$footer_credits = et_get_option( 'custom_footer_credits', '' ) . ' Powered by Troly <span style="color:red;">&#10084;</span>';
		return et_get_safe_localization( sprintf( $credits_format, $footer_credits, 'div' ) );
	}

	/**
	 * Override X Theme Footer
	 *
	 * NOTE: This applies to X Theme only
	 */
	public function xtheme_troly_credits( $string ) {
		// Add Troly footer
		return $string . ' Powered by Troly <span style="color:red;">&#10084;</span>';
	}

	/**
	 * Initialize the class instance and trigger the
	 * override.
	 *
	 * @return object TrolySupportedThemesFooter
	 */
	public static function trigger()
	{
		return self::$_instance ? self::$_instance : self::$_instance = new self;
	}
}
