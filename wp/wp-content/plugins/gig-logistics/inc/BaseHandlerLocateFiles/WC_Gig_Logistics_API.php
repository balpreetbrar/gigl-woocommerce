<?php
	
	defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );
	
	class WC_Gig_Logistics_API
	{
		protected $env;
		
		protected $login_credentials;
		
		protected $request_url;

		protected $username;

		protected $password;
		
		public function __construct($settings = array())
		{
			$this->env = isset($settings['mode']) ? $settings['mode'] : 'test';
			
			if ($this->env == 'Live') {
				$username    = isset($settings['live_username']) ? $settings['live_username'] : '';
				$password = isset($settings['live_password']) ? $settings['live_password'] : '';    
				
				
				$this->request_url = 'https://prod-agilitythirdpartyapi.theagilitysystems.com/api/thirdParty/';//'https://thirdparty.gigl-go.com/api/thirdparty/'; //'https://mobile.gigl-go.com/api/thirdparty/';

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
			* Call the Gigl Delivery Login API
			*
			* @param string $username
			* @param string $password
			* @return void
		*/
		public function vendor_login($username, $password)
		{
			
			$login_credentials = get_transient('login_credentials_from_gig_logistics');
			
			// Transient expired or doesn't exist, fetch the data
			if (empty($login_credentials)  ) {
				
				$params = array(
               'email'        => $username?$username:'gigtestsystems@gmail.com',
               'password'     =>$password?$password: 'GiGL1324@!',//$password,
              
				);
				//print_r('still load api'); die();
				$response = $this->api_request(
                'login',
                $params
				);
				$login_credentials = $response;
				// print_r($response); die();
				//set transient
				set_transient('login_credentials_from_gig_logistics', $login_credentials, (HOUR_IN_SECONDS / 24)); // set transient for 5 mins to 9 mins
				
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
			// print_r($params); 
			// print_r('access token checked'); 
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
			
			if (!empty($address)) {
				
				
			   $address = 'https://nominatim.openstreetmap.org/search?q='.urlencode($address).'&format=json&limit=1';
			   $params = array();
			   $geocodeResponse = $this->api_request($address, $params,'GET');
				
			   if(isset($geocodeResponse[0]->lat)){
				$coordinate['Latitude']  = (!empty($geocodeResponse)) ? $geocodeResponse[0]->lat : '';
				$coordinate['Longitude'] = (!empty($geocodeResponse)) ? $geocodeResponse[0]->lon : '';
			   }else{
				$coordinate['Latitude']  =  '6.61';
				$coordinate['Longitude'] ='3.35';
			   }
			}else{
				$coordinate =  array();
			   }
			
			// $access_token = $this->login_credentials->data->{'access-token'};
			// $address = rawurlencode($address);
			// $coordinate   = get_transient('gig_logistics_addr_geocode_' . $address);
			
			// //print_r($coordinate); die();
			// if (empty($coordinate['Latitude'])) {
			// 	$params = array('Address' => $address);
			// 	$geocodeResponse = $this->api_request('getaddressdetails', $params,'post',$access_token);
				
			//  	$coordinate['Latitude']  = $geocodeResponse->Object->Latitude;
			//  	$coordinate['Longitude'] = $geocodeResponse->Object->Longitude;
			
			//  	set_transient('gig_logistics_addr_geocode_' . $address, $coordinate, DAY_IN_SECONDS * 90);
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
			// 	print($endpoint);die();
			// }
			 //$uri = "{$this->request_url}{$endpoint}";
				
				 $arg = array(
				 	'method'      => $method,
        			'timeout'     => 260,
        			'sslverify'   => false,
        			'headers'     => $this->get_headers($token),
        			'body'        => json_encode($args),

				 );
				 if($method == 'GET'){
					$arg = array(
					   'timeout'     => 260,
					   'sslverify'   => false,
					   'headers'     => $this->get_headers($token),
					);
					
					$getApiResponse = wp_remote_get( $uri, $arg );
				 }else{
				// 	if($endpoint=='price'){
				// 	print_r($args); print_r($uri);die();
				//  }
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
			public function get_headers($token)
		{
			if(!empty($token)){
				$getHead = array(
            'access-token' => "{$token}",
            'Content-Type'  => 'application/json',
        );
			}else{
				$getHead = array('Content-Type'  => 'application/json',);
			}
				print_r($getHead);
			return $getHead;
			
		}

	}
