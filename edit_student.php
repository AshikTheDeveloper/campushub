<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$student = null;


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student) {
        die("Student not found!");
    }
} else {
    header("Location: students.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);

    if (!empty($student_id) && !empty($name) && !empty($email) && !empty($department)) {
        $stmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, email = ?, department = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $student_id, $name, $email, $department, $id);

        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div style='color: green; font-weight: bold;'>Student updated successfully!</div>";
            header("Location: students.php");
            exit();
        } else {
            $message = "<div style='color: red; font-weight: bold;'>Update Error: " . $conn->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div style='color: red; font-weight: bold;'>All fields are required!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f6f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        .nav { margin-bottom: 20px; }
        .nav a { text-decoration: none; color: #007bff; font-weight: bold; }
        form { background: #eef2f5; padding: 15px; border-radius: 5px; }
        input[type="text"], input[type="email"] { padding: 8px; margin: 5px 0 15px 0; width: 100%; box-sizing: border-box; }
        input[type="submit"] { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav">
        <a href="students.php">⬅ Back to Student List</a>
    </div>

    <h2>✏️ Edit Student Information</h2>
    <?php echo $message; ?>

    <form action="edit_student.php?id=<?php echo $id; ?>" method="POST">
        <label>Student ID:</label>
        <input type="text" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" required>

        <label>Full Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>

        <label>Department:</label>
        <input type="text" name="department" value="<?php echo htmlspecialchars($student['department']); ?>" required>

        <input type="submit" name="update_student" value="Update Student">
    </form>
</div>

</body>
</html>