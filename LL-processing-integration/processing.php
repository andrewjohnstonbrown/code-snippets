<?php

session_start();
header('Content-Type: application/json');
include_once(dirname(__FILE__) . '/api_config.php');
include_once(dirname(__FILE__) . '/lib/limelight.php');
require_once(dirname(__FILE__) . '/lib/marolob.php');

$method = $_POST['process']; //checkout, upsell, lob, maropost

try {

	switch($method) {
		case 'checkout':

			// BEGIN lob validation
			$marolob = new MaroLob();

			if ( isset($_POST['shipping-shiptodiffadd']) && $_POST['shipping-shiptodiffadd'] == 1 ) {
				$_POST['action'] = 'lob';
				$_POST['checkout'] = 'TRUE';
				$_POST['address_line1'] = $_POST['shipping-address'];
				$_POST['address_line2'] = $_POST['shipping-address2'];
				$_POST['address_city'] = $_POST['shipping-city'];
				$_POST['address_state'] = $_POST['shipping-state'];
				$_POST['address_zip'] = $_POST['shipping-postalcode'];
				$_POST['address_country'] = $_POST['shipping-country'];
				//$lob_result = $marolob->CheckAction($_POST['action']);
			}
			else {
				$_POST['action'] = 'lob';
				$_POST['checkout'] = 'TRUE';
				$_POST['address_line1'] = $_POST['billing-address'];
				$_POST['address_line2'] = $_POST['billing-address2'];
				$_POST['address_city'] = $_POST['billing-city'];
				$_POST['address_state'] = $_POST['billing-state'];
				$_POST['address_zip'] = $_POST['billing-postalcode'];
				$_POST['address_country'] = $_POST['billing-country'];
				//$lob_result = $marolob->CheckAction($_POST['action']);
			}

			$lob_result = $marolob->checkLob($_POST['checkout'], $_POST['address_line1'], $_POST['address_line2'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country']);
			//var_dump($lob_result);

			if ( $lob_result === FALSE  ) {
				echo json_encode(array(
					"status"  => "error",
					"error" => "address validation failed",
					"message" => "address validation failed" . $lob_result['message']
					));
				die();

			}
			if ( strpos($lob_result, "error" ) !== false ) {
				echo json_encode(array(
					"status"  => "error",
					"error" => "invalid address, please try again",
					"message" => "invalid address, please try again"
					));
				die();
			}
			// END lob validation


			$limelight = new NC_Limelight(_API_BASE_);

			if ($_POST['cc-number'] == "" ){
				$_POST['cc-number'] = "";
			}

			if ( isset($_POST['shipping-shiptodiffadd']) && $_POST['shipping-shiptodiffadd'] == 1 ) {

				$customer_data = array(  'firstName' => $_POST['billing-firstname'],
					'lastName' => $_POST['billing-lastname'],
					'shippingMethod' => $_POST['ship_type'],
					'shippingAddress1' => $_POST['shipping-address'],
					'shippingAddress2' => $_POST['shipping-address2'],
					'shippingCity' => $_POST['shipping-city'],
					'shippingState' => $_POST['shipping-state'],
					'shippingZip' => $_POST['shipping-postalcode'],
					'shippingCountry' => $_POST['shipping-country'],
					'billingSameAsShipping' => 'NO',
					'billingFirstName' =>$_POST['billing-firstname'],
					'billingLastName' => $_POST['billing-lastname'],
					'billingAddress1' => $_POST['billing-address'],
					'billingAddress2' => $_POST['billing-address2'],
					'billingCity' => $_POST['billing-city'],
					'billingState' => $_POST['billing-state'],
					'billingZip' => $_POST['billing-postalcode'],
					'billingCountry' => $_POST['billing-country'],
					'phone' => $_POST['billing-phone'],
					'email' => $_POST['billing-email'],
					'creditCardType' => $_POST['cc-type'],
					'creditCardNumber' => $_POST['cc-number'],
					'expirationDate' => $_POST['cc-expmonth'] . substr($_POST['cc-expyear'], -2),
					'CVV' => $_POST['cc-ccv2'], //"OVERRIDE",
					'ipAddress' => $_POST['ip-address']);
			} else {
				$customer_data = array(  'firstName' => $_POST['billing-firstname'],
					'lastName' => $_POST['billing-lastname'],
					'shippingMethod' => $_POST['ship_type'],
					'shippingAddress1' => $_POST['billing-address'],
					'shippingAddress2' => $_POST['billing-address2'],
					'shippingCity' => $_POST['billing-city'],
					'shippingState' => $_POST['billing-state'],
					'shippingZip' => $_POST['billing-postalcode'],
					'shippingCountry' => $_POST['billing-country'],
					'billingSameAsShipping' => 'YES',
					'billingFirstName' =>$_POST['billing-firstname'],
					'billingLastName' => $_POST['billing-lastname'],
					'billingAddress1' => $_POST['billing-address'],
					'billingAddress2' => $_POST['billing-address2'],
					'billingCity' => $_POST['billing-city'],
					'billingState' => $_POST['billing-state'],
					'billingZip' => $_POST['billing-postalcode'],
					'billingCountry' => $_POST['billing-country'],
					'phone' => $_POST['billing-phone'],
					'email' => $_POST['billing-email'],
					'creditCardType' => $_POST['cc-type'],
					'creditCardNumber' => $_POST['cc-number'],
					'expirationDate' => $_POST['cc-expmonth'] . substr($_POST['cc-expyear'], -2),
					'CVV' => $_POST['cc-ccv2'], //"OVERRIDE",
					'ipAddress' => $_POST['ip-address']);
			}


			$funnel_data = array(
				'username' => _USER_,
				'password' => _PASS_,
				'method' => _METHOD_,
								//'upsellCount' => _UPSELLCOUNT_,
								//'upsellProductIds' => _UPSELLPRODUCTIDS_,
				'productId' => $_POST['product-id'],
				'product_qty_'. $_POST['product-id'] => isset($_POST['qty-fe']) ? $_POST['qty-fe'] : 1,
				'campaignId' => $_POST['c-id'],
				'shippingId' => $_POST['s-id'],

				'tranType' => 'Sale', //Sale, AuthVoid, Capture
				//for subscription
				'force_subscription_cycle' => '', //recurring_days, subscription_week, subscription_day
				'recurring_days' => '', //1-31
				'subscription_week' => '', // 1-5
				'subscription_day' => '',  // 1-7
				'temp_customer_id' => '', //for existing customers

				/**
				*	add any affiliate related query strings to the arguments
				*	so the API can process them
				*/
				'AFID' => isset($_POST['AFID']) ? $_POST['AFID'] : '', // affiliate
				'SID' => isset($_POST['SID']) ? $_POST['SID'] : '' // sub-affiliate
				);


		if ( isset($_POST['upsell_id']) && $_POST['upsell_id'] != 0 ) {
			$funnel_data['upsellCount'] = 1;
			$funnel_data['upsellProductIds'] = $_POST['upsell_id'];
		}


			/**
			*	if it's webform, then the result will
			*	be an array of goodies for use in JS
			*/
			if ( $_POST['Method_type'] == "webform" ) {
				$result = $limelight->NewOrder($customer_data, $funnel_data);
				echo json_encode($result);

			} else if ( $_POST['Method_type'] == "batch" ) {

				$next_url = trim($_POST['next_url_a']) ;

				$result = $limelight->NewBatchOrder($customer_data, $funnel_data);

				$data = array(  'status' => $result['errorFound'] ? "error" : "success",
					'message' => urldecode($result['message']),
					'url' => $next_url . '?batch_id=' . $result['batch_id'] . '&email=' . $_POST['billing-email'] );

				// rs only on TY , ty is where we process the orders
				$_SESSION['rs_fe_order_id'] = '';

				echo json_encode($data);

			} else {
				$result = $limelight->NewOrder($customer_data, $funnel_data);

				parse_str($result, $result);

				// decide on oto1a or oto1b
				$next_url = trim($_POST['next_url_a']) ;

				$data = array(  'status' => $result['errorFound'] ? "error" : "success",
					'message' => urldecode($result['errorMessage']),
					'url' => $next_url . '?previousOrderId=' . $result['orderId']);

				// added this for RETENTION SCIENCE
				$_SESSION['rs_fe_order_id'] = $result['orderId'];
				setcookie("rs_fe_order_id", $result['orderId'], time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
				$_SESSION['rs_email'] = $_POST['billing-email'];
				setcookie("rs_email", $_POST['billing-email'], time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
				$_SESSION['rs_order_total'] = $_POST['_pc'];
				setcookie("rs_order_total", $_POST['_pc'], time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);

				echo json_encode($data);

			}
			// end if else is webform

			break;

		case 'upsell': //needs to be added on upsell request

			// decide on oto1a or oto1b
		$next_url = trim($_POST['next_url_a']) ;
		$limelight = new NC_Limelight(_API_BASE_);

		$funnel_data = array('username' => _USER_,
			'password' => _PASS_,
			'method' => _METHOD_UPSELL_,
				'previousOrderId' => $_POST['previousOrderId'], //needs to be added on upsell request
				'productId' => $_POST['product-id'],
				'product_qty_'. $_POST['product-id'] => $_POST['qty-upsell'],
				'campaignId' => $_POST['c-id'],
				'shippingId' => 3); //$_POST['s-id']

			/**
			*	if WEBFORM and said YES
			*/
			if ( $_POST['Method_type'] == "webform" && $_POST["button-name"] == "ButtonYes" ) {
				$funnel_data['act_up'] = "yes";

				// set member to true if they bought FPA
				if ( $_POST['product-id'] == 112 ){
					$_SESSION["member"] = true;
					setcookie("OTOtaken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
				}
				if ( $_POST['offer-step'] == 'OTO2' ){
					setcookie("OTO2taken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
				}
				if ( $_POST['offer-step'] == 'OTO3' ){
					setcookie("OTO3taken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
				}
			}
			/**
			*	if WEBFORM and said NO
			*/
			elseif ( $_POST['Method_type'] == "webform" && $_POST["button-name"] == "ButtonYes" ) {
				$funnel_data['act_up'] = "no";
			}

			/**
			*	if WEBFORM, submit differently
			*/
			if ( $_POST['Method_type'] == "webform" ) {

				$result = $limelight->NewUpsell($funnel_data);
				echo json_encode($result);
				die();

			}

			if ( $_POST['Method_type'] == "batch" ) {

				if ( $_POST["button-name"] == "ButtonNo" ) {
					if ( isset($_POST['downsell_url']) && !empty($_POST['downsell_url']) ) {
						$next_url = trim($_POST['downsell_url']) ;
					}
					$data = array(
						'status' => "success",
						'message' => "No Charge, did not take upsell.",
						'url' => $next_url . '?batch_id=' . $_POST['batch_id']. '&email=' . $_POST['email']
						);
					echo json_encode($data);
					die();

				} else {
					$result = $limelight->NewBatchUpsell($funnel_data);

					//var_dump($result);
					$data = array(  'status' => $result['status'],
						'message' => $result['message'],
						'url' => $next_url . $result['url_query']
						);
					echo json_encode($data);
					die();
				}
			}




			if ( $_POST["button-name"] == "ButtonNo" ) {

				//change the next_url if there is downsell url
				if ( isset($_POST['downsell_url']) && !empty($_POST['downsell_url']) ) {
					$next_url = trim($_POST['downsell_url']) ;
				}

				$data = array(
					'status' => "success",
					'message' => "No Charge, did not take upsell.",
					'url' => $next_url . '?previousOrderId=' . $_POST['previousOrderId']
					);
				echo json_encode($data);
				die();
			}



			parse_str($limelight->NewUpsell($funnel_data), $result);

			$next_url = trim($_POST['next_url_a']) ;

			$error_Message = urldecode($result['errorMessage']);
			if ( $error_Message != 'The credit card number or email address has already purchased this product(s)'  ) {
				$data = array(  'status' =>  $result['errorFound'] ? "error" : "success",
				'message' => urldecode($result['errorMessage']),
				'url' => $next_url . '?previousOrderId=' . $result['orderId']);
			}
			else{
				$data = array(  'status' => "success",
				'message' => urldecode($result['errorMessage']),
				'url' => $next_url . '?previousOrderId=' . $_POST['previousOrderId']);
			}


			// set member to true if they bought FPA
			if ( $_POST['product-id'] == 112 ){
				$_SESSION["member"] = true;
				// set 0T0taken only if step = 1
				if ( $_POST['offer-step'] != 'OTO2' && $_POST['offer-step'] != 'OTO3' ){
					setcookie("OTOtaken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
				}
			}
			// for aquastiq flow condition
			if ( $_POST['product-id'] == 153 && $_POST['offer-step'] != 'OTO2' && $_POST['offer-step'] != 'OTO3'){
				setcookie("OTOtaken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
			}

			// for blackout bag flow condition
			if ( $_POST['product-id'] == 118 && $_POST['offer-step'] != 'OTO2' && $_POST['offer-step'] != 'OTO3'){
				setcookie("OTOtaken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
			}
			if ( $_POST['offer-step'] == 'OTO2' ){
				setcookie("OTO2taken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
			}
			if ( $_POST['offer-step'] == 'OTO3' ){
				setcookie("OTO3taken", 'YES', time()+3600, "/", $_SERVER['HTTP_HOST'], false, false);
			}

			echo json_encode($data);

		break;

		default:
			echo "Unknown Error. Press back and Try again later or Contact Suppport.";
		break;

	}

}
catch ( Exception $e ) {
	echo $e->getMessage();
}
