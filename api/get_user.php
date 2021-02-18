<?php
// header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$result = [
    "auth" => false
];

require_once '../db_credentials.php';
require_once '../database_private_api.php';
include_once 'objects/user.php';

$databaseApi = new DatabaseAPI();

$db = connectToDB();

$token = null;
$headers = apache_request_headers();
if (isset($headers['Authorization'])) {
    $matches = array();
    preg_match('/Bearer (.*)/', $headers['Authorization'], $matches);
    if (isset($matches[1])) {
        $token = $matches[1];
    }
}

$email = $_GET['email'];

$userCreator = new User($db);

$userCreatorIsAuthorised = $userCreator->parseFromToken($token);

$result['auth'] = $userCreatorIsAuthorised;
// $result['token'] = $token;
// $result['matches'] = $matches;
$result['headers'] = $headers;

if ($userCreatorIsAuthorised['success']) {
    http_response_code(200);

    if ($email == '' || !isset($email)) {
        // http_response_code(401);


        $result = array(
            'auth' => $result['auth'],
            "message" => "No email",
            "success" => false,
            'data' => array(
                "getData" => $_GET,
                "email" => $email,
            ),
        );
    } else {
        $user = $databaseApi->emailExists($email);

        if ($user == false) {
            $result = array(
                'auth' => $result['auth'],
                "message" => "No user with this mail",
                "success" => false,
                'data' => array(
                    "email" => $email,
                )
            );
        } else {
            $result = array(
                'auth' => $result['auth'],
                "message" => "User found",
                "success" => true,
                'data' => array(
                    "user" => $user,
                )
            );
        }
    }
} else {
    http_response_code(401);

    $result = array(
        'auth' => $result['auth'],
        "message" => "Not authorised",
        "success" => false,
    );
}
$result['headers'] = $headers;

echo json_encode($result);
exit();
