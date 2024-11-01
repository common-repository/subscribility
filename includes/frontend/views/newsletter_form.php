<?php
/**
 * Newsletter Registration Form.
 * This creates a customer in subs with the "received newsletter" flag turned on.
 */

?>

<style>
    #newsletter_registration_form label{
        display:block;
        width:75%;
    }

    #newsletter_registration_form input[type=text]{
        width:100%;
    }

    #newsletter_registration_form .form-submit{
        margin-top:40px;
    }
</style>

<div class="woocommerce">
	<form id="newsletter_registration_form" action="#newsletter_registration_form" method="POST">

		<div class="woocommerce">
			<?php if ( !is_admin() ) { if ( function_exists('wc_print_notices') ) { wc_print_notices(); } } ?>
		</div>

		<div class="cfix">

			<?php $fields = array(
				'first_name' => array(
					(get_option('wp99234_newsletter_use_placeholders') == 'yes' ? 'placeholder' : 'label') => __( 'First Name', 'wp99234' ),
					'default' => '',
					'attributes' => [
						'class' => 'input-text',
					],
				),
				'reg_email' => array(
					(get_option('wp99234_newsletter_use_placeholders') == 'yes' ? 'placeholder' : 'label') => __( 'Email', 'wp99234' ),
					'default' => '',
					'attributes' => [
						'class' => 'input-text',
					],
				),
			);

			if ( get_option('wp99234_newsletter_collect_mobile') == 'yes' ) {
			$fields['mobile'] = array(
				( get_option( 'wp99234_newsletter_use_placeholders' ) == 'yes' ? 'placeholder' : 'label' ) => __( 'Mobile', 'wp99234' ),
				'default' => '',
				'attributes' => [
					'class' => 'input-text',
				],
			);
			}

			// Only show Postcode if not 'hidden'
			if ( get_option('wp99234_newsletter_collect_postcode') != 'hidden' ) {
			$fields['postcode'] = array(
				( get_option( 'wp99234_newsletter_use_placeholders' ) == 'yes' ? 'placeholder' : 'label' ) => __( 'Postcode', 'wp99234' ),
				'default' => '',
			);
			}

			?>

			<?php foreach( $fields as $key => $field ){
				WP99234()->_newsletter->display_field( $key, $field );
			} ?>

			<p class="form-submit form-row">
				<input type="hidden" name="<?php echo WP99234()->_newsletter->nonce_name; ?>" value="<?php echo wp_create_nonce( WP99234()->_newsletter->nonce_action ); ?>" />
				<input type="submit" name="<?php echo WP99234()->_newsletter->submit_name; ?>" value="<?php _e( 'Sign Up Now', 'wp99234' ); ?>" class="wp99234-newsletter-signup-button" />
			</p>

		</div>

	</form>
</div>