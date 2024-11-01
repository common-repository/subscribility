<?php
/**
 * Troly Tab Template for showing product description
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/troly/awards.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer)
 * will need to copy the new files to your theme to maintain compatibility. We try to do this
 * as little as possible, but it does happen. When this occurs the version of the template file will
 * be bumped and the readme will list any important changes.
 *
 * @see     https://troly.kayako.com/article/147-woocommerce-templates
 * @author  Troly
 * @version 3.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
# Do NOT use global $product as we will pass you a $product var automatically
# You can only use 'title' and 'content' in this file
?>
<div class="wp99234_meta_item <?php esc_attr_e( str_replace( ' ', '_', strtolower( $product->title ) ) ); ?>">

		<h4 class="wp99234_meta_title"><?php esc_html_e( $product->title ); ?></h4>

		<?php echo $product->content; ?>

</div>