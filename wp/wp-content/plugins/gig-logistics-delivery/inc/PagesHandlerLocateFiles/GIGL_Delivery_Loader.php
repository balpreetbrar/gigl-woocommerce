<?php
	
	/**
		* Main Gig Logistics Delivery Class.
		*
		* @class  GIGL_Delivery_Loader
	*/
	namespace IncGiGl\PagesHandlerLocateFiles;	

	defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );
	class GIGL_Delivery_Loader
	{
		private static $active_plugins;
		public function register(){
			self::$active_plugins = (array) get_option('active_plugins', array());
			
			if (is_multisite()) {
				self::$active_plugins = array_merge(self::$active_plugins, get_site_option('active_sitewide_plugins', array()));
			}
			
			if (!$this->wc_active_check()) {
				return;
			}
			add_action('plugins_loaded', array($this, 'init_plugin'));
		}
		public function wc_active_check()
		{
			return in_array('woocommerce/woocommerce.php', self::$active_plugins) || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins);
		}
		public function init_plugin()
		{
			
			// load the main plugin class
			require_once(plugin_dir_path(__FILE__) . 'GIGL_Delivery_Main.php');
			
			GIGL_Delivery_Main();
		}
	}
