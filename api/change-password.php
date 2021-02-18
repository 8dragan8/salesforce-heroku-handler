<?php
// header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


require_once '../db_credentials.php';
include_once 'objects/user.php';
$data = new \stdClass();
$db = connectToDB();

$currentUser = new User($db);
if ($_POST) {
    foreach ($_POST as $key => $value) {
        $data->$key = $value;
    }
} else {
    
    $data = json_decode(file_get_contents("php://input"));
}

$tokenData = base64_decode($data->token);
$tokenData = json_decode($tokenData);

$currentUser->email = $tokenData->email;
$userExist = $currentUser->emailExists();
if ($userExist['success']) {
    if (!isset($data->old_password) || empty(($data->old_password))) {
        $result = array("status" => 400, "message" => "old_password field is required.", "data" => array());
    } else if (!isset($data->new_password) || empty(($data->new_password))) {
        $result = array("status" => 400, "message" => "new_password field is required.", "data" => array());
    } else {
        $result = $currentUser->changePassword($data->new_password);
    }
} else {
    $result = array("status" => 400, "message" => "No such user.", "data" => array());
}


echo json_encode($result);
exit();
