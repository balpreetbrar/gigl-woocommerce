<?php
/**
 * @package  GIGLDelivery
 */
namespace IncGiGl;

final class GIGL_Delivery_Init
{
	/**
	 * Store all the classes inside an array
	 * @return array Full list of classes
	 */
	public static function get_services() 
	{
		return [
			BaseHandlerLocateFiles\GIGL_Delivery_Settings_Links::class,
			PagesHandlerLocateFiles\GIGL_Delivery_Admin::class,
			BaseHandlerLocateFiles\GIGL_Delivery_Enqueue::class,
			PagesHandlerLocateFiles\GIGL_Delivery_Loader::class
		];
	}

	/**
	 * Loop through the classes, initialize them, 
	 * and call the register() method if it exists
	 * @return
	 */
	public static function register_services() 
	{
		foreach ( self::get_services() as $class ) {
			$service = self::instantiate( $class );
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 * @param  class $class    class from the services array
	 * @return class instance  new instance of the class
	 */
	private static function instantiate( $class )
	{
		$service = new $class();

		return $service;
	}
}