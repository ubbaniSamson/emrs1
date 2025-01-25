<?php
require_once '../controllers/UserController.php';

// Create UserController instance
$controller = new UserController();
$controller->registerUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>user Register</title>
    <style>
        body {
            background: url('assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Open Sans', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-form h1 {
            margin-bottom: 20px;
            font-size: 2rem;
            color: #0073e6;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .login-form button {
            width: 100%;
            padding: 10px;
            background-color: #0073e6;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
        }
        .login-form button:hover {
            background-color: #005bb5;
        }
        .login-form a {
            color: #0073e6;
            text-decoration: none;
            font-weight: bold;
        }
        .login-form a:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>

<video autoplay muted loop>
        <source src="video/video1.mp4" type="video/mp4">
        Your browser does not support the video tag.
</video>
<div class="login-form">
    <h1>User Register</h1>
    <form method="POST" action="register.php">
        <input type="text" name="firstname" placeholder="First Name" required>
        <input type="text" name="lastname" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>

    <button onclick="window.location.href='index.php'" class="back-btn">Back</button>
</div>
</body>
</html>
