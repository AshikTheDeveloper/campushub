<?php
session_start();
require_once 'config.php';

// Check teacher login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_username = $_SESSION['username'];
$message = '';
$error = '';

// Fetch Assigned Courses for this specific teacher
$courses_query = "SELECT course_code, course_name FROM courses WHERE assigned_teacher = ?";
$stmt_courses = $conn->prepare($courses_query);
$stmt_courses->bind_param("s", $teacher_username);
$stmt_courses->execute();
$courses_res = $stmt_courses->get_result();

// ১. Marks Entry/Submit Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_marks'])) {
    $student_id = trim($_POST['student_id']);
    $course_info = trim($_POST['course_info']); // Contains "course_code|course_name"
    $marks = intval($_POST['marks']);

    if (!empty($student_id) && !empty($course_info)) {
        // Extract course_code and course_name from selected dropdown value
        list($course_code, $course_name) = explode('|', $course_info);

        // Grade Calculation Logic
        $grade = 'F';
        if ($marks >= 80) $grade = 'A+';
        elseif ($marks >= 75) $grade = 'A';
        elseif ($marks >= 70) $grade = 'A-';
        elseif ($marks >= 65) $grade = 'B+';
        elseif ($marks >= 60) $grade = 'B';
        elseif ($marks >= 55) $grade = 'B-';
        elseif ($marks >= 50) $grade = 'C+';
        elseif ($marks >= 45) $grade = 'C';
        elseif ($marks >= 40) $grade = 'D';

        $stmt = $conn->prepare("INSERT INTO marks (student_id, course_code, course_name, marks, grade, posted_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $student_id, $course_code, $course_name, $marks, $grade, $teacher_username);

        if ($stmt->execute()) {
            $message = "✅ Marks uploaded successfully!";
        } else {
            $error = "❌ Failed to upload marks: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "❌ All fields are required!";
    }
}

// ২. Marks Delete Logic
if (isset($_GET['delete_mark_id'])) {
    $mark_id = intval($_GET['delete_mark_id']);
    $del_stmt = $conn->prepare("DELETE FROM marks WHERE id = ?");
    $del_stmt->bind_param("i", $mark_id);
    if ($del_stmt->execute()) {
        $message = "🗑️ Mark record deleted successfully!";
    } else {
        $error = "❌ Failed to delete record!";
    }
    $del_stmt->close();
}

// Fetch students based on assigned course batches for this teacher
$students_query = "
    SELECT DISTINCT s.student_id, s.name 
    FROM students s
    JOIN courses c ON s.batch = c.batch
    WHERE c.assigned_teacher = ?
    ORDER BY s.student_id ASC
";
$stmt_std = $conn->prepare($students_query);
$stmt_std->bind_param("s", $teacher_username);
$stmt_std->execute();
$students_res = $stmt_std->get_result();

// Fetch marks posted by this teacher only
$stmt_marks = $conn->prepare("SELECT * FROM marks WHERE posted_by = ? ORDER BY id DESC");
$stmt_marks->bind_param("s", $teacher_username);
$stmt_marks->execute();
$marks_list = $stmt_marks->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Marks - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1050px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-back:hover { background: #2d3748; }
        
        .main-grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; font-size: 16px; }

        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 4px; }
        input, select { width: 100%; padding: 9px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; font-size: 13px; }
        .btn-submit { width: 100%; background: #38a169; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 5px; font-size: 14px; }
        .btn-submit:hover { background: #2f855a; }

        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f7fafc; color: #4a5568; }
        .badge-grade { background: #3182ce; color: white; padding: 2px 8px; border-radius: 10px; font-weight: bold; font-size: 11px; }
        .btn-del { color: #e53e3e; text-decoration: none; font-weight: bold; }
        .btn-del:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📊 Manage Student Marks & Results</h2>
        <a href="teacher_dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="main-grid">
        <!-- Left: Upload Form -->
        <div class="card">
            <h3>➕ Upload / Assign Marks</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Select Student ID:</label>
                    <select name="student_id" required>
                        <option value="">-- Choose Student --</option>
                        <?php if ($students_res && $students_res->num_rows > 0): ?>
                            <?php while ($st = $students_res->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($st['student_id']); ?>">
                                    <?php echo htmlspecialchars($st['student_id'] . " - " . $st['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No Students Found in Assigned Batches</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Course:</label>
                    <select name="course_info" required>
                        <option value="">-- Choose Course --</option>
                        <?php if ($courses_res && $courses_res->num_rows > 0): ?>
                            <?php while ($crs = $courses_res->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($crs['course_code'] . '|' . $crs['course_name']); ?>">
                                    <?php echo htmlspecialchars($crs['course_code'] . " - " . $crs['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No Courses Assigned</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Obtained Marks (out of 100):</label>
                    <input type="number" name="marks" min="0" max="100" placeholder="e.g. 85" required>
                </div>

                <button type="submit" name="submit_marks" class="btn-submit">Submit Marks 🚀</button>
            </form>
        </div>

        <!-- Right: Submitted Marks Table -->
        <div class="card">
            <h3>📋 Submitted Marks History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($marks_list && $marks_list->num_rows > 0): ?>
                        <?php while ($m = $marks_list->fetch_assoc()): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($m['student_id']); ?></b></td>
                                <td><?php echo htmlspecialchars($m['course_code']); ?></td>
                                <td><?php echo $m['marks']; ?> / 100</td>
                                <td><span class="badge-grade"><?php echo $m['grade']; ?></span></td>
                                <td>
                                    <a href="manage_marks.php?delete_mark_id=<?php echo $m['id']; ?>" class="btn-del" onclick="return confirm('Delete this mark entry?');">Delete ❌</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #a0aec0; padding: 15px;">No marks entry found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>