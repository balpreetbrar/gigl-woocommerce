<?php
/**
 * @package  GIGLDelivery
 */
namespace IncGiGl\BaseHandlerLocateFiles;

use \IncGiGl\BaseHandlerLocateFiles\GIGL_Delivery_Base_Controller;
if ( ! defined( 'ABSPATH' ) ) exit;

class GIGL_Delivery_Settings_Links extends GIGL_Delivery_Base_Controller
{	
	
	
	public function register() 
	{
		
		
		add_filter( "plugin_action_links_$this->plugin", array( $this, 'settings_link' ) );
	}

	public function settings_link( $links ) 
	{
		$settings_link = '<a href="admin.php?page=wc-settings&tab=shipping&section=gig_logistics_delivery">Settings</a>';
		array_push( $links, $settings_link );
		return $links;
	}
	
}