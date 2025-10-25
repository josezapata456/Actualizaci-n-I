<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

// Log para debug
error_log("=== TRANSFER API CALLED ===");

$input = file_get_contents("php://input");
error_log("Raw input: " . $input);

$data = json_decode($input);
error_log("Parsed data: " . print_r($data, true));

// Verificar datos requeridos
if(empty($data->user_id)) {
    error_log("❌ Missing user_id");
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "user_id es requerido"));
    exit();
}

if(empty($data->target_username)) {
    error_log("❌ Missing target_username");
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "target_username es requerido"));
    exit();
}

if(empty($data->amount) || $data->amount <= 0) {
    error_log("❌ Invalid amount: " . ($data->amount ?? 'empty'));
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "amount debe ser mayor a 0"));
    exit();
}

error_log("✅ Datos válidos recibidos: user_id={$data->user_id}, target_username={$data->target_username}, amount={$data->amount}");

try {
    $db->beginTransaction();

    // Verificar que el usuario origen existe
    $user = new User($db);
    $user->id = $data->user_id;
    $current_balance = $user->getBalance();
    
    error_log("💰 Saldo actual usuario {$data->user_id}: {$current_balance}");

    if($current_balance < $data->amount) {
        throw new Exception("Saldo insuficiente. Tienes: {$current_balance}, necesitas: {$data->amount}");
    }

    // Verificar que el usuario destino existe
    $target_user = $user->getUserByUsername($data->target_username);
    
    if(!$target_user) {
        throw new Exception("El usuario destino '{$data->target_username}' no existe");
    }
    
    error_log("✅ Usuario destino encontrado: ID={$target_user['id']}, Nombre={$target_user['full_name']}");

    // Verificar que no sea transferencia a sí mismo
    if($target_user['id'] == $data->user_id) {
        throw new Exception("No puedes transferir a tu propia cuenta");
    }

    // Obtener username del usuario origen
    $origin_user = $user->getUserById($data->user_id);
    if(!$origin_user) {
        throw new Exception("Usuario origen no encontrado");
    }

    error_log("🔄 Iniciando transferencia de {$origin_user['username']} a {$target_user['username']} por {$data->amount}");

    // Realizar la transferencia: restar del usuario origen
    $query = "UPDATE users SET balance = balance - :amount WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":amount", $data->amount);
    $stmt->bindParam(":id", $data->user_id);
    
    if(!$stmt->execute()) {
        throw new Exception("Error al debitar del usuario origen");
    }
    error_log("✅ Débito realizado");

    // Sumar al usuario destino
    $query = "UPDATE users SET balance = balance + :amount WHERE id = :target_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":amount", $data->amount);
    $stmt->bindParam(":target_id", $target_user['id']);
    
    if(!$stmt->execute()) {
        throw new Exception("Error al acreditar al usuario destino");
    }
    error_log("✅ Crédito realizado");

    // Registrar transacción para el usuario origen (débito)
    $query = "INSERT INTO transactions (user_id, target_user_id, type, amount, description) 
              VALUES (:user_id, :target_user_id, 'transfer', :amount, :description)";
    $stmt = $db->prepare($query);
    $debit_amount = -$data->amount;
    $description_debit = "Transferencia a " . $target_user['username'];
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->bindParam(":target_user_id", $target_user['id']);
    $stmt->bindParam(":amount", $debit_amount);
    $stmt->bindParam(":description", $description_debit);
    $stmt->execute();
    error_log("✅ Transacción débito registrada");

    // Registrar transacción para el usuario destino (crédito)
    $query = "INSERT INTO transactions (user_id, target_user_id, type, amount, description) 
              VALUES (:user_id, :target_user_id, 'transfer', :amount, :description)";
    $stmt = $db->prepare($query);
    $credit_amount = $data->amount;
    $description_credit = "Transferencia de " . $origin_user['username'];
    $stmt->bindParam(":user_id", $target_user['id']);
    $stmt->bindParam(":target_user_id", $data->user_id);
    $stmt->bindParam(":amount", $credit_amount);
    $stmt->bindParam(":description", $description_credit);
    $stmt->execute();
    error_log("✅ Transacción crédito registrada");

    // Obtener nuevo saldo del usuario origen
    $new_balance = $user->getBalance();

    $db->commit();
    error_log("🎉 Transferencia COMPLETADA. Nuevo saldo: {$new_balance}");

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Transferencia realizada exitosamente a " . $target_user['full_name'],
        "new_balance" => $new_balance,
        "target_user" => $target_user['full_name']
    ));

} catch (Exception $e) {
    $db->rollBack();
    error_log("❌ Error en transferencia: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => $e->getMessage()
    ));
}
?>