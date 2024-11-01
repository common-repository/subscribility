<?php namespace Troly\Bootstrap;

/**
 * Troly plugin bootstrap class.
 *
 * @todo Move all bootstrap functions in this class.
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 * @since 2.9.19
 */
class BootstrapController {
	private static $_instance;

	public function __construct()
	{
		add_filter( 'plugin_action_links_' . TROLY_PLUGIN_BASENAME, [$this, 'addActionLinks'] );
	}

	/**
	 * Instantiate the class.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return object | BootstrapController::class
	 */
	public static function boot()
	{
		if ( ! self::$_instance ) self::$_instance = new self;

		return self::$_instance;
	}

	/**
	 * Add additional plugin action links.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param array $links
	 * @return array
	 */
	public static function addActionLinks( $links ) {
		$actionLinks = [
			'settings' => '<a href="' . admin_url( 'admin.php?page=wp99234' ) . '"><span class="trolyicon"></span>' . __( 'Settings', 'troly' ) . '</a>',
			'docs' => '<a href="https://troly.io/help/tags/provider-website-wordpress/" target="_blank"><span class="trolyicon"></span>' . __( 'Documentation', 'troly' ) . '</a>'
		];

		return array_merge( $links, $actionLinks );
	}
}
