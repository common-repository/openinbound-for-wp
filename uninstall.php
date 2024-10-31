<?php
/**
 * Plugin uninstall routine.
 *
 * @package Required\OpenInbound
 */

defined( 'WP_UNINSTALL_PLUGIN' ) or die;

delete_option( 'openinbound_tracking_id' );
delete_option( 'openinbound_api_key' );
