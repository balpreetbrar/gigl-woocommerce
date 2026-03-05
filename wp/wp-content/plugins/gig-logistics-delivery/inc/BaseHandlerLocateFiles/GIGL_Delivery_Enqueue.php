<?php
/**
 * @package GIGLDelivery
 */

namespace GIGLODE\BaseHandlerLocateFiles;

use GIGLODE\BaseHandlerLocateFiles\GIGL_Delivery_Base_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GIGL_Delivery_Enqueue extends GIGL_Delivery_Base_Controller {

	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue() {

		wp_enqueue_style(
			'gigl-delivery-frontend-style',
			$this->plugin_url . 'assets/css/gigl-frontend.css',
			array(),
			'1.0.0',
			'all'
		);

		wp_enqueue_script(
			'gigl-delivery-frontend-script',
			$this->plugin_url . 'assets/js/gigl-frontend.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}
}