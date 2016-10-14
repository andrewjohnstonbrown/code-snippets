<?php


class NC_Limelight {

	var $api_base =  "";
	var $batch_url =  "";

	public function __construct ( $api_base){
		$this->$api_base = $api_base;
	}

	public function NewBatchOrder($customer_data, $funnel_data) {

		$authorize_amount = 50;
		$form_processor = $this->api_base . "/transact.php";

		$args = array_merge($funnel_data, $customer_data);
		$args['auth_amount'] = $authorize_amount ;
		$args['save_customer'] = 1;
		$args['method'] = 'authorize_payment';
		$args['tranType'] = '';
		$args['site'] = $this->api_base . '/';

		//OVERRIDE DOESNT WORK THO WE CAN SUPPLY FAKE CVV
		//$args['CVV'] = '000';

		$args = array_filter($args);

		$response = $this->sendQuery($args, $form_processor);

		$args['creditCardType'] = '';
		$args['creditCardNumber'] = '';
		$args['expirationDate'] = '';
		$args['CVV'] = '';
		$args['shippingMethod'] = '';
		$args['username'] = '';
		$args['password'] = '';
		$args['method'] = '';
		$args['save_customer'] = '';
		$args['auth_key'] = '';

		parse_str($response, $response);
		$args = array_merge($args, $response);
		$args = array_filter($args);

		$batch_run = $this->sendQuery($args, $this->batch_url . "send-order");
		$batch_run = json_decode($batch_run, true);

		if ( $response['responseCode'] == '100' && $batch_run['status'] == 'true' ) {
			$result = array(
				'errorFound' => '',
				'message' => $batch_run['message'],
				'batch_id' => $batch_run['batch_id'],
				);
			unset($args);
			return $result;
		} else {
			$result = array(
				'errorFound' => 'true',
				'message' => $response['errorMessage'] ? $response['errorMessage'] : "Error Processing Order",
				'batch_id' => $batch_run['batch_id'],
				);

			unset($args);
			return $result;
		}
	}
	public function NewBatchUpsell($args) {

		$args['username'] = '';
		$args['password'] = '';
		$args['method'] = '';
		$args['campaignId'] = '';
		$args['shippingId'] = '';
		$args['previousOrderId'] = '';
		$args['batch_id'] = $_POST['batch_id'];
		$args['email'] = $_POST['email'];
		$args['auth_key'] = '';

		$args = array_filter($args);

		$batch_update = $this->sendQuery($args, $this->batch_url . "send-update");
		$batch_update = json_decode($batch_update, true);


		if ( isset($batch_update['status']) && $batch_update['status'] == 'true' ) {
			$data = array(  'status' => "success",
				'message' => 'Success',
				'url_query' => '?batch_id=' . $args['batch_id'] . '&email=' . $args['email'],
				);

			unset($args);
			return $data;

		} else {
			$data = array(  'status' => "failure",
				'message' => 'Failure',
				'url_query' => '?batch_id=' . $args['batch_id'] . '&email=' . $args['email'],
				);

			unset($args);
			return $data;

		}

	}

	private function sendQuery($query, $target_url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
		$response = curl_exec($ch);
		curl_close ($ch);

		return $response;
	}


