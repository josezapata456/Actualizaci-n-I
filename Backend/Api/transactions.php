<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(isset($data->action) && $data->action == 'get_transactions' && !empty($data->user_id)) {
    
    try {
        // Usar la nueva función del modelo User
        $transactions = $user->getTransactions(
            $data->user_id, 
            $data->filter_type ?? 'all'
        );

        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "transactions" => $transactions,
            "count" => count($transactions)
        ));

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Error al obtener transacciones: " . $e->getMessage()
        ));
    }

} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos inválidos"));
}
?>