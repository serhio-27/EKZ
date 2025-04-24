<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $full_name;
    public $role_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        try {
            $query = "SELECT u.*, r.name as role_name 
                     FROM " . $this->table_name . " u
                     JOIN roles r ON u.role_id = r.id
                     WHERE u.username = :username";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(password_verify($password, $row['password'])) {
                    return $row;
                }
            }
            
            return false;
        } catch(PDOException $e) {
            throw new Exception("Ошибка при авторизации: " . $e->getMessage());
        }
    }
}
?> 