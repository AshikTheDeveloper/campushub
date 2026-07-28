<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background-color: #eef2f5; }
        .welcome-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        
        .btn { display: inline-block; margin-top: 15px; margin-right: 10px; text-decoration: none; color: white; padding: 10px 18px; border-radius: 4px; font-weight: bold; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #bd2130; }
    </style>
</head>
<body>

<div class="welcome-card">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! 🎉</h1>
    <p>You have successfully logged into the <b>CampusHub</b> Admin Panel.</p>
    
    <!-- Navigation Buttons -->
    <a href="students.php" class="btn btn-primary">🎓 Manage Students</a>
    <a href="logout.php" class="btn btn-danger">Logout</a>
</div>

</body>
</html>