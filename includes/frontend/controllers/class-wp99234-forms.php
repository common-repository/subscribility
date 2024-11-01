<?php
/**
 * Wrapper class to handle the registration form
 */

class WP99234_Forms {

	var $submit_name = 'wp99234_form_submit';

	var $nonce_name = 'wp99234_form_nonce';

	var $nonce_action = 'wp99234_form_submit';

	var $template;

	var $errors = array();

    function __construct() {
        $this->setup_actions();
    }

	function setup_actions() {

		add_action('init', array($this, 'init'));

	}

	function init() {

		if (isset($_POST[$this->submit_name])) {

			if (!wp_verify_nonce($_POST[$this->nonce_name], $this->nonce_action)) {
				wp_die(__('Invalid Form. Please Refresh Page.', 'wp99234'));
			}

			$this->handle_submit();

		}

	}

	/**
	 * Handle the form submission.
	 *
	 * @return bool
	 */
	function handle_submit() {
		wp_die(__('Invalid Form Submission Handler', 'wp99234'));
	}

	/**
	 * Validate the given value against the given rules.
	 *
	 * @param $key
	 * @param $value
	 * @param $rules
	 *
	 * @return mixed
	 */
	public function validate_field($key, $value, $rules) {

		if (isset($this->errors[$key])) {
			return $value;
		}

		foreach ($rules as $method => $check) {

			if (is_array($check)) {
				$msg = $check['error_msg'];
				$contains = $check['check_val'];
				$expYearLength = $check['length'];
			} else {
				$msg = $check;
			}

			switch ($method) {

				case 'required':

					if (!$value || $value === '') {
						$this->errors[$key] = $msg;
					}

					break;

				case 'is_email':

					if ($value != '' && !is_email($value)) {
						$this->errors[$key] = $msg;
					}

					break;

				case 'is_numeric':

					if ($value != '' && !is_numeric($value)) {
						$this->errors[$key] = $msg;
					}

					break;
				case 'is_phone':
					if ($value != '' && !WC_Validation::is_phone($value)) {
						$this->errors[$key] = $msg;
					}
					break;

				case 'contains':
					if (strpos($value, $contains) === false) {
						$this->errors[$key] = $msg;
					}

					// Throw an error if expiry year is not 4 digit.
					if ( (int) $expYearLength !== strlen( $value ) ) {
						$this->errors[ $key ] = $msg;
					}

					break;
			}

		}

		return $value;

	}

	/**
	 * Get the form HTML.
	 *
	 * @return string
	 */
	public function get_form() {

		if (!empty($this->errors)) {
			foreach ($this->errors as $error) {
				wc_add_notice($error, 'error');
			}
		}

		return WP99234()->template->get_template($this->template);

	}

	function display_field($key, $field, $css_class = null) {
		$type = (isset($field['type']))?$field['type']:'text';
		$isRequired = isset( $field['attributes']['required'] ) ?? false;

		?>
		<p class="form-row <?php echo $key . ' ' . $css_class;?>">

		<?php
		if ($type != 'select') {
			$value = (isset($_POST[$key]))?$_POST[$key]:$field['default'];
		} else {
			$value = (isset($_POST[$key]))?$_POST[$key]:$field['default'];
			$options = $field['options'];
		}

		# We may have a place holder instead
		if(array_key_exists('label', $field)) {
		?>
			<label for="<?php echo $key;?>">
				<?php echo $field['label'];?>
				<?php echo $isRequired ? '<abbr class="required" title="required">*</abbr>' : ''; ?>
			</label>
		<?php
		}
		$attributes = '';
		if (isset($field['attributes'])) {
			foreach ($field['attributes'] as $_key => $val) {
				// Check if DOB was set to required and only add *required* attribute
				if ($key == 'subs_birthday' && $_key == 'required') {
					if (esc_attr($val) == 1) {
						$attributes .= sprintf(' %s="%s"', $_key, esc_attr($val));
					}
				} else {
					$attributes .= sprintf(' %s="%s"', $_key, esc_attr($val));
				}
			}
		}

		if(array_key_exists('placeholder', $field)){
			if (isset($field['attributes']['placeholder'])) {
				$attributes .= sprintf(' %s="%s"', 'placeholder', $field['placeholder'].' '.$field['attributes']['placeholder']);
			} else {
				$attributes .= ' placeholder="'.$field['placeholder'].'"';
			}
		}
		switch ($type):
		case 'textarea':
			echo "<textarea $attributes name=\"" .esc_attr($key)."\" class='wp99234_delivery_area'>".esc_textarea($value)."</textarea>";
			break;

		case 'password':
			echo "<input $attributes type=\"password\" name=\"" .esc_attr($key)."\" value=\"".esc_attr($value)."\" id='".$field['id']."' />";
			break;

		case 'select':
			echo "<select $attributes name=\"" . esc_attr($key) . "\" id='" . $field['id'] . "'>";
			foreach ($options as $option) {
				if ($option == $value) {
					echo "<option value='$option' selected='selected'>$option</option>";
				} else {
					echo "<option value='$option'>$option</option>";
				}
			}
			echo "</select>";
			break;

		case 'hidden':
			echo "<input $attributes type=\"hidden\" name=\"" .esc_attr($key)."\" value=\"".esc_attr($value)."\" />";
			break;

		case 'text':
		default:
			echo "<input $attributes type=\"text\" name=\"" .esc_attr($key)."\" value=\"".esc_attr($value)."\" />";
			break;

		endswitch;
		?>
		</label>

		</p>
		<?php

	}

		/**
	 * Flag to test if the given user is registered to a given membership already.
	 *
	 * @param $user_id
	 * @param $membership_id
	 *
	 * @return bool
	 */
		public function user_is_registered_for_membership($user_id, $membership_id) {

			$user_memberships = get_user_meta($user_id, 'current_memberships', true);

			if ($user_memberships && is_array($user_memberships)) {

				if (isset($user_memberships[$membership_id])) {
					return true;
				}

		}

		return false;

	}

}
