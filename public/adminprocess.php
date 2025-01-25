<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$conn = $database->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['admin_email'];
    $password = $_POST['admin_password'];

    $query = "SELECT * FROM admins WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $password === $admin['password']) { // Assuming plain text passwords
        $_SESSION['success'] = "Welcome Admin!";
        header("Location: admin_dashboard.php");
    } else {
        $_SESSION['error'] = "Invalid Email or Password!";
        header("Location: admin_login.php");
    }
}
?>
