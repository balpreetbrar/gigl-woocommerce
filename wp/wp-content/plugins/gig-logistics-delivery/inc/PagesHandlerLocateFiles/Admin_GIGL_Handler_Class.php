<?php 
/**
 * @package  GIGLDelivery
 */
namespace IncGiGl\PagesHandlerLocateFiles;

use \IncGiGl\BaseHandlerLocateFiles\Base_Controller_Handler_Class;
use \IncGiGl\ApiHandlerLocateFiles\GIGL_Delivery_Settings_API;

/**
* 
*/
class Admin_GIGL_Handler_Class extends Base_Controller_Handler_Class
{
	public $settings;

	public $pages = array();

	public $subpages = array();

	public function __construct()
	{
		$this->settings = new GIGL_Delivery_Settings_API();

		$this->pages = array(
			array(
				'page_title' => 'GIGL Plugin', 
				'menu_title' => 'GIGL Logistics', 
				'capability' => 'manage_options', 
				'menu_slug' => 'gigl_plugin', 
				'callback' => function() { return require_once( plugin_dir_path( dirname( __FILE__, 2 ) ) . "/templates/admin.php" ); }, 
				'icon_url' => 'dashicons-airplane', 
				'position' => 110
			)
		);

		$this->subpages = array(
			array(
				'parent_slug' => 'gigl_plugin',
				'page_title' => 'GIGL Settings',
				'menu_title' => 'GIGL Settings',
				'capability' => 'manage_options',
				'menu_slug' => 'wc-settings&tab=shipping&section=gig_logistics_delivery',
				'callback' => function () {
					echo '<h1>' . esc_html__('CPT Manager', 'gig-logistics-delivery') . '</h1>';
				}
			)

		);
	}

	public function register() 
	{
		$this->settings->addPages( $this->pages )->withSubPage( 'Dashboard' )->addSubPages( $this->subpages )->register();
	}
}