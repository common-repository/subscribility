<?php
$giftOrderSelected = WC()->session->get( 'troly_gift_order_wrap' ) ? 'checked' : null;
$giftOrderMessage = WC()->session->get( 'troly_gift_order_msg' ) ?? null;
$giftWrapPrice = get_option( 'troly_gift_wrap_price' );
?>
<tr id="troly-gift-order" class="cart_item cart-subtotal">
	<td class="product-name"><label for="troly_gift_order"><?php _e( 'Make this a gift order', 'troly' ); ?></label></td>
	<td class="product-total">
		<input type="checkbox" name="troly_gift_order" value="1" id="troly_gift_order" <?php echo ! is_null( $giftOrderMessage ) ? 'checked' : null; ?>>
	</td>
</tr>

<tr id="wp99234-gift-order-message" class="cart_item">
	<td colspan="2">
		<div>
			<textarea name="troly_gift_order_message" id="troly_gift_order_message" placeholder="Type your message here"><?php echo $giftOrderMessage; ?></textarea>
		</div>
	</td>
</tr>

<?php if ( $giftWrapPrice && $giftWrapPrice > 0 ) { ?>
	<tr id="troly-gift-order-wrapping" class="cart_item">
		<td class="product-name"><label for="troly_gift_order_wrap"><?php _e( 'Gift wrap the order?', 'troly' ); ?></label></td>
		<td class="product-total">
			<input type="checkbox" name="troly_gift_order_wrap" id="troly_gift_order_wrap" value="1" <?php echo $giftOrderSelected; ?>>
			<span class="woocommerce-Price-amount amount">
				<?php echo get_woocommerce_currency_symbol() . $giftWrapPrice; ?>
			</span>
		</td>
	</tr>
<?php } ?>