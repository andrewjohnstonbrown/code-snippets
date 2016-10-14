<?php 
$debug = 0;

require_once("HawkFunnel.php");
use comhawkgroupmediatriangle\HawkFunnel as funnel;

$funnel = new funnel();
$funnelInfo = funnel::setUpPath($funnelRoot);
$configOptions = $funnel->getConfigOptions();
$redirect = $funnelInfo['funnelRoot'] . $configOptions['OTO1']['DIRNAME'] . $funnelInfo['qString'];
//$redirect = $funnelInfo['ty'] . $funnelInfo['qString'];

$checkIt = array();

foreach ( $_POST as $k => $v ) {
    $checkIt[$k] = filter_var($v, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}


date_default_timezone_set('America/Chicago');
$cur_time =  date("D M d, Y g:i:s a");

$formQsm = intval($checkIt['quantity_sm']);
$formQm = intval($checkIt['quantity_m']);
$formQl = intval($checkIt['quantity_l']);
$formQxl = intval($checkIt['quantity_xl']);
$formQxxl = intval($checkIt['quantity_xxl']);
$formQxxxl = intval($checkIt['quantity_xxxl']);
$formQtotal = intval($checkIt['quantity_total']);
setcookie("totalPurchase", $formQtotal, time()+3600, "/");

if(!is_null($formQsm)) {
	$qtys = array("S"=>$formQsm, "M"=>$formQm, "L"=>$formQl, "XL"=>$formQxl, "XXL"=>$formQxxl, "XXXL"=>$formQxxxl);
}
formCleanzer::dbgNow($qtys, "qtys");

$diffShipping = false;
// set up associative array for use in the create prospect method
$params = array(
	'username'=>$funnel->getApi()->configOptions['Settings']['USERNAME'],
	'password'=>$funnel->getApi()->configOptions['Settings']['PASSWORD'],
	'productTypeID'=>$configOptions['Project']['ID'], //	this is PROJECT ID
	'firstName'=>$checkIt['Contact0FirstName'],
	'lastName'=>$checkIt['Contact0LastName'],
	'address1'=>$checkIt['Contact0StreetAddress1'],
	'address2'=>$checkIt['Contact0StreetAddress2'],
	'city'=>$checkIt['Contact0City'],
	'state'=>$checkIt['Shipping_State_US'],
	'zip'=>$checkIt['Contact0PostalCode'],
	'phone'=>$checkIt['Contact0Phone1'],
	'email'=>$checkIt['Contact0Email'],
	'ip'=>$_SERVER['REMOTE_ADDR'],
	'affiliate'=>$checkIt['Affiliate_ID'],
	'subAffiliate'=>$checkIt['SubAffiliate_ID'],

);

if ( !empty($checkIt['billing-street-address']) ) {		// user filled out the different shipping address

	$diffShipping = true;
	$params['address1'] = $checkIt['billing-street-address'];
	$params['address2'] = $checkIt['billing-optional-address'];
	$params['city'] = $checkIt['billing-city'];
	$params['state'] = $checkIt['billing-state'];
	$params['zip'] = $checkIt['billing-zip'];

}
else {		// user filled it out normally

	$params['address1'] = $checkIt['Contact0StreetAddress1'];
	$params['address2'] = $checkIt['Contact0StreetAddress2'];
	$params['city'] = $checkIt['Contact0City'];
	$params['state'] = $checkIt['Shipping_State_US'];
	$params['zip'] = $checkIt['Contact0PostalCode'];

} // end if

$result = $funnel->getApi()->CreateProspect($params);
$prospectID = $result->Result->ProspectID;

// if create prospect method was successful, add to the associative array in preparation for the Charge method
if ($result->State == "Success") {

	if ( $diffShipping === true ) {		// override the address fields to pass into Charge() **will go in as Billing Address!**

		$params['address1'] = $checkIt['Contact0StreetAddress1'];
		$params['address2'] = $checkIt['Contact0StreetAddress2'];
		$params['city'] = $checkIt['Contact0City'];
		$params['state'] = $checkIt['Shipping_State_US'];
		$params['zip'] = $checkIt['Contact0PostalCode'];

	}
	
	
	$params['amount'] = $configOptions['Front']['AMOUNT'];
	$params['shipping'] = $checkIt['PayTotal_A'];
	$params['campaignID'] = $configOptions['Project']['CAMPAIGN'];
	$params['productID'] = $configOptions['Front']['PRODUCTID'];
	$params['creditCard'] = $checkIt['CreditCard0CardNumber'];
	$params['paymentType'] = $checkIt['CreditCard0CardType'];
	$params['cvv'] = $checkIt['CreditCard0VerificationCode'];
	$params['expMonth'] = $checkIt['CreditCard0ExpirationMonth'];
	$params['expYear'] = $checkIt['CreditCard0ExpirationYear'];
	$params['sendConfirmationEmail'] = true;
	$params['prospectID'] = $prospectID;
}
else {
	formCleanzer::dbgNow($result->Info, "result->Info");
}

// Charge the credit card using the parameters and capture the results
$returnObject = $funnel->getApi()->Charge($params);
$saleID = $returnObject->Result->SaleID;
setcookie("SaleID", $saleID, time()+3600, "/");
if ($debug) {
	formCleanzer::dbgNow($_POST, "POST");
	formCleanzer::dbgNow($checkIt, "CHECKIT");
	formCleanzer::dbgNow($params, "PARAMS");
	formCleanzer::dbgNow($prospectID, "PROSPECTID");
	formCleanzer::dbgNow($returnObject, "RETURNOBJECT");
	formCleanzer::dbgNow($cur_time, "current Time");
	sleep(7);
}

if ($returnObject->State == "Error") {
	formCleanzer::dbgNow($returnObject->Info, "returnObject->Info");
}
if ($returnObject->State == "Success") {
	
	$fileName = "results";
	$fName = $fileName . ".csv";
	
	if (!file_exists($fName)) {
		$cols = array("TimeStamp","Order ID" , "Shipping Name", "Shipping Address 1",
		"Shipping Address 2", "Shipping City", "Shipping State", 
		"Shipping Zipcode", "Shipping Country", "Shipping Phone", 
		"Shipping Email", "Item SKU", "Size", "Quantity");
	
		funnel::createCSV($fileName, $cols);
	}
	
	$name = $params['firstName'] . " " . $params['lastName'];
				
	if (!is_null($qtys)) {
		foreach($qtys as $k=>$v) {
			if ($v > 0) {
				$vars = array($cur_time, $saleID, $name, $params['address1'], $params['address2'],
				$params['city'], $params['state'], $params['zip'], 
				'United States', $params['phone'], $params['email'], 
				$configOptions['Front']['SKU'], $k, $v);
				funnel::addToCSV($fileName, $vars);
			}
		}
	}
	/*
	$name = $params['firstName'] . " " . $params['lastName'];
	$vars = array($saleID, $name, $params['address1'], $params['address2'],
		$params['city'], $params['state'], $params['zip'], 
		'United States', $params['phone'], $params['email'], 
		42239102, $checkIt['size'], 1);
		
	funnel::addToCSV($fileName, $vars);
	*/
	
	if (!$debug)
		header("location: $redirect");
}
