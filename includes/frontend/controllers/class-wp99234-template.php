<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class WP99234_Template
 * (SEB) Defines the template overrides / hooks for wordpress+woocommerce
 */
class WP99234_Template {

	var $post_id = null;

	var $use_troly_images = false;

	public function __construct() {
		add_action( 'init', [$this, 'initialize'] );
	}

	/**
	 * Retrieve the ID for the upsell redirect page.
	 *
	 * @author Aditya Bhaskar Sharma <adityabhaskarsharma@gmail.com>
	 * @since 2.9.19
	 * @return int|string $id
	 */
	public function getUpsellPageID()
	{
		$id = get_option( 'troly_upsell_redirect_page', '' );

		return $id;
	}

	function initialize(){
		$use_wc_product_imgs = get_option('wp99234_use_wc_product_images');

		if (empty($use_wc_product_imgs) || $use_wc_product_imgs == "no") {
		  $this->use_troly_images = true;
		}

		$this->setup_actions();
		$this->setup_filters();
		$this->setup_shortcodes();
	}

	/**
	 * Setup the required WP Actions.
	 */
	function setup_actions(){
		add_action( 'template_redirect', array( $this, 'on_template_redirect' ) );
	}

	/**
	 * initialise the required filters for
	 */
	function setup_filters(){

		//get_{$meta_type}_metadata
		add_filter( 'get_post_metadata', array( $this, 'get_post_metadata_filter' ), 10, 4 );

		if ($this->use_troly_images) {
		  // Thumbnail used on shop pages and alike
		  add_filter( 'post_thumbnail_html', array( $this, 'post_thumbnail_html_filter' ), 10, 5 );

		  // When WooCommerce asks for images, lets manipulate it
		  add_filter( 'woocommerce_product_get_image', array( $this, 'get_cl_url' ), 10, 5 );

		  // Single product image html
		  add_filter( 'wp_get_attachment_image_src', array($this, 'retrieve_featured_image'), 99, 4);

		  // Alter the admin page to show the correct featured image HTML
		  add_filter( 'admin_post_thumbnail_html', array( $this, 'show_troly_featured_image'), 10, 3 );

		}

		add_filter( 'get_the_terms', array( $this, 'get_the_terms_filter' ), 10, 3 );

		add_filter( 'woocommerce_product_tabs', array( $this, 'filter_woocommerce_product_tabs' ) );

		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_woocommerce_checkout_fields' ) );

		add_filter( 'the_content', array( $this, 'handle_hidden_content' ) );
	}

	/**
	 * Filter the woocommerce_get_image to enable the cloudinary integration.
	 * apply_filters( 'admin_post_thumbnail_size', $size, $thumbnail_id, $post );
	 * @param $size
	 * @param $thumbnail_id
	 * @param $post
	 *
	 * @return string
	 */
	function show_troly_featured_image( $content, $post_id ) {

	  if(get_post_type( $post_id ) == 'product' && is_admin() && isset(get_post_meta($post_id)['subs_id'][0])){

		$hero_img = WP99234()->template->get_var( 'hero_img', $post_id );

		$html = $this->get_cl_image_html( $hero_img, $size = 'thumbnail', $attr = array() );

		return '<p>To change this image, please <a href="//'.WP99234_DOMAIN.'/p/'.get_post_meta($post_id)['subs_id'][0].'" target="_blank">upload a new image</a> in Troly.</p>'.$html;
	  } else {
		return $content;
	  }
	}

	/**
	 * Filter the woocommerce_get_image to enable the cloudinary integration.
	 * apply_filters( 'woocommerce_product_get_image', wc_get_relative_url( $image ), $this, $size, $attr, $placeholder );
	 * @param $image_url
	 * @param $wc_product
	 * @param $size
	 * @param $size
	 * @param $placeholder
	 *
	 * @return string
	 */
	function get_cl_url( $image, $wc_product, $size, $attr, $placeholder ){

		/* If we are not a WooCommerce single product page, just return the image */

		$hero_img = @WP99234()->template->get_var( 'hero_img', $wc_product->get_id() );

		// Show default image place-holder
		if ( !$hero_img || is_null($hero_img->url) ) { return $image; };

		$html = $this->get_cl_image_html( $hero_img, $size, $attr );

		return $html;

	}


