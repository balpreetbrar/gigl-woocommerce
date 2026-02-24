<?php
	
	defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );
	
	/**
		* Gig Logistics Delivery Shipping Method Class
		*
		* Real-time shipping rates from Gigl delivery and handle order requests
		*
		* @extends \WC_Shipping_Method
	*/
	class WC_Gig_Logistics_Delivery_Shipping_Method extends WC_Shipping_Method
	{
		/**
			* Constructor.
		*/
		public function __construct($instance_id = 0)
		{
			$getMessages = get_option("_transient_login_credentials_from_gigl_deleivery");
			
			if(!empty($getMessages)){
				if(!empty($getMessages->message)){
					$getMessage = "<span style='".(($getMessages->message == 'login successful') ? 'color:#1c4a05;' : 'color:#cb0847;')."font-weight: 800;'>".$getMessages->message."</spssssan>";
				}elseif(!empty($getMessages->success) && ($getMessages->success == true)) {
					$getMessage = "<span style='color:#1c4a05;font-weight: 800;'>Successful</span>";
				}else{
					$getMessage = "<span style='color:#cb0847;font-weight: 800;'>Invalid credentials</span>";
				}
			}else{
			 $getMessage = 'Save your details, add to cart to re-authentication your login, then verify on this dashboard to see authentication status';
			}
			$this->id                 = 'gig_logistics_delivery';
			$this->instance_id 		  = absint($instance_id);
			$this->method_title       = __('Gig Logistics Delivery');
			$this->method_description = __('Get your parcels delivered fast and cheaper via Gig Logistics Delivery'.'<br><br><br><span><b>Status</b>: '. $getMessage.'</span>');
			
			$this->supports  = array(
			'settings',
			'shipping-zones',
			);
			
			$this->init();
			
			$this->title = 'Gig Logistics Delivery';
			
			$this->enabled = $this->get_option('enabled');
		}
		
		/**
			* Init.
			*
			* Initialize Gigl delivery shipping method.
			*
			* @since 1.0.0
		*/
		public function init()
		{
			$this->init_form_fields();
			$this->init_settings();
			
			// Save settings in admin option if you have any defined
			add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
		}
		
		/**
			* Init fields.
			*
			* Add fields to the gigl delivery settings page.
			*
			* @since 1.0.0
		*/
		public function init_form_fields()
		{
			$pickup_state_code = WC()->countries->get_base_state();
			$pickup_country_code = WC()->countries->get_base_country();
			$pickup_postcode = (!empty($pickup_postcode)) ? $pickup_postcode : WC()->countries->get_base_postcode();

			$pickup_city = WC()->countries->get_base_city();
			$pickup_state_data = WC()->countries->get_states($pickup_country_code);
			$pickup_state = (isset($pickup_state_data) ? (isset($pickup_state_data[$pickup_state_code]) ? $pickup_state_data[$pickup_state_code] : '') : '');
			$pickup_base_address = WC()->countries->get_base_address();

			

			$this->form_fields = array(
			// 'login_status' => array(
			// 	'title' 	=> __('Login status'),
			// 	'type' 		=> 'text',
			// 	'default' 	=> $getMessagess,
			// ),	
			'enabled' => array(
				'title' 	=> __('Enable/Disable'),
				'type' 		=> 'checkbox',
				'label' 	=> __('Enable this shipping method'),
				'default' 	=> 'no',
			),
			'mode' => array(
				'title'       => 	__('Mode'),
				'type'        => 	'select',
				'description' => 	__('Default is (test), choose (Live) when your ready to start processing orders via  gigl delivery'),
				'default'     => 	'test',
				'options'     => 	array('test' => 'Test', 'live' => 'Live'),
				'class'		  =>	' gigl_mode'
			),
			'test_username' => array(
				'title'       => 	__('Test Username'),
				'type'        => 	'text',
				'description' => 	__('Your Sanbox Gigl delivery usernsme', 'woocommerce-gigl-logistics-delivery'),
				'class'		  =>	'gidl_test_user gigl_test',
				'default'     => 	__('')
			),
			'test_password' => array(
				'title'       => 	__('Test Password'),
				'type'        => 	'password',
				'description' => 	__('Your Sanbox account password', 'woocommerce-gigl-logistics-delivery'),
				'class'		  =>	'gidl_test_pass gigl_test',
				'default'     => 	__('')
			),
			'live_username' => array(
				'title'       => 	__('Live Username'),
				'type'        => 	'text',
				'description' => 	__('Your Live Gigl delivery usernsme', 'woocommerce-gigl-logistics-delivery'),
				'class'		  =>	'gidl_live_user gigl_live',
				'default'     => 	__('')
			),
			'live_password' => array(
				'title'       => 	__('Live Password'),
				'type'        => 	'password',
				'description' => 	__('Your Live account password', 'woocommerce-gigl-logistics-delivery'),
				'class'		  =>	'gidl_live_pass gigl_live',
				'default'     => 	__('')
			),
			'pickup_country' => array(
				'title'       => 	__('Pickup Country'),
				'type'        => 	'select',
				'description' => 	__('Gigl delivery/pickup is only available for Nigeria'),
				'default'     => 	'NG',
				'options'     => 	array("NG" => "Nigeria", "" => "Please Select"),
				'class'		  =>	'gidl_country'
			),
			
			'pickup_state' => array(
				'title'        =>	__('Pickup State'),
				'type'         =>	'select',
				'description'  =>	__('Gigl delivery/pickup state.'),
				'default'      =>	__('Lagos'),
				'options'      =>	array("abia"=>"Abia","FC"=>"Abuja Federal Capital Territory","adamawa"=>"Adamawa","AK"=>"Akwa Ibom","anambra"=>"Anambra","bauchi"=>"Bauchi","bayelsa"=>"Bayelsa","benue"=>"Benue","borno"=>"Borno","CR"=>"Cross River","delta"=>"Delta","ebonyi"=>"Ebonyi","edo"=>"Edo","ekiti"=>"Ekiti","enugu"=>"Enugu","gombe"=>"Gombe"
				,"imo"=>"Imo","jigawa"=>"Jigawa","kaduna"=>"Kaduna","kano"=>"Kano","katsina"=>"Katsina","kebbi"=>"Kebbi","kogi"=>"Kogi","kwara"=>"Kwara","lagos"=>"Lagos","nasarawa"=>"Nasarawa","niger"=>"Niger","ogun"=>"Ogun","ondo"=>"Ondo","osun"=>"Osun","oyo"=>"Oyo","plateau"=>"Plateau","rivers"=>"Rivers","sokoto"=>"Sokoto","taraba"=>"Taraba","yobe"=>"Yobe","zamfara"=>"Zamfara")
			),
			
			'pickup_city' => array(
				'title'       => 	__('Pickup City'),
				'type'        => 	'text',
				'description' => 	__('The local area where the parcel will be picked up.'),
				'default'     => 	__($pickup_city)
			),
			'pickup_postcode' => array(
				'title'       => 	__('Pickup Postcode '),
				'type'        => 	'text',
				'description' => 	__('The local postcode where the parcel will be picked up.'),
				'default'     => 	__($pickup_postcode)
			),
			'pickup_base_address' => array(
				'title'       => 	__('Pickup Address'),
				'type'        => 	'text',
				'description' => 	__('The street address where the parcel will be picked up.'),
				'default'     => 	__($pickup_base_address)
			),
			'sender_name' => array(
				'title'       => 	__('Sender Name'),
				'type'        => 	'text',
				'description' => 	__("Sender Name"),
				'default'     => 	__('')
			),
			'sender_phone_number' => array(
				'title'       => 	__('Sender Phone Number'),
				'type'        => 	'text',
				'description' => 	__('Used to coordinate pickup if the Gigl rider is outside attempting delivery. Must be a valid phone number'),
				'default'     => 	__('')
			),
			);
		}
		
		
		/**
			* Calculate shipping by sending destination/items gigl and parsing returned rates
			*
			* @since 1.0
			* @param array $package
		*/
		public function calculate_shipping($package = array())
		{
			if ($this->get_option('enabled') == 'no') {
				return;
			}
			
			// country required for all shipments
			if ($package['destination']['country'] !== 'NG') {
				
				return;
			}
			
			$delivery_country_code = $package['destination']['country'];
			$delivery_state_code = $package['destination']['state'];
			$delivery_city = $package['destination']['city'];
			$delivery_postcode = $package['destination']['postcode'];
			$delivery_base_address = $package['destination']['address'];

			$delivery_base_contents = $package['contents'];
			$delivery_base_user_id = $package['user']['ID'];
			$delivery_base_cart_subtotal = $package['cart_subtotal'];
			
			$delivery_state = WC()->countries->get_states($delivery_country_code)[$delivery_state_code];
			$delivery_country = WC()->countries->get_countries()[$delivery_country_code];
			if(empty($delivery_postcode)){
				$delivery_postcode='';
			}
			 try {
			 	//$apisss = wc_gig_logistics_delivery()->get_apiss();
			 	$api = wc_gig_logistics_delivery()->get_api();
			 	} catch (\Exception $e) {
				 wc_add_notice(__('Gig Logistics Delivery shipping method could not set up'), 'notice');
				wc_add_notice(__($e->getMessage()) . ' Please Contact Support' , 'error'); 
				
			 	return;
			 }
				
			$sender_name        = $this->get_option('sender_name');
			$sender_phone       = $this->get_option('sender_phone_number');
			$pickup_city 		= $this->get_option('pickup_city');
			$pickup_postcode 	= $this->get_option('pickup_postcode');
			$pickup_state 		= $this->get_option('pickup_state');
			$pickup_base_address = $this->get_option('pickup_base_address');
			$pickup_country 	= WC()->countries->get_countries()[$this->get_option('pickup_country')];
			if (trim($pickup_country) == '') {
					$pickup_country = 'NG';
				}

			if($delivery_base_user_id > 0){
				$author_obj = get_user_by('id', $delivery_base_user_id);
				$delivery_base_receiver_name = $author_obj->display_name;
				$delivery_base_receiver_email = $author_obj->user_email;
				$delivery_base_receiver_phone = $phone = get_user_meta($delivery_base_user_id,'phone_number',true);
			}else{
				$delivery_base_receiver_name = "Not login user";
				$delivery_base_receiver_email = "nouser@demo.com";
				$delivery_base_receiver_phone = "08030000000";
			}


			$receiver_name      = $delivery_base_receiver_name;
				$receiver_email     = $delivery_base_receiver_email;
				$receiver_phone     = $delivery_base_receiver_phone;
				
				
				$preShipmentItems = array();
				foreach( $delivery_base_contents as $item_id => $item ){

					// methods of WC_Order_Item class
					$product_id = $item["product_id"];
					$product = wc_get_product( $product_id );
					// $product->get_price();
					//$wc_product = $item->get_product();
					if ( $product ) {
						$quantity = $item['quantity'];
						$weight = $product->get_weight(); // Weight in kg (default WooCommerce unit)
						
						// Sum the weight
						$total_weight = floatval( $weight ) * intval( $quantity );
					}
						$eachProductItem = array(
											// "PreShipmentItemMobileId"=> 0,
											"Description"=> $product->get_name(), 
											// "SpecialPackageId" => "0", 
							                "Quantity" => $item['quantity'], 
							                "Weight" => ($total_weight) ? $total_weight : 1,  
											// "Weight2"=> 0,
							                // "ItemType" => "Normal", 
							                // "WeightRange" => "0", 
							                "ItemName" => $product->get_name(), 
							                "Value" => $item["line_total"],
							                "ShipmentType" => 1,
											"IsVolumetric"=> false,
											// "EstimatedPrice"=> $item['line_total'],
											// "ImageUrl"=> "",
											// "SerialNumber"=> (int) ('4536'.$item["line_total"]),
											
											// "Length"=> null,
											// "Width"=> null,
											// "Height"=> null,
											// "PreShipmentMobileId"=> 0,
											// "CalculatedPrice"=> $item['line_total'],
											// "IsCancelled"=> false,
											// "PictureName"=> "",
											// "PictureDate"=> null,
											// "WeightRange"=> "0"
							            	);

					$preShipmentItems[] = $eachProductItem;

				}
				
				
				
				// $todaydate =  date('Y-m-d H:i:s', time());
				// $pickup_date = date('Y-m-d H:i:s', strtotime($todaydate . ' +1 day'));
				// $delivery_date = date('Y-m-d H:i:s', strtotime($todaydate . ' +2 day'));
				
				
				if($delivery_postcode == '' || empty($delivery_postcode)) { 
					$delivery_address1 = (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					$delivery_address = (($delivery_base_address) ? ("$delivery_base_address,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					
					$delivery_coordinate = $api->get_lat_lng($delivery_address);
					
					// if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					// 	$delivery_coordinate = $api->get_lat_lng($delivery_address1);
					// }
					
					// if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					// 	$delivery_coordinate = $api->get_lat_lng("$delivery_state, $delivery_country");
					// }
					
					$delivery_address1 = (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address = trim("$pickup_base_address $pickup_city, $pickup_state, $pickup_country");
					$pickup_coordinate = $api->get_lat_lng($pickup_address);
					
					// if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					// 	$pickup_coordinate = $api->get_lat_lng($delivery_address1);
					// }
					
				
				}else {
				
					$delivery_address = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					//$delivery_address = trim("$delivery_address");
					$delivery_address1 = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_base_address) ? ("$delivery_base_address,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					$delivery_address2 = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					$delivery_address3 = (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					//$delivery_addressd = trim("$delivery_base_address $delivery_city, $delivery_state, $delivery_country,$delivery_postcode");
					$delivery_coordinate = $api->get_lat_lng($delivery_address);
					
					// if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					// 	$delivery_coordinate = $api->get_lat_lng("$delivery_address");
					// }
					// if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					// 	$delivery_coordinate = $api->get_lat_lng("$delivery_address1");
					// }
					// if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					// 	$delivery_coordinate = $api->get_lat_lng("$delivery_address2");
					// }
					// if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
					// 	$delivery_coordinate = $api->get_lat_lng("$delivery_address3");
					// }
					
					$pickup_address = (($pickup_postcode) ? ("$pickup_postcode,") : '') . (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address1 = (($pickup_postcode) ? ("$pickup_postcode,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address2 = (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address = trim("$pickup_address");
					
					$pickup_coordinate = $api->get_lat_lng($pickup_address);
					
					// if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					// 	$pickup_coordinate = $api->get_lat_lng("$pickup_address");
					// }
					// if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					// 	$pickup_coordinate = $api->get_lat_lng("$pickup_address1");
					// }
					// if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
					// 	$pickup_coordinate = $api->get_lat_lng("$pickup_address2");
					// }
					
				}
				$receiverLocation = array(
										"Latitude" => $delivery_coordinate['Latitude'],
										"Longitude" => $delivery_coordinate['Longitude'],
										// "FormattedAddress" => "",
										// "Name" => "",
										// "LGA" => ""
										);

				$senderLocation = array(
										"Latitude" => $pickup_coordinate['Latitude'],
										"Longitude" => $pickup_coordinate['Longitude'],
										// "FormattedAddress" => "",
										// "Name" => "",
										// "LGA" => ""
										);
				





					$params = array(
								// "PreShipmentMobileId"=> 0,
								 "SenderStationId"=> 1,
  								 "ReceiverStationId"=> 1,
								 "VehicleType" => 1, //1 for bike, 0 for car, 2 for van, 3 for truck
								 "ReceiverLocation" => $receiverLocation,
								 "SenderLocation" => $senderLocation,
								 "CustomerType"=> 0, 
								 "PickUpOptions"=> 1,

								// "ReceiverAddress" => $delivery_address,  
								// "SenderLocality" => $pickup_city,
								// "InputtedSenderAddress"=>$pickup_address,
								// "SenderAddress" => $pickup_address, 
								// "ReceiverPhoneNumber" => $receiver_phone, 
								// "InputtedReceiverAddress"=>$delivery_address,
								
								// "SenderPhoneNumber" => $sender_phone, 
								// "SenderName" => $sender_name,
								// "ReceiverName" => $receiver_name, 
								
								
								"ShipmentItems" => $preShipmentItems,
								// "IsBatchPickUp"=> false,
								// "WaybillImage"=> "",
								// "WaybillImageFormat"=> "",
								// "DestinationServiceCenterId"=> 0,
								// "DestinationServiceCentreId"=> 0,
								// "IsCashOnDelivery"=> false,
								// "CashOnDeliveryAmount"=> 0.00
		    					);
				
			 try {
			 	$res = $api->calculate_pricing($params);
				
			 	} catch (\Exception $e) {
					wc_add_notice(__('Gig Logistics Delivery pricing calculation could not complete'), 'notice');
				wc_add_notice(__($e->getMessage()), 'error');  
				
				return;
			 }
			
			 $data = $res;
			//$verifyValue = json_encode($data);
			 $handling_fee = 0;
			 $cost = isset($data->data->DeliverPrice) ? (wc_format_decimal($data->data->DeliverPrice)) : wc_format_decimal($handling_fee);
			if(isset($preShipmentItems)){
				$total_no_of_tasks = ($preShipmentItems !== null) ? count($preShipmentItems) : 0;
			}else{
				$total_no_of_tasks = 0;
			}
		
			$this->add_rate(array(
			'id'    	=> $this->id . $this->instance_id,
			'label' 	=> $this->title,
			'cost'  	=> $cost,
			'meta_data' => array(
			'per_task_cost'		   => $data->data->DeliverPrice,
			'insurance_amount'     =>  isset($data->data->InsuranceValue) ? $data->data->InsuranceValue : 0,
			'total_no_of_tasks'    => $total_no_of_tasks,
			'total_service_charge' => isset($data->data->vat) ? $data->data->vat : 0,
			)
			));	
		}
	}