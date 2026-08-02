<?php
session_start();
require_once 'config.php';

// সিকিউরিটি: শুধু এডমিন এক্সেস পাবে
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

// নতুন স্টুডেন্ট যুক্ত করার প্রসেসিং
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $password = '123456'; // বাই ডিফল্ট পাসওয়ার্ড

    if (!empty($student_id) && !empty($name)) {
        
        // ১. আগে চেক করব students টেবিলে অলরেডি এই আইডি আছে কিনা
        $st_check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
        $st_check->bind_param("s", $student_id);
        $st_check->execute();
        $st_res = $st_check->get_result();

        if ($st_res->num_rows > 0) {
            $message = "⚠️ Student ID already exists in Student List!";
        } else {
            // ২. চেক করব users টেবিলে আছে কিনা
            $user_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $user_check->bind_param("s", $student_id);
            $user_check->execute();
            $user_res = $user_check->get_result();

            // যদি users টেবিলে না থাকে, তবে users টেবিলে ইনসার্ট করব
            if ($user_res->num_rows == 0) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'student';

                $user_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($user_stmt) {
                    $user_stmt->bind_param("ssss", $student_id, $email, $hashed_password, $role);
                    $user_stmt->execute();
                    $user_stmt->close();
                }
            }
            $user_check->close();

            // ৩. এবার students টেবিলে ইনসার্ট করব
            $student_stmt = $conn->prepare("INSERT INTO students (student_id, name, email, department) VALUES (?, ?, ?, ?)");
            if (!$student_stmt) {
                die("❌ Students Table Query Error: " . $conn->error);
            }
            $student_stmt->bind_param("ssss", $student_id, $name, $email, $department);
            
            if ($student_stmt->execute()) {
                $message = "✅ Student added successfully! (Default Password: 123456)";
            } else {
                $message = "⚠️ Error inserting into students table: " . $conn->error;
            }
            $student_stmt->close();
        }
        $st_check->close();
    } else {
        $message = "❌ Student ID and Name are required!";
    }
}

// স্টুডেন্ট লিস্ট ফেচ করা
$students_result = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; }
        .top-nav { margin-bottom: 20px; }
        .top-nav a { text-decoration: none; font-weight: bold; margin-right: 15px; }
        .logout { color: #dc3545; float: right; }
        
        .form-box { background: #e9ecef; padding: 15px; border-radius: 6px; margin-bottom: 25px; }
        .form-group { margin-bottom: 10px; }
        label { font-weight: bold; display: block; margin-bottom: 3px; font-size: 13px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .action-btn { text-decoration: none; font-weight: bold; font-size: 13px; margin-right: 8px; }
        .edit-btn { color: #007bff; }
        .delete-btn { color: #dc3545; }
        .msg { padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <a href="dashboard.php">⬅ Back to Dashboard</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <h2>🎓 Manage Students</h2>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Add Student Form -->
    <div class="form-box">
        <h3>Add New Student</h3>
        <form action="students.php" method="POST">
            <div class="form-group">
                <label>Student ID:</label>
                <input type="text" name="student_id" placeholder="e.g. 2026-CSE-001" required>
            </div>
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="name" placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" placeholder="e.g. john@example.com">
            </div>
            <div class="form-group">
                <label>Department:</label>
                <input type="text" name="department" placeholder="e.g. CSE">
            </div>
            <button type="submit" name="add_student" class="btn">Add Student</button>
        </form>
    </div>

    <!-- Student List Table -->
    <h3>Student List</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($students_result && $students_result->num_rows > 0): ?>
                <?php while ($row = $students_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td>
                            <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="action-btn edit-btn">Edit</a>
                            <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No students found!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>