	/**
	* Return the hero_img url if we are unable to return an attachment
	* @param $image
	* @param $attachment_id
	* @param $size
	* @param $icon
	*
	* @todo Fix this function and use a caching mechanism to load the assets.
    * @return array
    */
    function retrieve_featured_image($image, $attachment_id, $size, $icon)
    {
	// Make sure we're manipulating the intended images only.
	if ( ! is_woocommerce() || ! in_the_loop() ) return $image;

		$product = @WP99234()->template->get_var( 'hero_img', get_the_ID() );

        /* This handles the specific instance of being an image loaded on
        WooCommerce page but the image is _not_ a product */
        if ( ! $product || is_null( $product->url ) ) return $image;

		$featured_image = $product->url;
		$sizes = WP99234()->template->get_var( 'hero_img_size', get_the_ID() );

		if ($size === 'full' || $size === 'woocommerce_single') {
			$featured_image = $product->tasting_item->url;

			// Replace cloudinary image height to 600
			$featured_image = preg_replace("/h_\d*/", 'h_600', $featured_image);

			// Replace cloudinary image width to 600
			$featured_image = preg_replace("/w_\d*/", 'w_600', $featured_image);

			// Replace cloudinary image c_fit to c_pad
			$featured_image = preg_replace("/c_fit/", 'c_pad', $featured_image);

			// Set cloudinary image quality to 95
			$featured_image = preg_replace("/q_\d*/", 'q_95', $featured_image);

			$sizes = [600, 600];
		}
		else if ($size === 'woocommerce_thumbnail') {
			$featured_image = $product->tasting_item->url;

			// Replace cloudinary image height to 300
			$featured_image = preg_replace("/h_\d*/", 'h_300', $featured_image);

			// Replace cloudinary image width to 300
			$featured_image = preg_replace("/w_\d*/", 'w_300', $featured_image);

			$sizes = [300, 300];
		}

		@list( $width, $height ) = $sizes;

		if (!isset($sizes) || $width == 0 || $height == 0) {
			@list( $width, $height ) = [ 300, 300 ];
			WP99234()->template->set_var( 'hero_img_size', get_the_ID(), [$width, $height] );
		}

		return array($featured_image, $width, $height);
	}

	/**
	 * Filter for the_content, automagically add the product metadata before and after the default content.
	 *
	 * @TODO - Add an option to disable this?
	 *
	 * @param $content
	 * @return string
	 */
	function load_single_product_template( $content ){
		global $post;

		if( get_post_type( $post ) !== 'wp99234_product' || ! is_single() ){
			return $content;
		}

		$_content = $this->get_template( 'single_product.php' );

		if( ! $_content ){
			WP99234()->logger->error( 'single_product.php was not found.' );
			return $content;
		}

		return $_content;

	}

	/**
	 * Filter the post_thumbnail_html to enable the cloudinary integration.
	 *
	 * @param $html
	 * @param $post_id
	 * @param $post_thumbnail_id
	 * @param $size
	 * @param $attr
	 *
	 * @return string
	 */
	function post_thumbnail_html_filter( $html, $post_id, $post_thumbnail_id, $size, $attr ){

	   /* If a post thumbnail isn't found, just return the image */

		$hero_img = @WP99234()->template->get_var( 'hero_img', $post_id );

		// Show default image place-holder
		if ( !$hero_img || is_null($hero_img->url) ) { return $html; };

		$html = $this->get_cl_image_html( $hero_img, $size, $attr );

		return $html;

	}

