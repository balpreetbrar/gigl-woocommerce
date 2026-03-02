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
function gigl_activate_plugin() {
	IncGiGl\BaseHandlerLocateFiles\GIGL_Delivery_Activator::activate();
}
register_activation_hook( __FILE__, 'gigl_activate_plugin' );

function gigl_deactivate_plugin() {
	IncGiGl\BaseHandlerLocateFiles\GIGL_Delivery_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'gigl_deactivate_plugin' );

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( 'IncGiGl\\GIGL_Delivery_Init' ) ) {
	IncGiGl\GIGL_Delivery_Init::register_services();
}
if ( ! defined( 'GIGL_PLUGIN_URL' ) ) {
    define( 'GIGL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}