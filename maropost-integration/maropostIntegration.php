<?php

class MP {
	
	/** 
	* the $auth_token_{} will need to be updated with {"auth_token_goes_here"}
	* for each account the account ID and list ID is received from AJAX request
	*/
	protected $auth_token_ABA 	= "";
	protected $auth_token_MT = "";
	protected $authorization	= null;
	protected $url_api = null;
	
	public function __construct( $acc ) {

		switch ( $acc ) {

			case "ABA":
				$this->url_api = "http://api.maropost.com/accounts/296/"; // 296 is the ABA maropost account
				$this->authorization = ".json?auth_token=" . $this->auth_token_ABA;
				break;
			case "MT":
				$this->url_api = "http://api.maropost.com/accounts/200/"; // 200 is the MT maropost account
				$this->authorization = ".json?auth_token=" . $this->auth_token_MT;
				break;
			default:
				$this->url_api = "http://api.maropost.com/accounts/296/"; // 296 is the ABA maropost account
				$this->authorization = ".json?auth_token=" . $this->auth_token_SL;

		} // end switch

	} // end constructor	
	
	public function request( $action, $endpoint, $dataArray ) {
		
		$this->url_api .= $endpoint . $this->authorization; 
	  	$ch = curl_init();
		
		$json = json_encode( $dataArray );
	    
		//var_dump($json);
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	    curl_setopt( $ch, CURLOPT_URL, $this->url_api );
	    
		switch($action){
			
	        case "POST":
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	            break;
	        case "GET":
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	            break;
	        case "PUT":
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	            break;
	        case "DELETE":
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	            break;
	        default:
	            break;
	    }
		
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json','Accept: application/json'));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		
		$output = curl_exec($ch);
	    curl_close($ch);
	    
		$decoded = json_decode($output);
	    return $decoded;
		
	} // end METHOD
	
	public function __toString() {
		$info = 'URL:  ' . $this->url_api;
		return $info;
	}
	
} // end CLASS

if (isset($_POST['email'])) {
	
	$first_name = $_POST['first_name'];
	$last_name = $_POST['last_name'];
	$email = $_POST['email'];
	$phone = $_POST['phone'];
	$fax = $_POST['fax'];
	
	$account = $_POST['account'];
	$list = $_POST['list'];
	
	//echo $account;
	
	$datum = array();
	
	$datum['custom_field'] = array(
			'custom_field_1' => true,
			'custom_field_2' => null,
			'custom_field_3' => "abc123"
		);
		
	$datum['contact'] = array(
		"first_name" => $first_name,
		"email" => $email,
		"phone" => $phone,
		"fax" => $fax,
		"last_name" => $last_name
	);
	
	$datum['subscribe'] = true;
	
} // end IF 

$mp = new MP($account);

$newcontact = $mp->request('POST', 'lists/' . $list . '/contacts', $datum);

//echo $mp;

var_dump($newcontact);
