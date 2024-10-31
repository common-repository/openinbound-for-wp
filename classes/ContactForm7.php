<?php
/**
 * Holds the Contact Form 7 integration class.
 *
 * @package Required\OpenInbound
 */

namespace Required\OpenInbound;

/**
 * Contact Form 7 integration.
 *
 * Integrates OpenInbound with the popular forms plugin.
 */
class ContactForm7 extends FormAbstract {
	/**
	 * Returns the list of supported field names for Contact Form 7 tracking.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Allowed field names.
	 */
	public function get_list_of_field_names() {
		$field_names = [
			'email'        => 'email',
			'e-mail'       => 'email',
			'name'         => 'name',
			'first_name'   => 'first_name',
			'last_name'    => 'last_name',
			'phone'        => 'phone',
			'company'      => 'company_name',
			'company_name' => 'company_name',
			'company_department' => 'company_department',
			'company_industry' => 'company_industry',
		];

		/**
		 * Filters the list of allowed fields for OpenInbound tracking in Contact Form 7.
		 *
		 * @param array $field_names The allowed field names.
		 */
		return apply_filters( 'openinbound_cf7_field_names', $field_names );
	}
	/**
	 * @inheritdoc
	 */
	public function show_help_content() {
		if ( ! defined( 'WPCF7_PLUGIN' ) ) {
			return;
		}

		/**
		 * Register a new section on the "openinbound" page.
		 */
		add_settings_section(
			'openinbound_contact_form_7',
			__( 'Contact Form 7', 'openinbound' ),
			[ $this, 'print_help_text' ],
			'openinbound'
		);
	}

	/**
	 * Prints the actual explanation for Contact Form 7 integration.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function print_help_text() {
		$field_names = '<br><code>' . implode( '</code>,<code>', array_keys( $this->get_list_of_field_names() ) ) . '</code>';
		?>
		<p><?php printf( __( 'In order to properly track your Contact Form 7 contact forms, please use any of the following name attributes when creating form fields: %s', 'openinbound' ), $field_names ); ?></p>
		<?php
	}
}