	/**
	 * Get the HTML for a given Cloudinary Image
	 *
	 * @param $hero_img
	 * @param $size
	 * @param array $attr
	 *
	 * @return bool|string
	 */
	function get_cl_image_html( $hero_img, $size, $attr = array()  ){

		$image_sizes = $this->get_all_image_sizes();

		$html = '';

		$image_size_attrs = array();

		if( $hero_img && ! empty( $hero_img ) && $hero_img->url ){
			if( is_array( $size ) ){
				$image_size_attrs = array(
					'width'  => $size[0],
					'height' => $size[1],
					'crop'   => ( isset( $size[2] ) ) ? $size[2] : false
				);
			} elseif( isset( $image_sizes[$size] ) ){
				$image_size_attrs = array(
					'width'  => $image_sizes[ $size ][ 'width' ],
					'height' => $image_sizes[ $size ][ 'height' ],
					'crop'   => $image_sizes[ $size ][ 'crop' ]
				);
			}

			$parsed_url = parse_url( $hero_img->url );

			$_url_data = explode( '/', $parsed_url['path'] );

			$image_name = array_pop( $_url_data );

			unset($image_size_attrs['crop']);

			# here we are defining the "base" attributes and image transformations for the
			# Cloudinary based on the configured wordpress / woocommerce image sizes.
			#
			# Note that a new filter is being called which allow a child theme to receive
			# the image information apd override it. For onstance, if an image was updated
			# in cloudinary as a portrait bottleshot, but the theme would like to enforce a
			# square aspect ratio (woocommerce doesn't calculate aspect ratio on images it
			# doesn't host!), then the theme can overide.
			#
			# See https://res.cloudinary.com/subscribility-p/image/upload/c_pad,b_white,h_450,w_450,q_80/ea3rzplkcjslsee757fl.jpg
			# and change the 'white' for 'red' for a visual example

			$opts = array_merge(array(
				'width'  => $image_size_attrs['width'],
				'height' => $image_size_attrs['height'],
				'crop'   => 'pad',
				'class'  => ('img_size_' . is_array($size) ? 'woocommerce_thumbnail' : $size)
			), apply_filters('wp99234_woocommerce_image_transformation', $image_size_attrs));

			if (isset($opts['height']) && $opts['height'] == 0) { unset($opts['height']); }
			if (isset($opts['width']) && $opts['width'] == 0) { unset($opts['width']); }

			if( ! empty( $attr ) ){
				$opts = array_merge( $opts, $attr );
			}

			$html = cl_image_tag( $image_name, $opts );

		}

		return $html;

	}

	/**
	 * Filter comments_open to hide the comments section.
	 *
	 * @param $open
	 * @param $post_id
	 *
	 * @return bool
	 */
	function comments_open_filter( $open, $post_id ){

		if( get_post_type( $post_id ) === WP99234()->_products->products_post_type ){
			return false;
		}

		return $open;

	}

	/**
	 * Override the check for _thumbnail_id to ensure that we can always return true to has_post_thumbnail so we can override with the Troly image.
	 *
	 * Also checks for actual existance of the hero_img.
	 *
	 * @param $value
	 * @param $object_id
	 * @param $meta_key
	 * @param $single
	 *
	 * @return bool
	 */

	function get_post_metadata_filter( $value, $object_id, $meta_key, $single ){

		$product = get_post( $object_id );

		if( get_post_type( $product ) !== WP99234()->_products->products_post_type ) {
			return $value;
		}

		switch ( $meta_key ) {

			case '_thumbnail_id':

				// it is possible for the get_post_metadata_filter to be called for other properties we could
				// want to override, however when it comes to images we need to make sure we follow the
				// settings made available to our plugin users.

				if ($this->use_troly_images) {

					$hero_img = WP99234()->template->get_var( 'hero_img', $object_id );
					if ( $hero_img && !empty($hero_img) && isset($hero_img->url) ) {

						//print_r(array('value' => $value, 'object_id' => $object_id, 'meta_key' => $meta_key, 'single' => ($single ? TRUE : FALSE), 'hero_img' => $hero_img));

						// the value we return here will be used for both
						// has_post_thumbnail() and
						// get_post_thumbnail_id()
						// We must return a valid post_id for this to work. To be safe, we return
						// the id of the current post, after checking that it's a product driven by Troly
						// Requires an array to be returned

						$value = $object_id;
						//$value = $hero_img->url;
						if (!$single) {
							$value = array($value);
						}
					}
				}

			break;
		}

		return $value;

	}


	/**
	 * Filter the get_the_terms in order to hide the product-config terms from the frontend.
	 *
	 * @param $terms
	 * @param $post_id
	 * @param $taxonomy
	 *
	 * @return mixed
	 */
	function get_the_terms_filter( $terms, $post_id, $taxonomy ){

		if( is_admin() ){
			return $terms;
		}

		if( $taxonomy == WP99234()->_products->tag_taxonomy_name ){

			$config_term = get_term_by( 'slug', 'product-config', WP99234()->_products->tag_taxonomy_name );

			foreach( $terms as $key => $term ){
				if( $term->parent == $config_term->term_id ){
					unset( $terms[$key] );
				}
			}

		}

		return $terms;

	}


	/**
	 * Initialize the plugin shortcodes.
	 * @TODO - Make this work.
	 */
	function setup_shortcodes(){

		// Prevent render when editing in WP admin
		if ( is_admin() ) {
		  return;
		}

		add_shortcode( 'wp99234_registration_form', array( WP99234()->_registration, 'get_form' ) );
		// For compatibility with previous versions of the shortcode.
		add_shortcode( 'wpsubs_registration_form', array( WP99234()->_registration, 'get_form' ) );

		add_shortcode( 'wp99234_newsletter_registration', array( WP99234()->_newsletter, 'get_form' ) );
		// For compatibility with previous versions of the shortcode.
		add_shortcode( 'wpsubs_newsletter_registration', array( WP99234()->_newsletter, 'get_form' ) );

	}

