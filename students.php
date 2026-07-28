<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// ১. ডিলিট করার লজিক (DELETE)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "<div style='color: green; font-weight: bold;'>Student deleted successfully!</div>";
    } else {
        $message = "<div style='color: red; font-weight: bold;'>Delete Error: " . $conn->error . "</div>";
    }
}

// ২. স্টুডেন্ট যোগ করার লজিক (CREATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);

    if (!empty($student_id) && !empty($name) && !empty($email) && !empty($department)) {
        
        $stmt = $conn->prepare("INSERT INTO students (student_id, name, email, department) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssss", $student_id, $name, $email, $department);

            if ($stmt->execute()) {
                $message = "<div style='color: green; font-weight: bold;'>Student added successfully!</div>";
            } else {
                $message = "<div style='color: red; font-weight: bold;'>Execution Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div style='color: red; font-weight: bold;'>SQL Prepare Error: " . $conn->error . "</div>";
        }

    } else {
        $message = "<div style='color: red; font-weight: bold;'>All fields are required!</div>";
    }
}

// ৩. স্টুডেন্টদের ডাটা নিয়ে আসার লজিক (READ)
$result = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f6f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .nav { margin-bottom: 20px; }
        .nav a { text-decoration: none; color: #007bff; font-weight: bold; margin-right: 15px; }
        form { margin-bottom: 30px; background: #eef2f5; padding: 15px; border-radius: 5px; }
        input[type="text"], input[type="email"] { padding: 8px; margin: 5px 0 15px 0; width: 100%; box-sizing: border-box; }
        input[type="submit"] { background: #28a745; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .btn-edit { color: #007bff; font-weight: bold; text-decoration: none; margin-right: 10px; }
        .btn-delete { color: red; font-weight: bold; text-decoration: none; }
        .btn-edit:hover, .btn-delete:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <a href="dashboard.php">⬅ Back to Dashboard</a>
        <a href="logout.php" style="color: red;">Logout</a>
    </div>

    <h2>🎓 Manage Students</h2>
    <?php echo $message; ?>

    <!-- স্টুডেন্ট যোগ করার ফর্ম -->
    <form action="students.php" method="POST">
        <h3>Add New Student</h3>
        <label>Student ID:</label>
        <input type="text" name="student_id" placeholder="e.g. 2026-CSE-001" required>

        <label>Full Name:</label>
        <input type="text" name="name" placeholder="e.g. John Doe" required>

        <label>Email:</label>
        <input type="email" name="email" placeholder="e.g. john@example.com" required>

        <label>Department:</label>
        <input type="text" name="department" placeholder="e.g. CSE" required>

        <input type="submit" name="add_student" value="Add Student">
    </form>

    <!-- স্টুডেন্ট লিস্ট দেখানোর টেবিল -->
    <h3>Student List</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Action</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['student_id'] ?? $row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                    <td>
                        <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                        <a href="students.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No students found. Add one above!</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>