<?php //namespace Troly\frontend\controllers;

/**
 * WooCommerce filters related to users.
 *
 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
 * @since 2.9.20
 */
class UserFiltersController {
	private $trolyCustomer;

	public function __construct()
	{
		add_action( 'init', [$this, 'addAccountEndpoints'] );
		add_filter( 'woocommerce_account_menu_items', [$this, 'addViewMembershipLink'] );
		add_action( 'woocommerce_account_view-membership_endpoint', [$this, 'viewMembershipContent'] );
		add_filter( 'the_title', [ $this, 'membershipEndpointTitle' ], 20 );
	}

	/**
	 * Change the membership endpoint title.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.19.20
	 * @param string $title
	 * @return string $title
	 */
	public function membershipEndpointTitle( $title ) {
		global $wp_query;

		$referralEndpoint = isset( $wp_query->query_vars[ 'view-membership' ] );

		if ( $referralEndpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			$title = __( 'Membership', 'troly' );

			remove_filter( 'the_title', [ $this, 'membershipEndpointTitle' ], 20 );
		}

		return $title;
	}

	/**
	 * Add link in WooCommerce user profile to view membership details.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.20
	 * @param array $menuLinks
	 * @return array $menuLinks
	 */
	public function addViewMembershipLink( $menuLinks )
	{
		$menuLinks = array_slice( $menuLinks, 0, 5, true )
		+ [ 'view-membership' => 'Membership' ]
		+ array_slice( $menuLinks, 5, null, true );

		return $menuLinks;
	}

	/**
	 * Adds an endpoint for WordPress to understand.
	 *
	 * @todo might need to revamp this.
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.20
	 * @return void
	 */
	public function addAccountEndpoints()
	{
		add_rewrite_endpoint( 'view-membership', EP_PAGES );
		flush_rewrite_rules(); // should we call this every time?
	}

	/**
	 * Undocumented function
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.20
	 * @return void
	 */
	public function viewMembershipContent()
	{
		$userID = get_current_user_id();
		$membershipDetails = get_user_meta( $userID, 'current_memberships', true );
		$companyCustomerDetails = get_user_meta( $userID, 'company_customers', true );
		$membershipDetails = $membershipDetails ?? false;
		$companyCustomerDetails = $companyCustomerDetails ? current( $companyCustomerDetails ) : false;

		if ( ! $membershipDetails ) :
			$upsellPageID = WP99234()->template->getUpsellPageID();
			$upsellPagePermalink = get_permalink( $upsellPageID );

			echo '<h3>You currently do not have any membership</h3>';
			echo '<a href="'. $upsellPagePermalink .'">Become a Club Member Now!</a>';
		else :
			$membershipDetails = current( $membershipDetails ); ?>

			<h5>Membership Details</h5>
			<p><?php _e( 'Your membership name is <strong>'. $membershipDetails->name .'</strong> with the membership number <strong>'. ($companyCustomerDetails ? $companyCustomerDetails->membership_num : '&mdash;').' </strong> and join date is the <strong>'. ($companyCustomerDetails ? $companyCustomerDetails->since_date : '&mdash;') .'</strong>. You currently have <strong>'. ($companyCustomerDetails ? ( $companyCustomerDetails->reward_points <= 0 ? '0' : $companyCustomerDetails->reward_points ) : '&mdash;') .'</strong> reward points.', 'troly' ); ?></p>

			<?php if ( (int) $membershipDetails->deliveries >= 0 ) :
				$scheduledDeliveriesText = '';
				for ( $i=1; $i <= $membershipDetails->deliveries; $i++ ) :
					$deliverDate[] = '<strong>' . $membershipDetails->{'delivery_date_' . $i} . '</strong>';
				endfor;

				$lastItem = 'and ' . end( $deliverDate );
				$lastIndex = key( $deliverDate );
				$deliverDate[ $lastIndex ] = $lastItem;

				$scheduledDeliveriesText .= 'Your scheduled deliveries are on '. implode( ', ', $deliverDate );

			else:
				$scheduledDeliveriesText = 'No scheduled deliveries.';
			endif; ?>

			<h5>Scheduled Deliveries</h5>
			<p><?php _e( $scheduledDeliveriesText, 'troly' ); ?></p>

		<?php endif;
	}
}