<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body {
            background-size: cover;
            font-family: 'Open Sans', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .welcome-page {
            text-align: center;
            margin-top: 10%;
            color: white;
        }
        .welcome-page h1 {
            font-size: 3rem;
            margin-bottom: 30px;
            font-weight: bold;
            text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        }
        .buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .btn {
            text-decoration: none;
            background-color: rgba(0, 115, 230, 0.8);
            color: white;
            padding: 15px 25px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
        }
        .btn:hover {
            background-color: rgba(0, 115, 230, 1);
            transform: scale(1.1);
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.5);
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
    </style>
</head>
<body>
<video autoplay muted loop>
        <source src="video/video4.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="welcome-page">
        <h1>Welcome to Environmental Monitoring System</h1>  </br> </br> </br> </br> </br> </br> </br> </br>
        <div class="buttons">
        <a href="adminlogin.php" class="btn">Admin Login</a>
            <a href="login.php" class="btn">User Login</a>
        </div>
    </div>
</body>
</html>
