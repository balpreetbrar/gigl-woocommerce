<?php

if (!defined('ABSPATH'))
	exit; // Exit if accessed directly

/**
 * Main Gig Logistics Delivery Class.
 *
 * @class  GIGL_Delivery_Main
 */
class GIGL_Delivery_Main
{
	/** @var \GIGL_Delivery_API api for this plugin */
	public $api;

	/** @var array settings value for this plugin */
	public $settings;

	/** @var array order status value for this plugin */
	public $statuses;

	/** @var plugin path identifier */
	public $my_plugin_path;

	/** @var get shipping waybill*/
	public $currentWaybill;

	/** @var \GIGL_Delivery_Main single instance of this plugin */
	protected static $instance;

	/**
	 * Loads functionality/admin classes and add auto schedule order hook.
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		//get plugin_path
		$this->my_plugin_path = plugin_dir_path(dirname(__FILE__, 1));

		// get settings
		$this->settings = maybe_unserialize(get_option('woocommerce_gig_logistics_delivery_settings'));

		$this->statuses = [
			'UPCOMING',
			'STARTED',
			'ENDED',
			'FAILED',
			'ARRIVED',
			'',
			'UNASSIGNED',
			'ACCEPTED',
			'DECLINE',
			'CANCEL',
			'DELETED',
			'MCRT'
		];

		$this->init_plugin();

		$this->init_hooks();

	}

	/**
	 * Initializes the plugin.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function init_plugin()
	{
		$this->includes();

		if (is_admin()) {
			$this->admin_includes();
		}

	}



	/**
	 * Includes all files.
	 *
	 * @since 1.0.0
	 */
	public function includes()
	{
		$plugin_path = plugin_dir_path(dirname(__FILE__, 2));

		require_once $plugin_path . 'inc/BaseHandlerLocateFiles/GIGL_Delivery_API.php';

		require_once $plugin_path . 'inc/BaseHandlerLocateFiles/GIGL_Delivery_Shipping_Method.php';
	}

	public function admin_includes()
	{
		$plugin_path = plugin_dir_path(dirname(__FILE__, 2));

		require_once $plugin_path . 'inc/BaseHandlerLocateFiles/GIGL_Delivery_Orders.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	public function init_hooks()
	{
		/**
		 * Actions
		 */


		// create order when \WC_Order::payment_complete()
		add_action('woocommerce_thankyou', array($this, 'create_order_shipping_task'));


		add_action('woocommerce_shipping_init', array($this, 'load_shipping_method'));

		//add css file for tracking popup
		// add_action( 'wp_enqueue_scripts', array( $this, 'gigl_enqueue_front_styles' ) );
		add_action('wp_enqueue_scripts', array($this, 'gigl_enqueue_assets'));
		
		// cancel a Gigl delivery task when an order is cancelled in WC
		add_action('woocommerce_order_status_cancelled', array($this, 'cancel_order_shipping_task'));

		// adds tracking button(s) to the View Order page
		add_action('woocommerce_order_details_after_order_table', array($this, 'add_view_order_tracking'), 99, 3);

		/**
		 * Filters
		 */
		// Add shipping icon to the shipping label on cart and checkout.
		add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'add_shipping_icon'), PHP_INT_MAX, 2);

		add_filter('woocommerce_checkout_fields', array($this, 'remove_address_2_checkout_fields'));