	/**
	 * Locate the given template in the theme 'wp99234' directory or the plugin before looking in the default WP locations.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	function locate_template( $template ){

	  if( file_exists( get_stylesheet_directory() . '/troly/' . $template ) ){
		  return get_stylesheet_directory() . '/troly/' . $template;
	  }

	  if( file_exists( get_stylesheet_directory() . '/wp99234/' . $template ) ){
		  return get_stylesheet_directory() . '/wp99234/' . $template;
	  }


	  if( file_exists( get_stylesheet_directory() . '/subscribility/' . $template ) ){
		  return get_stylesheet_directory() . '/subscribility/' . $template;
	  }

	  if( file_exists( WP99234_ABSPATH . 'includes/frontend/views/' . $template ) ){
		  return WP99234_ABSPATH . 'includes/frontend/views/' . $template ;
	  }

	  return locate_template( $template );

	}

	/**
	 * Get the contents of a template in a string to enable further manipulation or parsing before output.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	function get_template( $template, $var_name=null, $args=null ){

		if( $_template = $this->locate_template( $template ) ){

			ob_start();
			if($var_name && $args){
			  if(isset($args['filename']))
				unset($args['filename']);
			  $$var_name = (object)$args;
			  $args = null;
			}
			include $_template;
			$content = ob_get_contents();
			ob_end_clean();

			return $content;

		}

		return false;

	}

	/**
	 * Add the product information tab to the default WC tabs settings.
	 *
	 * @param $tabs
	 *
	 * @return mixed
	 */
	function filter_woocommerce_product_tabs( $tabs ){

		unset( $tabs['description'] );

		$tabs['product_info'] = array(
			'title' => __( 'Product Information', 'wp99234' ),
			'priority' => 10,
			'callback' => array( $this, 'generate_product_info_tab_html' )
		);

		return $tabs;

	}

	/**
	 * Modify the checkout fields where required.
	 *
	 * @param $fields
	 */
	function filter_woocommerce_checkout_fields( $fields ){

		$fields['order']['order_comments']['label'] = __( 'Delivery notes and instructions', 'wp99234' );

		return $fields;

	}

	function generate_product_info_tab_html(){
		echo WP99234()->template->get_template( 'product_meta.php' );
	}

	/**
	 * Get a variable from the posts meta.
	 *
	 * @param $var
	 * @param null $post_id
	 *
	 * @return mixed
	 */
	public function get_var( $var, $post_id = null ){

		if( $post_id == null ){
			$post_id = get_the_ID();
		}

		$pm =  get_post_meta( $post_id, $var, true );

		// This forces a false value
		return ($pm == '' ? false : $pm);

	}

	/**
	 * Set a variable from the posts meta.
	 *
	 * @param $var
	 * @param null $post_id
	 * @param null $content
	 *
	 * @return mixed
	 */
	public function set_var( $var, $post_id = null, $content = null){

		if( $post_id == null ){
			$post_id = get_the_ID();
		}

		return update_post_meta( $post_id, $var, $content );

	}

	/**
	 * Get the awards list for the current product.
	 *
	 * @return bool|string
	 */
	function awards_list(){

		$award_meta_fields = array(
			'award_1',
			'award_2',
			'award_3',
			'award_4',
		);

		$li_items = '';

		foreach( $award_meta_fields as $award_meta_field ){
			$field = $this->get_var( $award_meta_field );
			if( $field && ! empty( $field ) ){
				$li_items .= sprintf( '<li>%s</li>', $field );
			}
		}

		if( ! empty( $li_items ) ){
			return sprintf( '<ul class="wp99234_awards_list">%s</ul>', $li_items );
		}

		return false;

	}

