<?php
/**
 * @package  GIGLDelivery
 */
namespace IncGiGl\BaseHandlerLocateFiles;

class GIGL_Delivery_Activator
{
	public static function activate() {
		flush_rewrite_rules();
	}
}