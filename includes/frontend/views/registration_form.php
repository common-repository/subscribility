<?php
/**
 * Registration form for users to register as a club member.
 * Template Name: Membership Registration
 *
 * This template can be overridden by copying it to wp-content/themes/yourtheme/wp99234/registration_form.php.`
 *
 */

$break = 1; // <--- Why?

$membership_options = get_option( 'wp99234_company_membership_types' );

$current_user = wp_get_current_user();
$current_membership_id = @array_keys(get_user_meta($current_user->ID, 'current_memberships', true))[0];
if($current_membership_id === null)
  $current_membership_id = @$_POST['selected_membership'];
if($current_membership_id === null)
  $current_membership_id = 'null';

// Enqueue credit card validator logics.
wp_enqueue_script( 'jquery-payment-troly' );

echo "\n\n".'<script type="text/javascript">window.troly_current_membership_id = '.$current_membership_id.';</script>'."\n\n";

// Enqueue WooCommerce styling and functions for dropdown elements.
wp_enqueue_style( 'select2' );
wp_enqueue_script( 'selectWoo' );
get_header();
?>
<style type="text/css">
	.membership_variation.hide { display:none; }

	/* clearfix hack class */
	.wp99234-cfix:after { content: ""; display: table; clear: both; }

	.wp99234-membership_option p {
		padding: 10px 5px;
	}

	.wp99234-section.chosen-preferences .tag, .wp99234-section.chosen-preferences .tag-editable {
		position: relative;
		display: inline-block;
	}

	.wp99234-section.chosen-preferences .tag-selected button:before,
	.wp99234-membership_option.selected button:before {
		content:'\2713\00a0\00a0'; /* Add check icon to selected tag */
		white-space: pre;
	}

	.wp99234-membership_option:nth-child(2n+1){ clear:both; }

	.wp99234-selected_membership,
	.wp99234-restricted_membership {
		/*margin-top: 1.313em;*/
	}

	.wp99234-section.cc_details {
		padding-top: 15px;
	}

	#wp99234-create_password_section {
		padding-top: 15px;
	}

	.wp99234_delivery_area {
	  display: block;
	  width: 100%;
	  min-height: 150px;
	}

	/* User details, CC details and delivery sections */
	.wp99234-section.user_details,
	.wp99234-section.cc_details,
	.wp99234-section.delivery_details,
	.wp99234-input_field_textfield {
		width: 100%;
		box-sizing: border-box;
	}
	@media screen and (min-width: 700px) {

		.wp99234-section.user_details,
		.wp99234-section.cc_details {
			float: left;
			width: 50%;
			padding-right: 20px;
		}
		.wp99234-section.delivery_details {
			float: right;
			width: 50%;
			padding-left: 20px;
		}
	}

	@media screen and (max-width: 700px) {
		.wp99234-section.delivery_details {
			padding-top: 15px;
		}
	}

	#wp99234_use_existing_card{
		width:auto;
	}

   #wp99234_membership_options .restricted {
	  font-style: italic;
	  margin-top: 1.313em;
   }

	/* membership variation dropdown */
	.membership_variation {
		height: 43px;
		border: 1px solid #CCC;
	}

	#wp99234-registration_shipping_instructions {
		width: 100%;
	}

</style>

