<?php
/**
 * @package  GIGLDelivery
 */
namespace GIGLODE\BaseHandlerLocateFiles;

class GIGL_Delivery_Activator
{
	public static function activate() {
		flush_rewrite_rules();
	}
}