<?php
session_start();


// Check if this is an AJAX request to fetch threads
if (isset($_GET['action']) && $_GET['action'] === 'fetch_threads') {
    header("Content-Type: application/json");

    $threads_query = "
        SELECT t.id, u.name AS user_name, t.title, t.content, t.created_at 
        FROM threads t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC";
    try {
        $stmt = $conn->prepare($threads_query);
        $stmt->execute();
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($threads);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database query failed: " . $e->getMessage()]);
    }
    exit();
}


// Check if the admin is logged in
if (!isset($_SESSION['admin'])) {
    echo "<script>alert('Please login first!'); window.location.href='adminlogin.php';</script>";
    exit();
}

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

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    echo "<script>alert('You have been logged out!'); window.location.href='adminlogin.php';</script>";
    exit();
}

// Fetch admin details
$admin_email = $_SESSION['admin'];
$query = "SELECT firstname FROM admins WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_firstname = $admin['firstname'];

// Fetch statistics
$total_users_query = "SELECT COUNT(*) AS total_users FROM users";
$total_users_result = $conn->query($total_users_query)->fetch_assoc()['total_users'];

$total_reports_query = "SELECT COUNT(*) AS total_reports FROM reports";
$total_reports_result = $conn->query($total_reports_query)->fetch_assoc()['total_reports'];

$reports_24hr_query = "SELECT COUNT(*) AS reports_24hr FROM reports WHERE created_at >= NOW() - INTERVAL 1 DAY";
$reports_24hr_result = $conn->query($reports_24hr_query)->fetch_assoc()['reports_24hr'];

$user_logins_query = "SELECT COUNT(*) AS user_logins FROM user_logins"; // Replace with your login tracking table
$user_logins_result = $conn->query($user_logins_query)->fetch_assoc()['user_logins'];

// Fetch reports for table
$reports_query = "SELECT * FROM reports ORDER BY created_at DESC";
$reports_result = $conn->query($reports_query);

// Handle report deletion
if (isset($_POST['delete_report'])) {
    $report_id = $_POST['report_id'];
    $delete_query = "DELETE FROM reports WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $report_id);
    if ($delete_stmt->execute()) {
        echo "<script>alert('Report deleted successfully!'); window.location.href='admindashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to delete the report.');</script>";
    }
}

// Handle thread update
if (isset($_POST['edit_thread'])) {
    $thread_id = $_POST['thread_id'];
    $new_title = $_POST['new_title'];
    $new_content = $_POST['new_content'];
    
    $update_query = "UPDATE threads SET title = ?, content = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $new_title, $new_content, $thread_id);
    if ($update_stmt->execute()) {
        echo "<script>alert('Thread updated successfully!'); window.location.href='admindashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to update thread.');</script>";
    }
}

