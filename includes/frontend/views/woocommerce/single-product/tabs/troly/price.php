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
$show_6_pack = isset($product->content['6-pack']);
$show_12_pack = isset($product->content['12-pack']);
$show_cheapest_member_pricing = isset($product->content['Cheapest Member Price']);
$show_all_member_pricing = isset($product->content['Member Prices']);

# Change this to edit what is shown after special pricing
$unit = ($product->content['is_composite'] ? 'pack' : 'bottle');

if(!($show_6_pack || $show_12_pack || $show_cheapest_member_pricing || $show_all_member_pricing)){
	return;
}
?>
<div class="wp99234_meta_item <?php esc_attr_e( str_replace( ' ', '_', strtolower( $product->title ) ) ); ?>">

		<?php if($show_6_pack || $show_12_pack || $show_cheapest_member_pricing) {
			echo '<h4 class="wp99234_meta_title">Prices</h4>';
		} ?>


		<!-- Enable this to show your single-unit pricing -->
		<!--
			<p class="price" id="wp99234_product_title">
				<strong>Single Unit: </strong><?php echo $product->content['Single']; ?>
			</p>
		-->


		<!-- Our Six Pack pricing -->
		<?php if($show_6_pack){ ?>
			<p class="price" id="wp99234_product_six_pack">
				<?php echo "<strong>".get_option('wp99234_product_display_pack_6_title').": </strong>{$product->content['6-pack']} / {$unit}" ?>
			</p>
		<?php } ?>


			<!-- 12 Pack pricing -->
		<?php if($show_12_pack){ ?>
			<p class="price" id="wp99234_product_twelve_pack">
				<?php echo "<strong>".get_option('wp99234_product_display_pack_12_title').": </strong>{$product->content['12-pack']} / {$unit}" ?>
			</p>
		<?php } ?>

		<!--
			Show the cheapest available member-only price
		-->
	<?php if($show_cheapest_member_pricing){ ?>
		<p class='price' id='wp99234_product_cheapest_member_prices'>
			<?php echo "<strong>".get_option('wp99234_product_display_pack_cheapest_title').": </strong>{$product->content['Cheapest Member Price']} / {$unit}" ?>
		</p>
	<?php } ?>

		<!--
			Show all available member prices
		-->
		<?php
			if($show_all_member_pricing) {
				echo '<h4 class="wp99234_product_member_prices_title">Member Prices</h4>';
				echo "<p class='price wp99234_available_member_price_title' id='wp99234_available_price_title'>All prices shown below are <strong>per {$unit}</strong>.</p>";
				foreach($product->content['Member Prices'] as $club => $price) {
					echo "
						<p class='price wp99234_available_member_price' id='wp99234_product_member_".str_replace(' ', '_', strtolower($club))."'>
							<strong>{$club}: </strong> {$price}
						</p>
					";
				}
			}
		?>

		<!-- Do not remove. This assists search engines when indexing your site -->
        <?php if (isset($product->content['meta'])): // Fix notice ?>
		<meta itemprop="price" content="<?php echo $product->content['meta']['Single'] ?>" />
        <?php endif; ?>
		<meta itemprop="priceCurrency" content="<?php echo esc_attr( get_woocommerce_currency() ); ?>" />
		<link itemprop="availability" href="http://schema.org/<?php echo (isset($product->content['meta']) && $product->content['meta']['inStock']) ? 'InStock' : 'OutOfStock'; ?>">

</div>