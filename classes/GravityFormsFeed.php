<?php
/**
 * Holds the Gravity Forms feed add-on.
 *
 * @package Required\OpenInbound
 */

namespace Required\OpenInbound;

use GFFeedAddOn;
use OI;

/**
 * Gravity Forms feed add-on.
 *
 * Integrates OpenInbound with the popular forms plugin.
 */
class GravityFormsFeed extends GFFeedAddOn {
	/**
	 * Do not allow multiple feeds.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $_multiple_feeds = false;

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string Version number of the add-on.
	 */
	protected $_version = '1.0.0';

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string Gravity Forms minimum version requirement.
	 */
	protected $_min_gravityforms_version = '1.9.14';

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string URL-friendly identifier.
	 */
	protected $_slug = 'openinbound';

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string Full path to the plugin.
	 */
	protected $_full_path = __FILE__;

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string URL to the add-on website.
	 */
	protected $_url = 'https://openinbound.com';

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var array Members plugin integration. List of capabilities to add to roles.
	 */
	protected $_capabilities = [
		'gravityforms_openinbound',
	];

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string Required capability for the settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_openinbound';

	/**
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string Required capability to edit form settings.
	 */
	protected $_capabilities_form_settings = 'gravityforms_openinbound';

	/**
	 * Add-on instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var self
	 */
	private static $_instance;

	/**
	 * Get instance of this class.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Register needed plugin hooks.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {
		$this->_title       = __( 'OpenInbound', 'openinbound' );
		$this->_short_title = __( 'OpenInbound', 'openinbound' );

		parent::init();
	}

	/**
	 * Prepare settings to be rendered on the feed settings tab.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array The feed settings fields
	 */
	public function feed_settings_fields() {
		$settings = [
			[
				'title'  => '',
				'fields' => [
					[
						'name'     => 'feed_name',
						'label'    => __( 'Name', 'openinbound' ),
						'type'     => 'text',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . esc_html__( 'Name', 'openinbound' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'openinbound' ),
					],
					[
						'name'      => 'fields',
						'label'     => __( 'Map Fields', 'openinbound' ),
						'type'      => 'field_map',
						'field_map' => $this->fields_for_feed_mapping(),
						'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'openinbound' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective OpenInbound fields.', 'openinbound' ),
					],
				],
			],
		];

		/**
		 * Filters the OpenInbound settings in Gravity Forms.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings Feed addon settings.
		 */
		return apply_filters( 'openinbound_gf_settings', $settings );
	}

	/**
	 * Prepare fields for field mapping feed settings field.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Fields for mapping.
	 */
	public function fields_for_feed_mapping() {
		$fields = array(
			array(
				'name'       => 'email',
				'label'      => __( 'Email Address', 'openinbound' ),
				'required'   => false,
				'field_type' => array( 'email' ),
			),
			array(
				'name'     => 'first_name',
				'label'    => __( 'First Name', 'openinbound' ),
				'required' => false,
			),
			array(
				'name'     => 'last_name',
				'label'    => __( 'Last Name', 'openinbound' ),
				'required' => false,
			),
			array(
				'name'     => 'phone',
				'label'    => __( 'Phone Number', 'openinbound' ),
				'required' => false,
			),
			array(
				'name'     => 'company_name',
				'label'    => __( 'Company', 'openinbound' ),
				'required' => false,
			),
		);

		/**
		 * Filters the fields mapping from OpenInbound to Gravity Forms.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields The fields to map.
		 */
		return apply_filters( 'openinbound_gf_fields_mapping', $fields );
	}

	/**
	 * Configure which columns should be displayed on the feed list page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Additional columns.
	 */
	public function feed_list_columns() {
		return array(
			'feed_name' => __( 'Name', 'openinbound' ),
		);
	}

	/**
	 * Process the feed and subscribe the user to the list.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 */
	public function process_feed( $feed, $entry, $form ) {
		$this->log_debug( __METHOD__ . '(): Processing feed.' );

		// Prepare audience member import array.
		$data = array(
			'ip_address' => $entry['ip'],
		);

		// Find all fields mapped and push them to the data array.
		foreach ( static::get_field_map_fields( $feed, 'fields' ) as $field_name => $field_id ) {
			$field_value = $this->get_field_value( $form, $entry, $field_id );
			if ( ! rgblank( $field_value ) ) {
				$data[ $field_name ] = $field_value;
			}
		}

		// If email address is empty, return.
		if ( \GFCommon::is_invalid_or_empty_email( $data['email'] ) ) {
			$this->log_error( __METHOD__ . '(): Email address not provided.' );

			return;
		}

		// Push any custom fields to the data array.
		if ( ! empty( $feed['meta']['custom_fields'] ) ) {
			foreach ( $feed['meta']['custom_fields'] as $custom_field ) {

				// If field map field is not paired to a form field, skip.
				if ( rgblank( $custom_field['value'] ) ) {
					continue;
				}

				$field_value = $this->get_field_value( $form, $entry, $custom_field['value'] );

				if ( ! rgblank( $field_value ) ) {
					$field_name          = ( 'gf_custom' === $custom_field['key'] ) ? $custom_field['custom_key'] : $custom_field['key'];
					$data[ $field_name ] = $field_value;
				}
			}
		}

		$tracking_id = get_option( 'openinbound_tracking_id', '' );
		$api_key     = get_option( 'openinbound_api_key', '' );

		if ( empty( $tracking_id ) ) {
			$this->log_error( __METHOD__ . '(): Tracking ID not set.' );
			return;
		}

		if ( empty( $api_key ) ) {
			$this->log_error( __METHOD__ . '(): API key not set.' );
			return;
		}

		$oi = new OI( $tracking_id, $api_key );

		$oi->updateContact( $_COOKIE['_oi_contact_id'], $data );

		$this->log_debug( __METHOD__ . '(): Updating contact on OpenInbound' );

		$properties = [
			'title'      => sprintf( 'Form submission by %1$s - %2$s', $data['email'], $this->get_field_value( $form, $entry, 'form_title' ) ),
			'event_type' => 'submission',
			// Todo: Does this need to be the raw GF data?
			'raw'        => json_encode( $data ),
		];

		$oi->addEvent( $_COOKIE['_oi_contact_id'], $properties );

		$this->log_debug( __METHOD__ . '(): Sending new event to OpenInbound' );
	}
}
