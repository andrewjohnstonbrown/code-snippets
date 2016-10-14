<?php
$debug = 0;

require_once("HawkFunnel.php");
use comhawkgroupmediatriangle\HawkFunnel as funnel;
$funnelInfo = funnel::setUpPath($funnelRoot);

//echo strpos($_SERVER['HTTP_REFERER'], "indexb");

if ( strpos($_SERVER['HTTP_REFERER'], "indexb.php") === false ) { // on original
	$redirect = $funnelInfo['ty'];
	//echo "on version A";
}
else { // on version B
	$redirect = $funnelInfo['ty']. "indexb.php";
	//echo "on version B";
}

$funnel = new funnel();
$configOptions = $funnel->getConfigOptions();

// the links on each up-sell have a query string of accept=true/false, this
// is being used to determine their taking or refusing the up-sell
// then the parameters array is being fully set up based on JavaScript-cookies/ajax
if (isset($_GET['accept'])) {
	if ($_GET['accept'] == "true") {
		if (isset($_POST['Contact0Email'])) {
			$params = array(
			'username'=>$funnel->getApi()->configOptions['Settings']['USERNAME'],
			'password'=>$funnel->getApi()->configOptions['Settings']['PASSWORD'],
			'productTypeID'=>$configOptions['Project']['ID'],
			'productID'=>$configOptions['OTO2']['PRODUCTID'],
			'campaignID'=>$configOptions['Project']['CAMPAIGN'],
			'amount'=>$configOptions['OTO2']['AMOUNT'],
			'shipping'=>$configOptions['OTO2']['SHIPPING'],
			'paymentType'=>$_POST['CreditCard0CardType'],
			'creditCard'=>$_POST['CreditCard0CardNumber'],
			'cvv'=>$_POST['CreditCard0VerificationCode'],
			'expMonth'=>$_POST['CreditCard0ExpirationMonth'],
			'expYear'=>$_POST['CreditCard0ExpirationYear'],
			'email'=>$_POST['Contact0Email'],
			'sendConfirmationEmail'=>true,
			'firstName'=>$_POST['Contact0FirstName'],
			'lastName'=>$_POST['Contact0LastName'],
			'phone'=>$_POST['Contact0Phone1'],
			'address1'=>$_POST['Contact0StreetAddress1'],
			'address2'=>$_POST['Contact0StreetAddress2'],
			'city'=>$_POST['Contact0City'],
			'state'=>$_POST['Shipping_State_US'],
			'zip'=>$_POST['Contact0PostalCode'],
			'affiliate'=>$_POST['AffiliateID'],
			'subAffiliate'=>$_POST['subAffiliateID']
			);
		}
		
		if ($debug) {
			formCleanzer::dbgNow($_GET, "GET");
			formCleanzer::dbgNow($_POST, "POST");
			formCleanzer::dbgNow($params, "PARAMS");
			formCleanzer::dbgNow($charge_upsell_results, "charge_upsell_results");	
		}
		
		$charge_upsell_results = $funnel->getApi()->Charge($params);
		$saleID = $charge_upsell_results->Result->SaleID;
		setcookie("OTOs_SaleID", $saleID, time()+3600, "/");
		// charge them for the upsell
		
		if ($charge_upsell_results->State == "Error") {
			formCleanzer::dbgNow($charge_upsell_results->Info, "charge_upsell_results->Info");
		}
		if ($charge_upsell_results->State == "Success") {
				// if the charge was successful return 'success' so AJAX will re-direct them
				if (!$debug)
				echo "success";
		}
		
	} // END IF $_GET['accept'] == true
	if ($_GET['accept'] == "false") {
			header("location: $redirect");
			// if they don't want this product, send them to next page 
		}
}