<div class="woocommerce">
	<form id="wp99234_member_registration_form" action="" method="POST">

		<div class="woocommerce wp99234_registration_notices">
			<?php if ( !is_admin() ) { if ( function_exists('wc_print_notices') ) { wc_print_notices(); } } ?>
		</div>

		<div class="wp99234-cfix">

			<ul id="wp99234_membership_options" class="wp99234-cfix">

				<?php
				$idx = 0; foreach ( apply_filters( 'wp99234_rego_form_membership_options', $membership_options ) as $membership_option ): ?>

					<?php
						/* Any membership not available to the broader public is not rendered.
						Public: anyone can sign up
						Restricted: invite-only
						*/
						if( isset( $membership_option->visibility ) && !in_array( $membership_option->visibility, [ 'public', 'restricted' ] ) ) continue;
					?>

						<li class="wp99234-membership_option
							<?php echo $current_membership_id == $membership_option->id ? 'selected' : 'inactive'; ?>"
							id="wp99234-club_<?php echo str_replace( ' ', '_', strtolower( $membership_option->name ) ); ?>">

						<h5 class="wp99234-membership_option_title"><?php echo esc_html( $membership_option->name ); ?></h5>

						<div class="wp99234-membership_option_details">

							<?php if ( !empty( $membership_option->description )) { ?>
							<p class="wp99234-membership-description"><?php echo $membership_option->description; ?></p>
							<?php } ?>

							<?php if ( !empty( $membership_option->benefits )) { ?>
							<p><a href="#" class="subs-toggle-member-benefits wp99234-membership_benefits_show">Additional benefits</a></p>
							<div class="wp99234-membership_benefits">
							<h6 class="wp99234-membership_option_member_benefits_title">Member Benefits</h6>
							<ul><li><?php echo implode("</li><li>",explode("\n",$membership_option->benefits)); ?></li></ul>
							</div>
						<?php } ?>
						</div>

						<?php
						if($current_membership_id == $membership_option->id) {
							echo '<div class="restricted wp99234_current_membership">You are currently a member of this club.</div>';
						}
						if( isset( $membership_option->visibility) && $membership_option->visibility == 'public') {  ?>

							<input type="radio" class="selected_membership_radio" name="selected_membership" value="<?php echo $membership_option->id; ?>" <?php if($current_membership_id == $membership_option->id){echo 'checked="checked"';}?> />

							<button class="wp99234-selected_membership button" data-original_text="<?php _e( 'Select', 'wp99234' ); ?>" data-selected_text="<?php _e( 'Selected', 'wp99234' ); ?>" data-membership_option_id="<?php echo $membership_option->id; ?>" >
							<?php if($current_membership_id == $membership_option->id){ _e( 'Selected', 'wp99234' );}else{ _e( 'Select', 'wp99234' );}?>
							</button>

							<!-- enable membership variations for variation membership only -->
							<?php if( isset($membership_option->is_variation_membership) && $membership_option->is_variation_membership ): ?>
								<!-- only selected variation will be set name to 'variation_id'. It will be passed to Troly for creating membership during submission -->
								<select id="membership_option_<?php echo $membership_option->id ?>" class="membership_variation hide" name="">
									<option value='' disabled>Membership option</option>
									<?php
										foreach($membership_option->variations as $v_id => $v_name) {
											echo "<option value=".$v_id.">".$v_name."</option>";
										}
									?>
								</select>
							<?php endif; ?>

						<?php } else if ( isset( $membership_option->visibility ) && $membership_option->visibility == 'restricted' && $current_membership_id != $membership_option->id) { ?>

							<div class="restricted">You cannot currently sign up for this membership. Please contact us for further information.</div>

						<?php } ?>

					</li>

				<?php endforeach; ?>

			</ul>

			<h3 class="wp99234-section_title">Membership Details</h3>

			<?php
				$customer_tags = get_option('troly_customer_tags');
				$tag_ids_str = get_user_meta( $current_user->ID , 'tag_ids', true);
				$tag_ids = explode(',', $tag_ids_str);
				foreach($customer_tags as $id=>$tag) {
					if (!$tag->is_public) {
						unset($customer_tags[$id]);
					}
				}
				if (!empty($customer_tags)):
			?>
			<div class="wp99234-section chosen-preferences">
				<h4>Tell us about you</h4>
				<ul class="tags">
					<?php foreach($customer_tags as $tag): ?>
					<li class="tag <?= in_array($tag->id, $tag_ids) ? 'tag-selected' : ''; ?>" customer-tag="<?= $tag->id; ?>">
						<button class="button"><?= $tag->name; ?></button>
					</li>
					<?php endforeach; ?>
				</ul>
				<input type="hidden" name="tag_ids" id="tag_ids" value="<?= $tag_ids_str; ?>">
			</div>
			<?php endif; ?>

			<div class="wp99234-section user_details">

				<h4 class="wp99234-section_title"><?php _e( 'Your Details', 'wp99234' ); ?></h4>

				<div class="wp99234-section_content">

					<?php
						$combinedLayout = false;
						$formStyle = get_option( 'troly_forms_layout', 'placeholder' );

						if ( $formStyle === 'both' ) {
							$formStyle = 'placeholder';
							$combinedLayout = true;
						}

					$user_fields = array(
						'first_name' => array(
						$formStyle => __( 'First Name', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID, 'first_name', true ),
						'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_first_name')
						),
						'last_name' => array(
						$formStyle => __( 'Last Name', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID , 'last_name', true),
						'attributes' => array('class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_last_name' ),
						),
						'reg_email' => array(
						$formStyle => __( 'Email', 'wp99234' ),
						'default' => ( $current_user ) ? $current_user->user_email : '' ,
						'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_email')
						),
						'phone' => array(
						$formStyle => __( 'Phone Number', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID , 'phone', true),
						'attributes' => array('class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_phone' ),
						),
						'mobile' => array(
						$formStyle => __( 'Mobile Number', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID , 'mobile', true),
						'attributes' => array('class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_mobile' ),
						),
					);

					$requireDOB = get_option( 'troly_require_dob' );
					$isRequiredDOB = $requireDOB === 'membership' || $requireDOB === 'both' ? true : false;

					if ( $isRequiredDOB ) {
						$user_fields['subs_birthday'] = [
							$formStyle => __( 'Date of Birth', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'birthday', true ),
							'attributes' => [
								'class' => 'wp99234-input_field_text input-text',
								'required' => true,
								'id' => 'subs_birthday',
							],
						];
					}

					foreach( $user_fields as $key => $user_field ){
						if ( $combinedLayout && isset( $user_field['placeholder'] ) ) {
							$user_field['label'] = $user_field['placeholder'];
						}
						WP99234()->_registration->display_field( $key, $user_field );
					}
					?>

				</div>

			</div>

			<div class="wp99234-section delivery_details">

				<h4 class="wp99234-section_title"><?php _e( 'Delivery Details', 'troly' ); ?></h4>

				<div class="wp99234-section_content">

					<?php
					$countries_obj   = new WC_Countries();
					$countriesArray  = $countries_obj->__get( 'countries' );

					$delivery_fields = array(
						'company_name' => array(
							$formStyle => __( 'Company Name (optional)', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'shipping_company', true),
							'attributes' => array('class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_company_name' ),
						),
						'shipping_address_1' => array(
							$formStyle => __( 'Delivery Address', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'shipping_address_1', true ),
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_shipping_address_1' )
						),
						'shipping_suburb' => array(
							$formStyle => __( 'Suburb', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'shipping_city', true ),
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_shipping_suburb')
						),
						'shipping_postcode' => array(
							$formStyle => __( 'Postcode', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'shipping_postcode', true ),
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text', 'id' => 'wp99234-registration_shipping_postcode')
						),
						'shipping_country' => array(
							$formStyle => __( 'Country', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'shipping_country', true ),
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text'),
							'id' => 'troly_shipping_country',
							'type' => 'select',
							'options' => $countriesArray,
						),
						'shipping_state' => array(
							$formStyle => __( 'State', 'troly' ),
							'default' => get_user_meta( $current_user->ID , 'shipping_state', true ),
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text input-text', 'id' => 'troly_shipping_state'),
						),
						'shipping_instructions' => array(
							'type'  => 'textarea',
							$formStyle => __( 'Delivery notes and instructions (optional)', 'troly' ),
							'default' => get_user_meta( $current_user->ID, 'delivery_instructions', true),
							'attributes' => array('class' => 'wp99234-input_field_textfield input-text', 'id' => 'wp99234-registration_shipping_instructions')
						)
					);

					foreach( $delivery_fields as $key => $delivery_field ){
						if ( $combinedLayout && isset( $delivery_field['placeholder'] ) ) {
							$delivery_field['label'] = $delivery_field['placeholder'];
						}
						WP99234()->_registration->display_field( $key, $delivery_field );
					}

					if(!is_user_logged_in()){
						echo '<div id="wp99234-create_password_section">';
						?>
						<h4 class="wp99234-section_title"><?php _e( 'Create Password', 'wp99234' ); ?></h4>
						<?php
						$pass_fields = array(
						'user_pass' => array(
							$formStyle => __( 'Password', 'wp99234' ),
							'type' => 'password' ,
							'id' => 'password' ,
							'default' => '',
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_text')
						),
						'conf_pass' => array(
							$formStyle => __( 'Confirm Password', 'wp99234' ),
							'type' => 'password' ,
							'id' => 'confirm_password' ,
							'default' => '',
							'attributes' => array('required' => true, 'class' => 'wp99234-input_field_password')
						)
						);
						foreach( $pass_fields as $key => $pass_field ){
							if ( $combinedLayout && isset( $pass_field['placeholder'] ) ) {
								$pass_field['label'] = $pass_field['placeholder'];
							}
						WP99234()->_registration->display_field( $key, $pass_field );
						}
						echo '</div>';
					}
					?>
				</div>

			</div>

			<div class="wp99234-section cc_details">

				<h4 class="wp99234-section_title"><?php _e( 'Payment Details', 'wp99234' ); ?></h4>

				<div class="wp99234-section_content">

					<?php

					$cc_fields = array(
						'cc_name' => array(
							$formStyle => __( 'Cardholder Name', 'wp99234' ),
							'default' => '',
							'attributes' => array(
								'required' => true,
								'autocomplete' => 'cc-name',
								'id' => 'troly-field-cc-name',
							)
						),
						'cc_number' => array(
							$formStyle => __( 'Card Number', 'wp99234' ),
							'default' =>'',
							'attributes' => array(
								'required' => true,
								'class' => 'woocommerce-Input input-text',
								'placeholder' => "•••• •••• •••• ••••",
								'maxlength' => 19,
								'type'=> 'tel',
								'inputmode'=> 'numeric',
								'autocomplete' => 'cc-number',
								'id' => 'troly-field-cc-number'
							),
						),
						'cc_expiry' => array(
							$formStyle => __( 'Card Expiry Date', 'wp99234' ),
							'default' => '' ,
							'attributes' => array(
								'placeholder' => 'MM / YYYY',
								'required' => true,
								'autocomplete' => 'cc-exp',
								'id' => 'troly-field-cc-exp'
							),
						),
						'cc_cvv' => array(
							$formStyle => __( 'Card code', 'wp99234' ),
							'default' => '' ,
							'attributes' => array(
								'placeholder' => 'CVC',
								'required' => true,
								'autocomplete' => 'cc-cvv',
								'id' => 'troly-field-cc-cvv'
							),
						)
					);

					$has_cc_details = false;

					if( is_user_logged_in() ){
						$has_cc_meta = get_user_meta( get_current_user_id(), 'has_subs_cc_data', true );
						if( $has_cc_meta && $has_cc_meta == true ){
							$has_cc_details = true;
						}
					}

					if( $has_cc_details ){
						echo '<div><input type="checkbox" id="wp99234_use_existing_card" name="wp99234_use_existing_card" checked="checked" value="yes" style="width:auto;display:inline-block;"/> ' . sprintf( '<label for="wp99234_use_existing_card" style="width:auto;display:inline-block;vertical-align:middle;margin-bottom:0;margin-left:5px;">Use existing card (%s)</label></div>', get_user_meta( get_current_user_id(), 'cc_number', true ) );
						echo '<div id="hidden_cc_form"><br /><p>' . __( 'Card details entered here will be stored securely for future use.', 'wp99234' ) . '</p>';
					}

					foreach( $cc_fields as $key => $cc_field ){
						$css_class = null;
						if ($key === 'cc_expiry') $css_class = 'form-row-first woocommerce-validated';
						if ($key === 'cc_cvv') $css_class = 'form-row-last woocommerce-validated';

						if ( $combinedLayout && isset( $cc_field['placeholder'] ) ) {
							$cc_field['label'] = $cc_field['placeholder'];
						}

						WP99234()->_registration->display_field( $key, $cc_field, $css_class );
					}

					if( $has_cc_details ){
						echo '</div>';
					}

					?>

				</div>

			</div>

			<?php
			do_action('wp99234_preferences_form');
			?>

		</div>

		<p class="form-submit form-row">
		<label id='message'></label>
		<input type="hidden" name="<?php echo WP99234()->_registration->nonce_name; ?>" value="<?php echo wp_create_nonce( WP99234()->_registration->nonce_action ); ?>" />
		<input class="button" type="submit" name="<?php echo WP99234()->_registration->submit_name; ?>" value="<?php _e( 'Sign Up Now', 'wp99234' ); ?>" id="wp99234_member_submit" />
		</p>

	</form>
