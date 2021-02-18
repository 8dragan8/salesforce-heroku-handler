<?php

$secure = true;
require_once('libs/SalesforceInboundHandler.php');
require_once('/create_user_fs.php');


$xml = file_get_contents('php://input');

$url = 'https://app1.renderator.com/php/api/update_suites_data.php';

//Initiate cURL
$curl = curl_init($url);

//Set the Content-Type to text/xml.
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));

//Set CURLOPT_POST to true to send a POST request.
curl_setopt($curl, CURLOPT_POST, true);

//Attach the XML string to the body of our request.
curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);

//Tell cURL that we want the response to be returned as
//a string instead of being dumped to the output.
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

//Execute the POST request and send our XML.
$result = curl_exec($curl);

//Do some basic error checking.
if (curl_errno($curl)) {
    throw new Exception(curl_error($curl));
}

//Close the cURL handle.
curl_close($curl);

//Print out the response output.
echo $result;
