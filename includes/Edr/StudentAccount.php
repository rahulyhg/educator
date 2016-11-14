<?php

class Edr_StudentAccount {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'edr_register_form', array( __CLASS__, 'register_form' ), 10, 2 );
		add_filter( 'edr_register_form_validate', array( __CLASS__, 'register_form_validate' ), 10, 2 );
		add_filter( 'edr_register_user_data', array( __CLASS__, 'register_user_data' ), 10, 2 );
		add_action( 'edr_new_student', array( __CLASS__, 'new_student' ), 10, 2 );
		add_action( 'edr_update_student', array( __CLASS__, 'update_student' ), 10, 2 );
	}

	/**
	 * Determine fields that can have multiple error codes.
	 *
	 * @param array $error_codes
	 * @return array
	 */
	protected static function parse_register_errors( $error_codes ) {
		$has_error = array();

		foreach ( $error_codes as $error_code ) {
			switch ( $error_code ) {
				case 'account_info_empty':
					$has_error['account_username'] = true;
					$has_error['account_email'] = true;
					break;
				case 'invalid_username':
				case 'existing_user_login':
					$has_error['account_username'] = true;
					break;
				case 'invalid_email':
				case 'existing_user_email':
					$has_error['account_email'] = true;
					break;
			}
		}

		return $has_error;
	}

	/**
	 * Output default user register form.
	 *
	 * @param WP_Error $errors
	 * @param WP_Post $object
	 */
	public static function register_form( $errors, $object ) {
		$user = wp_get_current_user();

		$error_codes = is_wp_error( $errors ) ? $errors->get_error_codes() : array();

		// Determine fields that can have multiple errors.
		$has_error = self::parse_register_errors( $error_codes );

		// Setup form.
		$form = new Edr_Form();
		$form->default_decorators();

		if ( ! $user->ID ) {
			// Add account details group.
			$form->add_group( array(
				'name'  => 'account',
				'label' => __( 'Create an Account', 'edr' ),
			) );

			// Set values.
			$form->set_value( 'account_username', isset( $_POST['account_username'] ) ? $_POST['account_username'] : '' );
			$form->set_value( 'account_email', isset( $_POST['account_email'] ) ? $_POST['account_email'] : '' );

			// Username.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'account_username',
				'container_id' => 'account-username-field',
				'label'        => __( 'Username', 'edr' ),
				'id'           => 'account-username',
				'class'        => isset( $has_error['account_username'] ) ? 'error' : '',
				'required'     => true,
			), 'account' );

			// Email.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'account_email',
				'container_id' => 'account-email-field',
				'label'        => __( 'Email', 'edr' ),
				'id'           => 'account-email',
				'class'        => isset( $has_error['account_email'] ) ? 'error' : '',
				'required'     => true,
			), 'account' );
		}

		if ( edr_collect_billing_data( $object ) ) {
			// Add billing details group.
			$form->add_group( array(
				'name'  => 'billing',
				'label' => __( 'Billing Details', 'edr' ),
			) );

			// Set values.
			$values = Edr_Payments::get_instance()->get_billing_data( $user->ID );

			if ( empty( $values['country'] ) ) {
				$values['country'] = edr_get_location( 'country' );
			}

			if ( empty( $values['state'] ) ) {
				$values['state'] = edr_get_location( 'state' );
			}

			$values['first_name'] = ( $user->ID ) ? $user->first_name : '';
			$values['last_name'] = ( $user->ID ) ? $user->last_name : '';

			foreach ( $values as $key => $value ) {
				$post_key = 'billing_' . $key;

				if ( isset( $_POST[ $post_key ] ) ) {
					$form->set_value( $post_key, $_POST[ $post_key ] );
				} else {
					$form->set_value( $post_key, $value );
				}
			}

			// First Name.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_first_name',
				'container_id' => 'billing-first-name-field',
				'label'        => __( 'First Name', 'edr' ),
				'id'           => 'billing-first-name',
				'class'        => in_array( 'billing_first_name_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Last Name.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_last_name',
				'container_id' => 'billing-last-name-field',
				'label'        => __( 'Last Name', 'edr' ),
				'id'           => 'billing-last-name',
				'class'        => in_array( 'billing_last_name_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Address.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_address',
				'container_id' => 'billing-address-field',
				'label'        => __( 'Address', 'edr' ),
				'id'           => 'billing-address',
				'class'        => in_array( 'billing_address_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Address Line 2.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_address_2',
				'container_id' => 'billing-address-2-field',
				'label'        => __( 'Address Line 2', 'edr' ),
				'id'           => 'billing-address-2',
			), 'billing' );

			// City.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_city',
				'container_id' => 'billing-city-field',
				'label'        => __( 'City', 'edr' ),
				'id'           => 'billing-city',
				'class'        => in_array( 'billing_city_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			$edr_countries = Edr_Countries::get_instance();

			// State.
			$state_field = array(
				'name'         => 'billing_state',
				'container_id' => 'billing-state-field',
				'label'        => __( 'State / Province', 'edr' ),
				'id'           => 'billing-state',
				'class'        => in_array( 'billing_state_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			);

			$country = $form->get_value( 'billing_country' );
			$states = $country ? $edr_countries->get_states( $country ) : null;

			if ( $states ) {
				$state_field['type'] = 'select';
				$state_field['options'] = array_merge( array( '' => '&nbsp;' ), $states );
				unset( $states );
			} else {
				$state_field['type'] = 'text';
			}

			$form->add( $state_field, 'billing' );

			// Postcode.
			$form->add( array(
				'type'         => 'text',
				'name'         => 'billing_postcode',
				'container_id' => 'billing-postcode-field',
				'label'        => __( 'Postcode / Zip', 'edr' ),
				'id'           => 'billing-postcode',
				'class'        => in_array( 'billing_postcode_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
			), 'billing' );

			// Country.
			$form->add( array(
				'type'         => 'select',
				'name'         => 'billing_country',
				'container_id' => 'billing-country-field',
				'label'        => __( 'Country', 'edr' ),
				'id'           => 'billing-country',
				'class'        => in_array( 'billing_country_empty', $error_codes ) ? 'error' : '',
				'required'     => true,
				'options'      => array_merge( array( '' => '&nbsp;' ), $edr_countries->get_countries() ),
			), 'billing' );
		}

		$form->display();
	}

	/**
	 * Validate the default user registration form.
	 *
	 * @param WP_Error $errors
	 * @param WP_Post $object
	 * @return WP_Error
	 */
	public static function register_form_validate( $errors, $object ) {
		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			// Username.
			if ( ! empty( $_POST['account_username'] ) ) {
				if ( ! validate_username( $_POST['account_username'] ) ) {
					$errors->add( 'invalid_username', __( 'Please check if you entered your username correctly.', 'edr' ) );
				}
			} else {
				$errors->add( 'account_info_empty', __( 'Please enter your username and email.', 'edr' ) );
			}

			// Email.
			if ( ! empty( $_POST['account_email'] ) ) {
				if ( ! is_email( $_POST['account_email'] ) ) {
					$errors->add( 'invalid_email', __( 'Please check if you entered your email correctly.', 'edr' ) );
				}
			} elseif ( ! $errors->get_error_message( 'account_info_empty' ) ) {
				$errors->add( 'account_info_empty', __( 'Please enter your username and email.', 'edr' ) );
			}
		}

		if ( edr_collect_billing_data( $object ) ) {
			// First Name.
			if ( empty( $_POST['billing_first_name'] ) ) {
				$errors->add( 'billing_first_name_empty', __( 'Please enter your first name.', 'edr' ) );
			}

			// Last Name.
			if ( empty( $_POST['billing_last_name'] ) ) {
				$errors->add( 'billing_last_name_empty', __( 'Please enter your last name.', 'edr' ) );
			}

			// Address.
			if ( empty( $_POST['billing_address'] ) ) {
				$errors->add( 'billing_address_empty', __( 'Please enter your billing address.', 'edr' ) );
			}

			// Address Line 2.
			if ( empty( $_POST['billing_city'] ) ) {
				$errors->add( 'billing_city_empty', __( 'Please enter your billing city.', 'edr' ) );
			}

			// State / Province.
			if ( empty( $_POST['billing_state'] ) ) {
				$errors->add( 'billing_state_empty', __( 'Please enter your billing state / province.', 'edr' ) );
			}

			// Postcode / Zip.
			if ( empty( $_POST['billing_postcode'] ) ) {
				$errors->add( 'billing_postcode_empty', __( 'Please enter your billing postcode / zip.', 'edr' ) );
			}

			// Country.
			if ( empty( $_POST['billing_country'] ) ) {
				$errors->add( 'billing_country_empty', __( 'Please select your billing country.', 'edr' ) );
			}
		}

		return $errors;
	}

	/**
	 * Filter the default user registration data.
	 *
	 * @param array $data
	 * @param WP_Post $object
	 * @return array
	 */
	public static function register_user_data( $data, $object ) {
		$data['user_login'] = $_POST['account_username'];
		$data['user_email'] = $_POST['account_email'];
		$data['user_pass'] = wp_generate_password( 12, false );

		// Billing details.
		if ( edr_collect_billing_data( $object ) ) {
			$data['first_name'] = $_POST['billing_first_name'];
			$data['last_name'] = $_POST['billing_last_name'];
		}

		return $data;
	}

	/**
	 * Save billing data.
	 *
	 * @param int $user_id
	 */
	public static function save_billing_data( $user_id ) {
		update_user_meta( $user_id, '_edr_billing', array(
			'address'   => sanitize_text_field( $_POST['billing_address'] ),
			'address_2' => sanitize_text_field( $_POST['billing_address_2'] ),
			'city'      => sanitize_text_field( $_POST['billing_city'] ),
			'state'     => sanitize_text_field( $_POST['billing_state'] ),
			'postcode'  => sanitize_text_field( $_POST['billing_postcode'] ),
			'country'   => sanitize_text_field( $_POST['billing_country'] ),
		) );
	}

	/**
	 * Fires when a student is created through the payment page.
	 *
	 * @param int $user_id
	 * @param WP_Post $object
	 */
	public static function new_student( $user_id, $object ) {
		if ( edr_collect_billing_data( $object ) ) {
			self::save_billing_data( $user_id );
		}
	}

	/**
	 * Fires when a student is updated through the payment page.
	 * For example, being logged in, a user purchases a new course or a membership.
	 *
	 * @param int $user_id
	 * @param WP_Post $object
	 */
	public static function update_student( $user_id, $object ) {
		$data = array();

		if ( edr_collect_billing_data( $object ) ) {
			$data['first_name'] = $_POST['billing_first_name'];
			$data['last_name'] = $_POST['billing_last_name'];

			// Update billing data.
			self::save_billing_data( $user_id );
		}

		if ( ! empty( $data ) ) {
			$data['ID'] = $user_id;
			wp_update_user( $data );
		}
	}

	/**
	 * Get payment info table.
	 *
	 * @param WP_Post $object
	 * @param array $args
	 * @return string
	 */
	public static function payment_info( $object, $args = array() ) {
		// Get price.
		if ( ! isset( $args['price'] ) ) {
			if ( EDR_PT_COURSE == $object->post_type ) {
				$args['price'] = Edr_Courses::get_instance()->get_course_price( $object->ID );
			} elseif ( EDR_PT_MEMBERSHIP == $object->post_type ) {
				$args['price'] = Edr_Memberships::get_instance()->get_price( $object->ID );
			}
		}

		// Get tax data.
		$tax_enabled = edr_get_option( 'taxes', 'enable' );

		if ( $tax_enabled ) {
			$tax_manager = Edr_TaxManager::get_instance();
			$tax_data = $tax_manager->calculate_tax( $tax_manager->get_tax_class_for( $object->ID ), $args['price'], $args['country'], $args['state'] );
		} else {
			$tax_data = array(
				'taxes'    => array(),
				'subtotal' => $args['price'],
				'tax'      => 0.0,
				'total'    => $args['price'],
			);
		}

		// Items list.
		$output = '<table class="edr-payment-table">';
		$output .= '<thead><tr><th>' . __( 'Item', 'edr' ) . '</th><th>' . __( 'Price', 'edr' ) . '</th></tr></thead>';

		if ( EDR_PT_COURSE == $object->post_type ) {
			$output .= '<tbody><tr>';
			$output .= '<td><a href="' . esc_url( get_permalink( $object->ID ) ) . '" target="_blank">' . esc_html( $object->post_title ) . '</a></td>';
			$output .= '<td>' . edr_format_price( $tax_data['subtotal'], false ) . '</td>';
			$output .= '</tr></tbody>';
		} elseif ( EDR_PT_MEMBERSHIP == $object->post_type ) {
			$ms = Edr_Memberships::get_instance();
			$duration = $ms->get_duration( $object->ID );
			$period = $ms->get_period( $object->ID );

			$output .= '<tbody><tr>';
			$output .= '<td>' . esc_html( $object->post_title ) . '</td>';
			$output .= '<td>' . edr_format_membership_price( $tax_data['subtotal'], $duration, $period ) . '</td>';
			$output .= '</tr></tbody>';
		}

		$output .= '</table>';

		// Summary.
		$output .= '<dl class="edr-payment-summary edr-dl">';

		if ( $tax_data['tax'] > 0.0 ) {
			$output .= '<dt class="payment-subtotal">' . __( 'Subtotal', 'edr' ) . '</dt><dd>' . edr_format_price( $tax_data['subtotal'], false ) . '</dd>';

			foreach ( $tax_data['taxes'] as $tax ) {
				$output .= '<dt class="payment-tax">' . esc_html( $tax->name ) . '</dt><dd>' . edr_format_price( $tax->amount, false ) . '</dd>';
			}
		}

		$output .= '<dt class="payment-total">' . __( 'Total', 'edr' ) . '</dt><dd>' . edr_format_price( $tax_data['total'], false ) . '</dd>';
		$output .= '</dl>';

		return $output;
	}
}
