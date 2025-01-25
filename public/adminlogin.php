<?php
session_start();

// Database connection
$host = 'localhost';
$username = 'root'; // Replace with your DB username
$password = ''; // Replace with your DB password
$database = 'emrs_db';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate admin credentials
    $query = "SELECT * FROM admins WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Assuming admin ID is retrieved after successful login
        $admin = $result->fetch_assoc();
        $admin_id = $admin['id']; // Ensure `id` exists in your `admins` table
    
        // Log the login event
        $log_query = "INSERT INTO user_logins (user_id, login_time) VALUES (?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("i", $admin_id);
        $log_stmt->execute();
    
        // Proceed with login
        $_SESSION['admin'] = $email;
        echo "<script>alert('Login successful!'); window.location.href='admindashboard.php';</script>";
    } else {
        echo "<script>alert('Invalid email or password!');</script>";
    }
    
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            animation: morphEffect 6s infinite alternate; /* Morphing animation */
        }

        .login-container {
            background: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background: #0073e6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .login-container button:hover {
            background: #005bb5;
        }

        .back-btn {
            margin-top: 15px;
            display: inline-block;
            text-decoration: none;
            background: #f4f4f4;
            color: #333;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #ddd;
        }
        .back-btn {
        margin-top: 15px;
        padding: 10px 20px;
        background-color: #ff4d4d;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1.1rem;
        cursor: pointer;
        width: 100%;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background-color: #cc0000;
        transform: translateY(-3px);
        box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.3);
    }

        @keyframes morphEffect {
            0% {
                filter: brightness(100%) saturate(100%) blur(0px);
            }
            25% {
                filter: brightness(120%) saturate(120%) blur(2px);
            }
            50% {
                filter: brightness(90%) saturate(80%) blur(4px);
            }
            75% {
                filter: brightness(110%) saturate(110%) blur(2px);
            }
            100% {
                filter: brightness(100%) saturate(100%) blur(0px);
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop>
        <source src="video/video3.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="adminlogin.php" method="POST">
            <input type="text" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <button onclick="window.location.href='index.php'" class="back-btn">Back</button>

    </div>
</body>
</html>
