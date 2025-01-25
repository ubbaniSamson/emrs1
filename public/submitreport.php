<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a New Report</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <h1>Environmental Monitoring and Reporting System</h1>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="submit_report.php" class="menu-btn active">Submit a New Report</a>
            <a href="#" class="menu-btn">All Reports</a>
            <a href="#" class="menu-btn">Climate Impact Data Visualization</a>
            <a href="#" class="menu-btn">Community Engagement</a>
            <a href="#" class="menu-btn">Real-Time Alerts Map</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2>Submit a New Report</h2>
            <form action="process_report.php" method="POST" enctype="multipart/form-data">
    <label for="crime_type">Crime Type:</label>
    <select name="crime_type" id="crime_type" required>
        <option value="" disabled selected>Select Crime Type</option>
        <option value="Pollution">Pollution</option>
        <option value="Deforestation">Deforestation</option>
        <option value="Illegal Fishing">Illegal Fishing</option>
        <option value="Wildlife Poaching">Wildlife Poaching</option>
        <option value="Other">Other</option>
    </select>

    <label for="location">Location:</label>
    <input type="text" name="location" id="location" placeholder="e.g., City A, Forest Area" required>

    <!-- Map Integration -->
    <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d12093.13651913831!2d-74.0425728!3d40.7337731!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sus!4v1731605465672!5m2!1sen!2sus" 
            width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>

    <label for="description">Description:</label>
    <textarea name="description" id="description" placeholder="Describe the issue" rows="4" required></textarea>

    <label for="image">Upload Image:</label>
    <input type="file" name="image" id="image" accept="image/*">

    <button type="submit" class="btn">Submit Report</button>
</form>

        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; 2025 Environmental Monitoring and Reporting System
    </div>
</body>
</html>