// Handle thread deletion
if (isset($_POST['delete_thread'])) {
    $thread_id = $_POST['thread_id'];
    $delete_query = "DELETE FROM threads WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $thread_id);
    if ($delete_stmt->execute()) {
        echo "<script>alert('Thread deleted successfully!'); window.location.href='admindashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to delete thread.');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            background: #eaf4f9;
        }
        .header {
            background: linear-gradient(45deg, #0073e6, #00bfa6);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .nav-buttons {
            display: flex;
            justify-content: space-around;
            background: #0073e6;
            padding: 15px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .nav-buttons button {
            background: linear-gradient(45deg, #00bfa6, #0073e6);
            border: none;
            padding: 15px 25px;
            cursor: pointer;
            border-radius: 10px;
            font-size: 1rem;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        .nav-buttons button:hover {
            transform: translateY(-5px);
            background: linear-gradient(45deg, #0073e6, #00bfa6);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        .content {
            padding: 30px;
        }
        .content-section {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .content-section.active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background: #0073e6;
            color: white;
        }
        .action-btn {
            background: #00bfa6;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            background: #0073e6;
        }
        canvas {
            margin: 30px auto;
        }
        .logout-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background-color: #ff4d4d;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 5px;
        font-size: 1rem;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        background-color: #cc0000;
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    }

    .action-btn {
    background: #00bfa6;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 5px;
    margin: 5px;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: #0073e6;
}

    </style>
</head>
<body>
    <div class="header">

    <h1>     Environment Monitoring and Reporting System</h1>
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($admin_firstname); ?>!</p>
        <button onclick="window.location.href='admindashboard.php?logout=true'" class="logout-btn">Logout</button>

     
    </div>

    <div class="nav-buttons">
        <button onclick="showSection('users')">Total Users</button>
        <button onclick="showSection('logins')">User Logins</button>
        <button onclick="showSection('recent-reports')">Reports in 24 Hours</button>
        <button onclick="showSection('all-reports')">Total Reports</button>
        <button onclick="showSection('ai-predictions')">Impact Prediction</button>
        <button onclick="showSection('user-threads')">Posted Threads</button>

    </div>

    <div class="content">
        <div id="users" class="content-section">
            <h2>Total Registered Users</h2>
            <p><?php echo $total_users_result; ?> users are registered in the system.</p>
        </div>

        <div id="logins" class="content-section">
            <h2>Total User Logins</h2>
            <p><?php echo $user_logins_result; ?> logins have been recorded.</p>
        </div>

        <div id="recent-reports" class="content-section">
            <h2>Reports Submitted in the Last 24 Hours</h2>
            <p><?php echo $reports_24hr_result; ?> reports were submitted in the last 24 hours.</p>
        </div>

        <div id="all-reports" class="content-section">
            <h2>Total Reports in the Database</h2>
            <p><?php echo $total_reports_result; ?> reports are currently in the database.</p>
            <h3>All Reports</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Crime Type</th>
                    <th>Severity</th>
                    <th>Location</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                <?php while ($report = $reports_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $report['id']; ?></td>
                        <td><?php echo htmlspecialchars($report['crime_type']); ?></td>
                        <td><?php echo htmlspecialchars($report['severity']); ?></td>
                        <td><?php echo htmlspecialchars($report['location']); ?></td>
                        <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                <button type="submit" name="delete_report" class="action-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div id="ai-predictions" class="content-section">
            <h2>Climate Impact Predictions</h2>
            <canvas id="climateChart" width="400" height="200"></canvas>
            <h3>Predicted Impact Data</h3>
            <table>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Location</th>
                        <th>Impact (sq.m)</th>
                        <th>Predicted Impact</th>
                    </tr>
                </thead>
                <tbody id="prediction-table-body">
                    <!-- Data will be dynamically populated -->
                </tbody>
            </table>
        </div>


        <div id="user-threads" class="content-section">
    <h2>User Posted Threads</h2>
    <table>
        <thead>
            <tr>
                <th>Thread ID</th>
                <th>User</th>
                <th>Title</th>
                <th>Content</th>
                <th>Posted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="threads-table-body">
            <!-- Data will be dynamically inserted here -->
        </tbody>
    </table>
</div>


<script>
   document.addEventListener("DOMContentLoaded", function () {
    const threadsTableBody = document.getElementById("threads-table-body");

    // Fetch thread data dynamically
    fetch("admindashboard.php?action=fetch_threads") // Same file with action parameter
        .then(response => {
            if (!response.ok) {
                throw new Error("Failed to fetch thread data");
            }
            return response.json();
        })
        .then(data => {
            // Clear the table body
            threadsTableBody.innerHTML = "";

            // Populate the table with thread data
            if (data.length > 0) {
                data.forEach(thread => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${thread.id}</td>
                        <td>${thread.user_name}</td>
                        <td>${thread.title}</td>
                        <td>${thread.content}</td>
                        <td>${thread.created_at}</td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="thread_id" value="${thread.id}">
                                <input type="text" name="new_title" placeholder="Edit Title" required>
                                <input type="text" name="new_content" placeholder="Edit Content" required>
                                <button type="submit" name="edit_thread" class="action-btn">Edit</button>
                                <button type="submit" name="delete_thread" class="action-btn">Delete</button>
                            </form>
                        </td>
                    `;
                    threadsTableBody.appendChild(row);
                });
            } else {
                threadsTableBody.innerHTML = `<tr><td colspan="6">No threads posted yet.</td></tr>`;
            }
        })
        .catch(error => {
            console.error("Error fetching thread data:", error);
            threadsTableBody.innerHTML = `<tr><td colspan="6">Error fetching thread data.</td></tr>`;
        });
});

</script>


        
    </div>

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
                    <td>${item.report.address || "Unknown"}</td> <!-- Use 'address' instead of 'location' -->
                    <td>${item.report.impact || 0} sq.m</td>
                    <td>${item.prediction}</td>
                `;
                tableBody.appendChild(row);
            });
            renderChart(data);
        })
        .catch((error) => console.error("Error fetching predictions:", error));
});

        function renderChart(data) {
            const ctx = document.getElementById('climateChart').getContext('2d');

            const labels = data.map(item => item.report.location || "Unknown");
            const values = data.map(item => item.report.impact || 0);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Impact Predictions",
                        data: values,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function showSection(sectionId) {
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    const activeSection = document.getElementById(sectionId);
    if (activeSection) {
        activeSection.classList.add('active');
    }
}



    </script>
</body>
</html>
