<?php
// 'user' object
class User
{

    // database connection and table name
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $password;
    public $role_id;
    public $email;
    public $lastname;
    public $country;
    public $city;
    public $address;
    public $phone_number;
    public $social_auth_name;
    public $is_social;

    // constructor
    public function __construct($db)
    {
        $this->conn = $db;
    }

    function create()
    {
        try {
            $this->conn->beginTransaction();
            // insert query

            $insertUser = " INSERT IGNORE INTO " . $this->table_name;
            $insertUser .= "(
                                `username`, 
                                `password`,
                                `role_id`,
                                `email`,
                                `lastname`,
                                `country`,
                                `city`,
                                `address`,
                                `phone_number`,
                                `social_auth_name`,
                                `is_social`
                                ) 
                                VALUES (
                                :username,
                                :password,
                                :role_id,
                                :email,
                                :lastname,
                                :country,
                                :city,
                                :address,
                                :phone_number,
                                :social_auth_name,
                                :is_social
                                )";

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            // prepare the query
            $stmt = $this->conn->prepare($insertUser);

            // sanitize
            // $this->username = htmlspecialchars(strip_tags($this->username));
            // $this->password = htmlspecialchars(strip_tags($this->password));
            // $this->email = htmlspecialchars(strip_tags($this->email));
            // $this->lastname = htmlspecialchars(strip_tags($this->lastname));
            // $this->country = htmlspecialchars(strip_tags($this->country));
            // $this->city = htmlspecialchars(strip_tags($this->city));
            // $this->address = htmlspecialchars(strip_tags($this->address));

            // bind the values
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':role_id', $this->role_id);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':lastname', $this->lastname);
            $stmt->bindParam(':country', $this->country);
            $stmt->bindParam(':city', $this->city);
            $stmt->bindParam(':address', $this->address);
            $stmt->bindParam(':phone_number', $this->phone_number);
            $stmt->bindParam(':social_auth_name', $this->social_auth_name);
            $stmt->bindParam(':is_social', $this->is_social);

            // hash the password before saving to database
            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $password_hash);

            $stmt->execute();
            // $stmt->debugDumpParams();
            $this->user_id = $this->conn->lastInsertId();
            $this->conn->commit();

            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            echo $e;
            header('HTTP/1.1 500 Internal Server Error');
            return false;
            exit();
        }
    }

    // check if given email exist in the database
    function emailExists()
    {

        // query to check if email exists
        $query = "SELECT *
            FROM " . $this->table_name . "
            WHERE email = ?
            LIMIT 0,1";

        // prepare the query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));

        // bind given email value
        $stmt->bindParam(1, $this->email);

        // execute the query
        $stmt->execute();

        // get number of rows
        $num = $stmt->rowCount();

        // if email exists, assign values to object properties for easy access and use for php sessions
        if ($num > 0) {

            // get record details / values
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // assign values to object properties
            $this->user_id = $row['user_id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            $this->role_id = $row['role_id'];
            $this->lastname = $row['lastname'];
            $this->country = $row['country'];
            $this->city = $row['city'];
            $this->address = $row['address'];
            $this->phone_number = $row['phone_number'];
            $this->social_auth_name = $row['social_auth_name'];
            $this->is_social = $row['is_social'];


            return array(
                "success" => true,
            );
        }

        return array("success" => false);
        // return false if email does not exist in the database
        // return false;
    }
    function userOwnsToken($token)
    {

        // query to check if email exists
        $query = "
        SELECT
            *
        FROM
            user_tokens    
        JOIN
            tokens ON user_tokens.token_id = tokens.token_id    
        WHERE
            user_tokens.user_id = " . $this->user_id . "
             AND
            tokens.token = " . $token;

        // prepare the query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->email = htmlspecialchars(strip_tags($this->email));

        // bind given email value
        $stmt->bindParam(1, $this->email);

        // execute the query
        $stmt->execute();

        // get number of rows
        $num = $stmt->rowCount();

        // if email exists, assign values to object properties for easy access and use for php sessions
        if ($num > 0) {

            return array(
                "success" => true,
            );
        }

        return array("success" => false);
    }

    // update a user record
    public function parseFromToken($token)
    {
        try {
            // decode token
            $data = base64_decode($token);
            $data = json_decode($data);
            $this->email = $data->email;
            $email_exists = $this->emailExists();

            if ($email_exists['success']) {

                if ($this->role_id == 2) {

                    http_response_code(202);

                    return array(
                        "success" => true,
                        "message" => "Access granted.",
                        "data" => array(
                            "user" => $this->username,
                            "userID" => $this->user_id,
                        ),
                        "error" => false
                    );

                    // return true;
                } elseif ($this->role_id == 1) {
                    http_response_code(401);
                    return array(
                        "success" => false,
                        "message" => "You don't have permission to create new user.",
                        "data" => array(
                            "user" => $this->username,
                            "userID" => $this->user_id,
                        ),
                        "error" => true
                    );
                }
            } else {
                http_response_code(404);
                return array(
                    "success" => false,
                    "message" => "Access denied - no such user.",
                    "data" => array(
                        "user" => $this->username,
                    ),
                    "error" => true
                );
            }
        } catch (Exception $e) {

            http_response_code(400);
            return false;
            return array(
                "success" => false,
                "message" => "Access denied - token failed to decode.",
                "data" => array(
                    "error" => $e->getMessage()
                ),
                "error" => true
            );
        }
    }
    public function isUserOwnerOfToken($api_token, $token)
    {
        try {
            // decode api_token
            $data = base64_decode($api_token);
            $data = json_decode($data);
            $this->email = $data->email;
            $email_exists = $this->emailExists();

            if ($email_exists['success']) {

                if ($this->role_id == 2) {

                    http_response_code(202);


                    // return true;
                } else if ($this->userOwnsToken($token)) {
                    return array(
                        "success" => true,
                        "message" => "Access granted.",
                        "data" => array(
                            "user" => $this->username,
                            "userID" => $this->user_id,
                        ),
                        "error" => false
                    );
                } else {
                    http_response_code(401);
                    return array(
                        "success" => false,
                        "message" => "You don't have permission to change data.",
                        "data" => array(
                            "user" => $this->username,
                            "userID" => $this->user_id,
                        ),
                        "error" => true
                    );
                }
            } else {
                http_response_code(404);
                return array(
                    "success" => false,
                    "message" => "Access denied - no such user.",
                    "data" => array(
                        "user" => $this->username,
                    ),
                    "error" => true
                );
            }
        } catch (Exception $e) {

            http_response_code(400);
            return false;
            return array(
                "success" => false,
                "message" => "Access denied - token failed to decode.",
                "data" => array(
                    "error" => $e->getMessage()
                ),
                "error" => true
            );
        }
    }
    public function createToken()
    {
        $token = array(
            "user_id" => $this->user_id,
            "username" => $this->username,
            "password" => $this->password,
            "role_id" => $this->role_id,
            "lastname" => $this->lastname,
            "country" => $this->country,
            "city" => $this->city,
            "address" => $this->address,
            "phone_number" => $this->phone_number,
            "email" => $this->email,
            "social_auth_name" => $this->social_auth_name,
            "is_social" => $this->is_social,
        );
        $token = json_encode($token);
        return base64_encode($token);
    }
    public function changePassword($newPassword)
    {
        $new_password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (password_verify($newPassword, $this->password) == true) {
            return array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
        } else {
            try {
                $this->conn->beginTransaction();
                $query = "UPDATE " . $this->table_name;
                $query .=    " SET `password` = :password
                            WHERE `user_id` = :userId";

                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                // prepare the query
                $stmt = $this->conn->prepare($query);

                $stmt->bindParam(':userId', $this->user_id);
                $stmt->bindParam(':password', $new_password_hash);

                $stmt->execute();
                // $stmt->debugDumpParams();
                $this->user_id = $this->conn->lastInsertId();
                $this->conn->commit();

                http_response_code(200);

                return array(
                    "success" => true,
                    "message" => "Password changed successfully.",
                    "data" => array(
                        "user_id" => $this->user_id,
                        "username" => $this->username,
                        "password" => $this->password,
                        "role_id" => $this->role_id,
                        "lastname" => $this->lastname,
                        "country" => $this->country,
                        "city" => $this->city,
                        "address" => $this->address,
                        "phone_number" => $this->phone_number,
                        "email" => $this->email,
                        "social_auth_name" => $this->social_auth_name,
                        "is_social" => $this->is_social,
                        "pass" => $this->password,
                        "newpassh" => $new_password_hash,
                        "newpass" => $newPassword,
                    ),
                    "error" => false
                );
            } catch (Exception $e) {

                $this->conn->rollback();
                echo $e;
                header('HTTP/1.1 500 Internal Server Error');

                http_response_code(400);

                return array(
                    "success" => false,
                    "message" => "Database error.",
                    "data" => array(
                        "error" => $e->getMessage()
                    ),
                    "error" => true
                );
                exit();
            }
        }
    }
    public function update()
    {

        // if password needs to be updated
        $password_set = !empty($this->password) ? ", password = :password" : "";

        // if no posted password, do not update the password
        $query = "UPDATE " . $this->table_name . "
            SET
                firstname = :firstname,
                lastname = :lastname,
                email = :email
                {$password_set}
            WHERE id = :id";

        // prepare the query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // bind the values from the form
        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':email', $this->email);

        // hash the password before saving to database
        if (!empty($this->password)) {
            $this->password = htmlspecialchars(strip_tags($this->password));
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $password_hash);
        }

        // unique ID of record to be edited
        $stmt->bindParam(':id', $this->id);

        // execute the query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
