<?php
/**
 * @package  GIGLogisticsDelivery
 */
/*
Plugin Name: GIG Logistics Delivery
Plugin URI: https://giglogistics.com
Description: A delivery platform.
Version: 1.0.0
Requires PHP: 7.2.24
Requires at least: 6.5
Author: Pushtechn "GIGL"
Author URI: https://giglogistics.com/
License: GPLv2 or later
Text Domain: gig-logistics-delivery
Copyright: © 2026 GIG Logistics
Icon: assets/logo.png
*/


// If this file is called firectly, abort!!!
defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

// Require once the Composer Autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * The code that runs during plugin activation
 */
function activate_gigl_plugin() {
	IncGiGl\BaseHandlerLocateFiles\Activate_Shipping_Class_Handler::activate();
}
register_activation_hook( __FILE__, 'activate_gigl_plugin' );

/**
 * The code that runs during plugin deactivation
 */
function deactivate_gigl_plugin() {
	IncGiGl\BaseHandlerLocateFiles\Deactivate_Shipping_Class_Handler::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_gigl_plugin' );

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( 'IncGiGl\\Init_GIGL' ) ) {
	IncGiGl\Init_GIGL::register_services();
}
