<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$database = new Database();
$conn = $database->connect();


// Fetch report counts
$total_reports = 0;
$reports_last_24h = 0;

try {
    // Total reports
    $query_total = "SELECT COUNT(*) AS total FROM reports";
    $stmt_total = $conn->prepare($query_total);
    $stmt_total->execute();
    $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_reports = $result_total['total'];

    // Reports in the last 24 hours
    $query_24h = "SELECT COUNT(*) AS last_24h FROM reports WHERE created_at >= NOW() - INTERVAL 1 DAY";
    $stmt_24h = $conn->prepare($query_24h);
    $stmt_24h->execute();
    $result_24h = $stmt_24h->fetch(PDO::FETCH_ASSOC);
    $reports_last_24h = $result_24h['last_24h'];
} catch (PDOException $e) {
    $message = "<div class='message error'>Error fetching counts: " . $e->getMessage() . "</div>";
}


//Code to Fetch Reports for Map
// Code to Fetch Threads

// Fetch reports as JSON for Real-Time Alerts Map
if (isset($_GET['action']) && $_GET['action'] === 'fetch_reports') {
    header('Content-Type: application/json');
    try {
        $query = "SELECT crime_type, description, location, lat, lng FROM reports";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($reports);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit(); // Stop further processing
}


// This fetches threads from the database and returns them as JSON:


if (isset($_GET['action']) && $_GET['action'] === 'fetch_threads') {
    header('Content-Type: application/json');
    try {
        $query = "SELECT * FROM community_threads ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($threads);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit();
}
// This saves a new thread submitted by the user:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_thread') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    try {
        $query = "INSERT INTO community_threads (title, content) VALUES (:title, :content)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        if ($stmt->execute()) {
            echo json_encode(["success" => "Thread posted successfully!"]);
        } else {
            echo json_encode(["error" => "Failed to post thread."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit();
}




// Initialize message variable
$message = "";

$reports = [];
try {
    $query = "SELECT crime_type, severity, impact, location, description, image_path, created_at 
              FROM reports ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "<div class='message error'>Error fetching reports: " . $e->getMessage() . "</div>";
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id']; // Get logged-in user ID
    $crime_type = $_POST['crime_type'];
    $severity = $_POST['severity'];
    $impact = $_POST['impact'];
    $location = $_POST['location'];
    $lat = $_POST['lat']; // Latitude from the hidden input
    $lng = $_POST['lng']; // Longitude from the hidden input
    $description = $_POST['description'];
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = "../uploads/" . $image_name;

        if (!is_dir("../uploads")) {
            mkdir("../uploads", 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = "uploads/" . $image_name;
        }
    }

    // Insert report into the database
    try {
        $query = "INSERT INTO reports (user_id, crime_type, severity, impact, location, lat, lng, description, image_path) 
                  VALUES (:user_id, :crime_type, :severity, :impact, :location, :lat, :lng, :description, :image_path)";
        $stmt = $conn->prepare($query);

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':crime_type', $crime_type);
        $stmt->bindParam(':severity', $severity);
        $stmt->bindParam(':impact', $impact);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':lat', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image_path', $image_path);

        if ($stmt->execute()) {
            $message = "<div class='message success'>Report submitted successfully!</div>";
        } else {
            $message = "<div class='message error'>Error submitting the report. Please try again.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='message error'>Database error: " . $e->getMessage() . "</div>";
    }
}


// Fetch all reports
$reports = [];
try {
    $query = "SELECT crime_type, severity, impact, location, description, image_path, created_at 
              FROM reports ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "<div class='message error'>Error fetching reports: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEw86vbAZhQXFt8e2uEaruRRPhED3ZmbQ&libraries=places"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Environmental Monitoring and Reporting System</h1>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <!-- Dashboard Layout -->
    <div class="dashboard-container">



<!-- Sidebar -->
<div class="sidebar">
<a href="#" class="menu-btn" onclick="showContent('home')">Home</a>
    <a href="#" class="menu-btn active" onclick="loadContent('submitReport')">Submit a New Report</a>
    <a href="#" class="menu-btn" onclick="loadContent('allReports')">All Reports</a>
    <a href="#" class="menu-btn" onclick="loadContent('climateVisualization')">Climate Visualization</a>
    <a href="#" class="menu-btn" onclick="loadContent('communityEngagement')">Community Engagement</a>
    <a href="#" class="menu-btn" onclick="loadContent('realTimeAlerts')">Real-Time Alerts</a>
    <a href="#" class="menu-btn" onclick="loadContent('environmentalTips')">Environmental Tips</a>
</div>

       <!-- Main Content -->
       <div class="main-content">
            <div id="blankSection" class="blank-section">
            <p>Please select an option from the sidebar to view content.</p></br>
                <video   autoplay muted loop>
                      <source src="video/puppy.mp4" type="video/mp4">
                         Your browser does not support the video tag.
                 </video>
               
                
            </div>
            

            <!-- Home Section -->
            <div id="home" class="content-section">
                <h2>Report an Environmental Violation, General Information</h2>
                <h3>General Information</h3>
                <p>EPA seeks your help! Please let us know about potentially harmful environmental activities in your community or workplace.</p>
                <ul>
                    <li><a href="#">What EPA's Enforcement Program Does</a></li>
                    <li><a href="#">How You Can Help</a></li>
                    <li><a href="#">What is an Environmental Crime?</a></li>
                    <li><a href="#">What Not to Report</a></li>
                    <li><a href="#">What to do in Case of an Emergency</a></li>
                    <li><a href="#">Brochures</a></li>
                    <li><a href="#">Reporting Potential Violations by Phone</a></li>
                </ul>
                <h3>What EPA's Enforcement Program Does</h3>
                <p>The mission of EPA's Office of Enforcement and Compliance Assurance (OECA) is to improve the environment and protect human health by ensuring compliance with environmental laws and regulations, preventing pollution, and promoting environmental stewardship...</p>
                <h3>How You Can Help</h3>
                <p>We invite you to help us protect our nation's environment by identifying and reporting environmental violations...</p>
                <h3>What is an Environmental Crime?</h3>
                <p>Violations of environmental laws take many different forms. Some are done intentionally and may be criminal violations...</p>
                <h3>What Not to Report</h3>
                <p>EPA does not have jurisdiction over automobile safety, consumer product safety, foods, medicine, cosmetics, or medical devices...</p>
                <h3>What to Do in Case of an Emergency</h3>
                <p>If you are experiencing an environmental emergency or are witnessing an environmental event that poses an imminent threat...</p>
            </div>

                <!-- Submit Report -->
                <div id="submitReport" style="display: none;">
    <h2>Submit a New Report</h2>
    <div id="submissionMessage"></div> <!-- For displaying success/error messages -->
    <form id="reportForm" enctype="multipart/form-data">
        <label for="crime_type">Crime Type:</label>
        <select name="crime_type" id="crime_type" required>
            <option value="" disabled selected>Select Crime Type</option>
            <option value="Pollution">Pollution</option>
            <option value="Deforestation">Deforestation</option>
            <option value="Illegal Fishing">Illegal Fishing</option>
            <option value="Wildlife Poaching">Wildlife Poaching</option>
        </select>

        <label for="severity">Severity Level:</label>
        <select name="severity" id="severity" required>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
        </select>

        <label for="location">Location:</label>
        <input type="text" name="location" id="location" placeholder="Type your address here" required>
        <input type="hidden" name="lat" id="lat">
        <input type="hidden" name="lng" id="lng">

        <label for="impact">Impact Scale (Optional):</label>
        <input type="text" name="impact" id="impact">

        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="4" required></textarea>

        <label for="image">Upload Image:</label>
        <input type="file" name="image" id="image" accept="image/*">

        <button type="button" onclick="submitReport()" class="btn">Submit Report</button>
    </form>
</div>


                <!-- All Reports -->

                

                <div id="allReports" style="display: none;">
    <h2>All Reports</h2>
    <!-- Report Summary -->
    <div class="report-summary">
        <div class="card report-card">
            <h3>Total Reports</h3>
            <p><?= $total_reports ?></p>
        </div>
        <div class="card report-card">
            <h3>Reports in Last 24 Hours</h3>
            <p><?= $reports_last_24h ?></p>
        </div>
    </div>

    <!-- Table for Reports -->
    <?php if (count($reports) > 0): ?>
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Crime Type</th>
                    <th>Severity</th>
                    <th>Impact</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?= htmlspecialchars($report['crime_type']) ?></td>
                        <td><?= htmlspecialchars($report['severity']) ?></td>
                        <td><?= htmlspecialchars($report['impact']) ?></td>
                        <td><?= htmlspecialchars($report['location']) ?></td>
                        <td><?= htmlspecialchars($report['description']) ?></td>
                        <td>
                            <?php if ($report['image_path']): ?>
                                <img src="../<?= $report['image_path'] ?>" width="100">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($report['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No reports found.</p>
    <?php endif; ?>
</div>

<!-- Climate Visualization Section -->
<div id="climateVisualization">
   <center> <h2>Climate Impact Data Visualization</h2>
    <p>Analyze climate data using AI predictions.</p>
    </center>

<!-- Chart -->
<h3>Impact Visualization</h3>
    <canvas id="climateChart" width="600" height="300"></canvas>


    <!-- Table -->
    <h3>Predicted Impact Data</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Location</th>
                <th>Impact (sq.m)</th>
                <th>Predicted Impact</th>
            </tr>
        </thead>
        <tbody id="prediction-table-body">
            <!-- Data will be injected dynamically -->
        </tbody>
    </table>

    
</div>


<!-- Table and Button CSS -->
<style>
    
    .dashboard-container {
            display: flex;
        }

        .sidebar {
            width: 20%;
            background-color: #343a40;
            padding: 20px;
            color: white;
        }

        .menu-btn {
            display: block;
            color: white;
            text-decoration: none;
            margin: 10px 0;
            padding: 10px;
            background-color: #007bff;
            border-radius: 5px;
            text-align: center;
        }

        .menu-btn.active {
            background-color: #0056b3;
        }

        .main-content {
            width: 80%;
            background-color: #f8f9fa;
            padding: 20px;
            min-height: 100vh; /* Ensure full height */
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }
 .button-container {
    text-align: center;
    margin-bottom: 15px;
}
.blank-section {
            height: 100%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .blank-section p {
            font-size: 1.5rem;
            color: #6c757d;
        }

.toggle-btn {
    padding: 10px 20px;
    margin: 5px;
    background-color: #0073e6;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 1rem;
    transition: 0.3s;
}

.toggle-btn:hover {
    background-color: #005bb5;
}

.chart-container, .table-container {
    margin: 0 auto;
    max-width: 800px;
}

table.prediction-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    margin-top: 20px;
}

table.prediction-table th, table.prediction-table td {
    border: 1px solid #ddd;
    padding: 8px;
}

table.prediction-table th {
    background-color: #f4f4f4;
    font-weight: bold;
}

.impact-low {
    color: #28a745;
    font-weight: bold;
}
.impact-moderate {
    color: #ffc107;
    font-weight: bold;
}
.impact-high {
    color: #ff5722;
    font-weight: bold;
}
.impact-severe {
    color: #dc3545;
    font-weight: bold;
}


ul {
            list-style-type: disc;
            padding-left: 20px;
        }

        ul li a {
            text-decoration: none;
            color: #007bff;
        }

        ul li a:hover {
            text-decoration: underline;
        }
        #threads-container {
    margin-bottom: 20px;
}

.thread {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
}

.thread h4 {
    margin: 0;
    font-size: 1.2rem;
    color: #007bff;
}

.thread p {
    margin: 10px 0;
    color: #555;
}

.thread small {
    display: block;
    color: #aaa;
}

/* Home Section Styling */
#home {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    color: #333;
}

#home h2 {
    font-size: 2rem;
    color: #007bff;
    margin-bottom: 10px;
    border-bottom: 2px solid #007bff;
    display: inline-block;
    padding-bottom: 5px;
}

#home h3 {
    font-size: 1.5rem;
    color: #333;
    margin-top: 20px;
    margin-bottom: 10px;
}

#home p {
    font-size: 1rem;
    line-height: 1.6;
    color: #555;
}

#home ul {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

#home ul li {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    font-size: 1rem;
    color: #007bff;
}

