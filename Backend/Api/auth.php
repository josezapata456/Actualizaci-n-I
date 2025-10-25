<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if(isset($data->action)) {
        switch($data->action) {
            case 'register':
                if(!empty($data->username) && !empty($data->email) && !empty($data->password) && !empty($data->full_name)) {
                    $user->username = $data->username;
                    $user->email = $data->email;
                    $user->password = $data->password;
                    $user->full_name = $data->full_name;

                    if(!$user->userExists()) {
                        if($user->register()) {
                            http_response_code(201);
                            echo json_encode(array(
                                "success" => true,
                                "message" => "Usuario registrado exitosamente.",
                                "user" => array(
                                    "id" => $user->id,
                                    "username" => $user->username,
                                    "full_name" => $user->full_name
                                )
                            ));
                        } else {
                            http_response_code(503);
                            echo json_encode(array("success" => false, "message" => "No se pudo registrar el usuario."));
                        }
                    } else {
                        http_response_code(409);
                        echo json_encode(array("success" => false, "message" => "El usuario ya existe."));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(array("success" => false, "message" => "Datos incompletos."));
                }
                break;

            case 'login':
                if(!empty($data->username) && !empty($data->password)) {
                    $user->username = $data->username;
                    $user->password = $data->password;

                    if($user->login()) {
                        http_response_code(200);
                        echo json_encode(array(
                            "success" => true,
                            "message" => "Login exitoso.",
                            "user" => array(
                                "id" => $user->id,
                                "username" => $user->username,
                                "full_name" => $user->full_name,
                                "balance" => $user->balance
                            )
                        ));
                    } else {
                        http_response_code(401);
                        echo json_encode(array("success" => false, "message" => "Credenciales incorrectas."));
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(array("success" => false, "message" => "Datos incompletos."));
                }
                break;
        }
    }
}
?>