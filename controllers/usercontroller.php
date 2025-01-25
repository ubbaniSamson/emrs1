<?php
require_once '../config/Database.php';
require_once '../models/User.php';

class UserController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function loginUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User($this->db);
            $user->email = $_POST['email'];
            $user->password = $_POST['password'];
            $result = $user->login();

            if ($result) {
                // Set session variables
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user'] = $result['firstname'];
                echo "<script>
                        alert('Login Successful!');
                        window.location.href = 'dashboard.php';
                      </script>";
                exit();
            } else {
                echo "<script>
                        alert('Wrong Email or Password!');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            }
        }
    }

    public function registerUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $query = "INSERT INTO users (firstname, lastname, email, password) 
                      VALUES (:firstname, :lastname, :email, :password)";
            $stmt = $this->db->prepare($query);

            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);

            try {
                if ($stmt->execute()) {
                    echo "Registration successful! <a href='index.php'>Login here</a>";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
}
?>
