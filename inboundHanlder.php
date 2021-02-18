<?php

$secure = true;
require_once('libs/SalesforceInboundHandler.php');
require_once('../create_user_fs.php');



$userFileSystem = new UserFileSystem();

$headers =  getallheaders();
$userFileSystem->writeErrorLog($headers);
// $userFileSystem->writeErrorLog(file_get_contents('php://input'));

$handler = new SalesforceInboundHandler(false); // default = false , true means will use sample instead posted data.
$handler->SalesforceToMysqlSyncWithApi();


$fields = array("unit__c", "status");

if ($handler->isError == false && $handler->records) {

	$json  = $handler->getJsonWithFields($fields);
	//some process here.

	if ($json) {
		$dataUpdateProcess = $userFileSystem->updateSuitesData($json, '1609941097');
		error_log($dataUpdateProcess);
	} else {
		error_log($json);
	}
	$handler->respondSuccess();
} else {

	$handler->respondBad($handler->errorMsg);
}
