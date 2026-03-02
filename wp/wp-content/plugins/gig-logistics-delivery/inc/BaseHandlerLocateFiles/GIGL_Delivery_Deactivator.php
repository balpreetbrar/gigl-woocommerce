<?php
/**
 * @package  GIGLDelivery
 */
namespace IncGiGl\BaseHandlerLocateFiles;

class GIGL_Delivery_Deactivator
{
	public static function deactivate() {
		flush_rewrite_rules();
	}
}