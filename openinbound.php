<?php
/**
 * @wordpress-plugin
 * Plugin Name: OpenInbound for WP
 * Plugin URI:  https://openinbound.com
 * Description: Connector plugin for OpenInbound.com.
 * Version:     1.0.0
 * Author:      required
 * Author URI:  https://required.com
 * Text Domain: openinbound
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) or die;

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	include __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( 'WP_Requirements_Check' ) ) {
	trigger_error( 'Class files not found. Check Composer autoloader.', E_USER_WARNING );

	return;
}

$requirements_check = new WP_Requirements_Check( array(
	'title' => 'OpenInbound',
	'php'   => '5.4',
	'wp'    => '4.4',
	'file'  => __FILE__,
) );

if ( $requirements_check->passes() ) {
	include dirname( __FILE__ ) . '/init.php';
}

unset( $requirements_check );