#home ul li a {
    color: #007bff;
    text-decorati
    footer {
    background-color: #333;
    color: #fff;
    padding: 20px 0;
}
footer {
    position: fixed; /* Keeps the footer at the bottom of the page */
    bottom: 0; /* Aligns the footer to the bottom */
    left: 0; /* Starts from the very left of the screen */
    width: 100%; /* Makes the footer span the full width */
    background-color: #2C3E50; /* Dark blue background */
    color: #ECF0F1; /* Light text color */
    padding: 20px 0; /* Padding for content inside the footer */
    text-align: center; /* Centers text and content */
    box-shadow: 0px -2px 5px rgba(0, 0, 0, 0.2); /* Optional: Adds a subtle shadow at the top of the footer */
    z-index: 100; /* Ensures it stays above other content */
}

footer .footer-container {
    display: flex; /* Creates a horizontal layout */
    flex-wrap: wrap; /* Adjusts layout for smaller screens */
    justify-content: space-around; /* Spreads the sections evenly */
    max-width: 1200px; /* Optional: Sets a max-width for content */
    margin: auto; /* Centers the footer content */
}

footer h4 {
    font-size: 18px;
    margin-bottom: 10px;
    font-weight: bold;
}

footer ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

footer ul li {
    margin-bottom: 5px;
}

