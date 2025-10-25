<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->user_id) && !empty($data->amount) && $data->amount < 0) {
    $user->id = $data->user_id;
    
    // Verificar saldo suficiente
    $current_balance = $user->getBalance();
    $withdraw_amount = abs($data->amount);
    
    if($current_balance >= $withdraw_amount) {
        if($user->updateBalance($data->amount)) {
            // Registrar transacción
            $query = "INSERT INTO transactions (user_id, type, amount, description) 
                      VALUES (:user_id, 'withdraw', :amount, 'Retiro de efectivo')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $data->user_id);
            $stmt->bindParam(":amount", $withdraw_amount);
            $stmt->execute();

            $new_balance = $user->getBalance();
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Retiro exitoso",
                "new_balance" => $new_balance
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("success" => false, "message" => "Error en el retiro"));
        }
    } else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Usted no cuenta con el saldo suficiente para retirar"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos inválidos"));
}
?>