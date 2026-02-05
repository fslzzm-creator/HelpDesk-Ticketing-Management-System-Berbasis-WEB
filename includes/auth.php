<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function register($username, $email, $password, $full_name) {
        // Check if user exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            return "Username or email already exists";
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password_hash, $full_name);
        
        if ($stmt->execute()) {
            $user_id = $this->conn->insert_id;
            $this->loginUser($user_id, $username, 'user');
            return true;
        }
        
        return "Registration failed";
    }
    
    public function login($username, $password) {
    $stmt = $this->conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $this->loginUser($user['id'], $user['username'], $user['role']);
            return true;
        }
    }
    
    return "Invalid username or password";
}
    private function loginUser($user_id, $username, $role) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['logged_in'] = true;
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: ../login.php");
            exit();
        }
    }
    
    public function requireRole($required_role) {
        $this->requireLogin();
        
        if ($this->getUserRole() !== $required_role) {
            header("Location: ../dashboard.php");
            exit();
        }
    }
}
?>
