<?php
/**
 * @package GIGLogisticsDelivery
 */

/*
Plugin Name: GIG Logistics Delivery
Plugin URI: https://giglogistics.com
Description: WooCommerce shipping integration for GIG Logistics.
Version: 1.0.0
Requires PHP: 7.2
Requires at least: 6.0
Author: Pushtechn "GIGL"
Author URI: https://giglogistics.com/
License: GPLv2 or later
Text Domain: gig-logistics-delivery
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin URL constant
if ( ! defined( 'GIGL_PLUGIN_URL' ) ) {
	define( 'GIGL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Require Composer autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * Plugin activation
 */
function giglode_activate_plugin() {
	GIGLODE\BaseHandlerLocateFiles\GIGL_Delivery_Activator::activate();
}
register_activation_hook( __FILE__, 'giglode_activate_plugin' );

/**
 * Plugin deactivation
 */
function giglode_deactivate_plugin() {
	GIGLODE\BaseHandlerLocateFiles\GIGL_Delivery_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'giglode_deactivate_plugin' );

/**
 * Initialize plugin
 */
if ( class_exists( 'GIGLODE\\GIGL_Delivery_Init' ) ) {
	GIGLODE\GIGL_Delivery_Init::register_services();
}