footer ul li a {
    color: #ECF0F1;
    text-decoration: none;
    font-size: 14px;
}

footer ul li a:hover {
    text-decoration: underline;
    color: #1ABC9C;
}

footer .social-links a {
    font-size: 18px;
    margin: 0 10px;
    color: #ECF0F1;
    text-decoration: none;
}

footer .social-links a:hover {
    color: #1ABC9C;
}

footer .footer-bottom {
    font-size: 12px;
    color: #95A5A6;
    margin-top: 10px;
}


</style>



<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tableBody = document.getElementById("prediction-table-body");

    // Fetch predictions from AI service
    fetch("http://127.0.0.1:8000/predict_from_db")
        .then((response) => {
            if (!response.ok) throw new Error("Failed to fetch predictions");
            return response.json();
        })
        .then((data) => {
            tableBody.innerHTML = "";
            data.forEach((item, index) => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.report.address || "Unknown Address"}</td>
                    <td>${item.report.impact || 0} sq.m</td>
                    <td>${item.prediction}</td>
                `;
                tableBody.appendChild(row);
            });
            renderChart(data);
        })
        .catch((error) => console.error("Error fetching predictions:", error));
});
function navigateTo(sectionId) {
    const sections = ['home', 'submitReport', 'allReports', 'climateVisualization', 'communityEngagement', 'realTimeAlerts', 'environmentalTips'];
    sections.forEach(id => document.getElementById(id).style.display = 'none');
    document.getElementById(sectionId).style.display = 'block';
    document.querySelectorAll('.menu-btn').forEach(button => button.classList.remove('active'));
    document.querySelector(`.menu-btn[onclick="showContent('${sectionId}')"]`).classList.add('active');
}


function renderChart(data) {
    const ctx = document.getElementById('climateChart').getContext('2d');

    const labels = data.map(item => item.report.address || "Unknown Address");
    const values = data.map(item => item.report.impact || 0);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: "Predicted Impact (sq.m)",
                data: values,
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: "AI Climate Impact Predictions"
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}


</script>




                <!-- Community Engagement -->
                <div id="communityEngagement" class="content-section">
    <h2>Community Discussions</h2>

    <!-- Existing Threads -->
    <div id="threads-container">
        <!-- Threads will be dynamically loaded here -->
    </div>

    <!-- New Thread Form -->
    <h3>Start a New Discussion</h3>
    <form id="newThreadForm">
        <label for="threadTitle">Title:</label>
        <input type="text" id="threadTitle" name="title" placeholder="Enter discussion title" required>

        <label for="threadContent">Content:</label>
        <textarea id="threadContent" name="content" placeholder="Write your discussion content" rows="4" required></textarea>

        <button type="button" onclick="submitThread()">Post Thread</button>
    </form>
</div>

                <!-- Real-Time Alerts Map Section -->
                <div id="realTimeAlerts" style="display: none;">
    <h2>Real-Time Alerts Map</h2>
    <p>View live updates of environmental reports:</p>
    <div id="map" style="height: 400px; width: 100%;"></div>
</div>

               <!-- Environmental Tips Section -->
               <div id="environmentalTips" style="display: none;">
    <h2>Environmental Tips</h2>
    <ul>
        <li>Reduce, reuse, and recycle to minimize waste.</li>
        <li>Save energy by using energy-efficient appliances.</li>
        <li>Plant trees to combat deforestation and absorb CO₂.</li>
        <li>Support clean energy sources like solar and wind power.</li>
        <li>Reduce your carbon footprint by using public transport.</li>
        <li>Report environmental crimes like pollution and illegal logging.</li>
    </ul>
</div>



    <!-- JavaScript -->
    <script>


function fetchPredictions() {
    fetch('http://127.0.0.1:8000/predict_from_db') // Connect to AI service
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Fetched Predictions:", data); // Log data for debugging

            if (data.error) {
                document.getElementById('ai-prediction').innerText = "Error: " + data.error;
                console.error("Server Error:", data.error);
                return;
            }

            const labels = data.map((item, index) => `Location ${index + 1}`);
            const predictions = data.map(item => item.prediction);

            // Generate chart
            const ctx = document.getElementById('climateChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Impact Predictions",
                        data: predictions,
                        backgroundColor: '#4BC0C0',
                        borderColor: '#0073e6',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'AI Climate Impact Predictions'
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // Display messages
            const messages = data.map((item, index) => 
                `<li>Location ${index + 1}: ${item.report.impact} sq.m - Predicted Impact: ${item.prediction}</li>`
            ).join('');
            document.getElementById('prediction-messages').innerHTML = `<ul>${messages}</ul>`;
        })
        .catch(error => {
            console.error("Error fetching predictions:", error);
            document.getElementById('ai-prediction').innerText = "Error fetching predictions.";
        });
}

document.addEventListener('DOMContentLoaded', fetchPredictions);


      function loadContent(section) {
    const sections = ['home', 'submitReport', 'allReports', 'climateVisualization', 'communityEngagement', 'realTimeAlerts', 'environmentalTips'];
    sections.forEach(id => document.getElementById(id).style.display = 'none');
    document.getElementById(section).style.display = 'block';
    document.querySelectorAll('.menu-btn').forEach(button => button.classList.remove('active'));
    event.target.classList.add('active');
}

                    function initAutocomplete() {
    const input = document.getElementById('location');
    const autocomplete = new google.maps.places.Autocomplete(input);

    // Extract latitude and longitude when an address is selected
    autocomplete.addListener('place_changed', function () {
        const place = autocomplete.getPlace();
        if (place.geometry) {
            document.getElementById('lat').value = place.geometry.location.lat();
            document.getElementById('lng').value = place.geometry.location.lng();
        }
    });
}


        document.addEventListener('DOMContentLoaded', initAutocomplete);


        document.addEventListener('DOMContentLoaded', initMap);

        function initMap() {
    const map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 40.7128, lng: -74.0060 }, // Default center (New York)
        zoom: 5
    });

    fetch("dashboard.php?action=fetch_reports")
        .then(response => response.json())
        .then(data => {
            data.forEach(report => {
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(report.lat), lng: parseFloat(report.lng) },
                    map: map,
                    title: report.crime_type,
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `<h4>${report.crime_type}</h4>
                              <p>${report.description}</p>
                              <p><strong>Location:</strong> ${report.location}</p>`,
                });

                marker.addListener("click", () => {
                    infoWindow.open(map, marker);
                });
            });
        })
        .catch(error => console.error("Error fetching report data:", error));
}


function submitReport() {
    const formData = new FormData(document.getElementById('reportForm'));

    fetch('dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('submissionMessage').innerHTML = '<div class="message success">Report submitted successfully!</div>';
        document.getElementById('reportForm').reset();
    })
    .catch(error => {
        document.getElementById('submissionMessage').innerHTML = '<div class="message error">Error submitting report. Please try again.</div>';
        console.error('Error:', error);
    });
}
// Function to toggle content
function showContent(sectionId) {
    const sections = document.querySelectorAll('.content-section'); // All content sections
    const buttons = document.querySelectorAll('.menu-btn'); // All sidebar buttons
    const blankSection = document.getElementById('blankSection'); // Blank section

    // Hide all content sections
    sections.forEach(section => section.classList.remove('active'));
    blankSection.style.display = 'none';

    // Remove the active class from all buttons
    buttons.forEach(button => button.classList.remove('active'));

    // Show the selected content section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.add('active'); // Make selected section visible
    }

    // Highlight the selected button
    event.target.classList.add('active');
}


       
         // Initially show blank section
         document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('blankSection').style.display = 'flex'; // Show blank section initially

    const buttons = document.querySelectorAll('.menu-btn');
    buttons.forEach(button => {
        button.addEventListener('click', (event) => {
            const sectionId = event.target.getAttribute('onclick').match(/'([^']+)'/)[1];
            showContent(sectionId);
        });
    });
});
document.addEventListener('DOMContentLoaded', () => {
    // Fetch existing threads on page load
    fetchThreads();
});

function fetchThreads() {
    fetch('dashboard.php?action=fetch_threads')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('threads-container');
            container.innerHTML = ''; // Clear existing content

            if (data.error) {
                container.innerHTML = `<p>Error: ${data.error}</p>`;
                return;
            }

            if (data.length === 0) {
                container.innerHTML = '<p>No discussions found. Start a new one!</p>';
                return;
            }

            data.forEach(thread => {
                const threadDiv = document.createElement('div');
                threadDiv.classList.add('thread');
                threadDiv.innerHTML = `
                    <h4>${thread.title}</h4>
                    <p>${thread.content}</p>
                    <small>Posted on: ${new Date(thread.created_at).toLocaleString()}</small>
                `;
                container.appendChild(threadDiv);
            });
        })
        .catch(error => console.error('Error fetching threads:', error));
}

function submitThread() {
    const title = document.getElementById('threadTitle').value;
    const content = document.getElementById('threadContent').value;

    if (!title || !content) {
        alert('Please fill in both the title and content.');
        return;
    }

    fetch('dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'create_thread',
            title: title,
            content: content,
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(`Error: ${data.error}`);
            } else {
                alert(data.success);
                document.getElementById('newThreadForm').reset();
                fetchThreads(); // Refresh the thread list
            }
        })
        .catch(error => console.error('Error submitting thread:', error));
}

    </script>


</body>
</html>
