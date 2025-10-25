<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $balance;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET username=:username, email=:email, password=:password, full_name=:full_name";
        
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":full_name", $this->full_name);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function login() {
        $query = "SELECT id, username, password, full_name, balance 
                  FROM " . $this->table_name . " 
                  WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();

        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->full_name = $row['full_name'];
                $this->balance = $row['balance'];
                return true;
            }
        }
        return false;
    }

    public function userExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE username = :username OR email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function updateBalance($amount) {
        $query = "UPDATE " . $this->table_name . " 
                  SET balance = balance + :amount 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    public function getBalance() {
        $query = "SELECT balance FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['balance'];
    }

    // ⬇️⬇️⬇️ NUEVAS FUNCIONES PARA TRANSFERENCIAS ⬇️⬇️⬇️

    public function getUserByUsername($username) {
        $query = "SELECT id, username, full_name, balance FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function hasSufficientBalance($amount) {
        $current_balance = $this->getBalance();
        return $current_balance >= $amount;
    }

    public function getCurrentUsername($user_id) {
        $query = "SELECT username FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['username'] : '';
    }

    // Función para obtener datos completos del usuario
    public function getUserById($user_id) {
        $query = "SELECT id, username, email, full_name, balance FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
    // Agrega esta función a tu clase User en backend/models/User.php
public function getTransactions($user_id, $filter_type = 'all') {
    // Construir la consulta base
    $query = "SELECT t.*, u.username as target_username 
              FROM transactions t 
              LEFT JOIN users u ON t.target_user_id = u.id 
              WHERE t.user_id = :user_id";
    
    $params = [':user_id' => $user_id];

    // Aplicar filtro si es necesario
    if($filter_type != 'all') {
        $query .= " AND t.type = :type";
        $params[':type'] = $filter_type;
    }

    // Ordenar por fecha más reciente primero
    $query .= " ORDER BY t.created_at DESC LIMIT 50";

    $stmt = $this->conn->prepare($query);
    
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>