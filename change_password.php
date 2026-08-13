<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_pass = md5(trim($_POST['current_password']));
    $new_pass = trim($_POST['new_password']);
    $confirm_pass = trim($_POST['confirm_password']);

    if (!empty($current_pass) && !empty($new_pass) && !empty($confirm_pass)) {
        if ($new_pass !== $confirm_pass) {
            $error = "❌ New passwords do not match!";
        } elseif (strlen($new_pass) < 6) {
            $error = "❌ Password must be at least 6 characters long!";
        } else {
            
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && $user['password'] === $current_pass) {
                
                $hashed_new_pass = md5($new_pass);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_new_pass, $user_id);

                if ($update_stmt->execute()) {
                    $message = "✅ Password updated successfully!";
                } else {
                    $error = "❌ Failed to update password!";
                }
                $update_stmt->close();
            } else {
                $error = "❌ Incorrect current password!";
            }
        }
    } else {
        $error = "❌ Please fill in all fields!";
    }
}


$back_link = "student_dashboard.php";
if ($role === 'admin') {
    $back_link = "admin_dashboard.php";
} elseif ($role === 'teacher') {
    $back_link = "teacher_dashboard.php";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 90vh; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .card h2 { margin-top: 0; color: #1a202c; font-size: 20px; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 5px; }
        input[type="password"] { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .btn-submit { width: 100%; background: #3182ce; color: white; border: none; padding: 11px; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #2b6cb0; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #718096; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-back:hover { text-decoration: underline; color: #2d3748; }
        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center; }
    </style>
</head>
<body>

<div class="card">
    <h2>🔒 Change Password</h2>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" required>
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="••••••••" required>
        </div>

        <button type="submit" name="update_password" class="btn-submit">Update Password 🚀</button>
    </form>

    <a href="<?php echo $back_link; ?>" class="btn-back">⬅ Back to Dashboard</a>
</div>

</body>
</html>