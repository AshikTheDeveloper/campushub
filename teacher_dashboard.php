<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .box { background: #eef2f5; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; }
        .box h3 { margin-top: 0; color: #28a745; }
    </style>
</head>
<body>

<div class="container">
    <div class="card header">
        <h2>👨‍🏫 Teacher Dashboard</h2>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    
    <div class="card">
        <h3>Welcome, Professor <?php echo htmlspecialchars($username); ?>! 👋</h3>
        <p>You are logged in as a <b>Faculty Member</b>.</p>
    </div>

    
    <div class="grid">
        <div class="box">
            <h3>📋 Student Marks & Results</h3>
            <p>Upload exam marks, assign grades, and submit results.</p>
        </div>
        <div class="box">
            <h3>📅 Class Attendance</h3>
            <p>Take daily class attendance and view attendance reports.</p>
        </div>
        <div class="box">
            <h3>📂 Course Materials</h3>
            <p>Upload lecture notes, assignments, and class notices.</p>
        </div>
    </div>
</div>

</body>
</html>