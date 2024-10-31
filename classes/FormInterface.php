<?php
/**
 * Holds the default form interface.
 *
 * @package Required\OpenInbound
 */

namespace Required\OpenInbound;

/**
 * Default form interface.
 *
 * This should be implemented by the various form integrations.
 *
 * @since 1.0.0
 */
interface FormInterface {
	/**
	 * Sends the collected form data to OpenInbound.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param mixed $data Form data to process.
	 * @param string $form_title The form title.
	 */
	public function send_form_data( $data, $form_title );

	/**
	 * Displays a help section on the settings screen.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function show_help_content();

}