	public function NewOrder($customer_data, $funnel_data) {

		$args = array_merge($customer_data, $funnel_data);

		if ($_POST['Method_type'] == "webform") {

			$form_processor = "";
			$uArgs = array();

			/**
			*	The processor for LL Web Forms expects
			*	POST to have certain key names
			*	so here we create an array using those
			*	expected key names
			*/
			foreach ($args as $key => $value) {

				//var_dump($key);
				//var_dump($value);

				if ( $key === "productId" ) {
					$uArgs['custom_product'] = $value;
				}
				elseif ( stripos($key, "billingFirstName") !== false ) {
					$uArgs['fields_fname'] = $value;
				}
				elseif ( stripos($key, "billingLastName") !== false ) {
					$uArgs['fields_lname'] = $value;
				}
				elseif ( stripos($key, "email") !== false ) {
					$uArgs['fields_email'] = $value;
				}
				elseif ( stripos($key, "phone") !== false ) {
					$uArgs['fields_phone'] = $value;
				}
				elseif ( stripos($key, "billingAddress1") !== false ) {
					$uArgs['fields_address1'] = $value;
				}
				elseif ( stripos($key, "billingAddress2") !== false ) {
					$uArgs['fields_address2'] = $value;
				}
				elseif ( stripos($key, "billingZip") !== false ) {
					$uArgs['fields_zip'] = $value;
				}
				elseif ( stripos($key, "billingCity") !== false ) {
					$uArgs['fields_city'] = $value;
				}
				elseif ( stripos($key, "billingState") !== false ) {
					$uArgs['fields_state'] = $value;
				}
				elseif ( stripos($key, "creditCardType") !== false ) {
					$uArgs['cc_type'] = $value;
				}
				elseif ( stripos($key, "creditCardNumber") !== false ) {
					$uArgs['cc_number'] = $value;
				}
				elseif ( stripos($key, "expirationDate") !== false ) {
					$uArgs['cc_expires'] = $value;
				}
				elseif ( stripos($key, "campaignId") !== false ) {
					$uArgs['campaign_id'] = $value;
				}
				elseif ( stripos($key, "shippingId") !== false ) {
					$uArgs['shipping'] = $value;
				}
				// end if else of mapping

			}
			// end foreach in customer_data

			$uArgs['cc_cvv'] = $_POST['cc-ccv2']; //"OVERRIDE";
			$uArgs['fields_expmonth'] = substr($uArgs['cc_expires'], 0, 2);
			$uArgs['fields_expyear'] = substr($uArgs['cc_expires'], -2);
			$uArgs['fields_state_hid'] = $uArgs['fields_state'];
			$uArgs['is_upsell'] = $_POST['is_upsell'];
			$uArgs['limelight_charset'] = "ISO-8859-1";
			$uArgs['step'] = "first";
			$uArgs['product_name'] = $_POST['product_name'];
			$uArgs['data_provider_previous_values'] = $_POST['data_provider_previous_values'];
			$uArgs['data_verification_provider_id'] = $_POST['data_verification_provider_id'];
			$uArgs['AFID'] = $_POST['AFID'];
			$uArgs['SID'] = $_POST['SID'];

			if ( isset($_POST['upsell_id']) && $_POST['upsell_id'] != 0 ) {

				$uArgs["upsell_custom_price[{$_POST['upsell_id']}]"] = $_POST["upsell_custom_price"];
				$uArgs["upsell_{$_POST['upsell_id']}"] = 'on';

			}

			//var_dump($uArgs);
			//die();

			$query = http_build_query($uArgs);

			/**
			*	return the properly formatted
			*	URL with all the params
			*	and some meta-data
			*/
			return [
			"post_url" => "{$form_processor}&{$query}",
			"destination_url" => $form_processor,
			"query_string" => $uArgs,
			"type" => "webform"
			];

		}
		else {

			$form_processor = $this->api_base . "/transact.php";
			$query = http_build_query($args);

			// create context
			$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
					'content' => $query,
					),
				));

			// send request and collect data
			$response = file_get_contents(
				$target = $form_processor,
				$use_include_path = false,
				$context);

			return $response;

		}
		// end if is webform or not

	}
	// end NewOrder function

	public function NewUpsell($funnel_data){

		/**
		*	if it's webform, then the result will
		*	be an array of goodies for use in JS
		*
		*	we need to form the new query string
		*	and return it to the JS
		*/
		if ($_POST['Method_type'] == "webform") {

			$form_processor = "";
			$uArgs = array();

			$uArgs['act_up'] = $funnel_data['act_up'];
			$uArgs['limelight_charset'] = "ISO-8859-1";
			$uArgs['product_id'] = $_POST['product-id'];
			$uArgs['step'] = $_POST['step'];
			$uArgs['step_upsell'] = $_POST['step_upsell'];
			$uArgs["upsell_custom_price[{$_POST['product-id']}]"] = $_POST["upsell_custom_price"]["{$_POST['product-id']}"];
			$uArgs['temp_order'] = $_POST['temp_order'];
			$uArgs['AFID'] = $_POST['AFID'];
			$uArgs['SID'] = $_POST['SID'];

			$query = http_build_query($uArgs);

			/**
			*	return the properly formatted
			*	URL with all the params
			*	and some meta-data
			*/
			return [
			"post_url" => "{$form_processor}&{$query}",
			"destination_url" => $form_processor,
			"query_string" => $uArgs,
			"type" => "webform"
			];


		}
		else{

			$query = http_build_query($funnel_data);

			// create context
			$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
					'content' => $query,
					),
				));

			$form_processor = $this->api_base . "/transact.php";

			// send request and collect data
			$response = file_get_contents(
				$target = $form_processor,
				$use_include_path = false,
				$context);

			return $response;

		}
		// end if is webform


	}
	// end method new upsell


}
// end class
