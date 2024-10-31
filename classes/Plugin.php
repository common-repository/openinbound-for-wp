<?php
/**
 * @package Required\OpenInbound
 */

namespace Required\OpenInbound;

use GFAddOn;
use GFForms;
use WPCF7_ContactForm;
use WPCF7_Submission;
use WP_REST_Response;

class Plugin {
	/**
	 * URL to the tracking script.
	 *
	 * @since 1.0.0
	 */
	const OI_TRACKER_URL = '//api.openinbound.com/tracker.js';

	/**
	 * Tracking ID option name.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string
	 */
	protected $oi_tracking_id_meta = 'openinbound_tracking_id';

	/**
	 * API key option name.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string
	 */
	protected $oi_api_key_meta = 'openinbound_api_key';

	/**
	 * Page hook suffix for the settings page.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @var string
	 */
	protected $page_hook = '';

	/**
	 * Registers all the needed hooks.
	 */
	public function init() {
		// General & Admin.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		//add_action( 'init', [ $this, 'oi_rewrite_rule' ], 10, 0);

		add_action( 'rest_api_init',[ $this, 'oi_endpoints' ]);


		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'plugin_action_links_' . basename( dirname( __DIR__ ) ) . '/openinbound.php', [ $this, 'plugin_action_links' ] );

		// Add tracker script to front-end.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_async_attr' ], 10, 2 );

		// Contact Form 7 tracking.
		add_action( 'wpcf7_mail_sent', [ $this, 'track_contact_form7' ] );

		add_action( 'admin_init', [ new ContactForm7(), 'show_help_content' ] );

		// Gravity Forms tracking.
		add_action( 'gform_loaded', [ $this, 'track_gravity_forms' ] );

		add_filter('rewrite_rules_array', [ $this, 'create_rewrite_rules' ] );

