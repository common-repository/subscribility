<?php namespace Troly\Common;

/**
 * Get data related to plugin version and other store related info.
 *
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 * @since 2.9.19
 */
class InfoDump {
	public function __construct()
	{
		add_action( 'template_redirect', [$this, 'template'] );
		add_filter( 'query_vars', [$this, 'addQueryVariables'] );
	}

	/**
	 * Assign a template to display the output.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	public function template()
	{
		// Make sure our defined query_vars matches.
		if ( get_query_var( 'troly-insight' ) && 'info-dump' === get_query_var( 'troly-insight' ) ) {
			add_filter( 'template_include', function() {
				return TROLY_VIEWS_PATH . '/info-dump-view.php';
			});
		}
	}

	/**
	 * Define query variables for custom template selection.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param array $vars
	 * @return array | $vars
	 */
	public function addQueryVariables( $vars )
	{
        $vars[] = 'troly-insight';
        $vars[] = 'info-dump';

        return $vars;
	}

	/**
	 * Fetch all the info.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	public function fetch()
	{
		$this->fetchPluginsVersion();
		$this->fetchWPVersion();
		$this->fetchWCTaxSetting();
	}

	/**
	 * Fetch WordPress version.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	private function fetchWPVersion()
	{
		echo '<mark>WordPress</mark>:' . get_bloginfo( 'version' ) . '<br/>';
	}

	/**
	 * Fetch WooCommerce tax setting.
	 * Determine if the setting is set to "Inclusive" or "Exclusive".
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	private function fetchWCTaxSetting()
	{
		echo 'Tax Setting:' . ( wc_prices_include_tax() && 'yes' === get_option('woocommerce_prices_include_tax') ? 'Inclusive' : 'Exclusive' ) . '<br/>';
	}

	/**
	 * Fetch all the plugins meta comment data.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return void
	 */
	private function fetchPluginsVersion()
	{
		$plugins = get_plugins();

		foreach ( $plugins as $path => $plugin ) :
			if ( $this->pluginIsActive( $path ) )
				echo $plugin['Name'] . ':' . $plugin['Version'] . '<br/>';
		endforeach;
	}

	/**
	 * Checks whether the plugin is actually active on the site.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @param string $pluginFile
	 * @return boolean
	 */
	private function pluginIsActive( $pluginFilePath )
	{
		$activePlugins = get_option( 'active_plugins', [] );

		return in_array( $pluginFilePath, $activePlugins );
	}
}