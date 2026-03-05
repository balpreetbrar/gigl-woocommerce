<?php
/**
 * @package  GIGLDelivery
 */
namespace GIGLODE\BaseHandlerLocateFiles;

class GIGL_Delivery_Deactivator
{
	public static function deactivate() {
		flush_rewrite_rules();
	}
}