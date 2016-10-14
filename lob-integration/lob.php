<?php

/* test data
$args = array('address_line1' => '220 William T Morrissey Boulevard',
	'address_city' => 'Boston',
	'address_state' => 'MA',
	'address_zip' => '021125');
*/
$args = array('address_line1' => $_POST['address_line1'],
	'address_line2' => $_POST['address_line2'],
	'address_city' => $_POST['address_city'],
	'address_state' => $_POST['address_state'],
	'address_zip' => $_POST['address_zip'],
	'address_country' => '');

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, "");
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$output = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
print_r($output);

?>
