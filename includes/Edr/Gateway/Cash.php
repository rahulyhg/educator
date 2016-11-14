<?php

class Edr_Gateway_Cash extends Edr_Gateway_Base {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'cash';
		$this->title = __( 'Cash', 'edr' );

		// Setup options.
		$this->init_options( array(
			'description' => array(
				'type'      => 'textarea',
				'label'     => __( 'Instructions for a student', 'edr' ),
				'id'        => 'gateway-description',
				'rich_text' => true,
			),
		) );

		add_action( 'edr_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $object_id, $user_id = null, $payment_type = 'course', $atts = array() ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array( 'redirect' => home_url( '/' ) );
		}

		$payment = $this->create_payment( $object_id, $user_id, $payment_type, $atts );
		$redirect_args = array();

		if ( $payment->ID ) {
			$redirect_args['value'] = $payment->ID;
		}

		return array(
			'status'   => 'pending',
			'redirect' => $this->get_redirect_url( $redirect_args ),
			'payment'  => $payment,
		);
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		$payment_id = get_query_var( 'edr-payment' );

		if ( ! $payment_id ) {
			echo '<p>' . __( 'You\'ve paid for this course already.' ) . '</p>';
		} else {
			$description = $this->get_option( 'description' );

			if ( ! empty( $description ) ) {
				echo '<h2>' . __( 'Payment Instructions', 'edr' ) . '</h2>';
				echo '<div class="edr-gateway-description">' . wpautop( stripslashes( $description ) ) . '</div>';
			}
		}
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'description':
					$input[ $option_name ] = wp_kses_data( $value );
					break;
			}
		}

		return $input;
	}
}