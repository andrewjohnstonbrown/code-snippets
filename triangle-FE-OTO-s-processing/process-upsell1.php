<?php
$debug = 0;

require_once("HawkFunnel.php");
use comhawkgroupmediatriangle\HawkFunnel as funnel;

$funnel = new funnel();
$funnelInfo = funnel::setUpPath($funnelRoot);
$configOptions = $funnel->getConfigOptions();	

$redirect = $funnelInfo['funnelRoot'] . $configOptions['OTO2']['DIRNAME']; // if NO chosen on new OR old design, they go to indexb

// the links on each up-sell have a query string of accept=true/false, this
// is being used to determine their taking or refusing the up-sell
// then the parameters array is being fully set up based on JavaScript-cookies/ajax 
if (isset($_GET['accept'])) {
	if ($_GET['accept'] == "true") {		
		if (isset($_POST['Contact0Email'])) {
			$params = array(
			'username'=>$funnel->getApi()->configOptions['Settings']['USERNAME'],
			'password'=>$funnel->getApi()->configOptions['Settings']['PASSWORD'],
			'trialPackageID'=>$configOptions['OTO1']['TRIALID'],
			'chargeForTrial'=>true,
			'planID'=>$configOptions['OTO1']['PLANID'],
			'campaignID'=>$configOptions['Project']['CAMPAIGN'],
			'productID'=>$configOptions['OTO1']['PRODUCTID'],
			'paymentType'=>$_POST['CreditCard0CardType'],
			'creditCard'=>$_POST['CreditCard0CardNumber'],
			'cvv'=>$_POST['CreditCard0VerificationCode'],
			'expMonth'=>$_POST['CreditCard0ExpirationMonth'],
			'expYear'=>$_POST['CreditCard0ExpirationYear'],
			'email'=>$_POST['Contact0Email'],
			'ip'=>$_SERVER['REMOTE_ADDR'],
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
		
		// create their subscription here (since this is a recurring billing product)
		$subscription_results = $funnel->getApi()->CreateSubscription($params);
		$saleID = $subscription_results->Result->TrialCharge->SaleID;
		setcookie("OTO_SaleID", $saleID, time()+3600, "/");
		
if ($debug) {
	formCleanzer::dbgNow($_GET, "GET");
	formCleanzer::dbgNow($_POST, "POST");
	formCleanzer::dbgNow($params, "PARAMS");
	formCleanzer::dbgNow($saleID, "saleID****");
	formCleanzer::dbgNow($subscription_results, "SUBSCRIPTIONRESULTS");
	//sleep(7);
}
		
		if ($subscription_results->State == "Error") {
			formCleanzer::dbgNow($subscription_results->Info, "subscriptionResults->Info");
		}
		if ($subscription_results->State == "Success") {
				// if the charge was successful return 'success' so AJAX will re-direct them to next page
				if (!$debug)
				echo "<h1>success</h1>";
		}
		
	} // END IF GET['accept'] == true
	if ($_GET['accept'] == "false") {
		// if they don't want this product, send them to next page
			header("location: $redirect"); 
	}
}
