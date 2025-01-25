<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->connect();

    $user_id = $_SESSION['user_id'];
    $crime_type = $_POST['crime_type'];
    $location = $_POST['location'];
    $description = $_POST['description'];

    try {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_path = __DIR__ . "/uploads/" . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = "uploads/" . $image_name;

                $query = "INSERT INTO reports (user_id, crime_type, location, description, image_path)
                          VALUES (:user_id, :crime_type, :location, :description, :image_path)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':crime_type', $crime_type);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':image_path', $image_path);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Report submitted successfully!";
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                throw new Exception("Failed to upload the image.");
            }
        } else {
            throw new Exception("Please upload an image.");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}
?>