</div>
<?php
$limit = (int)(get_option('wp99234_legal_drinking_age'));
$year = date('Y');
?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$("#subs_birthday").datepicker({
			maxDate: '-<?php echo $limit; ?>',
			changeYear: true,
			changeMonth: true,
			minDate: '-105y',
			yearRange: '<?php echo ($year - 105) . ':' . ($year - $limit); ?>',
			defaultDate: '<?php echo date('F') . ' ' . date('d') . ', ' . ($year - $limit); ?>',
		});

		$('#wp99234_member_registration_form .tags .tag').click(function(e) {
			e.preventDefault();
			if ($(this).hasClass('tag-selected')) {
				$(this).removeClass('tag-selected');
			} else {
				$(this).addClass('tag-selected');
			}
			var tag_ids = [];
			$('#wp99234_member_registration_form .tag').each(function(i, row) {
				if ($(row).hasClass('tag-selected')) {
					tag_ids.push($(row).attr('customer-tag'));
				}
			});
			$('#tag_ids').val(tag_ids);
		});

		jQuery('input[name=cc_expiry]').payment('formatCardExpiry');
		jQuery('input[name=cc_number]').payment('formatCardNumber');

		$('input[name=cc_number]').on( 'blur', function(){
			if( ! jQuery.payment.validateCardNumber( $( this ).val() ) ){
				$( this ).addClass( 'invalid' )
			} else {
				$( this ).removeClass( 'invalid' )
			}
		});

		$( '#troly_shipping_country' ).selectWoo({
			width: '100%',
			height: '350px'
		})
	});
</script>
