<?php
/**
 * @package GIGLDelivery
 */

namespace GIGLODE;

final class GIGL_Delivery_Init {

	/**
	 * List of services
	 */
	public static function get_services() {
		return [
			BaseHandlerLocateFiles\GIGL_Delivery_Settings_Links::class,
			PagesHandlerLocateFiles\GIGL_Delivery_Admin::class,
			BaseHandlerLocateFiles\GIGL_Delivery_Enqueue::class,
			PagesHandlerLocateFiles\GIGL_Delivery_Loader::class
		];
	}

	/**
	 * Register services
	 */
	public static function register_services() {

		foreach ( self::get_services() as $class ) {

			if ( class_exists( $class ) ) {

				$service = self::instantiate( $class );

				if ( method_exists( $service, 'register' ) ) {
					$service->register();
				}

			}

		}
	}

	/**
	 * Instantiate class
	 */
	private static function instantiate( $class ) {
		return new $class();
	}
}