		add_filter('query_vars', [ $this, 'add_query_vars' ]);
		add_action( 'admin_init', [ $this, 'flush_rewrite_rules' ] );
	}


	function oi_rewrite_rule(){
		//echo 'woot';
		//add_rewrite_rule('oi2/repost.js', '/wp-json/oi/repost.js', 'top');
	}

	function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	function create_rewrite_rules($rules) {
		global $wp_rewrite;
		$newRule = array('oi/repost.js' => 'wp-json/oi/repost.js');
		$newRules = $newRule + $rules;
		return $newRules;
	}

	function add_query_vars($new_var) {
		$new_var[] = "oi2/repost.js";
		return $new_var;
	}


	function oi_endpoints() {
		register_rest_route( 'oi', '/repost.js', array(
			'methods' => 'GET',
			'callback' => [ $this, 'oi_endpoint_repost' ],
		));
	}
	function oi_endpoint_repost() {
		$response = $_GET;
		$oi_contact_id = filter_input(INPUT_COOKIE, '_oi_contact_id', FILTER_VALIDATE_INT);
		if ($oi_contact_id > 0) {
			$oi_email = filter_input(INPUT_GET, 'oi_email');
			if (!empty($oi_email)) {
				if ($oi_email) {
					$data['email'] = $oi_email;
					$oi = new OI(get_option('openinbound_tracking_id'), get_option('openinbound_api_key'));
					$oi->updateContact($oi_contact_id, $data);

					$properties = array();
					$properties['title'] = 'E-Mail updated to '.$oi_email.' through "oi_email" parameter.';
					$properties['event_type'] = 'oi_email';
					$properties['raw'] = json_encode($data);
					$oi->addEvent($oi_contact_id, $properties);
				}
			}

			$oi_add_tag = filter_input(INPUT_GET, 'oi_add_tag');
			if (!empty($oi_add_tag)) {
				$oi = new OI(get_option('openinbound_tracking_id'), get_option('openinbound_api_key'));
				$oi->addTagToContact($oi_contact_id, $oi_add_tag);

				$properties = array();
				$properties['title'] = 'Tag "'.$oi_add_tag.'" added through "oi_add_tag" parameter.';
				$properties['event_type'] = 'oi_add_tag';
				$properties['raw'] = json_encode($oi_add_tag);
				$oi->addEvent($oi_contact_id, $properties);
			}

			$oi_add_event = filter_input(INPUT_GET, 'oi_add_event');
			if (!empty($oi_add_event)) {
				$oi = new OI(get_option('openinbound_tracking_id'), get_option('openinbound_api_key'));
				$properties = array();
				$properties['title'] = 'Event "'.$oi_add_event.'" added through "oi_add_event" parameter.';
				$properties['event_type'] = 'oi_add_event';
				$properties['raw'] = json_encode($oi_add_event);
				$oi->addEvent($oi_contact_id, $properties);
			}
		}
		header("Content-type: text/javascript");
		echo $oi_contact_id;
		exit();
		return new WP_REST_Response( $response );
	}

	/**
	 * Loads the plugin's translations.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'openinbound' );
	}

	/**
	 * Adds a settings link to the plugins page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $links Plugin action links.
	 * @return array Plugin action links.
	 */
	public function plugin_action_links( array $links ) {
		return array_merge( [ '<a href="' . admin_url( 'options-general.php?page=openinbound' ) . '">' . __( 'Settings', 'openinbound' ) . '</a>' ], $links );
	}

	/**
	 * Add the OI tracker.js script to the front-end.
	 */
	public function enqueue_scripts() {
		// Get the tracking_id option.
		$tracking_id = get_option( $this->oi_tracking_id_meta, '' );

		// Don't register the script when no tracking_id is set.
		if ( empty( $tracking_id ) ) {
			return;
		}

		/**
		 * Add ?tracking_id=<tracking_id> to the tracker.js URL.
		 */
		$tracker_url = add_query_arg( 'tracking_id', trim( $tracking_id ), self::OI_TRACKER_URL );

		wp_enqueue_script( 'openinbound', $tracker_url, null, '1.0.0', true );
		wp_script_add_data( 'openinbound', 'async', true );
	}

	/**
	 * Add async loading to the script.
	 *
	 * @param string $tag    script tag.
	 * @param string $handle script name.
	 *
	 * @return string mixed script tag with async option.
	 */
	public function add_async_attr( $tag, $handle ) {
		if ( wp_scripts()->get_data( $handle, 'async' ) ) {
			$tag = str_replace( '></', ' async></', $tag );
		}

		return $tag;
	}

	/**
	 * Registers setting and settings fields.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_settings() {
		/**
		 * Register a new section on the "openinbound" page.
		 */
		add_settings_section(
			'openinbound_keys',
			__( 'API Key &amp; Tracking ID for your website', 'openinbound' ),
			[ $this, 'keys_section_cb' ],
			'openinbound'
		);

		/**
		 * Register new settings field in the "openinbound_keys" section on the "openinbound" page.
		 */
		add_settings_field(
			$this->oi_api_key_meta,
			__( 'API Key', 'openinbound' ),
			[ $this, 'field_cb' ],
			'openinbound',
			'openinbound_keys',
			[
				'label_for' => $this->oi_api_key_meta,
				'class'     => 'openinbound_row',
			]
		);

		/**
		 * Register new settings field in the "openinbound_keys" section on the "openinbound" page.
		 *
		 * @since 1.0.0
		 * @access public
		 */
		add_settings_field(
			$this->oi_tracking_id_meta,
			__( 'Tracking ID', 'openinbound' ),
			[ $this, 'field_cb' ],
			'openinbound',
			'openinbound_keys',
			[
				'label_for' => $this->oi_tracking_id_meta,
				'class'     => 'openinbound_row',
			]
		);

		register_setting( 'openinbound', $this->oi_tracking_id_meta, 'sanitize_text_field' );
		register_setting( 'openinbound', $this->oi_api_key_meta, 'sanitize_text_field' );
	}

	/**
	 * Adding a custom admin page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_admin_menu() {
		$this->page_hook = add_options_page(
			__( 'OpenInbound Settings', 'openinbound' ),
			__( 'OpenInbound', 'openinbound' ),
			'manage_options',
			'openinbound',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Displays the settings page content.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function render_admin_page() {
		/**
		 * Check user capabilities.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				/**
				 * Output security fields for the registered setting "openinbound".
				 */
				settings_fields( 'openinbound' );
				/**
				 * Render setting sections and their fields.
				 */
				do_settings_sections( 'openinbound' );
				/**
				 * Output a save button.
				 */
				submit_button( __( 'Save Settings', 'openinbound' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Callback when rendering the section on the "openinbound" page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args Setting arguments.
	 */
	public function keys_section_cb( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Get your API Key and Tracking ID from the OpenInbound.com Settings page.', 'openinbound' ); ?></p>
		<?php
	}

	/**
	 * Renders the input fields for the settings.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args for this field.
	 */
	public function field_cb( $args ) {
		if ( ! isset( $args['label_for'] ) ) {
			return;
		}

		$option = get_option( $args['label_for'], '' );
		?>
		<input class="regular-text code" id="<?php echo esc_attr( $args['label_for'] ); ?>" value="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" type="text" />
		<?php
	}

	/**
	 * Tracks Contact Form 7 form submissions.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WPCF7_ContactForm $form The form that was submitted.
	 */
	public function track_contact_form7( WPCF7_ContactForm $form ) {
		$submission = WPCF7_Submission::get_instance();
		$data = [];

		$posted_data = $submission->get_posted_data();

		$contact_form_7 = new ContactForm7();

		$data = $posted_data;

		/*
		// Only send predefiend fields back to Openinbound
		foreach ( $contact_form_7->get_list_of_field_names() as $cf7_field => $oi_field ) {
			if ( isset( $posted_data[ $cf7_field ] ) ) {
				$data[ $oi_field ] = $posted_data[ $cf7_field ];
			}
		}*/


		$contact_form_7->send_form_data( $data, $form->title() );
	}

	/**
	 * Registers the Gravity Forms feed add-on.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function track_gravity_forms() {
		GFForms::include_feed_addon_framework();
		GFAddOn::register( GravityFormsFeed::class );
	}
}
