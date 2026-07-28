<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // পাসওয়ার্ড ভ্যালিডেশন (Plain or Hashed)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // রোল অনুযায়ী সঠিক ড্যাশবোর্ডে রিডাইরেক্ট (Case-Insensitive Check)
                $role = strtolower(trim($user['role']));

                if ($role === 'admin') {
                    header("Location: dashboard.php");
                } elseif ($role === 'teacher') {
                    header("Location: teacher_dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "User not found!";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 320px; }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; background: #007bff; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        input[type="submit"]:hover { background: #0056b3; }
        .error { color: red; font-size: 14px; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>🎓 CampusHub Login</h2>
    <?php if($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label>Username / ID:</label>
        <input type="text" name="username" placeholder="Enter ID or Username" required>

        <label>Password:</label>
        <input type="password" name="password" placeholder="Enter Password" required>

        <input type="submit" value="Login">
    </form>
</div>

</body>
</html>