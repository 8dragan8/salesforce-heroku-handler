<?php
// header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../db_credentials.php';
include_once 'objects/user.php';
$db = connectToDB();
$data = new \stdClass();
$authorised = false;
$user = new User($db);

if ($_POST) {
    foreach ($_POST as $key => $value) {
        $data->$key = $value;
    }
} else {

    $data = json_decode(file_get_contents("php://input"));
}

if ($data->email == '' || !isset($data->email)) {
    http_response_code(401);

    echo json_encode(array(
        "message" => "Login failed.",
    ));
} else {

    $user->email =  $data->email;
    $email_exists = $user->emailExists();

    if ($user->is_social) {
        $authorised = true;
    } else {
        $authorised = password_verify($data->password, $user->password);;
    }

    if ($email_exists['success'] && $authorised) {

        $token = $user->createToken();
        $result = [
            "login" => true,
            "token" => $token,
            "username" => $user->username,
            "role_id" => $user->role_id,
            "email" => $user->email,
            "lastname" => $user->lastname,
            "country" => $user->country,
            "city" => $user->city,
            "address" => $user->address,
            "phone_number" => $user->phone_number,
            "user_id" => $user->user_id,
            "is_social" => $user->is_social,
        ];
        // set response code
        http_response_code(200);

        echo json_encode(array(
            "success" => true,
            "message" => "Successful login.",
            "data" => $result
        ));
    } else {
        $result = [
            "login" => false,
            "token" => "",
            "user" => $user->email,
            "is_social" => $user->is_social,
            "email_exists" => $email_exists['success'],
            "passVerified" => $passVerified,

        ];
        // set response code
        http_response_code(200);

        // tell the user login failed
        echo json_encode(array(
            "success" => false,
            "message" => "Login failed.",
            "data" => $result
        ));
    }
}
