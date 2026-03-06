<?php
	
	defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );
	
	class GIGL_Delivery_API
	{
		protected $env;
		
		protected $login_credentials;
		
		protected $request_url;

		protected $username;

		protected $password;
		
		public function __construct($settings = array())
		{
			$this->env = isset($settings['mode']) ? $settings['mode'] : 'test';
			if ($this->env == 'Live' || $this->env == 'live') {
				$username    = isset($settings['live_username']) ? $settings['live_username'] : '';
				$password = isset($settings['live_password']) ? $settings['live_password'] : '';    
				
				
				$this->request_url = 'https://thirdpartynode.theagilitysystems.com/';//'https://thirdparty.gigl-go.com/api/thirdparty/'; //'https://mobile.gigl-go.com/api/thirdparty/';

				$this->sender_name = isset($settings['sender_name']) ? $settings['sender_name'] : '';
				$this->sender_phone_number = isset($settings['sender_phone_number']) ? $settings['sender_phone_number'] : '';
			} else {
				$username    = isset($settings['test_username']) ? $settings['test_username'] : '';
				$password = isset($settings['test_password']) ? $settings['test_password'] : '';
				
				$this->request_url = 'https://dev-thirdpartynode.theagilitysystems.com/';//'https://giglthirdpartyapitestenv.azurewebsites.net/api/thirdparty/'; //'http://test.giglogisticsse.com/api/thirdparty/';

				$this->sender_name = isset($settings['sender_name']) ? $settings['sender_name'] : '';
				$this->sender_phone_number = isset($settings['sender_phone_number']) ? $settings['sender_phone_number'] : '';
			}
			
			$this->vendor_login($username, $password);
		}
		
		/**
			* Call the Gig Logistics Delivery Login API
			*
			* @param string $username
			* @param string $password
			* @return void
		*/
		public function vendor_login($username, $password)
		{
			
		
			$login_credentials = get_transient('giglode_login_credentials');
			
			// Transient expired or doesn't exist, fetch the data
			if (empty($login_credentials)  ) {
				
					$params = array(
               'email'        => $username,//?$username:'gigtestsystems@gmail.com',
               'password'     =>$password//?$password: 'GiGL1324@!',//$password,
              
				);
				
				$response = $this->api_request(
                'login',
                $params
				);
				$login_credentials = $response;
				//set transient
				set_transient('giglode_login_credentials', $login_credentials, (HOUR_IN_SECONDS / 24)); // set transient for 5 mins to 9 mins
				
			}
			
		 	$this->login_credentials = $login_credentials;
		 }
		
		public function get_order_details($waybill)
		{
			$access_token = $this->login_credentials->data->{'access-token'};
			$params = [];
			
			return $this->api_request('track/mobileShipment?Waybill='.$waybill, $params, 'GET', $access_token);
		}
		public function create_task($params)
		{

			
			$access_token = $this->login_credentials->data->{'access-token'};
			// $params['UserId'] = $this->login_credentials->data->userId;
			// $params['CustomerCode'] = $this->login_credentials->data->name; 
			
         	// $params['ReceiverStationId'] = "1";
          	// $params['SenderStationId'] = "1";
			return $this->api_request('capture/preshipment', $params, 'POST', $access_token);
		}
		public function track_details($waybill)
		{
			$access_token = $this->login_credentials->data->{'access-token'};
			
			$params = [];
			
			return $this->api_request('track/mobileShipment?Waybill='.$waybill, $params, 'GET', $access_token);
			
		}
		public function calculate_pricing($params)
		{
			
			$access_token = $this->login_credentials->data->{'access-token'};
			if(!$access_token){
				
				$this->vendor_login($this->username, $this->password);
				$access_token = $this->login_credentials->data->{'access-token'};
			}
			
			// $params['UserId'] = $this->login_credentials->data->userId;
			$params['CustomerCode'] = $this->login_credentials->data->UserChannelCode; 
			
         	// $params['ReceiverStationId'] = "1";
          	// $params['SenderStationId'] = "1";
			

			return $this->api_request('price', $params, 'POST', $access_token);
		}
		
		public function get_lat_lng($address)
		{		
			// $coordinate['Latitude']  =  '6.61';
			// 	$coordinate['Longitude'] ='3.35';
			// return $coordinate;
			
			if (!empty($address)) {
			   $address = 'https://api.latlng.work/api?q='.urlencode($address);
			   $params = array();
			   $geocodeResponse = $this->api_request($address, $params,'GET', 'latlng_5ylwu0sjwjyuzs3d59bhbq4p9wg1nl4x');
			   if(isset($geocodeResponse->features[0]->geometry->coordinates)){
				$coordinate['Latitude']  = (!empty($geocodeResponse)) ? $geocodeResponse->features[0]->geometry->coordinates[1] : '';
				$coordinate['Longitude'] = (!empty($geocodeResponse)) ? $geocodeResponse->features[0]->geometry->coordinates[0] : '';
			   }else{
				$coordinate['Latitude']  =  '6.61';
				$coordinate['Longitude'] ='3.35';
			   }
			   	// $coordinate['Latitude']  =  '6.61';
				// $coordinate['Longitude'] ='3.35';
			}else{
				// $coordinate =  array();
				$coordinate['Latitude']  =  '6.61';
				$coordinate['Longitude'] ='3.35';
			   }
			
			// $access_token = $this->login_credentials->data->token;
			// $address = rawurlencode($address);
			// $coordinate   = get_transient('gig_logistics_delivery_addr_geocode_' . $address);
			
			
			// if (empty($coordinate['Latitude'])) {
			// 	$params = array('Address' => $address);
			// 	$geocodeResponse = $this->api_request('getaddressdetails', $params,'post',$access_token);
				
			//  	$coordinate['Latitude']  = $geocodeResponse->Object->Latitude;
			//  	$coordinate['Longitude'] = $geocodeResponse->Object->Longitude;
			
			//  	set_transient('gig_logistics_delivery_addr_geocode_' . $address, $coordinate, DAY_IN_SECONDS * 90);
			// }
			
			return $coordinate;
		}
		
		/**
			* Send HTTP Request
			* @param string $endpoint API request path
			* @param array $args API request arguments
			* @param string $method API request method
			* * @param string $token API request token
			* @return JSON decoded transaction object. NULL on API error.
		*/
		public function api_request(
        $endpoint,
        $args = array(),
        $method = 'POST', $token = NULL
		) {
			$uri = (strpos($endpoint, "https://") !== false) ? $endpoint : "{$this->request_url}{$endpoint}";
			// if(strpos($endpoint, "https://") !== false){
			
			// }
			 //$uri = "{$this->request_url}{$endpoint}";
			
				 $arg = array(
				 	'method'      => $method,
        			'timeout'     => 260,
        			'sslverify'   => false,
        			'headers'     => $this->get_headers($token, $endpoint),
        			'body'        => json_encode($args),

				 );
				 if($method == 'GET'){
					$arg = array(
					   'timeout'     => 260,
					   'sslverify'   => false,
					   'headers'     => $this->get_headers($token, $endpoint),
					);
					
					$getApiResponse = wp_remote_get( $uri, $arg );
				 }else{
					
				
					$getApiResponse = wp_remote_request( $uri, $arg );
				 }
				if (is_wp_error($getApiResponse)){
                       $bodyApiResponse = $getApiResponse->get_error_message();
                   }else{
                       $bodyApiResponse = json_decode(wp_remote_retrieve_body($getApiResponse));
                }
			
			return $bodyApiResponse;
		}
		
		/**
			* Generates the headers to pass to API request.
		*/
		public function get_headers($token, $endpoint)
		{	
			if(strpos($endpoint, "https://api.latlng.work/api") !== false){
				$getHead = array('X-Api-Key'  => $token, 'Content-Type'  => 'application/json',);
				return $getHead;
			}else if(!empty($token)){
				$getHead = array(
            'access-token' => "{$token}",
            'Content-Type'  => 'application/json',
        );
			}else{
				$getHead = array('Content-Type'  => 'application/json',);
			}

			return $getHead;
			
		}

	}
