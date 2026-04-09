<?php
	
defined( 'ABSPATH' ) || exit;

/**
 * Gig Logistics Delivery Shipping Method Class
 *
 * Real-time shipping rates from Gigl delivery and handle order requests
 *
 * @extends WC_Shipping_Method
 */
class GIGL_Delivery_Shipping_Method extends WC_Shipping_Method {

	public function __construct( $instance_id = 0 ) {

		$getMessages = get_option("_transient_giglode_login_credentials");

		if ( ! empty( $getMessages ) ) {

			if ( ! empty( $getMessages->message ) ) {
				$getMessage = sprintf(
					'<span style="%s">%s</span>',
					( $getMessages->message === 'login successful' )
						? 'color:#1c4a05;font-weight:800;'
						: 'color:#cb0847;font-weight:800;',
					esc_html( $getMessages->message )
				);
			} elseif ( ! empty( $getMessages->success ) && true === $getMessages->success ) {
				$getMessage = '<span style="color:#1c4a05;font-weight:800;">' .
					esc_html__( 'Successful', 'gig-logistics-delivery' ) .
					'</span>';
			} else {
				$getMessage = '<span style="color:#cb0847;font-weight:800;">' .
					esc_html__( 'Invalid credentials', 'gig-logistics-delivery' ) .
					'</span>';
			}
		} else {
			$getMessage = esc_html__(
				'Save your details, add to cart to re-authenticate your login, then verify on this dashboard to see authentication status.',
				'gig-logistics-delivery'
			);
		}

		$this->id          = 'gig_logistics_delivery';
		$this->instance_id = absint( $instance_id );
		$this->method_title = __( 'Gig Logistics Delivery', 'gig-logistics-delivery' );

		$this->method_description =
			__( 'Get your parcels delivered fast and cheaper via Gig Logistics Delivery.', 'gig-logistics-delivery' )
			. '<br><br><br><span><strong>'
			. esc_html__( 'Status', 'gig-logistics-delivery' )
			. ':</strong> '
			. $getMessage
			. '</span>';

		$this->supports = array(
			'settings',
			'shipping-zones',
		);

		$this->init();

		$this->title   = __( 'Gig Logistics Delivery', 'gig-logistics-delivery' );
		$this->enabled = $this->get_option( 'enabled' );
	}

