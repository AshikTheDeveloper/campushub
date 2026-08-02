<?php
session_start();
require_once 'config.php';

// সিকিউরিটি চেক: শুধু এডমিন এক্সেস পাবে
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$student_id_pk = $_GET['id'] ?? null; // students টেবিলের প্রাইমারি কি (id)
$student = null;

// স্টুডেন্টের বর্তমান তথ্য নিয়ে আসা
if ($student_id_pk) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id_pk);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// যদি স্টুডেন্ট খুঁজে না পাওয়া যায়
if (!$student) {
    header("Location: students.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $password = trim($_POST['password']);

    if (!empty($name)) {
        // ১. students টেবিল আপডেট করা
        $stmt1 = $conn->prepare("UPDATE students SET name = ?, email = ?, department = ? WHERE id = ?");
        $stmt1->bind_param("sssi", $name, $email, $department, $student_id_pk);
        $stmt1->execute();
        $stmt1->close();

        // ২. users টেবিল আপডেট করা (ইমেইল এবং যদি পাসওয়ার্ড পরিবর্তন করা হয়)
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE username = ?");
            $stmt2->bind_param("sss", $email, $hashed_password, $student['student_id']);
        } else {
            $stmt2 = $conn->prepare("UPDATE users SET email = ? WHERE username = ?");
            $stmt2->bind_param("ss", $email, $student['student_id']);
        }
        $stmt2->execute();
        $stmt2->close();

        $message = "✅ Student updated successfully in both tables!";

        // ফর্মের মানগুলো তাৎক্ষণিক রিফ্রেশ করা
        $student['name'] = $name;
        $student['email'] = $email;
        $student['department'] = $department;
    } else {
        $message = "❌ Full Name is required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 500px; background: white; margin: 30px auto; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[disabled] { background-color: #e9ecef; cursor: not-allowed; }
        .btn { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; }
        .btn:hover { background: #0056b3; }
        .msg { margin-bottom: 15px; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ Edit Student Information</h2>
    
    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Student ID (Cannot be changed):</label>
            <input type="text" value="<?php echo htmlspecialchars($student['student_id']); ?>" disabled>
        </div>

        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Email Address:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
        </div>

        <div class="form-group">
            <label>Department:</label>
            <input type="text" name="department" value="<?php echo htmlspecialchars($student['department']); ?>">
        </div>

        <div class="form-group">
            <label>New Password (Leave blank to keep current):</label>
            <input type="password" name="password" placeholder="Enter new password if changing">
        </div>

        <button type="submit" name="update_student" class="btn">Update Student</button>
    </form>

    <a href="students.php" class="back-link">⬅ Back to Student List</a>
</div>

</body>
</html>