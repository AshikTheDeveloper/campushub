<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$student_info = null;


$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student_info = $result->fetch_assoc();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .box { background: #eef2f5; padding: 20px; border-radius: 8px; border-left: 5px solid #007bff; }
        .box h3 { margin-top: 0; color: #007bff; }
    </style>
</head>
<body>

<div class="container">
    <div class="card header">
        <h2>👨‍🎓 Student Dashboard</h2>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    
    <div class="card">
        <h3>Welcome, <?php echo htmlspecialchars($student_info['name'] ?? $username); ?>! 👋</h3>
        <p><b>Student ID:</b> <?php echo htmlspecialchars($username); ?></p>
        <p><b>Email:</b> <?php echo htmlspecialchars($student_info['email'] ?? 'N/A'); ?></p>
        <p><b>Department:</b> <?php echo htmlspecialchars($student_info['department'] ?? 'N/A'); ?></p>
    </div>

    
    <div class="grid">
        <div class="box">
            <h3>📚 Enrolled Courses</h3>
            <p>View your registered subjects and course materials.</p>
        </div>
        <div class="box">
            <h3>📅 Class Routine</h3>
            <p>Check your weekly class schedules and exam dates.</p>
        </div>
        <div class="box">
            <h3>📊 Academic Results</h3>
            <p>View semester marks and CGPA overview.</p>
        </div>
    </div>
</div>

</body>
</html>