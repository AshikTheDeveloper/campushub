<?php
session_start();
require_once 'config.php';

// শুধুমাত্র Admin এক্সেস করতে পারবে
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// স্টুডেন্টদের লিস্ট আনা
$students_res = $conn->query("SELECT student_id, name, department FROM students ORDER BY student_id ASC");

// কোর্সের লিস্ট আনা
$courses_res = $conn->query("SELECT id, course_code, course_name, credit FROM courses ORDER BY course_code ASC");

// এনরোল সাবমিট প্রসেস
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_student'])) {
    $student_id = trim($_POST['student_id']);
    $course_id  = intval($_POST['course_id']);

    if (!empty($student_id) && !empty($course_id)) {
        // ডুপ্লিকেট চেক
        $check_stmt = $conn->prepare("SELECT id FROM course_enrollments WHERE student_id = ? AND course_id = ?");
        $check_stmt->bind_param("si", $student_id, $course_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "❌ Student is already enrolled in this course!";
        } else {
            $stmt = $conn->prepare("INSERT INTO course_enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("si", $student_id, $course_id);
            if ($stmt->execute()) {
                $message = "✅ Student enrolled in course successfully!";
            } else {
                $error = "❌ Failed to enroll student!";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $error = "❌ Please select both student and course!";
    }
}

// এনরোলমেন্ট ডিলিট
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM course_enrollments WHERE id = ?");
    $del_stmt->bind_param("i", $del_id);
    if ($del_stmt->execute()) {
        $message = "🗑️ Course enrollment removed!";
    }
    $del_stmt->close();
}

// সকল এনরোলমেন্টের লিস্ট
$enrollments_res = $conn->query("
    SELECT ce.id, ce.student_id, c.course_code, c.course_name, c.credit, ce.enrolled_at 
    FROM course_enrollments ce
    JOIN courses c ON ce.course_id = c.id
    ORDER BY ce.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Course Enrollment - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-back:hover { background: #2d3748; }

        .grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 5px; }
        select { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #3182ce; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #2b6cb0; }

        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f7fafc; color: #4a5568; }
        .btn-del { color: #e53e3e; text-decoration: none; font-weight: bold; }
        .btn-del:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🎓 Student Course Enrollment</h2>
        <a href="admin_dashboard.php" class="btn-back">⬅️ Back to Admin Dashboard</a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="grid">
        <!-- Assign Course Form -->
        <div class="card">
            <h3>➕ Enroll Student to Course</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Select Student:</label>
                    <select name="student_id" required>
                        <option value="">-- Choose Student --</option>
                        <?php if($students_res && $students_res->num_rows > 0): ?>
                            <?php while($s = $students_res->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($s['student_id']); ?>">
                                    <?php echo htmlspecialchars($s['student_id']) . " - " . htmlspecialchars($s['name'] ?? 'Student'); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Course:</label>
                    <select name="course_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php if($courses_res && $courses_res->num_rows > 0): ?>
                            <?php while($c = $courses_res->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['course_code']) . " - " . htmlspecialchars($c['course_name']) . " (" . $c['credit'] . " Cr)"; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" name="enroll_student" class="btn-submit">Assign Course 🚀</button>
            </form>
        </div>

        <!-- Enrollment List -->
        <div class="card">
            <h3>📖 Current Student Course Registrations</h3>
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Credit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($enrollments_res && $enrollments_res->num_rows > 0): ?>
                        <?php while ($row = $enrollments_res->fetch_assoc()): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($row['student_id']); ?></b></td>
                                <td><b><?php echo htmlspecialchars($row['course_code']); ?></b></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo number_format($row['credit'], 2); ?></td>
                                <td>
                                    <a href="enroll_student.php?delete_id=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('Remove student from this course?');">Drop ❌</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#718096;">No student enrolled in any course yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>