		add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));

		add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');

		add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_false');
	}

	/**
	 * shipping_icon to desplay on cart and checkout.
	 *
	 * @since   1.0.0
	 */
	function add_shipping_icon($label, $method)
	{
		if ($method->method_id == 'gig_logistics_delivery') {
			$plugin_path = $this->my_plugin_path;
			$logo_title = 'Gig Logistics Delivery';
			$icon_url = plugins_url('assets/logo/gig-logo2.png', $plugin_path);
			$img = '<img class="gigl-logistics-delivery-logo"' .
				' alt="' . $logo_title . '"' .
				' title="' . $logo_title . '"' .
				' style="width:25px; height:25px; display:inline;"' .
				' src="' . $icon_url . '"' .
				'>';
			$label = $img . ' ' . $label;
		}

		return $label;
	}
	public function gigl_enqueue_assets()
	{
		// Load only on WooCommerce My Account / View Order page
		// if (!is_account_page()) {
		// 	return;
		// }

		// CSS
		wp_enqueue_style(
			'gigl-front-style',
			GIGL_PLUGIN_URL . 'assets/css/gigl-admin.css',
			array(),
			'1.0.0'
		);

		// JS (THIS IS WHERE YOUR CODE GOES)
		wp_enqueue_script(
			'gigl-modal',
			GIGL_PLUGIN_URL . 'assets/js/gigl-modal.js',
			array(),
			'1.0.0',
			true
		);
	}
	public function gigl_enqueue_front_styles()
	{

		// Load only on My Account / View Order page
		if (!is_account_page()) {
			return;
		}

		wp_enqueue_style(
			'gigl-front-style',
			GIGL_PLUGIN_URL . 'assets/css/gigl-admin.css',
			array(),
			'1.0.0'
		);
	}

	/**
	 * Submit data to Gigl to handle your delivery.
	 *
	 * @since   1.0.0
	 */

	public function create_order_shipping_task($order_id)
	{
		$order = wc_get_order($order_id);
		$get_waybilling = get_post_meta($order_id, 'gig_logistics_delivery_waybill', true);
		if (!empty($get_waybilling)) {
			return;
		}
		// $order_status    = $order->get_status();
		$order_items = $order->get_items();
		$methods = $order->get_shipping_methods();
		$shipping_method = !empty($methods) ? array_shift($methods) : null;

		if (strpos($shipping_method->get_method_id(), 'gig_logistics_delivery') !== false) {

			$delivery_country_code = $order->get_shipping_country();
			if ( $delivery_country_code !== 'NG' ) {
				$this->create_international_order_shipping_task($order_id);
				return;
			}

			$receiver_name = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
			$receiver_email = $order->get_billing_email();
			$receiver_phone = $order->get_billing_phone();
			$delivery_base_address = $order->get_shipping_address_1();
			// $delivery_address2  = $order->get_shipping_address_2();
			// $delivery_company   = $order->get_shipping_company();
			$delivery_city = $order->get_shipping_city();
			$delivery_state_code = $order->get_shipping_state();
			$delivery_postcode = $order->get_shipping_postcode();


			$delivery_country_code = $order->get_shipping_country();
			$delivery_state = WC()->countries->get_states($delivery_country_code)[$delivery_state_code];
			$delivery_country = WC()->countries->get_countries()[$delivery_country_code];
			$payment_method = $order->get_payment_method();

			if ($payment_method == 'cod') {

				$gigl_payment_method = 1048576;

			} else {

				$gigl_payment_method = 524288;

			}

			$preShipmentItems = array();
			foreach ($order_items as $item_id => $item) {

				// methods of WC_Order_Item class

				// The element ID can be obtained from an array key or from:
				$item_id = $item->get_id();

				// methods of WC_Order_Item_Product class

				$item_name = $item->get_name(); // Name of the product
				$item_type = $item->get_type(); // Type of the order item ("line_item")

				$product_id = $item->get_product_id(); // the Product id
				$wc_product = $item->get_product();    // the WC_Product object


				if ($wc_product) {
					$quantity = $item->get_quantity();
					$weight = $wc_product->get_weight(); // Weight in kg (default WooCommerce unit)

					// Sum the weight
					$total_weight = floatval($weight) * intval($quantity);
				}

				// order item data as an array
				$item_data = $item->get_data();
				$eachProductItem = array(
					// "PreShipmentItemMobileId"=> 0,
					"Description" => $item_data['name'],
					"ItemName" => $item_data['name'],
					"ShipmentType" => 1,

					"Quantity" => $item_data['quantity'],
					"Weight" => ($total_weight) ? $total_weight : 1,
					"IsVolumetric" => false,
					"Length" => 10,
					"Width" => 5,
					"Height" => 3,
					"Value" => $item_data['total'],
					"SpecialPackageId" => 0,
					// "Weight2"=> 0,
					// "ItemType" => "Normal", 
					// "WeightRange" => "0", 
					"HaulageId" => 0


					// "EstimatedPrice"=> $item_data['total'],
					// "ImageUrl"=> "",
					// "SerialNumber"=> (int) ('4'.$item_data["total"]),


					// "PreShipmentMobileId"=> 0,
					// "CalculatedPrice"=> $item_data['total'],
					// "IsCancelled"=> false,
					// "PictureName"=> "",
					// "PictureDate"=> null,
				);

				$preShipmentItems[] = $eachProductItem;

			}

			$sender_name = $this->settings['sender_name'];
			$sender_phone = $this->settings['sender_phone_number'];
			$pickup_base_address = $this->settings['pickup_base_address'];
			$pickup_city = $this->settings['pickup_city'];
			$pickup_state = $this->settings['pickup_state'];
			$pickup_country = $this->settings['pickup_country'];
			$pickup_postcode = $this->settings['pickup_postcode'];
			if (trim($pickup_country) == '') {
				$pickup_country = 'NG';
			}

			$timestamp = current_time('timestamp');
			$todaydate = gmdate('Y-m-d H:i:s', $timestamp);
			$pickup_date = gmdate('Y-m-d H:i:s', strtotime('+1 day', $timestamp));
			$delivery_date = gmdate('Y-m-d H:i:s', strtotime('+2 day', $timestamp));

			$api = $this->get_api();

			if ($delivery_postcode == '') {
				$delivery_address = (($delivery_base_address) ? ("$delivery_base_address,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : '') . 'nigeria';
				$delivery_address1 = (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : '') . 'nigeria';

				$delivery_coordinate = $api->get_lat_lng($delivery_address);

				if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					$delivery_coordinate = $api->get_lat_lng($delivery_address1);
				}
				if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					$delivery_coordinate = $api->get_lat_lng("$delivery_state, $delivery_country");
				}

				$pickup_address = (($pickup_base_address) ? ("$pickup_base_address,") : '') . (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : '') . 'nigeria';
				$pickup_address1 = (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : '') . 'nigeria';

				$pickup_coordinate = $api->get_lat_lng($pickup_address);

				if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					$pickup_coordinate = $api->get_lat_lng($pickup_address1);
				}
				if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					$pickup_coordinate = $api->get_lat_lng("$pickup_state, $pickup_country");
				}

			} else {


				$delivery_address1 = $delivery_postcode . ',' . $delivery_city . ',' . $delivery_state . ',nigeria';
				$delivery_address1 = trim("$delivery_address1");
				$delivery_address3 = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : '') . 'nigeria';
				$delivery_address2 = (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : '') . 'nigeria';
				$delivery_address = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_base_address) ? ("$delivery_base_address,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : '') . 'nigeria';
				//$delivery_address = trim("$delivery_base_address, $delivery_city, $delivery_state, $delivery_country,$delivery_postcode");
				$delivery_coordinate = $api->get_lat_lng($delivery_address);

				if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					$delivery_coordinate = $api->get_lat_lng("$delivery_address3");
				}
				if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					$delivery_coordinate = $api->get_lat_lng($delivery_address2);
				}

				$pickup_address = (($pickup_postcode) ? ("$pickup_postcode,") : '') . (($pickup_base_address) ? ("$pickup_base_address,") : '') . (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : '') . 'nigeria';
				$pickup_address1 = (($pickup_postcode) ? ("$pickup_postcode,") : '') . (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : '') . 'nigeria';
				$pickup_address2 = (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : '') . 'nigeria';

				$pickup_coordinate = $api->get_lat_lng($pickup_address);

				if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					$pickup_coordinate = $api->get_lat_lng("$pickup_address1");
				}
				if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					$pickup_coordinate = $api->get_lat_lng($pickup_address2);
				}
			}
			$receiverLocation = array(
				"Latitude" => (string) $delivery_coordinate['Latitude'],
				"Longitude" => (string) $delivery_coordinate['Longitude'],
				"FormattedAddress" => "",
				"Name" => "",
				"LGA" => ""
			);

			$senderLocation = array(
				"Latitude" => (string) $pickup_coordinate['Latitude'],
				"Longitude" => (string) $pickup_coordinate['Longitude'],
				"FormattedAddress" => "",
				"Name" => "",
				"LGA" => ""
			);
			// if($payment_method == 'cod') {
			$params = array(
				"ShipmentItems" => $preShipmentItems,
				"ShipmentDetails" => [
					"VehicleType" => 1,
					"IsBatchPickUp" => 1,
					"IsFromAgility" => 0
				],
				"SenderDetails" => [
					"SenderName" => $sender_name,
					"SenderPhoneNumber" => $sender_phone,
					// "SenderStationId" => 1,
					"SenderAddress" => $pickup_address,
					"InputtedSenderAddress" => $pickup_address,
					"SenderLocality" => $pickup_city,
					"SenderLocation" => $senderLocation
				],
				"ReceiverDetails" => [
					// "ReceiverStationId" => 1,
					"ReceiverName" => $receiver_name,
					"ReceiverPhoneNumber" => $receiver_phone,
					"ReceiverAddress" => $delivery_address,
					"InputtedReceiverAddress" => $delivery_address,
					"ReceiverLocation" => $receiverLocation
				],

			);

			// }else{

			// 	$params = array(
			// 				"PreShipmentMobileId"=> 0,
			// 				"ReceiverAddress" => $delivery_address,  
			// 				"SenderLocality" => $pickup_city,
			// 				"InputtedSenderAddress"=>$pickup_address,
			// 				"SenderAddress" => $pickup_address, 
			// 				"ReceiverPhoneNumber" => $receiver_phone,
			// 				"InputtedReceiverAddress"=>$delivery_address,
			// 				"VehicleType" => "BIKE", 
			// 				"SenderPhoneNumber" => $sender_phone, 
			// 				"SenderName" => $sender_name,
			// 				"ReceiverName" => $receiver_name, 
			// 				"ReceiverLocation" => $receiverLocation,
			// 				"SenderLocation" => $senderLocation,
			// 				"PreShipmentItems" => $preShipmentItems,
			// 				"IsBatchPickUp"=> false,
			// 				"WaybillImage"=> "",
			// 				"WaybillImageFormat"=> "",
			// 				"DestinationServiceCenterId"=> 0,
			// 				"DestinationServiceCentreId"=> 0,
			// 				"IsCashOnDelivery"=> false,
			// 				"CashOnDeliveryAmount"=> 0.00
			// 			);
			// }


			$response = $api->create_task($params);
			$order->add_order_note("Gig Logistics Delivery: " . $response->message);
			

			if (isset($response->data->Waybill)) {
				if ($this->settings['mode'] == 'test' || $this->settings['mode'] == 'Test') {
					$endpoint = 'https://dev-thirdpartynode.theagilitysystems.com/track/mobileShipment?Waybill=';
				} else {
					$endpoint = 'https://thirdpartynode.theagilitysystems.com/track/mobileShipment?Waybill=';
				}
				
				//$data = $res['data'];
				$this->currentWaybill = $response->data->Waybill;
				update_post_meta($order_id, 'gig_logistics_delivery_waybill', $response->data->Waybill);
				update_post_meta($order_id, 'gig_logistics_delivery_check_status_url', $endpoint . $response->data->Waybill);

				// For Pickup
				update_post_meta($order_id, 'gig_logistics_delivery_pickup_id', $response->data->Waybill);
				//update_post_meta($order_id, 'gig_logistics_delivery_pickup_status', $this->statuses[6]); // UNASSIGNED
				update_post_meta($order_id, 'gig_logistics_delivery_pickup_tracking_url', $endpoint . $response->data->Waybill);

				// For Delivery
				update_post_meta($order_id, 'gig_logistics_delivery_delivery_id', $response->data->Waybill);
				//update_post_meta($order_id, 'gig_logistics_delivery_delivery_status', $this->statuses[6]); // UNASSIGNED
				update_post_meta($order_id, 'gig_logistics_delivery_delivery_tracking_url', $endpoint . $response->data->Waybill);

				update_post_meta($order_id, 'gig_logistics_delivery_status_res', $this->statuses[6]); // UNASSIGNED

				update_post_meta($order_id, 'gig_logistics_delivery_order_response', $response);

				$note = sprintf(
					/* translators: %s: Gig Logistics Waybill ID */
					__('Shipment scheduled via Gigl delivery (Order Id: %s)', 'gig-logistics-delivery'),
					$response->data->Waybill
				);
				$order->add_order_note($note);
			}
		}
	}

	public function create_international_order_shipping_task($order_id) {
		$order = wc_get_order($order_id);
		$api = $this->get_api();

		$country_code = $order->get_shipping_country();
		$countries_res = get_transient( 'giglode_countries_list' );

		if ( empty( $countries_res ) ) {
			$countries_res = $api->get_countries();
			if ( ! empty( $countries_res->data ) ) {
				set_transient( 'giglode_countries_list', $countries_res, DAY_IN_SECONDS );
			}
		}

		$destination_country_id = 0;
		$destination_country_name = '';

		if ( ! empty( $countries_res->data ) ) {
			foreach ( $countries_res->data as $country ) {
				if ( (isset($country->TwoLetterCode) && $country->TwoLetterCode === $country_code) || (isset($country->CountryCode) && $country->CountryCode === $country_code) ) {
					$destination_country_id = $country->CountryId;
					$destination_country_name = $country->CountryName;
					break;
				}
			}
		}

		if ( empty( $destination_country_id ) ) {
			return;
		}

		// Get selected delivery type and logistic company from shipping method meta
		$methods = $order->get_shipping_methods();
		$shipping_method = !empty($methods) ? array_shift($methods) : null;
		$delivery_type = 0; // Default Standard
		$logistic_company = 0;

		if ( $shipping_method ) {
			// Prefer meta_data values saved during add_rate() — more reliable than parsing the rate ID
			$delivery_type_meta    = $shipping_method->get_meta('delivery_type');
			$logistic_company_meta = $shipping_method->get_meta('logistic_company');

			if ( $delivery_type_meta !== '' && $delivery_type_meta !== null ) {
				$delivery_type = intval($delivery_type_meta);
			} else {
				// Fallback: extract from rate ID suffix e.g. gig_logistics_delivery:1_0
				$full_id = $shipping_method->get_id();
				if ( preg_match('/_(\d+)$/', $full_id, $matches) ) {
					$delivery_type = intval($matches[1]);
				}
			}

			if ( $logistic_company_meta !== '' && $logistic_company_meta !== null ) {
				$logistic_company = intval($logistic_company_meta);
			}
		}

		$preShipmentItems = array();
		$declared_value = 0;
		foreach ($order->get_items() as $item) {
			$wc_product = $item->get_product();
			$quantity = $item->get_quantity();
			$weight = $wc_product ? $wc_product->get_weight() : 1;
			$total_weight = floatval($weight) * intval($quantity);
			
			$preShipmentItems[] = array(
				"InternationalShipmentItemType" => 1,
				"Description" => $item->get_name(),
				"Weight" => $total_weight ?: 1,
				"Quantity" => $quantity,
				"Nature" => 1,
				"IsVolumetric" => true,
				"Length" => 10,
				"Width" => 10,
				"Height" => 10,
				"PackagingType" => 1,
				"Value" => $item->get_total()
			);
			$declared_value += $item->get_total();
		}

		$params = array(
			"Shipments" => array(
				array(
					"Receiver" => array(
						"ReceiverName" => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
						"ReceiverState" => $order->get_shipping_state(),
						"ReceiverPhoneNumber" => $order->get_billing_phone(),
						"ReceiverAltPhoneNumber" => $order->get_billing_phone(),
						"ReceiverEmail" => $order->get_billing_email(),
						"ReceiverCity" => $order->get_shipping_city(),
						"ReceiverAddress" => $order->get_shipping_address_1(),
						"ReceiverPostalCode" => $order->get_shipping_postcode(),
						"ReceiverCountryCode" => $country_code,
						"ReceiverCountry" => $destination_country_name,
						"ReceiverStateOrProvinceCode" => $order->get_shipping_state()
					),
					"ShipmentItems" => $preShipmentItems,
					"ShipmentDetails" => array(
						"ManufacturerCountry" => "NIGERIA",
						"DestinationCountryId" => $destination_country_id,
						"PickupOptions" => 1,
						"DeclaredValue" => $declared_value,
						"DeliveryType" => $delivery_type,
						"IsVacuumSeal" => false,
						"IsPhytosanitaryCertification" => false,
						"LogisticsCompany" => $logistic_company,
						"FedexPackagingType" => 1
					)
				)
			)
		);

		$response = $api->create_international_shipment($params);

		error_log( '[GIGL INTL] create_international_shipment response: ' . print_r( $response, true ) );

		// Extract waybill from multiple possible response structures
		$waybill = null;
		if ( isset( $response->data->Waybill ) ) {
			$waybill = $response->data->Waybill;
		} elseif ( isset( $response->data ) && is_array( $response->data ) && isset( $response->data[0]->Waybill ) ) {
			$waybill = $response->data[0]->Waybill;
		} elseif ( isset( $response->data ) && is_object( $response->data ) ) {
			// Walk all properties to find anything with 'waybill' in the key
			foreach ( (array) $response->data as $key => $value ) {
				if ( stripos( $key, 'waybill' ) !== false && ! empty( $value ) ) {
					$waybill = $value;
					break;
				}
			}
		}

		if ( ! empty( $waybill ) ) {
			update_post_meta($order_id, 'gig_logistics_delivery_waybill', $waybill);
			$order->add_order_note(sprintf(__('International Shipment scheduled via Gigl delivery (Waybill: %s)', 'gig-logistics-delivery'), $waybill));
		} else {
			// Log the full response so we can trace the actual waybill key
			$message = isset($response->message) ? $response->message : 'Unknown error';
			$order->add_order_note(sprintf(__('GIGL international shipment response: %s — check debug.log for full response', 'gig-logistics-delivery'), $message));
		}
	}

	/**
	 * Cancels an order in Gig Logistics Delivery when it is cancelled in WooCommerce.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id
	 */
	public function cancel_order_shipping_task($order_id)
	{
		$order = wc_get_order($order_id);
		$gigl_waybill = $order->get_meta('gig_logistics_delivery_waybill');
		$gigl_pickup_id = $order->get_meta('gig_logistics_delivery_pickup_id');
		$gig_logistics_delivery_id = $order->get_meta('gig_logistics_delivery_delivery_id');

		if ($gigl_waybill) {

			try {
				$params = [
					'job_id' => $gigl_pickup_id  // check if to cancel pickup task or delivery task
					//'job_status' => 9 // Gigl delivery job status is 9 for a cancelled task
				];
				$this->get_api()->cancel_task($params);

				$order->update_status('cancelled');

				$order->add_order_note(
					__('Order has been cancelled in Gig Logistics Delivery.', 'gig-logistics-delivery')
				);
			} catch (Exception $exception) {

				$order->add_order_note(
					sprintf(
						/* translators: %s: Error message returned from Gig Logistics API */
						esc_html__('Unable to cancel order in Gig Logistics Delivery: %s', 'gig-logistics-delivery'),
						$exception->getMessage()
					)
				);
			}
		}
	}

	/**
	 * Update order status by fetching the order details from Gig Logistics Delivery.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id
	 */
	public function update_order_shipping_status($order_id)
	{


		// $order = wc_get_order($order_id);
		// if(!empty($this->currentWaybill)){
		// 	$gigl_waybill = $this->currentWaybill;
		// }else{
		// 	$gigl_waybill = $order->get_meta('gig_logistics_waybill');
		// }
		$order = wc_get_order($order_id);
		$current_order_id = $order->get_id();
		if (!empty($this->currentWaybill)) {
			$gigl_waybill = $this->currentWaybill;
		} else {
			$gigl_waybill = get_post_meta($current_order_id, 'gig_logistics_delivery_waybill', true);
		}



		if ($gigl_waybill) {
			$response_order_detail = $this->get_api()->get_order_details($gigl_waybill);
			if (isset($response_order_detail->data[0]->MobileShipmentTrackings[0]->MobileShipmentTrackingId)) {
				// $job_delivery_status = $this->statuses[$res->data[0]->MobileShipmentTrackings[0]->Status];
				$job_delivery_status = $response_order_detail->data[0]->MobileShipmentTrackings[0]->Status;
				$tracking_id = $response_order_detail->data[0]->MobileShipmentTrackings[0]->MobileShipmentTrackingId;
				// $tracking_id = $this->statuses[$res->data[0]->MobileShipmentTrackings[0]->MobileShipmentTrackingId];

				// if ($pickup_status == 'ACCEPTED') {
				// 	$order->add_order_note("Gig Logistics Delivery: Agent $pickup_status order");
				// 	} elseif ($pickup_status == 'STARTED') {
				// 	$order->add_order_note("Gig Logistics Delivery: Agent $pickup_status order");
				// 	} elseif($job_delivery_status == 'MCRT'){
				// 	$order->add_order_note("Gig Logistics Delivery: Agent has $pickup_status destination");
				// 	}elseif ($delivery_status == 'ARRIVED') {
				// 	$order->add_order_note("Gig Logistics Delivery: Agent has $pickup_status destination");
				// 	} elseif ($delivery_status == 'ENDED') {
				// 	$order->update_status('completed', 'Gig Logistics Delivery: Order completed successfully');
				// }
				update_post_meta($order_id, 'gig_logistics_delivery_tracking_id', $tracking_id);
				update_post_meta($order_id, 'gig_logistics_delivery_status_res', $job_delivery_status);
				update_post_meta($order_id, 'gig_logistics_delivery_order_details_response', $response_order_detail);
			}
		}
	}

	/**
	 * Add tracking information to the Order page.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Order $order the order object
	 */
	public function add_view_order_tracking($order)
	{

		$order = wc_get_order($order);
		$current_order_id = $order->get_id();
		if (!empty($this->currentWaybill)) {
			$gigl_waybill = $this->currentWaybill;
		} else {
			$gigl_waybill = get_post_meta($current_order_id, 'gig_logistics_delivery_waybill', true);
		}

		// sleep(500);

		$res = $this->get_api()->track_details($gigl_waybill);

		if (!isset($res)) {

			$res = (object) [
				"data" => [
					(object) [
						"Waybill" => "Waybill not found",
						"MobileShipmentTrackings" => []
					]
				]
			];
		}
		// $response_tracking_details=json_encode($res);
		$pickup_tracking_url = get_post_meta($current_order_id, 'gig_logistics_delivery_pickup_tracking_url', true);
		$delivery_tracking_url = get_post_meta($current_order_id, 'gig_logistics_delivery_delivery_tracking_url', true);
		$gigl_state_value = get_post_meta($current_order_id, 'gigl_state_value', true);

		// reload the page to fetch the request again.
		if (isset($pickup_tracking_url) && !empty($delivery_tracking_url)) {

			?>

			<?php
		} else {
			if (!empty($gigl_state_value)) {

			} else {
				add_post_meta($current_order_id, 'gigl_state_value', 'order_id_' . $current_order_id, true);

				// $redirect_url = wp_get_referer();

				// if ($redirect_url) {
				// 	wp_safe_redirect(esc_url_raw($redirect_url));
				// 	exit;
				// }
			}
		}

		if (isset($delivery_tracking_url) && !empty($delivery_tracking_url)) {
			if (empty($gigl_waybill)) { ?>
				<p class="wc-gigl-logistics-delivery-track-deliverys"><span style="padding:20px 0px;">Refresh page if track page/button
						not visible</span>
					<button onClick="window.location.reload();">Refresh Page</button>
				</p>
				<?php
			} else {
				?>
				<!-- p class="wc-gigl-logistics-delivery-track-deliverys"> -->
				<?php
				if (
					isset($res->data) &&
					is_array($res->data) &&
					isset($res->data[0])

				) {
					?>
					<a href="#" class="button" id="myBtnTrack"
						data-track-url="<?php echo esc_attr($delivery_tracking_url . 'the' . $gigl_waybill); ?>">
						Track Delivery
					</a>
					<?php
				}
				?>
				</p>

			<?php } ?>

			<div id="myModal" class="modal">

				<!-- Modal content -->
				<div class="modal-content" id="modelTrac">
					<span class="close">&times;</span>
					<h4>>>> Tracking</h4>
					<hr>
					<ul>
						<li><strong>Waybill</strong></li>

						<li><?php
						if (
							isset($res->data) &&
							is_array($res->data) &&
							isset($res->data[0]) &&
							isset($res->data[0]->Waybill) &&
							!empty($res->data[0]->Waybill)
						) {
							echo esc_html($res->data[0]->Waybill);
						}
						?></li>
					</ul>
					<hr>
					<ul>
						<li><strong>Pickup</strong></li>
						<li><?php if (
							isset($res->data) &&
							is_array($res->data) &&
							isset($res->data[0]) &&
							isset($res->data[0]->MobileShipmentTrackings) &&
							!empty($res->data[0]->MobileShipmentTrackings)
						) {
							echo esc_html($res->data[0]->MobileShipmentTrackings[0]->PickupOptions);
						} ?></li>
					</ul>
					<hr>
					<ul>
						<li><strong>Destination</strong></li>
						<li><?php if (
							isset($res->data) &&
							is_array($res->data) &&
							isset($res->data[0]) &&
							isset($res->data[0]->MobileShipmentTrackings) &&
							!empty($res->data[0]->MobileShipmentTrackings)
						) {
							echo esc_html($res->data[0]->MobileShipmentTrackings[0]->DeliveryOption);
						} ?></li>
					</ul>
					<hr>
					<ul>
						<li><strong>Status</strong></li>
						<li><?php if (
							isset($res->data) &&
							is_array($res->data) &&
							isset($res->data[0]) &&
							isset($res->data[0]->MobileShipmentTrackings) &&
							!empty($res->data[0]->MobileShipmentTrackings)
						) {
							foreach ($res->data[0]->MobileShipmentTrackings as $MobileShipmentTrackings) {
								$getStatusVal = $MobileShipmentTrackings->Status . "<br>";
								echo wp_kses($getStatusVal, array('br' => array(), ));
							}
						} ?>
						</li>
					</ul>
				</div>

			</div>


			<?php
		}
	}


	/**
		*Remove the shipping and billing address 2 from checkout
		*
		@since 1.0.0
	*/

	public function remove_address_2_checkout_fields($fields)
	{
		unset($fields['billing']['billing_address_2']);
		unset($fields['shipping']['shipping_address_2']);

		return $fields;
	}

	/**
	 * Load Shipping method.
	 *
	 * Load the WooCommerce shipping class.
	 *
	 * @since 1.0.0
	 */
	public function load_shipping_method()
	{
		$this->shipping_method = new GIGL_Delivery_Shipping_Method;
	}

	/**
	 * Add shipping method.
	 *
	 * to the list of available shipping on cart or checkout.
	 *
	 * @since 1.0.0
	 */
	public function add_shipping_method($methods)
	{
		if (class_exists('GIGL_Delivery_Shipping_Method')):
			$methods['gig_logistics_delivery'] = 'GIGL_Delivery_Shipping_Method';
		endif;

		return $methods;
	}

	/**
	 * returns the instance of Gig Logistics Delivery API object.
	 *
	 * @since 1.0
	 *
	 * @return \GIGL_Delivery_API instance
	 */
	public function get_api()
	{
		// return API object
		if (is_object($this->api)) {
			return $this->api;
		}

		$gig_logistics_delivery_settings = $this->settings;

		// instantiate API
		return $this->api = new GIGL_Delivery_API($gig_logistics_delivery_settings);
	}
	// public function get_apiss()
	// {

	// 	// return API object
	// 	if (is_object($this->api)) {
	// 		return $this->api;
	// 	}

	// 	$gig_logistics_delivery_settings = $this->settings;

	// 	// instantiate API
	// 	return $this->api = new GIGL_Delivery_API($gig_logistics_delivery_settings);
	// }
	public function get_plugin_path()
	{
		return plugin_dir_path(__FILE__);
	}

	/**
	 * Returns Gig Logistics Delivery Instance.
	 *
	 * Loaded only one instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \GIGL_Delivery_Main
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


/**
 * Returns True Instance of WooCommerce GiglDelivery.
 *
 * @since 1.0.0
 *
 * @return \GIGL_Delivery_Main
 */
function GIGL_Delivery_Main()
{
	return \GIGL_Delivery_Main::instance();
}