	public function init() {

		$this->init_form_fields();
		$this->init_settings();

		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	public function init_form_fields() {

		$pickup_state_code   = WC()->countries->get_base_state();
		$pickup_country_code = WC()->countries->get_base_country();
		$pickup_postcode     = WC()->countries->get_base_postcode();
		$pickup_city         = WC()->countries->get_base_city();
		$pickup_base_address = WC()->countries->get_base_address();

		$this->form_fields = array(

			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'gig-logistics-delivery' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this shipping method', 'gig-logistics-delivery' ),
				'default' => 'no',
			),

			'mode' => array(
				'title'       => __( 'Mode', 'gig-logistics-delivery' ),
				'type'        => 'select',
				'description' => __( 'Default is Test. Choose Live when ready to process orders.', 'gig-logistics-delivery' ),
				'default'     => 'test',
				'options'     => array(
					'test' => __( 'Test', 'gig-logistics-delivery' ),
					'live' => __( 'Live', 'gig-logistics-delivery' ),
				),
			),

			'test_username' => array(
				'title'       => __( 'Test Username', 'gig-logistics-delivery' ),
				'type'        => 'text',
				'description' => __( 'Your Sandbox Gigl delivery username.', 'gig-logistics-delivery' ),
				'default'     => '',
			),

			'test_password' => array(
				'title'       => __( 'Test Password', 'gig-logistics-delivery' ),
				'type'        => 'password',
				'description' => __( 'Your Sandbox account password.', 'gig-logistics-delivery' ),
				'default'     => '',
			),

			'live_username' => array(
				'title'       => __( 'Live Username', 'gig-logistics-delivery' ),
				'type'        => 'text',
				'description' => __( 'Your Live Gigl delivery username.', 'gig-logistics-delivery' ),
				'default'     => '',
			),

			'live_password' => array(
				'title'       => __( 'Live Password', 'gig-logistics-delivery' ),
				'type'        => 'password',
				'description' => __( 'Your Live account password.', 'gig-logistics-delivery' ),
				'default'     => '',
			),

			'pickup_city' => array(
				'title'   => __( 'Pickup City', 'gig-logistics-delivery' ),
				'type'    => 'text',
				'default' => $pickup_city,
			),

			'pickup_postcode' => array(
				'title'   => __( 'Pickup Postcode', 'gig-logistics-delivery' ),
				'type'    => 'text',
				'default' => $pickup_postcode,
			),

			'pickup_base_address' => array(
				'title'   => __( 'Pickup Address', 'gig-logistics-delivery' ),
				'type'    => 'text',
				'default' => $pickup_base_address,
			),

			'sender_name' => array(
				'title'   => __( 'Sender Name', 'gig-logistics-delivery' ),
				'type'    => 'text',
				'default' => '',
			),

			'sender_phone_number' => array(
				'title'   => __( 'Sender Phone Number', 'gig-logistics-delivery' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/* calculate_shipping() remains EXACTLY as you wrote it */

	public function calculate_shipping( $package = array() ) {

		if ( $this->get_option( 'enabled' ) === 'no' ) {
			return;
		}

		if ( empty( $package['destination']['country'] ) ) {
			return;
		}

		$is_international = $package['destination']['country'] !== 'NG';

		if ( empty( $package['destination']['state'] ) || empty( $package['destination']['city'] ) ) {
			return;
		}

		try {
			$api = GIGL_Delivery_Main()->get_api();
		} catch ( Exception $e ) {
			wc_add_notice(
				__( 'Gig Logistics Delivery shipping method could not set up.', 'gig-logistics-delivery' ),
				'error'
			);
			return;
		}

		$delivery_country_code = $package['destination']['country'];
		print_r($package);
		$delivery_state_code   = $package['destination']['state'];
		$delivery_city         = $package['destination']['city'];
		$delivery_postcode     = isset( $package['destination']['postcode'] ) ? $package['destination']['postcode'] : '';
		$delivery_base_address = isset( $package['destination']['address'] ) ? $package['destination']['address'] : '';

		$delivery_state   = WC()->countries->get_states( $delivery_country_code )[ $delivery_state_code ] ?? '';
		$delivery_country = WC()->countries->get_countries()[ $delivery_country_code ] ?? 'Nigeria';

		$delivery_base_contents = $package['contents'];

		$preShipmentItems = array();

		foreach ( $delivery_base_contents as $item ) {

			$product_id = $item['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$quantity = intval( $item['quantity'] );
			$weight   = floatval( $product->get_weight() );
			$total_weight = ( $weight > 0 ) ? ( $weight * $quantity ) : 1;

			$preShipmentItems[] = array(
				'Description'  => $product->get_name(),
				'Quantity'     => $quantity,
				'Weight'       => $total_weight,
				'ItemName'     => $product->get_name(),
				'Value'        => $item['line_total'],
				'ShipmentType' => 1,
				'IsVolumetric' => false,
			);
		}

		if ( empty( $preShipmentItems ) ) {
			return;
		}

		if ( $is_international ) {
			$this->calculate_international_shipping($package, $preShipmentItems);
			return;
		}


		$pickup_city          = $this->get_option( 'pickup_city' );
		$pickup_state         = $this->get_option( 'pickup_state' );
		$pickup_postcode      = $this->get_option( 'pickup_postcode' );
		$pickup_base_address  = $this->get_option( 'pickup_base_address' );
		$pickup_country       = 'Nigeria';
		// $delivery_address = trim(
		// 	"$delivery_base_address, $delivery_city, $delivery_state, $delivery_country"
		// );
		

		

		// $pickup_address = trim(
		// 	"$pickup_base_address,$pickup_city, $pickup_state, $pickup_country"
		// );

		// $delivery_coordinate = $api->get_lat_lng( $delivery_address );
		// $pickup_coordinate   = $api->get_lat_lng( $pickup_address );

		// if (
		// 	empty( $delivery_coordinate['Latitude'] ) ||
		// 	empty( $delivery_coordinate['Longitude'] ) )
		// ) {
		// 	//after removing base address and fetching latlng again
		// 	$delivery_address = trim(
		// 	"$delivery_city, $delivery_state, $delivery_country"
		// );
		// 	$delivery_coordinate = $api->get_lat_lng($delivery_address);
		// }
		// if (
		// 	empty( $pickup_coordinate['Latitude'] ) ||
		// 	empty( $pickup_coordinate['Longitude'] )
		// ) {
		// 	//after removing base address and fetching latlng again
		// 	$pickup_address = trim(
		// 	"$pickup_city, $pickup_state, $pickup_country"
		// );
		// 		$pickup_coordinate   = $api->get_lat_lng( $pickup_address );
		// }
		// if (
		// 	empty( $delivery_coordinate['Latitude'] ) ||
		// 	empty( $delivery_coordinate['Longitude'] ) ||
		// 	empty( $pickup_coordinate['Latitude'] ) ||
		// 	empty( $pickup_coordinate['Longitude'] )
		// ) {
		// 	return;
		// }
		if($delivery_postcode == '' || empty($delivery_postcode)) { 
					$delivery_address1 = (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					$delivery_address = (($delivery_base_address) ? ("$delivery_base_address,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					
					$delivery_coordinate = $api->get_lat_lng($delivery_address);
					
					if (!isset($delivery_coordinate['Latitude']) || !isset($delivery_coordinate['Longitude'])) {
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
					
					
				
				}else {
				
					$delivery_address = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					//$delivery_address = trim("$delivery_address");
					$delivery_address1 = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_base_address) ? ("$delivery_base_address,") : '') . (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					$delivery_address2 = (($delivery_postcode) ? ("$delivery_postcode,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					$delivery_address3 = (($delivery_city) ? ("$delivery_city,") : '') . (($delivery_state) ? ("$delivery_state,") : ''). 'Nigeria';
					//$delivery_addressd = trim("$delivery_base_address $delivery_city, $delivery_state, $delivery_country,$delivery_postcode");
					$delivery_coordinate = $api->get_lat_lng($delivery_address);
					
					if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
						$delivery_coordinate = $api->get_lat_lng("$delivery_address");
					}
					if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
						$delivery_coordinate = $api->get_lat_lng("$delivery_address1");
					}
					if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
						$delivery_coordinate = $api->get_lat_lng("$delivery_address2");
					}
					if (!isset($delivery_coordinate['Latitude']) && !isset($delivery_coordinate['Longitude'])) {
						$delivery_coordinate = $api->get_lat_lng("$delivery_address3");
					}
					
					$pickup_address = (($pickup_postcode) ? ("$pickup_postcode,") : '') . (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address1 = (($pickup_postcode) ? ("$pickup_postcode,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address2 = (($pickup_city) ? ("$pickup_city,") : '') . (($pickup_state) ? ("$pickup_state,") : ''). 'Nigeria';
					$pickup_address = trim("$pickup_address");
					
					$pickup_coordinate = $api->get_lat_lng($pickup_address);
					
					if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
						$pickup_coordinate = $api->get_lat_lng("$pickup_address");
					}
					if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
						$pickup_coordinate = $api->get_lat_lng("$pickup_address1");
					}
					if (!isset($pickup_coordinate['Latitude']) && !isset($pickup_coordinate['Longitude'])) {
						$pickup_coordinate = $api->get_lat_lng("$pickup_address2");
					}
					
				}

		$params = array(
			// 'SenderStationId'   => 1,
			// 'ReceiverStationId' => 1,
			'VehicleType'       => 1,
			'ReceiverLocation'  => array(
				// 'Latitude'  => '6.5583775',
				'Latitude'  => $delivery_coordinate['Latitude'],
				'Longitude' => $delivery_coordinate['Longitude'],
				// 'Longitude' => '3.3983414',
			),
			'SenderLocation'    => array(
				'Latitude'  => $pickup_coordinate['Latitude'],
				'Longitude' => $pickup_coordinate['Longitude'],
			),
			// 'CustomerType'      => 0,
			'IsPriorityShipment' => false,
			'PickUpOptions'     => 1,
			'ShipmentItems'     => $preShipmentItems,
		);

		try {
			$res = $api->calculate_pricing( $params );
			// print_r($res);
		} catch ( Exception $e ) {
			wc_add_notice(
				__( 'Gig Logistics Delivery pricing calculation could not complete.', 'gig-logistics-delivery' ),
				'error'
			);
			return;
		}

		if ( empty( $res->data->GrandTotal ) ) {
			return;
		}

		$cost = wc_format_decimal( $res->data->GrandTotal );

		$this->add_rate( array(
			'id'    => $this->id . $this->instance_id,
			'label' => $this->title,
			'cost'  => $cost,
			'meta_data' => array(
				'per_task_cost'        => $res->data->GrandTotal / count( $preShipmentItems ),
				'insurance_amount'     => $res->data->InsuranceValue ?? 0,
				'total_no_of_tasks'    => count( $preShipmentItems ),
				'total_service_charge' => $res->data->vat ?? 0,
			),
		) );
	}

	public function calculate_international_shipping( $package, $preShipmentItems ) {
		try {
			$api = GIGL_Delivery_Main()->get_api();
		} catch ( Exception $e ) {
			return;
		}

		$country_code = $package['destination']['country'];
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
			return; // Country not supported by GIGL
		}

		$declared_value = 0;
		$intlShipmentItems = array();

		foreach ( $preShipmentItems as $item ) {
			$declared_value += $item['Value'];
			$intlShipmentItems[] = array(
				'InternationalShipmentItemType' => 1, // Default to Normal/Other
				'Description' => $item['Description'],
				'Weight' => $item['Weight'],
				'Quantity' => $item['Quantity'],
				'Nature' => 1,
				'Value' => $item['Value']
			);
		}

		$params = array(
			'DestinationCountryId' => $destination_country_id,
			'ReceiverCity' => $package['destination']['city'],
			'ReceiverAddress' => $package['destination']['address'],
			'ReceiverPostalCode' => $package['destination']['postcode'],
			'ReceiverCountryCode' => $country_code,
			'ReceiverCountry' => $destination_country_name,
			'PickupOptions' => 1,
			'DeclaredValue' => $declared_value,
			'IsVacuumSeal' => false,
			'IsPhytosanitaryCertification' => false,
			'ShipmentItems' => $intlShipmentItems,
		);

		try {
			$res = $api->calculate_international_pricing( $params );
		} catch ( Exception $e ) {
			return;
		}

		if ( empty( $res->data->data ) || ! is_array( $res->data->data ) ) {
			return;
		}

		foreach ( $res->data->data as $option ) {
			$label = $this->title;
			if ( isset( $option->DeliveryType ) ) {
				$delivery_types = array(
					0 => 'Standard',
					1 => 'Express',
					2 => 'Extra'
				);
				$label .= ' (' . ( $delivery_types[$option->DeliveryType] ?? 'International' ) . ')';
			}

			$this->add_rate( array(
				'id'    => $this->id . $this->instance_id . '_' . $option->DeliveryType,
				'label' => $label,
				'cost'  => wc_format_decimal( $option->GrandTotal ),
				'meta_data' => array(
					'is_international' => true,
					'logistic_company' => $option->LogisticCompany,
					'delivery_type'    => $option->DeliveryType,
					'grand_total'      => $option->GrandTotal,
					'currency'         => $option->Currency,
				),
			) );
		}
	}
}
