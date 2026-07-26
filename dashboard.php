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
        a.logout-btn { display: inline-block; margin-top: 15px; text-decoration: none; color: white; background: #dc3545; padding: 8px 15px; border-radius: 4px; }
        a.logout-btn:hover { background: #bd2130; }
    </style>
</head>
<body>

<div class="welcome-card">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! 🎉</h1>
    <p>You have successfully logged into the <b>CampusHub</b> Admin Panel.</p>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

</body>
</html>