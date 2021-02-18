<?php

$secure = true;
require_once('libs/SalesforceInboundHandler.php');





$handler = new SalesforceInboundHandler(false); // default = false , true means will use sample instead posted data.
$handler->SalesforceToMysqlSyncWithApi();


$fields = array("unit__c", "status");

if ($handler->isError == false && $handler->records) {

    $json  = $handler->getJsonWithFields($fields);
    //some process here.

    error_log($json);

    $url = 'https://app1.renderator.com/php/api/inboundHanlder.php?token=1609941097';

    $xml = file_get_contents('php://input');

    $response = false;
    if ($xml) {

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
            $err = new Exception(curl_error($curl));
            $response = [
                "xml" => $xml,
                "result" => $result,
                "err" => $err
            ];
            throw $err;
        }

        //Close the cURL handle.
        curl_close($curl);
    }


    $handler->respondSuccess();
} else {

    $handler->respondBad($handler->errorMsg);
}
// header("Content-Type: application/json; charset=UTF-8");