	/**
	 * Get the prices list for the current product.
	 *
	 * Since 2.8, we now support options for pricing
	 *
	 * @return bool|string
	 */
	public function price_list($raw=false){
		global $post;

		// Which product are on Woo?
		$current_product = wc_get_product( $post->ID );

		// For single products (i.e. bottles), do we show member pricing?
		$single_show_member_pricing = get_option('wp99234_product_display_show_member_pricing_single');

		// For single products, do we show pack prices?
		$single_show_pack_pricing = get_option('wp99234_product_display_show_single_pricing_pack');

		// For single products (i.e. bottles), do we show member pricing?
		$composite_show_member_pricing = get_option('wp99234_product_display_show_member_pricing_composite');

		// For single products, do we show pack prices?
		$composite_show_pack_pricing = get_option('wp99234_product_display_show_composite_pricing_pack');

		// For later boolean comparisons
		$show_pack_pricing   = (get_post_meta($current_product->get_id(), 'is_composite', true) ? $composite_show_pack_pricing : $single_show_pack_pricing);
		$show_member_pricing = (get_post_meta($current_product->get_id(), 'is_composite', true) ? $composite_show_member_pricing : $single_show_member_pricing);

		// Get the product variety
		$product_variety = get_post_meta(get_the_ID(), '_variety', true);
		$product_variety = ($product_variety && ! empty($product_variety)) ? $product_variety : "wine";

		/* Internal fields used to help process information */
		$price_meta_fields = array(
		  'price'      => __( (($product_variety != 'wine' && $product_variety != 'beer') ? 'item' : 'bottle'), 'wp99234' ),
		  'price_6pk'  => __( '6-pack', 'wp99234' ),
		  'price_case' => __( '12-pack', 'wp99234' )
		);


		// What price are we going to show that the customer will be paying?
		$price_to_display = false;

		/* The raw output - helps us analyse the correct price to show and what HTML to render */
		$raw_items = ['price_meta' => ['Single' => ''], 'Single'=>'', 'Cheapest Member Price'=>null];

		/* Get out current membership pricings */
		$mt = null;
		if (is_user_logged_in()) {
			$current_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true );
			if (is_array($current_memberships) && !empty($current_memberships)) {
				// Set the membership type to only display prices from this membership type
			   $mt = reset($current_memberships)->membership_type_id;
			}
		}

		/*
		 * Get the list of memberships so we can filter out product prices for private memberships
		 * of which the customer is not a member
		 */
		$current_company_memberships = get_option( 'wp99234_company_membership_types' );

		// Membership prices from
		$product_prices = WP99234()->_prices->get_membership_prices_for_product( get_the_ID(), 'all' );

		// Get the membership price
		if($product_prices && $show_member_pricing != 'never') {
		  foreach( $product_prices as $product_price ){
			$price_mem_id = $product_price->membership_id;
			if($raw && $current_company_memberships[$price_mem_id]->visibility !== "private" && $show_member_pricing == 'always'){
			  $raw_items['Member Prices'][$current_company_memberships[$price_mem_id]->name] = $this->format_currency($product_price->price);
			}

			// Don't display membership price for a different membership
			// Ignore this price if it is for a private membership and they are not a member of that membership
			// We can use the previous condition and we only need to handle non-members here
			if( (!$product_price->price || $product_price->price <= 0 ) || (isset($mt) && ($price_mem_id != $mt || $current_company_memberships[$price_mem_id]->visibility == "private"))){
			  continue;
			}
			else if( ! $price_to_display || $product_price->price < $price_to_display->price ) {
			  $price_to_display = $product_price;
			}
		  }
		}

		$li_items = '';

		$out = '';

		foreach( $price_meta_fields as $price_meta_field => $title ){
		  if($title == '6-pack' && ($show_pack_pricing != 'all' && $show_pack_pricing != '6-pack')) continue;
		  if($title == '12-pack' && ($show_pack_pricing != 'all' && $show_pack_pricing != '12-pack')) continue;

			$field = $this->get_var( $price_meta_field );
			if ( $field && ! empty( $field ) && $field > 0) {
			  if ($mt != null && $price_to_display && $price_to_display->price < $field) {
				  // Don't display the 6pack/12 pack price if they would always get their member price over this one
				  $raw_items[$title] = $this->format_currency( $price_to_display->price );
			  } else {
				if($raw){
				  $raw_items[$title] = $this->format_currency( $field );
				} else {
				  $li_items .= sprintf( '<li><strong>%s</strong> %s</li>', $title, $this->format_currency( $field ) );
				}
			  }
			}
		}

		if( ! empty( $li_items ) ){
			$out .= sprintf( '<ul class="wp99234_price_list">%s</ul>', $li_items );
		}

