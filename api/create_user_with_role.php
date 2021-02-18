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
include_once 'objects/user.php';
$data = new \stdClass();
$db = connectToDB();

$userCreator = new User($db);
if ($_POST) {
    foreach ($_POST as $key => $value) {
        $data->$key = $value;
    }
} else {

    $data = json_decode(file_get_contents("php://input"));
}

$userCreatorIsAuthorised = $userCreator->parseFromToken($data->token);

$result['auth'] = $userCreatorIsAuthorised;

if ($userCreatorIsAuthorised['success']) {

    $newUser = new User($db);
    if ($data->email == '' || !isset($data->email)) {
        http_response_code(401);

        $result['result'] = array(
            "message" => "No email",
            "error" => true,
            "success" => false,
            'data' => array(
                "postData" => $data
            ),
        );
    } else {
        $newUser->email = $data->email;
        $email_exists = $newUser->emailExists();

        if ($email_exists['success']) {
            http_response_code(400);

            // $result['result'] = $email_exists;
            if ($newUser->is_social == 1) {
                $result['result'] = array(
                    "success" => true,
                    "is_social" => $newUser->is_social,
                    "message" => "This email is already registered with one of the social platform",
                    "data" => array(
                        "email" => $newUser->email,
                        "userID" => $newUser->user_id,
                    ),
                    "error" => true
                );
            } else {

                $result['result'] = array(
                    "message" => "User already exists.",
                    "is_social" => $newUser->is_social,
                    "error" => true,
                    "success" => true,
                    'data' => array(
                        "email" => $newUser->email,
                        "userID" => $newUser->user_id,
                    ),
                );
            }
        } else {

            $newUser->username = $data->username;
            $newUser->role_id = $data->role_id;
            $newUser->lastname = $data->lastname;
            $newUser->country = $data->country;
            $newUser->city = $data->city;
            $newUser->address = $data->address;
            $newUser->phone_number = $data->phone_number;
            $newUser->password = $data->password;
            $newUser->social_auth_name = $data->social_auth_name;
            $newUser->is_social = $data->is_social;

            // $isAuthenticated = tokenCheck($data->token);
            if (
                !empty($newUser->username) &&
                !empty($newUser->email) &&
                // !empty($newUser->password) &&
                !empty($newUser->country) &&
                !empty($newUser->role_id) &&
                $newUser->create()
            ) {
                // set response code
                $token = $newUser->createToken();
                http_response_code(200);
                $result['result'] = array(
                    "message" => "New user created successfully.",
                    "error" => false,
                    "success" => true,
                    'data' => array(
                        "user_id" => $newUser->user_id,
                        "username" => $newUser->username,
                        "token" => $token,
                        "role_id" => $newUser->role_id,
                        "email" => $newUser->email,
                        "lastname" => $newUser->lastname,
                        "country" => $newUser->country,
                        "city" => $newUser->city,
                        "address" => $newUser->address,
                        "phone_number" => $newUser->phone_number,
                        "is_social" => $newUser->is_social,
                        "social_auth_name" => $newUser->social_auth_name,
                    ),
                );
            } else {
                // set response code
                http_response_code(400);
                $result['result'] = array(
                    "message" => "Error creating new user.",
                    "error" => true,
                    "success" => false,
                    'data' => array(
                        "user" => $newUser,
                    ),
                );
            }
        }
    }
}

echo json_encode($result);
exit();
