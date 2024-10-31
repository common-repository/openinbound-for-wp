<?php
/**
 * Holds the abstract form class.
 *
 * @package Required\OpenInbound
 */

namespace Required\OpenInbound;

use OpenInbound\OI;

/**
 * Default base form class.
 *
 * @since 1.0.0
 */
abstract class FormAbstract implements FormInterface {
	/**
	 * @inheritdoc
	 */
	public function send_form_data( $data, $form_title ) {
		$tracking_id = get_option( 'openinbound_tracking_id', '' );
		$api_key     = get_option( 'openinbound_api_key', '' );

		if ( empty( $tracking_id ) || empty( $api_key ) ) {
			return;
		}

		$oi = new OI( $tracking_id, $api_key );

		$oi_contact_id = filter_input(INPUT_COOKIE, '_oi_contact_id', FILTER_VALIDATE_INT);
		$oi->updateContact( $oi_contact_id, $data );

		$properties = [
			'title'      => sprintf( 'Form submission by %1$s - %2$s', $data['email'], $form_title ),
			'event_type' => 'submission',
			'raw'        => json_encode( $data ),
		];

		$oi->addEvent( $oi_contact_id, $properties );
	}

	/**
	 * @inheritdoc
	 */
	public function show_help_content() {
		// TODO: Implement show_help_content() method.
	}
}