		if( $price_to_display ){
			$current_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true );
			if( $mt != null) {
			  if($raw){
				$raw_items['Single'] = wc_price( $price_to_display->price);
				$raw_items['price_meta']['Single'] = $price_to_display->price;
			  } else {
				$out .= sprintf( __( 'Member price %s', 'wp99234' ), wc_price( $price_to_display->price ) );
			  }
			} else {
			  if($raw){
				$raw_items['Single'] = $this->format_currency($current_product->get_price());
				$raw_items['price_meta']['Single'] = $current_product->get_price();
				if($show_member_pricing == 'cheapest')
				  $raw_items['Cheapest Member Price'] = wc_price( $price_to_display->price);
			  } else {
				$out .= sprintf( __( 'Member price starting from %s', 'wp99234' ), wc_price( $price_to_display->price ) );
			  }
			}
		} else if ($raw && !$price_to_display) {
		  $raw_items['Single'] = $this->format_currency($current_product->get_price());
		  $raw_items['price_meta']['Single'] = $current_product->get_price();
		}

		if($raw){
		  $raw_items['price_meta']['inStock'] = $current_product->is_in_stock();
		  $raw_items['is_composite'] = get_post_meta($current_product->get_id(), 'is_composite', true);
		  return $raw_items;
		} else {
		  return $out;
		}

	}

	/**
	 * Display the categories and tags for the current product.
	 */
	public function product_categories(){
		global $post;

		$categories = get_the_term_list( $post->ID, 'wp99234_category' );
		$tags = get_the_term_list( $post->ID, 'wp99234_tag' );

		if( $categories ){
			echo $categories . '<br />';
		}

		if( $tags ){
			echo $tags;
		}

	}

	public function format_currency( $amount ){

		if( function_exists( 'wc_price' ) ){
			return wc_price( $amount );
		} else {
			return  '$' . number_format( $amount, 2 );
		}

	}

	/**
	 * Return an array with all image sizes and details.
	 *
	 * @return array|bool
	 */
	public function get_all_image_sizes() {

		global $_wp_additional_image_sizes;

		$sizes = array();
		$get_intermediate_image_sizes = get_intermediate_image_sizes();

		// Create the full array with sizes and crop info
		foreach( $get_intermediate_image_sizes as $_size ) {

			if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

				$sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
				$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
				$sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

				$sizes[ $_size ] = array(
					'width' => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
				);

			}

		}

		return $sizes;

	}

	/**
	 * template_redirect hook. If in a post or page and the content has been marked as member only content, handle the required logic.
	 *
	 * If the user is logged in but not a registered member (IE, is just a regular customer)
	 * they will see the content of the page overwritten with a notice that they need to upgrade @see handle_hidden_content().
	 *
	 * If they are not logged in, they will be redirected to the login page.
	 *
	 */
	public function on_template_redirect(){
		global $post;

		if( is_singular( array( 'post', 'page' ) ) ){

			$hide_content = get_post_meta( $post->ID, 'wp99234_hide_content', true );

			if( $hide_content && $hide_content == 1 ){

				if( ! is_user_logged_in() ){

					$redirect = get_permalink( wc_get_page_id( 'myaccount' ) );

					wc_add_notice( apply_filters( 'wp99234_unauthorized_content_error_message', __( 'You must be logged in to access members only areas.', 'wp99234' ) ), 'error' );

					wp_redirect( $redirect );
					exit;

				}

			}

		}

	}

	/**
	 * Filter the content to hide the content from users who are not authorised.
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function handle_hidden_content( $content ){
		global $post;

		/* We may be in an initialisation stage, so let's just return what we have */
		if( !is_object($post) )
		  return $content;

		$hide_content = get_post_meta( $post->ID, 'wp99234_hide_content', true );

		if( $hide_content && $hide_content == 1 ){

			if( is_user_logged_in() ){

				$user_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true );

				if( ! $user_memberships || ! is_array( $user_memberships ) ){

					$content = $this->get_template( 'hidden_content_unauthorised.php' );

				}

			}

		}

		return $content;

	}

	/**
	 * Display the given meta fields to the screen, Useful for splitting the fields for display in different areas.
	 *
	 * @param $fields
	 */
	function display_meta_fields( $fields ){

		foreach( $fields as $key => $field ){

			if( $field['content'] && ! empty( $field['content'] ) ):

				?>

				<div class="wp99234_meta_item <?php esc_attr_e( $key ); ?>">

					<h4 class="wp99234_meta_title"><?php esc_html_e( $field['title'] ); ?></h4>

					<?php echo $field['content']; ?>

				</div>

			<?php

			endif;

		}

	}

}
