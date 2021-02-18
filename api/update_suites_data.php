<?php
header("Content-Type: application/json; charset=UTF-8");

require '../create_user_fs.php';
require_once '../db_credentials.php';
include_once 'objects/user.php';

$data = new \stdClass();
$db = connectToDB();

$response = [
    "auth" => false,
    'message' => '',
    'error' => false,
    'serverData' => ''
];

$userCreator = new User($db);

if ($_POST) {
    foreach ($_POST as $key => $value) {
        $data->$key = $value;
    }
} else {

    $data = json_decode(file_get_contents("php://input"));
}

if ($api_token = getBearerToken()) {

    $token = $data->token;


    $userCreatorIsAuthorized = $userCreator->isUserOwnerOfToken($api_token, $token);

    $response['auth'] = $userCreatorIsAuthorized;

    if ($userCreatorIsAuthorized['success']) {

        $userFileSystem = new UserFileSystem();
        $getJSON = json_decode($userFileSystem->getUserBuildingSettingsJSON($token));
        $getSuitesJSON = json_decode($userFileSystem->getUserCanvasElevationJSON($token, 'north'));
        if (!$getJSON) {
            $response['error'] = true;
            $response['message'] = 'Problem fetching the saved settings';
            $response['serverData'] = $userInfo["appStatus"];
        } else {


            if (!empty($data->data)) {
                $updateData = $data->data;
                foreach ($getSuitesJSON->objects as &$v1) {
                    $apartmentName = $v1->RENDERATOR->dataEntry->appartmentName;
                    $pattern = "/\d{2,6}/i";
                    if (preg_match($pattern, $apartmentName, $matches)) {
                        $prop = $matches[0];
                        $v1->RENDERATOR->dataEntry->availability = $updateData->$prop->availability;
                    }
                }
            }
            $canvasCreated = $userFileSystem->saveUserCanvasJSON($token, 'north', json_encode($getSuitesJSON, JSON_UNESCAPED_SLASHES));

            if ($canvasCreated['error'] === true) {
                $response['error'] = true;
                $response['message'] = $canvasCreated['message'];
            } else {
                $response['data'] = array(
                    'suites' => $getSuitesJSON,
                    'updateData' => $getSuitesJSON
                );
            }
        }
    } else {
        http_response_code(401);

        $response = array(
            'auth' => $userCreatorIsAuthorized,
            "message" => "Not authorized",
            "success" => false,
        );
    }
} else {
    http_response_code(401);

    $response = array(
        "message" => "Restricted access, please send api_token",
        "success" => false,
    );
}

echo json_encode($response);
exit();


function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
/**
 * get access token from header
 * */
function getBearerToken()
{
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
